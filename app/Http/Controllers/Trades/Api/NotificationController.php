<?php

namespace App\Http\Controllers\Trades\Api;

use App\Exceptions\TradeException;
use App\Http\Controllers\Controller;
use App\Services\Trades\TradeNotificationService;
use App\Support\TradeApi;
use App\Support\TradePresenter;
use App\Support\TradeQueryRules;
use App\Support\TradeSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request, TradeNotificationService $notifications): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);
        $input = TradeApi::validated($request, TradeQueryRules::notifications());
        if ($input instanceof JsonResponse) {
            return $input;
        }
        $page = $notifications->forUser(auth('trades')->user(), $input['page'] ?? 1);

        return TradeApi::ok([
            'items' => collect($page->items())->map(fn ($row) => TradePresenter::notification($row->loadMissing('listing')))->all(),
            'page' => $page->currentPage(),
        ]);
    }

    public function unreadCount(TradeNotificationService $notifications): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);

        return TradeApi::ok(['unread_count' => $notifications->unreadCount(auth('trades')->user())]);
    }

    public function read(int $id, TradeNotificationService $notifications): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);
        try {
            return TradeApi::ok(['notification' => TradePresenter::notification($notifications->markRead(auth('trades')->user(), $id))]);
        } catch (TradeException $e) {
            return TradeApi::fromException($e);
        }
    }

    public function readAll(TradeNotificationService $notifications): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);

        return TradeApi::ok(['updated' => $notifications->markAllRead(auth('trades')->user())]);
    }
}
