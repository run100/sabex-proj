<?php

use App\Support\SabHost;
use Illuminate\Support\Facades\Route;

Route::domain(SabHost::host('admin'))->group(function (): void {
    require __DIR__.'/admin.php';
});

Route::domain(SabHost::host('trades'))->group(function (): void {
    require __DIR__.'/trades.php';
});

$registerWww = static function (): void {
    include __DIR__.'/www.php';
};

foreach (array_values(array_unique(array_filter([
    SabHost::host('www'),
    'localhost',
    '127.0.0.1',
]))) as $wwwHost) {
    Route::domain($wwwHost)->group($registerWww);
}
