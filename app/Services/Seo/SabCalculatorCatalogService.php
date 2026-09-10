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

        $syncedAt ??= now()->toIso8601String();
        $meta = $this->readMeta();
        $chunkIds = array_keys($chunks);
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
                'counts' => [
                    'items' => count($index),
                    'mutations' => $mutationCount,
                    'chunks' => count($chunkIds),
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
        foreach ($chunkIds as $chunkId) {
            $bytes += filesize($releaseRoot.'/chunks/'.$chunkId.'.json') ?: 0;
        }

        return [
            'version' => $version,
            'manifest_path' => $manifestPath,
            'items' => count($index),
            'mutations' => $mutationCount,
            'chunks' => count($chunkIds),
            'bytes' => $bytes,
        ];
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
