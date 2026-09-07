<?php

namespace App\Support;

class TradePaths
{
    public const ULID = '[0-9A-HJKMNP-TV-Za-hjkmnp-tv-z]{26}';

    public static function marketplace(): string
    {
        return '/trading';
    }

    public static function create(): string
    {
        return '/trading/new';
    }

    public static function pending(): string
    {
        return '/trading/pending';
    }

    public static function completed(): string
    {
        return '/trading/completed';
    }

    public static function show(string $ulid): string
    {
        return '/trading/'.$ulid;
    }

    public static function profile(string $profileId): string
    {
        return '/profile/'.$profileId;
    }

    public static function account(): string
    {
        return '/user';
    }

    public static function offers(): string
    {
        return '/user/offers';
    }

    public static function notifications(): string
    {
        return '/notifications';
    }

    public static function robloxLogin(?string $returnTo = null): string
    {
        $path = '/auth/roblox';
        if ($returnTo !== null && $returnTo !== '') {
            $path .= '?return_to='.rawurlencode($returnTo);
        }

        return $path;
    }

    public static function logout(): string
    {
        return '/logout';
    }

    public static function apiTrades(): string
    {
        return '/api/v1/trading/trades';
    }

    public static function apiTrade(string $ulid): string
    {
        return '/api/v1/trading/trades/'.$ulid;
    }

    public static function apiJoin(string $ulid): string
    {
        return '/api/v1/trading/join-requests/'.$ulid;
    }
}
