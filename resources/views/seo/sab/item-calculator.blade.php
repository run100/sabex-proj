@extends('seo.sab.layout-calculator')

@section('content')
@php
  $itemAttrs = is_array($item->attributes_json ?? null) ? $item->attributes_json : [];

  $displayName = $displayName ?? $item->name;
  $brainrotData = $brainrotData ?? [];
  $baseIncome = (float) ($brainrotData['baseIncome'] ?? 0);
  $robuxValue = $brainrotData['robuxValue'] ?? null;
  $demand = trim((string) ($brainrotData['demand'] ?? ''));
  $trend = trim((string) ($brainrotData['trend'] ?? ''));
  $mutationRows = $brainrotData['mutations'] ?? [];

  $mutationPriceData = is_array($mutationPriceData ?? null) ? $mutationPriceData : null;
  $showMutationPriceSection = $mutationPriceData !== null;
  $mpdMutations = $showMutationPriceSection ? ($mutationPriceData['mutations'] ?? []) : [];
  $mpdBaseIncome = $showMutationPriceSection ? (float) ($mutationPriceData['baseIncome'] ?? 0) : $baseIncome;
  $mpdBaseRobux = $showMutationPriceSection ? ($mutationPriceData['baseRobuxValue'] ?? null) : null;
  $initialMutation = $showMutationPriceSection ? ($mpdMutations[0] ?? []) : [];
  $priceHistorySource = $showMutationPriceSection
    ? ($initialMutation['priceHistory'] ?? [])
    : ($priceHistory ?? []);
  $priceHistoryRows = collect($priceHistorySource)->filter(fn ($r) => is_array($r) && isset($r['value']))->values();
  $pricePointCount = $priceHistoryRows->count();
  $priceHistoryLast = $pricePointCount > 0 ? $priceHistoryRows->last() : null;
  $priceHistoryFirst = $pricePointCount > 0 ? $priceHistoryRows->first() : null;
  $priceHistoryLatestValue = $priceHistoryLast
    ? (float) ($priceHistoryLast['value'] ?? 0)
    : ($showMutationPriceSection ? ($initialMutation['robuxValue'] ?? null) : null);
  $priceHistoryFirstValue = $priceHistoryFirst ? (float) ($priceHistoryFirst['value'] ?? 0) : null;
  $priceHistoryChange = ($pricePointCount >= 2 && $priceHistoryFirstValue > 0)
    ? (($priceHistoryLatestValue - $priceHistoryFirstValue) / $priceHistoryFirstValue) * 100
    : null;
  $priceHistoryDate = $priceHistoryLast['date'] ?? null;

  $imgSrc = \App\Services\Seo\SabRenderService::listingImageSrc($item);
  $rarityKey = \App\Services\Seo\SabRenderService::canonicalRarityKey($item->rarity ?? null);
  $rarityLower = $rarityKey;
  $rarityLabel = $rarityKey !== '' ? \App\Services\Seo\SabRenderService::canonicalRarityLabel($rarityKey) : '';
  $badgeClass = match(true) {
    in_array($rarityLower, ['legendary', 'secret', 'brainrot god']) => 'bg-pink-500/20 text-pink-400 border border-pink-500/30',
    $rarityLower === 'mythic'   => 'bg-fuchsia-500/20 text-fuchsia-400 border border-fuchsia-500/30',
    $rarityLower === 'epic'     => 'bg-orange-500/20 text-orange-400 border border-orange-500/30',
    $rarityLower === 'rare'     => 'bg-purple-500/20 text-purple-400 border border-purple-500/30',
    $rarityLower === 'uncommon' => 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30',
    default                    => 'bg-slate-700/50 text-slate-400 border border-slate-600/30',
  };

  // Income formatter
  $incomeFmt = match(true) {
    $baseIncome >= 1e9  => '$' . round($baseIncome / 1e9, 1) . 'B/s',
    $baseIncome >= 1e6  => '$' . round($baseIncome / 1e6, 1) . 'M/s',
    $baseIncome >= 1e3  => '$' . round($baseIncome / 1e3, 1) . 'K/s',
    $baseIncome > 0     => '$' . round($baseIncome) . '/s',
    default             => '—',
  };

  // Navigation links
  $homeHref = ($urlPrefix ?? '') === '' ? '/' : rtrim($urlPrefix ?? '', '/');
  $brainrotsHref = rtrim($urlPrefix ?? '', '/') . '/' . \App\Services\Seo\SabRenderService::PAGE_BRAINROTS_LIST;

  // About content
  $localDescription = trim((string) ($description ?? ''));
  $rotRocksDescHtml = trim((string) ($rotRocksDescriptionHtml ?? ''));
  $hasLocalContent = $localDescription !== '' || $rotRocksDescHtml !== '';

  // SEO insight sentences
  $rarityValueSentences = [];
  if ($rarityLabel !== '') {
    $rarityValueSentences[] = $displayName . ' is listed as ' . $rarityLabel . ' rarity in Steal a Brainrot.';
  }
  if ($robuxValue !== null) {
    $rarityValueSentences[] = 'The latest tracked value signal is around ' . number_format((float) $robuxValue) . ' ROBUX.';
  }
  if ($demand !== '') {
    $rarityValueSentences[] = 'Current community demand is marked as ' . $demand . '.';
  }
  if ($baseIncome > 0) {
    $rarityValueSentences[] = 'The base income rate is ' . $incomeFmt . ', which is the main earning signal for this brainrot.';
  }

  $mutationSentences = [];
  $realMutationCount = count(array_filter($mutationRows, fn ($m) => ($m['id'] ?? '') !== 'base'));
  if ($realMutationCount > 0) {
    $mutationSentences[] = $displayName . ' has ' . $realMutationCount . ' known mutation(s). Compare mutation values in the table above to find the most valuable variant.';
  }

  $tradeSentences = [];
  if (in_array($rarityLower, ['mythic', 'secret', 'brainrot god'], true)) {
    $tradeSentences[] = $displayName . ' is generally considered high-value for trading due to its ' . $rarityLabel . ' rarity tier.';
  } elseif ($rarityLabel !== '') {
    $tradeSentences[] = $displayName . ' trade interest depends on its ' . $rarityLabel . ' rarity, current demand, and value signals.';
  }
  if ($demand !== '') {
    $tradeSentences[] = 'Community demand is currently ' . $demand . '. Use this alongside the 30D price chart above before making trade decisions.';
  }

  $hasSeoInsights = count($rarityValueSentences) > 0 || count($mutationSentences) > 0 || count($tradeSentences) > 0;
  $hasAboutContent = $hasLocalContent || $hasSeoInsights;

  // FAQ
  $faqQ_rarity = match ($rarityLower) {
    'mythic', 'secret', 'brainrot god' => e($displayName) . ' is considered <strong>extremely rare</strong> in Steal a Brainrot. Its ' . e($rarityLabel) . ' rarity tier puts it among the hardest items to obtain.',
    'legendary' => e($displayName) . ' is a <strong>Legendary</strong> tier brainrot — one of the rarer items in the game.',
    'epic'      => e($displayName) . ' is an <strong>Epic</strong> tier item, moderately rare and sought after by collectors.',
    'rare'      => e($displayName) . ' is classified as <strong>Rare</strong>. It is harder to obtain than Common items but more accessible than higher tiers.',
    'common'    => e($displayName) . ' is a <strong>Common</strong> tier item, relatively easy to find compared to higher-rarity brainrots.',
    default     => e($displayName) . '\'s rarity has not been officially confirmed yet. Check community value lists for the latest classification.',
  };

  $faqQ_income = $baseIncome > 0
    ? e($displayName) . ' generates <strong>' . e($incomeFmt) . '</strong> per second in Steal a Brainrot. This is the base rate for the normal (Default) variant.'
    : 'Income data for ' . e($displayName) . ' is not currently tracked. Check the in-game stats for the latest values.';

  $faqQ_value = $robuxValue !== null
    ? 'The current tracked value of ' . e($displayName) . ' is approximately <strong>' . number_format((float) $robuxValue) . ' ROBUX</strong>. Value may shift with demand and trading activity — see the 30D chart above.'
    : 'No confirmed ROBUX value is available yet for ' . e($displayName) . '. Check the 30D price history section for the latest signals.';

  $faqQ_mutations = $realMutationCount > 0
    ? e($displayName) . ' has <strong>' . $realMutationCount . '</strong> known mutation(s). Check the Mutations table on this page for each variant\'s ROBUX value and demand to identify the most valuable option.'
    : e($displayName) . ' does not have confirmed mutation value data in this tracker yet.';

  $faqQ_trade = match (true) {
    in_array($rarityLower, ['mythic', 'secret', 'brainrot god']) =>
      'Yes — ' . e($displayName) . ' is generally high-value for trading due to its ' . e($rarityLabel) . ' rarity tier and limited supply.',
    $rarityLower === 'legendary' =>
      e($displayName) . ' is Legendary tier and typically holds <strong>strong trade value</strong>, especially for mutated variants.',
    strtolower($demand) === 'high' =>
      'With <strong>High</strong> demand, ' . e($displayName) . ' is actively sought after and may hold good trade value.',
    default =>
      'Trade value for ' . e($displayName) . ' depends on current market demand. Use the 30D price chart and demand signal above as context before trading.',
  };

  $faqPairs = [
    ['Is ' . $displayName . ' rare?', $faqQ_rarity],
    ['What is ' . $displayName . '\'s income per second?', $faqQ_income],
    ['What is ' . $displayName . '\'s ROBUX value?', $faqQ_value],
    ['What mutations does ' . $displayName . ' have?', $faqQ_mutations],
    ['Is ' . $displayName . ' worth trading for?', $faqQ_trade],
  ];

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

<style>
  .sab-ci-breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: rgb(100, 116, 139);
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
  }
  .sab-ci-breadcrumb a {
    color: rgb(148, 163, 184);
    text-decoration: none;
  }
  .sab-ci-breadcrumb a:hover {
    color: rgb(34, 211, 238);
  }
  .sab-ci-breadcrumb-sep {
    color: rgb(71, 85, 105);
  }
  .sab-ci-hero {
    display: grid;
    grid-template-columns: 4.5rem minmax(0, 1fr);
    gap: 0.875rem;
    align-items: center;
    margin-bottom: 1.25rem;
  }
  .sab-ci-hero-img-wrap {
    width: 4.5rem;
    height: 4.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.875rem;
    border: 1px solid rgba(255,255,255,0.1);
    background: rgba(15,23,42,0.7);
    padding: 0.5rem;
  }
  .sab-ci-hero-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 0.5rem;
  }
  .sab-ci-hero-title {
    font-size: 1.25rem;
    font-weight: 900;
    color: rgb(226, 232, 240);
    line-height: 1.2;
    margin: 0 0 0.375rem;
  }
  .sab-ci-hero-title .sab-ci-accent {
    color: rgb(74, 222, 128);
  }
  .sab-ci-asset-note {
    margin: 0.65rem 0 0;
    max-width: 36rem;
    color: rgb(100, 116, 139);
    font-size: 0.75rem;
    line-height: 1.5;
  }
  .sab-ci-asset-note a {
    color: rgb(148, 163, 184);
    text-decoration: underline;
    text-underline-offset: 2px;
  }
  .sab-ci-asset-note a:hover {
    color: rgb(226, 232, 240);
  }
  .sab-ci-stat-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0;
    border-radius: 0.875rem;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(15,23,42,0.75);
    overflow: hidden;
    margin-bottom: 1.25rem;
  }
  .sab-ci-stat-cell {
    padding: 0.75rem 1rem;
  }
  .sab-ci-stat-cell + .sab-ci-stat-cell {
    border-left: 1px solid rgba(255,255,255,0.07);
  }
  .sab-ci-stat-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: rgb(100, 116, 139);
    margin-bottom: 0.25rem;
  }
  .sab-ci-stat-value {
    font-size: 1rem;
    font-weight: 800;
    line-height: 1.2;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .sab-ci-stat-value--income { color: rgb(74, 222, 128); }
  .sab-ci-stat-value--value  { color: rgb(251, 191, 36); }
  .sab-ci-stat-value--demand { color: rgb(226, 232, 240); }
  .sab-ci-stat-value--empty  { color: rgb(71, 85, 105); }
  .sab-ci-price-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
  }
  .sab-ci-price-stat-label {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: rgb(100, 116, 139);
  }
  .sab-ci-mutation-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
    margin-bottom: 1rem;
  }
  .sab-ci-mut-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.65rem;
    border: 1px solid rgba(148,163,184,0.25);
    border-radius: 0.5rem;
    background: rgba(15,23,42,0.6);
    color: #94a3b8;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: border-color 120ms, background 120ms, color 120ms;
  }
  .sab-ci-mut-tab img { width: 1rem; height: 1rem; object-fit: contain; }
  .sab-ci-mut-tab:hover { color: #e2e8f0; border-color: rgba(148,163,184,0.45); }
  .sab-ci-mut-tab.is-active {
    border-color: rgba(34,197,94,0.7);
    background: rgba(6,78,59,0.22);
    color: #4ade80;
  }
  .sab-ci-chart-empty {
    display: none;
    padding: 1.5rem 0;
    text-align: center;
    font-size: 0.875rem;
    color: rgb(100, 116, 139);
  }
  .sab-ci-section-nav {
    display: flex;
    flex-wrap: nowrap;
    gap: 0.25rem;
    overflow-x: auto;
    padding: 0 1rem;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
  }
  .sab-ci-section-nav::-webkit-scrollbar { display: none; }
  .sab-ci-section-nav > a {
    flex-shrink: 0;
    margin-bottom: -1px;
    padding: 0.7rem 0.85rem;
    border-bottom: 2px solid transparent;
    color: rgb(148, 163, 184);
    font-size: 0.875rem;
    font-weight: 700;
    line-height: 1.1;
    text-decoration: none;
    white-space: nowrap;
    transition: color 0.15s ease, border-color 0.15s ease;
  }
  .sab-ci-section-nav > a:hover {
    color: rgb(226, 232, 240);
  }
  .sab-ci-section-nav > a.is-active {
    color: rgb(74, 222, 128);
    border-bottom-color: rgb(74, 222, 128);
  }
  @media (min-width: 768px) {
    .sab-ci-section-nav {
      flex-wrap: wrap;
      gap: 0.35rem;
      overflow: visible;
    }
    .sab-ci-hero {
      grid-template-columns: 7rem minmax(0, 1fr);
    }
    .sab-ci-hero-img-wrap {
      width: 7rem;
      height: 7rem;
    }
    .sab-ci-hero-title { font-size: 1.75rem; }
  }
</style>

<div class="sab-br-page" style="max-width:860px;margin:0 auto;padding:1.25rem 1rem 3rem;">

  {{-- Breadcrumb --}}
  <nav class="sab-ci-breadcrumb" aria-label="Breadcrumb">
    <a href="{{ $homeHref }}">Home</a>
    <span class="sab-ci-breadcrumb-sep">›</span>
    <a href="{{ $brainrotsHref }}">Brainrots</a>
    <span class="sab-ci-breadcrumb-sep">›</span>
    <span style="color:rgb(226,232,240)">{{ $displayName }}</span>
  </nav>

  {{-- Section anchor nav --}}
  <div class="sticky z-40 -mx-4 mb-5 border-b border-white/10 bg-slate-950/90 backdrop-blur" style="top:var(--sab-ci-sticky-top,64px)" data-ci-nav-shell>
    <nav class="sab-ci-section-nav" aria-label="Page sections" data-ci-nav>
      <a href="#value" class="is-active" data-ci-link="value" aria-current="true">Value</a>
      @if($showMutationPriceSection || $pricePointCount > 0)
      <a href="#price-history" data-ci-link="price-history">30D Price</a>
      @endif
      @if(!empty($mutationRows) && count($mutationRows) > 1)
      <a href="#mutations" data-ci-link="mutations">Mutations</a>
      @endif
      <a href="#faq" data-ci-link="faq">FAQ</a>
      @if($hasAboutContent)
      <a href="#about" data-ci-link="about">About</a>
      @endif
    </nav>
  </div>

  {{-- Hero --}}
  <section id="value" class="scroll-mt-32 mb-6" style="scroll-margin-top:8rem">
    <div class="sab-ci-hero">
      <div class="sab-ci-hero-img-wrap">
        @if($imgSrc)
        <img src="{{ $imgSrc }}" alt="{{ $displayName }}" width="112" height="112" />
        @else
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:rgb(71,85,105)">No Image</div>
        @endif
      </div>
      <div style="min-width:0">
        <h1 class="sab-ci-hero-title">
          {{ $displayName }} <span class="sab-ci-accent">Value &amp; Income</span>
        </h1>
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.5rem">
          @if($rarityLabel !== '')
          <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase {{ $badgeClass }}">{{ $rarityLabel }}</span>
          @endif
          @if($trend !== '')
          <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase bg-slate-700/40 text-slate-400 border border-slate-600/30">{{ $trend }}</span>
          @endif
        </div>
        <p class="sab-ci-asset-note">
          Item image and description are shown for identification only. Unofficial fan reference — see
          <a href="{{ \App\Services\Seo\SabRenderService::calculatorStaticPageHref($urlPrefix ?? '', 'about-us') }}">About</a>
          for sources.
        </p>
      </div>
    </div>

    {{-- Stat card --}}
    <div class="sab-ci-stat-grid">
      <div class="sab-ci-stat-cell">
        <div class="sab-ci-stat-label">Income/s</div>
        <div class="sab-ci-stat-value {{ $baseIncome > 0 ? 'sab-ci-stat-value--income' : 'sab-ci-stat-value--empty' }}">{{ $incomeFmt }}</div>
      </div>
      <div class="sab-ci-stat-cell">
        <div class="sab-ci-stat-label">Value</div>
        <div class="sab-ci-stat-value {{ $robuxValue !== null ? 'sab-ci-stat-value--value' : 'sab-ci-stat-value--empty' }}">
          @if($robuxValue !== null)
          {{ number_format((float) $robuxValue) }} <span style="font-size:0.65rem;font-weight:600;opacity:.7">ROBUX</span>
          @else
          —
          @endif
        </div>
      </div>
      <div class="sab-ci-stat-cell">
        <div class="sab-ci-stat-label">Demand</div>
        <div class="sab-ci-stat-value {{ $demand !== '' ? 'sab-ci-stat-value--demand' : 'sab-ci-stat-value--empty' }}">{{ $demand !== '' ? $demand : '—' }}</div>
      </div>
    </div>
  </section>

  {{-- Calculator CTA --}}
  <div class="mb-6">
    <a
      href="{{ $homeHref }}"
      class="flex w-full items-center justify-between gap-3 rounded-2xl border border-white/10 bg-slate-900/80 px-5 py-4 text-sm font-semibold text-slate-300 shadow-[0_20px_60px_rgba(0,0,0,.22)] transition-colors hover:border-green-400/40 hover:text-green-300"
    >
      <span>Trade <span class="text-slate-100">{{ $displayName }}</span> — Check in Calculator</span>
      <span class="shrink-0 text-green-400">→</span>
    </a>
  </div>

  {{-- 30D Price History --}}
  @if($showMutationPriceSection || $priceHistoryLatestValue !== null)
  <section id="price-history" class="mb-6 scroll-mt-32" style="scroll-margin-top:8rem">
    <div class="rounded-2xl border border-white/10 bg-slate-900/80 p-5 shadow-[0_20px_60px_rgba(0,0,0,.22)]">
      @if($showMutationPriceSection)
      <div class="sab-ci-price-stats">
        <div>
          <div class="sab-ci-price-stat-label">Value</div>
          <div id="sabCiBaseValue" class="mt-1 text-base font-bold text-slate-100">
            @if($mpdBaseRobux !== null)
            ${{ number_format($mpdBaseRobux, $mpdBaseRobux >= 1000 ? 0 : 2) }}
            @else
            —
            @endif
          </div>
        </div>
        <div>
          <div class="sab-ci-price-stat-label">Income/s</div>
          <div id="sabCiBaseIncome" class="mt-1 text-base font-bold text-emerald-400" data-income="{{ $mpdBaseIncome }}">
            @if($mpdBaseIncome > 0)
            @php
              $mpdIncomeFmt = match(true) {
                $mpdBaseIncome >= 1e9 => '$' . round($mpdBaseIncome / 1e9, 1) . 'B/s',
                $mpdBaseIncome >= 1e6 => '$' . round($mpdBaseIncome / 1e6, 1) . 'M/s',
                $mpdBaseIncome >= 1e3 => '$' . round($mpdBaseIncome / 1e3, 1) . 'K/s',
                default              => '$' . round($mpdBaseIncome) . '/s',
              };
            @endphp
            {{ $mpdIncomeFmt }}
            @else
            —
            @endif
          </div>
        </div>
        <div>
          <div class="sab-ci-price-stat-label">Mutation Value</div>
          <div id="sabCiMutValue" class="mt-1 text-base font-bold text-amber-300">
            @if(($initialMutation['robuxValue'] ?? null) !== null)
            ${{ number_format($initialMutation['robuxValue'], $initialMutation['robuxValue'] >= 1000 ? 0 : 2) }}
            @else
            —
            @endif
          </div>
        </div>
      </div>

      @if(count($mpdMutations) > 1)
      <div class="sab-ci-mutation-tabs" id="sabCiMutTabs" role="group" aria-label="Mutations">
        @foreach($mpdMutations as $mutIdx => $mut)
        <button
          type="button"
          class="sab-ci-mut-tab{{ $mutIdx === 0 ? ' is-active' : '' }}"
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
          <span id="sabCiPriceSignal" class="rounded-full border px-3 py-1 text-xs font-black {{ $priceHistoryChange >= 0 ? 'border-emerald-400/40 bg-emerald-400/10 text-emerald-300' : 'border-rose-400/40 bg-rose-400/10 text-rose-300' }}">
            {{ $priceHistoryChange >= 0 ? 'High' : 'Low' }}
          </span>
          <span id="sabCiPricePct" class="text-lg font-black {{ $priceHistoryChange >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
            {{ $priceHistoryChange >= 0 ? '+' : '' }}{{ number_format($priceHistoryChange, 1) }}%
          </span>
          @else
          <span id="sabCiPriceSignal" class="rounded-full border px-3 py-1 text-xs font-black hidden"></span>
          <span id="sabCiPricePct" class="text-lg font-black hidden"></span>
          @endif
        </div>
      </div>

      <canvas
        id="sabCiPriceChart"
        class="block h-28 w-full"
        width="720" height="112"
        @if($showMutationPriceSection)
        data-mutations='@json($mpdMutations)'
        @else
        data-price-history='@json($priceHistoryRows->values()->all())'
        @endif
        aria-label="{{ $displayName }} 30D price history"
      ></canvas>
      <div id="sabCiPriceEmpty" class="sab-ci-chart-empty">No price history yet</div>

      <div class="mt-4 flex items-center justify-between gap-4 text-sm">
        <span id="sabCiPriceRange" class="font-semibold text-slate-500">
          @if($pricePointCount === 1 && $priceHistoryDate)
          1 price point · {{ \Illuminate\Support\Carbon::parse($priceHistoryDate)->format('m-d') }}
          @elseif($pricePointCount > 1)
          {{ $pricePointCount }} days
          @endif
        </span>
        <span id="sabCiPriceCurVal" class="font-black text-amber-300">
          @if($priceHistoryLatestValue !== null)
          {{ number_format($priceHistoryLatestValue, $priceHistoryLatestValue >= 1000 ? 0 : 2) }} ROBUX
          @endif
        </span>
      </div>
    </div>
  </section>
  @endif

  {{-- Mutations table --}}
  @if(!empty($mutationRows) && count($mutationRows) > 1)
  <section id="mutations" class="mb-6 scroll-mt-32" style="scroll-margin-top:8rem">
    <h2 class="text-base font-bold mb-3">Mutations</h2>
    <div class="rounded-xl border border-white/10 bg-slate-900 shadow-sm shadow-black/20 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm" data-ci-variants-table>
          <thead>
            <tr class="border-b border-white/10">
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Variant</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider" aria-sort="none">
                <button type="button" class="-mx-2 -my-2 inline-flex items-center gap-1.5 rounded-md bg-amber-400/10 px-2.5 py-1.5 uppercase tracking-wider text-amber-200 hover:bg-amber-400/20" title="Sort by value" data-ci-value-sort>
                  <span>Value</span>
                  <span class="rounded bg-amber-400/10 px-1.5 py-0.5 text-[10px] font-black tracking-normal text-amber-200" data-ci-sort-icon>↕ Sort</span>
                </button>
              </th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider hidden sm:table-cell">Demand</th>
            </tr>
          </thead>
          <tbody data-ci-variants-body>
            @foreach($mutationRows as $mut)
            @php
              $mutName = $mut['name'] ?? '—';
              $mutValue = isset($mut['robuxValue']) ? (float) $mut['robuxValue'] : null;
              $mutDemand = $mut['demand'] ?? null;
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
                'Default'     => '#94a3b8',
              ];
              $dotColor = null;
              foreach ($colorMap as $kw => $clr) {
                if (stripos((string) $mutName, $kw) !== false) { $dotColor = $clr; break; }
              }
            @endphp
            <tr class="border-b border-white/5 hover:bg-white/5" data-value-sort="{{ $mutValue !== null ? $mutValue : '' }}">
              <td class="px-4 py-3 font-medium">
                @if($dotColor)
                  @if(str_starts_with($dotColor, 'linear-gradient'))
                  <span style="background:{{ $dotColor }};-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">{{ $mutName }}</span>
                  @else
                  <span style="color:{{ $dotColor }}">{{ $mutName }}</span>
                  @endif
                @else
                {{ $mutName }}
                @endif
              </td>
              <td class="px-4 py-3 font-mono text-amber-300">{{ $mutValue !== null ? number_format($mutValue) : '—' }}</td>
              <td class="px-4 py-3 text-slate-400 hidden sm:table-cell">{{ $mutDemand ?: '—' }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </section>
  @endif

  {{-- FAQ --}}
  <section id="faq" class="mb-12 scroll-mt-32" style="scroll-margin-top:8rem">
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

  {{-- About --}}
  @if($hasAboutContent)
  <section id="about" class="mb-12 scroll-mt-32" style="scroll-margin-top:8rem">
    <h2 class="mb-4 text-xl font-black text-slate-100">About {{ $displayName }}</h2>
    <div class="grid gap-4">
      @if($hasLocalContent)
      <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
        <div class="leading-7 text-slate-300 [&_em]:text-slate-200 [&_strong]:font-bold [&_strong]:text-slate-100 [&_p+p]:mt-2">
          @if($localDescription !== '')
          <p>{{ $localDescription }}</p>
          @endif
          @if($rotRocksDescHtml !== '')
          {!! $rotRocksDescHtml !!}
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
        @if(count($mutationSentences) > 0)
        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
          <h3 class="text-lg font-bold text-slate-100">Mutations</h3>
          <ul class="mt-2 list-none space-y-1 leading-7 text-slate-300">
            @foreach($mutationSentences as $sentence)
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
      @endif
    </div>
  </section>
  @endif

</div>

@endsection

@section('scripts')
<script>
(function () {
  /* ── Section nav ───────────────────────────────────────── */
  var nav = document.querySelector('[data-ci-nav]');
  if (nav) {
    var navShell = document.querySelector('[data-ci-nav-shell]');
    var siteHeader = document.querySelector('body > header');
    var links = Array.prototype.slice.call(nav.querySelectorAll('[data-ci-link]'));
    var sections = links
      .map(function (link) {
        var id = link.getAttribute('data-ci-link') || '';
        var section = document.getElementById(id);
        return section ? { id: id, section: section, link: link } : null;
      })
      .filter(Boolean);

    function syncStickyTop() {
      if (!navShell || !siteHeader) return;
      var headerBottom = Math.ceil(siteHeader.getBoundingClientRect().bottom);
      navShell.style.setProperty('--sab-ci-sticky-top', Math.max(0, headerBottom) + 'px');
    }

    function setActive(id) {
      links.forEach(function (link) {
        var isActive = link.getAttribute('data-ci-link') === id;
        link.classList.toggle('is-active', isActive);
        if (isActive) link.setAttribute('aria-current', 'true');
        else link.removeAttribute('aria-current');
      });
    }

    function activeByScroll() {
      var offset = Math.max(120, Math.floor(window.innerHeight * 0.22));
      var current = sections[0];
      sections.forEach(function (e) {
        if (e.section.getBoundingClientRect().top <= offset) current = e;
      });
      if (current) setActive(current.id);
    }

    window.addEventListener('scroll', activeByScroll, { passive: true });
    window.addEventListener('resize', function () { syncStickyTop(); activeByScroll(); });
    syncStickyTop();
    activeByScroll();
  }

  /* ── Price chart ───────────────────────────────────────── */
  var canvas = document.getElementById('sabCiPriceChart');
  if (!canvas) return;

  var emptyEl  = document.getElementById('sabCiPriceEmpty');
  var signalEl = document.getElementById('sabCiPriceSignal');
  var pctEl    = document.getElementById('sabCiPricePct');
  var rangeEl  = document.getElementById('sabCiPriceRange');
  var curValEl = document.getElementById('sabCiPriceCurVal');
  var mutValEl = document.getElementById('sabCiMutValue');
  var mutTabsEl= document.getElementById('sabCiMutTabs');

  var mutations = [];
  try { mutations = JSON.parse(canvas.getAttribute('data-mutations') || '[]'); } catch(e) { mutations = []; }
  var legacyHistory = [];
  if (mutations.length === 0) {
    try { legacyHistory = JSON.parse(canvas.getAttribute('data-price-history') || '[]'); } catch(e) { legacyHistory = []; }
  }

  function normalize(raw) {
    return (raw || [])
      .filter(function (p) { return p && p.value !== null && p.value !== undefined; })
      .map(function (p) { return { date: String(p.date || ''), value: Number(p.value) }; })
      .filter(function (p) { return Number.isFinite(p.value); });
  }

  function fmtDate(s) {
    if (!s) return '';
    var d = new Date(s);
    if (Number.isNaN(d.getTime())) return s;
    return String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  }

  function fmtVal(n) {
    if (n === null || n === undefined || !Number.isFinite(Number(n))) return '—';
    n = Number(n);
    if (n >= 1e9) return '$' + (n / 1e9).toFixed(2) + 'B';
    if (n >= 1e6) return '$' + (n / 1e6).toFixed(2) + 'M';
    if (n >= 1e3) return '$' + (n / 1e3).toFixed(1) + 'K';
    return '$' + n.toFixed(2);
  }

  function fmtRobux(n) {
    if (n === null || n === undefined || !Number.isFinite(Number(n))) return '';
    n = Number(n);
    return n.toLocaleString('en-US', { maximumFractionDigits: n >= 1000 ? 0 : 2 }) + ' ROBUX';
  }

  function updateMeta(history, robuxValue) {
    var lastVal  = history.length > 0 ? history[history.length - 1].value : robuxValue;
    var firstVal = history.length > 0 ? history[0].value : null;
    var pctChange = (history.length >= 2 && firstVal > 0)
      ? ((lastVal - firstVal) / firstVal) * 100 : null;

    if (mutValEl && robuxValue != null) mutValEl.textContent = fmtVal(robuxValue);
    if (curValEl) curValEl.textContent = lastVal != null ? fmtRobux(lastVal) : '';

    if (signalEl && pctEl) {
      if (pctChange !== null) {
        var pos = pctChange >= 0;
        signalEl.textContent = pos ? 'High' : 'Low';
        signalEl.className = 'rounded-full border px-3 py-1 text-xs font-black '
          + (pos ? 'border-emerald-400/40 bg-emerald-400/10 text-emerald-300' : 'border-rose-400/40 bg-rose-400/10 text-rose-300');
        signalEl.classList.remove('hidden');
        pctEl.textContent = (pos ? '+' : '') + pctChange.toFixed(1) + '%';
        pctEl.className = 'text-lg font-black ' + (pos ? 'text-emerald-300' : 'text-rose-300');
        pctEl.classList.remove('hidden');
      } else {
        signalEl.classList.add('hidden');
        pctEl.classList.add('hidden');
      }
    }
    if (rangeEl) {
      if (history.length === 0) rangeEl.textContent = '';
      else if (history.length === 1) rangeEl.textContent = '1 price point · ' + fmtDate(history[0].date);
      else rangeEl.textContent = history.length + ' days · ' + fmtDate(history[0].date) + ' - ' + fmtDate(history[history.length - 1].date);
    }
  }

  function drawHistory(history) {
    history = normalize(history);
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

    var padX = 12, padY = 10;
    var plotW = width - padX * 2, plotH = height - padY * 2;

    ctx.strokeStyle = 'rgba(148,163,184,.12)';
    ctx.lineWidth = 1;
    ctx.setLineDash([5, 5]);
    for (var i = 0; i < 3; i++) {
      var gy = padY + (plotH / 2) * i;
      ctx.beginPath(); ctx.moveTo(padX, gy); ctx.lineTo(width - padX, gy); ctx.stroke();
    }
    ctx.setLineDash([]);

    var values = history.map(function (p) { return p.value; });
    var minVal = Math.min.apply(Math, values);
    var maxVal = Math.max.apply(Math, values);
    var range = maxVal - minVal || Math.max(maxVal, 1);
    var pts = values.map(function (v, idx) {
      return {
        x: history.length === 1 ? width / 2 : padX + (idx / (history.length - 1)) * plotW,
        y: padY + (1 - (v - minVal) / range) * plotH,
      };
    });

    if (history.length === 1) {
      ctx.beginPath(); ctx.arc(pts[0].x, pts[0].y, 5, 0, Math.PI * 2);
      ctx.fillStyle = '#fbbf24'; ctx.fill();
      ctx.lineWidth = 2; ctx.strokeStyle = '#22c55e'; ctx.stroke();
      return;
    }

    var firstV = values[0], lastV = values[values.length - 1];
    var isPos = lastV >= firstV;
    var lineColor = isPos ? '#22c55e' : '#ef4444';
    var grad = ctx.createLinearGradient(0, padY, 0, height);
    grad.addColorStop(0, isPos ? 'rgba(34,197,94,.24)' : 'rgba(239,68,68,.22)');
    grad.addColorStop(1, isPos ? 'rgba(34,197,94,.02)' : 'rgba(239,68,68,.02)');

    ctx.beginPath(); ctx.moveTo(pts[0].x, pts[0].y);
    for (var j = 1; j < pts.length; j++) {
      var cpX = (pts[j-1].x + pts[j].x) / 2;
      ctx.bezierCurveTo(cpX, pts[j-1].y, cpX, pts[j].y, pts[j].x, pts[j].y);
    }
    ctx.lineTo(pts[pts.length-1].x, height - padY);
    ctx.lineTo(pts[0].x, height - padY);
    ctx.closePath(); ctx.fillStyle = grad; ctx.fill();

    ctx.beginPath(); ctx.moveTo(pts[0].x, pts[0].y);
    for (var k = 1; k < pts.length; k++) {
      var cp = (pts[k-1].x + pts[k].x) / 2;
      ctx.bezierCurveTo(cp, pts[k-1].y, cp, pts[k].y, pts[k].x, pts[k].y);
    }
    ctx.strokeStyle = lineColor; ctx.lineWidth = 3; ctx.stroke();

    var minIdx = values.indexOf(minVal), maxIdx = values.indexOf(maxVal);
    [[minIdx, '#f59e0b'], [maxIdx, '#fbbf24']].forEach(function (e) {
      ctx.beginPath(); ctx.arc(pts[e[0]].x, pts[e[0]].y, 4, 0, Math.PI * 2);
      ctx.fillStyle = e[1]; ctx.fill();
    });
  }

  var currentHistory = mutations.length > 0 ? normalize(mutations[0].priceHistory) : normalize(legacyHistory);
  var currentRobux   = mutations.length > 0 ? mutations[0].robuxValue : null;

  function renderCurrent() { updateMeta(currentHistory, currentRobux); drawHistory(currentHistory); }

  if (mutTabsEl && mutations.length > 0) {
    mutTabsEl.querySelectorAll('.sab-ci-mut-tab').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var idx = Number(btn.getAttribute('data-mut-idx'));
        if (!Number.isFinite(idx) || !mutations[idx]) return;
        mutTabsEl.querySelectorAll('.sab-ci-mut-tab').forEach(function (b) { b.classList.remove('is-active'); });
        btn.classList.add('is-active');
        currentHistory = normalize(mutations[idx].priceHistory);
        currentRobux = mutations[idx].robuxValue;
        renderCurrent();
      });
    });
  }

  if (currentHistory.length === 0 && currentRobux == null && legacyHistory.length === 0) return;
  renderCurrent();
  window.addEventListener('resize', renderCurrent, { passive: true });

  /* ── Mutations table sort ──────────────────────────────── */
  var tbl  = document.querySelector('[data-ci-variants-table]');
  var tbody = document.querySelector('[data-ci-variants-body]');
  var sortBtn = document.querySelector('[data-ci-value-sort]');
  var sortIcon = document.querySelector('[data-ci-sort-icon]');
  if (tbl && tbody && sortBtn) {
    var direction = '';
    sortBtn.addEventListener('click', function () {
      direction = direction === 'desc' ? 'asc' : 'desc';
      var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr')).map(function (row, i) {
        var v = row.getAttribute('data-value-sort');
        return { row: row, idx: i, value: v === '' || v === null ? null : Number(v) };
      });
      rows.slice().sort(function (a, b) {
        if (a.value === null && b.value === null) return a.idx - b.idx;
        if (a.value === null) return 1;
        if (b.value === null) return -1;
        if (a.value === b.value) return a.idx - b.idx;
        return direction === 'desc' ? b.value - a.value : a.value - b.value;
      }).forEach(function (e) { tbody.appendChild(e.row); });
      if (sortIcon) sortIcon.textContent = direction === 'desc' ? '↓ High to low' : '↑ Low to high';
    });
  }
})();
</script>
@endsection
