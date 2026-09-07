<?php

namespace App\Services\Trades;

use App\Exceptions\TradeException;
use App\Models\TradeAuthAccount;
use App\Models\TradeUser;
use App\Support\AccessLogService;
use App\Support\TradeSchema;
use Illuminate\Support\Facades\DB;

class TradeAuthAccountService
{
    public function findByProvider(string $provider, string $uid): ?TradeUser
    {
        if (! TradeSchema::identitiesReady()) {
            return $this->findLegacyUser($provider, $uid);
        }

        $account = TradeAuthAccount::query()
            ->where('provider', $provider)
            ->where('provider_uid', $uid)
            ->first();

        return $account?->user ?? $this->findLegacyUser($provider, $uid);
    }

    /**
     * @param  array{username?: string, display_name?: string, avatar_url?: string, email?: string, profile_url?: string}  $profile
     */
    public function loginOrCreate(string $provider, string $uid, array $profile = []): TradeUser
    {
        $profile = $this->withProviderUid($provider, $uid, $profile);

        return DB::transaction(function () use ($provider, $uid, $profile): TradeUser {
            $user = $this->findByProvider($provider, $uid);
            if ($user === null) {
                $user = app(TradeUserCreator::class)->createForProvider($provider, $uid, $profile);
                AccessLogService::write('trade_user', (int) $user->id, 'register', AccessLogService::ip(), 'trade_user', (int) $user->id);
            }
            if (TradeSchema::identitiesReady()) {
                $this->attachIdentity($user, $provider, $uid, $profile);
            }

            $this->syncUserCache($user, $provider, $profile);
            $user->last_login_at = now();
            AccessLogService::assign($user, 'last_login_ip', AccessLogService::ip());
            $user->save();
            $this->touchIdentityLogin($user, $provider, $profile);

            return $user->fresh() ?? $user;
        });
    }

    /**
     * @param  array{username?: string, display_name?: string, avatar_url?: string, email?: string, profile_url?: string}  $profile
     */
    public function bind(TradeUser $user, string $provider, string $uid, array $profile = []): TradeAuthAccount
    {
        abort_unless(TradeSchema::identitiesReady(), 404);

        return DB::transaction(function () use ($user, $provider, $uid, $profile): TradeAuthAccount {
            $profile = $this->withProviderUid($provider, $uid, $profile);
            $existing = TradeAuthAccount::query()
                ->where('provider', $provider)
                ->where('provider_uid', $uid)
                ->first();
            if ($existing !== null && (int) $existing->user_id !== (int) $user->id) {
                throw TradeException::conflict(
                    'AUTH_PROVIDER_TAKEN',
                    'That '.self::providerLabel($provider).' is already linked to another account.'
                );
            }

            $legacy = $this->findLegacyUser($provider, $uid);
            if ($legacy !== null && (int) $legacy->id !== (int) $user->id) {
                throw TradeException::conflict(
                    'AUTH_PROVIDER_TAKEN',
                    'That '.self::providerLabel($provider).' is already linked to another account.'
                );
            }

            $sameProvider = TradeAuthAccount::query()
                ->where('user_id', $user->id)
                ->where('provider', $provider)
                ->first();
            if ($sameProvider !== null && $sameProvider->provider_uid !== $uid) {
                throw TradeException::conflict(
                    'AUTH_PROVIDER_ALREADY_BOUND',
                    'This account already has a different '.self::providerLabel($provider).' linked.'
                );
            }

            $account = $this->attachIdentity($user, $provider, $uid, $profile);
            $this->syncUserCache($user, $provider, $profile);
            $user->save();

            return $account;
        });
    }

    /**
     * @param  array{username?: string, display_name?: string, avatar_url?: string, email?: string, profile_url?: string}  $profile
     */
    public function attachIdentity(TradeUser $user, string $provider, string $uid, array $profile = []): TradeAuthAccount
    {
        abort_unless(TradeSchema::identitiesReady(), 404);

        $existing = TradeAuthAccount::query()
            ->where('provider', $provider)
            ->where('provider_uid', $uid)
            ->first();
        if ($existing !== null) {
            if ((int) $existing->user_id !== (int) $user->id) {
                throw TradeException::conflict(
                    'AUTH_PROVIDER_TAKEN',
                    'That '.self::providerLabel($provider).' is already linked to another account.'
                );
            }
            $this->fillIdentity($existing, $profile);
            $existing->save();

            return $existing;
        }

        $sameProvider = TradeAuthAccount::query()
            ->where('user_id', $user->id)
            ->where('provider', $provider)
            ->first();
        if ($sameProvider !== null) {
            if ($sameProvider->provider_uid !== $uid) {
                throw TradeException::conflict(
                    'AUTH_PROVIDER_ALREADY_BOUND',
                    'This account already has a different '.self::providerLabel($provider).' linked.'
                );
            }
            $this->fillIdentity($sameProvider, $profile);
            $sameProvider->save();

            return $sameProvider;
        }

        return TradeAuthAccount::query()->create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_uid' => $uid,
            'provider_username' => $profile['username'] ?? $user->username,
            'avatar_url' => $profile['avatar_url'] ?? $user->avatar_url,
            'bound_at' => now(),
            'last_login_at' => now(),
        ]);
    }

    /**
     * @param  array{username?: string, display_name?: string, avatar_url?: string, email?: string, profile_url?: string}  $profile
     */
    public function syncUserCache(TradeUser $user, string $provider, array $profile): void
    {
        if ($provider === TradeAuthAccount::PROVIDER_EMAIL) {
            $email = strtolower(trim((string) ($profile['email'] ?? $profile['username'] ?? $user->email ?? '')));
            if ($email !== '') {
                $user->email = $email;
                $user->email_verified_at ??= now();
                $user->approvePosting();
            }
            if (blank($user->username) && filled($profile['username'] ?? null)) {
                $user->username = (string) $profile['username'];
            }
            if (blank($user->display_name) && filled($profile['display_name'] ?? null)) {
                $user->display_name = (string) $profile['display_name'];
            }

            return;
        }

        if ($provider === TradeAuthAccount::PROVIDER_ROBLOX) {
            $user->roblox_sub = (string) ($profile['roblox_sub'] ?? $user->roblox_sub);
            if (filled($profile['username'] ?? null)) {
                $user->username = (string) $profile['username'];
            }
            if (filled($profile['display_name'] ?? null)) {
                $user->display_name = (string) $profile['display_name'];
            }
            if (array_key_exists('avatar_url', $profile)) {
                $user->avatar_url = (string) $profile['avatar_url'];
            }
            $user->profile_url = (string) ($profile['profile_url'] ?? ('https://www.roblox.com/users/'.$user->roblox_sub.'/profile'));

            return;
        }

        if ($provider === TradeAuthAccount::PROVIDER_LOCAL && filled($profile['username'] ?? null) && blank($user->username)) {
            $user->username = (string) $profile['username'];
        }
    }

    /**
     * @param  array{username?: string, display_name?: string, avatar_url?: string, email?: string, profile_url?: string, roblox_sub?: string}  $profile
     * @return array{username?: string, display_name?: string, avatar_url?: string, email?: string, profile_url?: string, roblox_sub?: string}
     */
    private function withProviderUid(string $provider, string $uid, array $profile): array
    {
        if ($provider === TradeAuthAccount::PROVIDER_EMAIL) {
            $profile['email'] = $uid;
        }
        if ($provider === TradeAuthAccount::PROVIDER_ROBLOX) {
            $profile['roblox_sub'] = $uid;
            $profile['profile_url'] ??= 'https://www.roblox.com/users/'.$uid.'/profile';
        }

        return $profile;
    }

    public static function usernameFromEmail(string $email): string
    {
        $local = strstr(strtolower($email), '@', true);
        $username = preg_replace('/[^a-z0-9_]/', '_', (string) $local) ?? '';
        $username = trim($username, '_');
        $username = substr($username !== '' ? $username : 'trader', 0, 24);
        $base = $username;
        $suffix = 0;
        while (TradeUser::query()->where('username', $username)->exists()) {
            $suffix++;
            $username = substr($base, 0, 20).'_'.$suffix;
        }

        return $username;
    }

    private function findLegacyUser(string $provider, string $uid): ?TradeUser
    {
        if ($provider === TradeAuthAccount::PROVIDER_EMAIL) {
            return TradeUser::query()->where('email', $uid)->first();
        }

        if (in_array($provider, [TradeAuthAccount::PROVIDER_ROBLOX, TradeAuthAccount::PROVIDER_LOCAL], true)) {
            return TradeUser::query()->where('roblox_sub', $uid)->first();
        }

        return null;
    }

    /**
     * @param  array{username?: string, display_name?: string, avatar_url?: string}  $profile
     */
    private function fillIdentity(TradeAuthAccount $account, array $profile): void
    {
        if (filled($profile['username'] ?? null)) {
            $account->provider_username = (string) $profile['username'];
        }
        if (array_key_exists('avatar_url', $profile)) {
            $account->avatar_url = (string) $profile['avatar_url'];
        }
    }

    /**
     * @param  array{username?: string, display_name?: string, avatar_url?: string}  $profile
     */
    private function touchIdentityLogin(TradeUser $user, string $provider, array $profile): void
    {
        if (! TradeSchema::identitiesReady()) {
            return;
        }

        $identity = TradeAuthAccount::query()
            ->where('user_id', $user->id)
            ->where('provider', $provider)
            ->first();
        if ($identity === null) {
            return;
        }

        $this->fillIdentity($identity, $profile);
        $identity->last_login_at = now();
        $identity->save();
    }

    private static function providerLabel(string $provider): string
    {
        return match ($provider) {
            TradeAuthAccount::PROVIDER_EMAIL => 'email',
            TradeAuthAccount::PROVIDER_ROBLOX => 'Roblox account',
            TradeAuthAccount::PROVIDER_GOOGLE => 'Google account',
            default => 'login method',
        };
    }
}
