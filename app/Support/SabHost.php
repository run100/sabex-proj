<?php

namespace App\Support;

use Illuminate\Http\Request;

class SabHost
{
    public static function role(?string $host = null): string
    {
        $host ??= request()->getHost();

        return match ($host) {
            (string) config('sab.hosts.admin') => 'admin',
            (string) config('sab.hosts.trades') => 'trades',
            default => 'www',
        };
    }

    public static function isAdmin(?string $host = null): bool
    {
        return self::role($host) === 'admin';
    }

    public static function isTrades(?string $host = null): bool
    {
        return self::role($host) === 'trades';
    }

    public static function host(string $role): string
    {
        return (string) config('sab.hosts.'.$role, '');
    }

    public static function origin(string $role, ?Request $request = null): string
    {
        $request ??= request();
        $host = self::host($role);
        $scheme = $request->getScheme() ?: 'https';
        $port = (int) $request->getPort();
        $origin = $scheme.'://'.$host;
        if ($port > 0 && ! in_array($port, [80, 443], true)) {
            $origin .= ':'.$port;
        }

        return $origin;
    }

    /**
     * @return list<string>
     */
    public static function trustedHosts(): array
    {
        return array_values(array_unique(array_filter([
            self::host('www'),
            self::host('admin'),
            self::host('trades'),
            'localhost',
            '127.0.0.1',
        ])));
    }
}
