<?php

namespace App\Services\Trades;

use App\Exceptions\TradeException;
use App\Mail\TradeBindCode;
use App\Mail\TradeLoginCode;
use App\Models\TradeEmailCode;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class EmailCodeService
{
    public static function normalize(string $email): string
    {
        return strtolower(trim($email));
    }

    public function send(string $email, string $purpose): void
    {
        $email = self::normalize($email);
        $this->assertRateLimits($email);

        $code = (string) random_int(100000, 999999);
        TradeEmailCode::query()->create([
            'email' => $email,
            'code' => $code,
            'purpose' => $purpose,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        $mailable = $purpose === TradeEmailCode::PURPOSE_BIND
            ? new TradeBindCode($code)
            : new TradeLoginCode($code);
        Mail::to($email)->send($mailable);

        RateLimiter::hit($this->emailKey($email), 60);
        RateLimiter::hit($this->ipKey(), 3600);
    }

    public function verify(string $email, string $code, string $purpose): void
    {
        $email = self::normalize($email);
        $row = TradeEmailCode::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if ($row === null) {
            throw TradeException::invalid('EMAIL_CODE_INVALID', 'That code is invalid or expired.');
        }
        if ($row->attempts >= 5) {
            throw TradeException::invalid('EMAIL_CODE_LOCKED', 'Too many attempts. Request a new code.');
        }

        $row->attempts++;
        if (! hash_equals($row->code, $code)) {
            $row->save();
            throw TradeException::invalid('EMAIL_CODE_INVALID', 'That code is invalid or expired.');
        }

        $row->used_at = now();
        $row->save();
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
        return 'trades.email.send:'.$email;
    }

    private function ipKey(): string
    {
        return 'trades.email.ip:'.(request()?->ip() ?: 'cli');
    }
}
