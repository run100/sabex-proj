<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\TradeAuthAccount;
use App\Services\Trades\RobloxOAuthService;
use App\Services\Trades\TradeAuthAccountService;
use App\Support\TradeAuthRedirect;
use App\Support\TradeCanonical;
use App\Support\TradePaths;
use App\Support\TradePresenter;
use App\Support\TradeSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RobloxAuthController extends Controller
{
    public function show(Request $request, RobloxOAuthService $oauth): View|RedirectResponse
    {
        TradeAuthRedirect::storeReturnTo($request);
        if ($request->boolean('start') && $oauth->configured()) {
            return $this->redirect($request, $oauth);
        }

        return view('trades.login', TradePresenter::page([
            'seoTitle' => 'Sign in to SAB Trades',
            'seoDescription' => 'Sign in with official Roblox OAuth to post Steal a Brainrot trades.',
            'canonical' => TradeCanonical::absolute('/auth/roblox'),
            'robots' => 'noindex,nofollow',
        ]));
    }

    public function redirect(Request $request, RobloxOAuthService $oauth): RedirectResponse
    {
        if (! $oauth->configured()) {
            return redirect('/auth/roblox')->with('trade_error', TradePresenter::oauthUnavailableCopy());
        }

        TradeAuthRedirect::storeReturnTo($request);
        $auth = $oauth->authorization();
        $request->session()->put('roblox_oauth_state', $auth['state']);
        $request->session()->put('roblox_oauth_verifier', $auth['verifier']);
        $request->session()->put('roblox_oauth_nonce', $auth['nonce']);

        return redirect()->away($auth['url']);
    }

    public function callback(Request $request, RobloxOAuthService $oauth, TradeAuthAccountService $accounts): RedirectResponse
    {
        abort_unless($oauth->configured() && TradeSchema::ready(), 404);
        abort_unless(hash_equals((string) $request->session()->pull('roblox_oauth_state'), (string) $request->query('state')), 404);

        $profile = $oauth->userFromCallback(
            (string) $request->query('code'),
            (string) $request->session()->pull('roblox_oauth_verifier')
        );

        $user = $accounts->loginOrCreate(TradeAuthAccount::PROVIDER_ROBLOX, $profile['sub'], [
            'username' => $profile['username'],
            'display_name' => $profile['display_name'],
            'avatar_url' => $profile['avatar_url'],
        ]);

        abort_unless($user->isActive(), 404);

        return TradeAuthRedirect::login($request, $user);
    }

    public function logout(Request $request): RedirectResponse
    {
        auth('trades')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(TradePaths::marketplace());
    }
}
