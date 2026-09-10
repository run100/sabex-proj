<?php

namespace App\Http\Controllers\Trades\Api;

use App\Exceptions\TradeException;
use App\Http\Controllers\Controller;
use App\Services\Trades\BrainrotCatalogService;
use App\Support\TradeApi;
use App\Support\TradeQueryRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrainrotController extends Controller
{
    public function search(Request $request, BrainrotCatalogService $catalog): JsonResponse
    {
        $input = TradeApi::validated($request, TradeQueryRules::brainrotSearch());
        if ($input instanceof JsonResponse) {
            return $input;
        }

        return TradeApi::ok([
            'items' => $catalog->searchBrainrots($input['q'] ?? '', $input['limit'] ?? 20),
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
