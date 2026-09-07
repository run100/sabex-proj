<?php

namespace App\Http\Controllers\Trades\Api;

use App\Http\Controllers\Controller;
use App\Services\Trades\TradeNotificationService;
use App\Support\TradeApi;
use App\Support\TradePresenter;
use App\Support\TradeSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(TradeNotificationService $notifications): JsonResponse
    {
        $user = auth('trades')->user();
        $unread = ($user && TradeSchema::ready())
            ? $notifications->unreadCount($user)
            : 0;

        return TradeApi::ok([
            'user' => TradePresenter::userNav($user),
            'unread_count' => $unread,
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
