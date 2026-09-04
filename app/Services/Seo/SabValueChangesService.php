<?php

namespace App\Services\Seo;

use App\Models\SeoGame;
use App\Models\SeoSite;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SabValueChangesService
{
    public const GAME_SLUG = 'steal-a-brainrot';

    public const SOURCE_SLUG = SabRotCalculatorSyncService::SOURCE_SLUG;

    /** @var list<int> */
    public const ALLOWED_DAY_WINDOWS = [1, 7, 14, 30];

    /**
     * @return list<array<string, mixed>>
     */
    public function changes(int $days = 7, ?string $direction = null, string $sort = 'recent', int $limit = 200): array
    {
        if (! Schema::hasTable('seo_item_observations')) {
            return [];
        }

        $game = $this->resolveGame();
        if ($game === null) {
            return [];
        }

        $days = $this->normalizeDays($days);
        $direction = $this->normalizeDirection($direction);
        $sort = $this->normalizeSort($sort);
        $since = Carbon::now()->subDays($days)->startOfDay();

        $ranked = DB::table('seo_item_observations as observation')
            ->join('seo_item_variants as variant', 'variant.id', '=', 'observation.seo_item_variant_id')
            ->join('seo_items as item', 'item.id', '=', 'variant.seo_item_id')
            ->join('seo_value_sources as source', 'source.id', '=', 'observation.seo_value_source_id')
            ->where('item.seo_game_id', $game->id)
            ->where('item.is_listed', true)
            ->where('variant.variant_key', 'base')
            ->where('source.slug', self::SOURCE_SLUG)
            ->whereNotNull('observation.value_normalized')
            ->where('observation.observed_at', '>=', $since)
            ->select([
                'observation.id',
                'observation.observed_at',
                'observation.value_raw',
                'observation.value_normalized',
                'observation.demand',
                'item.slug as item_slug',
                'item.name as item_name',
                'item.display_name as item_display_name',
                'item.rarity as item_rarity',
            ])
            ->selectRaw('lag(observation.value_raw) over (partition by observation.seo_item_variant_id, observation.seo_value_source_id order by observation.observed_at, observation.id) as before_raw')
            ->selectRaw('lag(observation.value_normalized) over (partition by observation.seo_item_variant_id, observation.seo_value_source_id order by observation.observed_at, observation.id) as before_normalized')
            ->selectRaw('lag(observation.demand) over (partition by observation.seo_item_variant_id, observation.seo_value_source_id order by observation.observed_at, observation.id) as before_demand');

        $query = DB::query()->fromSub($ranked, 'ranked')
            ->whereNotNull('before_raw')
            ->where(function ($builder): void {
                $builder->whereColumn('before_raw', '!=', 'value_raw')
                    ->orWhereRaw("coalesce(before_demand, '') <> coalesce(demand, '')");
            });

        if ($direction === 'up') {
            $query->whereRaw('cast(value_normalized as decimal(20,4)) > cast(before_normalized as decimal(20,4))');
        } elseif ($direction === 'down') {
            $query->whereRaw('cast(value_normalized as decimal(20,4)) < cast(before_normalized as decimal(20,4))');
        }

        if ($sort === 'biggest') {
            $query->orderByRaw('abs(cast(value_normalized as decimal(20,4)) - cast(before_normalized as decimal(20,4))) desc');
        } else {
            $query->orderByDesc('observed_at')->orderByDesc('id');
        }

        return $query
            ->limit(max(1, $limit))
            ->get()
            ->map(fn (object $row): array => $this->mapRow($row))
            ->all();
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    public function topMoverSummaries(int $days = 7, int $limit = 10): array
    {
        $limit = max(1, $limit);
        $poolSize = max($limit * 5, 50);

        $gainerCandidates = $this->dedupeRowsBySlug(
            $this->changes($days, 'up', 'biggest', $poolSize),
            fn (array $a, array $b): int => ((float) ($a['delta'] ?? 0)) <=> ((float) ($b['delta'] ?? 0)),
        );

        $loserCandidates = $this->dedupeRowsBySlug(
            $this->changes($days, 'down', 'biggest', $poolSize),
            fn (array $a, array $b): int => ((float) ($a['deltaDrop'] ?? 0)) <=> ((float) ($b['deltaDrop'] ?? 0)),
        );

        $gainerBySlug = collect($gainerCandidates)->keyBy('itemSlug');
        $loserBySlug = collect($loserCandidates)->keyBy('itemSlug');

        foreach ($gainerBySlug->keys()->intersect($loserBySlug->keys()) as $slug) {
            $gainer = $gainerBySlug->get($slug);
            $loser = $loserBySlug->get($slug);
            if ($gainer === null || $loser === null) {
                continue;
            }

            $gainPct = abs((float) ($gainer['deltaPct'] ?? 0));
            $lossPct = abs((float) ($loser['deltaPct'] ?? 0));

            if ($gainPct >= $lossPct) {
                $loserBySlug->forget($slug);
            } else {
                $gainerBySlug->forget($slug);
            }
        }

        $topGainers = $gainerBySlug
            ->sortByDesc(fn (array $row): float => (float) ($row['delta'] ?? 0))
            ->take($limit)
            ->values()
            ->all();

        $topLosers = $loserBySlug
            ->sortByDesc(fn (array $row): float => (float) ($row['deltaDrop'] ?? 0))
            ->take($limit)
            ->values()
            ->all();

        return [$topGainers, $topLosers];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function topLosers(int $days = 7, int $limit = 10): array
    {
        return $this->topMoverSummaries($days, $limit)[1];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function topGainers(int $days = 7, int $limit = 10): array
    {
        return $this->topMoverSummaries($days, $limit)[0];
    }

    public function latestObservedAt(int $days = 7): ?Carbon
    {
        if (! Schema::hasTable('seo_item_observations')) {
            return null;
        }

        $game = $this->resolveGame();
        if ($game === null) {
            return null;
        }

        $since = Carbon::now()->subDays($this->normalizeDays($days))->startOfDay();
        $latest = DB::table('seo_item_observations as observation')
            ->join('seo_item_variants as variant', 'variant.id', '=', 'observation.seo_item_variant_id')
            ->join('seo_items as item', 'item.id', '=', 'variant.seo_item_id')
            ->join('seo_value_sources as source', 'source.id', '=', 'observation.seo_value_source_id')
            ->where('item.seo_game_id', $game->id)
            ->where('item.is_listed', true)
            ->where('variant.variant_key', 'base')
            ->where('source.slug', self::SOURCE_SLUG)
            ->where('observation.observed_at', '>=', $since)
            ->max('observation.observed_at');

        return $latest ? Carbon::parse($latest) : null;
    }

    public function normalizeDays(int $days): int
    {
        return in_array($days, self::ALLOWED_DAY_WINDOWS, true) ? $days : 7;
    }

    public function normalizeDirection(?string $direction): ?string
    {
        return in_array($direction, ['up', 'down'], true) ? $direction : null;
    }

    public function normalizeSort(string $sort): string
    {
        return $sort === 'biggest' ? 'biggest' : 'recent';
    }

    private function resolveGame(): ?SeoGame
    {
        $site = SeoSite::query()->where('slug', SabRenderService::SITE_SLUG)->first();
        if ($site === null) {
            return null;
        }

        return SeoGame::query()
            ->where('seo_site_id', $site->id)
            ->where('slug', self::GAME_SLUG)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapRow(object $row): array
    {
        $beforeNum = is_numeric($row->before_normalized) ? (float) $row->before_normalized : null;
        $afterNum = is_numeric($row->value_normalized) ? (float) $row->value_normalized : null;
        $delta = ($beforeNum !== null && $afterNum !== null) ? round($afterNum - $beforeNum, 4) : null;
        $deltaDrop = ($beforeNum !== null && $afterNum !== null && $afterNum < $beforeNum)
            ? round($beforeNum - $afterNum, 4)
            : 0.0;
        $deltaPct = ($beforeNum !== null && $beforeNum != 0.0 && $delta !== null)
            ? round(($delta / $beforeNum) * 100, 1)
            : null;
        $observedAt = Carbon::parse($row->observed_at)->toIso8601String();

        return [
            'itemName' => trim((string) ($row->item_display_name ?? '')) ?: (string) $row->item_name,
            'itemSlug' => (string) $row->item_slug,
            'itemRarity' => (string) ($row->item_rarity ?? ''),
            'sourceLabel' => 'rot.rocks calculator',
            'observedAt' => $observedAt,
            'observedLabel' => Carbon::parse($row->observed_at)->format('M j, Y'),
            'beforeValue' => $beforeNum,
            'afterValue' => $afterNum,
            'before' => $this->formatValueLabel($row->before_raw, $beforeNum),
            'after' => $this->formatValueLabel($row->value_raw, $afterNum),
            'delta' => $delta,
            'deltaDrop' => $deltaDrop,
            'deltaLabel' => $this->formatDeltaLabel($delta),
            'deltaPct' => $deltaPct,
            'deltaPctLabel' => $deltaPct === null ? '—' : (($deltaPct > 0 ? '+' : '') . $deltaPct . '%'),
            'demandBefore' => (string) ($row->before_demand ?? ''),
            'demandAfter' => (string) ($row->demand ?? ''),
        ];
    }

    private function formatValueLabel(mixed $raw, ?float $normalized): string
    {
        $text = trim((string) $raw);
        if ($text !== '') {
            return $text;
        }

        return $normalized !== null ? number_format($normalized) : '—';
    }

    private function formatDeltaLabel(?float $delta): string
    {
        if ($delta === null || $delta == 0.0) {
            return '—';
        }

        return ($delta > 0 ? '+' : '') . rtrim(rtrim(number_format($delta, 2, '.', ''), '0'), '.');
    }

    /**
     * Keep one row per item slug, preferring the row that wins the comparator.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  callable(array<string, mixed>, array<string, mixed>): int  $prefer
     * @return list<array<string, mixed>>
     */
    private function dedupeRowsBySlug(array $rows, callable $prefer): array
    {
        $bySlug = [];

        foreach ($rows as $row) {
            $slug = (string) ($row['itemSlug'] ?? '');
            if ($slug === '') {
                continue;
            }

            if (! array_key_exists($slug, $bySlug) || $prefer($row, $bySlug[$slug]) > 0) {
                $bySlug[$slug] = $row;
            }
        }

        return array_values($bySlug);
    }
}
