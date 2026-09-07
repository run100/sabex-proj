<?php

namespace App\Support;

use App\Models\TradeUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TradeAuthRedirect
{
    public static function storeReturnTo(Request $request): void
    {
        $return = (string) $request->query('return_to', $request->input('return_to', ''));
        if ($return !== '') {
            $request->session()->put('roblox_oauth_return', self::safe($return));
        }
    }

    public static function pullReturn(Request $request, string $fallback = '/trading'): string
    {
        return self::safe((string) $request->session()->pull('roblox_oauth_return', $fallback));
    }

    public static function safe(string $path): string
    {
        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return '/';
        }

        return $path;
    }

    public static function login(Request $request, TradeUser $user, string $fallback = '/trading'): RedirectResponse
    {
        $ip = AccessLogService::ip($request);
        $user->last_login_at = now();
        AccessLogService::assign($user, 'last_login_ip', $ip);
        $user->save();
        AccessLogService::write('trade_user', (int) $user->id, 'login', $ip, 'trade_user', (int) $user->id, $request);

        auth('trades')->login($user, true);
        $request->session()->regenerate();

        return redirect(self::pullReturn($request, $fallback));
    }
}
