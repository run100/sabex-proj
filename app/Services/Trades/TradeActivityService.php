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
        $query = TradeListing::query()
            ->with(['owner', 'counterparty', 'items.traits'])
            ->where(function (Builder $q) use ($user): void {
                $q->where('owner_user_id', $user->id)
                    ->orWhere('counterparty_user_id', $user->id);
            });

        $pending = [TradeListing::STATUS_PENDING, TradeListing::STATUS_PENDING_CONFIRMATION];
        $query = match ($status) {
            'open' => $query->where('status', TradeListing::STATUS_OPEN),
            'pending' => $query->whereIn('status', $pending),
            'completed' => $query->where('status', TradeListing::STATUS_COMPLETED),
            'failed' => $query->where('status', TradeListing::STATUS_FAILED),
            'disputed' => $query->where('status', TradeListing::STATUS_DISPUTED),
            default => $query->whereNotIn('status', [TradeListing::STATUS_HIDDEN]),
        };

        return $query->orderByDesc('updated_at')->orderByDesc('id')
            ->paginate(max(1, min($limit, 50)), ['*'], 'page', $page);
    }
}
