<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\TradeAuthAccount;
use App\Models\TradeUser;
use App\Services\Trades\TradeAuthAccountService;
use App\Services\Trades\TradeUserCreator;
use App\Support\AccessLogService;
use App\Support\TradeAuthRedirect;
use App\Support\TradeLocalAuth;
use App\Support\TradeSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LocalAuthController extends Controller
{
    public function register(Request $request, TradeAuthAccountService $accounts, TradeUserCreator $creator): RedirectResponse
    {
        $this->guardLocal();

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[a-zA-Z0-9_]+$/'],
            'password' => ['required', 'string', 'min:8', 'max:72'],
            'display_name' => ['nullable', 'string', 'max:100'],
        ]);

        $username = strtolower($data['username']);
        $sub = TradeLocalAuth::robloxSub($username);
        if (TradeUser::query()->where('roblox_sub', $sub)->exists()) {
            throw ValidationException::withMessages([
                'username' => 'That local username is already taken.',
            ]);
        }

        $ip = AccessLogService::ip($request);
        $user = $creator->create([
            'roblox_sub' => $sub,
            'roblox_user_id' => null,
            'username' => $username,
            'display_name' => trim((string) ($data['display_name'] ?? '')) ?: $username,
            'avatar_url' => '',
            'account_status' => TradeUser::STATUS_ACTIVE,
            'profile_visibility' => TradeUser::VISIBILITY_PUBLIC,
            'moderation_status' => TradeUser::MODERATION_CLEAR,
            'posting_approved' => false,
            'posting_approved_at' => null,
            'password' => $data['password'],
            'last_login_at' => now(),
            ...AccessLogService::attrs('seo_trade_users', [
                'registered_ip' => $ip,
                'last_login_ip' => $ip,
            ]),
        ], function (TradeUser $created) use ($accounts, $sub, $username): void {
            if (TradeSchema::identitiesReady()) {
                $accounts->attachIdentity($created, TradeAuthAccount::PROVIDER_LOCAL, $sub, [
                    'username' => $username,
                ]);
            }
        });
        AccessLogService::write('trade_user', (int) $user->id, 'register', $ip, 'trade_user', (int) $user->id, $request);

        return TradeAuthRedirect::login($request, $user);
    }

    public function login(Request $request): RedirectResponse
    {
        $this->guardLocal();

        $data = $request->validate([
            'username' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string'],
        ]);

        $user = TradeUser::query()->where(
            'roblox_sub',
            TradeLocalAuth::robloxSub(strtolower($data['username']))
        )->first();

        if ($user === null || blank($user->password) || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => 'Those local credentials do not match.',
            ]);
        }
        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'username' => 'This account is suspended.',
            ]);
        }

        $user->last_login_at = now();
        AccessLogService::assign($user, 'last_login_ip', AccessLogService::ip($request));
        $user->save();

        return TradeAuthRedirect::login($request, $user);
    }

    private function guardLocal(): void
    {
        abort_unless(TradeLocalAuth::enabled() && TradeSchema::ready(), 404);
    }
}
