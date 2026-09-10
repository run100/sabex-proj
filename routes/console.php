<?php

use App\Services\Seo\SabGeoflowFileSyncService;
use App\Services\Seo\SabCalculatorCatalogService;
use App\Services\Seo\SabRenderService;
use App\Services\Seo\SabRotCalculatorSyncService;
use App\Models\SeoSite;
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
        $result = app(SabRotCalculatorSyncService::class)->refresh(
            fn (string $message) => $this->line($message),
        );
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
        $this->line('Catalog: version='.$result['catalog_version']
            .' items='.$result['catalog_items']
            .' mutations='.$result['catalog_mutations']
            .' chunks='.$result['catalog_chunks']
            .' value_rows='.$result['catalog_value_list_rows']
            .' bytes='.$result['catalog_bytes']);
        $this->line('Catalog manifest: '.$result['catalog_manifest_path']);
        Log::info('seo.sab-calculator-refresh', $result);
    } catch (\Throwable $e) {
        Log::error('seo.sab-calculator-refresh', ['error' => $e->getMessage()]);
        $this->error($e->getMessage());

        return 1;
    }

    return 0;
})->purpose('Fetch rot.rocks catalog and prices, upsert local calculator data, and refresh Last update');

Artisan::command('seo:sab-calculator-catalog', function () {
    try {
        $site = SeoSite::query()
            ->where('slug', SabRenderService::SITE_SLUG)
            ->firstOrFail();
        $result = app(SabCalculatorCatalogService::class)->publish(
            $site,
            null,
            null,
            fn (string $message) => $this->line($message),
        );

        $this->info('SAB calculator catalog completed.');
        $this->line('Catalog: version='.$result['version']
            .' items='.$result['items']
            .' mutations='.$result['mutations']
            .' chunks='.$result['chunks']
            .' value_rows='.$result['value_list_rows']
            .' bytes='.$result['bytes']);
        $this->line('Catalog manifest: '.$result['manifest_path']);
        Log::info('seo.sab-calculator-catalog', $result);
    } catch (\Throwable $e) {
        Log::error('seo.sab-calculator-catalog', ['error' => $e->getMessage()]);
        $this->error($e->getMessage());

        return 1;
    }

    return 0;
})->purpose('Generate the local SAB calculator catalog without fetching rot.rocks');

Artisan::command('seo:sab-price-history-backfill {--slug=* : Item slug to backfill; omit to process all eligible items} {--all : Same as omitting --slug (kept for compatibility)} {--sleep-ms=150 : Milliseconds to wait after each price-history API request} {--limit=0 : Process only the first N slugs (0 = no limit)}', function () {
    $service = app(SabRotCalculatorSyncService::class);
    $slugs = array_values(array_filter(array_map(
        fn ($slug): string => trim((string) $slug),
        (array) $this->option('slug')
    )));

    if ($slugs === []) {
        $slugs = $service->resolveBackfillSlugs();
    }

    $limit = max(0, (int) $this->option('limit'));
    if ($limit > 0) {
        $slugs = array_slice($slugs, 0, $limit);
    }

    $sleepMs = max(0, (int) $this->option('sleep-ms'));

    try {
        $result = $service->backfillPriceHistory(
            $slugs,
            $sleepMs,
            fn (string $message) => $this->line($message),
        );
        $this->info('Written='.(int) ($result['written'] ?? 0).' skipped='.(int) ($result['skipped'] ?? 0));
        Log::info('seo.sab-price-history-backfill', [
            'written' => $result['written'] ?? 0,
            'skipped' => $result['skipped'] ?? 0,
            'item_count' => count($result['items'] ?? []),
        ]);
    } catch (\Throwable $e) {
        Log::error('seo.sab-price-history-backfill', ['error' => $e->getMessage()]);
        $this->error($e->getMessage());

        return 1;
    }

    return 0;
})->purpose('Backfill rot.rocks price-history points into sab-price-history JSON files');

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
