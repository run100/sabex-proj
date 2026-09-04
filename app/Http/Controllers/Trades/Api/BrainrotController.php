<?php

namespace App\Http\Controllers\Trades\Api;

use App\Exceptions\TradeException;
use App\Http\Controllers\Controller;
use App\Services\Trades\BrainrotCatalogService;
use App\Support\TradeApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrainrotController extends Controller
{
    public function search(Request $request, BrainrotCatalogService $catalog): JsonResponse
    {
        return TradeApi::ok([
            'items' => $catalog->searchBrainrots((string) $request->query('q', ''), (int) $request->query('limit', 20)),
        ]);
    }

    public function options(string $brainrot, BrainrotCatalogService $catalog): JsonResponse
    {
        try {
            return TradeApi::ok($catalog->getTradeOptions($brainrot));
        } catch (TradeException $e) {
            return TradeApi::fromException($e);
        }
    }
}
