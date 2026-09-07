<?php

use App\Http\Controllers\Trades\CompletedController;
use App\Http\Controllers\Trades\ListingController;
use App\Http\Controllers\Trades\PendingController;
use App\Http\Controllers\Trades\PostController;
use App\Http\Controllers\Trades\TradeShowController;
use App\Support\TradePaths;
use Illuminate\Support\Facades\Route;

Route::middleware('cache.private')->prefix('trading')->group(function (): void {
    Route::get('/', ListingController::class);
    Route::get('/new', [PostController::class, 'create']);
    Route::get('/pending', PendingController::class);
    Route::get('/completed', CompletedController::class);
    Route::get('/{ulid}', TradeShowController::class)->where('ulid', TradePaths::ULID);
});
