<?php

namespace App\Services\Seo;

use App\Models\SeoGame;
use App\Models\SeoItem;
use App\Models\SeoItemCurrentValue;
use App\Models\SeoItemObservation;
use App\Models\SeoItemVariant;
use App\Models\SeoSite;
use App\Models\SeoValueSource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SabRotCalculatorSyncService
{
    public const SOURCE_SLUG = 'rot-rocks-calculator';

    public const IMAGE_ROOT = 'uploads/images/sab/calculator';

    private const LOCAL_IMAGE_ROOT = 'uploads/images/sab-calculator';

    private const BASE_URL = 'https://rot.rocks';

    private const META_PATH = 'app/seo/sab-calculator-meta.json';

    private ?int $nextObservationId = null;

    private int $imagesDownloaded = 0;

    public static function calculatorImageRoot(): string
    {
        return '/'.self::IMAGE_ROOT;
    }

    public static function calculatorMetaPath(): string
    {
        if (app()->environment('testing')) {
            return storage_path('framework/testing/sab-calculator-meta.json');
        }

        return storage_path(self::META_PATH);
    }

    /**
     * Fetch rot.rocks catalog and prices, upsert local items/variants/values,
     * merge today's price-history points, and rewrite calculator meta.
     *
     * @return array{
     *     items: int,
     *     new_items: int,
     *     new_item_slugs: list<string>,
     *     current_values: int,
     *     values_changed: int,
     *     values_unchanged: int,
     *     observations: int,
     *     json_files: int,
     *     traits: int,
     *     new_traits: int,
     *     mutations_upserted: int,
     *     new_mutations: int,
     *     images_downloaded: int,
     *     remote_brainrots: int,
     *     remote_mutations: int,
     *     remote_traits: int,
     *     synced_at: string,
     *     duration_ms: int,
     *     meta_path: string
     * }
     */
    public function refresh(): array
    {
        $this->ensureCliMemoryLimit();
        $startedAt = microtime(true);
        $this->imagesDownloaded = 0;

        $site = SeoSite::query()->firstOrCreate(
            ['slug' => SabRenderService::SITE_SLUG],
            [
                'name' => 'SAB Exist Count',
                'base_url' => 'https://sabexistcount.com',
                'output_path' => '',
            ]
        );
        $game = SeoGame::query()->firstOrCreate(
            [
                'seo_site_id' => $site->id,
                'slug' => 'steal-a-brainrot',
            ],
            ['name' => 'Steal a Brainrot']
        );
        SeoValueSource::query()->updateOrCreate(
            ['slug' => self::SOURCE_SLUG],
            [
                'name' => 'Rot Rocks Calculator',
                'url' => self::BASE_URL.'/trading/calculator',
                'parser_type' => 'json_api',
                'priority' => 80,
                'is_primary_source' => false,
                'enabled' => true,
            ]
        );
        $source = SeoValueSource::query()->where('slug', self::SOURCE_SLUG)->firstOrFail();

        $brainrots = $this->namedRows($this->fetchJson('/api/brainrots')['brainrots'] ?? []);
        $mutations = $this->namedRows($this->fetchJson('/api/mutations')['mutations'] ?? []);
        $traitsPayload = $this->fetchJson('/api/traits');
        $traits = $this->namedRows($traitsPayload['traits'] ?? []);
        $streakMultipliers = $traitsPayload['streakMultipliers'] ?? ['3' => 2, '6' => 3];
        $mutationById = collect($mutations)->keyBy('id');
        $previousTraitSlugs = $this->previousMetaTraitSlugs();

        $writer = app(SabPriceHistoryWriter::class);
        $today = now()->toDateString();
        $counts = [
            'items' => 0,
            'new_items' => 0,
            'new_item_slugs' => [],
            'current_values' => 0,
            'values_changed' => 0,
            'values_unchanged' => 0,
            'observations' => 0,
            'json_files' => 0,
            'traits' => count($traits),
            'new_traits' => 0,
            'mutations_upserted' => 0,
            'new_mutations' => 0,
            'images_downloaded' => 0,
            'remote_brainrots' => count($brainrots),
            'remote_mutations' => count($mutations),
            'remote_traits' => count($traits),
        ];

        foreach ($brainrots as $row) {
            if (! is_array($row) || trim((string) ($row['name'] ?? '')) === '') {
                continue;
            }

            $slug = Str::slug((string) $row['name']);
            $existed = SeoItem::query()
                ->where('seo_game_id', $game->id)
                ->where('slug', $slug)
                ->exists();
            $item = $this->upsertItem($game, $row);
            if (! $existed) {
                $counts['new_items']++;
                if (count($counts['new_item_slugs']) < 20) {
                    $counts['new_item_slugs'][] = $slug;
                }
            }

            $todayPoints = [];
            $base = $this->upsertBaseVariant($item);
            if (isset($row['robuxValue']) && $row['robuxValue'] !== null && $row['robuxValue'] !== '') {
                $valueResult = $this->upsertCurrentValue($base, $source, $row['robuxValue'], $row['demand'] ?? null);
                if ($valueResult['observed']) {
                    $counts['observations']++;
                }
                if ($valueResult['changed']) {
                    $counts['values_changed']++;
                } else {
                    $counts['values_unchanged']++;
                }
                $counts['current_values']++;
                $todayPoints[$base->id] = (float) $row['robuxValue'];
            }

            foreach ((array) ($row['mutationValues'] ?? []) as $mutationValue) {
                if (! is_array($mutationValue)) {
                    continue;
                }
                $mutation = $mutationById->get($mutationValue['mutationId'] ?? '');
                $value = $mutationValue['robuxValue'] ?? null;
                if (! is_array($mutation) || $value === null || $value === '') {
                    continue;
                }
                $variant = $this->upsertMutationVariant($item, $mutation, $mutationValue);
                $counts['mutations_upserted']++;
                if ($variant->wasRecentlyCreated) {
                    $counts['new_mutations']++;
                }
                $valueResult = $this->upsertCurrentValue($variant, $source, $value, $mutationValue['demand'] ?? null);
                if ($valueResult['observed']) {
                    $counts['observations']++;
                }
                if ($valueResult['changed']) {
                    $counts['values_changed']++;
                } else {
                    $counts['values_unchanged']++;
                }
                $counts['current_values']++;
                $todayPoints[$variant->id] = (float) $value;
            }

            if ($todayPoints !== []) {
                $writer->mergeToday((string) $item->slug, $todayPoints, $today);
                $counts['json_files']++;
            }
            $counts['items']++;
        }

        $this->downloadMissingCatalogImages($mutations, $traits);
        $syncedAt = now()->toIso8601String();
        $this->saveCalculatorMeta($traits, $mutations, $streakMultipliers, $syncedAt);
        $this->storeStreakMultipliers($site, $streakMultipliers);
        foreach ($traits as $trait) {
            $slug = Str::slug((string) $trait['name']);
            if (! isset($previousTraitSlugs[$slug])) {
                $counts['new_traits']++;
            }
        }
        $counts['images_downloaded'] = $this->imagesDownloaded;
        $counts['synced_at'] = $syncedAt;
        $counts['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
        $counts['meta_path'] = self::calculatorMetaPath();

        return $counts;
    }

    /**
     * Save global traits/mutations definitions to sab-calculator-meta.json.
     *
     * @param  list<array<string, mixed>>  $traits
     * @param  list<array<string, mixed>>  $mutations
     */
    public function saveCalculatorMeta(array $traits, array $mutations, mixed $streakMultipliers, ?string $syncedAt = null): void
    {
        $traitRows = [];
        foreach ($traits as $trait) {
            if (! is_array($trait) || trim((string) ($trait['name'] ?? '')) === '') {
                continue;
            }
            $slug = Str::slug((string) $trait['name']);
            $traitRows[] = [
                'id' => $trait['id'] ?? $slug,
                'name' => (string) $trait['name'],
                'multiplier' => (float) ($trait['multiplier'] ?? 1),
                'valueMultiplier' => (float) ($trait['valueMultiplier'] ?? 1),
                'image' => $this->existingCalculatorImage('traits', $slug, $trait['localImage'] ?? $trait['imageUrl'] ?? null),
            ];
        }
        usort($traitRows, fn (array $left, array $right): int => $right['multiplier'] <=> $left['multiplier']);

        $mutationRows = [];
        foreach ($mutations as $mutation) {
            if (! is_array($mutation) || trim((string) ($mutation['name'] ?? '')) === '') {
                continue;
            }
            $slug = Str::slug((string) $mutation['name']);
            $mutationRows[] = [
                'id' => $mutation['id'] ?? $slug,
                'name' => (string) $mutation['name'],
                'multiplier' => (float) ($mutation['multiplier'] ?? 1),
                'image' => $this->existingCalculatorImage('mutations', $slug, $mutation['localImage'] ?? $mutation['imageUrl'] ?? null),
            ];
        }

        $path = self::calculatorMetaPath();
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, json_encode([
            'synced_at' => $syncedAt ?? now()->toIso8601String(),
            'traits' => $traitRows,
            'mutations' => $mutationRows,
            'streakMultipliers' => is_array($streakMultipliers) ? $streakMultipliers : ['3' => 2, '6' => 3],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function upsertItem(SeoGame $game, array $row): SeoItem
    {
        $name = trim((string) $row['name']);
        $slug = Str::slug($name);
        $sourceUrl = $row['localImage'] ?? $row['imageUrl'] ?? null;
        $image = $this->ensureCalculatorImage('brainrots', $slug, $sourceUrl);

        $item = SeoItem::query()->where('seo_game_id', $game->id)->where('slug', $slug)->first();
        $attributes = is_array($item?->attributes_json) ? $item->attributes_json : [];
        Arr::set($attributes, 'rot_rocks', [
            'rot_id' => $row['id'] ?? null,
            'name' => $name,
            'rarity' => (string) ($row['rarity'] ?? ''),
            'base_income' => isset($row['baseIncome']) ? (float) $row['baseIncome'] : null,
            'base_price' => isset($row['robuxValue']) ? (float) $row['robuxValue'] : null,
            'robux_value' => $row['robuxValue'] ?? null,
            'base_cost' => isset($row['baseCost']) ? (float) $row['baseCost'] : null,
            'demand' => $row['demand'] ?? null,
            'trend' => $row['trend'] ?? null,
            'source_image_url' => is_string($sourceUrl) ? $sourceUrl : null,
            'calculator_image_url' => $image,
            'first_seen_at' => data_get($attributes, 'rot_rocks.first_seen_at') ?? now()->toIso8601String(),
        ]);

        if ($item) {
            $item->fill([
                'rarity' => $item->rarity ?: (string) ($row['rarity'] ?? ''),
                'avg_coins_raw' => $item->avg_coins_raw ?: (string) ($row['baseIncome'] ?? ''),
                'attributes_json' => $attributes,
            ])->save();

            return $item;
        }

        return SeoItem::query()->create([
            'seo_game_id' => $game->id,
            'slug' => $slug,
            'name' => $name,
            'rarity' => (string) ($row['rarity'] ?? ''),
            'description' => null,
            'summary' => null,
            'is_publish_html' => false,
            'is_listed' => true,
            'avg_coins_raw' => (string) ($row['baseIncome'] ?? ''),
            'image_url' => is_string($sourceUrl) ? $sourceUrl : '',
            'local_image_url' => '',
            'attributes_json' => $attributes,
            'sort_order' => 0,
        ]);
    }

    private function upsertBaseVariant(SeoItem $item): SeoItemVariant
    {
        return SeoItemVariant::query()->updateOrCreate(
            [
                'seo_item_id' => $item->id,
                'variant_key' => 'base',
            ],
            [
                'variant_name' => 'Base',
                'variant_type' => 'base',
                'mutation' => '',
                'mutation_name' => '',
                'trait' => '',
                'trait_name' => '',
                'multiplier' => 1,
                'sort_order' => 0,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $mutation
     * @param  array<string, mixed>  $value
     */
    private function upsertMutationVariant(SeoItem $item, array $mutation, array $value): SeoItemVariant
    {
        $name = (string) ($mutation['name'] ?? $value['mutationName'] ?? '');
        $slug = Str::slug($name);
        $sourceUrl = $mutation['localImage'] ?? $mutation['imageUrl'] ?? null;
        $image = $this->ensureCalculatorImage('mutations', $slug, $sourceUrl);

        return SeoItemVariant::query()->updateOrCreate(
            [
                'seo_item_id' => $item->id,
                'variant_key' => 'mutation-'.$slug,
            ],
            [
                'variant_name' => $name,
                'variant_type' => 'mutation',
                'mutation' => $name,
                'mutation_name' => $name,
                'trait' => '',
                'trait_name' => '',
                'multiplier' => (float) ($mutation['multiplier'] ?? $value['mutationMultiplier'] ?? 1),
                'sort_order' => 10,
                'attributes_json' => [
                    'rot_id' => $mutation['id'] ?? $value['mutationId'] ?? null,
                    'source_image_url' => is_string($sourceUrl) ? $sourceUrl : null,
                    'calculator_image_url' => $image,
                    'demand' => $value['demand'] ?? null,
                    'trend' => $value['trend'] ?? null,
                ],
            ]
        );
    }

    /**
     * @return array{observed: bool, changed: bool}
     */
    private function upsertCurrentValue(SeoItemVariant $variant, SeoValueSource $source, mixed $value, mixed $demand): array
    {
        if ($value === null || $value === '') {
            return ['observed' => false, 'changed' => false];
        }

        $hash = sha1(json_encode([$variant->id, $value, $demand]));
        $existing = SeoItemCurrentValue::query()
            ->where('seo_item_variant_id', $variant->id)
            ->where('seo_value_source_id', $source->id)
            ->first();
        $changed = $existing === null
            || round((float) $existing->value_normalized, 4) !== round((float) $value, 4);

        SeoItemCurrentValue::query()->updateOrCreate(
            [
                'seo_item_variant_id' => $variant->id,
                'seo_value_source_id' => $source->id,
            ],
            [
                'collected_at' => now(),
                'changed_at' => now(),
                'exist_count_raw' => '',
                'value_raw' => (string) $value,
                'value_normalized' => (float) $value,
                'currency' => 'ROBUX',
                'demand' => $demand ? (string) $demand : '',
                'confidence' => 80,
                'source_payload_hash' => $hash,
            ]
        );

        $recentExists = SeoItemObservation::query()
            ->where('seo_item_variant_id', $variant->id)
            ->where('seo_value_source_id', $source->id)
            ->where('observed_at', '>=', now()->subHours(12))
            ->where('source_payload_hash', $hash)
            ->exists();

        if ($recentExists) {
            return ['observed' => false, 'changed' => $changed];
        }

        $observation = new SeoItemObservation([
            'seo_item_variant_id' => $variant->id,
            'seo_value_source_id' => $source->id,
            'observed_at' => now(),
            'exist_count_raw' => '',
            'value_raw' => (string) $value,
            'value_normalized' => (float) $value,
            'currency' => 'ROBUX',
            'demand' => $demand ? (string) $demand : '',
            'confidence' => 80,
            'source_payload_hash' => $hash,
        ]);
        $observation->id = $this->nextObservationId();
        $observation->save();

        return ['observed' => true, 'changed' => $changed];
    }

    private function storeStreakMultipliers(SeoSite $site, mixed $streakMultipliers): void
    {
        $settings = $site->settings_json ?? [];
        if (! is_array($settings)) {
            $settings = [];
        }
        Arr::set($settings, 'sab_calculator.streak_multipliers', is_array($streakMultipliers) ? $streakMultipliers : ['3' => 2, '6' => 3]);
        $site->forceFill(['settings_json' => $settings])->save();
    }

    /**
     * @param  list<array<string, mixed>>  $mutations
     * @param  list<array<string, mixed>>  $traits
     */
    private function downloadMissingCatalogImages(array $mutations, array $traits): void
    {
        foreach ($mutations as $mutation) {
            if (! is_array($mutation) || trim((string) ($mutation['name'] ?? '')) === '') {
                continue;
            }
            $this->ensureCalculatorImage(
                'mutations',
                Str::slug((string) $mutation['name']),
                $mutation['localImage'] ?? $mutation['imageUrl'] ?? null,
            );
        }
        foreach ($traits as $trait) {
            if (! is_array($trait) || trim((string) ($trait['name'] ?? '')) === '') {
                continue;
            }
            $this->ensureCalculatorImage(
                'traits',
                Str::slug((string) $trait['name']),
                $trait['localImage'] ?? $trait['imageUrl'] ?? null,
            );
        }
    }

    private function ensureCalculatorImage(string $type, string $slug, mixed $url): ?string
    {
        $existing = $this->existingCalculatorImage($type, $slug, $url);
        if ($existing !== null) {
            return $existing;
        }

        return $this->downloadImage($url, $type, $slug, false);
    }

    private function existingCalculatorImage(string $type, string $slug, mixed $url): ?string
    {
        $url = is_string($url) ? trim($url) : '';
        if ($url === '') {
            return null;
        }

        foreach ($this->calculatorImageRelativePaths($url, $type, $slug) as $relative) {
            $absolute = public_path($relative);
            if ($this->isUsableCalculatorImage($absolute)) {
                return '/'.$relative;
            }
        }

        return null;
    }

    private function downloadImage(mixed $url, string $type, string $slug, bool $refreshImages): ?string
    {
        $url = is_string($url) ? trim($url) : '';
        if ($url === '') {
            return null;
        }

        $existing = $this->existingCalculatorImage($type, $slug, $url);
        if (! $refreshImages && $existing !== null) {
            return $existing;
        }

        $relative = $this->writableCalculatorImageRelativePath($url, $type, $slug);
        $absolute = public_path($relative);

        try {
            $response = Http::timeout(30)->get($url);
            if (! $response->successful() || $response->body() === '') {
                return $this->isUsableCalculatorImage($absolute) ? '/'.$relative : null;
            }
        } catch (\Throwable) {
            return $this->isUsableCalculatorImage($absolute) ? '/'.$relative : null;
        }

        if (! is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0755, true);
        }
        file_put_contents($absolute, $response->body());
        if (! $this->isUsableCalculatorImage($absolute)) {
            @unlink($absolute);

            return null;
        }

        $this->imagesDownloaded++;

        return '/'.$relative;
    }

    private function isUsableCalculatorImage(string $absolute): bool
    {
        if (! is_file($absolute) || filesize($absolute) < 128) {
            return false;
        }

        return @getimagesize($absolute) !== false;
    }

    private function isGeoflowOwnedPath(string $absolute): bool
    {
        $cursor = dirname($absolute);
        for ($i = 0; $i < 8; $i++) {
            if (is_link($cursor)) {
                $target = (string) readlink($cursor);
                if (str_contains($target, 'geo-ant-design-pro')) {
                    return true;
                }
            }
            $parent = dirname($cursor);
            if ($parent === $cursor) {
                break;
            }
            $cursor = $parent;
        }

        $real = realpath(dirname($absolute));

        return is_string($real) && str_contains($real, 'geo-ant-design-pro');
    }

    /**
     * @return list<string>
     */
    private function calculatorImageRelativePaths(string $url, string $type, string $slug): array
    {
        $file = $type.'/'.$slug.'.'.$this->imageExtension($url);

        return [
            self::IMAGE_ROOT.'/'.$file,
            self::LOCAL_IMAGE_ROOT.'/'.$file,
        ];
    }

    private function writableCalculatorImageRelativePath(string $url, string $type, string $slug): string
    {
        $shared = self::IMAGE_ROOT.'/'.$type.'/'.$slug.'.'.$this->imageExtension($url);
        if (! $this->isGeoflowOwnedPath(public_path($shared))) {
            return $shared;
        }

        return self::LOCAL_IMAGE_ROOT.'/'.$type.'/'.$slug.'.'.$this->imageExtension($url);
    }

    private function imageExtension(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

        return in_array($extension, ['webp', 'png', 'jpg', 'jpeg', 'gif'], true) ? $extension : 'png';
    }

    private function nextObservationId(): int
    {
        $this->nextObservationId ??= ((int) SeoItemObservation::query()->max('id')) + 1;

        return $this->nextObservationId++;
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return list<array<string, mixed>>
     */
    private function namedRows(mixed $rows): array
    {
        return array_values(array_filter(
            is_array($rows) ? $rows : [],
            fn ($row): bool => is_array($row) && trim((string) ($row['name'] ?? '')) !== ''
        ));
    }

    /**
     * @return array<string, true>
     */
    private function previousMetaTraitSlugs(): array
    {
        $path = self::calculatorMetaPath();
        if (! is_readable($path)) {
            return [];
        }
        $meta = json_decode((string) file_get_contents($path), true);
        $slugs = [];
        foreach (is_array($meta) ? ($meta['traits'] ?? []) : [] as $trait) {
            if (! is_array($trait) || trim((string) ($trait['name'] ?? '')) === '') {
                continue;
            }
            $slugs[Str::slug((string) $trait['name'])] = true;
        }

        return $slugs;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchJson(string $path): array
    {
        $payload = Http::retry(3, 1000)
            ->timeout(30)
            ->acceptJson()
            ->get(self::BASE_URL.$path)
            ->throw()
            ->json();

        return is_array($payload) ? $payload : [];
    }

    private function ensureCliMemoryLimit(): void
    {
        if (PHP_SAPI !== 'cli') {
            return;
        }

        if ($this->memoryLimitBytes((string) ini_get('memory_limit')) < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }
    }

    private function memoryLimitBytes(string $value): int
    {
        $value = strtolower(trim($value));
        if ($value === '-1') {
            return PHP_INT_MAX;
        }
        $number = (int) $value;

        return $number * match (substr($value, -1)) {
            'g' => 1024 * 1024 * 1024,
            'm' => 1024 * 1024,
            'k' => 1024,
            default => 1,
        };
    }
}
