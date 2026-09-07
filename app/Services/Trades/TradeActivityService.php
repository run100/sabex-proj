<?php

namespace App\Services\Trades;

use App\Models\TradeJoinRequest;
use App\Models\TradeListing;
use App\Models\TradeUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TradeActivityService
{
    /**
     * @return LengthAwarePaginator<int, TradeListing>
     */
    public function forUser(TradeUser $user, string $status = 'received', int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        $query = $this->scopedQuery($user, $status)->with(['owner', 'counterparty', 'items.traits']);

        return $query->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(max(1, min($limit, 50)), ['*'], 'page', $page);
    }

    /**
     * @return array{ads: int, received: int, sent: int, pending: int, completed: int, expired: int}
     */
    public function countsForUser(TradeUser $user): array
    {
        return [
            'ads' => $this->scopedQuery($user, 'ads')->count(),
            'received' => $this->scopedQuery($user, 'received')->count(),
            'sent' => $this->scopedQuery($user, 'sent')->count(),
            'pending' => $this->scopedQuery($user, 'pending')->count(),
            'completed' => $this->scopedQuery($user, 'completed')->count(),
            'expired' => $this->scopedQuery($user, 'expired')->count(),
        ];
    }

    /**
     * @return Builder<TradeListing>
     */
    private function scopedQuery(TradeUser $user, string $status): Builder
    {
        $pending = [TradeListing::STATUS_PENDING, TradeListing::STATUS_PENDING_CONFIRMATION];

        return match ($status) {
            'ads' => TradeListing::query()
                ->where('owner_user_id', $user->id)
                ->whereNotIn('status', [TradeListing::STATUS_HIDDEN]),
            'sent' => TradeListing::query()
                ->where('owner_user_id', '!=', $user->id)
                ->whereHas('joinRequests', function (Builder $q) use ($user): void {
                    $q->where('requester_user_id', $user->id)
                        ->where('status', TradeJoinRequest::STATUS_REQUESTED);
                }),
            'expired' => TradeListing::query()
                ->where('status', TradeListing::STATUS_EXPIRED)
                ->where(function (Builder $q) use ($user): void {
                    $q->where('owner_user_id', $user->id)
                        ->orWhere('counterparty_user_id', $user->id)
                        ->orWhereHas('joinRequests', function (Builder $join) use ($user): void {
                            $join->where('requester_user_id', $user->id);
                        });
                }),
            'received' => TradeListing::query()
                ->where('owner_user_id', $user->id)
                ->whereHas('joinRequests', function (Builder $q): void {
                    $q->where('status', TradeJoinRequest::STATUS_REQUESTED);
                }),
            'open' => $this->forUserQuery($user)->where('status', TradeListing::STATUS_OPEN),
            'pending' => $this->forUserQuery($user)->whereIn('status', $pending),
            'completed' => $this->forUserQuery($user)->where('status', TradeListing::STATUS_COMPLETED),
            'failed' => $this->forUserQuery($user)->where('status', TradeListing::STATUS_FAILED),
            'disputed' => $this->forUserQuery($user)->where('status', TradeListing::STATUS_DISPUTED),
            'all' => $this->forUserQuery($user)->whereNotIn('status', [TradeListing::STATUS_HIDDEN]),
            default => TradeListing::query()
                ->where('owner_user_id', $user->id)
                ->whereHas('joinRequests', function (Builder $q): void {
                    $q->where('status', TradeJoinRequest::STATUS_REQUESTED);
                }),
        };
    }

    /**
     * @return Builder<TradeListing>
     */
    private function forUserQuery(TradeUser $user): Builder
    {
        return TradeListing::query()
            ->where(function (Builder $q) use ($user): void {
                $q->where('owner_user_id', $user->id)
                    ->orWhere('counterparty_user_id', $user->id);
            });
    }
}
