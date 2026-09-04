<?php

namespace App\Support;

use App\Exceptions\TradeException;
use Illuminate\Http\JsonResponse;

class TradeApi
{
    public static function ok(mixed $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
        ], $status);
    }

    public static function error(string $code, string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }

    public static function fromException(TradeException $exception): JsonResponse
    {
        return self::error($exception->errorCode, $exception->getMessage(), $exception->status);
    }
}
