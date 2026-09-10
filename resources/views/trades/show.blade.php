@extends('trades.layout')

@section('content')
@php
  $user = $tradeUser ?? null;
  $isOwner = $user && (int) $user->id === (int) $listing->owner_user_id;
  $isParticipant = $user && in_array((int) $user->id, [(int) $listing->owner_user_id, (int) $listing->counterparty_user_id], true);
  $offeringItems = $listing->items->where('side', 'offering')->sortBy('slot_no')->values();
  $lookingItems = $listing->items->where('side', 'looking_for')->sortBy('slot_no')->values();
  $h1Offering = $h1Offering ?? [];
  $h1Looking = $h1Looking ?? [];
  $offeringMore = (int) ($offeringMore ?? 0);
  $lookingMore = (int) ($lookingMore ?? 0);
  $wfl = \App\Support\TradePresenter::wflLabel($listing->result_snapshot);
  $ownerName = $listing->owner?->display_name ?: $listing->owner?->username ?: 'Trader';
  $ownerProfile = \App\Support\TradeProfileAccess::canAccessProfile($listing->owner) ? $listing->owner->profilePath() : null;
  $counterparty = $listing->counterparty;
  $counterpartyName = $counterparty?->display_name ?: $counterparty?->username ?: 'Trader';
  $counterpartyProfile = \App\Support\TradeProfileAccess::canAccessProfile($counterparty) ? $counterparty->profilePath() : null;
  $canSendOffer = $listing->isOpen() && $user && ! $isOwner;
  $myConfirmation = $user
      ? $listing->confirmations->firstWhere('user_id', $user->id)
      : null;
  $myMarkedCompleted = $myConfirmation?->confirmation === \App\Models\TradeConfirmation::COMPLETED;
  $myMarkedFailed = $myConfirmation?->confirmation === \App\Models\TradeConfirmation::FAILED;
  $waitName = $isOwner ? $counterpartyName : $ownerName;
  $loginHref = \App\Support\TradePaths::robloxLogin('/trading/'.$listing->public_id);
  $messageUrl = '/api/v1/trading/trades/'.$listing->public_id.'/messages';
  $messagePeer = $messagePeer ?? null;
  $joinQuota = $joinQuota ?? null;
  $contactLimit = (int) config('sab-trades.contact_limit_per_user', 5);
  $canMessage = $user && $messagePeer && (int) $messagePeer->id !== (int) $user->id;
  $peerName = $messagePeer?->display_name ?: $messagePeer?->username ?: 'Trader';
  $peerAvatar = (string) ($messagePeer?->avatar_url ?? '');
  $quickReplies = [
    "I'm ready to trade!",
    'Join me!',
    "I'll join you!",
    "I'm in the game!",
    'Can we trade later?',
    'I changed my mind!',
    'I already traded with someone else',
    'Please mark the trade as completed',
  ];
  $mutationColors = [
    'Rainbow' => 'linear-gradient(90deg,#f87171,#fb923c,#facc15,#4ade80,#60a5fa,#c084fc)',
    'Radioactive' => '#a3e635',
    'Yin Yang' => '#e2e8f0',
    'Diamond' => '#67e8f9',
    'Galaxy' => '#818cf8',
    'Candy' => '#ec4899',
    'Lava' => '#f97316',
    'Gold' => '#f59e0b',
    'Normal' => '#94a3b8',
  ];
  $listingStatus = (string) $listing->status;
  $statusTone = match ($listingStatus) {
      \App\Models\TradeListing::STATUS_PENDING => 'pending',
      \App\Models\TradeListing::STATUS_PENDING_CONFIRMATION => 'confirm',
      \App\Models\TradeListing::STATUS_COMPLETED => 'completed',
      \App\Models\TradeListing::STATUS_FAILED,
      \App\Models\TradeListing::STATUS_DISPUTED => 'danger',
      \App\Models\TradeListing::STATUS_CANCELLED,
      \App\Models\TradeListing::STATUS_EXPIRED,
      \App\Models\TradeListing::STATUS_HIDDEN => 'expired',
      default => 'open',
  };
  $statusLabel = match ($listingStatus) {
      \App\Models\TradeListing::STATUS_PENDING => 'Pending',
      \App\Models\TradeListing::STATUS_PENDING_CONFIRMATION => 'Awaiting confirmation',
      \App\Models\TradeListing::STATUS_COMPLETED => 'Completed',
      \App\Models\TradeListing::STATUS_FAILED => 'Failed',
      \App\Models\TradeListing::STATUS_DISPUTED => 'Disputed',
      \App\Models\TradeListing::STATUS_CANCELLED => 'Cancelled',
      \App\Models\TradeListing::STATUS_EXPIRED => 'Expired',
      \App\Models\TradeListing::STATUS_HIDDEN => 'Hidden',
      default => 'Waiting for trade',
  };
  $viewsCount = (int) $listing->views_count;
@endphp
<section class="trades-show">
  <nav class="trades-show__back" aria-label="Breadcrumb">
    <a href="/">Home</a>
    <span class="trades-show__back-sep" aria-hidden="true">›</span>
    <a href="{{ \App\Support\TradePaths::marketplace() }}">All Trades</a>
    <span class="trades-show__back-sep" aria-hidden="true">›</span>
    <span class="trades-show__back-current">{{ $listingH1 }}</span>
  </nav>

  <header class="trades-show__hero">
    <h1 class="trades-show__title">
      <span class="trades-show__offer">
        @foreach($h1Offering as $index => $token)
          @php
            $h1Label = is_array($token) ? (string) ($token['label'] ?? '') : (string) $token;
            $h1Rarity = is_array($token) ? (string) ($token['rarity'] ?? '') : '';
            $h1Slug = \App\Support\TradeSeo::nameSlug($h1Rarity);
          @endphp
          @if($index > 0)
            <span class="trades-show__plus"> + </span>
          @endif
          <span @class([
            'trades-show__name',
            'trades-show__name--'.$h1Slug => $h1Slug !== '',
            'trades-show__name--chroma' => \App\Support\TradeSeo::isChroma($h1Rarity),
          ])>{{ $h1Label }}</span>
        @endforeach
        @if($offeringMore > 0)
          <span class="trades-show__more">+{{ $offeringMore }} more</span>
        @endif
      </span>
      <span class="trades-show__swap" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M3.8 7.15h11.05l-1.85-1.85a.95.95 0 0 1 1.35-1.35l3.5 3.5c.37.37.37.98 0 1.35l-3.5 3.5a.95.95 0 1 1-1.35-1.35l1.85-1.85H3.8a.95.95 0 0 1 0-1.9Z"/>
          <path d="M20.2 16.85H9.15L11 18.7a.95.95 0 1 1-1.35 1.35l-3.5-3.5a.95.95 0 0 1 0-1.35l3.5-3.5a.95.95 0 1 1 1.35 1.35l-1.85 1.85H20.2a.95.95 0 0 1 0 1.9Z"/>
        </svg>
      </span>
      <span class="trades-show__look">
        @foreach($h1Looking as $index => $token)
          @php
            $h1Label = is_array($token) ? (string) ($token['label'] ?? '') : (string) $token;
            $h1Rarity = is_array($token) ? (string) ($token['rarity'] ?? '') : '';
            $h1Slug = \App\Support\TradeSeo::nameSlug($h1Rarity);
          @endphp
          @if($index > 0)
            <span class="trades-show__plus"> + </span>
          @endif
          <span @class([
            'trades-show__name',
            'trades-show__name--'.$h1Slug => $h1Slug !== '',
            'trades-show__name--chroma' => \App\Support\TradeSeo::isChroma($h1Rarity),
          ])>{{ $h1Label }}</span>
        @endforeach
        @if($lookingMore > 0)
          <span class="trades-show__more">+{{ $lookingMore }} more</span>
        @endif
      </span>
    </h1>
    <p class="trades-show__lead">Check both sides against current values, then send an offer or message the trader.</p>
    <p class="trades-show__tools">
      <a class="trades-show__ghost" href="/{{ \App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h.01"/></svg>
        Value Calculator
      </a>
      <a class="trades-show__ghost" href="/{{ \App\Services\Seo\SabRenderService::PAGE_VALUE_LIST }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/></svg>
        Value List
      </a>
    </p>
  </header>

  <div class="trades-show__userbar">
    <div class="trades-show__idline">
      <span class="trades-show__id-label">Trade ID:</span>
      <span class="trades-show__id" title="#{{ $listing->public_id }}">#{{ $listing->public_id }}</span>
      <button type="button" class="trades-show__id-copy" data-copy="{{ $listing->public_id }}" data-toast="Trade ID copied." aria-label="Copy trade ID">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
      </button>
    </div>
    <p class="trades-show__statusline">
      <span class="trades-show__badge trades-show__badge--{{ $statusTone }}">
        @if($statusTone === 'pending' || $statusTone === 'confirm')
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.5 1.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        @elseif($statusTone === 'completed')
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="m8 12.5 2.5 2.5L16 9"/></svg>
        @elseif($statusTone === 'danger' || $statusTone === 'expired')
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M15 9 9 15M9 9l6 6"/></svg>
        @else
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h11l-3-3M17 16H6l3 3"/></svg>
        @endif
        {{ $statusLabel }}
      </span>
      <span class="trades-show__views">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
        {{ $viewsCount }} {{ $viewsCount === 1 ? 'view' : 'views' }}
      </span>
    </p>
    <div class="trades-show__userbar-main">
      <div class="trades-show__who">
      @include('trades.partials.avatar', ['url' => $listing->owner?->avatar_url, 'size' => 40])
      <div class="trades-show__who-copy">
        <div class="trades-show__who-row">
          @if($ownerProfile)
            <a href="{{ $ownerProfile }}" class="trades-show__name">{{ $ownerName }}</a>
          @else
            <span class="trades-show__name">{{ $ownerName }}</span>
          @endif
          @if($wfl !== '')
            <span class="trades-show__wfl trades-show__wfl--{{ $listing->result_snapshot }}">{{ $wfl }}</span>
          @endif
        </div>
        <span class="trades-show__posted">Posted {{ optional($listing->created_at)->diffForHumans() }}</span>
      </div>
      </div>
      <div class="trades-show__cta">
      @if($canMessage)
        <button type="button" class="trades-show__ghost" data-message-open>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a8.5 8.5 0 0 1-12.4 7.5L3 21l1.6-5A8.5 8.5 0 1 1 21 12Z"/></svg>
          Message
        </button>
      @elseif(! $user)
        <a class="trades-show__ghost" href="{{ $loginHref }}" data-nav-sign-in>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a8.5 8.5 0 0 1-12.4 7.5L3 21l1.6-5A8.5 8.5 0 1 1 21 12Z"/></svg>
          Message
        </a>
      @endif
      <button type="button" class="trades-show__offer-btn" data-copy="href" data-toast="Link copied.">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/></svg>
        <span data-share-text>Share Trade</span>
      </button>
      @if($canSendOffer)
        <button type="button" class="trades-show__offer-btn" data-offer-open>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h11l-3-3M17 16H6l3 3"/></svg>
          Join To Trade
        </button>
      @elseif($listing->isOpen() && !$user)
        <a class="trades-show__offer-btn" href="{{ $loginHref }}" data-nav-sign-in>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h11l-3-3M17 16H6l3 3"/></svg>
          Join To Trade
        </a>
      @elseif($isOwner && $listing->isOpen())
        <form method="post" action="/api/v1/trading/trades/{{ $listing->public_id }}/cancel" data-json-form>
          @csrf
          <button class="trades-show__ghost trades-show__ghost--danger" type="submit">Cancel listing</button>
        </form>
      @endif
      @if($isParticipant && $listing->isPendingLike())
        @if(! $myMarkedCompleted)
          <form method="post" action="/api/v1/trading/trades/{{ $listing->public_id }}/confirm" data-json-form>
            @csrf
            <input type="hidden" name="confirmation" value="completed">
            <button class="trades-show__offer-btn" type="submit">Mark Completed</button>
          </form>
        @endif
        @if(! $myMarkedFailed)
          <form method="post" action="/api/v1/trading/trades/{{ $listing->public_id }}/confirm" data-json-form>
            @csrf
            <input type="hidden" name="confirmation" value="failed">
            <button class="trades-show__ghost trades-show__ghost--danger" type="submit">Mark Failed</button>
          </form>
        @endif
        @if($myMarkedCompleted || $myMarkedFailed)
          <p class="trades-show__wait">Waiting for {{ $waitName }} to confirm</p>
        @endif
      @endif
    </div>
    </div>
  </div>
  @if($isOwner && $listing->isOpen() && count($joinRequests) > 0)
  <section class="trades-show__joins">
    <h2>Join requests</h2>
    @foreach($joinRequests as $join)
      <div class="trades-show__join">
        @include('trades.partials.avatar', ['url' => $join->requester?->avatar_url, 'size' => 28])
        <div class="trades-show__join-copy">
          <p>
            <a href="{{ $join->requester?->profilePath() ?? '#' }}">{{ $join->requester?->display_name ?: $join->requester?->username }}</a>
            <span>requested</span>
          </p>
          @if($join->note)
            <p>{{ $join->note }}</p>
          @endif
        </div>
        @if($join->isActive())
          <div class="trades-show__join-actions">
            <form method="post" action="/api/v1/trading/join-requests/{{ $join->public_id }}/accept" data-json-form>@csrf<button class="trades-show__offer-btn" type="submit">Accept</button></form>
            <form method="post" action="/api/v1/trading/join-requests/{{ $join->public_id }}/reject" data-json-form>@csrf<button class="trades-show__ghost" type="submit">Reject</button></form>
          </div>
        @endif
      </div>
    @endforeach
  </section>
  @endif
  <p class="trades-show__status" data-form-status></p>

  @if($counterparty)
  <section class="trades-show__people">
    <h2>Participants</h2>
    <div class="trades-show__people-card">
    <div class="trades-show__people-grid">
      <article class="trades-show__person">
        <p class="trades-show__person-label">Posted By</p>
        <div class="trades-show__person-row">
          @include('trades.partials.avatar', ['url' => $listing->owner?->avatar_url, 'size' => 40])
          <div>
            <p class="trades-show__person-name">
              @if($ownerProfile)
                <a href="{{ $ownerProfile }}">{{ $ownerName }}</a>
              @else
                <span>{{ $ownerName }}</span>
              @endif
              @if($ownerProfile)
                <a class="trades-show__person-link" href="{{ $ownerProfile }}" aria-label="Open {{ $ownerName }} profile">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4M14 4h6v6M10 14 20 4"/></svg>
                </a>
              @endif
            </p>
            <p class="trades-show__person-time">{{ optional($listing->created_at)->diffForHumans() }}</p>
          </div>
        </div>
      </article>
      <article class="trades-show__person">
        <p class="trades-show__person-label">Accepted By</p>
        <div class="trades-show__person-row">
          @include('trades.partials.avatar', ['url' => $counterparty->avatar_url, 'size' => 40])
          <div>
            <p class="trades-show__person-name">
              @if($counterpartyProfile)
                <a href="{{ $counterpartyProfile }}">{{ $counterpartyName }}</a>
              @else
                <span>{{ $counterpartyName }}</span>
              @endif
              @if($counterpartyProfile)
                <a class="trades-show__person-link" href="{{ $counterpartyProfile }}" aria-label="Open {{ $counterpartyName }} profile">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4M14 4h6v6M10 14 20 4"/></svg>
                </a>
              @endif
            </p>
            <p class="trades-show__person-time">{{ optional($listing->accepted_at)->diffForHumans() }}</p>
          </div>
        </div>
      </article>
    </div>
    </div>
  </section>
  @endif

  <div class="trades-show__boards">
    @foreach([
      ['label' => "They're offering", 'icon' => 'offer', 'items' => $offeringItems, 'value' => $listing->offering_value_snapshot],
      ['label' => "They're looking for", 'icon' => 'want', 'items' => $lookingItems, 'value' => $listing->looking_value_snapshot],
    ] as $index => $side)
      @if($index === 1)
        <div class="trades-show__arrow" aria-hidden="true">
          <img src="/static/img/trades-transfer.png" alt="" aria-hidden="true">
        </div>
      @endif
      <section class="trades-show__panel trades-show__panel--{{ $side['icon'] }}">
        <header class="trades-show__panel-head">
          <h2>
            @if($side['icon'] === 'offer')
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h11l-3-3M17 16H6l3 3"/></svg>
            @else
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3 4.2 4.2 0 0 1 7 3c0 5.6-7 10-7 10Z"/></svg>
            @endif
            {{ $side['label'] }}
            <span class="trades-show__count">{{ $side['items']->count() }}</span>
          </h2>
        </header>
        @php
          $demandLabel = \App\Support\TradePresenter::sideDemand($side['items']);
          $demandTone = \App\Support\TradePresenter::demandTone($demandLabel);
        @endphp
        <div class="trades-show__stats">
          <span class="trades-show__stat trades-show__stat--value">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4M7 8 4 12l3 4M17 8l3 4-3 4"/></svg>
            <em>Value</em>
            <strong>{{ \App\Support\TradePresenter::compactValue($side['value']) }}</strong>
          </span>
          <span class="trades-show__stat trades-show__stat--{{ $demandTone }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 17 10 11l4 4 6-8"/><path stroke-linecap="round" d="M14 7h6v6"/></svg>
            <em>Demand</em>
            <strong>{{ $demandLabel }}</strong>
          </span>
        </div>
        <div class="trades-show__well">
          <div class="trades-show__grid trades-show__grid--{{ min(3, max(1, $side['items']->count())) }}">
            @foreach($side['items'] as $item)
              @include('trades.partials.show-item', ['item' => $item, 'mutationColors' => $mutationColors])
            @endforeach
          </div>
        </div>
      </section>
    @endforeach
  </div>

  {{-- Report UI and POST /api/v1/reports are disabled. --}}
  @if(false && $user)
  <section class="trades-show__block">
    <h2>Report</h2>
    <form method="post" action="/api/v1/reports" data-json-form>
      @csrf
      <input type="hidden" name="listing_public_id" value="{{ $listing->public_id }}">
      <input type="hidden" name="reason" value="other">
      <label>Describe the issue
        <input name="description" maxlength="1000">
      </label>
      <button type="submit" class="trades-show__text-btn">Submit report</button>
    </form>
  </section>
  @endif

  <section class="trades-show__block">
    <h2>Timeline</h2>
    <ol class="trades-show__rail">
      @foreach(\App\Support\TradePresenter::milestoneTimeline($listing) as $step)
        <li class="trades-show__step trades-show__step--{{ $step['tone'] }}{{ $step['done'] ? ' is-done' : ' is-wait' }}">
          <span class="trades-show__step-icon" aria-hidden="true">
            @if($step['key'] === 'posted')
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5M7 10l5-5 5 5"/></svg>
            @elseif($step['key'] === 'joined')
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="m16 11 2 2 4-4"/></svg>
            @elseif($step['key'] === 'pending')
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.5 1.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            @elseif($step['tone'] === 'danger')
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M15 9 9 15M9 9l6 6"/></svg>
            @else
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="m8 12.5 2.5 2.5L16 9"/></svg>
            @endif
          </span>
          <div class="trades-show__step-card">
            @if($step['done'])
              <p class="trades-show__step-label">{{ $step['label'] }}</p>
              @if($step['at'])
                <p class="trades-show__step-time">{{ optional($step['at'])->diffForHumans() }}</p>
              @endif
              <p class="trades-show__step-detail">
                @if($step['actor_name'] !== '')
                  @include('trades.partials.avatar', ['url' => $step['actor_avatar'], 'size' => 20])
                  <strong>{{ $step['actor_name'] }}</strong>
                @endif
                {{ $step['detail'] }}
              </p>
            @else
              <p class="trades-show__step-wait">{{ $step['waiting'] }}</p>
            @endif
          </div>
        </li>
      @endforeach
    </ol>
  </section>
</section>

@if($canMessage)
<div class="trades-msg" hidden data-message-modal data-contact-limit="{{ $contactLimit }}" data-messages-url="{{ $messageUrl }}" data-peer-name="{{ $peerName }}" data-peer-avatar="{{ $peerAvatar }}">
  <div class="trades-msg__dialog" role="dialog" aria-modal="true" aria-labelledby="trades-msg-name">
    <header class="trades-msg__head">
      <div class="trades-msg__peer">
        @include('trades.partials.avatar', ['url' => $peerAvatar, 'size' => 36])
        <div>
          <p id="trades-msg-name" class="trades-msg__name" data-message-peer-name>{{ $peerName }}</p>
          <p class="trades-msg__trade">Trade #{{ $listing->public_id }}</p>
        </div>
      </div>
      <button type="button" class="trades-msg__close" data-message-close aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
      </button>
    </header>
    <div class="trades-msg__thread" data-message-thread></div>
    <details class="trades-msg__quick" open>
      <summary>Quick replies</summary>
      <div class="trades-msg__chips">
        @foreach($quickReplies as $reply)
          <button type="button" data-quick-reply="{{ $reply }}">{{ $reply }}</button>
        @endforeach
      </div>
    </details>
    <form class="trades-msg__composer" data-message-form>
      <input type="text" name="message" maxlength="280" placeholder="Send a message..." data-message-input autocomplete="off">
      <button type="submit" class="trades-msg__send" aria-label="Send">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M22 2 11 13M22 2 15 22l-4-9-9-4 20-7Z"/></svg>
      </button>
    </form>
    <p class="trades-msg__error" data-message-error></p>
  </div>
</div>
@endif

@if($canSendOffer)
<div class="trades-offer" hidden data-offer-modal data-contact-limit="{{ $joinQuota['limit'] ?? '' }}" data-contact-remaining="{{ $joinQuota['remaining'] ?? '' }}">
  <div class="trades-offer__dialog" role="dialog" aria-modal="true" aria-labelledby="trades-offer-title">
    <header class="trades-offer__head">
      <div class="trades-offer__who">
        @include('trades.partials.avatar', ['url' => $listing->owner?->avatar_url, 'size' => 36])
        <div>
          <h2 id="trades-offer-title">Send an offer</h2>
          <p>to {{ $ownerName }} · posted {{ optional($listing->created_at)->diffForHumans() }}</p>
        </div>
      </div>
      <button type="button" class="trades-msg__close" data-offer-close aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
      </button>
    </header>
    <div class="trades-offer__sides">
      @foreach([
        ['label' => 'You give', 'items' => $lookingItems, 'value' => $listing->looking_value_snapshot],
        ['label' => 'You get', 'items' => $offeringItems, 'value' => $listing->offering_value_snapshot],
      ] as $side)
        <section class="trades-offer__side">
          <h3>{{ $side['label'] }}</h3>
          <div class="trades-offer__stats">
            <span><em>Value</em> <strong>{{ \App\Support\TradePresenter::compactValue($side['value']) }}</strong></span>
            <span><em>Demand</em> <strong>{{ \App\Support\TradePresenter::sideDemand($side['items']) }}</strong></span>
          </div>
          <div class="trades-show__well">
            <div class="trades-show__grid trades-show__grid--{{ min(3, max(1, $side['items']->count())) }}">
              @foreach($side['items'] as $item)
                @include('trades.partials.show-item', ['item' => $item, 'mutationColors' => $mutationColors])
              @endforeach
            </div>
          </div>
        </section>
      @endforeach
    </div>
    <form class="trades-offer__form" data-offer-form action="/api/v1/trading/trades/{{ $listing->public_id }}/join">
      <label class="trades-offer__message">
        <span>Message</span>
        <textarea name="note" maxlength="280" rows="3">Hi! I have the items you're looking for.</textarea>
      </label>
      <p class="trades-offer__error" data-offer-error></p>
      <div class="trades-offer__foot">
        <p>The owner accepts or declines this offer. You'll be notified when they respond.</p>
        <div class="trades-offer__actions">
          <button type="button" class="trades-show__ghost" data-offer-close>Cancel</button>
          <button type="submit" class="trades-show__offer-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h11l-3-3M17 16H6l3 3"/></svg>
            Send offer
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
@endif
@endsection

@section('scripts')
<script>
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  document.querySelectorAll('[data-json-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const status = document.querySelector('[data-form-status]');
      const body = {};
      new FormData(form).forEach((value, key) => { if (key !== '_token') body[key] = value; });
      const response = await fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify(body),
      });
      const payload = await response.json().catch(() => ({}));
      if (!payload.success) {
        if (status) status.textContent = payload.error?.message || 'Request failed';
        return;
      }
      window.location.reload();
    });
  });

  function containsTradeMarkup(value) {
    return /<\/?[A-Za-z_:][A-Za-z0-9:._-]*(?:\s+[^<>]*)?\s*\/?>|<!--[\s\S]*?(?:-->|$)|<![A-Za-z][^>]*>|<\?[A-Za-z][^>]*\?>|<\/?[A-Za-z_:][A-Za-z0-9:._-]*(?:\s+[^<>]*)?$|&(?:[A-Za-z][A-Za-z0-9]+|#\d+|#x[0-9A-F]+);/i.test(value);
  }

  function isTradeNormalText(value) {
    return /^[\p{L}\p{M}\p{N}\p{Zs}\r\n.,!?;:'\"()\-_\/，。！？；：、（）「」『』【】《》〈〉…—–·]+$/u.test(value);
  }

@if($canMessage)
  (function () {
    const modal = document.querySelector('[data-message-modal]');
    if (!modal) return;
    const thread = modal.querySelector('[data-message-thread]');
    const form = modal.querySelector('[data-message-form]');
    const input = modal.querySelector('[data-message-input]');
    const error = modal.querySelector('[data-message-error]');
    const url = modal.getAttribute('data-messages-url');
    const contactLimit = Number(modal.getAttribute('data-contact-limit') || 5);
    let messageQuota = null;

    function setOpen(open) {
      modal.hidden = !open;
      document.body.classList.toggle('trades-msg-open', open);
      if (open) loadThread();
    }

    function escapeHtml(value) {
      return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function updateMessageQuota(quota) {
      messageQuota = quota && typeof quota === 'object' ? quota : null;
      const exhausted = messageQuota && Number(messageQuota.remaining) < 1;
      input.disabled = Boolean(exhausted);
      modal.querySelector('.trades-msg__send').disabled = Boolean(exhausted);
      modal.querySelectorAll('[data-quick-reply]').forEach((button) => {
        button.disabled = Boolean(exhausted);
      });
      if (exhausted) {
        error.textContent = `You can send at most ${contactLimit} messages or trade requests to this user.`;
      }
    }

    function renderItems(items) {
      thread.innerHTML = (items || []).map((item) => (
        '<p class="trades-msg__bubble' + (item.mine ? ' is-mine' : '') + '">' + escapeHtml(item.message) + '</p>'
      )).join('');
      thread.scrollTop = thread.scrollHeight;
    }

    async function loadThread() {
      error.textContent = '';
      const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
      const payload = await response.json().catch(() => ({}));
      if (!payload.success) {
        error.textContent = payload.error?.message || 'Could not load messages.';
        return;
      }
      updateMessageQuota(payload.data?.contact_quota || payload.data?.message_quota);
      renderItems(payload.data?.items || []);
    }

    async function sendMessage(text) {
      const message = String(text || '').trim();
      if (!message) return;
      if (containsTradeMarkup(message)) {
        error.textContent = 'HTML/XML tags are not allowed.';
        return;
      }
      if (!isTradeNormalText(message)) {
        error.textContent = 'Only normal text, numbers, spaces, line breaks, and common punctuation are allowed.';
        return;
      }
      if (messageQuota && Number(messageQuota.remaining) < 1) {
        updateMessageQuota(messageQuota);
        return;
      }
      error.textContent = '';
      const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ message }),
      });
      const payload = await response.json().catch(() => ({}));
      if (!payload.success) {
        if (payload.error?.code === 'MESSAGE_LIMIT_REACHED') {
          updateMessageQuota({ limit: contactLimit, sent: contactLimit, remaining: 0 });
        }
        error.textContent = payload.error?.message || 'Could not send.';
        return;
      }
      input.value = '';
      await loadThread();
    }

    document.querySelector('[data-message-open]')?.addEventListener('click', () => setOpen(true));
    modal.querySelectorAll('[data-message-close]').forEach((button) => {
      button.addEventListener('click', () => setOpen(false));
    });
    modal.addEventListener('click', (event) => {
      if (event.target === modal) setOpen(false);
    });
    document.addEventListener('keydown', (event) => {
      const offerOpen = document.querySelector('[data-offer-modal]:not([hidden])');
      if (event.key === 'Escape' && !modal.hidden && !offerOpen) setOpen(false);
    });
    modal.querySelectorAll('[data-quick-reply]').forEach((button) => {
      button.addEventListener('click', () => {
        input.value = button.getAttribute('data-quick-reply') || '';
        input.focus();
      });
    });
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      sendMessage(input.value);
    });
  })();
@endif

@if($canSendOffer)
  (function () {
    const modal = document.querySelector('[data-offer-modal]');
    if (!modal) return;
    const form = modal.querySelector('[data-offer-form]');
    const error = modal.querySelector('[data-offer-error]');
    const noteInput = form?.querySelector('[name="note"]');
    const submitButton = form?.querySelector('button[type="submit"]');
    const contactLimit = Number(modal.getAttribute('data-contact-limit') || 5);
    const rawRemaining = modal.getAttribute('data-contact-remaining');
    let contactQuota = rawRemaining === null || rawRemaining === ''
      ? null
      : {
          limit: contactLimit,
          sent: Math.max(0, contactLimit - Number(rawRemaining)),
          remaining: Math.max(0, Number(rawRemaining)),
        };

    function updateOfferQuota(quota) {
      contactQuota = quota && typeof quota === 'object' ? quota : null;
      const exhausted = contactQuota && Number(contactQuota.remaining) < 1;
      if (noteInput) noteInput.disabled = Boolean(exhausted);
      if (submitButton) submitButton.disabled = Boolean(exhausted);
      if (exhausted && error) {
        error.textContent = `You can send at most ${contactLimit} messages or trade requests to this user.`;
      }
    }

    function setOpen(open) {
      modal.hidden = !open;
      document.body.classList.toggle('trades-offer-open', open);
      if (open) {
        if (error) error.textContent = '';
        updateOfferQuota(contactQuota);
      }
    }

    updateOfferQuota(contactQuota);
    document.querySelector('[data-offer-open]')?.addEventListener('click', () => setOpen(true));
    modal.querySelectorAll('[data-offer-close]').forEach((button) => {
      button.addEventListener('click', () => setOpen(false));
    });
    modal.addEventListener('click', (event) => {
      if (event.target === modal) setOpen(false);
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !modal.hidden) setOpen(false);
    });
    form?.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (error) error.textContent = '';
      const note = String(new FormData(form).get('note') || '').trim();
      if (note && containsTradeMarkup(note)) {
        if (error) error.textContent = 'HTML/XML tags are not allowed.';
        return;
      }
      if (note && !isTradeNormalText(note)) {
        if (error) error.textContent = 'Only normal text, numbers, spaces, line breaks, and common punctuation are allowed.';
        return;
      }
      if (contactQuota && Number(contactQuota.remaining) < 1) {
        updateOfferQuota(contactQuota);
        return;
      }
      const response = await fetch(form.getAttribute('action'), {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ note }),
      });
      const payload = await response.json().catch(() => ({}));
      if (!payload.success) {
        if (payload.error?.code === 'JOIN_LIMIT_REACHED') {
          updateOfferQuota({ limit: contactLimit, sent: contactLimit, remaining: 0 });
        }
        if (error) error.textContent = payload.error?.message || 'Could not send offer.';
        return;
      }
      if (contactQuota) {
        updateOfferQuota({
          limit: Number(contactQuota.limit),
          sent: Number(contactQuota.sent) + 1,
          remaining: Math.max(0, Number(contactQuota.remaining) - 1),
        });
      }
      setOpen(false);
      if (window.tradesToast) {
        window.tradesToast("Offer sent. You'll be notified when they respond.");
      }
    });
  })();
@endif
</script>
@endsection
