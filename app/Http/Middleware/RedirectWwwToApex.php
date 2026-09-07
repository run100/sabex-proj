<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectWwwToApex
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getHost() === 'www.sabexistcount.com') {
            $target = 'https://sabexistcount.com'.$request->getRequestUri();

            return redirect()->to($target, 301);
        }

        if ($request->getHost() === 'sabex.lab') {
            $target = 'http://www.sabex.lab'.$request->getRequestUri();

            return redirect()->to($target, 301);
        }

        return $next($request);
    }
}
