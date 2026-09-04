<?php

namespace App\Services\Seo;

use App\Models\SeoItem;
use App\Models\SeoItemCurrentValue;
use App\Models\SeoItemObservation;
use App\Models\SeoItemVariant;
use App\Models\SeoValueSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SabRotCalculatorSyncService
{
    public const SOURCE_SLUG = 'rot-rocks-calculator';

    public const IMAGE_ROOT = 'uploads/images/sab/calculator';

    private const BASE_URL = 'https://rot.rocks';

    private const META_PATH = 'app/seo/sab-calculator-meta.json';

    private ?int $nextObservationId = null;

    public static function calculatorImageRoot(): string
    {
        return '/' . self::IMAGE_ROOT;
    }

    public static function calculatorMetaPath(): string
    {
        if (app()->environment('testing')) {
            return storage_path('framework/testing/sab-calculator-meta.json');
        }

        return storage_path(self::META_PATH);
    }

    /**
     * Fetch rot.rocks prices for existing listed items, upsert current values,
     * insert new observations, and merge today's points into per-item JSON.
     *
     * @return array{items: int, current_values: int, observations: int, json_files: int, skipped_remote: int}
     */
    public function refresh(): array
    {
        $source = SeoValueSource::query()->where('slug', self::SOURCE_SLUG)->firstOrFail();
        $brainrots = $this->fetchJson('/api/brainrots')['brainrots'] ?? [];
        $mutations = $this->fetchJson('/api/mutations')['mutations'] ?? [];
        $mutationById = collect(is_array($mutations) ? $mutations : [])->keyBy('id');

        $items = SeoItem::query()
            ->where('is_listed', true)
            ->with('variants')
            ->get()
            ->keyBy('slug');

        $writer = app(SabPriceHistoryWriter::class);
        $today = now()->toDateString();
        $counts = [
            'items' => 0,
            'current_values' => 0,
            'observations' => 0,
            'json_files' => 0,
            'skipped_remote' => 0,
        ];

        foreach (is_array($brainrots) ? $brainrots : [] as $row) {
            if (! is_array($row) || trim((string) ($row['name'] ?? '')) === '') {
                continue;
            }

            $slug = Str::slug((string) $row['name']);
            $item = $items->get($slug);
            if (! $item instanceof SeoItem) {
                $counts['skipped_remote']++;
                continue;
            }

            $todayPoints = [];

            $base = $item->variants->firstWhere('variant_key', 'base')
                ?? $item->variants->firstWhere('variant_type', 'base');
            if ($base && isset($row['robuxValue']) && $row['robuxValue'] !== null && $row['robuxValue'] !== '') {
                if ($this->upsertCurrentValue($base, $source, $row['robuxValue'], $row['demand'] ?? null)) {
                    $counts['observations']++;
                }
                $counts['current_values']++;
                $todayPoints[$base->id] = (float) $row['robuxValue'];
            }

            foreach ((array) ($row['mutationValues'] ?? []) as $mutationValue) {
                if (! is_array($mutationValue)) {
                    continue;
                }
                $variant = $this->existingMutationVariant(
                    $item->variants,
                    $mutationById,
                    $mutationValue,
                );
                $value = $mutationValue['robuxValue'] ?? null;
                if (! $variant || $value === null || $value === '') {
                    continue;
                }
                if ($this->upsertCurrentValue($variant, $source, $value, $mutationValue['demand'] ?? null)) {
                    $counts['observations']++;
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

        return $counts;
    }

    /**
     * @param  Collection<int, SeoItemVariant>  $variants
     * @param  Collection<string|int, mixed>  $mutationById
     */
    private function existingMutationVariant(Collection $variants, Collection $mutationById, array $mutationValue): ?SeoItemVariant
    {
        $mutation = $mutationById->get($mutationValue['mutationId'] ?? '');
        $name = is_array($mutation)
            ? (string) ($mutation['name'] ?? $mutationValue['mutationName'] ?? '')
            : (string) ($mutationValue['mutationName'] ?? '');
        $key = $name !== '' ? 'mutation-'.Str::slug($name) : '';
        $mutationId = trim((string) ($mutationValue['mutationId'] ?? ''));

        return $variants->first(function (SeoItemVariant $variant) use ($key, $mutationId): bool {
            if ($key !== '' && (string) $variant->variant_key === $key) {
                return true;
            }

            return $mutationId !== ''
                && (string) data_get($variant->attributes_json, 'rot_id') === $mutationId;
        });
    }

    /**
     * @return bool true when a new observation row was inserted
     */
    private function upsertCurrentValue(SeoItemVariant $variant, SeoValueSource $source, mixed $value, mixed $demand): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $hash = sha1(json_encode([$variant->id, $value, $demand]));

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
            return false;
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

        return true;
    }

    private function nextObservationId(): int
    {
        $this->nextObservationId ??= ((int) SeoItemObservation::query()->max('id')) + 1;

        return $this->nextObservationId++;
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
}
