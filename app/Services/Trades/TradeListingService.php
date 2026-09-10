<?php

namespace App\Services\Trades;

use App\Exceptions\TradeException;
use App\Models\TradeEvent;
use App\Models\TradeJoinRequest;
use App\Models\TradeListing;
use App\Models\TradeListingItem;
use App\Models\TradeListingItemTrait;
use App\Models\TradeUser;
use App\Support\AccessLogService;
use App\Support\TradeProfileAccess;
use App\Support\TradeSchema;
use App\Support\TradeTextPolicy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
     * @param  mixed  $note  Optional public listing note.
     */
    public function create(TradeUser $user, array $offering, array $lookingFor, mixed $note = null, ?string $ip = null): TradeListing
    {
        $this->assertActive($user);
        $this->assertCanPost($user);
        $note = TradeTextPolicy::optional($note);

        $snapshot = $this->valuation->snapshot($offering, $lookingFor);

        return DB::transaction(function () use ($user, $snapshot, $note, $ip): TradeListing {
            TradeUser::query()->where('id', $user->id)->lockForUpdate()->first();
            $this->assertPostLimits($user);

            $listing = TradeListing::query()->create([
                'owner_user_id' => $user->id,
                'status' => TradeListing::STATUS_OPEN,
                'result_snapshot' => $snapshot['wfl'],
                'offering_value_snapshot' => $snapshot['offering_total'],
                'looking_value_snapshot' => $snapshot['looking_total'],
                'value_difference_snapshot' => $snapshot['difference'],
                'difference_percent_snapshot' => $snapshot['difference_percent'],
                'note' => $note,
                'expires_at' => now()->addHours((int) config('sab-trades.trade_expire_hours', 72)),
                ...AccessLogService::attrs('seo_trade_listings', [
                    'posted_ip' => $ip,
                ]),
            ]);

            $this->writeItems($listing, $snapshot['offering']);
            $this->writeItems($listing, $snapshot['looking_for']);
            $this->event($listing, 'trade_posted', $user);
            AccessLogService::write('trade_user', (int) $user->id, 'publish', $ip, 'trade_listing', (int) $listing->id);

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
        if (! app(TradeModerationService::class)->isModerator($actor)) {
            throw TradeException::forbidden('AUTH_REQUIRED', 'Moderator only.');
        }
        $listing->status = TradeListing::STATUS_HIDDEN;
        $listing->save();
        $this->event($listing, 'trade_hidden', $actor);

        return $listing;
    }

    public function hideByAdmin(TradeListing $listing): TradeListing
    {
        if ($listing->status === TradeListing::STATUS_HIDDEN) {
            throw TradeException::invalid('TRADE_ALREADY_HIDDEN', 'This trade is already hidden.');
        }

        $previous = $listing->status;
        $listing->status = TradeListing::STATUS_HIDDEN;
        $listing->save();
        $this->event($listing, 'trade_hidden', null, [
            'admin' => true,
            'previous_status' => $previous,
        ]);

        return $listing;
    }

    public function unhide(TradeListing $listing): TradeListing
    {
        if ($listing->status !== TradeListing::STATUS_HIDDEN) {
            throw TradeException::invalid('TRADE_NOT_HIDDEN', 'This trade is not hidden.');
        }

        $listing->status = $this->statusBeforeHide($listing);
        $listing->save();
        $this->event($listing, 'trade_unhidden', null, ['admin' => true]);

        return $listing;
    }

    public function forceClose(TradeListing $listing): TradeListing
    {
        if (! $listing->isOpen() && ! $listing->isPendingLike() && $listing->status !== TradeListing::STATUS_HIDDEN) {
            throw TradeException::invalid('TRADE_NOT_CLOSABLE', 'This trade can no longer be force closed.');
        }

        return DB::transaction(function () use ($listing): TradeListing {
            $listing->status = TradeListing::STATUS_CANCELLED;
            $listing->cancelled_at = now();
            $listing->save();

            TradeJoinRequest::query()
                ->where('listing_id', $listing->id)
                ->where('status', TradeJoinRequest::STATUS_REQUESTED)
                ->update([
                    'status' => TradeJoinRequest::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                ]);

            $this->event($listing, 'trade_cancelled', null, ['admin' => true]);

            $listing->load(['owner', 'counterparty']);
            foreach ([$listing->owner, $listing->counterparty] as $user) {
                if ($user) {
                    $this->notifications->notify(
                        $user,
                        'trade_cancelled',
                        'Trade closed',
                        'An administrator closed this trade.',
                        $listing,
                    );
                }
            }

            return $listing;
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function recent(array $filters = []): LengthAwarePaginator
    {
        return $this->filteredQuery($filters, [TradeListing::STATUS_OPEN])
            ->paginate($this->limit((int) ($filters['limit'] ?? 20)), ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    public function publicOpenCount(): int
    {
        return $this->basePublicQuery([TradeListing::STATUS_OPEN])->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function pending(array $filters = []): LengthAwarePaginator
    {
        return $this->filteredQuery($filters, [
            TradeListing::STATUS_PENDING,
            TradeListing::STATUS_PENDING_CONFIRMATION,
        ], true)
            ->paginate($this->limit((int) ($filters['limit'] ?? 20)), ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

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
            ->with(['owner', 'counterparty', 'items.traits', 'items.seoItem', 'events.actor', 'joinRequests.requester', 'confirmations'])
            ->where('public_id', $publicId)
            ->first();
        if ($listing === null || $listing->status === TradeListing::STATUS_HIDDEN) {
            throw TradeException::notFound();
        }
        if (! TradeProfileAccess::canShowPublicIdentity($listing->owner)) {
            throw TradeException::notFound();
        }
        if ($listing->counterparty && ! TradeProfileAccess::canShowPublicIdentity($listing->counterparty)) {
            throw TradeException::notFound();
        }

        return $listing;
    }

    public function recordView(TradeListing $listing): void
    {
        $listing->increment('views_count');
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
    private function filteredQuery(array $filters, array $statuses, bool $requireCounterparty = false): Builder
    {
        $query = $this->basePublicQuery($statuses, $requireCounterparty)
            ->with(['owner', 'counterparty', 'items.traits']);

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

        $query = $this->applyPinSort($query);

        return match ((string) ($filters['sort'] ?? 'newest')) {
            'value_desc' => $query->orderByDesc('looking_value_snapshot')->orderByDesc('id'),
            'value_asc' => $query->orderBy('looking_value_snapshot')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };
    }

    /**
     * @param  list<string>  $statuses
     */
    private function basePublicQuery(array $statuses, bool $requireCounterparty = false): Builder
    {
        $query = TradeListing::query()
            ->whereIn('status', $statuses)
            ->whereHas('owner', fn (Builder $q) => TradeProfileAccess::constrainPublicIdentity($q));
        if ($requireCounterparty) {
            $query->whereHas('counterparty', fn (Builder $q) => TradeProfileAccess::constrainPublicIdentity($q));
        } else {
            $query->where(function (Builder $outer): void {
                $outer->whereNull('counterparty_user_id')
                    ->orWhereHas('counterparty', fn (Builder $q) => TradeProfileAccess::constrainPublicIdentity($q));
            });
        }

        return $query;
    }

    private function applyPinSort(Builder $query): Builder
    {
        if (! Schema::hasColumn('seo_trade_listings', 'is_top')) {
            return $query;
        }

        return $query
            ->orderByDesc('is_top')
            ->orderByDesc('is_hot')
            ->orderByDesc('sort_order');
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
        $daily = TradeListing::query()
            ->where('owner_user_id', $user->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        if ($daily >= (int) config('sab-trades.post_trade_limit_per_day', 2)) {
            throw TradeException::rateLimited();
        }
    }

    private function assertActive(TradeUser $user): void
    {
        if (! $user->isActive()) {
            throw TradeException::banned();
        }
    }

    private function assertCanPost(TradeUser $user): void
    {
        if (! $user->canPost()) {
            throw TradeException::forbidden(
                'POSTING_NOT_APPROVED',
                'This account is waiting for posting approval.'
            );
        }
    }

    private function statusBeforeHide(TradeListing $listing): string
    {
        $event = TradeEvent::query()
            ->where('listing_id', $listing->id)
            ->where('event_type', 'trade_hidden')
            ->orderByDesc('id')
            ->first();
        $previous = is_array($event?->metadata) ? ($event->metadata['previous_status'] ?? null) : null;
        $allowed = [
            TradeListing::STATUS_OPEN,
            TradeListing::STATUS_PENDING,
            TradeListing::STATUS_PENDING_CONFIRMATION,
            TradeListing::STATUS_COMPLETED,
            TradeListing::STATUS_FAILED,
            TradeListing::STATUS_DISPUTED,
            TradeListing::STATUS_CANCELLED,
            TradeListing::STATUS_EXPIRED,
        ];
        if (is_string($previous) && in_array($previous, $allowed, true)) {
            return $previous;
        }
        if ($listing->counterparty_user_id) {
            return $listing->confirmations()->exists()
                ? TradeListing::STATUS_PENDING_CONFIRMATION
                : TradeListing::STATUS_PENDING;
        }

        return TradeListing::STATUS_OPEN;
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
