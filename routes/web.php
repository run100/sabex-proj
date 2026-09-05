<?php

use App\Support\SabHost;
use Illuminate\Support\Facades\Route;

Route::domain(SabHost::host('admin'))->group(function (): void {
    require __DIR__.'/admin.php';
});

$registerWww = static function (): void {
    include __DIR__.'/www.php';
    require __DIR__.'/trading.php';
    require __DIR__.'/user.php';
    require __DIR__.'/api.php';
};

foreach (array_values(array_unique(array_filter([
    SabHost::host('www'),
    'localhost',
    '127.0.0.1',
]))) as $wwwHost) {
    Route::domain($wwwHost)->group($registerWww);
}
