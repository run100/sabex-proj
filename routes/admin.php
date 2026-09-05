<?php

use App\Http\Controllers\Admin\AccessLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CodesController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\JobController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\SpaController;
use App\Http\Controllers\Admin\TradeEmailCodeController;
use App\Http\Controllers\Admin\TradeJoinController;
use App\Http\Controllers\Admin\TradeListingController;
use App\Http\Controllers\Admin\TradeReportController;
use App\Http\Controllers\Admin\TradeUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['admin.ip', 'admin.noindex'])->group(function (): void {
    Route::get('/robots.txt', function () {
        return response("User-agent: *\nDisallow: /\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    });

    Route::get('/login', [AuthController::class, 'show']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('admin.auth')->group(function (): void {
        Route::get('/api/items', [ItemController::class, 'index']);
        Route::patch('/api/items/{item}', [ItemController::class, 'update']);
        Route::get('/api/news', [NewsController::class, 'index']);
        Route::patch('/api/news/{article}', [NewsController::class, 'update']);
        Route::get('/api/codes', [CodesController::class, 'show']);
        Route::put('/api/codes', [CodesController::class, 'update']);
        Route::post('/api/jobs/geoflow', [JobController::class, 'geoflow']);
        Route::post('/api/jobs/calculator', [JobController::class, 'calculator']);
        Route::get('/api/trade-users', [TradeUserController::class, 'index']);
        Route::patch('/api/trade-users/{tradeUser}', [TradeUserController::class, 'update']);
        Route::get('/api/trade-listings', [TradeListingController::class, 'index']);
        Route::get('/api/trade-listings/{listing}', [TradeListingController::class, 'show']);
        Route::patch('/api/trade-listings/{listing}', [TradeListingController::class, 'update']);
        Route::get('/api/trade-joins', [TradeJoinController::class, 'index']);
        Route::get('/api/trade-reports', [TradeReportController::class, 'index']);
        Route::patch('/api/trade-reports/{report}', [TradeReportController::class, 'update']);
        Route::get('/api/trade-email-codes', [TradeEmailCodeController::class, 'index']);
        Route::get('/api/access-logs', [AccessLogController::class, 'index']);

        Route::get('/{any?}', SpaController::class)->where('any', '^(?!api/).*$');
    });
});
