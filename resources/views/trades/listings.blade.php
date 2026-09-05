@extends('trades.layout')

@section('content')
@php
  $wantBrainrot = $wantBrainrot ?? null;
  $haveBrainrot = $haveBrainrot ?? null;
  $filterOpen = $wantBrainrot || $haveBrainrot;
@endphp
<section class="trades-home-hero">
  <h1>Steal a Brainrot Trades</h1>
  <p class="trades-home-hero__lead">Find recent Steal a Brainrot trades from Roblox players. Search by the Brainrot you want or have, compare current SAB values, and post or join an offer.</p>
  <div class="trades-home-actions">
    <a href="/trading/new" class="trades-btn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
      Post a Trade
    </a>
    <a href="/user/activity" class="trades-btn trades-btn-ghost">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 12h3l2-6 4 12 2-6h7"/></svg>
      See Activity
    </a>
    <a href="/trading/completed" class="trades-btn trades-btn-ghost">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 12.5 10.5 15 16 9"/></svg>
      Completed Trades
    </a>
  </div>
  <p class="trades-home-note">Community listings only. SAB Exist Count is not official Roblox, does not hold items, and does not complete trades. Finish exchanges in Roblox.</p>
</section>

<form class="trades-filter" method="get" action="/trading" data-trades-filter>
  <details class="trades-filter__panel{{ $filterOpen ? ' is-open' : '' }}" @if($filterOpen) open @endif>
  <summary class="trades-filter__head">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 5h16l-6 8v5l-4 2v-7L4 5z"/></svg>
    <span>Filter Trades</span>
    <svg class="trades-filter__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
  </summary>
  <div class="trades-filter__grid">
    <div class="trades-filter__field" data-filter-field>
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
    <div class="trades-filter__field" data-filter-field>
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
<p class="text-slate-500">No open listings yet. Post the first one.</p>
@endif

<div class="trades-listing-grid">
  @foreach($listings ?? [] as $listing)
    @include('trades.partials.card', ['listing' => $listing])
  @endforeach
</div>

@if(isset($listings) && method_exists($listings, 'links'))
  {{ $listings->withQueryString()->onEachSide(1)->links('trades.partials.pagination') }}
@endif

<section class="trades-home-about mt-10 max-w-3xl">
  <h2 class="text-xl font-black">About Steal a Brainrot trades</h2>
  <p class="mt-2 text-sm text-slate-400">SABExistCount lists live Steal a Brainrot trade ads so you can compare SAB values, mutations, traits and exist counts before you finish a swap in Roblox. Listings are community posts, not escrow.</p>
</section>
<section class="trades-home-faq mt-8 max-w-3xl">
  <h2 class="text-xl font-black">Trade FAQ</h2>
  <h3 class="mt-4 text-base font-bold">How do I post a trade?</h3>
  <p class="mt-1 text-sm text-slate-400">Open Post a Trade, add the Brainrots you have and want, then publish. Guests can build the ad first and sign in with Roblox when they publish.</p>
  <h3 class="mt-4 text-base font-bold">Does SABExistCount complete the trade?</h3>
  <p class="mt-1 text-sm text-slate-400">No. Confirm the swap in Roblox, then both players mark the listing completed here.</p>
</section>
@endsection

@section('scripts')
<script src="/static/js/trades-filter.js" defer></script>
@endsection
