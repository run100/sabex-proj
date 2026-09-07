<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FinalizeCachedSeo
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $cacheControl = (string) $response->headers->get('Cache-Control');
        if (str_contains($cacheControl, 's-maxage='.CachePublicSeo::CDN_TTL)) {
            $response->headers->remove('Set-Cookie');
        }

        return $response;
    }
}