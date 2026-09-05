<?php

namespace App\Exceptions;

use RuntimeException;

class TradeException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 400,
    ) {
        parent::__construct($message, $status);
    }

    public static function authRequired(): self
    {
        return new self('AUTH_REQUIRED', 'Sign in to continue.', 401);
    }

    public static function banned(): self
    {
        return new self('USER_BANNED', 'This account is suspended.', 403);
    }

    public static function notFound(string $code = 'TRADE_NOT_FOUND', string $message = 'Trade not found.'): self
    {
        return new self($code, $message, 404);
    }

    public static function conflict(string $code, string $message): self
    {
        return new self($code, $message, 409);
    }

    public static function forbidden(string $code, string $message): self
    {
        return new self($code, $message, 403);
    }

    public static function invalid(string $code, string $message): self
    {
        return new self($code, $message, 422);
    }

    public static function rateLimited(): self
    {
        return new self('RATE_LIMITED', 'Too many requests. Try again later.', 429);
    }
}
