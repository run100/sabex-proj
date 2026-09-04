<?php

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
    })
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
