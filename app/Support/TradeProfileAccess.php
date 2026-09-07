<?php

namespace App\Support;

use App\Models\TradeUser;
use Illuminate\Database\Eloquent\Builder;

class TradeProfileAccess
{
    public static function canAccessProfile(?TradeUser $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->account_status === TradeUser::STATUS_ACTIVE
            && $user->moderation_status !== TradeUser::MODERATION_RESTRICTED
            && $user->profile_visibility === TradeUser::VISIBILITY_PUBLIC;
    }

    public static function canShowPublicIdentity(?TradeUser $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->account_status === TradeUser::STATUS_ACTIVE
            && $user->moderation_status !== TradeUser::MODERATION_RESTRICTED;
    }

    public static function constrainPublicIdentity(Builder $query): Builder
    {
        return $query
            ->where('account_status', TradeUser::STATUS_ACTIVE)
            ->where(function (Builder $inner): void {
                $inner->where('moderation_status', '!=', TradeUser::MODERATION_RESTRICTED)
                    ->orWhereNull('moderation_status');
            });
    }
}
