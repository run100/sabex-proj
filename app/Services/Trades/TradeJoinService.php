<?php

namespace App\Services\Trades;

use App\Exceptions\TradeException;
use App\Models\TradeJoinRequest;
use App\Models\TradeListing;
use App\Models\TradeUser;
use App\Models\TradeUserBlock;
use App\Support\TradeTextPolicy;
use Illuminate\Support\Facades\DB;

class TradeJoinService
{
    public function __construct(
        private readonly TradeListingService $listings,
        private readonly TradeNotificationService $notifications,
        private readonly TradeInteractionQuotaService $quota,
    ) {}

    public function join(TradeUser $user, TradeListing $listing, mixed $note = null): TradeJoinRequest
    {
        $this->assertActive($user);
        $note = TradeTextPolicy::optional($note);

        return DB::transaction(function () use ($user, $listing, $note): TradeJoinRequest {
            $lockedListing = TradeListing::query()
                ->with('owner')
                ->where('id', $listing->id)
                ->lockForUpdate()
                ->first();
            if ($lockedListing === null || $lockedListing->owner === null) {
                throw TradeException::notFound();
            }

            $owner = $lockedListing->owner;
            $this->quota->lockPair($user, $owner);
            $user->refresh();
            $this->assertActive($user);

            if ((int) $lockedListing->owner_user_id === (int) $user->id) {
                throw TradeException::forbidden('CANNOT_JOIN_OWN_TRADE', 'You cannot join your own trade.');
            }
            if (! $lockedListing->isOpen()) {
                throw TradeException::conflict('TRADE_NOT_OPEN', 'This trade is no longer open.');
            }
            if ($this->blocked($user, $owner)) {
                throw TradeException::forbidden('USER_BLOCKED', 'This trade is not available.');
            }

            $existing = TradeJoinRequest::query()
                ->where('listing_id', $lockedListing->id)
                ->where('requester_user_id', $user->id)
                ->where('status', TradeJoinRequest::STATUS_REQUESTED)
                ->first();
            if ($existing) {
                throw TradeException::conflict('JOIN_ALREADY_EXISTS', 'You already requested to join this trade.');
            }

            $quota = $this->quota->contactQuota($lockedListing, $user, $owner);
            if ($quota['remaining'] < 1) {
                throw TradeException::conflict(
                    'JOIN_LIMIT_REACHED',
                    'You can send at most '.$quota['limit'].' messages or trade requests to this user.'
                );
            }

            $hourly = TradeJoinRequest::query()
                ->where('requester_user_id', $user->id)
                ->where('created_at', '>=', now()->subHour())
                ->count();
            if ($hourly >= (int) config('sab-trades.join_limit_per_hour', 30)) {
                throw TradeException::rateLimited();
            }

            $request = TradeJoinRequest::query()->create([
                'listing_id' => $lockedListing->id,
                'requester_user_id' => $user->id,
                'owner_user_id' => $lockedListing->owner_user_id,
                'status' => TradeJoinRequest::STATUS_REQUESTED,
                'note' => $note,
                'expires_at' => $lockedListing->expires_at,
            ]);
            $this->listings->event($lockedListing, 'join_requested', $user, ['join_public_id' => $request->public_id]);
            $this->notifications->notify(
                $owner,
                'join_requested',
                'New join request',
                ($user->display_name ?: $user->username).' asked to join your trade.',
                $lockedListing,
                $request,
                $user,
            );

            return $request;
        });
    }

    /**
     * @return array{limit: int, sent: int, remaining: int}
     */
    public function contactQuota(TradeListing $listing, TradeUser $user, TradeUser $owner): array
    {
        return $this->quota->contactQuota($listing, $user, $owner);
    }

    public function cancel(TradeUser $user, TradeJoinRequest $request): TradeJoinRequest
    {
        $this->assertActive($user);
        if ((int) $request->requester_user_id !== (int) $user->id) {
            throw TradeException::forbidden('JOIN_REQUEST_NOT_FOUND', 'You cannot cancel this request.');
        }
        if (! $request->isActive()) {
            throw TradeException::conflict('JOIN_REQUEST_ALREADY_RESOLVED', 'This join request is already resolved.');
        }
        $request->status = TradeJoinRequest::STATUS_CANCELLED;
        $request->cancelled_at = now();
        $request->save();
        $this->listings->event($request->listing, 'join_cancelled', $user);

        return $request;
    }

    public function accept(TradeUser $owner, TradeJoinRequest $request): TradeListing
    {
        $this->assertActive($owner);

        return DB::transaction(function () use ($owner, $request): TradeListing {
            $listing = TradeListing::query()->where('id', $request->listing_id)->lockForUpdate()->first();
            if ($listing === null) {
                throw TradeException::notFound();
            }
            if ((int) $listing->owner_user_id !== (int) $owner->id) {
                throw TradeException::forbidden('ONLY_OWNER_CAN_ACCEPT', 'Only the owner can accept a join request.');
            }
            if (! $listing->isOpen() || $listing->counterparty_user_id !== null) {
                throw TradeException::conflict('TRADE_ALREADY_PENDING', 'This trade is no longer open.');
            }
            $lockedRequest = TradeJoinRequest::query()->where('id', $request->id)->lockForUpdate()->first();
            if ($lockedRequest === null || ! $lockedRequest->isActive()) {
                throw TradeException::conflict('JOIN_REQUEST_ALREADY_RESOLVED', 'This join request is already resolved.');
            }

            $lockedRequest->status = TradeJoinRequest::STATUS_ACCEPTED;
            $lockedRequest->accepted_at = now();
            $lockedRequest->save();

            TradeJoinRequest::query()
                ->where('listing_id', $listing->id)
                ->where('id', '!=', $lockedRequest->id)
                ->where('status', TradeJoinRequest::STATUS_REQUESTED)
                ->get()
                ->each(function (TradeJoinRequest $other) use ($listing, $owner): void {
                    $other->status = TradeJoinRequest::STATUS_AUTO_REJECTED;
                    $other->rejected_at = now();
                    $other->save();
                    $this->notifications->notify(
                        $other->requester,
                        'join_rejected',
                        'Join request not accepted',
                        'The owner accepted someone else.',
                        $listing,
                        $other,
                        $owner,
                    );
                });

            $hours = (int) config('sab-trades.trade_expire_hours', 72);
            $listing->status = TradeListing::STATUS_PENDING;
            $listing->counterparty_user_id = $lockedRequest->requester_user_id;
            $listing->accepted_at = now();
            $listing->pending_at = now();
            $listing->expires_at = now()->addHours($hours);
            $listing->save();

            $this->listings->event($listing, 'join_accepted', $owner, ['join_public_id' => $lockedRequest->public_id]);
            $this->listings->event($listing, 'trade_pending', $owner);
            $this->notifications->notify(
                $lockedRequest->requester,
                'join_accepted',
                'Join request accepted',
                'Go complete the trade in Roblox, then confirm here.',
                $listing,
                $lockedRequest,
                $owner,
            );
            $this->notifications->notify(
                $owner,
                'trade_pending',
                'Trade is pending',
                'Finish the swap in Steal a Brainrot, then confirm the result.',
                $listing,
                $lockedRequest,
                $lockedRequest->requester,
            );

            return $listing->fresh(['owner', 'counterparty', 'items.traits']) ?? $listing;
        });
    }

    public function reject(TradeUser $owner, TradeJoinRequest $request): TradeJoinRequest
    {
        $this->assertActive($owner);
        if ((int) $request->owner_user_id !== (int) $owner->id) {
            throw TradeException::forbidden('ONLY_OWNER_CAN_ACCEPT', 'Only the owner can reject a join request.');
        }
        if (! $request->isActive()) {
            throw TradeException::conflict('JOIN_REQUEST_ALREADY_RESOLVED', 'This join request is already resolved.');
        }
        $request->status = TradeJoinRequest::STATUS_REJECTED;
        $request->rejected_at = now();
        $request->save();
        $this->listings->event($request->listing, 'join_rejected', $owner);
        $this->notifications->notify(
            $request->requester,
            'join_rejected',
            'Join request rejected',
            'The owner declined this request.',
            $request->listing,
            $request,
            $owner,
        );

        return $request;
    }

    /**
     * @return list<TradeJoinRequest>
     */
    public function forListing(TradeUser $owner, TradeListing $listing): array
    {
        if ((int) $listing->owner_user_id !== (int) $owner->id) {
            throw TradeException::forbidden('ONLY_OWNER_CAN_ACCEPT', 'Only the owner can view join requests.');
        }

        return TradeJoinRequest::query()
            ->with('requester')
            ->where('listing_id', $listing->id)
            ->orderByDesc('id')
            ->get()
            ->all();
    }

    public function findPublic(string $publicId): TradeJoinRequest
    {
        $request = TradeJoinRequest::query()->with(['listing', 'requester', 'owner'])->where('public_id', $publicId)->first();
        if ($request === null) {
            throw TradeException::notFound('JOIN_REQUEST_NOT_FOUND', 'Join request not found.');
        }

        return $request;
    }

    public function blocked(TradeUser $a, TradeUser $b): bool
    {
        return TradeUserBlock::query()
            ->where(function ($q) use ($a, $b): void {
                $q->where('user_id', $a->id)->where('blocked_user_id', $b->id);
            })
            ->orWhere(function ($q) use ($a, $b): void {
                $q->where('user_id', $b->id)->where('blocked_user_id', $a->id);
            })
            ->exists();
    }

    private function assertActive(TradeUser $user): void
    {
        if (! $user->isActive()) {
            throw TradeException::banned();
        }
    }
}
