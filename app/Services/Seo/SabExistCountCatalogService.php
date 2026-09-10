<?php

namespace App\Services\Seo;

use App\Models\SeoGame;
use App\Models\SeoItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Builds the local, compact data used by the homepage and exist-count list.
 *
 * The command may load the relationships here while publishing a release, but
 * public page requests read the resulting JSON instead of repeating this work.
 */
class SabExistCountCatalogService
{
    public const PUBLIC_PATH = '/data/seo/sab-exist-count-list.json';

    private const STORAGE_PATH = 'app/seo/sab-exist-count-list.json';

    public static function path(): string
    {
        if (app()->environment('testing')) {
            return storage_path('framework/testing/sab-exist-count-list.json');
        }

        return storage_path(self::STORAGE_PATH);
    }

    public static function publicUrl(): string
    {
        return self::PUBLIC_PATH;
    }

    /**
     * @return array{generated_at: string, url: string, rows: list<array<string, mixed>>, home: array<string, mixed>}|null
     */
    public static function read(): ?array
    {
        $path = self::path();
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $data = json_decode((string) File::get($path), true);
        if (! is_array($data)
            || ! is_string($data['generated_at'] ?? null)
            || ! is_array($data['rows'] ?? null)
            || ! is_array($data['home'] ?? null)) {
            return null;
        }

        return [
            'generated_at' => $data['generated_at'],
            'url' => self::publicUrl(),
            'rows' => array_values($data['rows']),
            'home' => $data['home'],
        ];
    }

    /**
     * @return array{path: string, generated_at: string, rows: int, bytes: int}
     */
    public function publish(SeoGame $game, ?callable $onProgress = null): array
    {
        $data = $this->build($game);
        if ($data['rows'] === []) {
            throw new \RuntimeException('Exist count catalog is empty; previous file was kept.');
        }

        $generatedAt = now()->toIso8601String();
        $payload = [
            'schema_version' => 1,
            'generated_at' => $generatedAt,
            'rows' => $data['rows'],
            'home' => $data['home'],
        ];
        $path = self::path();
        File::ensureDirectoryExists(dirname($path));
        $temporaryPath = $path.'.tmp-'.Str::uuid();

        try {
            File::put($temporaryPath, json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ));
            if (! @rename($temporaryPath, $path)) {
                throw new \RuntimeException('Unable to publish exist count catalog.');
            }
        } catch (\Throwable $e) {
            if (is_file($temporaryPath)) {
                File::delete($temporaryPath);
            }

            throw $e;
        }

        $this->progress($onProgress, 'Exist count catalog published: rows='.count($data['rows']));

        return [
            'path' => $path,
            'generated_at' => $generatedAt,
            'rows' => count($data['rows']),
            'bytes' => (int) (filesize($path) ?: 0),
        ];
    }

    /**
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     home: array<string, mixed>
     * }
     */
    public function build(SeoGame $game): array
    {
        $items = SeoItem::query()
            ->where('seo_game_id', $game->id)
            ->where('is_listed', true)
            ->with([
                'variants' => fn ($query) => $query
                    ->whereIn('variant_type', ['base', 'mutation'])
                    ->with('currentValues.source'),
            ])
            ->get();

        $items = $this->sortItemsForHomeTable($items);
        $rows = $this->buildRows($items);
        $knownCounts = $items->pluck('total_exists')->filter()->values();
        $homeSummaries = $items
            ->filter(fn (SeoItem $item): bool => SabRenderService::shouldLinkProduct($item))
            ->map(fn (SeoItem $item): array => $this->summary($item));

        $topRareItems = $homeSummaries
            ->filter(fn (array $entry): bool => $entry['existCount'] !== null
                && SabRenderService::canonicalRarityKey($entry['rarity'] ?? null) === 'og')
            ->sortBy(fn (array $entry): string => sprintf(
                '%012d-%s',
                (int) $entry['existCount'],
                strtolower((string) $entry['name']),
            ))
            ->take(10)
            ->values()
            ->all();

        $recentlyChangedItems = $homeSummaries
            ->filter(fn (array $entry): bool => $entry['latestDate'] !== null && $entry['existCount'] !== null)
            ->sortByDesc('latestDate')
            ->take(8)
            ->values()
            ->all();

        $rarityTagCounts = ['' => $items->count()];
        foreach ($items as $item) {
            $rarityKey = SabRenderService::canonicalRarityKey($item->rarity ?? null);
            if ($rarityKey !== '') {
                $rarityTagCounts[$rarityKey] = ($rarityTagCounts[$rarityKey] ?? 0) + 1;
            }
        }

        return [
            'rows' => $rows,
            'home' => [
                'stats' => [
                    'total' => $items->count(),
                    'lowest' => $knownCounts->min(),
                    'highest' => $knownCounts->max(),
                ],
                'rarityTagCounts' => $rarityTagCounts,
                'newCount' => $items->filter(fn (SeoItem $item): bool => SabRenderService::isNewHomeItem($item))->count(),
                'topRareItems' => $topRareItems,
                'recentlyChangedItems' => $recentlyChangedItems,
            ],
        ];
    }

    private function progress(?callable $onProgress, string $message): void
    {
        if (is_callable($onProgress)) {
            $onProgress($message);
        }
    }

    /**
     * @param  Collection<int, SeoItem>  $items
     * @return list<array<string, mixed>>
     */
    private function buildRows(Collection $items): array
    {
        $rows = [];
        foreach ($items as $item) {
            $knownCount = $item->total_exists !== null ? (int) $item->total_exists : null;
            $display = SabRenderService::resolveExistCountDisplay($item, $knownCount);
            $rarityKey = SabRenderService::canonicalRarityKey($item->rarity ?? null);
            $rarityLabel = $rarityKey === ''
                ? ''
                : ($rarityKey === 'og' ? 'OG' : SabRenderService::canonicalRarityLabel($rarityKey));
            $mutationLabel = $this->displayMutationLabel($item->rarest_mutation_name);
            $traitLabel = $this->displayMutationLabel($item->rarest_trait_name);
            $isNew = SabRenderService::isNewHomeItem($item);
            $searchText = implode(' ', array_unique(array_filter(array_map(
                static fn ($part): string => strtolower(trim((string) $part)),
                [
                    $item->name,
                    $item->slug,
                    $rarityKey,
                    $rarityLabel,
                    $knownCount !== null ? (string) $knownCount : null,
                    $knownCount !== null ? SabRenderService::formatLargeNumber((float) $knownCount) : null,
                    $display['kind'] === 'estimated' ? $display['primary'] : null,
                    $display['kind'] === 'estimated' ? ($display['short'] ?? '') : null,
                    $isNew ? 'new' : null,
                ],
            ), static fn (string $part): bool => $part !== '')));
            [$signalKey] = SabRenderService::raritySignal($knownCount !== null ? (float) $knownCount : null);

            $rows[] = [
                's' => SabRenderService::productPublicSlug($item->slug ?? ''),
                'n' => (string) $item->name,
                'r' => $rarityKey,
                'rl' => $rarityLabel,
                'e' => $display['sort_value'],
                'tier' => match ($display['kind']) {
                    'known' => 0,
                    'estimated' => 1,
                    default => 2,
                },
                'el' => $display['kind'] === 'estimated' ? $item->exist_estimate_low : null,
                'eh' => $display['kind'] === 'estimated' ? $item->exist_estimate_high : null,
                'img' => (string) (SabRenderService::listingImageSrc($item) ?? ''),
                'q' => $searchText,
                'k' => $display['kind'],
                'ecs' => match ($display['kind']) {
                    'known' => SabRenderService::formatLargeNumber((float) $knownCount),
                    'estimated' => (string) ($display['short'] ?? $display['primary']),
                    default => '—',
                },
                'mn' => $mutationLabel,
                'mcr' => $item->rarest_mutation_count,
                'mc' => $item->rarest_mutation_count !== null
                    ? SabRenderService::formatLargeNumber((float) $item->rarest_mutation_count)
                    : null,
                'tn' => $traitLabel,
                'tcr' => $item->rarest_trait_count,
                'tc' => $item->rarest_trait_count !== null
                    ? SabRenderService::formatLargeNumber((float) $item->rarest_trait_count)
                    : null,
                'sk' => $knownCount !== null ? $signalKey : '',
                'ss' => $knownCount !== null ? $this->signalSymbol($signalKey) : '',
                'link' => SabRenderService::shouldLinkProduct($item) ? 1 : 0,
                'nw' => $isNew ? 1 : 0,
            ];
        }

        return $rows;
    }

    /**
     * @return array{name: string, slug: string, rarity: ?string, imageSrc: ?string, existCount: ?int, latestDate: ?string}
     */
    private function summary(SeoItem $item): array
    {
        $latestDate = $item->variants
            ->flatMap(fn ($variant) => $variant->currentValues)
            ->map(fn ($value) => $value->changed_at ?: $value->collected_at)
            ->filter()
            ->sortDesc()
            ->first();

        return [
            'name' => (string) $item->name,
            'slug' => SabRenderService::productPublicSlug($item->slug),
            'rarity' => $item->rarity,
            'imageSrc' => SabRenderService::listingImageSrc($item),
            'existCount' => $item->total_exists !== null ? (int) $item->total_exists : null,
            'latestDate' => $latestDate instanceof Carbon
                ? $latestDate->toIso8601String()
                : ($latestDate !== null ? Carbon::parse($latestDate)->toIso8601String() : null),
        ];
    }

    /**
     * @param  Collection<int, SeoItem>  $items
     * @return Collection<int, SeoItem>
     */
    private function sortItemsForHomeTable(Collection $items): Collection
    {
        return $items->sort(function (SeoItem $left, SeoItem $right): int {
            $leftTier = $this->sortTier($left);
            $rightTier = $this->sortTier($right);
            if ($leftTier !== $rightTier) {
                return $leftTier <=> $rightTier;
            }

            $sortOrder = ((int) ($right->sort_order ?? 10)) <=> ((int) ($left->sort_order ?? 10));
            if ($sortOrder !== 0) {
                return $sortOrder;
            }

            $newOrder = (int) SabRenderService::isNewHomeItem($right)
                <=> (int) SabRenderService::isNewHomeItem($left);
            if ($newOrder !== 0) {
                return $newOrder;
            }

            $leftCount = $this->effectiveCount($left);
            $rightCount = $this->effectiveCount($right);
            if ($leftCount !== null || $rightCount !== null) {
                if ($leftCount === null) {
                    return 1;
                }
                if ($rightCount === null) {
                    return -1;
                }
                $countOrder = $rightCount <=> $leftCount;
                if ($countOrder !== 0) {
                    return $countOrder;
                }
            }

            $mutationOrder = ($left->rarest_mutation_count ?? PHP_INT_MAX)
                <=> ($right->rarest_mutation_count ?? PHP_INT_MAX);
            if ($mutationOrder !== 0) {
                return $mutationOrder;
            }

            $traitOrder = ($left->rarest_trait_count ?? PHP_INT_MAX)
                <=> ($right->rarest_trait_count ?? PHP_INT_MAX);
            if ($traitOrder !== 0) {
                return $traitOrder;
            }

            return strcasecmp((string) $left->name, (string) $right->name);
        })->values();
    }

    private function sortTier(SeoItem $item): int
    {
        if ($item->total_exists !== null) {
            return 0;
        }

        return $item->exist_estimate_low !== null && $item->exist_estimate_high !== null ? 1 : 2;
    }

    private function effectiveCount(SeoItem $item): ?int
    {
        if ($item->total_exists !== null) {
            return (int) $item->total_exists;
        }
        if ($item->exist_estimate_low !== null && $item->exist_estimate_high !== null) {
            return (int) (((int) $item->exist_estimate_low + (int) $item->exist_estimate_high) / 2);
        }

        return null;
    }

    private function displayMutationLabel(?string $label): string
    {
        $label = trim((string) $label);

        return $label !== '' && ! preg_match('/^(n\/a|na|none|null|—|-)$/iu', $label) ? $label : '';
    }

    private function signalSymbol(string $signalKey): string
    {
        return match ($signalKey) {
            'very_high' => '+++',
            'medium' => '++',
            'low' => '+',
            'very_low' => '-',
            'extremely_rare' => '--',
            'near_unique' => '1',
            'lowest' => '*',
            default => '',
        };
    }
}
