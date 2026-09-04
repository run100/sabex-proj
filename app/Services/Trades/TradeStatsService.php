<?php

namespace App\Services\Trades;

use App\Models\TradeListing;
use App\Models\TradeUser;

class TradeStatsService
{
    /**
     * @return array<string, int|float|null>
     */
    public function forUser(TradeUser $user): array
    {
        $owned = TradeListing::query()->where('owner_user_id', $user->id);
        $involved = TradeListing::query()->where(function ($q) use ($user): void {
            $q->where('owner_user_id', $user->id)->orWhere('counterparty_user_id', $user->id);
        });

        $posted = (clone $owned)->count();
        $accepted = (clone $owned)->whereNotNull('counterparty_user_id')->count();
        $joined = TradeListing::query()->where('counterparty_user_id', $user->id)->count();
        $completed = (clone $involved)->where('status', TradeListing::STATUS_COMPLETED)->count();
        $failed = (clone $involved)->where('status', TradeListing::STATUS_FAILED)->count();
        $disputed = (clone $involved)->where('status', TradeListing::STATUS_DISPUTED)->count();
        $closed = $completed + $failed;

        return [
            'trades_posted' => $posted,
            'trades_joined' => $joined,
            'trades_accepted' => $accepted,
            'trades_completed' => $completed,
            'trades_failed' => $failed,
            'trades_disputed' => $disputed,
            'completion_rate' => $closed > 0 ? round($completed / $closed, 4) : null,
        ];
    }
}
