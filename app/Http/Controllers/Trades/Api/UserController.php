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
use App\Support\TradeProfileAccess;
use App\Support\TradeSchema;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function show(string $profile_id, TradeStatsService $stats): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);
        $user = TradeUser::findPublic($profile_id);
        if (! TradeProfileAccess::canAccessProfile($user)) {
            return TradeApi::error('TRADE_NOT_FOUND', 'User not found.', 404);
        }

        return TradeApi::ok([
            'user' => TradePresenter::userPublic($user),
            'stats' => $stats->forUser($user),
        ]);
    }

    public function trades(string $profile_id): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);
        $user = TradeUser::findPublic($profile_id);
        if (! TradeProfileAccess::canAccessProfile($user)) {
            return TradeApi::error('TRADE_NOT_FOUND', 'User not found.', 404);
        }
        $open = TradeListing::query()
            ->with(['owner', 'items.traits'])
            ->where('owner_user_id', $user->id)
            ->where('status', TradeListing::STATUS_OPEN)
            ->whereHas('owner', fn ($q) => TradeProfileAccess::constrainPublicIdentity($q))
            ->orderByDesc('id')
            ->limit(20)
            ->get();
        $completed = TradeListing::query()
            ->with(['owner', 'counterparty', 'items.traits'])
            ->where('status', TradeListing::STATUS_COMPLETED)
            ->where(function ($q) use ($user): void {
                $q->where('owner_user_id', $user->id)->orWhere('counterparty_user_id', $user->id);
            })
            ->whereHas('owner', fn ($q) => TradeProfileAccess::constrainPublicIdentity($q))
            ->where(function ($q): void {
                $q->whereNull('counterparty_user_id')
                    ->orWhereHas('counterparty', fn ($inner) => TradeProfileAccess::constrainPublicIdentity($inner));
            })
            ->orderByDesc('completed_at')
            ->limit(20)
            ->get();

        return TradeApi::ok([
            'items' => $open->concat($completed)->map(fn ($row) => TradePresenter::listing($row))->values()->all(),
        ]);
    }

    public function block(string $profile_id, TradeModerationService $moderation): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);
        try {
            $target = TradeUser::findPublic($profile_id);
            if ($target === null) {
                throw TradeException::notFound('TRADE_NOT_FOUND', 'User not found.');
            }
            $moderation->block(auth('trades')->user(), $target);

            return TradeApi::ok(['blocked' => true]);
        } catch (TradeException $e) {
            return TradeApi::fromException($e);
        }
    }

    public function unblock(string $profile_id, TradeModerationService $moderation): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);
        $target = TradeUser::findPublic($profile_id);
        if ($target === null) {
            return TradeApi::error('TRADE_NOT_FOUND', 'User not found.', 404);
        }
        $moderation->unblock(auth('trades')->user(), $target);

        return TradeApi::ok(['blocked' => false]);
    }
}
