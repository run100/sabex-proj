<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAllowIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = config('sab.admin_allow_ips', []);
        $ip = $this->clientIp($request);
        if (! is_array($allowed) || $allowed === [] || ! in_array($ip, $allowed, true)) {
            if ($request->expectsJson()) {
                abort(403);
            }

            return response()->view('admin.denied', [], 403);
        }

        return $next($request);
    }

    private function clientIp(Request $request): string
    {
        $cf = trim((string) $request->headers->get('CF-Connecting-IP', ''));
        if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP)) {
            return $cf;
        }

        return (string) $request->ip();
    }
}
