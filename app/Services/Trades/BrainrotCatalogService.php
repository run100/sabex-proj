<?php

namespace App\Services\Trades;

use App\Exceptions\TradeException;
use App\Models\SeoItem;
use App\Models\SeoItemVariant;
use App\Services\Seo\SabRotCalculatorSyncService;
use Illuminate\Support\Collection;

class BrainrotCatalogService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function searchBrainrots(string $query, int $limit = 20): array
    {
        $query = trim($query);
        $items = SeoItem::query()
            ->where('is_listed', true)
            ->when($query !== '', function ($builder) use ($query): void {
                $builder->where(function ($inner) use ($query): void {
                    $inner->where('slug', 'like', '%'.$query.'%')
                        ->orWhere('name', 'like', '%'.$query.'%')
                        ->orWhere('display_name', 'like', '%'.$query.'%');
                });
            })
            ->orderBy('name')
            ->limit(max(1, min($limit, 50)))
            ->get();

        return $this->hydrateItems($items)->map(fn (array $row) => $this->searchCard($row))->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function getBrainrot(int|string $idOrSlug): array
    {
        return $this->catalogRow($this->findItem($idOrSlug));
    }

    /**
     * @return array<string, mixed>
     */
    public function getTradeOptions(int|string $idOrSlug): array
    {
        $row = $this->getBrainrot($idOrSlug);

        return [
            'brainrot' => $this->searchCard($row),
            'mutations' => $row['mutations'],
            'traits' => $this->getTraits(),
            'exist_count' => $row['exist_count'],
            'demand' => $row['demand'],
            'current_value' => $row['robux_value'],
            'income' => $row['base_income'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getTraits(): array
    {
        $meta = $this->calculatorMeta();
        $traits = $meta['traits'] ?? [];

        return array_values(array_filter(array_map(function ($trait): ?array {
            if (! is_array($trait) || trim((string) ($trait['name'] ?? '')) === '') {
                return null;
            }

            return [
                'name' => (string) $trait['name'],
                'multiplier' => (float) ($trait['multiplier'] ?? 1),
                'valueMultiplier' => (float) ($trait['valueMultiplier'] ?? 1),
            ];
        }, is_array($traits) ? $traits : [])));
    }

    /**
     * @return array<string, float>
     */
    public function streakMultipliers(): array
    {
        $meta = $this->calculatorMeta();
        $raw = $meta['streakMultipliers'] ?? ['3' => 2, '6' => 3];
        $out = [];
        foreach (is_array($raw) ? $raw : [] as $threshold => $multiplier) {
            $out[(string) $threshold] = (float) $multiplier;
        }

        return $out === [] ? ['3' => 2.0, '6' => 3.0] : $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveTrait(string $name): array
    {
        $needle = mb_strtolower(trim($name));
        foreach ($this->getTraits() as $trait) {
            if (mb_strtolower((string) $trait['name']) === $needle) {
                return $trait;
            }
        }

        throw TradeException::invalid('INVALID_TRAIT', 'Unknown trait: '.$name);
    }

    /**
     * @param  array<string, mixed>  $catalog
     * @return array<string, mixed>
     */
    public function resolveMutation(array $catalog, ?int $variantId, ?string $mutationKey, ?string $mutationName): array
    {
        $mutations = is_array($catalog['mutations'] ?? null) ? $catalog['mutations'] : [];
        if ($variantId) {
            foreach ($mutations as $mutation) {
                if ((int) ($mutation['variant_id'] ?? 0) === $variantId) {
                    return $mutation;
                }
            }
            throw TradeException::invalid('INVALID_MUTATION', 'Unknown mutation for this Brainrot.');
        }

        $key = trim((string) $mutationKey);
        if ($key !== '') {
            foreach ($mutations as $mutation) {
                if ((string) ($mutation['id'] ?? '') === $key) {
                    return $mutation;
                }
            }
        }

        $name = trim((string) $mutationName);
        if ($name !== '') {
            foreach ($mutations as $mutation) {
                if (strcasecmp((string) ($mutation['name'] ?? ''), $name) === 0) {
                    return $mutation;
                }
            }
            throw TradeException::invalid('INVALID_MUTATION', 'Unknown mutation: '.$name);
        }

        return $mutations[0] ?? [
            'id' => 'base',
            'variant_id' => $catalog['base_variant_id'] ?? null,
            'name' => 'Default',
            'multiplier' => 1.0,
            'robux_value' => $catalog['robux_value'],
            'demand' => $catalog['demand'],
        ];
    }

    /**
     * @param  Collection<int, SeoItem>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function hydrateItems(Collection $items): Collection
    {
        $items->load(['variants.currentValues.source']);

        return $items->map(fn (SeoItem $item) => $this->catalogRow($item));
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogRow(SeoItem $item): array
    {
        $item->loadMissing(['variants.currentValues.source']);
        $rot = data_get($item->attributes_json, 'rot_rocks', []);
        $baseVariant = $item->variants->firstWhere('variant_key', 'base')
            ?? $item->variants->firstWhere('variant_type', 'base');
        $baseValue = $this->currentValue($baseVariant);
        $robux = $baseValue ?? (data_get($rot, 'robux_value') !== null ? (float) data_get($rot, 'robux_value') : null);

        $mutations = $item->variants
            ->where('variant_type', 'mutation')
            ->filter(function (SeoItemVariant $variant): bool {
                if (trim((string) data_get($variant->attributes_json, 'rot_id')) === '') {
                    return (string) $variant->variant_key !== 'mutation-default'
                        && trim((string) ($variant->trait_name ?? '')) === '';
                }

                return (string) $variant->variant_key !== 'mutation-default'
                    && trim((string) ($variant->trait_name ?? '')) === ''
                    && ! str_ends_with((string) $variant->variant_key, '-1-trait');
            })
            ->map(fn (SeoItemVariant $variant) => $this->mutationPayload($variant))
            ->values();

        $mutations->prepend([
            'id' => 'base',
            'variant_id' => $baseVariant?->id,
            'name' => 'Default',
            'multiplier' => 1.0,
            'robux_value' => $robux,
            'demand' => data_get($rot, 'demand') ?: $this->currentDemand($baseVariant),
            'image' => null,
        ]);

        return [
            'id' => (int) $item->id,
            'slug' => (string) $item->slug,
            'name' => (string) (data_get($rot, 'name') ?: $item->display_name ?: $item->name),
            'image' => $this->imageForItem($item),
            'base_income' => $this->baseIncome($item),
            'robux_value' => $robux,
            'demand' => data_get($rot, 'demand') ?: $this->currentDemand($baseVariant),
            'exist_count' => $item->total_exists,
            'base_variant_id' => $baseVariant?->id,
            'mutations' => $mutations->unique('name')->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function searchCard(array $row): array
    {
        return [
            'id' => $row['id'],
            'slug' => $row['slug'],
            'name' => $row['name'],
            'image' => $row['image'],
            'current_value' => $row['robux_value'],
            'demand' => $row['demand'],
            'exist_count' => $row['exist_count'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mutationPayload(SeoItemVariant $variant): array
    {
        $value = $this->currentValue($variant);

        return [
            'id' => (string) $variant->variant_key,
            'variant_id' => (int) $variant->id,
            'name' => (string) ($variant->mutation_name ?: $variant->variant_name ?: $variant->variant_key),
            'multiplier' => (float) ($variant->multiplier ?? 1),
            'robux_value' => $value,
            'demand' => data_get($variant->attributes_json, 'demand') ?: $this->currentDemand($variant),
            'image' => data_get($variant->attributes_json, 'calculator_image_url'),
        ];
    }

    private function currentValue(?SeoItemVariant $variant): ?float
    {
        if ($variant === null) {
            return null;
        }
        $value = $variant->currentValues->first(
            fn ($cv) => ($cv->source?->slug) === SabRotCalculatorSyncService::SOURCE_SLUG
        )?->value_normalized;

        return $value !== null ? (float) $value : null;
    }

    private function currentDemand(?SeoItemVariant $variant): ?string
    {
        if ($variant === null) {
            return null;
        }
        $demand = $variant->currentValues->first(
            fn ($cv) => ($cv->source?->slug) === SabRotCalculatorSyncService::SOURCE_SLUG
        )?->demand;

        return $demand !== null && $demand !== '' ? (string) $demand : null;
    }

    private function baseIncome(SeoItem $item): float
    {
        $rot = data_get($item->attributes_json, 'rot_rocks', []);

        return (float) (data_get($rot, 'base_income') ?? preg_replace('/[^0-9.]/', '', (string) $item->avg_coins_raw));
    }

    private function imageForItem(SeoItem $item): ?string
    {
        $saved = data_get($item->attributes_json, 'rot_rocks.calculator_image_url');
        if (is_string($saved) && trim($saved) !== '') {
            return $saved;
        }

        return $item->local_image_url ? '/'.ltrim((string) $item->local_image_url, '/') : ($item->image_url ?: null);
    }

    private function findItem(int|string $idOrSlug): SeoItem
    {
        $item = is_numeric($idOrSlug)
            ? SeoItem::query()->find((int) $idOrSlug)
            : SeoItem::query()->where('slug', (string) $idOrSlug)->first();

        if ($item === null) {
            throw TradeException::invalid('INVALID_BRAINROT', 'Unknown Brainrot.');
        }

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    private function calculatorMeta(): array
    {
        $path = SabRotCalculatorSyncService::calculatorMetaPath();
        if (! is_file($path)) {
            return ['traits' => [], 'streakMultipliers' => ['3' => 2, '6' => 3]];
        }
        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : ['traits' => [], 'streakMultipliers' => ['3' => 2, '6' => 3]];
    }
}
