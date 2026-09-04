@extends('seo.sab.layout')

@section('content')
@php
  $listPerPage = (int) ($listPerPage ?? \App\Services\Seo\SabRenderService::VALUE_LIST_PER_PAGE);
  $ssrRows = $ssrValueListRows ?? array_slice($valueListRows ?? [], 0, $listPerPage);
  $trackedTotal = (int) ($listStatTotal ?? count($valueListRows ?? []));
  $demandFilters = ['ALL', 'AMAZING', 'HIGH', 'NORMAL', 'LOW', 'TERRIBLE'];
  $trendFilters = ['ALL', 'RISING', 'STABLE', 'LOWERING'];
  $formatCompactRobux = static function (?float $value): string {
    if ($value === null) {
      return '—';
    }
    $abs = abs($value);
    if ($abs >= 1_000_000) {
      return rtrim(rtrim(number_format($value / 1_000_000, 1, '.', ''), '0'), '.') . 'M';
    }
    if ($abs >= 1_000) {
      return rtrim(rtrim(number_format($value / 1_000, 1, '.', ''), '0'), '.') . 'k';
    }

    return number_format($value);
  };
  $demandClass = static function (string $label): string {
    $key = strtolower(trim($label));
    return match (true) {
      in_array($key, ['amazing', 'insane', 'high', 'booming'], true) => 'is-high',
      in_array($key, ['normal', 'medium'], true) => 'is-mid',
      $key === 'low' => 'is-low',
      $key === 'terrible' => 'is-bad',
      default => 'is-flat',
    };
  };
  $trendClass = static function (string $label): string {
    $key = strtolower(trim($label));
    return match ($key) {
      'rising' => 'is-up',
      'lowering' => 'is-down',
      default => 'is-flat',
    };
  };
  $rarityClass = static function (string $key): string {
    return match (strtolower(trim($key))) {
      'og' => 'is-og',
      'secret' => 'is-secret',
      'brainrot god', 'brainrot_god' => 'is-god',
      'legendary' => 'is-legendary',
      'mythic' => 'is-mythic',
      default => 'is-flat',
    };
  };
@endphp
<style>
  #brainrot-search { color: #f1f5f9; }
  #brainrot-search::placeholder {
    color: #475569;
    opacity: 1;
  }
  #brainrot-search::-webkit-input-placeholder { color: #475569; }
  #brainrot-search::-moz-placeholder { color: #475569; opacity: 1; }
  #brainrot-search:-webkit-autofill,
  #brainrot-search:-webkit-autofill:hover,
  #brainrot-search:-webkit-autofill:focus {
    -webkit-box-shadow: 0 0 0 100px #0f172a inset;
    -webkit-text-fill-color: #f1f5f9;
    caret-color: #f1f5f9;
    transition: background-color 99999s ease-out 0s;
  }
  .sab-vl-hero-label {
    display: inline-flex;
    align-items: center;
    gap: .375rem;
    border: 1px solid rgba(34, 211, 238, .28);
    background: rgba(6, 182, 212, .1);
    border-radius: 9999px;
    color: #67e8f9;
    font-size: .6875rem;
    font-weight: 800;
    letter-spacing: .08em;
    padding: .25rem .625rem;
    text-transform: uppercase;
  }
  .sab-vl-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: stretch;
    gap: .625rem;
    margin-bottom: .5rem;
  }
  .sab-vl-meta-line {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .25rem .35rem;
    margin: 0 0 .875rem;
    color: #94a3b8;
    font-size: .8125rem;
    font-variant-numeric: tabular-nums;
  }
  .sab-vl-meta-num {
    color: #22d3ee;
    font-size: 1rem;
    font-weight: 900;
    letter-spacing: -.02em;
  }
  .sab-vl-today {
    margin-top: .875rem;
    max-width: 36rem;
  }
  .sab-vl-today__title {
    margin: 0;
    color: #cbd5e1;
    font-size: .6875rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
  }
  .sab-vl-today__list {
    display: grid;
    gap: .375rem;
    margin-top: .5rem;
  }
  .sab-vl-today__row {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: .35rem .5rem;
    color: #94a3b8;
    font-size: .8125rem;
  }
  .sab-vl-today__label {
    min-width: 5.5rem;
    font-weight: 700;
  }
  .sab-vl-today__name {
    color: #e2e8f0;
    font-weight: 700;
    text-decoration: none;
  }
  a.sab-vl-today__name:hover {
    color: #67e8f9;
  }
  .sab-vl-today__pct.is-up { color: #4ade80; font-weight: 800; }
  .sab-vl-today__pct.is-down { color: #fb7185; font-weight: 800; }
  .sab-vl-today__empty {
    margin: .5rem 0 0;
    color: #64748b;
    font-size: .8125rem;
  }
  .sab-vl-search {
    position: relative;
    flex: 1 1 16rem;
    min-width: 0;
  }
  .sab-vl-search svg {
    position: absolute;
    left: .875rem;
    top: 50%;
    transform: translateY(-50%);
    width: 1rem;
    height: 1rem;
    color: #64748b;
    pointer-events: none;
  }
  .sab-vl-search input {
    width: 100%;
    min-height: 2.75rem;
    border: 1px solid rgba(148, 163, 184, .22);
    border-radius: .75rem;
    background: rgba(15, 23, 42, .75);
    color: #f1f5f9;
    font-size: .9375rem;
    font-weight: 600;
    outline: none;
    padding: .7rem .875rem .7rem 2.5rem;
  }
  .sab-vl-search input:focus {
    border-color: rgba(34, 211, 238, .45);
    box-shadow: 0 0 0 3px rgba(6, 182, 212, .16);
  }
  .sab-vl-sort {
    position: relative;
    flex: 0 0 auto;
  }
  .sab-vl-sort select {
    appearance: none;
    min-height: 2.75rem;
    min-width: 8.5rem;
    border: 1px solid rgba(148, 163, 184, .22);
    border-radius: .75rem;
    background: rgba(15, 23, 42, .75);
    color: #e2e8f0;
    font-size: .875rem;
    font-weight: 700;
    padding: .7rem 2.25rem .7rem .875rem;
    cursor: pointer;
  }
  .sab-vl-sort svg {
    position: absolute;
    right: .7rem;
    top: 50%;
    transform: translateY(-50%);
    width: 1rem;
    height: 1rem;
    color: #64748b;
    pointer-events: none;
  }
  .sab-vl-filters {
    display: grid;
    gap: .625rem;
    margin-bottom: 1.25rem;
  }
  .sab-vl-filter-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
  }
  .sab-vl-filter-label {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    color: #94a3b8;
    font-size: .6875rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    min-width: 4.5rem;
  }
  .sab-vl-chip {
    border: 1px solid rgba(148, 163, 184, .22);
    border-radius: .5rem;
    background: rgba(15, 23, 42, .55);
    color: #94a3b8;
    cursor: pointer;
    font-size: .625rem;
    font-weight: 800;
    letter-spacing: .08em;
    padding: .3rem .625rem;
    text-transform: uppercase;
    transition: border-color .15s ease, color .15s ease, background .15s ease;
  }
  .sab-vl-chip:hover {
    border-color: rgba(148, 163, 184, .4);
    color: #e2e8f0;
  }
  .sab-vl-chip.is-active {
    background: rgba(226, 232, 240, .1);
    border-color: rgba(226, 232, 240, .35);
    color: #f8fafc;
  }
  .sab-vl-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  @media (min-width: 640px) {
    .sab-vl-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  }
  @media (min-width: 1024px) {
    .sab-vl-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  }
  @media (min-width: 1280px) {
    .sab-vl-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
  }
  .sab-vl-card {
    display: flex;
    flex-direction: column;
    gap: .75rem;
    min-width: 0;
    border: 1px solid rgba(148, 163, 184, .16);
    border-radius: 1rem;
    background: linear-gradient(180deg, rgba(255,255,255,.03), transparent);
    padding: 1rem;
    text-decoration: none;
    color: inherit;
    transition: border-color .15s ease, transform .15s ease, background .15s ease;
  }
  a.sab-vl-card:hover,
  a.sab-vl-card:focus-visible {
    border-color: rgba(34, 211, 238, .4);
    outline: none;
    transform: translateY(-1px);
  }
  .sab-vl-card__head {
    display: flex;
    align-items: flex-start;
    gap: .75rem;
    min-width: 0;
  }
  .sab-vl-card__thumb {
    position: relative;
    width: 3.5rem;
    height: 3.5rem;
    flex-shrink: 0;
    overflow: hidden;
    border: 1px solid rgba(148, 163, 184, .22);
    border-radius: .75rem;
    background: #0f172a;
  }
  .sab-vl-card__thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  .sab-vl-card__title {
    margin: 0;
    color: #f1f5f9;
    font-size: .875rem;
    font-weight: 800;
    line-height: 1.25;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .sab-vl-card__badges {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .375rem;
    margin-top: .35rem;
  }
  .sab-vl-badge {
    display: inline-flex;
    align-items: center;
    gap: .2rem;
    min-width: 0;
    padding: .15rem .4rem;
    border-radius: .375rem;
    border: 1px solid transparent;
    font-size: .5625rem;
    font-weight: 800;
    letter-spacing: .08em;
    line-height: 1.25;
    text-transform: uppercase;
    white-space: nowrap;
  }
  .sab-vl-badge.is-up,
  .sab-vl-badge.is-high {
    background: rgba(34,197,94,.12);
    color: #4ade80;
    border-color: rgba(74,222,128,.28);
  }
  .sab-vl-badge.is-down,
  .sab-vl-badge.is-bad {
    background: rgba(244,63,94,.1);
    color: #fb7185;
    border-color: rgba(251,113,133,.28);
  }
  .sab-vl-badge.is-flat,
  .sab-vl-badge.is-mid {
    background: rgba(148,163,184,.1);
    color: #cbd5e1;
    border-color: rgba(148,163,184,.22);
  }
  .sab-vl-badge.is-low {
    background: rgba(251,146,60,.1);
    color: #fdba74;
    border-color: rgba(251,146,60,.28);
  }
  .sab-vl-badge.is-og {
    background: rgba(245,158,11,.12);
    color: #fbbf24;
    border-color: rgba(245,158,11,.28);
  }
  .sab-vl-badge.is-secret {
    background: rgba(168,85,247,.12);
    color: #c084fc;
    border-color: rgba(168,85,247,.28);
  }
  .sab-vl-badge.is-god {
    background: rgba(244,63,94,.12);
    color: #fb7185;
    border-color: rgba(244,63,94,.28);
  }
  .sab-vl-badge.is-legendary {
    background: rgba(250,204,21,.12);
    color: #fde047;
    border-color: rgba(250,204,21,.28);
  }
  .sab-vl-badge.is-mythic {
    background: rgba(56,189,248,.12);
    color: #7dd3fc;
    border-color: rgba(56,189,248,.28);
  }
  .sab-vl-trend-icon {
    width: .75rem;
    height: .75rem;
    flex-shrink: 0;
  }
  .sab-vl-trend-icon.is-up { color: #4ade80; }
  .sab-vl-trend-icon.is-down { color: #fb7185; }
  .sab-vl-trend-icon.is-flat { color: #64748b; }
  .sab-vl-value-box {
    border: 1px solid rgba(34, 211, 238, .22);
    border-radius: .75rem;
    background: rgba(6, 182, 212, .08);
    padding: .625rem .75rem;
  }
  .sab-vl-value-box__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
  }
  .sab-vl-value-box__label {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    color: #67e8f9;
    font-size: .625rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
  }
  .sab-vl-value-box__amount {
    display: inline-flex;
    align-items: baseline;
    gap: .25rem;
    color: #f8fafc;
    font-size: 1.125rem;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
    letter-spacing: -.02em;
  }
  .sab-vl-meta {
    display: grid;
    gap: .35rem;
    border: 1px solid rgba(148, 163, 184, .14);
    border-radius: .75rem;
    background: rgba(15, 23, 42, .45);
    padding: .625rem .75rem;
    color: #94a3b8;
    font-size: .6875rem;
  }
  .sab-vl-meta__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
  }
  .sab-vl-meta__row strong {
    color: #e2e8f0;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
  }
  .sab-vl-delta.is-up { color: #4ade80; }
  .sab-vl-delta.is-down { color: #fb7185; }
  .sab-vl-delta.is-flat { color: #64748b; }
  .sab-vl-muts {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem .5rem;
    color: #64748b;
    font-size: .5625rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
  }
  .sab-vl-muts span + span::before {
    content: "·";
    margin-right: .5rem;
    color: #475569;
  }
  .sab-vl-empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 2rem 1rem;
    color: #94a3b8;
    font-size: .875rem;
  }
  .sab-vl-load-wrap {
    display: flex;
    justify-content: center;
    margin-top: 1.25rem;
  }
  .sab-vl-load-more {
    min-height: 2.5rem;
    border: 1px solid rgba(34, 211, 238, .35);
    border-radius: .75rem;
    background: rgba(6, 182, 212, .1);
    color: #67e8f9;
    cursor: pointer;
    font-size: .8125rem;
    font-weight: 800;
    padding: .55rem 1rem;
  }
  .sab-vl-load-more:hover {
    background: rgba(6, 182, 212, .18);
  }
  .sab-vl-load-more[hidden] { display: none; }
</style>

<header class="py-5">
  <span class="sab-vl-hero-label">Live market index</span>
  <h1 class="mt-3 text-3xl md:text-4xl font-black tracking-tight text-slate-100">
    {{ $t['value_list_h1'] }}
  </h1>
  <p class="mt-3 text-sm leading-relaxed text-slate-400">{{ $t['value_list_intro'] }}</p>
  @if(!empty($calculatorLastUpdatedLabel))
  <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
    {{ $lastUpdateLabel ?? 'Last update' }}: <time datetime="{{ $calculatorLastUpdatedAt }}">{{ $calculatorLastUpdatedLabel }}</time>
    @if(!empty($valueChangesHref ?? null))
    <span aria-hidden="true"> · </span><a href="{{ $valueChangesHref }}" class="text-cyan-300 underline hover:text-cyan-100">{{ $valueTrendsLabel ?? 'View Daily Value Trends' }}</a>
    @endif
  </p>
  @endif
  @if(($locale ?? 'en') === 'en')
  @php
    $adminAbuseHref = rtrim((string) ($urlPrefix ?? ''), '/') . '/' . \App\Services\Seo\SabWikiPageDefinitions::PAGE_WIKI_ADMIN_ABUSE;
  @endphp
  <div class="mt-3 inline-flex max-w-full items-center gap-2 text-left text-xs font-semibold leading-5 text-cyan-200">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4 shrink-0 text-cyan-300" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 14h3l7 4V6L7 10H4v4Z"/>
      <path stroke-linecap="round" stroke-linejoin="round" d="M17 9a3 3 0 0 1 0 6M20 7a6 6 0 0 1 0 10"/>
    </svg>
    <a href="{{ $adminAbuseHref }}" class="font-semibold underline hover:text-cyan-100">New · Steal a Brainrot Admin Abuse Time Today</a>
  </div>
  @endif
  @php
    $todayTopGainer = is_array($todayTopGainer ?? null) ? $todayTopGainer : null;
    $todayTopLoser = is_array($todayTopLoser ?? null) ? $todayTopLoser : null;
  @endphp
  <div class="sab-vl-today">
    <h2 class="sab-vl-today__title">Today's summary</h2>
    @if($todayTopGainer || $todayTopLoser)
    <div class="sab-vl-today__list">
      <div class="sab-vl-today__row">
        <span class="sab-vl-today__label">Biggest gain</span>
        @if($todayTopGainer)
          @if(!empty($todayTopGainer['productUrl']))
          <a class="sab-vl-today__name" href="{{ $todayTopGainer['productUrl'] }}">{{ $todayTopGainer['itemName'] ?? '—' }}</a>
          @else
          <span class="sab-vl-today__name">{{ $todayTopGainer['itemName'] ?? '—' }}</span>
          @endif
          <span class="sab-vl-today__pct is-up">{{ $todayTopGainer['deltaPctLabel'] ?? '—' }}</span>
          <span>{{ $todayTopGainer['after'] ?? '—' }}</span>
        @else
          <span>—</span>
        @endif
      </div>
      <div class="sab-vl-today__row">
        <span class="sab-vl-today__label">Biggest drop</span>
        @if($todayTopLoser)
          @if(!empty($todayTopLoser['productUrl']))
          <a class="sab-vl-today__name" href="{{ $todayTopLoser['productUrl'] }}">{{ $todayTopLoser['itemName'] ?? '—' }}</a>
          @else
          <span class="sab-vl-today__name">{{ $todayTopLoser['itemName'] ?? '—' }}</span>
          @endif
          <span class="sab-vl-today__pct is-down">{{ $todayTopLoser['deltaPctLabel'] ?? '—' }}</span>
          <span>{{ $todayTopLoser['after'] ?? '—' }}</span>
        @else
          <span>—</span>
        @endif
      </div>
    </div>
    @else
    <p class="sab-vl-today__empty">No movers today yet.</p>
    @endif
  </div>
</header>

<section id="list" class="mb-16 mt-2 scroll-mt-20">
  <div class="sab-vl-toolbar">
    <label class="sab-vl-search">
      <span class="sr-only">{{ $t['search_placeholder'] ?? 'Search' }}</span>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
      </svg>
      <input id="brainrot-search" type="search" placeholder="{{ $t['search_placeholder'] }}" autocomplete="off" />
    </label>
    <label class="sab-vl-sort">
      <span class="sr-only">Sort by</span>
      <select id="brainrot-sort" aria-label="Sort items">
        <option value="value_desc" selected>Value ↓</option>
        <option value="value_asc">Value ↑</option>
        <option value="name_asc">A–Z</option>
      </select>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
      </svg>
    </label>
  </div>
  <p class="sab-vl-meta-line">
    <span id="brainrot-tracked-count"><strong class="sab-vl-meta-num">{{ number_format($trackedTotal) }}</strong> brainrots tracked</span>
    <span aria-hidden="true">·</span>
    <span id="brainrot-search-count" aria-live="polite"><strong class="sab-vl-meta-num">{{ number_format(min($listPerPage, $trackedTotal)) }}</strong> matches</span>
  </p>

  <div class="sab-vl-filters">
    <div class="sab-vl-filter-row" role="group" aria-label="Filter by demand" data-filter-group="demand">
      <span class="sab-vl-filter-label">Demand</span>
      @foreach($demandFilters as $filter)
        <button type="button" class="sab-vl-chip{{ $filter === 'ALL' ? ' is-active' : '' }}" data-demand-filter="{{ $filter }}" aria-pressed="{{ $filter === 'ALL' ? 'true' : 'false' }}">{{ $filter }}</button>
      @endforeach
    </div>
    <div class="sab-vl-filter-row" role="group" aria-label="Filter by trend" data-filter-group="trend">
      <span class="sab-vl-filter-label">Trend</span>
      @foreach($trendFilters as $filter)
        <button type="button" class="sab-vl-chip{{ $filter === 'ALL' ? ' is-active' : '' }}" data-trend-filter="{{ $filter }}" aria-pressed="{{ $filter === 'ALL' ? 'true' : 'false' }}">{{ $filter }}</button>
      @endforeach
    </div>
  </div>

  <div id="brainrot-list" class="sab-vl-grid" data-values-grid>
    @foreach($ssrRows as $row)
    @php
      $item = $row['item'];
      $canOpenProduct = (bool) ($row['canOpenProduct'] ?? false);
      $productUrl = $row['productUrl'] ?? '#';
      $direction = $row['direction'] ?? 'stable';
      $changeClass = match ($direction) {
        'up' => 'is-up',
        'down' => 'is-down',
        default => 'is-flat',
      };
      $demandLabel = (string) ($row['demandLabel'] ?? '—');
      $trendLabel = (string) ($row['trendLabel'] ?? '—');
      $rarityKey = (string) ($row['rarityKey'] ?? '');
      $rarityLabel = (string) ($row['rarityLabel'] ?? '');
      $mutations = array_values($row['mutationLabels'] ?? []);
      $visibleMuts = array_slice($mutations, 0, 4);
      $extraMuts = max(0, count($mutations) - count($visibleMuts));
      $currentValue = is_numeric($row['currentValue'] ?? null) ? (float) $row['currentValue'] : null;
      $tag = $canOpenProduct ? 'a' : 'div';
      $hrefAttr = $canOpenProduct ? ' href="'.e($productUrl).'"' : '';
    @endphp
    <{{ $tag }} class="sab-vl-card"{{ $hrefAttr }}>
      <div class="sab-vl-card__head">
        <div class="sab-vl-card__thumb">
          @if(!empty($row['imageSrc']))
          <img src="{{ $row['imageSrc'] }}" alt="{{ $item->name }}" width="56" height="56" loading="lazy" />
          @endif
        </div>
        <div class="min-w-0 flex-1">
          <h3 class="sab-vl-card__title">{{ $item->name }}</h3>
          <div class="sab-vl-card__badges">
            @if($rarityLabel !== '')
            <span class="sab-vl-badge {{ $rarityClass($rarityKey) }}">{{ $rarityLabel }}</span>
            @endif
            @if($demandLabel !== '—')
            <span class="sab-vl-badge {{ $demandClass($demandLabel) }}">{{ $demandLabel }}</span>
            @endif
            <svg class="sab-vl-trend-icon {{ $trendClass($trendLabel) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              @if(strtolower($trendLabel) === 'rising')
              <path d="M16 7h6v6"/><path d="m22 7-8.5 8.5-5-5L2 17"/>
              @elseif(strtolower($trendLabel) === 'lowering')
              <path d="M16 17h6v-6"/><path d="m22 17-8.5-8.5-5 5L2 7"/>
              @else
              <path d="M5 12h14"/>
              @endif
            </svg>
          </div>
        </div>
      </div>
      <div class="sab-vl-value-box">
        <div class="sab-vl-value-box__row">
          <span class="sab-vl-value-box__label">Robux</span>
          <span class="sab-vl-value-box__amount">
            <span>{{ $formatCompactRobux($currentValue) }}</span>
          </span>
        </div>
      </div>
      <div class="sab-vl-meta">
        <div class="sab-vl-meta__row"><span>Previous</span><strong>{{ $row['previousValueLabel'] ?? '—' }}</strong></div>
        <div class="sab-vl-meta__row">
          <span>Change</span>
          <strong class="sab-vl-delta {{ $changeClass }}">
            {{ $row['directionLabel'] ?? 'Stable' }}
            @if(($row['deltaPctLabel'] ?? '—') !== '—') {{ $row['deltaPctLabel'] }} @endif
            @if(($row['deltaLabel'] ?? '—') !== '—') · {{ $row['deltaLabel'] }} @endif
          </strong>
        </div>
        <div class="sab-vl-meta__row"><span>Trend</span><strong>{{ $trendLabel }}</strong></div>
      </div>
      @if($visibleMuts !== [])
      <div class="sab-vl-muts">
        @foreach($visibleMuts as $mutation)
          <span>{{ $mutation }}</span>
        @endforeach
        @if($extraMuts > 0)
          <span>+{{ $extraMuts }}</span>
        @endif
      </div>
      @endif
    </{{ $tag }}>
    @endforeach
  </div>

  <div id="brainrot-search-empty" class="sab-vl-empty hidden" aria-live="polite">
    <p class="font-semibold text-slate-100">{{ $t['search_empty'] }}</p>
  </div>

  <div class="sab-vl-load-wrap">
    <button type="button" id="brainrot-load-more" class="sab-vl-load-more" hidden>Load more</button>
  </div>

  <p class="text-xs text-slate-500 mt-4">{{ $t['value_list_updated'] }}</p>
</section>

<section id="faq" class="mb-16 scroll-mt-20">
  <h2 class="text-xl font-black text-slate-100 mb-4">{{ $t['value_list_faq_h2'] ?? 'Steal a Brainrot Value List FAQ' }}</h2>
  <div class="grid gap-4 md:grid-cols-2">
    @foreach($valueListFaqItems ?? [] as $faqItem)
      <article class="rounded-lg border border-white/10 bg-slate-900/60 p-4">
        <h3 class="font-bold text-slate-100">{{ $faqItem['question'] }}</h3>
        <p class="text-slate-400 text-sm mt-2 leading-relaxed">{!! $faqItem['answer'] !!}</p>
      </article>
    @endforeach
  </div>
</section>
@endsection

@section('scripts')
<script>
  (() => {
    const PER_PAGE = {{ (int) $listPerPage }};
    const PRODUCT_PREFIX = {!! json_encode($productUrlPrefix ?? '') !!};
    const ALL_ROWS = {!! json_encode($listRows ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!};
    const input = document.getElementById('brainrot-search');
    const sortSelect = document.getElementById('brainrot-sort');
    const grid = document.getElementById('brainrot-list');
    const count = document.getElementById('brainrot-search-count');
    const empty = document.getElementById('brainrot-search-empty');
    const loadMoreBtn = document.getElementById('brainrot-load-more');
    const total = ALL_ROWS.length;
    const normalize = v => String(v || '').toLowerCase().replace(/\s+/g, ' ').trim();
    const esc = s => String(s ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');

    let visibleCount = Math.min(PER_PAGE, total);
    let demandFilter = 'ALL';
    let trendFilter = 'ALL';

    const demandClass = label => {
      const key = normalize(label);
      if (['amazing', 'insane', 'high', 'booming'].includes(key)) return 'is-high';
      if (['normal', 'medium'].includes(key)) return 'is-mid';
      if (key === 'low') return 'is-low';
      if (key === 'terrible') return 'is-bad';
      return 'is-flat';
    };
    const trendClass = label => {
      const key = normalize(label);
      if (key === 'rising') return 'is-up';
      if (key === 'lowering') return 'is-down';
      return 'is-flat';
    };
    const changeClass = direction => {
      if (direction === 'up') return 'is-up';
      if (direction === 'down') return 'is-down';
      return 'is-flat';
    };
    const rarityClass = key => {
      const k = normalize(key);
      if (k === 'og') return 'is-og';
      if (k === 'secret') return 'is-secret';
      if (k === 'brainrot god' || k === 'brainrot_god') return 'is-god';
      if (k === 'legendary') return 'is-legendary';
      if (k === 'mythic') return 'is-mythic';
      return 'is-flat';
    };
    const formatCompact = value => {
      if (value === null || value === undefined || Number.isNaN(Number(value))) return '—';
      const n = Number(value);
      const abs = Math.abs(n);
      if (abs >= 1_000_000) return `${Number((n / 1_000_000).toFixed(1)).toString()}M`;
      if (abs >= 1_000) return `${Number((n / 1_000).toFixed(1)).toString()}k`;
      return String(Math.round(n));
    };
    const trendIcon = label => {
      const key = normalize(label);
      if (key === 'rising') {
        return '<svg class="sab-vl-trend-icon is-up" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 7h6v6"/><path d="m22 7-8.5 8.5-5-5L2 17"/></svg>';
      }
      if (key === 'lowering') {
        return '<svg class="sab-vl-trend-icon is-down" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 17h6v-6"/><path d="m22 17-8.5-8.5-5 5L2 7"/></svg>';
      }
      return '<svg class="sab-vl-trend-icon is-flat" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14"/></svg>';
    };

    const cardHtml = row => {
      const canOpen = !!row.link && !!row.s;
      const href = canOpen ? `${PRODUCT_PREFIX}/products/${row.s}` : '';
      const tagOpen = canOpen ? `<a class="sab-vl-card" href="${esc(href)}">` : '<div class="sab-vl-card">';
      const tagClose = canOpen ? '</a>' : '</div>';
      const thumb = row.img
        ? `<img src="${esc(row.img)}" alt="${esc(row.n)}" width="56" height="56" loading="lazy" />`
        : '';
      const rarity = row.r
        ? `<span class="sab-vl-badge ${rarityClass(row.rk || '')}">${esc(row.r)}</span>`
        : '';
      const demand = row.dm && row.dm !== '—'
        ? `<span class="sab-vl-badge ${demandClass(row.dm)}">${esc(row.dm)}</span>`
        : '';
      const cClass = changeClass(row.d || 'stable');
      const pct = (row.dp && row.dp !== '—') ? ` ${esc(row.dp)}` : '';
      const delta = (row.dd && row.dd !== '—') ? ` · ${esc(row.dd)}` : '';
      const muts = Array.isArray(row.mut) ? row.mut : [];
      const shown = muts.slice(0, 4);
      const extra = Math.max(0, muts.length - shown.length);
      const mutHtml = shown.length
        ? `<div class="sab-vl-muts">${shown.map(m => `<span>${esc(m)}</span>`).join('')}${extra > 0 ? `<span>+${extra}</span>` : ''}</div>`
        : '';
      return `${tagOpen}
        <div class="sab-vl-card__head">
          <div class="sab-vl-card__thumb">${thumb}</div>
          <div class="min-w-0 flex-1">
            <h3 class="sab-vl-card__title">${esc(row.n)}</h3>
            <div class="sab-vl-card__badges">${rarity}${demand}${trendIcon(row.tr || '')}</div>
          </div>
        </div>
        <div class="sab-vl-value-box">
          <div class="sab-vl-value-box__row">
            <span class="sab-vl-value-box__label">Robux</span>
            <span class="sab-vl-value-box__amount"><span>${esc(formatCompact(row.cvn))}</span></span>
          </div>
        </div>
        <div class="sab-vl-meta">
          <div class="sab-vl-meta__row"><span>Previous</span><strong>${esc(row.pv || '—')}</strong></div>
          <div class="sab-vl-meta__row"><span>Change</span><strong class="sab-vl-delta ${cClass}">${esc(row.dl || 'Stable')}${pct}${delta}</strong></div>
          <div class="sab-vl-meta__row"><span>Trend</span><strong>${esc(row.tr || '—')}</strong></div>
        </div>
        ${mutHtml}
      ${tagClose}`;
    };

    const sortRows = rows => {
      const mode = sortSelect?.value || 'value_desc';
      return rows.slice().sort((a, b) => {
        if (mode === 'name_asc') return String(a.n || '').localeCompare(String(b.n || ''));
        const av = a.cvn;
        const bv = b.cvn;
        const aHas = av !== null && av !== undefined;
        const bHas = bv !== null && bv !== undefined;
        if (aHas !== bHas) return aHas ? -1 : 1;
        if (aHas && bHas) {
          const cmp = mode === 'value_asc' ? (av - bv) : (bv - av);
          if (cmp !== 0) return cmp;
        }
        return String(a.n || '').localeCompare(String(b.n || ''));
      });
    };

    const filteredRows = () => {
      const q = normalize(input?.value || '');
      return sortRows(ALL_ROWS.filter(row => {
        if (q && !normalize(row.q || '').includes(q)) return false;
        if (demandFilter !== 'ALL' && normalize(row.dm) !== normalize(demandFilter)) return false;
        if (trendFilter !== 'ALL' && normalize(row.tr) !== normalize(trendFilter)) return false;
        return true;
      }));
    };

    const setChipActive = (group, value) => {
      document.querySelectorAll(`[data-filter-group="${group}"] .sab-vl-chip`).forEach(btn => {
        const active = normalize(btn.textContent) === normalize(value);
        btn.classList.toggle('is-active', active);
        btn.setAttribute('aria-pressed', active ? 'true' : 'false');
      });
    };

    const update = ({ resetVisible = false } = {}) => {
      const filtered = filteredRows();
      if (resetVisible) visibleCount = Math.min(PER_PAGE, filtered.length);
      visibleCount = Math.min(Math.max(visibleCount, 0), filtered.length);
      const slice = filtered.slice(0, visibleCount);
      grid.innerHTML = slice.map(cardHtml).join('');
      count.innerHTML = `<strong class="sab-vl-meta-num">${filtered.length.toLocaleString()}</strong> matches`;
      empty.classList.toggle('hidden', filtered.length !== 0);
      const remaining = Math.max(0, filtered.length - visibleCount);
      if (remaining > 0) {
        const nextBatch = Math.min(PER_PAGE, remaining);
        loadMoreBtn.hidden = false;
        loadMoreBtn.textContent = `Load ${nextBatch} more · ${remaining} left`;
      } else {
        loadMoreBtn.hidden = true;
      }
    };

    document.querySelectorAll('[data-demand-filter]').forEach(btn => {
      btn.addEventListener('click', () => {
        demandFilter = btn.getAttribute('data-demand-filter') || 'ALL';
        setChipActive('demand', demandFilter);
        update({ resetVisible: true });
      });
    });
    document.querySelectorAll('[data-trend-filter]').forEach(btn => {
      btn.addEventListener('click', () => {
        trendFilter = btn.getAttribute('data-trend-filter') || 'ALL';
        setChipActive('trend', trendFilter);
        update({ resetVisible: true });
      });
    });
    input?.addEventListener('input', () => update({ resetVisible: true }));
    sortSelect?.addEventListener('change', () => update({ resetVisible: true }));
    loadMoreBtn?.addEventListener('click', () => {
      visibleCount += PER_PAGE;
      update();
    });

    update({ resetVisible: true });
  })();
</script>
@endsection
