<?php

use App\Http\Middleware\AdminAllowIp;
use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\AdminNoIndex;
use App\Http\Middleware\TradesAuthenticate;
use App\Support\SabHost;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('seo:sab-calculator-refresh')
            ->dailyAt(env('SAB_CALCULATOR_SYNC_AT', '04:30'))
            ->when(fn () => filter_var(env('SAB_CALCULATOR_SYNC_ENABLED', true), FILTER_VALIDATE_BOOL))
            ->withoutOverlapping(120)
            ->appendOutputTo(storage_path('logs/sab-calculator-sync.log'));
        $schedule->command('seo:trade-expire')
            ->everyFiveMinutes()
            ->withoutOverlapping(10)
            ->appendOutputTo(storage_path('logs/sab-trade-expire.log'));
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustHosts(at: fn () => SabHost::trustedHosts());
        $middleware->alias([
            'admin.ip' => AdminAllowIp::class,
            'admin.auth' => AdminAuthenticate::class,
            'admin.noindex' => AdminNoIndex::class,
            'trades.auth' => TradesAuthenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
