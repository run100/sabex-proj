<?php

use App\Http\Controllers\Admin\AccessLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CodesController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\JobController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\SiteController;
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
        Route::post('/api/items/bulk', [ItemController::class, 'bulk']);
        Route::get('/api/value-sources', [ItemController::class, 'valueSources']);
        Route::get('/api/items/{item}/observations', [ItemController::class, 'observations']);
        Route::put('/api/items/{item}/variants', [ItemController::class, 'updateVariants']);
        Route::put('/api/items/{item}/translations', [ItemController::class, 'updateTranslations']);
        Route::put('/api/items/{item}/aliases', [ItemController::class, 'updateAliases']);
        Route::get('/api/items/{item}', [ItemController::class, 'show']);
        Route::patch('/api/items/{item}', [ItemController::class, 'update']);
        Route::get('/api/sites', [SiteController::class, 'index']);
        Route::patch('/api/sites/{site}', [SiteController::class, 'update']);
        Route::get('/api/news', [NewsController::class, 'index']);
        Route::post('/api/news', [NewsController::class, 'store']);
        Route::post('/api/news/images', [NewsController::class, 'uploadImage']);
        Route::patch('/api/news/{article}', [NewsController::class, 'update']);
        Route::get('/api/codes', [CodesController::class, 'show']);
        Route::put('/api/codes', [CodesController::class, 'update']);
        Route::post('/api/jobs/geoflow', [JobController::class, 'geoflow']);
        Route::post('/api/jobs/calculator', [JobController::class, 'calculator']);
        Route::get('/api/trade-users', [TradeUserController::class, 'index']);
        Route::get('/api/trade-users/{tradeUser}', [TradeUserController::class, 'show']);
        Route::patch('/api/trade-users/{tradeUser}', [TradeUserController::class, 'update']);
        Route::get('/api/trade-listings', [TradeListingController::class, 'index']);
        Route::get('/api/trade-listings/{listing}', [TradeListingController::class, 'show'])->whereNumber('listing');
        Route::patch('/api/trade-listings/{listing}', [TradeListingController::class, 'update'])->whereNumber('listing');
        Route::get('/api/trade-joins', [TradeJoinController::class, 'index']);
        Route::delete('/api/trade-joins/{join}', [TradeJoinController::class, 'destroy'])->whereNumber('join');
        Route::get('/api/trade-reports', [TradeReportController::class, 'index']);
        Route::patch('/api/trade-reports/{report}', [TradeReportController::class, 'update'])->whereNumber('report');
        Route::get('/api/trade-email-codes', [TradeEmailCodeController::class, 'index']);
        Route::get('/api/access-logs', [AccessLogController::class, 'index']);

        Route::get('/{any?}', SpaController::class)->where('any', '^(?!api/).*$');
    });
});
