<?php

namespace App\Http\Controllers\Trades\Api;

use App\Http\Controllers\Controller;
use App\Services\Trades\TradeListingService;
use App\Services\Trades\TradeNotificationService;
use App\Support\TradeApi;
use App\Support\TradePresenter;
use App\Support\TradeSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MeController extends Controller
{
    public function show(TradeNotificationService $notifications, TradeListingService $listings): JsonResponse
    {
        $user = auth('trades')->user();
        $unread = ($user && TradeSchema::ready())
            ? $notifications->unreadCount($user)
            : 0;
        $openCount = TradeSchema::ready()
            ? (int) Cache::remember('trade:open_public_recent_count', 7200, fn () => $listings->publicOpenCount())
            : 0;

        return TradeApi::ok([
            'user' => TradePresenter::userNav($user),
            'unread_count' => $unread,
            'open_listings_count' => $openCount,
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
