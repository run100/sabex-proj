@extends('seo.sab.layout')

@section('content')
<style>
  .sab-v2-article {
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }
  .sab-v2-hero {
    display: grid;
    grid-template-columns: 8rem minmax(0, 1fr);
    gap: 1.25rem;
    align-items: center;
  }
  .sab-v2-image-box {
    align-items: center;
    background: rgba(30, 41, 59, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.75rem;
    display: flex;
    height: 8rem;
    justify-content: center;
    padding: 0.5rem;
    width: 8rem;
  }
  .sab-v2-image-box img {
    border-radius: 0.5rem;
    height: 100%;
    object-fit: cover;
    width: 100%;
  }
  .sab-v2-panel {
    background: rgba(15, 23, 42, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.75rem;
    padding: 1.25rem;
  }
  .sab-v2-hero-count {
    background: linear-gradient(135deg, rgba(8, 145, 178, 0.22), rgba(15, 23, 42, 0.65));
    border: 1px solid rgba(103, 232, 249, 0.35);
    border-radius: 0.75rem;
    margin-top: 1rem;
    padding: 1rem;
  }
  .sab-v2-stats {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  .sab-v2-two-col {
    display: grid;
    gap: 1rem;
    grid-template-columns: minmax(0, 1fr) minmax(20rem, 26rem);
  }
  .sab-v2-source-grid {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .sab-v2-source-card {
    background: rgba(2, 6, 23, 0.35);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
    padding: 1rem;
  }
  .sab-v2-table-wrap {
    max-width: 100%;
    overflow-x: auto;
  }
  .sab-v2-wide-table {
    min-width: 720px;
    width: 100%;
  }
  @media (max-width: 767px) {
    .sab-v2-hero,
    .sab-v2-two-col,
    .sab-v2-source-grid {
      grid-template-columns: 1fr;
    }
    .sab-v2-stats {
      grid-template-columns: 1fr;
    }
    .sab-v2-image-box {
      height: 7rem;
      width: 7rem;
    }
  }
</style>

@php
  $formatCount = fn ($count) => $count !== null ? number_format((float) $count) : '-';
  $formatValue = fn ($value) => $value !== null ? number_format((float) $value) : '-';
  $formatDate = fn ($date) => $date ? $date->format('Y-m-d H:i') : '-';
  $variantAnchorKey = static fn ($name) => \Illuminate\Support\Str::slug((string) $name);
  $sourceDomainLabel = 'stealabrainrot.fandom.com';
  $isFandomUrl = static fn ($url) => str_contains(strtolower((string) $url), $sourceDomainLabel);

  $itemAttrs = is_array($item->attributes_json ?? null) ? $item->attributes_json : [];
  $galleryImages = collect(is_array($itemAttrs['gallery_variants'] ?? null) ? $itemAttrs['gallery_variants'] : [])
    ->filter(fn ($galleryImage) => is_array($galleryImage) && trim((string) ($galleryImage['local_image_url'] ?? '')) !== '')
    ->values();

  $localImagePath = ltrim((string) ($item->local_image_url ?? ''), '/');
  $imgSrc = $localImagePath !== '' && file_exists(public_path($localImagePath))
    ? ('/' . $localImagePath)
    : $item->image_url;

  $currentRows = $item->variants
    ->flatMap(fn ($variant) => $variant->currentValues->map(fn ($cv) => ['variant' => $variant, 'cv' => $cv]))
    ->values();
  $bestCountRow = $currentRows
    ->filter(fn ($row) => $row['cv']->exist_count_normalized !== null)
    ->sortByDesc(fn ($row) => $row['cv']->collected_at?->timestamp ?? 0)
    ->first();
  $bestValueRow = $currentRows
    ->filter(fn ($row) => $row['cv']->value_normalized !== null)
    ->sortByDesc(fn ($row) => $row['cv']->collected_at?->timestamp ?? 0)
    ->first();
  $bestCountCv = $bestCountRow['cv'] ?? null;
  $bestValueCv = $bestValueRow['cv'] ?? null;
  $bestCountPayload = is_array($bestCountCv?->lastObservation?->source_payload_json ?? null) ? $bestCountCv->lastObservation->source_payload_json : [];
  $bestValuePayload = is_array($bestValueCv?->lastObservation?->source_payload_json ?? null) ? $bestValueCv->lastObservation->source_payload_json : [];
  $bestCountSourceUrl = (string) ($bestCountPayload['source_page_url'] ?? $bestCountCv?->source?->url ?? '');
  $bestValueSourceUrl = (string) ($bestValuePayload['source_page_url'] ?? $bestValueCv?->source?->url ?? '');
  $totalExists = $item->total_exists;
  $rarityKey = \App\Services\Seo\SabRenderService::canonicalRarityKey($item->rarity ?? null);
  $rarityLabel = $rarityKey !== '' ? \App\Services\Seo\SabRenderService::canonicalRarityLabel($rarityKey) : '';
  $badgeClass = match(true) {
    in_array($rarityKey, ['legendary','secret']) => 'bg-pink-500/20 text-pink-300 border-pink-500/30',
    $rarityKey === 'rare' => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
    $rarityKey === 'uncommon' => 'bg-cyan-500/20 text-cyan-300 border-cyan-500/30',
    default => 'bg-slate-700/60 text-slate-300 border-slate-600/50',
  };

  $lastUpdated = collect([
    $item->stats_updated_at,
    $item->wiki_checked_at,
    $bestCountCv?->collected_at,
    $bestValueCv?->collected_at,
  ])->merge($sourcePool->pluck('last_collected_at'))
    ->filter()
    ->sortByDesc(fn ($date) => $date->timestamp ?? 0)
    ->first();

  $currentSourceCards = $currentRows->map(function ($row) {
    $cv = $row['cv'];
    $payload = is_array($cv->lastObservation?->source_payload_json ?? null) ? $cv->lastObservation->source_payload_json : [];
    $fields = collect([
      $cv->exist_count_normalized !== null ? 'Exist count' : null,
      $cv->value_normalized !== null ? 'Value' : null,
      $cv->demand ? 'Demand' : null,
    ])->filter()->implode(' / ');

    return [
      'name' => $cv->source?->name ?: ($cv->source?->slug ?: 'Unknown source'),
      'slug' => $cv->source?->slug ?: 'unknown',
      'type' => $fields !== '' ? $fields : 'Current value',
      'url' => (string) ($payload['source_page_url'] ?? $cv->source?->url ?? ''),
      'collector' => (string) ($payload['collector_endpoint'] ?? ''),
      'collected_at' => $cv->collected_at,
      'changed_at' => $cv->changed_at,
      'confidence' => $cv->confidence,
    ];
  });

  $poolSourceCards = $sourcePool->map(fn ($pool) => [
    'name' => $pool->source_name ?: $pool->source_slug,
    'slug' => $pool->source_slug,
    'type' => $pool->source_type ?: 'Item reference',
    'url' => (string) $pool->source_page_url,
    'collector' => (string) $pool->collector_endpoint,
    'collected_at' => $pool->last_collected_at,
    'changed_at' => $pool->last_changed_at,
    'confidence' => $pool->confidence,
  ]);

  $sourceCards = $currentSourceCards
    ->merge($poolSourceCards)
    ->filter(fn ($source) => trim((string) ($source['name'] ?? '')) !== '')
    ->filter(fn ($source) => $isFandomUrl($source['url'] ?? ''))
    ->unique(fn ($source) => ($source['slug'] ?? '').'|'.($source['type'] ?? '').'|'.($source['url'] ?? ''))
    ->take(10)
    ->values();

  $fandomRecentObservations = $recentObservations
    ->filter(function ($observation) use ($isFandomUrl) {
      $payload = is_array($observation->source_payload_json ?? null) ? $observation->source_payload_json : [];
      return $isFandomUrl($payload['source_page_url'] ?? $observation->source?->url ?? '');
    })
    ->values();

  $variantRows = $item->variants
    ->filter(fn ($variant) => $variant->variant_key !== 'base')
    ->sortBy(fn ($variant) => sprintf('%03d-%s', (int) ($variant->sort_order ?? 10), strtolower((string) $variant->variant_name)))
    ->map(function ($variant) {
      $latest = $variant->currentValues
        ->sortByDesc(fn ($cv) => ($cv->changed_at ?? $cv->collected_at)?->timestamp ?? 0)
        ->first();

      return [
        'name' => $variant->mutation_name ?: ($variant->trait_name ?: $variant->variant_name),
        'type' => $variant->variant_type ?: 'variant',
        'exist' => $latest?->exist_count_normalized,
        'value' => $latest?->value_normalized,
        'demand' => $latest?->demand,
        'source_url' => (string) (is_array($latest?->lastObservation?->source_payload_json ?? null)
          ? ($latest->lastObservation->source_payload_json['source_page_url'] ?? '')
          : ($latest?->source?->url ?? '')),
      ];
    })
    ->values();

  $explainStatus = $dataStatus['has_count']
    ? "{$displayName} currently has ".$formatCount($totalExists)." known exists in the tracked dataset. This number should be treated as a live reference because public source tables can change after game updates or source corrections."
    : "A source page is available for {$displayName}, but this preview does not have a confirmed public exist count yet. The page can still show item references, value data, and source checks without inventing a count.";
@endphp

<nav class="mb-5 mt-4 text-sm text-slate-400">
  <a href="/seo/sab/preview" class="hover:text-cyan-300">Home</a>
  <span class="mx-2 text-slate-600">/</span>
  <span class="text-slate-200">{{ $displayName }}</span>
</nav>

<article class="sab-v2-article">
  <section class="sab-v2-panel sab-v2-hero">
    <div class="sab-v2-image-box">
      @if($imgSrc)
      <img src="{{ $imgSrc }}" alt="{{ $displayName }}" />
      @else
      <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">No image</span>
      @endif
    </div>
    <div class="min-w-0">
      <div class="mb-2 flex flex-wrap items-center gap-2">
        @if($rarityLabel !== '')
        <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-bold uppercase {{ $badgeClass }}">{{ $rarityLabel }}</span>
        @endif
        <span class="inline-flex rounded-full border border-cyan-400/30 bg-cyan-400/10 px-2.5 py-0.5 text-xs font-bold uppercase text-cyan-200">{{ $dataStatus['label'] }}</span>
      </div>
      <h1 class="text-2xl font-black leading-tight text-slate-100 md:text-4xl">{{ $displayName }} <span class="text-cyan-300">Exist Count</span></h1>
      <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300 md:text-base">
        This preview tracks {{ $displayName }} exist count, value signals, mutation data, and the source records behind the numbers.
      </p>
      <div class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Last updated: {{ $formatDate($lastUpdated) }}</div>
      <div class="sab-v2-hero-count">
        <div class="text-xs font-black uppercase tracking-wide text-cyan-200">Confirmed Exist Count</div>
        <div class="mt-2 text-5xl font-black leading-none text-cyan-300">{{ $totalExists !== null ? $formatCount($totalExists) : '-' }}</div>
        @if($isFandomUrl($bestCountSourceUrl))
        <a href="{{ $bestCountSourceUrl }}" target="_blank" rel="nofollow noreferrer" class="mt-3 block truncate text-sm font-bold text-cyan-100 hover:text-cyan-200">{{ $sourceDomainLabel }}</a>
        @else
        <div class="mt-3 text-sm font-semibold text-slate-500">Source not confirmed</div>
        @endif
      </div>
    </div>
  </section>

  <section class="sab-v2-stats">
    <div class="sab-v2-panel">
      <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Current Value</div>
      <div class="mt-2 text-3xl font-black text-purple-300">{{ $bestValueCv?->value_normalized !== null ? $formatValue($bestValueCv->value_normalized) : '-' }}</div>
      @if($isFandomUrl($bestValueSourceUrl))
      <a href="{{ $bestValueSourceUrl }}" target="_blank" rel="nofollow noreferrer" class="mt-1 block truncate text-xs font-semibold text-cyan-300 hover:text-cyan-200">{{ $sourceDomainLabel }}</a>
      @endif
    </div>
    <div class="sab-v2-panel">
      <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Rarest Mutation</div>
      <div class="mt-2 text-xl font-black text-slate-100">{{ trim((string) $item->rarest_mutation_name) !== '' ? $item->rarest_mutation_name : '-' }}</div>
      <div class="mt-1 text-xs text-slate-500">{{ $item->rarest_mutation_count !== null ? $formatCount($item->rarest_mutation_count).' known exists' : 'No confirmed count' }}</div>
    </div>
    <div class="sab-v2-panel">
      <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Data Status</div>
      <div class="mt-2 text-xl font-black text-slate-100">{{ $dataStatus['label'] }}</div>
      <div class="mt-1 text-xs leading-5 text-slate-500">{{ $dataStatus['detail'] }}</div>
    </div>
  </section>

  <section class="sab-v2-two-col">
    <div class="sab-v2-panel">
      <h2 class="text-lg font-black text-slate-100">What this count means</h2>
      <p class="mt-3 text-sm leading-6 text-slate-300">{{ $explainStatus }}</p>
      @if(trim((string) ($description ?? '')) !== '')
      <p class="mt-3 text-sm leading-6 text-slate-400">{{ $description }}</p>
      @endif
    </div>
    <div class="sab-v2-panel">
      <h2 class="text-lg font-black text-slate-100">Item Reference</h2>
      <dl class="mt-3 space-y-3 text-sm">
        <div>
          <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Wiki page</dt>
          <dd class="mt-1 truncate text-slate-300">
            @if(trim((string) $item->wiki_page_url) !== '')
            <a href="{{ $item->wiki_page_url }}" target="_blank" rel="nofollow noreferrer" class="text-cyan-300 hover:text-cyan-200">{{ $sourceDomainLabel }}</a>
            @else
            -
            @endif
          </dd>
        </div>
        <div>
          <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Checked at</dt>
          <dd class="mt-1 text-slate-300">{{ $formatDate($item->wiki_checked_at) }}</dd>
        </div>
      </dl>
    </div>
  </section>

  <section class="sab-v2-panel">
    <h2 class="text-lg font-black text-slate-100">Data Sources</h2>
    @if($sourceCards->isNotEmpty())
    <div class="sab-v2-source-grid mt-4">
      @foreach($sourceCards as $source)
      <article class="sab-v2-source-card">
        <div class="flex flex-wrap items-start justify-between gap-2">
          <div>
            <h3 class="font-bold text-slate-100">{{ $sourceDomainLabel }}</h3>
            <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-cyan-300">{{ $source['type'] }}</p>
          </div>
          <span class="text-xs font-semibold text-slate-500">Confidence {{ $source['confidence'] ?? '-' }}</span>
        </div>
        <dl class="mt-3 grid gap-2 text-xs text-slate-400 sm:grid-cols-2">
          <div>
            <dt class="font-bold uppercase tracking-wide text-slate-500">Collected</dt>
            <dd class="mt-1">{{ $formatDate($source['collected_at'] ?? null) }}</dd>
          </div>
          <div>
            <dt class="font-bold uppercase tracking-wide text-slate-500">Changed</dt>
            <dd class="mt-1">{{ $formatDate($source['changed_at'] ?? null) }}</dd>
          </div>
        </dl>
        @if(trim((string) ($source['url'] ?? '')) !== '')
        <a href="{{ $source['url'] }}" target="_blank" rel="nofollow noreferrer" class="mt-3 block truncate text-sm font-semibold text-cyan-300 hover:text-cyan-200">{{ $source['url'] }}</a>
        @endif
      </article>
      @endforeach
    </div>
    @else
    <p class="mt-3 text-sm text-slate-500">No source evidence is available for this item in the current dataset.</p>
    @endif
  </section>

  <section class="sab-v2-panel">
    <h2 class="text-lg font-black text-slate-100">Count History</h2>
    @if($fandomRecentObservations->isNotEmpty())
    <div class="sab-v2-table-wrap mt-4">
      <table class="sab-v2-wide-table text-sm">
        <thead>
          <tr class="border-b border-white/10 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
            <th class="py-3 pr-4">Date</th>
            <th class="py-3 pr-4">Source</th>
            <th class="py-3 pr-4">Variant</th>
            <th class="py-3 pr-4">Exist Count</th>
            <th class="py-3 pr-4">Value</th>
            <th class="py-3 pr-4">Demand</th>
          </tr>
        </thead>
        <tbody>
          @foreach($fandomRecentObservations as $observation)
          @php
            $observationPayload = is_array($observation->source_payload_json ?? null) ? $observation->source_payload_json : [];
            $observationSourceUrl = (string) ($observationPayload['source_page_url'] ?? $observation->source?->url ?? '');
          @endphp
          <tr class="border-b border-white/5 text-slate-300">
            <td class="py-3 pr-4 text-slate-400">{{ $formatDate($observation->observed_at) }}</td>
            <td class="py-3 pr-4">
              <a href="{{ $observationSourceUrl }}" target="_blank" rel="nofollow noreferrer" class="font-semibold text-cyan-300 hover:text-cyan-200">{{ $sourceDomainLabel }}</a>
            </td>
            <td class="py-3 pr-4">{{ $observation->variant?->variant_name ?: ($observation->variant?->variant_key ?: '-') }}</td>
            <td class="py-3 pr-4 font-mono">{{ $observation->exist_count_normalized !== null ? $formatCount($observation->exist_count_normalized) : '-' }}</td>
            <td class="py-3 pr-4 font-mono">{{ $observation->value_normalized !== null ? $formatValue($observation->value_normalized) : '-' }}</td>
            <td class="py-3 pr-4">{{ $observation->demand ?: '-' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @else
    <p class="mt-3 text-sm text-slate-500">No Fandom count history is available for this item yet.</p>
    @endif
  </section>

  @if($variantRows->isNotEmpty())
  <section class="sab-v2-panel">
    <h2 class="text-lg font-black text-slate-100">Variants and Mutations</h2>
    <p class="mt-2 text-sm leading-6 text-slate-400">Rows marked with a source slug have current source data. Empty count fields mean the variant exists in the dataset, but no public count was found.</p>
    <div class="sab-v2-table-wrap mt-4">
      <table class="sab-v2-wide-table text-sm">
        <thead>
          <tr class="border-b border-white/10 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
            <th class="py-3 pr-4">Variant</th>
            <th class="py-3 pr-4">Type</th>
            <th class="py-3 pr-4">Exist Count</th>
            <th class="py-3 pr-4">Value</th>
            <th class="py-3 pr-4">Demand</th>
            <th class="py-3 pr-4">Source</th>
          </tr>
        </thead>
        <tbody>
          @foreach($variantRows as $row)
          <tr id="mutation-{{ $variantAnchorKey($row['name']) }}" class="border-b border-white/5 text-slate-300">
            <td class="py-3 pr-4 font-semibold text-slate-100">{{ $row['name'] }}</td>
            <td class="py-3 pr-4 text-slate-400">{{ $row['type'] }}</td>
            <td class="py-3 pr-4 font-mono">{{ $row['exist'] !== null ? $formatCount($row['exist']) : '-' }}</td>
            <td class="py-3 pr-4 font-mono">{{ $row['value'] !== null ? $formatValue($row['value']) : '-' }}</td>
            <td class="py-3 pr-4">{{ $row['demand'] ?: '-' }}</td>
            <td class="py-3 pr-4">
              @if($isFandomUrl($row['source_url'] ?? ''))
              <a href="{{ $row['source_url'] }}" target="_blank" rel="nofollow noreferrer" class="font-semibold text-cyan-300 hover:text-cyan-200">{{ $sourceDomainLabel }}</a>
              @else
              -
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </section>
  @endif

  @if($galleryImages->isNotEmpty())
  <section class="sab-v2-panel">
    <h2 class="text-lg font-black text-slate-100">{{ $t['item_gallery_images_h2'] }}</h2>
    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
      @foreach($galleryImages as $galleryImage)
      @php
        $gallerySrc = '/' . ltrim((string) ($galleryImage['local_image_url'] ?? ''), '/');
        $galleryName = trim((string) ($galleryImage['name'] ?? $displayName));
      @endphp
      <article class="rounded-lg border border-white/10 bg-slate-950/50 p-2">
        <img src="{{ $gallerySrc }}" alt="{{ $galleryName }}" class="aspect-square w-full rounded-md object-contain" loading="lazy" />
        <h3 class="mt-2 text-sm font-semibold leading-snug text-slate-100">{{ $galleryName }}</h3>
      </article>
      @endforeach
    </div>
  </section>
  @endif
</article>
@endsection
