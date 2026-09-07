<?php

namespace App\Support;

class TradeEmailAuth
{
    public const VERIFY_PROMPT = 'Check your email and open the verification link to finish.';

    public const EMAIL_ALREADY_REGISTERED = 'That email is already registered.';

    public static function loginAllowed(): bool
    {
        return (bool) config('sab-trades.allow_email_login', false)
            || app()->isLocal();
    }

    public static function registerAllowed(): bool
    {
        return app()->isLocal();
    }

    public static function bindAllowed(): bool
    {
        return (bool) config('sab-trades.allow_email_bind', false);
    }
}
