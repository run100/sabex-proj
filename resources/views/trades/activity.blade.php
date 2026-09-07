@extends('trades.layout')

@section('content')
@php
  $status = $status ?? 'received';
  $tabCopy = $tabCopy ?? [];
  $currentCopy = (string) ($tabCopy[$status] ?? $tabCopy['received'] ?? '');
@endphp
<nav class="trades-show__back" aria-label="Breadcrumb">
  <a href="/">Home</a>
  <span class="trades-show__back-sep" aria-hidden="true">›</span>
  <a href="{{ \App\Support\TradePaths::marketplace() }}">All Trades</a>
  <span class="trades-show__back-sep" aria-hidden="true">›</span>
  <span class="trades-show__back-current">Offers</span>
</nav>
<section class="trades-home-hero">
  <h1>Offers</h1>
  <p class="trades-show__tools">
    <a class="trades-btn" href="{{ \App\Support\TradePaths::create() }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
      Post a trade ad
    </a>
    <a class="trades-show__ghost" href="/{{ \App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h.01"/></svg>
      Value Calculator
    </a>
    <a class="trades-show__ghost" href="/{{ \App\Services\Seo\SabRenderService::PAGE_VALUE_LIST }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/></svg>
      Value List
    </a>
  </p>
</section>
<nav class="trades-activity-tabs" aria-label="Offer status">
  <a href="{{ \App\Support\TradePaths::offers() }}?status=ads" class="trades-activity-tabs__item{{ $status === 'ads' ? ' is-active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h10M4 18h7"/></svg>
    All Ads
    <span class="trades-activity-tabs__count">{{ $counts['ads'] ?? 0 }}</span>
  </a>
  <a href="{{ \App\Support\TradePaths::offers() }}?status=received" class="trades-activity-tabs__item{{ $status === 'received' ? ' is-active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16v12H4z"/><path d="m4 8 8 5 8-5"/></svg>
    Received
    <span class="trades-activity-tabs__count">{{ $counts['received'] ?? 0 }}</span>
  </a>
  <a href="{{ \App\Support\TradePaths::offers() }}?status=sent" class="trades-activity-tabs__item{{ $status === 'sent' ? ' is-active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
    Sent
    <span class="trades-activity-tabs__count">{{ $counts['sent'] ?? 0 }}</span>
  </a>
  <a href="{{ \App\Support\TradePaths::offers() }}?status=pending" class="trades-activity-tabs__item{{ $status === 'pending' ? ' is-active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
    Pending
    <span class="trades-activity-tabs__count">{{ $counts['pending'] ?? 0 }}</span>
  </a>
  <a href="{{ \App\Support\TradePaths::offers() }}?status=completed" class="trades-activity-tabs__item{{ $status === 'completed' ? ' is-active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.5 2.5L16 9"/></svg>
    Completed
    <span class="trades-activity-tabs__count">{{ $counts['completed'] ?? 0 }}</span>
  </a>
  <a href="{{ \App\Support\TradePaths::offers() }}?status=expired" class="trades-activity-tabs__item{{ $status === 'expired' ? ' is-active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 3h12M6 21h12M8 3c0 5 8 5 8 10s-8 5-8 10M16 3c0 5-8 5-8 10s8 5 8 10"/></svg>
    Expired
    <span class="trades-activity-tabs__count">{{ $counts['expired'] ?? 0 }}</span>
  </a>
</nav>
<p class="trades-offers-hint">{{ $currentCopy }}</p>
<div class="grid gap-4">
  @forelse($listings as $listing)
    @include('trades.partials.card', ['listing' => $listing])
  @empty
    <div class="trades-offers-empty">
      <span class="trades-offers-empty__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16v12H4z"/><path d="m4 8 8 5 8-5"/></svg>
      </span>
      @if($status === 'received')
        <p class="trades-offers-empty__title">No offers waiting</p>
      @elseif($status === 'ads')
        <p class="trades-offers-empty__title">No ads yet</p>
      @elseif($status === 'pending')
        <p class="trades-offers-empty__title">No pending trades</p>
      @endif
      <p class="trades-offers-empty__copy">{{ $currentCopy }}</p>
    </div>
  @endforelse
</div>
@if(method_exists($listings, 'links'))
  {{ $listings->withQueryString()->onEachSide(1)->links('trades.partials.pagination') }}
@endif
@endsection
