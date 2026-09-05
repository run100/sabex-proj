<?php

namespace App\Http\Controllers\Trades\Api;

use App\Exceptions\TradeException;
use App\Http\Controllers\Controller;
use App\Models\TradeReport;
use App\Services\Trades\TradeModerationService;
use App\Support\TradeApi;
use App\Support\TradeSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(Request $request, TradeModerationService $moderation): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);
        $data = $request->validate([
            'listing_public_id' => ['nullable', 'string'],
            'profile_id' => ['nullable', 'string', 'size:26'],
            'reason' => ['required', 'in:spam,fake_trade,scam,abuse,inappropriate,duplicate,other'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        try {
            $report = $moderation->report(auth('trades')->user(), $data);

            return TradeApi::ok(['public_id' => $report->public_id], 201);
        } catch (TradeException $e) {
            return TradeApi::fromException($e);
        }
    }

    public function review(Request $request, string $public_id, TradeModerationService $moderation): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);
        $data = $request->validate([
            'status' => ['required', 'in:reviewing,resolved,dismissed'],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $report = TradeReport::query()->where('public_id', $public_id)->first();
        if ($report === null) {
            return TradeApi::error('TRADE_NOT_FOUND', 'Report not found.', 404);
        }
        try {
            $moderation->review(auth('trades')->user(), $report, $data['status'], $data['resolution_note'] ?? null);

            return TradeApi::ok(['public_id' => $report->public_id, 'status' => $report->status]);
        } catch (TradeException $e) {
            return TradeApi::fromException($e);
        }
    }
}
