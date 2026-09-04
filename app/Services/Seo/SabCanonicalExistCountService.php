<?php

namespace App\Services\Seo;

use App\Models\SeoGame;
use App\Models\SeoItem;
use App\Models\SeoItemCurrentValue;
use App\Models\SeoItemVariant;
use Illuminate\Support\Collection;

class SabCanonicalExistCountService
{
    public const SOURCE_PRIORITY = [
        'eldorado',
        'fandom-exist-counts',
        'fandom-known-exists',
        'fandom-exist-count-gallery',
        'fandom',
        'fandom-full-catalog',
        'moonvalues',
    ];

    public function selectFromCurrentValues(Collection $cvBySource): ?int
    {
        foreach (self::SOURCE_PRIORITY as $sourceSlug) {
            $value = $cvBySource->get($sourceSlug)?->exist_count_normalized;
            if ($value !== null) {
                return (int) $value;
            }
        }

        return null;
    }

    public function selectForItem(SeoItem $item): ?int
    {
        $item->loadMissing(['variants.currentValues.source']);

        return $this->selectFromCurrentValues($this->mergedCvBySourceForVariants($item->variants));
    }

    public function syncItem(SeoItem $item): bool
    {
        $canonical = $this->selectForItem($item);
        if ($canonical === null || (int) ($item->total_exists ?? 0) === $canonical) {
            return false;
        }

        $item->forceFill(['total_exists' => $canonical])->save();

        return true;
    }

    public function syncItemsByIds(iterable $itemIds): int
    {
        $changed = 0;
        $ids = collect($itemIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        foreach ($ids->chunk(200) as $chunk) {
            SeoItem::query()
                ->whereIn('id', $chunk->all())
                ->with(['variants.currentValues.source'])
                ->get()
                ->each(function (SeoItem $item) use (&$changed): void {
                    if ($this->syncItem($item)) {
                        $changed++;
                    }
                });
        }

        return $changed;
    }

    public function syncGame(SeoGame $game): int
    {
        $changed = 0;

        SeoItem::query()
            ->where('seo_game_id', $game->id)
            ->whereHas('variants.currentValues', fn ($query) => $query->whereNotNull('exist_count_normalized'))
            ->with(['variants.currentValues.source'])
            ->chunkById(200, function (Collection $items) use (&$changed): void {
                foreach ($items as $item) {
                    if ($this->syncItem($item)) {
                        $changed++;
                    }
                }
            });

        return $changed;
    }

    public function previewGameChanges(SeoGame $game): array
    {
        $changes = [];

        SeoItem::query()
            ->where('seo_game_id', $game->id)
            ->whereHas('variants.currentValues', fn ($query) => $query->whereNotNull('exist_count_normalized'))
            ->with(['variants.currentValues.source'])
            ->chunkById(200, function (Collection $items) use (&$changes): void {
                foreach ($items as $item) {
                    $canonical = $this->selectForItem($item);
                    if ($canonical !== null && (int) ($item->total_exists ?? 0) !== $canonical) {
                        $changes[] = [
                            'slug' => $item->slug,
                            'old_total_exists' => $item->total_exists,
                            'new_total_exists' => $canonical,
                        ];
                    }
                }
            });

        return $changes;
    }

    public function mergedCvBySourceForVariants(Collection $variants): Collection
    {
        $merged = collect();

        foreach ($variants as $variant) {
            if (! $variant instanceof SeoItemVariant || $variant->variant_key === 'base') {
                continue;
            }

            foreach ($variant->currentValues as $cv) {
                if (! $cv instanceof SeoItemCurrentValue) {
                    continue;
                }
                $slug = optional($cv->source)->slug;
                if ($slug && ! $merged->has($slug)) {
                    $merged->put($slug, $cv);
                }
            }
        }

        $baseVariant = $variants->firstWhere('variant_key', 'base');
        if ($baseVariant instanceof SeoItemVariant) {
            foreach ($baseVariant->currentValues as $cv) {
                if (! $cv instanceof SeoItemCurrentValue) {
                    continue;
                }
                $slug = optional($cv->source)->slug;
                if ($slug) {
                    $merged->put($slug, $cv);
                }
            }
        }

        return $merged;
    }
}
