<?php

namespace App\Support;

use App\Exceptions\TradeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    /**
     * @param  array<string, list<mixed>>  $rules
     * @return array<string, mixed>|JsonResponse
     */
    public static function validated(Request $request, array $rules): array|JsonResponse
    {
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return self::error('INVALID_INPUT', (string) $validator->errors()->first(), 422);
        }

        return $validator->validated();
    }
}
