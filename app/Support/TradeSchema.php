<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class TradeSchema
{
    public static function ready(): bool
    {
        return Schema::hasTable('seo_trade_users')
            && Schema::hasTable('seo_trade_listings')
            && Schema::hasColumn('seo_trade_users', 'profile_id')
            && Schema::hasColumn('seo_trade_users', 'profile_visibility')
            && Schema::hasColumn('seo_trade_users', 'moderation_status')
            && Schema::hasColumn('seo_trade_users', 'roblox_user_id');
    }

    public static function identitiesReady(): bool
    {
        return self::ready()
            && Schema::hasTable('seo_trade_auth_accounts')
            && Schema::hasTable('seo_trade_email_codes');
    }
}
