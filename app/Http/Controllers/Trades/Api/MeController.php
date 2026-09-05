<?php

namespace App\Http\Controllers\Trades\Api;

use App\Http\Controllers\Controller;
use App\Services\Trades\TradeNotificationService;
use App\Support\TradeApi;
use App\Support\TradePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(TradeNotificationService $notifications): JsonResponse
    {
        $user = auth('trades')->user();

        return TradeApi::ok([
            'user' => TradePresenter::userPrivate($user),
            'unread_count' => $notifications->unreadCount($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        auth('trades')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return TradeApi::ok(['ok' => true]);
    }
}
