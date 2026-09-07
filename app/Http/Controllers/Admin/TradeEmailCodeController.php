<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TradeEmailCode;
use App\Support\TradeSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class TradeEmailCodeController extends Controller
{
    public function index(): JsonResponse
    {
        abort_unless(TradeSchema::identitiesReady() && Schema::hasTable('seo_trade_email_codes'), 404);

        $codes = TradeEmailCode::query()
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return response()->json([
            'codes' => $codes->map(fn (TradeEmailCode $code): array => [
                'id' => $code->id,
                'email' => $code->email,
                'code' => '******',
                'purpose' => $code->purpose,
                'attempts' => $code->attempts,
                'expires_at' => $code->expires_at,
                'used_at' => $code->used_at,
                'created_at' => $code->created_at,
            ])->all(),
        ]);
    }
}
