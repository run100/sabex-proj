<?php

use App\Http\Controllers\Trades\Api\BrainrotController;
use App\Http\Controllers\Trades\Api\JoinRequestController;
use App\Http\Controllers\Trades\Api\MeController;
use App\Http\Controllers\Trades\Api\NotificationController;
// use App\Http\Controllers\Trades\Api\ReportController;
use App\Http\Controllers\Trades\Api\TradeController;
use App\Http\Controllers\Trades\Api\UserController;
use App\Http\Middleware\TradeApiNoIndex;
use App\Support\TradePaths;
use Illuminate\Support\Facades\Route;

Route::middleware([TradeApiNoIndex::class, 'cache.private'])->prefix('api/v1')->group(function (): void {
    Route::get('/me', [MeController::class, 'show']);
    Route::get('/brainrots/search', [BrainrotController::class, 'search']);
    Route::get('/brainrots/{brainrot}/trade-options', [BrainrotController::class, 'options']);
    Route::get('/trading/trades', [TradeController::class, 'index']);
    Route::get('/trading/trades/pending', [TradeController::class, 'pending']);
    Route::get('/trading/trades/completed', [TradeController::class, 'completed']);
    Route::get('/trading/trades/{ulid}', [TradeController::class, 'show'])->where('ulid', TradePaths::ULID);
    Route::get('/users/{profile_id}', [UserController::class, 'show'])->where('profile_id', TradePaths::ULID);
    Route::get('/users/{profile_id}/trades', [UserController::class, 'trades'])->where('profile_id', TradePaths::ULID);

    Route::middleware('trades.auth')->group(function (): void {
        Route::post('/auth/logout', [MeController::class, 'logout']);
        Route::get('/activity', [TradeController::class, 'activity']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'read']);
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
        Route::post('/trading/trades', [TradeController::class, 'store']);
        Route::post('/trading/trades/{ulid}/cancel', [TradeController::class, 'cancel'])->where('ulid', TradePaths::ULID);
        Route::post('/trading/trades/{ulid}/hide', [TradeController::class, 'hide'])->where('ulid', TradePaths::ULID);
        Route::post('/trading/trades/{ulid}/join', [TradeController::class, 'join'])->where('ulid', TradePaths::ULID);
        Route::get('/trading/trades/{ulid}/messages', [TradeController::class, 'messages'])->where('ulid', TradePaths::ULID);
        Route::post('/trading/trades/{ulid}/messages', [TradeController::class, 'sendMessage'])->where('ulid', TradePaths::ULID);
        Route::get('/trading/trades/{ulid}/join-requests', [TradeController::class, 'joinRequests'])->where('ulid', TradePaths::ULID);
        Route::post('/trading/trades/{ulid}/confirm', [TradeController::class, 'confirm'])->where('ulid', TradePaths::ULID);
        Route::post('/trading/join-requests/{ulid}/accept', [JoinRequestController::class, 'accept'])->where('ulid', TradePaths::ULID);
        Route::post('/trading/join-requests/{ulid}/reject', [JoinRequestController::class, 'reject'])->where('ulid', TradePaths::ULID);
        Route::post('/trading/join-requests/{ulid}/cancel', [JoinRequestController::class, 'cancel'])->where('ulid', TradePaths::ULID);
        // Route::post('/reports', [ReportController::class, 'store']);
        // Route::post('/reports/{public_id}/review', [ReportController::class, 'review']);
        Route::post('/users/{profile_id}/block', [UserController::class, 'block'])->where('profile_id', TradePaths::ULID);
        Route::delete('/users/{profile_id}/block', [UserController::class, 'unblock'])->where('profile_id', TradePaths::ULID);
    });
});
