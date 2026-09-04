<?php

use App\Services\Seo\SabRotCalculatorSyncService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('seo:sab-calculator-refresh', function () {
    try {
        $result = app(SabRotCalculatorSyncService::class)->refresh();
        $this->info('SAB calculator refresh completed.');
        $this->line('Items: '.$result['items']);
        $this->line('Current values: '.$result['current_values']);
        $this->line('Observations: '.$result['observations']);
        $this->line('JSON files: '.$result['json_files']);
        $this->line('Skipped remote: '.$result['skipped_remote']);
    } catch (\Throwable $e) {
        $this->error($e->getMessage());

        return 1;
    }

    return 0;
})->purpose('Fetch rot.rocks prices for listed items, write observations, and merge per-item price JSON');
