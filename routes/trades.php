<?php

use App\Http\Controllers\Trades\ActivityController;
use App\Http\Controllers\Trades\Api\BrainrotController;
use App\Http\Controllers\Trades\Api\JoinRequestController;
use App\Http\Controllers\Trades\Api\MeController;
use App\Http\Controllers\Trades\Api\NotificationController;
use App\Http\Controllers\Trades\Api\ReportController;
use App\Http\Controllers\Trades\Api\TradeController;
use App\Http\Controllers\Trades\Api\UserController;
use App\Http\Controllers\Trades\CompletedController;
use App\Http\Controllers\Trades\ListingController;
use App\Http\Controllers\Trades\NotificationPageController;
use App\Http\Controllers\Trades\PostController;
use App\Http\Controllers\Trades\ProfileController;
use App\Http\Controllers\Trades\RobloxAuthController;
use App\Http\Controllers\Trades\TradeShowController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', function () {
    $body = "User-agent: *\nAllow: /\nAllow: /completed\nDisallow: /post\nDisallow: /activity\nDisallow: /notifications\nDisallow: /auth/\nDisallow: /api/\n";

    return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
});

Route::get('/', ListingController::class);
Route::get('/completed', CompletedController::class);
Route::get('/post', [PostController::class, 'create']);
Route::get('/t/{public_id}', TradeShowController::class);
Route::get('/u/{roblox_sub}', ProfileController::class);

Route::get('/auth/roblox', [RobloxAuthController::class, 'show']);
Route::post('/auth/roblox', [RobloxAuthController::class, 'redirect']);
Route::get('/auth/roblox/callback', [RobloxAuthController::class, 'callback']);
Route::post('/logout', [RobloxAuthController::class, 'logout']);

Route::get('/api/v1/brainrots/search', [BrainrotController::class, 'search']);
Route::get('/api/v1/brainrots/{brainrot}/trade-options', [BrainrotController::class, 'options']);
Route::get('/api/v1/trades', [TradeController::class, 'index']);
Route::get('/api/v1/trades/completed', [TradeController::class, 'completed']);
Route::get('/api/v1/trades/{public_id}', [TradeController::class, 'show']);
Route::get('/api/v1/users/{roblox_sub}', [UserController::class, 'show']);
Route::get('/api/v1/users/{roblox_sub}/trades', [UserController::class, 'trades']);

Route::middleware('trades.auth')->group(function (): void {
    Route::get('/activity', ActivityController::class);
    Route::get('/notifications', NotificationPageController::class);

    Route::get('/api/v1/me', [MeController::class, 'show']);
    Route::post('/api/v1/auth/logout', [MeController::class, 'logout']);
    Route::post('/api/v1/trades', [TradeController::class, 'store']);
    Route::post('/api/v1/trades/{public_id}/cancel', [TradeController::class, 'cancel']);
    Route::post('/api/v1/trades/{public_id}/hide', [TradeController::class, 'hide']);
    Route::post('/api/v1/trades/{public_id}/join', [TradeController::class, 'join']);
    Route::get('/api/v1/trades/{public_id}/join-requests', [TradeController::class, 'joinRequests']);
    Route::post('/api/v1/trades/{public_id}/confirm', [TradeController::class, 'confirm']);
    Route::post('/api/v1/join-requests/{public_id}/accept', [JoinRequestController::class, 'accept']);
    Route::post('/api/v1/join-requests/{public_id}/reject', [JoinRequestController::class, 'reject']);
    Route::post('/api/v1/join-requests/{public_id}/cancel', [JoinRequestController::class, 'cancel']);
    Route::get('/api/v1/activity', [TradeController::class, 'activity']);
    Route::get('/api/v1/notifications', [NotificationController::class, 'index']);
    Route::get('/api/v1/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/api/v1/notifications/{id}/read', [NotificationController::class, 'read']);
    Route::post('/api/v1/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('/api/v1/reports', [ReportController::class, 'store']);
    Route::post('/api/v1/reports/{public_id}/review', [ReportController::class, 'review']);
    Route::post('/api/v1/users/{roblox_sub}/block', [UserController::class, 'block']);
    Route::delete('/api/v1/users/{roblox_sub}/block', [UserController::class, 'unblock']);
});
