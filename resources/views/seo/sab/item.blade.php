@extends('seo.sab.layout')

@section('content')

@php
  $itemAttrs = is_array($item->attributes_json ?? null) ? $item->attributes_json : [];
  $galleryImages = collect(is_array($itemAttrs['gallery_variants'] ?? null) ? $itemAttrs['gallery_variants'] : [])
    ->filter(fn ($galleryImage) => is_array($galleryImage) && trim((string) ($galleryImage['local_image_url'] ?? '')) !== '')
    ->values();
  $displayVariants = $item->variants
    ->filter(function ($variant) {
      $attrs = is_array($variant->attributes_json ?? null) ? $variant->attributes_json : [];
      $hasGalleryMarker = !empty($attrs['gallery_name'])
        || !empty($attrs['gallery_local_image_url'])
        || !empty($attrs['gallery_source_page_url']);

      return $variant->variant_key === 'base' || !$hasGalleryMarker;
    })
    ->sortBy(function ($variant) {
      $attrs = is_array($variant->attributes_json ?? null) ? $variant->attributes_json : [];
      $group = $variant->variant_key === 'base'
        ? '0'
        : (($attrs['source_origin'] ?? '') === 'gallery-exist-count' ? '2' : '1');

      return $group . strtolower((string) $variant->variant_name);
    })
    ->values();
  $baseVariants = $displayVariants->filter(fn ($variant) => ($variant->variant_type ?? 'base') === 'base')->values();
  $mutationVariants = $displayVariants->filter(fn ($variant) => ($variant->variant_type ?? '') === 'mutation')->sortBy('sort_order')->values();
  $traitVariants = $displayVariants->filter(fn ($variant) => ($variant->variant_type ?? '') === 'trait')->sortBy('sort_order')->values();
  $valueVariants = $displayVariants->filter(fn ($variant) => ($variant->variant_type ?? '') === 'value_option')->sortBy('sort_order')->values();
  if ($valueVariants->isEmpty()) {
    $valueVariants = $displayVariants
      ->filter(fn ($variant) => $variant->variant_key !== 'base'
        && !in_array(($variant->variant_type ?? 'base'), ['mutation', 'trait', 'value_option'], true))
      ->values();
  }
  $variantNameKey = static function ($name) {
    $normalized = strtolower(trim((string) $name));
    return preg_replace('/\s+/', ' ', $normalized);
  };
  $variantAnchorKey = static function ($name) {
    return \Illuminate\Support\Str::slug((string) $name);
  };
  $latestCurrentValue = static function ($variant) {
    if (!$variant) return null;
    return $variant->currentValues
      ->sortByDesc(fn($row) => ($row->changed_at ?? $row->collected_at)?->timestamp ?? 0)
      ->first();
  };
  $valueCurrentValue = static function ($variant) {
    if (!$variant) return null;
    $moonCv = $variant->currentValues->first(fn($cv) => optional($cv->source)->slug === 'moonvalues');
    $fandomExistCv = $variant->currentValues->first(fn($cv) => optional($cv->source)->slug === 'fandom-exist-counts');
    $galleryExistCv = $variant->currentValues->first(fn($cv) => optional($cv->source)->slug === 'fandom-exist-count-gallery');
    $fandomCv = $variant->currentValues->first(fn($cv) => optional($cv->source)->slug === 'fandom');
    $eldoCv = $variant->currentValues->first(fn($cv) => optional($cv->source)->slug === 'eldorado');

    return [
      'exist' => ($eldoCv ?? $fandomExistCv ?? $galleryExistCv ?? $fandomCv ?? $moonCv)?->exist_count_normalized,
      'value' => $moonCv?->value_normalized,
      'demand' => $moonCv?->demand,
    ];
  };
  $mutationByName = $mutationVariants->keyBy(fn ($variant) => $variantNameKey($variant->mutation_name ?: $variant->variant_name));
  $usedMutationKeys = [];
  $variantRows = collect();
  if ($baseVariant = $baseVariants->firstWhere('variant_key', 'base')) {
    $variantRows->push([
      'name' => 'Normal',
      'exist' => $item->total_exists,
      'percentage' => null,
      'value' => null,
      'demand' => null,
      'sort_value' => null,
    ]);
  }
  foreach ($valueVariants as $valueVariant) {
    $name = (string) $valueVariant->variant_name;
    $key = $variantNameKey($name);
    $mutationVariant = $mutationByName->get($key);
    $mutationCv = $latestCurrentValue($mutationVariant);
    $valueCv = $valueCurrentValue($valueVariant);
    if ($mutationVariant) {
      $usedMutationKeys[$key] = true;
    }
    $variantRows->push([
      'name' => $name,
      'exist' => $mutationCv?->exist_count_normalized ?? $valueCv['exist'],
      'percentage' => $mutationVariant?->exist_percentage,
      'value' => $valueCv['value'],
      'demand' => $valueCv['demand'],
      'sort_value' => $valueCv['value'],
    ]);
  }
  foreach ($mutationVariants as $mutationVariant) {
    $name = (string) ($mutationVariant->mutation_name ?: $mutationVariant->variant_name);
    $key = $variantNameKey($name);
    if (isset($usedMutationKeys[$key])) continue;
    $mutationCv = $latestCurrentValue($mutationVariant);
    $variantRows->push([
      'name' => $name,
      'exist' => $mutationCv?->exist_count_normalized,
      'percentage' => $mutationVariant->exist_percentage,
      'value' => null,
      'demand' => null,
      'sort_value' => null,
    ]);
  }
  $colorMap = [
    'Rainbow'     => 'linear-gradient(90deg,#f87171,#fb923c,#facc15,#4ade80,#60a5fa,#c084fc)',
    'Radioactive' => '#a3e635',
    'Yin Yang'    => '#e2e8f0',
    'Diamond'     => '#67e8f9',
    'Galaxy'      => '#818cf8',
    'Candy'       => '#ec4899',
    'Lava'        => '#f97316',
    'Gold'        => '#f59e0b',
    'Normal'      => '#94a3b8',
  ];
  $variantColor = static function ($name) use ($colorMap) {
    foreach ($colorMap as $kw => $clr) {
      if (stripos((string) $name, $kw) !== false) return $clr;
    }
    return null;
  };
  $localDescription = trim((string) ($description ?? ''));
  $rotRocksDescriptionHtml = trim((string) ($rotRocksDescriptionHtml ?? ''));
  $hasAboutContent = $localDescription !== '' || $rotRocksDescriptionHtml !== '';
  $wikiTriviaDetail = is_array($itemAttrs['wiki_trivia'] ?? null) ? $itemAttrs['wiki_trivia'] : [];
  $vercelDetail = is_array($itemAttrs['vercel_detail'] ?? null) ? $itemAttrs['vercel_detail'] : [];
  $triviaItems = is_array($wikiTriviaDetail['trivia'] ?? null)
    ? $wikiTriviaDetail['trivia']
    : (is_array($vercelDetail['trivia'] ?? null) ? $vercelDetail['trivia'] : []);
  $wikiTrivia = collect($triviaItems)
    ->map(fn ($entry) => trim((string) $entry))
    ->filter()
    ->values()
    ->take(3);
  $hasWikiTrivia = $wikiTrivia->isNotEmpty();
@endphp

{{-- Breadcrumb --}}
<nav class="mt-4 text-sm text-slate-400 mb-4">
  @php $sabBreadcrumbHomeHref = (($urlPrefix ?? '') === '') ? '/' : $urlPrefix; @endphp
  <a href="{{ $sabBreadcrumbHomeHref }}" class="hover:text-cyan-300">{{ $t['breadcrumb_home'] }}</a>
  <span class="mx-2 text-slate-600">›</span>
  <span class="text-slate-200">{{ $displayName }} {{ $t['item_page_exist_count_label'] }}</span>
</nav>

{{-- Page anchors --}}
<div class="sticky z-40 -mx-4 mb-6 border-b border-white/10 bg-slate-950/90 backdrop-blur" style="top:var(--sab-sticky-anchor-top,64px)" data-section-nav-shell>
  <nav class="sab-section-nav flex flex-nowrap gap-2 overflow-x-auto px-4 py-2 text-sm font-bold text-slate-400 md:flex-wrap md:gap-3 md:overflow-visible" aria-label="Product page sections" data-section-nav>
    <a href="#exist-count" class="rounded-md border-b border-cyan-300 px-3 py-2 leading-none text-cyan-300 hover:text-cyan-300" data-section-link="exist-count" aria-current="true">{{ $t['exist_count'] }}</a>
    @if($variantRows->isNotEmpty())
    <a href="#variants" class="rounded-md border-b border-transparent px-3 py-2 leading-none hover:text-cyan-300" data-section-link="variants">{{ $t['variants'] }}</a>
    @endif
    @if($traitVariants->isNotEmpty())
    <a href="#traits" class="rounded-md border-b border-transparent px-3 py-2 leading-none hover:text-cyan-300" data-section-link="traits">Traits</a>
    @endif
    @if($hasWikiTrivia)
    <a href="#trivia" class="rounded-md border-b border-transparent px-3 py-2 leading-none hover:text-cyan-300" data-section-link="trivia">Trivia</a>
    @endif
    @if($galleryImages->isNotEmpty())
    <a href="#gallery" class="rounded-md border-b border-transparent px-3 py-2 leading-none hover:text-cyan-300" data-section-link="gallery">Gallery</a>
    @endif
    <a href="#faq" class="rounded-md border-b border-transparent px-3 py-2 leading-none hover:text-cyan-300" data-section-link="faq">FAQ</a>
    @if($hasAboutContent)
    <a href="#about" class="rounded-md border-b border-transparent px-3 py-2 leading-none hover:text-cyan-300" data-section-link="about">About</a>
    @endif
  </nav>
</div>

{{-- Single preferred-source snapshot for base variant --}}
@php
  $imgSrc = \App\Services\Seo\SabRenderService::listingImageSrc($item);
  $baseVariant = $baseVariants->firstWhere('variant_key', 'base') ?? $displayVariants->firstWhere('variant_key', 'base') ?? $displayVariants->first();
  $bestCv = null;
  $baseValueCv = null;
  if ($baseVariant) {
    $bestCv = $baseVariant->currentValues->sortByDesc(fn ($cv) => ($cv->changed_at ?? $cv->collected_at)?->timestamp ?? 0)->first();
    $valueCurrentValues = $baseVariant->currentValues
      ->filter(fn ($cv) => $cv->value_normalized !== null || trim((string) $cv->demand) !== '');
    $baseValueCv = $valueCurrentValues->first(
      fn ($cv) => optional($cv->source)->slug === \App\Services\Seo\SabRotCalculatorSyncService::SOURCE_SLUG
    ) ?? $valueCurrentValues
      ->sortByDesc(fn ($cv) => ($cv->changed_at ?? $cv->collected_at)?->timestamp ?? 0)
      ->first();
  }
  $totalExists = $item->total_exists;
  $existDisplay = \App\Services\Seo\SabRenderService::resolveExistCountDisplay($item, $totalExists !== null ? (int) $totalExists : null);
  $rarestMutationName = trim((string) ($item->rarest_mutation_name ?? ''));
  $rarestMutationCount = $item->rarest_mutation_count;
  $rarestTraitName = trim((string) ($item->rarest_trait_name ?? ''));
  $rarestTraitCount = $item->rarest_trait_count;
  $hasSnapshot = $totalExists !== null || $existDisplay['kind'] === 'estimated' || $rarestMutationName !== '' || $rarestTraitName !== '' || ($baseValueCv && ($baseValueCv->value_normalized !== null || $baseValueCv->demand)) || ($bestCv && $bestCv->changed_at) || $item->avg_coins_raw || \App\Services\Seo\SabRenderService::parseItemCoinsValue($item) !== null;
  $rarityKey = \App\Services\Seo\SabRenderService::canonicalRarityKey($item->rarity ?? null);
  $rarityLabel = $rarityKey !== '' ? \App\Services\Seo\SabRenderService::canonicalRarityLabel($rarityKey) : '';
  $rarityLower = $rarityKey;
  $purchaseUrl = trim((string) ($item->purchase_url ?? ''));
  $itemSummary = trim((string) ($item->summary ?? ''));
  $avgCoinsRaw = trim((string) ($item->avg_coins_raw ?? ''));
  $rotRocksAttrs = is_array($itemAttrs['rot_rocks'] ?? null) ? $itemAttrs['rot_rocks'] : [];
  $rotRocksDemand = trim((string) ($rotRocksAttrs['demand'] ?? ''));
  $rotRocksTrend = trim((string) ($rotRocksAttrs['trend'] ?? ''));
  $mutationPriceData = is_array($mutationPriceData ?? null) ? $mutationPriceData : null;
  $showMutationPriceSection = $mutationPriceData !== null;
  $initialMutation = $showMutationPriceSection ? ($mutationPriceData['mutations'][0] ?? []) : [];
  $extraRobux = is_array($mutationPriceData) ? ($mutationPriceData['baseRobuxValue'] ?? ($initialMutation['robuxValue'] ?? null)) : null;
  if ($extraRobux === null && is_array($calculatorBrainrot ?? null)) {
    $extraRobux = $calculatorBrainrot['robuxValue'] ?? null;
  }
  $valueDisplay = \App\Services\Seo\SabRenderService::itemValueDisplay($item, $baseValueCv, $extraRobux);
  $demandDisplay = \App\Services\Seo\SabRenderService::itemDemandDisplay($item, $baseValueCv);
  $calculatorItemHref = rtrim($urlPrefix ?? '', '/') . '/' . \App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR
      . '?item=' . rawurlencode((string) $item->slug);
  $hasSnapshot = $hasSnapshot || $valueDisplay['kind'] !== 'calculator' || $demandDisplay !== '';
  $priceHistorySource = $showMutationPriceSection
    ? ($initialMutation['priceHistory'] ?? [])
    : ($priceHistory ?? []);
  $priceHistoryRows = collect($priceHistorySource)->filter(fn ($row) => is_array($row) && isset($row['value']))->values();
  $pricePointCount = $priceHistoryRows->count();
  $priceHistoryFirst = $pricePointCount > 0 ? $priceHistoryRows->first() : null;
  $priceHistoryLast = $pricePointCount > 0 ? $priceHistoryRows->last() : null;
  $priceHistoryLatestValue = $priceHistoryLast
    ? (float) ($priceHistoryLast['value'] ?? 0)
    : ($showMutationPriceSection ? ($initialMutation['robuxValue'] ?? null) : null);
  $priceHistoryFirstValue = $priceHistoryFirst ? (float) ($priceHistoryFirst['value'] ?? 0) : null;
  $priceHistoryChange = ($pricePointCount >= 2 && $priceHistoryFirstValue > 0)
    ? (($priceHistoryLatestValue - $priceHistoryFirstValue) / $priceHistoryFirstValue) * 100
    : null;
  $priceHistoryDate = $priceHistoryLast['date'] ?? null;
  $badgeClass = match(true) {
    in_array($rarityLower, ['legendary','secret']) => 'bg-pink-500/20 text-pink-400 border border-pink-500/30',
    $rarityLower === 'rare'     => 'bg-purple-500/20 text-purple-400 border border-purple-500/30',
    $rarityLower === 'uncommon' => 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30',
    default                    => 'bg-slate-700/50 text-slate-400 border border-slate-600/30',
  };
  $hasValueSignal = $valueDisplay['kind'] !== 'calculator' || $demandDisplay !== '';
  if ($totalExists !== null) {
    $itemHeroIntro = $displayName . ' has ' . number_format((int) $totalExists) . ' known copies in Steal a Brainrot. Use this page to compare rarity, value signals, mutations, and traits.';
  } elseif (($existDisplay['kind'] ?? '') === 'estimated') {
    $itemHeroIntro = $displayName . ' does not have a confirmed official exist count yet. This page shows the current guess range, rarity context, and variants.';
  } else {
    $itemHeroIntro = $displayName . ' is tracked for Steal a Brainrot, but its confirmed exist count is not available yet. Check rarity, variants, images, and future updates here.';
  }
  $rarityValueSentences = [];
  if ($rarityLabel !== '') {
    $rarityValueSentences[] = $displayName . ' is listed as ' . $rarityLabel . ' rarity in Steal a Brainrot.';
  }
  if ($totalExists !== null) {
    $rarityValueSentences[] = 'Its current known exist count is ' . number_format((int) $totalExists) . ', which is the main supply signal for this item.';
  } elseif (($existDisplay['kind'] ?? '') === 'estimated') {
    $rarityValueSentences[] = 'The official exist count is not known yet, so the current guess range is used as a temporary supply signal.';
  }
  if (in_array($valueDisplay['kind'], ['robux', 'coins'], true) && $valueDisplay['amount'] !== null) {
    $rarityValueSentences[] = 'The latest tracked value signal is around ' . number_format((float) $valueDisplay['amount']) . ' ' . $valueDisplay['unit'] . '.';
  }
  if ($demandDisplay !== '') {
    $rarityValueSentences[] = 'Current demand is marked as ' . $demandDisplay . '.';
  }
  $mutationTraitSentences = [];
  if ($rarestMutationName !== '') {
    $mutationText = 'The rarest known mutation is ' . $rarestMutationName;
    if ($rarestMutationCount !== null) {
      $mutationText .= ' with ' . number_format((int) $rarestMutationCount) . ' tracked copies';
    }
    $mutationTraitSentences[] = $mutationText . '.';
  } elseif($mutationVariants->isNotEmpty()) {
    $mutationTraitSentences[] = 'This page tracks ' . number_format($mutationVariants->count()) . ' mutation option(s) for ' . $displayName . '.';
  }
  $faqTraitNameForSeo = ($rarestTraitName !== '' && !preg_match('/^(n\/a|na|none|null|—|-)$/iu', $rarestTraitName))
    ? $rarestTraitName
    : '';
  if ($faqTraitNameForSeo !== '') {
    $traitText = 'The rarest known trait is ' . $faqTraitNameForSeo;
    if ($rarestTraitCount !== null) {
      $traitText .= ' with ' . number_format((int) $rarestTraitCount) . ' tracked copies';
    }
    $mutationTraitSentences[] = $traitText . '.';
  } elseif($traitVariants->isNotEmpty()) {
    $mutationTraitSentences[] = 'This page tracks ' . number_format($traitVariants->count()) . ' trait option(s) for ' . $displayName . '.';
  }
  $tradeSentences = [];
  if (in_array($rarityKey, ['mythic', 'secret', 'brainrot god'], true)) {
    $tradeSentences[] = $displayName . ' can be worth watching for trades because its rarity tier is one of the strongest item signals.';
  } elseif($rarityLabel !== '') {
    $tradeSentences[] = $displayName . ' trade interest depends on its ' . $rarityLabel . ' rarity, current supply, demand, and variant data.';
  }
  if ($totalExists !== null && $totalExists < 5000) {
    $tradeSentences[] = 'The low known exist count may make rare variants more attractive to collectors.';
  }
  if ($hasValueSignal) {
    $tradeSentences[] = 'Use the value and demand signals on this page as context before comparing recent community trades.';
  }
  $availabilityTradeSentences = [];
  if ($itemSummary !== '') {
    $availabilityTradeSentences[] = $itemSummary;
  }
  if ($purchaseUrl !== '') {
    $availabilityTradeSentences[] = 'A source link is stored for checking the current acquisition or market context before making a trade decision.';
  } else {
    $availabilityTradeSentences[] = 'No confirmed acquisition method is stored yet, so this page avoids guessing how to obtain ' . $displayName . '.';
  }
  if ($rarityLabel !== '') {
    $availabilityTradeSentences[] = $rarityLabel . ' rarity is one of the main signals to check before judging whether ' . $displayName . ' is common, collectible, or trade-sensitive.';
  }
  if (($existDisplay['kind'] ?? '') === 'estimated') {
    $availabilityTradeSentences[] = 'The current exist count is a guess range, not a confirmed official count, so treat the supply signal as directional.';
  }
  if ($mutationVariants->isNotEmpty()) {
    $availabilityTradeSentences[] = 'Mutation data is available, so compare variant supply instead of judging only the base item.';
  }
  if ($rotRocksDemand !== '' || $rotRocksTrend !== '') {
    $parts = [];
    if ($rotRocksDemand !== '') {
      $parts[] = 'demand ' . $rotRocksDemand;
    }
    if ($rotRocksTrend !== '') {
      $parts[] = 'trend ' . $rotRocksTrend;
    }
    $availabilityTradeSentences[] = 'rot.rocks currently records ' . implode(' and ', $parts) . ' for this item.';
  }
  if ($avgCoinsRaw !== '') {
    $availabilityTradeSentences[] = 'The base income signal is ' . $avgCoinsRaw . ', which can matter when players compare collecting value against trading value.';
  }
  $hasSeoInsights = count($rarityValueSentences) > 0 || count($mutationTraitSentences) > 0 || count($tradeSentences) > 0 || count($availabilityTradeSentences) > 0;
  $hasAboutContent = $hasAboutContent || $hasSeoInsights;
@endphp

<style>
  #variants-more-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 0;
    border-top: 1px solid rgba(30, 41, 59, 0.9);
    text-align: center;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
  }
  #variants-more-btn {
    display: block;
    margin: 0 auto;
    text-align: center;
    text-transform: none;
  }
  @media (min-width: 640px) {
    #variants-more-btn {
      font-size: 0.875rem;
    }
  }
  .sab-variant-target:target {
    background: rgba(34, 211, 238, 0.12);
    outline: 1px solid rgba(34, 211, 238, 0.45);
    outline-offset: -1px;
  }
  .sab-section-nav {
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
  }
  .sab-section-nav::-webkit-scrollbar {
    display: none;
  }
  .sab-section-nav > a {
    flex-shrink: 0;
  }
  .sab-snapshot-mobile {
    display: none;
  }
  .sab-snapshot-compact {
    display: block;
  }
  .sab-snapshot-compact-row {
    display: grid;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }
  .sab-snapshot-compact-row:last-child {
    border-bottom: 0;
  }
  .sab-snapshot-compact-row--stats {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  .sab-snapshot-compact-row--meta {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .sab-snapshot-compact-cell {
    min-width: 0;
    padding: 0.625rem 0.75rem;
  }
  .sab-snapshot-compact-cell + .sab-snapshot-compact-cell {
    border-left: 1px solid rgba(255, 255, 255, 0.1);
  }
  .sab-snapshot-compact-label {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: rgb(100, 116, 139);
  }
  .sab-snapshot-value-demand-md {
    display: none;
  }
  .sab-snapshot-card {
    width: 100%;
  }
  .sab-snapshot-table {
    display: none;
  }
  .sab-item-hero {
    display: grid;
    grid-template-columns: 4.5rem minmax(0, 1fr);
    gap: 0.75rem;
    align-items: center;
  }
  .sab-item-hero-image {
    width: 4.5rem;
    height: 4.5rem;
    align-self: stretch;
  }
  .sab-item-hero-title {
    font-size: 1.125rem;
    line-height: 1.2;
    letter-spacing: 0;
  }
  .sab-item-hero-intro {
    display: none;
  }
  .sab-item-hero-tags {
    min-height: 1.5rem;
  }
  .sab-market-link {
    position: relative;
    display: inline-flex;
    width: fit-content;
    max-width: 100%;
    color: rgb(103, 232, 249);
  }
  .sab-market-link:hover,
  .sab-market-link:focus-visible {
    color: rgb(165, 243, 252);
    text-decoration: underline;
  }
  .sab-market-tooltip {
    position: absolute;
    z-index: 50;
    left: 0;
    top: calc(100% + 0.375rem);
    width: min(18rem, calc(100vw - 2rem));
    max-width: 18rem;
    border: 1px solid rgba(34, 211, 238, 0.22);
    border-radius: 0.5rem;
    background: rgba(15, 23, 42, 0.98);
    box-shadow: 0 12px 30px rgba(2, 6, 23, 0.35);
    color: rgb(148, 163, 184);
    opacity: 0;
    padding: 0.625rem 0.75rem;
    pointer-events: none;
    text-decoration: none;
    transform: translateY(-0.25rem);
    transition: opacity 120ms ease, transform 120ms ease;
  }
  .sab-market-link:hover .sab-market-tooltip,
  .sab-market-link:focus-visible .sab-market-tooltip {
    opacity: 1;
    transform: translateY(0);
  }
  @media (min-width: 768px) {
    .sab-snapshot-compact {
      display: none;
    }
    .sab-snapshot-value-demand-md {
      display: block;
    }
    .sab-snapshot-card {
      width: auto;
    }
    .sab-snapshot-table {
      display: table;
    }
    .sab-item-hero {
      grid-template-columns: 7rem minmax(0, 1fr);
      gap: 1rem;
    }
    .sab-item-hero-image {
      width: 7rem;
      height: 7rem;
    }
    .sab-item-hero-title {
      font-size: 1.5rem;
    }
    .sab-item-hero-intro {
      display: block;
    }
  }
  .sab-item-price-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
  }
  .sab-item-price-stat-label {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: rgb(100, 116, 139);
  }
  .sab-item-mutation-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
    margin-bottom: 1rem;
  }
  .sab-item-mut-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.65rem;
    border: 1px solid rgba(148, 163, 184, 0.25);
    border-radius: 0.5rem;
    background: rgba(15, 23, 42, 0.6);
    color: #94a3b8;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: border-color 120ms, background 120ms, color 120ms;
  }
  .sab-item-mut-tab img {
    width: 1rem;
    height: 1rem;
    object-fit: contain;
  }
  .sab-item-mut-tab:hover {
    color: #e2e8f0;
    border-color: rgba(148, 163, 184, 0.45);
  }
  .sab-item-mut-tab.is-active {
    border-color: rgba(34, 197, 94, 0.7);
    background: rgba(6, 78, 59, 0.22);
    color: #4ade80;
  }
  .sab-item-chart-empty {
    display: none;
    padding: 1.5rem 0;
    text-align: center;
    font-size: 0.875rem;
    color: rgb(100, 116, 139);
  }
</style>

{{-- Hero and current snapshot --}}
<section id="exist-count" class="mb-8 scroll-mt-32" style="scroll-margin-top:8rem">
  <div class="sab-item-hero min-w-0">
    <div class="sab-item-hero-image flex shrink-0 items-center justify-center rounded-xl border border-white/10 bg-slate-800/70 p-2">
      @if($imgSrc)
      <img src="{{ $imgSrc }}" alt="{{ $item->name }}"
           width="112" height="112"
           class="h-full w-full rounded-lg object-cover" />
      @else
        <div class="flex h-full w-full items-center justify-center rounded-lg bg-slate-900 text-center text-[10px] font-semibold uppercase tracking-wide text-slate-500">
          No Image
        </div>
      @endif
    </div>
    <div class="sab-item-hero-meta min-w-0">
      <h1 class="sab-item-hero-title font-black text-slate-100">
        {{ $displayName }} <span class="text-cyan-400">{{ $t['item_page_exist_count_label'] }}</span>
      </h1>
      <div class="sab-item-hero-tags mt-2 flex flex-wrap items-center gap-2">
        @if($rarityLabel !== '')
        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase {{ $badgeClass }}">{{ $rarityLabel }}</span>
        @endif
      </div>
      @if(!empty($calculatorBrainrot))
      @php
        $calculatorAddUrl = rtrim($urlPrefix ?? '', '/') . '/' . \App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR
          . '?item=' . rawurlencode((string) $item->slug);
      @endphp
      <a
        href="{{ $calculatorAddUrl }}"
        class="mt-3 inline-flex items-center gap-2 rounded-xl border border-cyan-400/40 bg-cyan-400/15 px-4 py-2.5 text-sm font-bold text-cyan-100 transition-colors hover:border-cyan-300/60 hover:bg-cyan-400/20 hover:text-white"
      >
        <span>Add To SAB Calculator</span>
        <span class="text-cyan-300" aria-hidden="true">→</span>
      </a>
      @endif
      <p class="sab-item-hero-intro mt-3 max-w-3xl text-sm leading-6 text-slate-300 md:text-base md:leading-7" aria-hidden="true">{{ $itemHeroIntro }}</p>
    </div>
  </div>

  <div class="mt-3 overflow-visible md:mt-4 md:overflow-x-auto">
    <div class="flex min-w-0 flex-col items-start gap-4 md:min-w-max md:flex-row md:items-center">
      @if($hasSnapshot)
      @if($totalExists !== null || $existDisplay['kind'] === 'estimated' || $rarestMutationName !== '' || $rarestTraitName !== '' || $valueDisplay['kind'] !== 'calculator' || $demandDisplay !== '')
      <div class="sab-snapshot-card shrink-0 overflow-hidden rounded-xl border border-white/10 bg-slate-900">
        <div class="sab-snapshot-compact text-sm">
          <div class="sab-snapshot-compact-row sab-snapshot-compact-row--stats">
            <div class="sab-snapshot-compact-cell">
              <div class="flex flex-wrap items-center gap-1">
                <div class="sab-snapshot-compact-label">Exist Count</div>
                @if($existDisplay['label'])
                <span class="rounded px-1 py-0.5 text-[9px] font-bold uppercase tracking-wider bg-yellow-400/10 text-yellow-300">{{ $existDisplay['label'] }}</span>
                @endif
              </div>
              @if($totalExists !== null)
              <div class="mt-0.5 text-xl font-black leading-none text-cyan-400">{{ number_format($totalExists) }}</div>
              @elseif($existDisplay['kind'] === 'estimated')
              <div class="mt-0.5 text-sm font-black leading-tight text-slate-400">Not Known</div>
              <div class="mt-0.5 text-[10px] font-semibold text-cyan-300">{{ $existDisplay['primary'] }}</div>
              @else
              <div class="mt-0.5 text-xl font-black leading-none text-slate-600">—</div>
              @endif
              @if($purchaseUrl !== '')
              <a href="{{ $purchaseUrl }}" class="sab-market-link mt-1 text-[10px] font-bold leading-tight" target="_blank" rel="nofollow sponsored noreferrer">
                <span>Market</span>
                <span class="sab-market-tooltip text-xs font-medium leading-relaxed">{{ $displayName }} may have different variants and stock status. Check the product page for current market availability.</span>
              </a>
              @endif
            </div>
            <div class="sab-snapshot-compact-cell">
              <div class="sab-snapshot-compact-label">{{ $t['value'] }}</div>
              @if(in_array($valueDisplay['kind'], ['robux', 'coins'], true) && $valueDisplay['amount'] !== null)
              <div class="mt-0.5 text-base font-bold leading-tight text-purple-400">{{ number_format($valueDisplay['amount']) }} <span class="text-[10px]">{{ $valueDisplay['unit'] }}</span></div>
              @else
              <a href="{{ $calculatorItemHref }}" class="mt-0.5 inline-block text-[11px] font-bold leading-tight text-cyan-300 hover:text-cyan-200">Compare in calculator</a>
              @endif
            </div>
            <div class="sab-snapshot-compact-cell">
              <div class="sab-snapshot-compact-label">{{ $t['demand'] }}</div>
              @if($demandDisplay !== '')
              <div class="mt-0.5 text-base font-bold leading-tight text-slate-100">{{ $demandDisplay }}</div>
              @else
              <div class="mt-0.5 text-base font-bold leading-tight text-slate-600">—</div>
              @endif
            </div>
          </div>
          <div class="sab-snapshot-compact-row sab-snapshot-compact-row--meta">
            <div class="sab-snapshot-compact-cell">
              <div class="sab-snapshot-compact-label">Rarest Mutation</div>
              <div class="mt-0.5 text-sm font-semibold leading-snug text-slate-100">
                @if($rarestMutationName !== '')
                <a href="#mutation-{{ $variantAnchorKey($rarestMutationName) }}" class="hover:text-cyan-300">
                  {{ $rarestMutationName }}@if($rarestMutationCount !== null) <span class="font-bold tabular-nums text-cyan-300">{{ number_format($rarestMutationCount) }}</span>@endif
                </a>
                @else
                —
                @endif
              </div>
            </div>
            <div class="sab-snapshot-compact-cell">
              <div class="sab-snapshot-compact-label">Rarest Trait</div>
              <div class="mt-0.5 text-sm font-semibold leading-snug text-slate-100">
                @if($rarestTraitName !== '')
                <a href="#trait-{{ $variantAnchorKey($rarestTraitName) }}" class="hover:text-cyan-300">
                  {{ $rarestTraitName }}@if($rarestTraitCount !== null) <span class="font-bold tabular-nums text-cyan-300">{{ number_format($rarestTraitCount) }}</span>@endif
                </a>
                @else
                —
                @endif
              </div>
            </div>
          </div>
        </div>
        <div class="sab-snapshot-mobile divide-y divide-white/10 text-sm">
          <div class="px-4 py-3">
            <div class="flex items-center gap-2">
              <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Exist Count</div>
              @if($existDisplay['label'])
              <span class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-yellow-400/10 text-yellow-300">{{ $existDisplay['label'] }}</span>
              @endif
            </div>
            @if($totalExists !== null)
            <div class="mt-1 text-2xl font-black leading-none text-cyan-400">{{ number_format($totalExists) }}</div>
            @elseif($existDisplay['kind'] === 'estimated')
            <div class="mt-1 text-lg font-black leading-none text-slate-400">Not Known</div>
            <div class="mt-1 text-xs font-semibold text-slate-400">Official exists is Not Known.</div>
            <div class="mt-1 text-xs font-semibold text-cyan-300">Guess range: {{ $existDisplay['primary'] }}</div>
            @if($existDisplay['reason'] !== '')
            <div class="mt-1 text-xs italic text-slate-500">{{ $existDisplay['reason'] }}</div>
            @endif
            @else
            <div class="mt-1 text-2xl font-black leading-none text-slate-600">—</div>
            @endif
            @if($purchaseUrl !== '')
            <a href="{{ $purchaseUrl }}" class="sab-market-link mt-2 text-xs font-bold" target="_blank" rel="nofollow sponsored noreferrer">
              <span>Check Market Availability</span>
              <span class="sab-market-tooltip text-xs font-medium leading-relaxed">{{ $displayName }} may have different variants and stock status. Check the product page for current market availability.</span>
            </a>
            @endif
          </div>
          <div class="px-4 py-3">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Rarest Mutation</div>
            <div class="mt-1 font-semibold text-slate-100">
              @if($rarestMutationName !== '')
              <a href="#mutation-{{ $variantAnchorKey($rarestMutationName) }}" class="hover:text-cyan-300">
                {{ $rarestMutationName }}@if($rarestMutationCount !== null) <span class="font-bold tabular-nums text-cyan-300">{{ number_format($rarestMutationCount) }}</span>@endif
              </a>
              @else
              —
              @endif
            </div>
          </div>
          <div class="px-4 py-3">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Rarest Trait</div>
            <div class="mt-1 font-semibold text-slate-100">
              @if($rarestTraitName !== '')
              <a href="#trait-{{ $variantAnchorKey($rarestTraitName) }}" class="hover:text-cyan-300">
                {{ $rarestTraitName }}@if($rarestTraitCount !== null) <span class="font-bold tabular-nums text-cyan-300">{{ number_format($rarestTraitCount) }}</span>@endif
              </a>
              @else
              —
              @endif
            </div>
          </div>
        </div>
        <table class="sab-snapshot-table min-w-[560px] text-sm">
          <thead class="bg-white/[0.03]">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Exist Count</th>
              <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Rarest Mutation</th>
              <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Rarest Trait</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="px-4 py-3">
                @if($totalExists !== null)
                <div class="text-2xl font-black leading-none text-cyan-400">{{ number_format($totalExists) }}</div>
                @elseif($existDisplay['kind'] === 'estimated')
                <div class="flex items-center gap-1.5">
                  <div class="text-lg font-black leading-none text-slate-400">Not Known</div>
                  <span class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-yellow-400/10 text-yellow-300">{{ $existDisplay['label'] }}</span>
                </div>
                <div class="mt-1 text-xs font-semibold text-slate-400">Official exists is Not Known.</div>
                <div class="mt-1 text-xs font-semibold text-cyan-300">Guess range: {{ $existDisplay['primary'] }}</div>
                @if($existDisplay['reason'] !== '')
                <div class="mt-1 text-xs italic text-slate-500">{{ $existDisplay['reason'] }}</div>
                @endif
                @else
                <div class="text-2xl font-black leading-none text-slate-600">—</div>
                @endif
                @if($purchaseUrl !== '')
                <a href="{{ $purchaseUrl }}" class="sab-market-link mt-2 text-xs font-bold" target="_blank" rel="nofollow sponsored noreferrer">
                  <span>Check Market Availability</span>
                  <span class="sab-market-tooltip text-xs font-medium leading-relaxed">{{ $displayName }} may have different variants and stock status. Check the product page for current market availability.</span>
                </a>
                @endif
              </td>
              <td class="px-4 py-3 font-semibold text-slate-100">
                @if($rarestMutationName !== '')
                <a href="#mutation-{{ $variantAnchorKey($rarestMutationName) }}" class="hover:text-cyan-300">
                  {{ $rarestMutationName }}@if($rarestMutationCount !== null) <span class="font-bold tabular-nums text-cyan-300">{{ number_format($rarestMutationCount) }}</span>@endif
                </a>
                @else
                —
                @endif
              </td>
              <td class="px-4 py-3 font-semibold text-slate-100">
                @if($rarestTraitName !== '')
                <a href="#trait-{{ $variantAnchorKey($rarestTraitName) }}" class="hover:text-cyan-300">
                  {{ $rarestTraitName }}@if($rarestTraitCount !== null) <span class="font-bold tabular-nums text-cyan-300">{{ number_format($rarestTraitCount) }}</span>@endif
                </a>
                @else
                —
                @endif
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      @endif
    @if(in_array($valueDisplay['kind'], ['robux', 'coins'], true) && $valueDisplay['amount'] !== null)
      <div class="sab-snapshot-value-demand-md shrink-0">
        <div class="text-lg font-bold leading-none text-purple-400 md:text-xl">{{ number_format($valueDisplay['amount']) }} {{ $valueDisplay['unit'] }}</div>
        <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t['value'] }}</div>
      </div>
    @elseif($valueDisplay['kind'] === 'calculator')
      <div class="sab-snapshot-value-demand-md shrink-0">
        <a href="{{ $calculatorItemHref }}" class="text-sm font-bold leading-tight text-cyan-300 hover:text-cyan-200">Compare in calculator</a>
        <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t['value'] }}</div>
      </div>
    @endif
    @if($demandDisplay !== '')
      <div class="sab-snapshot-value-demand-md shrink-0">
        <div class="text-lg font-bold leading-none text-slate-100 md:text-xl">{{ $demandDisplay }}</div>
        <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t['demand'] }}</div>
      </div>
    @endif
      @else
      <p class="shrink-0 text-sm text-slate-500">No exist count data available.</p>
      @endif
    </div>
  </div>
</section>

@if($showMutationPriceSection || $priceHistoryLatestValue !== null)
<section id="price-history" class="mb-8 scroll-mt-32" style="scroll-margin-top:8rem">
  <div class="rounded-2xl border border-white/10 bg-slate-900/80 p-5 shadow-[0_20px_60px_rgba(0,0,0,.22)]">
    @if($showMutationPriceSection)
    @php
      $mpdBaseRobux = $mutationPriceData['baseRobuxValue'] ?? null;
      $mpdBaseIncome = (float) ($mutationPriceData['baseIncome'] ?? 0);
      $mpdInitRobux = $initialMutation['robuxValue'] ?? $mpdBaseRobux;
      $mpdMutations = $mutationPriceData['mutations'] ?? [];
    @endphp
    <div class="sab-item-price-stats">
      <div>
        <div class="sab-item-price-stat-label">Value</div>
        <div id="sabItemBaseValue" class="mt-1 text-base font-bold text-slate-100">
          @if($mpdBaseRobux !== null)
          ${{ number_format($mpdBaseRobux, $mpdBaseRobux >= 1000 ? 0 : 2) }}
          @else
          —
          @endif
        </div>
      </div>
      <div>
        <div class="sab-item-price-stat-label">Income/s</div>
        <div id="sabItemBaseIncome" class="mt-1 text-base font-bold text-emerald-400" data-income="{{ $mpdBaseIncome }}">
          @if($mpdBaseIncome > 0)
          @php
            $incomeFmt = $mpdBaseIncome >= 1e9
              ? '$' . round($mpdBaseIncome / 1e9, 1) . 'B/s'
              : ($mpdBaseIncome >= 1e6
                ? '$' . round($mpdBaseIncome / 1e6, 1) . 'M/s'
                : ($mpdBaseIncome >= 1e3
                  ? '$' . round($mpdBaseIncome / 1e3, 1) . 'K/s'
                  : '$' . round($mpdBaseIncome) . '/s'));
          @endphp
          {{ $incomeFmt }}
          @else
          —
          @endif
        </div>
      </div>
      <div>
        <div class="sab-item-price-stat-label">Mutation Value</div>
        <div id="sabItemMutValue" class="mt-1 text-base font-bold text-amber-300">
          @if($mpdInitRobux !== null)
          ${{ number_format($mpdInitRobux, $mpdInitRobux >= 1000 ? 0 : 2) }}
          @else
          —
          @endif
        </div>
      </div>
    </div>

    @if(count($mpdMutations) > 1)
    <div class="sab-item-mutation-tabs" id="sabItemMutTabs" role="group" aria-label="Mutations">
      @foreach($mpdMutations as $mutIdx => $mut)
      <button
        type="button"
        class="sab-item-mut-tab{{ $mutIdx === 0 ? ' is-active' : '' }}"
        data-mut-idx="{{ $mutIdx }}"
      >
        @if(!empty($mut['image']))
        <img src="{{ $mut['image'] }}" alt="" loading="lazy">
        @endif
        {{ $mut['name'] ?? 'Default' }}
      </button>
      @endforeach
    </div>
    @endif
    @endif

    <div class="mb-4 flex items-start justify-between gap-4">
      <div class="text-xs font-black uppercase tracking-[.18em] text-slate-500">30D PRICE</div>
      <div class="flex items-center gap-3">
        @if($priceHistoryChange !== null)
        <span id="sabPriceHistorySignal" class="rounded-full border px-3 py-1 text-xs font-black {{ $priceHistoryChange >= 0 ? 'border-emerald-400/40 bg-emerald-400/10 text-emerald-300' : 'border-rose-400/40 bg-rose-400/10 text-rose-300' }}">
          {{ $priceHistoryChange >= 0 ? 'High' : 'Low' }}
        </span>
        <span id="sabPriceHistoryPct" class="text-lg font-black {{ $priceHistoryChange >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
          {{ $priceHistoryChange >= 0 ? '+' : '' }}{{ number_format($priceHistoryChange, 1) }}%
        </span>
        @else
        <span id="sabPriceHistorySignal" class="rounded-full border px-3 py-1 text-xs font-black hidden"></span>
        <span id="sabPriceHistoryPct" class="text-lg font-black hidden"></span>
        @endif
      </div>
    </div>

    <canvas
      id="sabPriceHistoryChart"
      class="block h-28 w-full"
      width="720"
      height="112"
      @if($showMutationPriceSection)
      data-mutations='@json($mpdMutations)'
      @else
      data-price-history='@json($priceHistoryRows->values()->all())'
      @endif
      aria-label="{{ $displayName }} 30D price history"
    ></canvas>
    <div id="sabPriceHistoryEmpty" class="sab-item-chart-empty">No price history yet</div>

    <div class="mt-4 flex items-center justify-between gap-4 text-sm">
      <span id="sabPriceHistoryRange" class="font-semibold text-slate-500">
        @if($pricePointCount === 1 && $priceHistoryDate)
        1 price point · {{ \Illuminate\Support\Carbon::parse($priceHistoryDate)->format('m-d') }}
        @elseif($pricePointCount > 1)
        {{ $pricePointCount }} days
        @endif
      </span>
      <span id="sabPriceHistoryCurVal" class="font-black text-amber-300">
        @if($priceHistoryLatestValue !== null)
        {{ number_format($priceHistoryLatestValue, $priceHistoryLatestValue >= 1000 ? 0 : 2) }} ROBUX
        @endif
      </span>
    </div>
  </div>
</section>
@endif

@if($galleryImages->isNotEmpty())
<style>
  .item-gallery-lightbox {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(2, 6, 23, 0.94);
    padding: 1rem;
  }
  .item-gallery-lightbox[hidden] {
    display: none;
  }
  .item-gallery-lightbox-inner {
    display: flex;
    flex-direction: column;
    height: 100%;
    max-width: 72rem;
    margin: 0 auto;
  }
  .item-gallery-lightbox-media {
    flex: 1;
    min-height: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
    background: rgb(15, 23, 42);
  }
  .item-gallery-lightbox-media img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    padding: 0.75rem;
  }
</style>

<div class="item-gallery-lightbox" hidden data-item-gallery-lightbox>
  <div class="item-gallery-lightbox-inner">
    <div class="mb-3 flex items-center justify-between gap-3">
      <p class="text-sm font-semibold text-slate-100" data-item-gallery-lightbox-title></p>
      <button type="button" class="rounded-lg border border-white/10 bg-slate-900 px-3 py-2 text-sm font-semibold text-slate-200 hover:border-cyan-400/60 hover:text-cyan-200" data-item-gallery-close>
        Close
      </button>
    </div>
    <div class="item-gallery-lightbox-media">
      <img src="" alt="" data-item-gallery-lightbox-image />
    </div>
  </div>
</div>
@endif

{{-- Mutation and value options table --}}
@if($variantRows->isNotEmpty())
<section id="variants" class="mb-8 scroll-mt-32" style="scroll-margin-top:8rem">
<h2 class="text-base font-bold mb-3">Mutations</h2>
<div class="sab-home-list-card rounded-xl border border-white/10 bg-slate-900 shadow-sm shadow-black/20 overflow-hidden">
  <div class="overflow-x-auto sab-table-scroll-wrap">
  <table class="w-full text-sm" data-variants-table>
    <thead>
      <tr class="border-b border-white/10">
        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t['variant_name'] }}</th>
        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t['exist_count'] }}</th>
        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider" aria-sort="none">
          <button type="button" class="-mx-2 -my-2 inline-flex items-center gap-1.5 rounded-md bg-cyan-400/10 px-2.5 py-1.5 uppercase tracking-wider text-cyan-200 hover:bg-cyan-400/20 hover:text-cyan-100" title="Sort by value" data-variants-value-sort>
            <span>{{ $t['value'] }}</span>
            <span class="rounded bg-cyan-400/10 px-1.5 py-0.5 text-[10px] font-black tracking-normal text-cyan-200" data-variants-value-sort-icon>↕ Sort</span>
          </button>
        </th>
        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider hidden sm:table-cell">{{ $t['demand'] }}</th>
      </tr>
    </thead>
    <tbody data-variants-body>
      @foreach($variantRows as $row)
      @php
        $name = $row['name'];
        $ec = $row['exist'];
        $pct = $row['percentage'];
        $val = $row['value'];
        $demand = $row['demand'];
        $dotColor = $variantColor($name);
      @endphp
      <tr id="mutation-{{ $variantAnchorKey($name) }}" class="sab-variant-target scroll-mt-32 border-b border-white/5 hover:bg-white/5" data-value-sort="{{ $row['sort_value'] !== null ? (float) $row['sort_value'] : '' }}">
        <td class="px-4 py-3 font-medium">
          @if($dotColor)
          @if(str_starts_with($dotColor, 'linear-gradient'))
          <span style="background:{{ $dotColor }};-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">{{ $name }}</span>
          @else
          <span style="color:{{ $dotColor }}">{{ $name }}</span>
          @endif
          @else
          {{ $name }}
          @endif
        </td>
        <td class="px-4 py-3 font-mono">
          {{ $ec !== null ? number_format($ec) : '—' }}
          @if($pct !== null)<span class="ml-1 text-xs text-slate-400">({{ rtrim(rtrim(number_format((float) $pct, 1), '0'), '.') }}%)</span>@endif
        </td>
        <td class="px-4 py-3 font-mono">{{ $val !== null ? number_format($val) : '—' }}</td>
        <td class="px-4 py-3 text-slate-400 hidden sm:table-cell">{{ $demand ?: '—' }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  </div>
  <div id="variants-more-wrap" class="sab-list-table-footer bg-white/[0.02] py-3 hidden">
    <button id="variants-more-btn" type="button"
            class="bg-transparent px-2 py-1 text-xs font-semibold text-cyan-300 hover:text-cyan-200 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500/50">
      {{ $t['home_show_all'] }}
    </button>
  </div>
</div>
</section>
@endif

@if($traitVariants->isNotEmpty())
<section id="traits" class="mb-8 scroll-mt-32" style="scroll-margin-top:8rem">
<h2 class="text-base font-bold mb-3">Traits</h2>
<div class="bg-slate-900 border border-white/10 rounded-xl overflow-hidden">
  <table class="w-full text-sm">
    <thead>
      <tr class="border-b border-white/10">
        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Trait</th>
        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t['exist_count'] }}</th>
        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Percentage</th>
      </tr>
    </thead>
    <tbody>
      @foreach($traitVariants as $variant)
      @php
        $cv = $latestCurrentValue($variant);
        $ec = $cv?->exist_count_normalized;
        $pct = $variant->exist_percentage;
      @endphp
      <tr id="trait-{{ $variantAnchorKey($variant->trait_name ?: $variant->variant_name) }}" class="sab-variant-target scroll-mt-32 border-b border-white/5 hover:bg-white/5">
        <td class="px-4 py-3 font-medium text-slate-100">{{ $variant->trait_name ?: $variant->variant_name }}</td>
        <td class="px-4 py-3 font-mono">{{ $ec !== null ? number_format($ec) : '—' }}</td>
        <td class="px-4 py-3 font-mono text-slate-400">{{ $pct !== null ? rtrim(rtrim(number_format((float) $pct, 1), '0'), '.') . '%' : '—' }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
</section>
@endif

@if($hasWikiTrivia)
<section id="trivia" class="mb-8 scroll-mt-32" style="scroll-margin-top:8rem">
  <h2 class="mb-3 text-base font-bold">Trivia</h2>
  <div class="grid gap-2 text-sm leading-6 text-slate-300">
    @foreach($wikiTrivia as $trivia)
    <p class="rounded-lg border border-white/10 bg-slate-900 px-4 py-3">{{ $trivia }}</p>
    @endforeach
  </div>
</section>
@endif

@if($galleryImages->isNotEmpty())
<section id="gallery" class="mb-8 scroll-mt-32" style="scroll-margin-top:8rem">
  <h2 class="text-base font-bold mb-3">{{ $t['item_gallery_images_h2'] }}</h2>
  <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
    @foreach($galleryImages as $galleryImage)
      @php
        $gallerySrc = '/' . ltrim((string) ($galleryImage['local_image_url'] ?? ''), '/');
        $galleryName = trim((string) ($galleryImage['name'] ?? $displayName));
      @endphp
      <article class="rounded-lg border border-white/10 bg-slate-900/80 p-2">
        <button type="button"
                class="block aspect-square w-full overflow-hidden rounded-md border border-white/10 bg-slate-800"
                data-item-gallery-open
                data-name="{{ $galleryName }}"
                data-src="{{ $gallerySrc }}">
          <img src="{{ $gallerySrc }}" alt="{{ $galleryName }}" class="h-full w-full object-contain" loading="lazy" />
        </button>
        <h3 class="mt-2 text-sm font-semibold leading-snug text-slate-100">{{ $galleryName }}</h3>
      </article>
    @endforeach
  </div>
</section>
@endif

@php
  // ── avg_coins_raw helper ─────────────────────────────────────────────────
  $avgCoinsRaw   = trim((string) ($item->avg_coins_raw ?? ''));
  $avgCoinsInt   = null;
  $avgCoinsClean = str_replace([',', ' '], '', $avgCoinsRaw);
  if ($avgCoinsClean !== '' && ctype_digit($avgCoinsClean)) {
      $avgCoinsInt = (int) $avgCoinsClean;
  }
  $coinsSuffix = $avgCoinsInt !== null
      ? ' Community trading data shows an average value of around <strong>' . number_format($avgCoinsInt) . ' coins</strong>.'
      : '';

  // ── Q0: What is the [name] value? Same resolver as the snapshot card. ─
  $faqCalculatorLink = '<a href="' . e($calculatorItemHref) . '" class="text-cyan-300 hover:text-cyan-200">Add To SAB Calculator</a>';
  if ($valueDisplay['kind'] === 'robux' && $valueDisplay['amount'] !== null) {
      $faqQ0Answer = 'The current ' . e($displayName) . ' value in Steal a Brainrot is <strong>' . number_format((float) $valueDisplay['amount']) . ' ROBUX</strong>. Community ROBUX values can change with demand and mutations. Use ' . $faqCalculatorLink . ' to compare both sides before you trade.';
  } elseif ($valueDisplay['kind'] === 'coins' && $valueDisplay['amount'] !== null) {
      $faqQ0Answer = 'Community trading data shows an average value of around <strong>' . number_format((float) $valueDisplay['amount']) . ' coins</strong> for ' . e($displayName) . ' in Steal a Brainrot. Use ' . $faqCalculatorLink . ' to compare both sides before you trade.';
  } else {
      $faqQ0Answer = 'Compare the latest community value in the SAB trading calculator. Use ' . $faqCalculatorLink . ' to check live offers before you trade.';
  }

  // ── Q1: How many [name] exist? ───────────────────────────────────────────
  // Skip entirely when no exist data exists — avoids 157 identical "unknown" answers.
  $faqQ1Answer = null;
  if ($totalExists !== null) {
      $faqQ1Answer = 'There are <strong>' . number_format((int) $totalExists) . '</strong> known ' . e($displayName) . ' in Steal a Brainrot based on current tracking data.';
  } elseif (($existDisplay['kind'] ?? '') === 'estimated') {
      $faqQ1Answer = 'The exact count is not confirmed, but ' . e($displayName) . ' is estimated to exist around <strong>' . e($existDisplay['primary']) . '</strong> copies.';
  }
  // null → will be excluded from $faqPairs below

  // ── Q2: Is [name] rare? (+ coin value suffix for uniqueness) ────────────
  $faqQ2Answer = match ($rarityKey) {
      'mythic', 'secret', 'brainrot god' => e($displayName) . ' is considered <strong>extremely rare</strong> in Steal a Brainrot. Its ' . e($rarityLabel) . ' rarity tier puts it among the hardest items to find.',
      'legendary'  => e($displayName) . ' is a <strong>Legendary</strong> tier item, making it one of the rarer Brainrots in the game.',
      'epic'       => e($displayName) . ' is an <strong>Epic</strong> tier item — moderately rare and sought after by collectors.',
      'rare'       => e($displayName) . ' is classified as <strong>Rare</strong>. It is harder to obtain than Common items but more accessible than higher tiers.',
      'common'     => e($displayName) . ' is a <strong>Common</strong> tier item, meaning it is relatively easy to find compared to higher rarity Brainrots.',
      default      => ($totalExists !== null && $totalExists < 1000)
          ? 'Based on its low exist count of <strong>' . number_format((int) $totalExists) . '</strong>, ' . e($displayName) . ' appears to be quite rare.'
          : e($displayName) . '\'s rarity is not officially categorized, but you can judge based on its exist count above.',
  };
  $faqQ2Answer .= $coinsSuffix;

  /* Q3: How do you get [name]? — temporarily disabled
  $wikiUrl = trim((string) ($item->wiki_page_url ?? ''));
  if ($purchaseUrl !== '') {
      $faqQ3Answer = e($displayName) . ' can be obtained via the in-game store. A direct purchase link is listed on this page.';
  } elseif ($wikiUrl !== '') {
      $faqQ3Answer = 'Refer to the <a href="' . e($wikiUrl) . '" target="_blank" rel="nofollow noreferrer" class="text-cyan-300 hover:text-cyan-200">' . e($displayName) . ' wiki page</a> for the latest information on how to obtain it in Steal a Brainrot.';
  } else {
      $faqQ3Answer = e($displayName) . ' can be obtained through standard in-game methods in Steal a Brainrot. Check the official game wiki or community Discord for the most up-to-date acquisition details.';
  }
  */

  // ── Q4: Best mutation / trait (skip when no data) ───────────────────────
  $faqQ4Answer = null;
  if ($rarestMutationName !== '') {
      $mutCountText = $rarestMutationCount !== null
          ? ' with only <strong>' . number_format($rarestMutationCount) . '</strong> known to exist'
          : '';
      $faqQ4Answer = 'The rarest known mutation for ' . e($displayName) . ' is <strong>' . e($rarestMutationName) . '</strong>' . $mutCountText . '. Rarer mutations generally carry higher trade value.';
  } elseif ($mutationVariants->isNotEmpty()) {
      $faqQ4Answer = e($displayName) . ' has <strong>' . $mutationVariants->count() . '</strong> known mutation(s). Check the Variants table above for exist counts per mutation to identify the rarest option.';
  }
  // Append rarest trait only when value is meaningful (not N/A, none, null, —, -)
  $faqTraitName = ($rarestTraitName !== '' && !preg_match('/^(n\/a|na|none|null|—|-)$/iu', $rarestTraitName))
      ? $rarestTraitName : '';
  if ($faqTraitName !== '') {
      $traitCountText = $rarestTraitCount !== null
          ? ' (' . number_format($rarestTraitCount) . ' exist)'
          : '';
      $traitSentence = ' The rarest trait is <strong>' . e($faqTraitName) . '</strong>' . $traitCountText . '.';
      if ($faqQ4Answer !== null) {
          $faqQ4Answer .= $traitSentence;
      } else {
          $faqQ4Answer = 'The rarest known trait for ' . e($displayName) . ' is <strong>' . e($faqTraitName) . '</strong>' . $traitCountText . '.';
      }
  }
  // null → excluded from $faqPairs (avoids 306 identical "No mutation data" answers)

  // ── Q5: Worth trading? (+ coin value suffix for uniqueness) ─────────────
  $faqQ5Answer = match (true) {
      in_array($rarityKey, ['mythic', 'secret', 'brainrot god']) =>
          'Yes — ' . e($displayName) . ' is generally considered <strong>high-value</strong> for trading due to its ' . e($rarityLabel) . ' rarity tier and low supply.',
      $rarityKey === 'legendary' =>
          e($displayName) . ' is Legendary tier and typically holds <strong>strong trade value</strong>, especially for mutated variants.',
      ($totalExists !== null && $totalExists < 500) =>
          'With only <strong>' . number_format((int) $totalExists) . '</strong> known copies, ' . e($displayName) . ' has strong scarcity and may be worth significant trades.',
      ($totalExists !== null && $totalExists < 5000) =>
          e($displayName) . ' has a relatively low exist count, which may make it desirable in trades depending on current demand.',
      ($totalExists !== null) =>
          e($displayName) . ' has a higher exist count, so its trade value depends more on demand than scarcity.',
      default =>
          'Trade value for ' . e($displayName) . ' depends on current market demand. Check community value lists and recent trade activity for the most accurate assessment.',
  };
  $faqQ5Answer .= $coinsSuffix;

  // ── Build $faqPairs — only include questions with real data ──────────────
  $faqPairs = [];
  if ($faqQ0Answer !== null) {
      $faqPairs[] = ['What is the ' . $displayName . ' value in Steal a Brainrot?', $faqQ0Answer];
  }
  if ($faqQ1Answer !== null) {
      $faqPairs[] = ['How many ' . $displayName . ' exist in Steal a Brainrot?', $faqQ1Answer];
  }
  $faqPairs[] = ['Is ' . $displayName . ' rare?', $faqQ2Answer];
  // Q3 temporarily disabled
  if ($faqQ4Answer !== null) {
      $faqPairs[] = ['What is the best mutation for ' . $displayName . '?', $faqQ4Answer];
  }
  $faqPairs[] = ['Is ' . $displayName . ' worth trading for?', $faqQ5Answer];

  // ── FAQ JSON-LD built here so Blade never sees "@context" as a directive ──
  $faqJsonLd = json_encode(
      [
          '@context'   => 'https://schema.org',
          '@type'      => 'FAQPage',
          'mainEntity' => array_map(
              fn ($qa) => [
                  '@type'          => 'Question',
                  'name'           => $qa[0],
                  'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($qa[1])],
              ],
              $faqPairs
          ),
      ],
      JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
  );
@endphp

<section id="faq" class="mb-16 scroll-mt-32" style="scroll-margin-top:8rem">
  <h2 class="mb-4 text-xl font-black text-slate-100">{{ $displayName }} FAQ</h2>
  <div class="grid gap-4">
    @foreach($faqPairs as $faqPair)
    <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
      <h3 class="text-lg font-bold text-slate-100">{{ $faqPair[0] }}</h3>
      <p class="mt-2 leading-7 text-slate-300">{!! $faqPair[1] !!}</p>
    </div>
    @endforeach
  </div>
</section>

<script type="application/ld+json">
{!! $faqJsonLd !!}
</script>

@if($hasAboutContent)
<section id="about" class="mb-16 scroll-mt-32" style="scroll-margin-top:8rem">
  <h2 class="mb-4 text-xl font-black text-slate-100">About {{ $displayName }}</h2>
  <div class="grid gap-4">
    @if($localDescription !== '' || $rotRocksDescriptionHtml !== '')
    <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
      <div class="leading-7 text-slate-300 [&_em]:text-slate-200 [&_strong]:font-bold [&_strong]:text-slate-100 [&_p+p]:mt-2">
        @if($localDescription !== '')
        <p>{{ $localDescription }}</p>
        @endif
        @if($rotRocksDescriptionHtml !== '')
        {!! $rotRocksDescriptionHtml !!}
        @endif
      </div>
    </div>
    @endif
    @if($hasSeoInsights)
      @if(count($rarityValueSentences) > 0)
      <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
        <h3 class="text-lg font-bold text-slate-100">Rarity and Value Signals</h3>
        <ul class="mt-2 list-none space-y-1 leading-7 text-slate-300">
          @foreach($rarityValueSentences as $sentence)
          <li>{{ $sentence }}</li>
          @endforeach
        </ul>
      </div>
      @endif
      @if(count($mutationTraitSentences) > 0)
      <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
        <h3 class="text-lg font-bold text-slate-100">Best Mutations and Traits</h3>
        <ul class="mt-2 list-none space-y-1 leading-7 text-slate-300">
          @foreach($mutationTraitSentences as $sentence)
          <li>{{ $sentence }}</li>
          @endforeach
        </ul>
      </div>
      @endif
      @if(count($tradeSentences) > 0)
      <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
        <h3 class="text-lg font-bold text-slate-100">Should You Trade for {{ $displayName }}?</h3>
        <ul class="mt-2 list-none space-y-1 leading-7 text-slate-300">
          @foreach($tradeSentences as $sentence)
          <li>{{ $sentence }}</li>
          @endforeach
        </ul>
      </div>
      @endif
      @if(count($availabilityTradeSentences) > 0)
      <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
        <h3 class="text-lg font-bold text-slate-100">Availability and Trade Notes</h3>
        <ul class="mt-2 list-none space-y-1 leading-7 text-slate-300">
          @foreach($availabilityTradeSentences as $sentence)
          <li>{{ $sentence }}</li>
          @endforeach
        </ul>
      </div>
      @endif
    @endif
  </div>
</section>
@endif

{{-- Sources section intentionally hidden --}}

@if(!empty($playRobBrainrotHref))
<section class="mb-16">
  <a href="{{ $playRobBrainrotHref }}" class="inline-flex items-center text-sm font-bold text-cyan-300 hover:text-cyan-100">Play Rob Brainrot →</a>
</section>
@endif

@endsection

@section('scripts')
<script>
  (function () {
    var nav = document.querySelector('[data-section-nav]');
    if (!nav) return;
    var navShell = document.querySelector('[data-section-nav-shell]');
    var siteHeader = document.querySelector('body > header');

    var links = Array.prototype.slice.call(nav.querySelectorAll('[data-section-link]'));
    if (!links.length) return;

    var activeClasses = ['border-cyan-300', 'text-cyan-300'];
    var inactiveClasses = ['border-transparent'];
    var sections = links
      .map(function (link) {
        var id = link.getAttribute('data-section-link') || '';
        var section = document.getElementById(id);
        return section ? { id: id, section: section, link: link } : null;
      })
      .filter(Boolean);

    if (!sections.length) return;

    function syncStickyTop() {
      if (!navShell || !siteHeader) return;
      var headerBottom = Math.ceil(siteHeader.getBoundingClientRect().bottom);
      navShell.style.setProperty('--sab-sticky-anchor-top', Math.max(0, headerBottom) + 'px');
    }

    function setActive(id) {
      links.forEach(function (link) {
        var isActive = link.getAttribute('data-section-link') === id;
        activeClasses.forEach(function (className) { link.classList.toggle(className, isActive); });
        inactiveClasses.forEach(function (className) { link.classList.toggle(className, !isActive); });
        if (isActive) {
          link.setAttribute('aria-current', 'true');
        } else {
          link.removeAttribute('aria-current');
        }
      });
    }

    function activeByScrollPosition() {
      var offset = Math.max(120, Math.floor(window.innerHeight * 0.22));
      var current = sections[0];
      sections.forEach(function (entry) {
        if (entry.section.getBoundingClientRect().top <= offset) {
          current = entry;
        }
      });
      setActive(current.id);
    }

    if ('IntersectionObserver' in window) {
      var visibleIds = {};
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          visibleIds[entry.target.id] = entry.isIntersecting;
        });

        var visible = sections.filter(function (entry) { return visibleIds[entry.id]; });
        if (visible.length) {
          visible.sort(function (a, b) {
            return Math.abs(a.section.getBoundingClientRect().top) - Math.abs(b.section.getBoundingClientRect().top);
          });
          setActive(visible[0].id);
        } else {
          activeByScrollPosition();
        }
      }, {
        rootMargin: '-28% 0px -55% 0px',
        threshold: [0, 0.1, 0.25, 0.5],
      });

      sections.forEach(function (entry) { observer.observe(entry.section); });
    }

    window.addEventListener('scroll', activeByScrollPosition, { passive: true });
    window.addEventListener('resize', function () {
      syncStickyTop();
      activeByScrollPosition();
    });
    syncStickyTop();
    activeByScrollPosition();
  })();
</script>

<script>
  (function () {
    var canvas = document.getElementById('sabPriceHistoryChart');
    if (!canvas) return;

    var emptyEl = document.getElementById('sabPriceHistoryEmpty');
    var signalEl = document.getElementById('sabPriceHistorySignal');
    var pctEl = document.getElementById('sabPriceHistoryPct');
    var rangeEl = document.getElementById('sabPriceHistoryRange');
    var curValEl = document.getElementById('sabPriceHistoryCurVal');
    var mutValueEl = document.getElementById('sabItemMutValue');
    var mutTabsEl = document.getElementById('sabItemMutTabs');

    var mutations = [];
    try {
      mutations = JSON.parse(canvas.getAttribute('data-mutations') || '[]');
    } catch (e) {
      mutations = [];
    }

    var legacyHistory = [];
    if (mutations.length === 0) {
      try {
        legacyHistory = JSON.parse(canvas.getAttribute('data-price-history') || '[]');
      } catch (e2) {
        legacyHistory = [];
      }
    }

    function normalizeHistory(raw) {
      return (raw || [])
        .filter(function (point) { return point && point.value !== null && point.value !== undefined; })
        .map(function (point) {
          return { date: String(point.date || ''), value: Number(point.value) };
        })
        .filter(function (point) { return Number.isFinite(point.value); });
    }

    function fmtDate(dateStr) {
      if (!dateStr) return '';
      var d = new Date(dateStr);
      if (Number.isNaN(d.getTime())) return dateStr;
      return String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function fmtValue(n) {
      if (n === null || n === undefined || !Number.isFinite(Number(n))) return '—';
      n = Number(n);
      if (n >= 1e9) return '$' + (n / 1e9).toFixed(2) + 'B';
      if (n >= 1e6) return '$' + (n / 1e6).toFixed(2) + 'M';
      if (n >= 1e3) return '$' + (n / 1e3).toFixed(1) + 'K';
      return '$' + n.toFixed(2);
    }

    function fmtRobuxFooter(n) {
      if (n === null || n === undefined || !Number.isFinite(Number(n))) return '';
      n = Number(n);
      return n.toLocaleString('en-US', { maximumFractionDigits: n >= 1000 ? 0 : 2 }) + ' ROBUX';
    }

    function updateMeta(history, robuxValue) {
      var lastVal = history.length > 0 ? history[history.length - 1].value : robuxValue;
      var firstVal = history.length > 0 ? history[0].value : null;
      var pctChange = (history.length >= 2 && firstVal > 0)
        ? ((lastVal - firstVal) / firstVal) * 100
        : null;

      if (mutValueEl && robuxValue != null) {
        mutValueEl.textContent = fmtValue(robuxValue);
      }
      if (curValEl) {
        curValEl.textContent = lastVal != null ? fmtRobuxFooter(lastVal) : '';
      }

      if (signalEl && pctEl) {
        if (pctChange !== null) {
          var isPositive = pctChange >= 0;
          signalEl.textContent = isPositive ? 'High' : 'Low';
          signalEl.className = 'rounded-full border px-3 py-1 text-xs font-black '
            + (isPositive
              ? 'border-emerald-400/40 bg-emerald-400/10 text-emerald-300'
              : 'border-rose-400/40 bg-rose-400/10 text-rose-300');
          signalEl.classList.remove('hidden');
          pctEl.textContent = (isPositive ? '+' : '') + pctChange.toFixed(1) + '%';
          pctEl.className = 'text-lg font-black ' + (isPositive ? 'text-emerald-300' : 'text-rose-300');
          pctEl.classList.remove('hidden');
        } else {
          signalEl.classList.add('hidden');
          pctEl.classList.add('hidden');
        }
      }

      if (rangeEl) {
        if (history.length === 0) {
          rangeEl.textContent = '';
        } else if (history.length === 1) {
          rangeEl.textContent = '1 price point · ' + fmtDate(history[0].date);
        } else {
          rangeEl.textContent = history.length + ' days · ' + fmtDate(history[0].date) + ' - ' + fmtDate(history[history.length - 1].date);
        }
      }
    }

    function drawHistory(history) {
      history = normalizeHistory(history);

      if (history.length === 0) {
        canvas.style.display = 'none';
        if (emptyEl) emptyEl.style.display = 'block';
        return;
      }

      canvas.style.display = 'block';
      if (emptyEl) emptyEl.style.display = 'none';

      var dpr = window.devicePixelRatio || 1;
      var width = canvas.clientWidth || 720;
      var height = canvas.clientHeight || 112;
      canvas.width = width * dpr;
      canvas.height = height * dpr;

      var ctx = canvas.getContext('2d');
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      ctx.clearRect(0, 0, width, height);

      var padX = 12;
      var padY = 10;
      var plotW = width - padX * 2;
      var plotH = height - padY * 2;

      ctx.strokeStyle = 'rgba(148, 163, 184, .12)';
      ctx.lineWidth = 1;
      ctx.setLineDash([5, 5]);
      for (var i = 0; i < 3; i++) {
        var gridY = padY + (plotH / 2) * i;
        ctx.beginPath();
        ctx.moveTo(padX, gridY);
        ctx.lineTo(width - padX, gridY);
        ctx.stroke();
      }
      ctx.setLineDash([]);

      var values = history.map(function (point) { return point.value; });
      var minVal = Math.min.apply(Math, values);
      var maxVal = Math.max.apply(Math, values);
      var range = maxVal - minVal || Math.max(maxVal, 1);

      var points = values.map(function (value, index) {
        return {
          x: history.length === 1 ? width / 2 : padX + (index / (history.length - 1)) * plotW,
          y: padY + (1 - (value - minVal) / range) * plotH,
        };
      });

      if (history.length === 1) {
        ctx.beginPath();
        ctx.arc(points[0].x, points[0].y, 5, 0, Math.PI * 2);
        ctx.fillStyle = '#fbbf24';
        ctx.fill();
        ctx.lineWidth = 2;
        ctx.strokeStyle = '#22c55e';
        ctx.stroke();
        return;
      }

      var firstVal = values[0];
      var lastVal = values[values.length - 1];
      var isPositive = lastVal >= firstVal;
      var lineColor = isPositive ? '#22c55e' : '#ef4444';
      var grad = ctx.createLinearGradient(0, padY, 0, height);
      grad.addColorStop(0, isPositive ? 'rgba(34,197,94,.24)' : 'rgba(239,68,68,.22)');
      grad.addColorStop(1, isPositive ? 'rgba(34,197,94,.02)' : 'rgba(239,68,68,.02)');

      ctx.beginPath();
      ctx.moveTo(points[0].x, points[0].y);
      for (var j = 1; j < points.length; j++) {
        var cpX = (points[j - 1].x + points[j].x) / 2;
        ctx.bezierCurveTo(cpX, points[j - 1].y, cpX, points[j].y, points[j].x, points[j].y);
      }
      ctx.lineTo(points[points.length - 1].x, height - padY);
      ctx.lineTo(points[0].x, height - padY);
      ctx.closePath();
      ctx.fillStyle = grad;
      ctx.fill();

      ctx.beginPath();
      ctx.moveTo(points[0].x, points[0].y);
      for (var k = 1; k < points.length; k++) {
        var cp = (points[k - 1].x + points[k].x) / 2;
        ctx.bezierCurveTo(cp, points[k - 1].y, cp, points[k].y, points[k].x, points[k].y);
      }
      ctx.strokeStyle = lineColor;
      ctx.lineWidth = 3;
      ctx.stroke();

      var minIdx = values.indexOf(minVal);
      var maxIdx = values.indexOf(maxVal);
      [[minIdx, '#f59e0b'], [maxIdx, '#fbbf24']].forEach(function (entry) {
        ctx.beginPath();
        ctx.arc(points[entry[0]].x, points[entry[0]].y, 4, 0, Math.PI * 2);
        ctx.fillStyle = entry[1];
        ctx.fill();
      });
    }

    var currentHistory = mutations.length > 0
      ? normalizeHistory(mutations[0].priceHistory)
      : normalizeHistory(legacyHistory);

    var currentRobux = mutations.length > 0 ? mutations[0].robuxValue : null;

    function renderCurrent() {
      updateMeta(currentHistory, currentRobux);
      drawHistory(currentHistory);
    }

    if (mutTabsEl && mutations.length > 0) {
      mutTabsEl.querySelectorAll('.sab-item-mut-tab').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var idx = Number(btn.getAttribute('data-mut-idx'));
          if (!Number.isFinite(idx) || !mutations[idx]) return;
          mutTabsEl.querySelectorAll('.sab-item-mut-tab').forEach(function (b) { b.classList.remove('is-active'); });
          btn.classList.add('is-active');
          currentHistory = normalizeHistory(mutations[idx].priceHistory);
          currentRobux = mutations[idx].robuxValue;
          renderCurrent();
        });
      });
    }

    if (currentHistory.length === 0 && currentRobux == null && legacyHistory.length === 0) {
      return;
    }

    renderCurrent();
    window.addEventListener('resize', renderCurrent, { passive: true });
  })();
</script>

<script>
  (function () {
    var table = document.querySelector('[data-variants-table]');
    var body = document.querySelector('[data-variants-body]');
    var button = document.querySelector('[data-variants-value-sort]');
    var moreWrap = document.getElementById('variants-more-wrap');
    var moreBtn = document.getElementById('variants-more-btn');
    if (!table || !body || !button) return;

    var defaultLimit = 15;
    var expanded = false;
    var showAllLabel = {!! json_encode($t['home_show_all']) !!};
    var showTopLabel = {!! json_encode($t['home_show_top_10']) !!};

    function refreshRowsMeta() {
      return Array.prototype.slice.call(body.querySelectorAll('tr')).map(function (row, index) {
        return {
          row: row,
          index: index,
          value: row.getAttribute('data-value-sort') === '' ? null : Number(row.getAttribute('data-value-sort')),
        };
      });
    }

    var rows = refreshRowsMeta();

    function applyVariantMoreLimit() {
      var domRows = Array.prototype.slice.call(body.querySelectorAll('tr'));
      if (!moreWrap || !moreBtn) return;
      if (domRows.length <= defaultLimit) {
        moreWrap.classList.add('hidden');
        domRows.forEach(function (tr) { tr.classList.remove('hidden'); });
        return;
      }
      moreWrap.classList.remove('hidden');
      moreBtn.style.display = 'block';
      if (expanded) {
        domRows.forEach(function (tr) { tr.classList.remove('hidden'); });
        moreBtn.textContent = showTopLabel;
      } else {
        domRows.forEach(function (tr, i) {
          tr.classList.toggle('hidden', i >= defaultLimit);
        });
        moreBtn.textContent = showAllLabel;
      }
    }

    if (moreBtn) {
      moreBtn.addEventListener('click', function () {
        expanded = !expanded;
        applyVariantMoreLimit();
      });
    }

    var icon = document.querySelector('[data-variants-value-sort-icon]');
    var valueHeader = button.closest('th');
    var direction = '';

    button.addEventListener('click', function () {
      direction = direction === 'desc' ? 'asc' : 'desc';

      rows = refreshRowsMeta();
      rows
        .slice()
        .sort(function (a, b) {
          var aMissing = a.value === null || Number.isNaN(a.value);
          var bMissing = b.value === null || Number.isNaN(b.value);
          if (aMissing && bMissing) return a.index - b.index;
          if (aMissing) return 1;
          if (bMissing) return -1;
          if (a.value === b.value) return a.index - b.index;

          return direction === 'desc' ? b.value - a.value : a.value - b.value;
        })
        .forEach(function (entry) {
          body.appendChild(entry.row);
        });

      rows = refreshRowsMeta();

      if (icon) icon.textContent = direction === 'desc' ? '↓ High to low' : '↑ Low to high';
      if (valueHeader) valueHeader.setAttribute('aria-sort', direction === 'desc' ? 'descending' : 'ascending');

      applyVariantMoreLimit();
    });

    applyVariantMoreLimit();
  })();
</script>

@if(!empty($galleryImages) && $galleryImages->isNotEmpty())
<script>
  (function () {
    var lightbox = document.querySelector('[data-item-gallery-lightbox]');
    var image = document.querySelector('[data-item-gallery-lightbox-image]');
    var title = document.querySelector('[data-item-gallery-lightbox-title]');

    document.querySelectorAll('[data-item-gallery-open]').forEach(function (button) {
      button.addEventListener('click', function () {
        if (!lightbox || !image) return;
        var src = button.getAttribute('data-src') || '';
        var name = button.getAttribute('data-name') || '';
        image.setAttribute('src', src);
        image.setAttribute('alt', name);
        if (title) title.textContent = name;
        lightbox.hidden = false;
        document.body.style.overflow = 'hidden';
      });
    });

    function closeLightbox() {
      if (!lightbox || !image) return;
      lightbox.hidden = true;
      image.setAttribute('src', '');
      document.body.style.overflow = '';
    }

    document.querySelectorAll('[data-item-gallery-close]').forEach(function (button) {
      button.addEventListener('click', closeLightbox);
    });
    if (lightbox) {
      lightbox.addEventListener('click', function (event) {
        if (event.target === lightbox) closeLightbox();
      });
    }
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeLightbox();
    });
  })();
</script>
@endif

@endsection
