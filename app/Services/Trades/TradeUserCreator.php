<?php

namespace App\Services\Trades;

use App\Exceptions\TradeException;
use App\Models\TradeAuthAccount;
use App\Models\TradeUser;
use App\Support\AccessLogService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class TradeUserCreator
{
    private const ULID_INDEXES = [
        'uk_trade_users_public_id',
        'uk_trade_users_profile_id',
        'seo_trade_users.public_id',
        'seo_trade_users.profile_id',
    ];

    /**
     * @param  array<string, mixed>  $attrs
     * @param  callable(TradeUser): void|null  $afterCreate
     */
    public function create(array $attrs, ?callable $afterCreate = null): TradeUser
    {
        $last = null;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($attrs, $afterCreate): TradeUser {
                    $attrs['public_id'] = (string) Str::ulid();
                    $attrs['profile_id'] = (string) Str::ulid();
                    $user = TradeUser::query()->create($attrs);
                    if ($afterCreate !== null) {
                        $afterCreate($user);
                    }

                    return $user;
                });
            } catch (UniqueConstraintViolationException $e) {
                $last = $e;
                if (! $this->isUlidCollision($e)) {
                    throw $e;
                }
            }
        }

        throw $last ?? TradeException::invalid('TRADE_CREATE_FAILED', 'Could not create the account.');
    }

    /**
     * @param  array{username?: string, display_name?: string, avatar_url?: string, email?: string, profile_url?: string, roblox_sub?: string}  $profile
     */
    public function createForProvider(string $provider, string $uid, array $profile, ?string $password = null): TradeUser
    {
        $username = (string) ($profile['username'] ?? ($provider === TradeAuthAccount::PROVIDER_EMAIL
            ? TradeAuthAccountService::usernameFromEmail($uid)
            : $uid));
        $display = (string) ($profile['display_name'] ?? $username);
        $ip = AccessLogService::ip();
        $attrs = [
            'username' => $username,
            'display_name' => $display,
            'avatar_url' => (string) ($profile['avatar_url'] ?? ''),
            'account_status' => TradeUser::STATUS_ACTIVE,
            'profile_visibility' => TradeUser::VISIBILITY_PUBLIC,
            'moderation_status' => TradeUser::MODERATION_CLEAR,
            'last_login_at' => now(),
            ...AccessLogService::attrs('seo_trade_users', [
                'registered_ip' => $ip,
                'last_login_ip' => $ip,
            ]),
        ];

        if ($provider === TradeAuthAccount::PROVIDER_EMAIL) {
            $attrs['email'] = $uid;
            $attrs['email_verified_at'] = array_key_exists('email_verified_at', $profile)
                ? $profile['email_verified_at']
                : now();
            $attrs['roblox_sub'] = null;
            $attrs['roblox_user_id'] = null;
            $attrs['posting_approved'] = (bool) ($attrs['email_verified_at'] ?? false);
            $attrs['posting_approved_at'] = $attrs['posting_approved'] ? now() : null;
            $attrs['password'] = $password;
        } elseif ($provider === TradeAuthAccount::PROVIDER_ROBLOX) {
            $robloxId = self::parseRobloxUserId($uid);
            if ($robloxId === null) {
                throw TradeException::invalid('INVALID_ROBLOX_ID', 'Roblox user id must be a positive integer.');
            }
            $attrs['roblox_sub'] = $uid;
            $attrs['roblox_user_id'] = $robloxId;
            $attrs['profile_url'] = (string) ($profile['profile_url'] ?? ('https://www.roblox.com/users/'.$uid.'/profile'));
            $attrs['posting_approved'] = true;
            $attrs['posting_approved_at'] = now();
        } else {
            $attrs['roblox_sub'] = $uid;
            $attrs['roblox_user_id'] = null;
            $attrs['posting_approved'] = false;
            $attrs['posting_approved_at'] = null;
            $attrs['password'] = $password;
        }

        return $this->create($attrs);
    }

    public static function parseRobloxUserId(string $value): ?string
    {
        if ($value === '' || ! ctype_digit($value)) {
            return null;
        }
        $normalized = ltrim($value, '0');
        if ($normalized === '' || strlen($normalized) > 20) {
            return null;
        }
        if (strlen($normalized) === 20 && strcmp($normalized, '18446744073709551615') > 0) {
            return null;
        }

        return $normalized;
    }

    private function isUlidCollision(Throwable $e): bool
    {
        $message = $e->getMessage();
        foreach (self::ULID_INDEXES as $token) {
            if (str_contains($message, $token)) {
                return true;
            }
        }

        return false;
    }
}
