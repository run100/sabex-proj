<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\TradeUser;
use App\Services\Trades\RobloxOAuthService;
use App\Support\TradePresenter;
use App\Support\TradeSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RobloxAuthController extends Controller
{
    public function show(Request $request, RobloxOAuthService $oauth): View|RedirectResponse
    {
        $this->storeReturnTo($request);
        if ($oauth->configured() && TradeSchema::ready() && $request->query('return_to')) {
            return $this->redirect($request, $oauth);
        }

        return view('trades.login', TradePresenter::page([
            'seoTitle' => 'Sign in with Roblox',
            'seoDescription' => 'Sign in with Roblox to post Steal a Brainrot trades.',
            'canonical' => url('/auth/roblox'),
            'robots' => 'noindex,nofollow',
        ]));
    }

    public function redirect(Request $request, RobloxOAuthService $oauth): RedirectResponse
    {
        abort_unless($oauth->configured(), 404);
        $this->storeReturnTo($request);
        $auth = $oauth->authorization();
        $request->session()->put('roblox_oauth_state', $auth['state']);
        $request->session()->put('roblox_oauth_verifier', $auth['verifier']);
        $request->session()->put('roblox_oauth_nonce', $auth['nonce']);

        return redirect()->away($auth['url']);
    }

    public function callback(Request $request, RobloxOAuthService $oauth): RedirectResponse
    {
        abort_unless($oauth->configured() && TradeSchema::ready(), 404);
        abort_unless(hash_equals((string) $request->session()->pull('roblox_oauth_state'), (string) $request->query('state')), 404);

        $profile = $oauth->userFromCallback(
            (string) $request->query('code'),
            (string) $request->session()->pull('roblox_oauth_verifier')
        );

        $user = TradeUser::query()->firstOrNew(['roblox_sub' => $profile['sub']]);
        $user->username = $profile['username'];
        $user->display_name = $profile['display_name'];
        $user->avatar_url = $profile['avatar_url'];
        $user->profile_url = 'https://www.roblox.com/users/'.$profile['sub'].'/profile';
        $user->last_login_at = now();
        if (! $user->exists) {
            $user->account_status = TradeUser::STATUS_ACTIVE;
        }
        $user->save();

        abort_unless($user->isActive(), 404);

        auth('trades')->login($user, true);
        $request->session()->regenerate();
        $return = (string) $request->session()->pull('roblox_oauth_return', '/');

        return redirect($this->safeReturn($return));
    }

    public function logout(Request $request): RedirectResponse
    {
        auth('trades')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function storeReturnTo(Request $request): void
    {
        $return = (string) $request->query('return_to', $request->input('return_to', ''));
        if ($return !== '') {
            $request->session()->put('roblox_oauth_return', $this->safeReturn($return));
        }
    }

    private function safeReturn(string $path): string
    {
        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return '/';
        }

        return $path;
    }
}
