<?php

namespace App\Support;

use App\Models\TradeListing;
use App\Models\TradeUser;

class TradeIndexEligibility
{
    public static function verifiedCompletedCount(TradeUser $user): int
    {
        return TradeListing::query()
            ->where('status', TradeListing::STATUS_COMPLETED)
            ->where(function ($q) use ($user): void {
                $q->where('owner_user_id', $user->id)
                    ->orWhere('counterparty_user_id', $user->id);
            })
            ->count();
    }

    public static function activeCount(TradeUser $user): int
    {
        return TradeListing::query()
            ->where('owner_user_id', $user->id)
            ->where('status', TradeListing::STATUS_OPEN)
            ->count();
    }

    public static function isEligible(TradeUser $user): bool
    {
        if (! TradeProfileAccess::canAccessProfile($user)) {
            return false;
        }
        $completed = self::verifiedCompletedCount($user);
        $active = self::activeCount($user);

        return $completed >= 3 || ($active + $completed) >= 5;
    }
}
