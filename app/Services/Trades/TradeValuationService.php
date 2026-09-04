<?php

namespace App\Services\Trades;

use App\Exceptions\TradeException;

class TradeValuationService
{
    public function __construct(private readonly BrainrotCatalogService $catalog) {}

    /**
     * @param  array<int, mixed>  $offering
     * @param  array<int, mixed>  $lookingFor
     * @return array{offering: list<array<string, mixed>>, looking_for: list<array<string, mixed>>, offering_total: float, looking_total: float, difference: float, difference_percent: float|null, wfl: string}
     */
    public function snapshot(array $offering, array $lookingFor): array
    {
        $offerItems = $this->sideSnapshots($offering, 'offering');
        $lookItems = $this->sideSnapshots($lookingFor, 'looking_for');
        if ($offerItems === []) {
            throw TradeException::invalid('EMPTY_OFFERING', 'Add at least one item you are offering.');
        }
        if ($lookItems === []) {
            throw TradeException::invalid('EMPTY_LOOKING_FOR', 'Add at least one item you are looking for.');
        }

        $offerTotal = $this->sumFinal($offerItems);
        $lookTotal = $this->sumFinal($lookItems);
        $difference = $lookTotal - $offerTotal;
        $percent = $offerTotal > 0 ? ($difference / $offerTotal) * 100 : null;

        return [
            'offering' => $offerItems,
            'looking_for' => $lookItems,
            'offering_total' => $offerTotal,
            'looking_total' => $lookTotal,
            'difference' => $difference,
            'difference_percent' => $percent,
            'wfl' => $this->wfl($percent),
        ];
    }

    /**
     * @param  array<int, mixed>  $items
     * @return list<array<string, mixed>>
     */
    private function sideSnapshots(array $items, string $side): array
    {
        $max = max(1, (int) config('sab-trades.max_items_per_side', 9));
        $out = [];
        foreach (array_values($items) as $index => $raw) {
            if ($index >= $max) {
                throw TradeException::invalid('TOO_MANY_ITEMS', 'Each side can have at most '.$max.' items.');
            }
            if (! is_array($raw)) {
                continue;
            }
            $out[] = $this->itemSnapshot($raw, $side, $index + 1);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function itemSnapshot(array $raw, string $side, int $slot): array
    {
        $brainrot = is_array($raw['brainrot'] ?? null) ? $raw['brainrot'] : [];
        $idOrSlug = $raw['seo_item_id'] ?? $raw['brainrot_id'] ?? $brainrot['id'] ?? $brainrot['slug'] ?? $raw['slug'] ?? null;
        if ($idOrSlug === null || $idOrSlug === '') {
            throw TradeException::invalid('INVALID_BRAINROT', 'Each slot needs a Brainrot.');
        }

        $catalog = $this->catalog->getBrainrot($idOrSlug);
        $mutationInput = is_array($raw['mutation'] ?? null) ? $raw['mutation'] : [];
        $mutation = $this->catalog->resolveMutation(
            $catalog,
            isset($raw['seo_item_variant_id']) ? (int) $raw['seo_item_variant_id'] : (isset($raw['mutation_id']) ? (int) $raw['mutation_id'] : null),
            isset($mutationInput['id']) ? (string) $mutationInput['id'] : null,
            isset($mutationInput['name']) ? (string) $mutationInput['name'] : (isset($raw['mutation_name']) ? (string) $raw['mutation_name'] : null),
        );

        $traitInputs = $this->traitInputs($raw);
        $traits = [];
        foreach ($traitInputs as $i => $name) {
            $trait = $this->catalog->resolveTrait($name);
            $traits[] = [
                'name' => $trait['name'],
                'income_multiplier' => (float) $trait['multiplier'],
                'value_multiplier' => (float) $trait['valueMultiplier'],
                'sort_order' => $i,
            ];
        }

        $calc = $this->calculate($catalog, $mutation, $traits);

        return [
            'side' => $side,
            'slot_no' => $slot,
            'seo_item_id' => (int) $catalog['id'],
            'slug_snapshot' => (string) $catalog['slug'],
            'brainrot_name_snapshot' => (string) $catalog['name'],
            'image_url_snapshot' => $catalog['image'],
            'seo_item_variant_id' => $mutation['variant_id'] ?? null,
            'mutation_name_snapshot' => (string) ($mutation['name'] ?? 'Default'),
            'base_value_snapshot' => $calc['base_value'],
            'mutation_value_multiplier_snapshot' => (float) ($mutation['multiplier'] ?? 1),
            'trait_value_multiplier_snapshot' => $calc['trait_value_multiplier'],
            'final_value_snapshot' => $calc['final_value'],
            'base_income_snapshot' => $calc['base_income'],
            'final_income_snapshot' => $calc['final_income'],
            'demand_snapshot' => $mutation['demand'] ?? $catalog['demand'],
            'exist_count_snapshot' => $catalog['exist_count'],
            'traits' => $traits,
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return list<string>
     */
    private function traitInputs(array $raw): array
    {
        if (isset($raw['trait_ids']) && is_array($raw['trait_ids'])) {
            return array_values(array_filter(array_map('strval', $raw['trait_ids'])));
        }
        if (isset($raw['trait_names']) && is_array($raw['trait_names'])) {
            return array_values(array_filter(array_map('strval', $raw['trait_names'])));
        }
        $names = [];
        foreach (is_array($raw['traits'] ?? null) ? $raw['traits'] : [] as $trait) {
            if (is_string($trait) && trim($trait) !== '') {
                $names[] = $trait;
            } elseif (is_array($trait) && trim((string) ($trait['name'] ?? '')) !== '') {
                $names[] = (string) $trait['name'];
            }
        }

        return $names;
    }

    /**
     * @param  array<string, mixed>  $catalog
     * @param  array<string, mixed>  $mutation
     * @param  list<array<string, mixed>>  $traits
     * @return array{base_value: float, trait_value_multiplier: float, final_value: float, base_income: float, final_income: float}
     */
    public function calculate(array $catalog, array $mutation, array $traits): array
    {
        $additive = 0.0;
        $multiplicative = 1.0;
        foreach ($traits as $trait) {
            $m = (float) ($trait['income_multiplier'] ?? 1);
            if ($m < 1) {
                $multiplicative *= $m;
            } else {
                $additive += $m;
            }
        }
        $incomeMultiplier = ((float) ($mutation['multiplier'] ?? 1) + $additive) * $multiplicative;
        $baseIncome = (float) ($catalog['base_income'] ?? 0);
        $baseValue = isset($mutation['robux_value']) && is_numeric($mutation['robux_value'])
            ? (float) $mutation['robux_value']
            : (float) ($catalog['robux_value'] ?? 0);
        $streak = $this->streakMultiplier(count($traits));
        $traitBonus = 0.0;
        foreach ($traits as $trait) {
            $traitBonus += ((float) ($trait['value_multiplier'] ?? 1) - 1);
        }
        $traitValueMultiplier = max(0.1, 1 + ($traitBonus * $streak));

        return [
            'base_value' => $baseValue,
            'trait_value_multiplier' => $traitValueMultiplier,
            'final_value' => round($baseValue * $traitValueMultiplier),
            'base_income' => $baseIncome,
            'final_income' => $baseIncome * $incomeMultiplier,
        ];
    }

    private function streakMultiplier(int $count): float
    {
        $result = 1.0;
        foreach ($this->catalog->streakMultipliers() as $threshold => $multiplier) {
            if ($count >= (int) $threshold && (float) $multiplier > $result) {
                $result = (float) $multiplier;
            }
        }

        return $result;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function sumFinal(array $items): float
    {
        $sum = 0.0;
        foreach ($items as $item) {
            $sum += (float) ($item['final_value_snapshot'] ?? 0);
        }

        return $sum;
    }

    private function wfl(?float $percent): string
    {
        if ($percent === null) {
            return 'na';
        }
        $threshold = (float) config('sab-trades.fair_threshold_percent', 5);
        if ($percent > $threshold) {
            return 'win';
        }
        if ($percent < -$threshold) {
            return 'lose';
        }

        return 'fair';
    }
}
