<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CachePublicSeo
{
    public const CDN_TTL = 14400;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'public, max-age=60, s-maxage='.self::CDN_TTL);
        $response->headers->set('Cloudflare-CDN-Cache-Control', 'max-age='.self::CDN_TTL);
        $response->headers->set('Vary', 'Accept-Encoding');
        $response->headers->remove('Set-Cookie');

        return $response;
    }
}
