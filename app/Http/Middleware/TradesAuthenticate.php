<?php

namespace App\Http\Middleware;

use App\Exceptions\TradeException;
use App\Support\TradeApi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TradesAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('trades')->user();
        if ($user === null) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return TradeApi::fromException(TradeException::authRequired());
            }

            $return = $request->getRequestUri();

            return redirect('/auth/roblox?return_to='.urlencode($return));
        }
        if (! $user->isActive()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return TradeApi::fromException(TradeException::banned());
            }

            return redirect('/trading')->with('trade_error', 'This account is suspended.');
        }

        return $next($request);
    }
}
