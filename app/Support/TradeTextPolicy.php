<?php

namespace App\Support;

use App\Exceptions\TradeException;

final class TradeTextPolicy
{
    public const MAX_LENGTH = 280;

    private const MARKUP_PATTERN = '/<\/?[A-Za-z_:][A-Za-z0-9:._-]*(?:\s+[^<>]*)?\s*\/?>|<!--[\s\S]*?(?:-->|$)|<![A-Za-z][^>]*>|<\?[A-Za-z][^>]*\?>|<\/?[A-Za-z_:][A-Za-z0-9:._-]*(?:\s+[^<>]*)?$|&(?:[A-Za-z][A-Za-z0-9]+|#\d+|#x[0-9A-F]+);/i';

    private const NORMAL_TEXT_PATTERN = '/\A[\p{L}\p{M}\p{N}\p{Zs}\r\n.,!?;\'"()\-_，。！？；：、（）「」『』【】《》〈〉…—–·]+\z/u';

    public static function required(mixed $value): string
    {
        if ($value === null) {
            $value = '';
        }
        if (! is_string($value)) {
            throw self::invalidNormalText();
        }

        if ($value === '' || mb_strlen($value) > self::MAX_LENGTH) {
            throw TradeException::invalid('INVALID_MESSAGE', 'Enter a message up to 280 characters.');
        }

        self::assertNormalText($value);
        $text = trim($value);
        if ($text === '') {
            throw TradeException::invalid('INVALID_MESSAGE', 'Enter a message up to 280 characters.');
        }

        return $text;
    }

    public static function optional(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (! is_string($value)) {
            throw self::invalidNormalText();
        }

        if ($value === '') {
            return null;
        }
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw TradeException::invalid('INVALID_MESSAGE', 'Enter a message up to 280 characters.');
        }

        self::assertNormalText($value);
        $text = trim($value);

        return $text !== '' ? $text : null;
    }

    private static function assertNormalText(string $text): void
    {
        if (preg_match(self::MARKUP_PATTERN, $text) === 1) {
            throw TradeException::invalid('INVALID_MESSAGE', 'HTML/XML tags are not allowed.');
        }
        if (preg_match(self::NORMAL_TEXT_PATTERN, $text) !== 1) {
            throw self::invalidNormalText();
        }
    }

    private static function invalidNormalText(): TradeException
    {
        return TradeException::invalid(
            'INVALID_MESSAGE',
            'Only normal text, numbers, spaces, line breaks, and common punctuation are allowed.'
        );
    }
}
