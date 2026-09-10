<?php

namespace App\Services\Seo;

use App\Models\SeoGame;
use App\Models\SeoItem;
use App\Models\SeoSite;
use App\Models\SeoValueSource;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SabCalculatorCatalogService
{
    public const SCHEMA_VERSION = 1;

    public const PUBLIC_BASE_PATH = '/data/calc/sab';

    private const CHUNK_SIZE = 64;

    private const STORAGE_ROOT = 'app/calc/sab';

    /**
     * @return array{
     *     version: string,
     *     manifest_path: string,
     *     items: int,
     *     mutations: int,
     *     chunks: int,
     *     value_list_rows: int,
     *     bytes: int
     * }
     */
    public function publish(
        SeoSite $site,
        ?SeoGame $game = null,
        ?string $syncedAt = null,
        ?callable $onProgress = null,
    ): array {
        $game ??= SeoGame::query()
            ->where('seo_site_id', $site->id)
            ->where('slug', SabSiteContext::GAME_SLUG)
            ->firstOrFail();

        $sourceId = SeoValueSource::query()
            ->where('slug', SabRotCalculatorSyncService::SOURCE_SLUG)
            ->value('id');
        $orderedIds = SeoItem::query()
            ->where('seo_game_id', $game->id)
            ->orderByDesc('sort_order')
            ->orderBy('name')
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $this->progress($onProgress, 'Building calculator catalog: '.count($orderedIds).' database items...');

        $chunks = [];
        $index = [];
        $valueListSourceRows = [];
        $mutationCount = 0;
        $calculator = app(SabRenderService::class);

        foreach (array_chunk($orderedIds, self::CHUNK_SIZE) as $chunkNumber => $ids) {
            $items = SeoItem::query()
                ->whereIn('id', $ids)
                ->with([
                    'variants' => function ($query) use ($sourceId): void {
                        $query->whereIn('variant_type', ['base', 'mutation'])
                            ->with([
                                'currentValues' => function ($query) use ($sourceId): void {
                                    if ($sourceId !== null) {
                                        $query->where('seo_value_source_id', $sourceId);
                                    }
                                    $query->with('source');
                                },
                            ]);
                    },
                ])
                ->get()
                ->keyBy('id');

            $orderedItems = collect($ids)
                ->map(fn (int $id): ?SeoItem => $items->get($id))
                ->filter(fn ($item): bool => $item instanceof SeoItem)
                ->values();
            if ($orderedItems->isEmpty()) {
                continue;
            }

            foreach ($orderedItems as $item) {
                $rot = data_get($item->attributes_json, 'rot_rocks', []);
                $baseVariant = $item->variants->firstWhere('variant_key', 'base')
                    ?? $item->variants->firstWhere('variant_type', 'base');
                $baseCurrentValue = $baseVariant?->currentValues->first(
                    fn ($value): bool => $value->source?->slug === SabRotCalculatorSyncService::SOURCE_SLUG,
                );
                $hasSourceValue = $item->variants->contains(
                    fn ($variant): bool => $variant->currentValues->contains(
                        fn ($value): bool => $value->source?->slug === SabRotCalculatorSyncService::SOURCE_SLUG,
                    ),
                );
                if ((bool) $item->is_listed && (data_get($item->attributes_json, 'rot_rocks') !== null || $hasSourceValue)) {
                    $valueListSourceRows[] = [
                        'slug' => (string) $item->slug,
                        'name' => (string) $item->name,
                        'summary' => (string) ($item->summary ?? ''),
                        'rarity' => (string) ($item->rarity ?? ''),
                        'is_publish_html' => (bool) ($item->is_publish_html ?? false),
                        'image' => SabRenderService::listingImageSrc($item),
                        'currentValue' => $baseCurrentValue?->value_normalized !== null
                            ? (float) $baseCurrentValue->value_normalized
                            : (is_numeric(data_get($rot, 'robux_value')) ? (float) data_get($rot, 'robux_value') : null),
                        'currentDemand' => (string) ($baseCurrentValue?->demand ?? ''),
                        'demand' => (string) data_get($rot, 'demand'),
                        'trend' => (string) data_get($rot, 'trend'),
                        'mutationLabels' => $item->variants
                            ->filter(fn ($variant): bool => in_array((string) ($variant->variant_type ?? ''), ['base', 'mutation'], true))
                            ->map(function ($variant): string {
                                if ((string) ($variant->variant_type ?? '') === 'base') {
                                    return 'Default';
                                }

                                return trim((string) ($variant->mutation_name ?: $variant->variant_name ?: ''));
                            })
                            ->filter()
                            ->unique(fn (string $label): string => strtolower($label))
                            ->values()
                            ->all(),
                    ];
                }
            }

            $rows = $calculator->calculatorData($orderedItems, $site)['brainrots'] ?? [];
            if (! is_array($rows) || $rows === []) {
                continue;
            }

            $chunkId = sprintf('%02d', $chunkNumber);
            $chunks[$chunkId] = array_values($rows);
            $mutationCount += array_sum(array_map(
                static fn (array $row): int => count(is_array($row['mutations'] ?? null) ? $row['mutations'] : []),
                $chunks[$chunkId],
            ));

            foreach ($chunks[$chunkId] as $row) {
                $index[] = [
                    'id' => (string) ($row['id'] ?? ''),
                    'slug' => (string) ($row['slug'] ?? ''),
                    'name' => (string) ($row['name'] ?? ''),
                    'image' => $row['image'] ?? null,
                    'baseIncome' => (float) ($row['baseIncome'] ?? 0),
                    'rarity' => (string) ($row['rarity'] ?? ''),
                    'isNew' => (bool) ($row['isNew'] ?? false),
                    'chunk' => $chunkId,
                ];
            }

            $this->progress($onProgress, 'Catalog chunk '.$chunkId.' written in memory: '.count($chunks[$chunkId]).' items.');
        }

        if ($index === []) {
            throw new \RuntimeException('Calculator catalog is empty; previous manifest was kept.');
        }

        $syncedAt ??= now()->toIso8601String();
        $meta = $this->readMeta();
        $chunkIds = array_keys($chunks);
        $valueList = $this->buildValueListData($valueListSourceRows);
        $seed = json_encode([
            'schema_version' => self::SCHEMA_VERSION,
            'synced_at' => $syncedAt,
            'items' => $index,
            'traits' => $meta['traits'],
            'streakMultipliers' => $meta['streakMultipliers'],
            'chunks' => $chunkIds,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $version = now()->format('YmdHis').'-'.substr(sha1($seed), 0, 12);

        $bootstrap = [
            'schema_version' => self::SCHEMA_VERSION,
            'version' => $version,
            'synced_at' => $syncedAt,
            'items' => $index,
            'traits' => $meta['traits'],
            'streakMultipliers' => $meta['streakMultipliers'],
        ];
        $encodedBootstrap = $this->encode($bootstrap);
        $encodedChunks = [];
        foreach ($chunks as $chunkId => $rows) {
            $encodedChunks[$chunkId] = $this->encode([
                'schema_version' => self::SCHEMA_VERSION,
                'version' => $version,
                'chunk' => $chunkId,
                'brainrots' => $rows,
            ]);
        }
        $encodedValueList = $this->encode([
            'schema_version' => self::SCHEMA_VERSION,
            'version' => $version,
            'synced_at' => $syncedAt,
            'rows' => $valueList['rows'],
            'today' => $valueList['today'],
        ]);

        $catalogRoot = self::catalogRootPath();
        $releasesRoot = $catalogRoot.'/releases';
        File::ensureDirectoryExists($releasesRoot);
        $temporaryRoot = $releasesRoot.'/.tmp-'.Str::uuid();
        $releaseRoot = $releasesRoot.'/'.$version;
        File::ensureDirectoryExists($temporaryRoot.'/chunks');

        try {
            File::put($temporaryRoot.'/bootstrap.json', $encodedBootstrap);
            foreach ($encodedChunks as $chunkId => $encodedChunk) {
                File::put($temporaryRoot.'/chunks/'.$chunkId.'.json', $encodedChunk);
            }
            File::put($temporaryRoot.'/value-list.json', $encodedValueList);

            if (! @rename($temporaryRoot, $releaseRoot)) {
                throw new \RuntimeException('Unable to publish calculator catalog release: '.$version);
            }

            $manifest = [
                'schema_version' => self::SCHEMA_VERSION,
                'version' => $version,
                'synced_at' => $syncedAt,
                'bootstrap' => self::PUBLIC_BASE_PATH.'/releases/'.$version.'/bootstrap.json',
                'chunks' => collect($chunkIds)->mapWithKeys(
                    fn (string $chunkId): array => [
                        $chunkId => self::PUBLIC_BASE_PATH.'/releases/'.$version.'/chunks/'.$chunkId.'.json',
                    ]
                )->all(),
                'value_list' => self::PUBLIC_BASE_PATH.'/releases/'.$version.'/value-list.json',
                'counts' => [
                    'items' => count($index),
                    'mutations' => $mutationCount,
                    'chunks' => count($chunkIds),
                    'value_list_rows' => count($valueList['rows']),
                ],
            ];
            $manifestPath = self::manifestPath();
            File::ensureDirectoryExists(dirname($manifestPath));
            $temporaryManifest = $manifestPath.'.tmp-'.Str::uuid();
            File::put($temporaryManifest, $this->encode($manifest));
            if (! @rename($temporaryManifest, $manifestPath)) {
                throw new \RuntimeException('Unable to publish calculator catalog manifest.');
            }
        } catch (\Throwable $e) {
            if (File::isDirectory($temporaryRoot)) {
                File::deleteDirectory($temporaryRoot);
            }

            throw $e;
        }

        $bytes = filesize($manifestPath) ?: 0;
        $bytes += filesize($releaseRoot.'/bootstrap.json') ?: 0;
        $bytes += filesize($releaseRoot.'/value-list.json') ?: 0;
        foreach ($chunkIds as $chunkId) {
            $bytes += filesize($releaseRoot.'/chunks/'.$chunkId.'.json') ?: 0;
        }

        return [
            'version' => $version,
            'manifest_path' => $manifestPath,
            'items' => count($index),
            'mutations' => $mutationCount,
            'chunks' => count($chunkIds),
            'value_list_rows' => count($valueList['rows']),
            'bytes' => $bytes,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $sourceRows
     * @return array{rows: list<array<string, mixed>>, today: array{gainer: ?array, loser: ?array}}
     */
    private function buildValueListData(array $sourceRows): array
    {
        $changesBySlug = collect(app(SabValueChangesService::class)
            ->changes(7, null, 'recent', 1000))
            ->keyBy(fn (array $change): string => (string) ($change['itemSlug'] ?? ''));
        $rows = [];

        foreach ($sourceRows as $sourceRow) {
                $slug = (string) ($sourceRow['slug'] ?? '');
                if ($slug === '') {
                    continue;
                }
                $change = $changesBySlug->get($slug);
                $currentValue = is_numeric($sourceRow['currentValue'] ?? null) ? (float) $sourceRow['currentValue'] : null;
                $previousValue = is_numeric($change['beforeValue'] ?? null) ? (float) $change['beforeValue'] : null;
                $delta = ($currentValue !== null && $previousValue !== null)
                    ? round($currentValue - $previousValue, 4)
                    : null;
                $deltaPct = ($previousValue !== null && $previousValue != 0.0 && $delta !== null)
                    ? round(($delta / $previousValue) * 100, 1)
                    : null;
                $direction = $delta === null ? 'stable' : ($delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'stable'));
                $demand = trim((string) (($sourceRow['demand'] ?? '')
                    ?: ($sourceRow['currentDemand'] ?? '')
                    ?: ($change['demandAfter'] ?? $change['demandBefore'] ?? '')));
                $trend = trim((string) ($sourceRow['trend'] ?? ''));
                $mutationLabels = array_values($sourceRow['mutationLabels'] ?? []);
                $rarityKey = SabRenderService::canonicalRarityKey($sourceRow['rarity'] ?? '');
                $rarityLabel = $rarityKey === ''
                    ? ''
                    : ($rarityKey === 'og' ? 'OG' : SabRenderService::canonicalRarityLabel($rarityKey));
                $item = new SeoItem([
                    'slug' => $slug,
                    'is_publish_html' => (bool) ($sourceRow['is_publish_html'] ?? false),
                ]);
                $canOpenProduct = SabRenderService::shouldLinkProduct($item);
                $name = (string) (($sourceRow['name'] ?? '') ?: $slug);
                $search = implode(' ', array_filter([
                    $name,
                    $rarityKey,
                    $rarityLabel,
                    $sourceRow['summary'] ?? '',
                    $currentValue !== null ? (string) (int) round($currentValue) : null,
                    $currentValue !== null ? number_format($currentValue, 0, '', '') : null,
                    $previousValue !== null ? (string) (int) round($previousValue) : null,
                    $previousValue !== null ? number_format($previousValue, 0, '', '') : null,
                    $direction,
                    $demand,
                    $trend,
                    implode(' ', $mutationLabels),
                ], fn ($value): bool => trim((string) $value) !== ''));

                $rows[] = [
                    'n' => $name,
                    's' => SabRenderService::productPublicSlug($slug),
                    'img' => (string) ($sourceRow['image'] ?? ''),
                    'link' => $canOpenProduct ? 1 : 0,
                    'cv' => $currentValue !== null ? number_format($currentValue) : '—',
                    'cvn' => $currentValue,
                    'pv' => $previousValue !== null ? number_format($previousValue) : '—',
                    'd' => $direction,
                    'dl' => match ($direction) {
                        'up' => 'Up',
                        'down' => 'Down',
                        default => 'Stable',
                    },
                    'dp' => $deltaPct === null ? '—' : (($deltaPct > 0 ? '+' : '').$deltaPct.'%'),
                    'dd' => $this->formatValueListDeltaLabel($delta),
                    'dm' => $demand !== '' ? Str::title($demand) : '—',
                    'tr' => $trend !== '' ? Str::title(strtolower(str_replace('_', ' ', $trend))) : '—',
                    'rk' => $rarityKey,
                    'r' => $rarityLabel,
                    'mut' => $mutationLabels,
                    'q' => strtolower($search),
                ];
        }

        usort($rows, static function (array $left, array $right): int {
            $leftValue = $left['cvn'] ?? null;
            $rightValue = $right['cvn'] ?? null;
            $leftHasValue = $leftValue !== null;
            $rightHasValue = $rightValue !== null;
            if ($leftHasValue !== $rightHasValue) {
                return $leftHasValue ? -1 : 1;
            }
            if ($leftHasValue && $rightHasValue && (float) $leftValue !== (float) $rightValue) {
                return (float) $rightValue <=> (float) $leftValue;
            }

            return strcasecmp((string) ($left['n'] ?? ''), (string) ($right['n'] ?? ''));
        });

        $valueChanges = app(SabValueChangesService::class);

        return [
            'rows' => $rows,
            'today' => [
                'gainer' => $valueChanges->topGainers(1, 1)[0] ?? null,
                'loser' => $valueChanges->topLosers(1, 1)[0] ?? null,
            ],
        ];
    }

    private function formatValueListDeltaLabel(?float $delta): string
    {
        if ($delta === null || $delta == 0.0) {
            return '—';
        }

        return ($delta > 0 ? '+' : '').rtrim(rtrim(number_format($delta, 2, '.', ''), '0'), '.');
    }

    public static function rootPath(): string
    {
        if (app()->environment('testing')) {
            return storage_path('framework/testing/calc/sab');
        }

        return storage_path(self::STORAGE_ROOT);
    }

    public static function catalogRootPath(): string
    {
        return self::rootPath().'/catalog';
    }

    public static function manifestPath(): string
    {
        return self::catalogRootPath().'/manifest.json';
    }

    public static function metaPath(): string
    {
        return self::rootPath().'/meta.json';
    }

    public static function legacyMetaPath(): string
    {
        if (app()->environment('testing')) {
            return storage_path('framework/testing/sab-calculator-meta.json');
        }

        return storage_path('app/seo/sab-calculator-meta.json');
    }

    public static function metaReadPath(): string
    {
        return is_file(self::metaPath()) ? self::metaPath() : self::legacyMetaPath();
    }

    public static function publicManifestUrl(): string
    {
        return self::PUBLIC_BASE_PATH.'/manifest.json';
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function readManifest(): ?array
    {
        $path = self::manifestPath();
        if (! is_file($path)) {
            return null;
        }

        $data = json_decode((string) File::get($path), true);

        return is_array($data) ? $data : null;
    }

    public static function currentValueListPath(): ?string
    {
        $manifest = self::readManifest();
        $version = trim((string) ($manifest['version'] ?? ''));
        if ($version === '') {
            return null;
        }

        $path = self::releasePath($version).'/value-list.json';

        return is_file($path) ? $path : null;
    }

    /**
     * @return array{rows: list<array<string, mixed>>, today: array<string, mixed>}|null
     */
    public static function readValueList(): ?array
    {
        $path = self::currentValueListPath();
        if ($path === null) {
            return null;
        }

        $data = json_decode((string) File::get($path), true);
        if (! is_array($data) || ! is_array($data['rows'] ?? null)) {
            return null;
        }

        return [
            'rows' => array_values($data['rows']),
            'today' => is_array($data['today'] ?? null) ? $data['today'] : [],
        ];
    }

    public static function releaseBootstrapPath(string $version): string
    {
        return self::releasePath($version).'/bootstrap.json';
    }

    public static function releaseChunkPath(string $version, string $chunk): string
    {
        return self::releasePath($version).'/chunks/'.$chunk.'.json';
    }

    public static function releasePath(string $version): string
    {
        if (! preg_match('/^[a-z0-9][a-z0-9-]*$/', $version)) {
            throw new \InvalidArgumentException('Invalid calculator catalog version.');
        }

        return self::catalogRootPath().'/releases/'.$version;
    }

    /**
     * @return array{traits: list<array<string, mixed>>, streakMultipliers: array<string, mixed>}
     */
    private function readMeta(): array
    {
        $path = self::metaReadPath();
        $data = is_file($path) ? json_decode((string) File::get($path), true) : null;

        if ($path !== self::metaPath() && is_array($data)) {
            File::ensureDirectoryExists(dirname(self::metaPath()));
            File::copy($path, self::metaPath());
        }

        return [
            'traits' => is_array($data['traits'] ?? null) ? array_values($data['traits']) : [],
            'streakMultipliers' => is_array($data['streakMultipliers'] ?? null)
                ? $data['streakMultipliers']
                : ['3' => 2, '6' => 3],
        ];
    }

    private function encode(array $payload): string
    {
        return json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }

    private function progress(?callable $onProgress, string $message): void
    {
        if (is_callable($onProgress)) {
            $onProgress($message);
        }
    }
}
