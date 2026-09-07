<?php

use App\Http\Controllers\Trades\AccountController;
use App\Http\Controllers\Trades\ActivityController;
use App\Http\Controllers\Trades\EmailAuthController;
use App\Http\Controllers\Trades\LocalAuthController;
use App\Http\Controllers\Trades\NotificationPageController;
use App\Http\Controllers\Trades\ProfileController;
use App\Http\Controllers\Trades\RobloxAuthController;
use App\Support\TradeEmailAuth;
use App\Support\TradeLocalAuth;
use App\Support\TradePaths;
use Illuminate\Support\Facades\Route;

Route::middleware('cache.private')->group(function (): void {
Route::get('/profile/{profile_id}', ProfileController::class)->where('profile_id', TradePaths::ULID);

Route::get('/auth/roblox', [RobloxAuthController::class, 'show']);
Route::post('/auth/roblox', [RobloxAuthController::class, 'redirect']);
Route::get('/auth/roblox/callback', [RobloxAuthController::class, 'callback']);
Route::post('/logout', [RobloxAuthController::class, 'logout']);

Route::get('/auth/email/verify/{id}/{hash}', [EmailAuthController::class, 'verify'])
    ->middleware('signed')
    ->name('trades.email.verify');

if (TradeEmailAuth::loginAllowed()) {
    Route::get('/auth/email', [EmailAuthController::class, 'show']);
    Route::post('/auth/email', [EmailAuthController::class, 'login'])->middleware('throttle:10,60');
    Route::get('/auth/email/check', [EmailAuthController::class, 'check']);
    Route::post('/auth/email/resend', [EmailAuthController::class, 'resend'])->middleware('throttle:10,60');
}

if (TradeEmailAuth::registerAllowed()) {
    Route::get('/auth/register', [EmailAuthController::class, 'registerForm']);
    Route::post('/auth/register', [EmailAuthController::class, 'register'])->middleware('throttle:10,60');
}

if (TradeLocalAuth::enabled()) {
    Route::post('/auth/local/register', [LocalAuthController::class, 'register']);
    Route::post('/auth/local/login', [LocalAuthController::class, 'login']);
}

Route::middleware('trades.auth')->group(function (): void {
    Route::get('/user', [AccountController::class, 'show']);
    Route::get('/user/offers', ActivityController::class);
    Route::get('/notifications', NotificationPageController::class);
});
});
