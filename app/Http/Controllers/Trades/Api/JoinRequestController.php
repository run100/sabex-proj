<?php

namespace App\Http\Controllers\Trades\Api;

use App\Exceptions\TradeException;
use App\Http\Controllers\Controller;
use App\Services\Trades\TradeJoinService;
use App\Support\TradeApi;
use App\Support\TradePresenter;
use App\Support\TradeSchema;
use Illuminate\Http\JsonResponse;

class JoinRequestController extends Controller
{
    public function accept(string $ulid, TradeJoinService $joins): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);
        try {
            $listing = $joins->accept(auth('trades')->user(), $joins->findPublic($ulid));

            return TradeApi::ok(['trade' => TradePresenter::listing($listing)]);
        } catch (TradeException $e) {
            return TradeApi::fromException($e);
        }
    }

    public function reject(string $ulid, TradeJoinService $joins): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);
        try {
            $row = $joins->reject(auth('trades')->user(), $joins->findPublic($ulid));

            return TradeApi::ok(['join_request' => TradePresenter::join($row)]);
        } catch (TradeException $e) {
            return TradeApi::fromException($e);
        }
    }

    public function cancel(string $ulid, TradeJoinService $joins): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);
        try {
            $row = $joins->cancel(auth('trades')->user(), $joins->findPublic($ulid));

            return TradeApi::ok(['join_request' => TradePresenter::join($row)]);
        } catch (TradeException $e) {
            return TradeApi::fromException($e);
        }
    }
}
