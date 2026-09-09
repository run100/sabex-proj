<?php

use App\Services\Seo\SabGeoflowFileSyncService;
use App\Services\Seo\SabRotCalculatorSyncService;
use App\Services\Trades\TradeListingService;
use App\Support\TradeSchema;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('seo:trade-expire', function () {
    if (! TradeSchema::ready()) {
        $this->warn('seo_trade_* tables are not installed.');

        return 0;
    }
    $count = app(TradeListingService::class)->expireDue();
    $this->info('Expired trades: '.$count);

    return 0;
})->purpose('Expire open and pending SAB trades past expires_at');

Artisan::command('seo:sab-calculator-refresh', function () {
    try {
        $result = app(SabRotCalculatorSyncService::class)->refresh();
        $this->info('SAB calculator refresh completed.');
        $this->line('synced_at: '.$result['synced_at']);
        $this->line('duration_ms: '.$result['duration_ms']);
        $this->line('Remote: brainrots='.$result['remote_brainrots'].' mutations='.$result['remote_mutations'].' traits='.$result['remote_traits']);
        $this->line('Items: processed='.$result['items'].' new='.$result['new_items']);
        if ((int) $result['new_items'] > 0) {
            $this->line('New slugs: '.implode(', ', $result['new_item_slugs']));
        }
        $this->line('Mutations: upserted='.$result['mutations_upserted'].' new='.$result['new_mutations']);
        $this->line('Current values: '.$result['current_values'].' changed='.$result['values_changed'].' unchanged='.$result['values_unchanged']);
        $this->line('Observations: '.$result['observations']);
        $this->line('JSON files: '.$result['json_files']);
        $this->line('Traits: '.$result['traits'].' new='.$result['new_traits']);
        $this->line('Images downloaded: '.$result['images_downloaded']);
        $this->line('Meta: '.$result['meta_path']);
        Log::info('seo.sab-calculator-refresh', $result);
    } catch (\Throwable $e) {
        $this->error($e->getMessage());

        return 1;
    }

    return 0;
})->purpose('Fetch rot.rocks catalog and prices, upsert local calculator data, and refresh Last update');

Artisan::command('seo:sab-geoflow-sync {--dry-run} {--files-only} {--tables-only}', function () {
    $files = app(SabGeoflowFileSyncService::class);
    if (! $this->option('files-only')) {
        $export = $files->runExport((bool) $this->option('dry-run'));
        $this->line($export['output'] !== '' ? $export['output'] : 'GEOFlow export finished.');
        if (! $export['ok']) {
            return 1;
        }
    }
    if (! $this->option('tables-only')) {
        if ($this->option('dry-run') && $this->option('files-only')) {
            $this->info('dry-run: file checksum compare only.');
        }
        foreach ($files->syncFiles((bool) $this->option('dry-run')) as $row) {
            $this->line($row['status'].' '.$row['destination']);
        }
    }

    return 0;
})->purpose('Upsert GEOFlow SAB tables (read-only source) and checksum-copy support JSON files');
