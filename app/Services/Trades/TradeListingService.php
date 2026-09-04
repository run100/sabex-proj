<?php

namespace App\Services\Trades;

use App\Exceptions\TradeException;
use App\Models\TradeEvent;
use App\Models\TradeJoinRequest;
use App\Models\TradeListing;
use App\Models\TradeListingItem;
use App\Models\TradeListingItemTrait;
use App\Models\TradeUser;
use App\Support\TradeSchema;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TradeListingService
{
    public function __construct(
        private readonly TradeValuationService $valuation,
        private readonly TradeNotificationService $notifications,
    ) {}

    public function tablesReady(): bool
    {
        return TradeSchema::ready();
    }

    /**
     * @param  array<int, mixed>  $offering
     * @param  array<int, mixed>  $lookingFor
     */
    public function create(TradeUser $user, array $offering, array $lookingFor, ?string $note = null): TradeListing
    {
        $this->assertActive($user);
        $this->assertPostLimits($user);

        $snapshot = $this->valuation->snapshot($offering, $lookingFor);

        return DB::transaction(function () use ($user, $snapshot, $note): TradeListing {
            $listing = TradeListing::query()->create([
                'owner_user_id' => $user->id,
                'status' => TradeListing::STATUS_OPEN,
                'result_snapshot' => $snapshot['wfl'],
                'offering_value_snapshot' => $snapshot['offering_total'],
                'looking_value_snapshot' => $snapshot['looking_total'],
                'value_difference_snapshot' => $snapshot['difference'],
                'difference_percent_snapshot' => $snapshot['difference_percent'],
                'note' => $note !== null ? mb_substr(trim($note), 0, 280) : null,
                'expires_at' => now()->addHours((int) config('sab-trades.trade_expire_hours', 72)),
            ]);

            $this->writeItems($listing, $snapshot['offering']);
            $this->writeItems($listing, $snapshot['looking_for']);
            $this->event($listing, 'trade_posted', $user);

            return $listing->fresh(['owner', 'items.traits']) ?? $listing;
        });
    }

    public function cancel(TradeUser $user, TradeListing $listing): TradeListing
    {
        $this->assertActive($user);
        if ((int) $listing->owner_user_id !== (int) $user->id) {
            throw TradeException::forbidden('ONLY_OWNER_CAN_ACCEPT', 'Only the owner can cancel this trade.');
        }
        if (! $listing->isOpen()) {
            throw TradeException::conflict('TRADE_NOT_OPEN', 'This trade is no longer open.');
        }

        $listing->status = TradeListing::STATUS_CANCELLED;
        $listing->cancelled_at = now();
        $listing->save();
        $this->event($listing, 'trade_cancelled', $user);

        return $listing;
    }

    public function hide(TradeUser $actor, TradeListing $listing): TradeListing
    {
        $listing->status = TradeListing::STATUS_HIDDEN;
        $listing->save();
        $this->event($listing, 'trade_hidden', $actor);

        return $listing;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function recent(array $filters = []): LengthAwarePaginator
    {
        return $this->filteredQuery($filters, [TradeListing::STATUS_OPEN])
            ->paginate($this->limit((int) ($filters['limit'] ?? 20)), ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function completed(array $filters = []): LengthAwarePaginator
    {
        $query = $this->filteredQuery($filters, [TradeListing::STATUS_COMPLETED]);
        if (! empty($filters['roblox_sub'])) {
            $sub = (string) $filters['roblox_sub'];
            $query->where(function (Builder $outer) use ($sub): void {
                $outer->whereHas('owner', fn (Builder $q) => $q->where('roblox_sub', $sub))
                    ->orWhereHas('counterparty', fn (Builder $q) => $q->where('roblox_sub', $sub));
            });
        }
        if (! empty($filters['username'])) {
            $name = $filters['username'];
            $query->where(function (Builder $outer) use ($name): void {
                $outer->whereHas('owner', fn (Builder $q) => $q->where('username', 'like', '%'.$name.'%'))
                    ->orWhereHas('counterparty', fn (Builder $q) => $q->where('username', 'like', '%'.$name.'%'));
            });
        }

        return $query->paginate($this->limit((int) ($filters['limit'] ?? 20)), ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    public function findPublic(string $publicId): TradeListing
    {
        $listing = TradeListing::query()
            ->with(['owner', 'counterparty', 'items.traits', 'events.actor', 'joinRequests.requester'])
            ->where('public_id', $publicId)
            ->first();
        if ($listing === null || $listing->status === TradeListing::STATUS_HIDDEN) {
            throw TradeException::notFound();
        }

        return $listing;
    }

    public function recordView(TradeListing $listing): void
    {
        $listing->increment('views_count');
        $this->event($listing, 'trade_viewed');
    }

    public function expireDue(): int
    {
        $count = 0;
        TradeListing::query()
            ->where('status', TradeListing::STATUS_OPEN)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->orderBy('id')
            ->each(function (TradeListing $listing) use (&$count): void {
                $listing->status = TradeListing::STATUS_EXPIRED;
                $listing->save();
                TradeJoinRequest::query()
                    ->where('listing_id', $listing->id)
                    ->where('status', TradeJoinRequest::STATUS_REQUESTED)
                    ->update(['status' => TradeJoinRequest::STATUS_EXPIRED]);
                $this->event($listing, 'trade_expired');
                $this->notifications->notify(
                    $listing->owner,
                    'trade_expired',
                    'Trade expired',
                    'Your listing expired before anyone was accepted.',
                    $listing,
                );
                $count++;
            });

        TradeListing::query()
            ->whereIn('status', [TradeListing::STATUS_PENDING, TradeListing::STATUS_PENDING_CONFIRMATION])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->orderBy('id')
            ->each(function (TradeListing $listing) use (&$count): void {
                $listing->status = TradeListing::STATUS_EXPIRED;
                $listing->save();
                $this->event($listing, 'trade_expired');
                foreach ([$listing->owner, $listing->counterparty] as $user) {
                    if ($user) {
                        $this->notifications->notify(
                            $user,
                            'trade_expired',
                            'Pending trade expired',
                            'Confirmation timed out.',
                            $listing,
                        );
                    }
                }
                $count++;
            });

        return $count;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function writeItems(TradeListing $listing, array $items): void
    {
        foreach ($items as $row) {
            $item = TradeListingItem::query()->create([
                'listing_id' => $listing->id,
                'side' => $row['side'],
                'slot_no' => $row['slot_no'],
                'seo_item_id' => $row['seo_item_id'],
                'slug_snapshot' => $row['slug_snapshot'],
                'brainrot_name_snapshot' => $row['brainrot_name_snapshot'],
                'image_url_snapshot' => $row['image_url_snapshot'],
                'seo_item_variant_id' => $row['seo_item_variant_id'],
                'mutation_name_snapshot' => $row['mutation_name_snapshot'],
                'base_value_snapshot' => $row['base_value_snapshot'],
                'mutation_value_multiplier_snapshot' => $row['mutation_value_multiplier_snapshot'],
                'trait_value_multiplier_snapshot' => $row['trait_value_multiplier_snapshot'],
                'final_value_snapshot' => $row['final_value_snapshot'],
                'base_income_snapshot' => $row['base_income_snapshot'],
                'final_income_snapshot' => $row['final_income_snapshot'],
                'demand_snapshot' => $row['demand_snapshot'],
                'exist_count_snapshot' => $row['exist_count_snapshot'],
                'created_at' => now(),
            ]);
            foreach ($row['traits'] as $trait) {
                TradeListingItemTrait::query()->create([
                    'listing_item_id' => $item->id,
                    'trait_name' => $trait['name'],
                    'trait_name_snapshot' => $trait['name'],
                    'value_multiplier_snapshot' => $trait['value_multiplier'],
                    'income_multiplier_snapshot' => $trait['income_multiplier'],
                    'sort_order' => $trait['sort_order'],
                    'created_at' => now(),
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<string>  $statuses
     */
    private function filteredQuery(array $filters, array $statuses): Builder
    {
        $query = TradeListing::query()
            ->with(['owner', 'items.traits'])
            ->whereIn('status', $statuses);

        if (! empty($filters['want_brainrot_id'])) {
            $id = (int) $filters['want_brainrot_id'];
            $query->whereHas('items', fn (Builder $q) => $q->where('side', TradeListingItem::SIDE_LOOKING)->where('seo_item_id', $id));
        }
        if (! empty($filters['have_brainrot_id'])) {
            $id = (int) $filters['have_brainrot_id'];
            $query->whereHas('items', fn (Builder $q) => $q->where('side', TradeListingItem::SIDE_OFFERING)->where('seo_item_id', $id));
        }
        if (isset($filters['min_value']) && $filters['min_value'] !== '') {
            $query->where('looking_value_snapshot', '>=', (float) $filters['min_value']);
        }
        if (isset($filters['max_value']) && $filters['max_value'] !== '') {
            $query->where('looking_value_snapshot', '<=', (float) $filters['max_value']);
        }
        if (! empty($filters['brainrot_id'])) {
            $id = (int) $filters['brainrot_id'];
            $query->whereHas('items', fn (Builder $q) => $q->where('seo_item_id', $id));
        }

        return match ((string) ($filters['sort'] ?? 'newest')) {
            'value_desc' => $query->orderByDesc('looking_value_snapshot')->orderByDesc('id'),
            'value_asc' => $query->orderBy('looking_value_snapshot')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };
    }

    private function assertPostLimits(TradeUser $user): void
    {
        $active = TradeListing::query()
            ->where('owner_user_id', $user->id)
            ->where('status', TradeListing::STATUS_OPEN)
            ->count();
        if ($active >= (int) config('sab-trades.max_active_listings_per_user', 10)) {
            throw TradeException::rateLimited();
        }
        $hourly = TradeListing::query()
            ->where('owner_user_id', $user->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        if ($hourly >= (int) config('sab-trades.post_trade_limit_per_hour', 10)) {
            throw TradeException::rateLimited();
        }
    }

    private function assertActive(TradeUser $user): void
    {
        if (! $user->isActive()) {
            throw TradeException::banned();
        }
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function event(TradeListing $listing, string $type, ?TradeUser $actor = null, ?array $metadata = null): void
    {
        TradeEvent::query()->create([
            'listing_id' => $listing->id,
            'actor_user_id' => $actor?->id,
            'event_type' => $type,
            'metadata' => $metadata,
        ]);
    }

    private function limit(int $limit): int
    {
        return max(1, min($limit === 0 ? 20 : $limit, 50));
    }
}
