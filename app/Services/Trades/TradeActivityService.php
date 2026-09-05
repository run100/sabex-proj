<?php

namespace App\Services\Trades;

use App\Models\TradeListing;
use App\Models\TradeUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TradeActivityService
{
    /**
     * @return LengthAwarePaginator<int, TradeListing>
     */
    public function forUser(TradeUser $user, string $status = 'all', int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        $query = $this->forUserQuery($user)->with(['owner', 'counterparty', 'items.traits']);

        $pending = [TradeListing::STATUS_PENDING, TradeListing::STATUS_PENDING_CONFIRMATION];
        $query = match ($status) {
            'open' => $query->where('status', TradeListing::STATUS_OPEN),
            'pending' => $query->whereIn('status', $pending),
            'completed' => $query->where('status', TradeListing::STATUS_COMPLETED),
            'failed' => $query->where('status', TradeListing::STATUS_FAILED),
            'disputed' => $query->where('status', TradeListing::STATUS_DISPUTED),
            default => $query->whereNotIn('status', [TradeListing::STATUS_HIDDEN]),
        };

        return $query->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(max(1, min($limit, 50)), ['*'], 'page', $page);
    }

    /**
     * @return array{all: int, pending: int, completed: int, failed: int}
     */
    public function countsForUser(TradeUser $user): array
    {
        $rows = $this->forUserQuery($user)
            ->whereNotIn('status', [TradeListing::STATUS_HIDDEN])
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'all' => (int) $rows->sum(),
            'pending' => (int) $rows->get(TradeListing::STATUS_PENDING, 0)
                + (int) $rows->get(TradeListing::STATUS_PENDING_CONFIRMATION, 0),
            'completed' => (int) $rows->get(TradeListing::STATUS_COMPLETED, 0),
            'failed' => (int) $rows->get(TradeListing::STATUS_FAILED, 0),
        ];
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
