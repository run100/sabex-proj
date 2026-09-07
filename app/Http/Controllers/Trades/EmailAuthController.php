<?php

namespace App\Http\Controllers\Trades;

use App\Exceptions\TradeException;
use App\Http\Controllers\Controller;
use App\Models\TradeAuthAccount;
use App\Models\TradeUser;
use App\Services\Trades\EmailVerificationService;
use App\Services\Trades\TradeAuthAccountService;
use App\Services\Trades\TradeUserCreator;
use App\Support\AccessLogService;
use App\Support\TradeAuthRedirect;
use App\Support\TradeCanonical;
use App\Support\TradeEmailAuth;
use App\Support\TradePaths;
use App\Support\TradePresenter;
use App\Support\TradeSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmailAuthController extends Controller
{
    public function show(Request $request): View
    {
        abort_unless(TradeEmailAuth::loginAllowed(), 404);
        TradeAuthRedirect::storeReturnTo($request);

        return view('trades.email-login', TradePresenter::page([
            'seoTitle' => 'Sign in with email',
            'seoDescription' => 'Sign in to SAB Trades with your email and password.',
            'canonical' => TradeCanonical::absolute('/auth/email'),
            'robots' => 'noindex,nofollow',
        ]));
    }

    public function registerForm(Request $request): View
    {
        abort_unless(TradeEmailAuth::registerAllowed(), 404);
        TradeAuthRedirect::storeReturnTo($request);

        return view('trades.register', TradePresenter::page([
            'seoTitle' => 'Create a SAB Trades account',
            'seoDescription' => 'Register with a username, email, and password.',
            'canonical' => TradeCanonical::absolute('/auth/register'),
            'robots' => 'noindex,nofollow',
        ]));
    }

    public function login(Request $request): RedirectResponse
    {
        abort_unless(TradeEmailAuth::loginAllowed() && TradeSchema::identitiesReady(), 404);
        TradeAuthRedirect::storeReturnTo($request);

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string'],
        ]);
        $email = EmailVerificationService::normalize($data['email']);
        $user = TradeUser::query()->where('email', $email)->first();

        if ($user === null || blank($user->password) || ! \Illuminate\Support\Facades\Hash::check($data['password'], $user->password) || ! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => 'Those credentials do not match.',
            ]);
        }
        if ($user->email_verified_at === null) {
            $request->session()->put('trade_email_login', $email);

            return redirect('/auth/email/check')->with('trade_error', 'Verify your email before signing in.');
        }

        $user->last_login_at = now();
        AccessLogService::assign($user, 'last_login_ip', AccessLogService::ip($request));
        $user->save();

        return TradeAuthRedirect::login($request, $user);
    }

    public function register(Request $request, TradeAuthAccountService $accounts, EmailVerificationService $verify, TradeUserCreator $creator): RedirectResponse
    {
        abort_unless(TradeEmailAuth::registerAllowed() && TradeSchema::identitiesReady(), 404);
        TradeAuthRedirect::storeReturnTo($request);

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[a-zA-Z0-9_]+$/'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ]);
        $username = strtolower($data['username']);
        $email = EmailVerificationService::normalize($data['email']);

        if ($verify->emailTaken($email)) {
            $existing = TradeUser::query()->where('email', $email)->first();
            if ($existing === null) {
                $identity = TradeAuthAccount::query()
                    ->where('provider', TradeAuthAccount::PROVIDER_EMAIL)
                    ->where('provider_uid', $email)
                    ->first();
                $existing = $identity?->user;
            }
            if ($existing !== null && $existing->email_verified_at === null) {
                $request->session()->put('trade_email_login', $email);

                return redirect('/auth/email/check')->with('trade_status', TradeEmailAuth::VERIFY_PROMPT);
            }

            throw ValidationException::withMessages([
                'email' => TradeEmailAuth::EMAIL_ALREADY_REGISTERED,
            ]);
        }
        if ($verify->usernameTaken($username)) {
            throw ValidationException::withMessages([
                'username' => 'That username is already taken.',
            ]);
        }

        $ip = AccessLogService::ip($request);
        $user = $creator->create([
            'username' => $username,
            'display_name' => $username,
            'email' => $email,
            'email_verified_at' => null,
            'avatar_url' => '',
            'account_status' => TradeUser::STATUS_ACTIVE,
            'profile_visibility' => TradeUser::VISIBILITY_PUBLIC,
            'moderation_status' => TradeUser::MODERATION_CLEAR,
            'posting_approved' => false,
            'posting_approved_at' => null,
            'password' => $data['password'],
            'roblox_sub' => null,
            'roblox_user_id' => null,
            ...AccessLogService::attrs('seo_trade_users', [
                'registered_ip' => $ip,
            ]),
        ], function (TradeUser $created) use ($accounts, $email, $username): void {
            $accounts->attachIdentity($created, TradeAuthAccount::PROVIDER_EMAIL, $email, [
                'username' => $username,
                'email' => $email,
            ]);
        });
        AccessLogService::write('trade_user', (int) $user->id, 'register', $ip, 'trade_user', (int) $user->id, $request);

        try {
            $verify->send($user, $email, EmailVerificationService::INTENT_REGISTER);
        } catch (TradeException $e) {
            return redirect('/auth/email/check')->with('trade_error', $e->getMessage());
        }

        $request->session()->put('trade_email_login', $email);

        return redirect('/auth/email/check')->with('trade_status', TradeEmailAuth::VERIFY_PROMPT);
    }

    public function check(Request $request): View|RedirectResponse
    {
        abort_unless(TradeEmailAuth::loginAllowed() && TradeSchema::identitiesReady(), 404);
        $email = (string) $request->session()->get('trade_email_login', '');
        if ($email === '') {
            return redirect('/auth/email');
        }

        return view('trades.email-check', TradePresenter::page([
            'seoTitle' => 'Check your email',
            'seoDescription' => 'Open the verification link we sent to finish signing in.',
            'canonical' => TradeCanonical::absolute('/auth/email/check'),
            'robots' => 'noindex,nofollow',
            'email' => $email,
        ]));
    }

    public function resend(Request $request, EmailVerificationService $verify): RedirectResponse
    {
        abort_unless(TradeEmailAuth::loginAllowed() && TradeSchema::identitiesReady(), 404);

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);
        $email = EmailVerificationService::normalize($data['email']);
        $request->session()->put('trade_email_login', $email);
        $user = TradeUser::query()->where('email', $email)->first();

        if ($user !== null && $user->email_verified_at === null) {
            try {
                $verify->send($user, $email, EmailVerificationService::INTENT_REGISTER);
            } catch (TradeException $e) {
                return redirect('/auth/email/check')->with('trade_error', $e->getMessage());
            }
        }

        return redirect('/auth/email/check')->with('trade_status', 'If that email can be verified, we sent a new link.');
    }

    public function verify(Request $request, TradeAuthAccountService $accounts, EmailVerificationService $verify, int $id, string $hash): RedirectResponse
    {
        abort_unless(TradeSchema::identitiesReady(), 404);

        $user = TradeUser::query()->findOrFail($id);
        abort_unless($verify->matchesHash($user, $hash), 403);
        abort_unless($user->isActive(), 404);

        $pending = $verify->pendingBind($user);
        if ($pending !== null) {
            if ($verify->emailTaken($pending['email'], $user->id)) {
                return redirect(TradePaths::account())->with('trade_error', 'That email is already linked to another account.');
            }
            $user->password = $pending['password'];
            $user->email = $pending['email'];
            $user->email_verified_at = now();
            $user->approvePosting();
            $user->save();
            $accounts->bind($user, TradeAuthAccount::PROVIDER_EMAIL, $pending['email'], [
                'email' => $pending['email'],
            ]);
            $verify->forgetBind($user);
            $request->session()->forget('trade_email_bind');

            if (! auth('trades')->check()) {
                return TradeAuthRedirect::login($request, $user, TradePaths::account());
            }

            return redirect(TradePaths::account())->with('trade_status', 'Email linked to this account.');
        }

        if ($user->email_verified_at === null) {
            $user->email_verified_at = now();
            $user->approvePosting();
            $user->save();
        }

        return TradeAuthRedirect::login($request, $user, TradePaths::marketplace());
    }
}
