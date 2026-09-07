<?php

namespace App\Support;

class TradeLocalAuth
{
    public static function enabled(): bool
    {
        return app()->environment(['local', 'testing']);
    }

    public static function robloxSub(string $username): string
    {
        return 'local:'.$username;
    }
}
