<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class TradeSchema
{
    public static function ready(): bool
    {
        return Schema::hasTable('seo_trade_users') && Schema::hasTable('seo_trade_listings');
    }
}
