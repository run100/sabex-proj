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
            default => 'www',
        };
    }

    public static function isAdmin(?string $host = null): bool
    {
        return self::role($host) === 'admin';
    }

    public static function host(string $role): string
    {
        if ($role === 'trades') {
            $role = 'www';
        }

        return (string) config('sab.hosts.'.$role, '');
    }

    public static function origin(string $role, ?Request $request = null): string
    {
        if ($role === 'trades') {
            $role = 'www';
        }
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

    public static function tradesBase(?Request $request = null): string
    {
        return rtrim(self::origin('www', $request), '/').'/trading';
    }

    /**
     * @return list<string>
     */
    public static function trustedHosts(): array
    {
        return array_values(array_unique(array_filter([
            self::host('www'),
            self::host('admin'),
            'www.sabexistcount.com',
            'localhost',
            '127.0.0.1',
        ])));
    }
}
