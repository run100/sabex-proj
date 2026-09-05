<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->guard('admin')->check()) {
            if ($request->expectsJson()) {
                abort(404);
            }

            return redirect('/login');
        }

        return $next($request);
    }
}
