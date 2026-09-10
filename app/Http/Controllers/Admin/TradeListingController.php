<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\TradeException;
use App\Http\Controllers\Controller;
use App\Models\TradeListing;
use App\Models\TradeListingItem;
use App\Services\Trades\TradeListingService;
use App\Support\TradeSchema;
use App\Support\TradeTextPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class TradeListingController extends Controller
{
    /**
     * @var list<string>
     */
    private const STATUSES = [
        TradeListing::STATUS_OPEN,
        TradeListing::STATUS_PENDING,
        TradeListing::STATUS_PENDING_CONFIRMATION,
        TradeListing::STATUS_COMPLETED,
        TradeListing::STATUS_FAILED,
        TradeListing::STATUS_DISPUTED,
        TradeListing::STATUS_CANCELLED,
        TradeListing::STATUS_EXPIRED,
        TradeListing::STATUS_HIDDEN,
    ];

    public function __construct(private readonly TradeListingService $listings) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);

        $data = $request->validate([
            'status' => ['sometimes', 'nullable', 'string', Rule::in(self::STATUSES)],
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);
        $status = trim((string) ($data['status'] ?? ''));
        $q = trim((string) ($data['q'] ?? ''));

        $listings = TradeListing::query()
            ->with(['owner:id,username,email,profile_id,avatar_url', 'counterparty:id,username,email,profile_id'])
            ->withCount([
                'offeringItems',
                'lookingItems',
            ])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('public_id', 'like', '%'.$q.'%')
                        ->orWhereHas('owner', function ($owner) use ($q): void {
                            $owner->where('username', 'like', '%'.$q.'%')
                                ->orWhere('email', 'like', '%'.$q.'%');
                        });
                });
            })
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return response()->json([
            'counts' => $this->counts(),
            'listings' => $listings->map(fn (TradeListing $listing): array => $this->serializeList($listing))->all(),
        ]);
    }

    public function show(int $listing): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);

        $with = [
            'owner:id,username,email,profile_id,avatar_url',
            'counterparty:id,username,email,profile_id',
            'items.traits',
            'confirmations.user:id,username,email',
            'joinRequests.requester:id,username,email',
            'events.actor:id,username',
        ];
        if (Schema::hasTable('seo_trade_reports')) {
            $with[] = 'reports';
        }

        $row = TradeListing::query()
            ->with($with)
            ->withCount(['offeringItems', 'lookingItems'])
            ->findOrFail($listing);

        return response()->json([
            'listing' => $this->serializeDetail($row),
        ]);
    }

    public function update(Request $request, int $listing): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);

        $row = TradeListing::query()->findOrFail($listing);
        $data = $request->validate([
            'status' => ['sometimes', Rule::in([...self::STATUSES, 'unhidden'])],
            'result_snapshot' => ['sometimes', Rule::in(['win', 'fair', 'lose', 'na'])],
            'note' => ['sometimes', 'nullable', 'string', 'max:280'],
            'views_count' => ['sometimes', 'integer', 'min:0'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:999999'],
            'is_hot' => ['sometimes', Rule::in([TradeListing::FLAG_YES, TradeListing::FLAG_NO])],
            'is_top' => ['sometimes', Rule::in([TradeListing::FLAG_YES, TradeListing::FLAG_NO])],
        ]);

        if ($data === []) {
            return response()->json([
                'message' => 'At least one field is required.',
            ], 422);
        }

        $normalizedNote = null;
        if (array_key_exists('note', $data)) {
            try {
                $normalizedNote = TradeTextPolicy::optional($data['note']);
            } catch (TradeException $exception) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], $exception->status);
            }
        }

        $dirty = false;
        if (array_key_exists('status', $data)) {
            try {
                $row = match ($data['status']) {
                    TradeListing::STATUS_CANCELLED => $this->listings->forceClose($row),
                    'unhidden' => $this->listings->unhide($row),
                    TradeListing::STATUS_HIDDEN => $this->listings->hideByAdmin($row),
                    default => tap($row, function (TradeListing $listing) use ($data, &$dirty): void {
                        $listing->status = $data['status'];
                        $dirty = true;
                    }),
                };
            } catch (TradeException $exception) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], $exception->status);
            }
        }

        if (array_key_exists('result_snapshot', $data)) {
            $row->result_snapshot = $data['result_snapshot'];
            $dirty = true;
        }
        if (array_key_exists('note', $data)) {
            $row->note = $normalizedNote;
            $dirty = true;
        }
        if (array_key_exists('views_count', $data)) {
            $row->views_count = (int) $data['views_count'];
            $dirty = true;
        }
        if (array_key_exists('expires_at', $data)) {
            $row->expires_at = $data['expires_at'];
            $dirty = true;
        }
        if (array_key_exists('sort_order', $data)) {
            $row->sort_order = (int) $data['sort_order'];
            $dirty = true;
        }
        if (array_key_exists('is_hot', $data)) {
            $row->is_hot = $data['is_hot'];
            $dirty = true;
        }
        if (array_key_exists('is_top', $data)) {
            $row->is_top = $data['is_top'];
            $dirty = true;
        }
        if ($dirty) {
            $row->save();
        }

        return response()->json([
            'listing' => $this->serializeList(
                $row->load(['owner:id,username,email,profile_id,avatar_url', 'counterparty:id,username,email,profile_id'])
                    ->loadCount(['offeringItems', 'lookingItems'])
            ),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        $grouped = TradeListing::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $counts = ['all' => (int) TradeListing::query()->count()];
        foreach (self::STATUSES as $status) {
            $counts[$status] = (int) $grouped->get($status, 0);
        }

        return $counts;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeList(TradeListing $listing): array
    {
        return [
            'id' => $listing->id,
            'public_id' => $listing->public_id,
            'status' => $listing->status,
            'result_snapshot' => $listing->result_snapshot,
            'owner_id' => $listing->owner_user_id,
            'owner_username' => $listing->owner?->username,
            'owner_email' => $listing->owner?->email,
            'owner_profile_id' => $listing->owner?->profile_id,
            'owner_avatar_url' => $listing->owner?->avatar_url,
            'counterparty_id' => $listing->counterparty_user_id,
            'counterparty_username' => $listing->counterparty?->username,
            'counterparty_email' => $listing->counterparty?->email,
            'counterparty_profile_id' => $listing->counterparty?->profile_id,
            'offering_value_snapshot' => $listing->offering_value_snapshot,
            'looking_value_snapshot' => $listing->looking_value_snapshot,
            'value_difference_snapshot' => $listing->value_difference_snapshot,
            'difference_percent_snapshot' => $listing->difference_percent_snapshot,
            'offering_items_count' => (int) ($listing->offering_items_count ?? 0),
            'looking_items_count' => (int) ($listing->looking_items_count ?? 0),
            'views_count' => $listing->views_count,
            'sort_order' => (int) $listing->sort_order,
            'is_hot' => $listing->is_hot,
            'is_top' => $listing->is_top,
            'note' => $listing->note,
            'posted_ip' => $listing->posted_ip,
            'accepted_at' => $listing->accepted_at,
            'pending_at' => $listing->pending_at,
            'completed_at' => $listing->completed_at,
            'failed_at' => $listing->failed_at,
            'cancelled_at' => $listing->cancelled_at,
            'expires_at' => $listing->expires_at,
            'created_at' => $listing->created_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDetail(TradeListing $listing): array
    {
        return [
            ...$this->serializeList($listing),
            'items' => $listing->items
                ->sortBy(fn (TradeListingItem $item): array => [$item->side, $item->slot_no])
                ->values()
                ->map(fn (TradeListingItem $item): array => [
                    'id' => $item->id,
                    'side' => $item->side,
                    'slot_no' => $item->slot_no,
                    'slug' => $item->slug_snapshot,
                    'name' => $item->brainrot_name_snapshot,
                    'image' => $item->image_url_snapshot,
                    'mutation' => $item->mutation_name_snapshot,
                    'base_value' => $item->base_value_snapshot,
                    'final_value' => $item->final_value_snapshot,
                    'income' => $item->final_income_snapshot,
                    'demand' => $item->demand_snapshot,
                    'exist_count' => $item->exist_count_snapshot,
                    'traits' => $item->traits->map(fn ($trait): array => [
                        'name' => $trait->trait_name_snapshot ?: $trait->trait_name,
                        'value_multiplier' => $trait->value_multiplier_snapshot,
                    ])->all(),
                ])->all(),
            'join_requests' => $listing->joinRequests
                ->sortByDesc('id')
                ->values()
                ->map(fn ($join): array => [
                    'id' => $join->id,
                    'public_id' => $join->public_id,
                    'requester_id' => $join->requester_user_id,
                    'requester_username' => $join->requester?->username,
                    'requester_email' => $join->requester?->email,
                    'status' => $join->status,
                    'note' => $join->note,
                    'created_at' => $join->created_at,
                    'accepted_at' => $join->accepted_at,
                    'rejected_at' => $join->rejected_at,
                    'cancelled_at' => $join->cancelled_at,
                ])->all(),
            'confirmations' => $listing->confirmations->map(fn ($row): array => [
                'username' => $row->user?->username,
                'email' => $row->user?->email,
                'confirmation' => $row->confirmation,
                'note' => $row->note,
                'created_at' => $row->created_at,
            ])->all(),
            'events' => $listing->events
                ->sortBy('created_at')
                ->values()
                ->map(fn ($event): array => [
                    'type' => $event->event_type,
                    'at' => $event->created_at,
                    'actor' => $event->actor?->username,
                ])->all(),
            'reports' => ($listing->relationLoaded('reports') ? $listing->reports : collect())
                ->map(fn ($report): array => [
                    'public_id' => $report->public_id,
                    'reason' => $report->reason,
                    'status' => $report->status,
                ])->all(),
        ];
    }
}
