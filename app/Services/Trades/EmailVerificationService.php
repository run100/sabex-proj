<?php

namespace App\Services\Trades;

use App\Exceptions\TradeException;
use App\Mail\TradeVerifyEmail;
use App\Models\TradeAuthAccount;
use App\Models\TradeUser;
use App\Support\SabHost;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

class EmailVerificationService
{
    public const INTENT_REGISTER = 'register';

    public const INTENT_BIND = 'bind';

    public static function normalize(string $email): string
    {
        return EmailCodeService::normalize($email);
    }

    public static function hash(string $email): string
    {
        return sha1(self::normalize($email));
    }

    public function signedUrl(TradeUser $user, string $email): string
    {
        $email = self::normalize($email);
        $root = rtrim(SabHost::origin('www'), '/');
        $previous = request()?->getSchemeAndHttpHost() ?: $root;
        URL::forceRootUrl($root);
        URL::forceScheme(parse_url($root, PHP_URL_SCHEME) ?: 'http');
        try {
            return URL::temporarySignedRoute(
                'trades.email.verify',
                now()->addHours(24),
                ['id' => $user->id, 'hash' => self::hash($email)]
            );
        } finally {
            URL::forceRootUrl($previous);
        }
    }

    public function send(TradeUser $user, string $email, string $intent = self::INTENT_REGISTER): void
    {
        if ($intent === self::INTENT_REGISTER && ! \App\Support\TradeEmailAuth::registerAllowed()) {
            throw TradeException::notFound();
        }
        if ($intent === self::INTENT_BIND && ! \App\Support\TradeEmailAuth::bindAllowed()) {
            throw TradeException::notFound();
        }
        $email = self::normalize($email);
        $this->assertRateLimits($email);
        Mail::to($email)->send(new TradeVerifyEmail($this->signedUrl($user, $email), $email, $intent));
        RateLimiter::hit($this->emailKey($email), 60);
        RateLimiter::hit($this->ipKey(), 3600);
    }

    public function rememberBind(TradeUser $user, string $email, string $password): void
    {
        if (! \App\Support\TradeEmailAuth::bindAllowed()) {
            throw TradeException::notFound();
        }
        Cache::put($this->bindKey($user), [
            'email' => self::normalize($email),
            'password' => $password,
            'intent' => self::INTENT_BIND,
        ], now()->addHours(24));
    }

    /**
     * @return array{email: string, password: string, intent: string}|null
     */
    public function pendingBind(TradeUser $user): ?array
    {
        $pending = Cache::get($this->bindKey($user));

        return is_array($pending) && filled($pending['email'] ?? null) ? $pending : null;
    }

    public function forgetBind(TradeUser $user): void
    {
        Cache::forget($this->bindKey($user));
    }

    public function emailTaken(string $email, ?int $exceptUserId = null): bool
    {
        $email = self::normalize($email);
        $users = TradeUser::query()->where('email', $email);
        $identities = TradeAuthAccount::query()
            ->where('provider', TradeAuthAccount::PROVIDER_EMAIL)
            ->where('provider_uid', $email);
        if ($exceptUserId !== null) {
            $users->where('id', '!=', $exceptUserId);
            $identities->where('user_id', '!=', $exceptUserId);
        }

        return $users->exists() || $identities->exists();
    }

    public function usernameTaken(string $username, ?int $exceptUserId = null): bool
    {
        $query = TradeUser::query()->whereRaw('LOWER(username) = ?', [strtolower($username)]);
        if ($exceptUserId !== null) {
            $query->where('id', '!=', $exceptUserId);
        }

        return $query->exists();
    }

    public function matchesHash(TradeUser $user, string $hash): bool
    {
        $email = self::normalize((string) ($user->email ?: ($this->pendingBind($user)['email'] ?? '')));

        return $email !== '' && hash_equals(self::hash($email), $hash);
    }

    private function bindKey(TradeUser $user): string
    {
        return 'trades.email.bind:'.$user->id;
    }

    private function assertRateLimits(string $email): void
    {
        if (RateLimiter::tooManyAttempts($this->emailKey($email), 1)) {
            throw TradeException::rateLimited();
        }
        if (RateLimiter::tooManyAttempts($this->ipKey(), 10)) {
            throw TradeException::rateLimited();
        }
    }

    private function emailKey(string $email): string
    {
        return 'trades.email.verify:'.$email;
    }

    private function ipKey(): string
    {
        return 'trades.email.verify.ip:'.(request()?->ip() ?: 'cli');
    }
}
