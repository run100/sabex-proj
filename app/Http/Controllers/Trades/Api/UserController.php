<?php

namespace App\Http\Controllers\Trades\Api;

use App\Exceptions\TradeException;
use App\Http\Controllers\Controller;
use App\Models\TradeListing;
use App\Models\TradeUser;
use App\Services\Trades\TradeModerationService;
use App\Services\Trades\TradeStatsService;
use App\Support\TradeApi;
use App\Support\TradePresenter;
use App\Support\TradeSchema;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function show(string $roblox_sub, TradeStatsService $stats): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);
        $user = TradeUser::query()->where('roblox_sub', $roblox_sub)->first();
        if ($user === null || $user->account_status === TradeUser::STATUS_DELETED) {
            return TradeApi::error('TRADE_NOT_FOUND', 'User not found.', 404);
        }

        return TradeApi::ok([
            'user' => TradePresenter::user($user),
            'stats' => $stats->forUser($user),
        ]);
    }

    public function trades(string $roblox_sub): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);
        $user = TradeUser::query()->where('roblox_sub', $roblox_sub)->first();
        if ($user === null) {
            return TradeApi::error('TRADE_NOT_FOUND', 'User not found.', 404);
        }
        $rows = TradeListing::query()
            ->with(['owner', 'items.traits'])
            ->where('owner_user_id', $user->id)
            ->where('status', TradeListing::STATUS_OPEN)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return TradeApi::ok(['items' => $rows->map(fn ($row) => TradePresenter::listing($row))->all()]);
    }

    public function block(string $roblox_sub, TradeModerationService $moderation): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);
        try {
            $target = TradeUser::query()->where('roblox_sub', $roblox_sub)->first();
            if ($target === null) {
                throw TradeException::notFound('TRADE_NOT_FOUND', 'User not found.');
            }
            $moderation->block(auth('trades')->user(), $target);

            return TradeApi::ok(['blocked' => true]);
        } catch (TradeException $e) {
            return TradeApi::fromException($e);
        }
    }

    public function unblock(string $roblox_sub, TradeModerationService $moderation): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);
        $target = TradeUser::query()->where('roblox_sub', $roblox_sub)->first();
        if ($target === null) {
            return TradeApi::error('TRADE_NOT_FOUND', 'User not found.', 404);
        }
        $moderation->unblock(auth('trades')->user(), $target);

        return TradeApi::ok(['blocked' => false]);
    }
}
