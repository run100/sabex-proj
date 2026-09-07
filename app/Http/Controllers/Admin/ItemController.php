<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoItem;
use App\Models\SeoItemAlias;
use App\Models\SeoItemCurrentValue;
use App\Models\SeoItemObservation;
use App\Models\SeoItemTranslation;
use App\Models\SeoItemVariant;
use App\Models\SeoValueSource;
use App\Services\Seo\SabRenderService;
use App\Support\SabHost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    /**
     * @var list<string>
     */
    private const WIKI_STATUSES = ['found', 'redirected', 'missing', 'unknown'];

    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', $request->query('name', '')));
        $rarity = trim((string) $request->query('rarity', ''));
        $listed = trim((string) $request->query('listed', ''));
        $publish = trim((string) $request->query('publish', ''));
        $wiki = trim((string) $request->query('wiki_page_status', $request->query('wiki', '')));
        $perPage = min(200, max(1, (int) $request->query('limit', 100)));

        $hasWikiStatus = Schema::hasColumn('seo_items', 'wiki_page_status');
        $hasCurrentValues = Schema::hasTable('seo_item_current_values')
            && Schema::hasTable('seo_item_variants');

        $query = SeoItem::query()->select('seo_items.*');

        if ($hasCurrentValues) {
            $query
                ->selectSub($this->sourcesCountSubquery(), 'sources_count')
                ->selectSub($this->primaryValueSubquery(), 'primary_value');
        }

        $query->withCount('variants');

        $query
            ->when($q !== '', fn ($inner) => $inner->where(function ($search) use ($q): void {
                $search->where('seo_items.slug', 'like', '%'.$q.'%')
                    ->orWhere('seo_items.name', 'like', '%'.$q.'%');
            }))
            ->when($this->isTruthy($listed), fn ($inner) => $inner->where('seo_items.is_listed', true))
            ->when($this->isFalsy($listed), fn ($inner) => $inner->where('seo_items.is_listed', false))
            ->when($this->isTruthy($publish), fn ($inner) => $inner->where('seo_items.is_publish_html', true))
            ->when($this->isFalsy($publish), fn ($inner) => $inner->where('seo_items.is_publish_html', false))
            ->when($wiki !== '' && $hasWikiStatus && in_array($wiki, self::WIKI_STATUSES, true), function ($inner) use ($wiki): void {
                if ($wiki === 'unknown') {
                    $inner->where(function ($status): void {
                        $status->whereNull('seo_items.wiki_page_status')
                            ->orWhere('seo_items.wiki_page_status', '')
                            ->orWhere('seo_items.wiki_page_status', 'unknown');
                    });

                    return;
                }
                $inner->where('seo_items.wiki_page_status', $wiki);
            })
            ->when($request->query('has_exist_count') !== null && $request->query('has_exist_count') !== '', function ($inner) use ($request, $hasCurrentValues): void {
                $want = $this->isTruthy((string) $request->query('has_exist_count'));
                if ($want) {
                    $inner->where(function ($exist) use ($hasCurrentValues): void {
                        $exist->whereNotNull('seo_items.total_exists');
                        if ($hasCurrentValues) {
                            $exist->orWhereExists($this->existCountExists());
                        }
                    });
                } else {
                    $inner->whereNull('seo_items.total_exists');
                    if ($hasCurrentValues) {
                        $inner->whereNotExists($this->existCountExists());
                    }
                }
            })
            ->when($request->query('has_value') !== null && $request->query('has_value') !== '' && $hasCurrentValues, function ($inner) use ($request): void {
                if ($this->isTruthy((string) $request->query('has_value'))) {
                    $inner->whereExists($this->valueExists());
                } else {
                    $inner->whereNotExists($this->valueExists());
                }
            })
            ->orderByDesc('seo_items.is_listed')
            ->orderBy('seo_items.name');

        if ($rarity !== '') {
            SabRenderService::applyCanonicalRarityFilter($query, $rarity);
        }

        $page = $query->paginate($perPage);
        $tags = SabRenderService::buildAdminRarityTagStats(
            SeoItem::query()->where('is_listed', true)->get(['id', 'rarity'])
        );

        return response()->json([
            'items' => collect($page->items())->map(fn (SeoItem $item): array => $this->serialize($item))->all(),
            'page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'total' => $page->total(),
            'counts' => [
                'all' => (int) SeoItem::query()->count(),
                'tags' => $tags,
            ],
        ]);
    }

    public function show(SeoItem $item): JsonResponse
    {
        return response()->json([
            'item' => $this->serializeDetail($item),
            'locales' => SabRenderService::supportedLocales(),
        ]);
    }

    public function update(Request $request, SeoItem $item): JsonResponse
    {
        $rules = [
            'is_listed' => ['sometimes', 'boolean'],
            'is_publish_html' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:999999'],
            'name' => ['sometimes', 'string', 'max:255'],
            'rarity' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
        foreach ([
            'slug' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'summary' => ['sometimes', 'nullable', 'string'],
            'is_hot' => ['sometimes', 'boolean'],
            'hot_rank' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:65535'],
            'total_exists' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'rarest_mutation_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'rarest_mutation_count' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'rarest_trait_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'rarest_trait_count' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'avg_rebirth' => ['sometimes', 'nullable', 'numeric'],
            'avg_coins_raw' => ['sometimes', 'nullable', 'string', 'max:255'],
            'stats_updated_at' => ['sometimes', 'nullable', 'date'],
            'image_url' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'local_image_url' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'source_page_url' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'wiki_page_url' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'wiki_page_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'wiki_page_id' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'wiki_page_status' => ['sometimes', 'nullable', Rule::in(self::WIKI_STATUSES)],
            'wiki_checked_at' => ['sometimes', 'nullable', 'date'],
            'purchase_url' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'attributes_json' => ['sometimes', 'nullable', 'array'],
        ] as $field => $rule) {
            if (Schema::hasColumn('seo_items', $field)) {
                $rules[$field] = $rule;
            }
        }

        $data = $request->validate($rules);
        if ($data === []) {
            return response()->json([
                'message' => 'At least one field is required.',
            ], 422);
        }

        foreach (['rarity', 'rarest_mutation_name', 'rarest_trait_name', 'avg_coins_raw', 'image_url', 'local_image_url', 'wiki_page_url', 'wiki_page_title', 'wiki_page_status'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null && Schema::hasColumn('seo_items', $field)) {
                $data[$field] = '';
            }
        }

        $item->fill($data);
        $item->save();

        $listOnly = array_diff(array_keys($data), ['is_listed', 'is_publish_html', 'is_hot', 'sort_order', 'name', 'rarity']) === [];

        return response()->json([
            'item' => $listOnly
                ? $this->serialize($this->hydrateListCounts($item))
                : $this->serializeDetail($item),
        ]);
    }

    public function updateVariants(Request $request, SeoItem $item): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.variant_key' => ['required', 'string', 'max:255'],
            'items.*.variant_name' => ['required', 'string', 'max:255'],
            'items.*.variant_type' => ['nullable', Rule::in(['base', 'mutation', 'trait', 'value_option'])],
            'items.*.mutation' => ['nullable', 'string', 'max:255'],
            'items.*.mutation_name' => ['nullable', 'string', 'max:255'],
            'items.*.trait' => ['nullable', 'string', 'max:255'],
            'items.*.trait_name' => ['nullable', 'string', 'max:255'],
            'items.*.exist_percentage' => ['nullable', 'numeric'],
            'items.*.multiplier' => ['nullable', 'numeric'],
            'items.*.sort_order' => ['nullable', 'integer'],
            'items.*.attributes_json' => ['nullable', 'array'],
        ]);

        try {
            DB::transaction(function () use ($item, $data): void {
                $keepIds = [];
                foreach ($data['items'] as $row) {
                    $variant = ! empty($row['id'])
                        ? SeoItemVariant::query()->where('seo_item_id', $item->id)->findOrFail((int) $row['id'])
                        : new SeoItemVariant(['seo_item_id' => $item->id]);
                    $fill = [
                        'variant_key' => $row['variant_key'],
                        'variant_name' => $row['variant_name'],
                        'variant_type' => $row['variant_type'] ?? 'base',
                        'sort_order' => $row['sort_order'] ?? 10,
                        'attributes_json' => $row['attributes_json'] ?? null,
                    ];
                    foreach (['mutation', 'mutation_name', 'trait', 'trait_name', 'exist_percentage', 'multiplier'] as $field) {
                        if (Schema::hasColumn('seo_item_variants', $field) && array_key_exists($field, $row)) {
                            $fill[$field] = $row[$field] ?? (in_array($field, ['exist_percentage', 'multiplier'], true) ? null : '');
                        }
                    }
                    $variant->fill($fill)->save();
                    $keepIds[] = $variant->id;
                }

                $deleteQuery = SeoItemVariant::query()->where('seo_item_id', $item->id)->whereNotIn('id', $keepIds);
                $blocked = (clone $deleteQuery)->where(function ($builder): void {
                    $builder->whereHas('currentValues')->orWhereHas('observations');
                })->exists();
                if ($blocked) {
                    throw new \RuntimeException('已有采集数据的规格不能直接删除');
                }
                $deleteQuery->delete();
            });
        } catch (\RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'item' => $this->serializeDetail($item->fresh()),
        ]);
    }

    public function updateTranslations(Request $request, SeoItem $item): JsonResponse
    {
        $data = $request->validate([
            'items' => ['present', 'array'],
            'items.*.locale' => ['required', 'string', 'max:8'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.seo_title' => ['nullable', 'string', 'max:255'],
            'items.*.seo_description' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($item, $data): void {
            $locales = [];
            foreach ($data['items'] as $row) {
                $locales[] = $row['locale'];
                SeoItemTranslation::query()->updateOrCreate(
                    ['seo_item_id' => $item->id, 'locale' => $row['locale']],
                    [
                        'name' => $row['name'] ?? '',
                        'description' => $row['description'] ?? '',
                        'seo_title' => $row['seo_title'] ?? '',
                        'seo_description' => $row['seo_description'] ?? '',
                    ]
                );
            }
            SeoItemTranslation::query()
                ->where('seo_item_id', $item->id)
                ->whereNotIn('locale', $locales)
                ->delete();
        });

        return response()->json([
            'item' => $this->serializeDetail($item->fresh()),
        ]);
    }

    public function updateAliases(Request $request, SeoItem $item): JsonResponse
    {
        if (! Schema::hasTable('seo_item_aliases')) {
            return response()->json(['message' => 'Aliases are unavailable.'], 422);
        }

        $data = $request->validate([
            'items' => ['present', 'array'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.seo_value_source_id' => ['required', 'integer'],
            'items.*.alias_name' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($item, $data): void {
            $keepIds = [];
            foreach ($data['items'] as $row) {
                $alias = ! empty($row['id'])
                    ? SeoItemAlias::query()->where('seo_item_id', $item->id)->findOrFail((int) $row['id'])
                    : new SeoItemAlias(['seo_item_id' => $item->id]);
                $alias->fill([
                    'seo_value_source_id' => $row['seo_value_source_id'],
                    'alias_name' => $row['alias_name'],
                ])->save();
                $keepIds[] = $alias->id;
            }
            SeoItemAlias::query()
                ->where('seo_item_id', $item->id)
                ->whereNotIn('id', $keepIds)
                ->delete();
        });

        return response()->json([
            'item' => $this->serializeDetail($item->fresh()),
        ]);
    }

    public function observations(Request $request, SeoItem $item): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('limit', 10)));
        $page = $this->observationQuery($item->id)->paginate($perPage);

        return response()->json([
            'items' => $this->decorateObservations(collect($page->items()))->all(),
            'page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'total' => $page->total(),
        ]);
    }

    public function valueSources(): JsonResponse
    {
        $sources = Schema::hasTable('seo_value_sources')
            ? SeoValueSource::query()->orderBy('name')->get(['id', 'slug', 'name'])
            : collect();

        return response()->json([
            'sources' => $sources->all(),
        ]);
    }

    public function bulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'is_listed' => ['sometimes', 'boolean'],
            'is_publish_html' => ['sometimes', 'boolean'],
        ]);

        $payload = [];
        if (array_key_exists('is_listed', $data)) {
            $payload['is_listed'] = (bool) $data['is_listed'];
        }
        if (array_key_exists('is_publish_html', $data)) {
            $payload['is_publish_html'] = (bool) $data['is_publish_html'];
        }
        if ($payload === []) {
            return response()->json([
                'message' => 'At least one field is required.',
            ], 422);
        }

        $updated = SeoItem::query()
            ->whereIn('id', $data['ids'])
            ->update($payload);

        return response()->json([
            'updated' => $updated,
        ]);
    }

    private function hydrateListCounts(SeoItem $item): SeoItem
    {
        if (! Schema::hasTable('seo_item_current_values') || ! Schema::hasTable('seo_item_variants')) {
            return $item->loadCount('variants');
        }

        $fresh = SeoItem::query()
            ->select('seo_items.*')
            ->selectSub($this->sourcesCountSubquery(), 'sources_count')
            ->selectSub($this->primaryValueSubquery(), 'primary_value')
            ->withCount('variants')
            ->whereKey($item->id)
            ->first();

        return $fresh ?? $item->loadCount('variants');
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(SeoItem $item): array
    {
        $rot = data_get($item->attributes_json, 'rot_rocks', []);
        $rot = is_array($rot) ? $rot : [];
        $basePrice = $rot['base_price'] ?? $rot['base_cost'] ?? null;
        $primary = $item->getAttribute('primary_value');
        if ($primary === null && Schema::hasColumn('seo_items', 'supreme_value') && $item->supreme_value !== null && $item->supreme_value !== '') {
            $primary = $item->supreme_value;
        }
        if ($primary === null && isset($rot['robux_value'])) {
            $primary = $rot['robux_value'];
        }

        $wikiStatus = Schema::hasColumn('seo_items', 'wiki_page_status')
            ? trim((string) ($item->wiki_page_status ?? ''))
            : '';
        if ($wikiStatus === '' || ! in_array($wikiStatus, self::WIKI_STATUSES, true)) {
            $wikiStatus = 'unknown';
        }

        return [
            'id' => $item->id,
            'slug' => $item->slug,
            'name' => $item->name,
            'rarity' => $item->rarity,
            'total_exists' => $item->total_exists,
            'base_income' => $rot['base_income'] ?? null,
            'base_cost' => $rot['base_cost'] ?? null,
            'base_price' => $basePrice,
            'value' => $primary,
            'primary_value' => $primary,
            'wiki_page_status' => $wikiStatus,
            'wiki_page_url' => Schema::hasColumn('seo_items', 'wiki_page_url') ? $item->wiki_page_url : null,
            'is_listed' => (bool) $item->is_listed,
            'is_publish_html' => (bool) $item->is_publish_html,
            'is_hot' => Schema::hasColumn('seo_items', 'is_hot') ? (bool) $item->is_hot : false,
            'sort_order' => (int) ($item->sort_order ?? 0),
            'variants_count' => (int) ($item->variants_count ?? 0),
            'sources_count' => (int) ($item->getAttribute('sources_count') ?? 0),
            'preview_path' => rtrim(SabHost::origin('www'), '/').'/products/'.$item->slug,
            'live_path' => 'https://sabexistcount.com/products/'.$item->slug,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDetail(SeoItem $item): array
    {
        $with = ['game.site'];
        if (Schema::hasTable('seo_item_translations')) {
            $with[] = 'translations';
        }
        if (Schema::hasTable('seo_item_aliases')) {
            $with[] = 'aliases.source';
        }
        if (Schema::hasTable('seo_item_variants')) {
            $with[] = Schema::hasTable('seo_item_current_values')
                ? 'variants.currentValues.source'
                : 'variants';
        }
        $item->load($with);
        if (Schema::hasTable('seo_item_current_values') && $item->relationLoaded('variants')) {
            $item->variants->each(function (SeoItemVariant $variant): void {
                if (! $variant->relationLoaded('currentValues')) {
                    return;
                }
                $variant->currentValues->each(function (SeoItemCurrentValue $current): void {
                    $current->setAttribute(
                        'evidence_url',
                        (string) ($current->source?->url ?? '')
                    );
                });
            });
        }

        $payload = $item->toArray();
        $payload['recent_observations'] = $this->decorateObservations(
            $this->observationQuery($item->id)->limit(30)->get()
        )->all();
        $payload['locales'] = SabRenderService::supportedLocales();

        return array_merge($payload, $this->serialize($this->hydrateListCounts($item)));
    }

    private function observationQuery(int $itemId)
    {
        if (! Schema::hasTable('seo_item_observations') || ! Schema::hasTable('seo_item_variants')) {
            return SeoItemObservation::query()->whereRaw('0 = 1');
        }

        $variantIds = SeoItemVariant::query()->where('seo_item_id', $itemId)->pluck('id');

        return SeoItemObservation::query()
            ->with(['source:id,slug,name,url', 'variant:id,variant_key,variant_name'])
            ->whereIn('seo_item_variant_id', $variantIds->isEmpty() ? [-1] : $variantIds->all())
            ->orderByDesc('observed_at')
            ->orderByDesc('id');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, SeoItemObservation>  $observations
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function decorateObservations($observations)
    {
        return $observations->map(function (SeoItemObservation $observation): array {
            $rawPayload = Schema::hasColumn('seo_item_observations', 'source_payload_json')
                ? $observation->source_payload_json
                : null;
            $payload = is_array($rawPayload) ? $rawPayload : [];

            return [
                'id' => $observation->id,
                'variant' => $observation->variant ? [
                    'id' => $observation->variant->id,
                    'variant_key' => $observation->variant->variant_key,
                    'variant_name' => $observation->variant->variant_name,
                ] : null,
                'source' => $observation->source ? [
                    'id' => $observation->source->id,
                    'name' => $observation->source->name,
                    'url' => $observation->source->url,
                ] : null,
                'evidence_url' => (string) ($payload['source_page_url'] ?? $observation->source?->url ?? ''),
                'exist_count_normalized' => $observation->exist_count_normalized ?? null,
                'value_normalized' => $observation->value_normalized,
                'demand' => $observation->demand,
                'observed_at' => optional($observation->observed_at)?->toIso8601String(),
                'source_payload_json' => Schema::hasColumn('seo_item_observations', 'source_payload_json')
                    ? $observation->source_payload_json
                    : null,
            ];
        });
    }

    private function sourcesCountSubquery(): \Closure
    {
        return function ($query): void {
            $query->from('seo_item_current_values')
                ->join('seo_item_variants', 'seo_item_variants.id', '=', 'seo_item_current_values.seo_item_variant_id')
                ->whereColumn('seo_item_variants.seo_item_id', 'seo_items.id')
                ->selectRaw('count(distinct seo_item_current_values.seo_value_source_id)');
        };
    }

    private function primaryValueSubquery(): \Closure
    {
        return function ($query): void {
            $query->from('seo_item_current_values')
                ->join('seo_item_variants', 'seo_item_variants.id', '=', 'seo_item_current_values.seo_item_variant_id')
                ->whereColumn('seo_item_variants.seo_item_id', 'seo_items.id')
                ->selectRaw('max(seo_item_current_values.value_normalized)');
        };
    }

    private function existCountExists(): \Closure
    {
        return function ($query): void {
            $query->selectRaw('1')
                ->from('seo_item_variants as exist_variants')
                ->join('seo_item_current_values as exist_values', 'exist_values.seo_item_variant_id', '=', 'exist_variants.id')
                ->whereColumn('exist_variants.seo_item_id', 'seo_items.id')
                ->whereNotNull('exist_values.exist_count_normalized');
        };
    }

    private function valueExists(): \Closure
    {
        return function ($query): void {
            $query->selectRaw('1')
                ->from('seo_item_variants as value_variants')
                ->join('seo_item_current_values as value_values', 'value_values.seo_item_variant_id', '=', 'value_variants.id')
                ->whereColumn('value_variants.seo_item_id', 'seo_items.id')
                ->whereNotNull('value_values.value_normalized');
        };
    }

    private function isTruthy(string $value): bool
    {
        return in_array(strtolower($value), ['1', 'true', 'y', 'yes'], true);
    }

    private function isFalsy(string $value): bool
    {
        return in_array(strtolower($value), ['0', 'false', 'n', 'no'], true);
    }
}
