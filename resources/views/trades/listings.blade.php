@extends('trades.layout')

@section('content')
@php
  $wantBrainrot = $wantBrainrot ?? null;
  $haveBrainrot = $haveBrainrot ?? null;
  $filterOpen = $wantBrainrot || $haveBrainrot;
  $guest = ! ($tradeUser ?? $user ?? null);
@endphp
<nav class="trades-show__back" aria-label="Breadcrumb">
  <a href="/">Home</a>
  <span class="trades-show__back-sep" aria-hidden="true">›</span>
  <span class="trades-show__back-current">All Trades</span>
</nav>
<section class="trades-home-hero">
  <h1>Steal a Brainrot Trades</h1>
  <p class="trades-home-hero__lead">Browse live Steal a Brainrot trades posted by the community — buy, sell and trade your SAB collection. Post a trade ad, join active offers, and check SAB trade values, exist counts, mutations and traits before you make a deal.</p>
  <div class="trades-home-actions">
    <a href="{{ \App\Support\TradePaths::create() }}" class="trades-btn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
      Post a Trade
    </a>
    <div class="trades-home-actions__row">
      <a href="{{ \App\Support\TradePaths::offers() }}" class="trades-btn trades-btn-ghost"@if($guest) data-nav-sign-in @endif>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 12h3l2-6 4 12 2-6h7"/></svg>
        Offers
      </a>
      <a href="/{{ \App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR }}" class="trades-btn trades-btn-ghost">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h.01"/></svg>
        Value Calculator
      </a>
      <a href="/{{ \App\Services\Seo\SabRenderService::PAGE_VALUE_LIST }}" class="trades-btn trades-btn-ghost">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/></svg>
        Value List
      </a>
    </div>
  </div>
</section>

<form class="trades-filter" method="get" action="/trading" data-trades-filter>
  <details class="trades-filter__panel{{ $filterOpen ? ' is-open' : '' }}" @if($filterOpen) open @endif>
  <summary class="trades-filter__head">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 5h16l-6 8v5l-4 2v-7L4 5z"/></svg>
    <span>Filter Trades</span>
    <svg class="trades-filter__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
  </summary>
  <div class="trades-filter__grid">
    <div class="trades-filter__field{{ !empty($haveBrainrot) ? ' is-active' : '' }}" data-filter-field>
      <label class="trades-filter__label" for="trades-filter-have">Brainrot I want to get</label>
      <p class="trades-filter__hint">Find trades offering this</p>
      <input type="hidden" name="have_brainrot_id" value="{{ $haveBrainrot['id'] ?? '' }}">
      <div class="trades-filter__control">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>
        <input id="trades-filter-have" type="search" value="{{ $haveBrainrot['name'] ?? '' }}" placeholder="Search a brainrot you want..." autocomplete="off" data-filter-q>
        <button type="button" class="trades-filter__clear" data-filter-clear @if(empty($haveBrainrot)) hidden @endif>Clear</button>
      </div>
      <ul class="trades-filter__results" hidden data-filter-results></ul>
    </div>
    <div class="trades-filter__field{{ !empty($wantBrainrot) ? ' is-active' : '' }}" data-filter-field>
      <label class="trades-filter__label" for="trades-filter-want">Brainrot I have to give</label>
      <p class="trades-filter__hint">Find who wants this</p>
      <input type="hidden" name="want_brainrot_id" value="{{ $wantBrainrot['id'] ?? '' }}">
      <div class="trades-filter__control">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>
        <input id="trades-filter-want" type="search" value="{{ $wantBrainrot['name'] ?? '' }}" placeholder="Search a brainrot you have..." autocomplete="off" data-filter-q>
        <button type="button" class="trades-filter__clear" data-filter-clear @if(empty($wantBrainrot)) hidden @endif>Clear</button>
      </div>
      <ul class="trades-filter__results" hidden data-filter-results></ul>
    </div>
  </div>
  <noscript>
    <button type="submit" class="trades-btn trades-btn-ghost">Apply filters</button>
  </noscript>
  </details>
</form>

@if(!empty($schemaMissing))
<p class="rounded border border-amber-700/50 bg-amber-950/40 px-4 py-3 text-amber-100">Trade tables are not installed yet. Review <code>database/schema/seo-trades.sql</code> before creating them.</p>
@endif

<h2 class="trades-home-list-title">Recent Steal a Brainrot Trades</h2>

@if(!$schemaMissing && ($listings ?? collect())->isEmpty())
<div class="trades-empty">
  <p class="trades-empty__title">No matching trades yet.</p>
  <p class="trades-empty__copy">Try changing your filters or post the trade you're looking for.</p>
  <a class="trades-btn" href="{{ \App\Support\TradePaths::create() }}">Post a Trade</a>
</div>
@endif

<div class="trades-listing-grid">
  @foreach($listings ?? [] as $listing)
    @include('trades.partials.card', ['listing' => $listing])
  @endforeach
</div>

@if(isset($listings) && method_exists($listings, 'links'))
  {{ $listings->withQueryString()->onEachSide(1)->links('trades.partials.pagination') }}
@endif

<section class="trades-home-about mt-10">
  <h2 class="text-xl font-black">About Steal a Brainrot trades</h2>
  <p class="mt-2 text-sm text-slate-400">SABExistCount lists live Steal a Brainrot trade ads so you can compare SAB values, mutations, traits and exist counts before you finish a swap in Roblox. Listings are community posts, not escrow.</p>
</section>
@php
  $tradeFaqs = \App\Support\TradeSeo::marketplaceFaqs();
@endphp
<section id="faq" class="trades-home-faq mt-8">
  <h2 class="text-xl font-black">Trade FAQ</h2>
  @foreach($tradeFaqs as $faq)
    <h3 class="mt-4 text-base font-bold">{{ $faq['q'] }}</h3>
    <p class="mt-1 text-sm text-slate-400">{{ $faq['a'] }}</p>
  @endforeach
</section>
<script type="application/ld+json">{!! \App\Support\TradeSeo::marketplaceFaqJsonLd() !!}</script>
@endsection

@section('scripts')
<script src="/static/js/trades-filter.js" defer></script>
@endsection
