@php
  $listing = $listing ?? null;
  $status = (string) ($listing?->status ?? '');
  $statusTone = match ($status) {
    'pending' => 'pending',
    'pending_confirmation' => 'confirm',
    'completed' => 'completed',
    'failed', 'disputed' => 'danger',
    'cancelled', 'expired', 'hidden' => 'expired',
    default => 'open',
  };
  $statusLabel = match ($status) {
    'pending' => 'Pending',
    'pending_confirmation' => 'Awaiting confirmation',
    'completed' => 'Completed',
    'failed' => 'Failed',
    'disputed' => 'Disputed',
    'cancelled' => 'Cancelled',
    'expired' => 'Expired',
    'hidden' => 'Hidden',
    default => 'Waiting for trade',
  };
  $wfl = \App\Support\TradePresenter::wflLabel($listing?->result_snapshot);
  $offering = $listing?->items?->where('side', 'offering')->sortBy('slot_no')->values() ?? collect();
  $looking = $listing?->items?->where('side', 'looking_for')->sortBy('slot_no')->values() ?? collect();
  $sides = [
    [
      'label' => "They're offering",
      'icon' => 'offer',
      'items' => $offering,
      'value' => $listing?->offering_value_snapshot,
    ],
    [
      'label' => "They're looking for",
      'icon' => 'want',
      'items' => $looking,
      'value' => $listing?->looking_value_snapshot,
    ],
  ];
@endphp
<article class="trades-card-board">
  <div class="trades-card-board__head">
    <div class="trades-card-board__who">
      @include('trades.partials.avatar', ['url' => $listing?->owner?->avatar_url, 'size' => 28])
      @if(\App\Support\TradeProfileAccess::canAccessProfile($listing?->owner))
        <a href="{{ $listing->owner->profilePath() }}" class="trades-card-board__name">{{ $listing->owner->display_name ?: $listing->owner->username }}</a>
      @else
        <span class="trades-card-board__name">{{ $listing?->owner?->display_name ?: $listing?->owner?->username ?: 'Trader' }}</span>
      @endif
      <span class="trades-card-board__status trades-card-board__status--{{ $statusTone }}">
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
      @if($wfl !== '')
        <span class="trades-card-board__wfl trades-card-board__wfl--{{ strtolower($wfl) }}">{{ $wfl }}</span>
      @endif
      @if(($listing?->is_top ?? 'N') === 'Y')
        <span class="trades-card-board__flag trades-card-board__flag--top">Top</span>
      @endif
      @if(($listing?->is_hot ?? 'N') === 'Y')
        <span class="trades-card-board__flag trades-card-board__flag--hot">Hot</span>
      @endif
    </div>
    <span class="trades-card-board__time">{{ optional($listing?->created_at)->diffForHumans() }}</span>
  </div>
  <div class="trades-card-board__sides">
    @foreach($sides as $index => $side)
      @if($index === 1)
        <div class="trades-card-board__arrow" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M3.8 7.15h11.05l-1.85-1.85a.95.95 0 0 1 1.35-1.35l3.5 3.5c.37.37.37.98 0 1.35l-3.5 3.5a.95.95 0 1 1-1.35-1.35l1.85-1.85H3.8a.95.95 0 0 1 0-1.9Z"/>
            <path d="M20.2 16.85H9.15l1.85 1.85a.95.95 0 1 1-1.35 1.35l-3.5-3.5a.95.95 0 0 1 0-1.35l3.5-3.5a.95.95 0 1 1 1.35 1.35l-1.85 1.85H20.2a.95.95 0 0 1 0 1.9Z"/>
          </svg>
        </div>
      @endif
      @php
        $demandLabel = \App\Support\TradePresenter::sideDemand($side['items']);
        $demandTone = \App\Support\TradePresenter::demandTone($demandLabel);
      @endphp
      <section class="trades-card-board__panel trades-card-board__panel--{{ $side['icon'] }}">
        <header class="trades-card-board__panel-head">
          <h2>
            @if($side['icon'] === 'offer')
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h11l-3-3M17 16H6l3 3"/></svg>
            @else
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3 4.2 4.2 0 0 1 7 3c0 5.6-7 10-7 10Z"/></svg>
            @endif
            {{ $side['label'] }}
            <span class="trades-card-board__count">{{ $side['items']->count() }}</span>
          </h2>
        </header>
        <div class="trades-card-board__stats">
          <span class="trades-card-board__stat trades-card-board__stat--value">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4M7 8 4 12l3 4M17 8l3 4-3 4"/></svg>
            <em>Value</em>
            <strong>{{ \App\Support\TradePresenter::compactValue($side['value']) }}</strong>
          </span>
          <span class="trades-card-board__stat trades-card-board__stat--demand trades-card-board__stat--{{ $demandTone }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 17 10 11l4 4 6-8"/><path stroke-linecap="round" d="M14 7h6v6"/></svg>
            <em>Demand</em>
            <strong>{{ $demandLabel }}</strong>
          </span>
        </div>
        <div class="trades-item-grid">
          @foreach($side['items'] as $item)
            @php
              $itemName = (string) $item->brainrot_name_snapshot;
              $mutation = trim((string) $item->mutation_name_snapshot);
              $traitNames = $item->traits
                  ->pluck('trait_name_snapshot')
                  ->map(fn ($name) => trim((string) $name))
                  ->filter()
                  ->values();
            @endphp
            <button type="button" class="trades-item-slot trades-item-slot--filled" data-item-tip>
              <span class="trades-item-slot__art">
                @if($mutation !== '')
                  <span class="trades-item-slot__mut">{{ mb_substr($mutation, 0, 1) }}</span>
                @endif
                @if($item->image_url_snapshot)
                  <img src="{{ $item->image_url_snapshot }}" alt="{{ $itemName }}">
                @else
                  <span class="trades-item-slot__name">{{ $itemName }}</span>
                @endif
              </span>
              <span class="trades-item-slot__meta">
                <strong>{{ $itemName }}</strong>
                <em>{{ \App\Support\TradePresenter::compactValue($item->final_value_snapshot) }}</em>
                @if($mutation !== '')
                  <span>{{ $mutation }}</span>
                @endif
                @if($traitNames->count() > 0)
                  <span>+{{ $traitNames->count() }} Traits</span>
                @endif
              </span>
              <span class="trades-item-tip" hidden>
                <strong>{{ $itemName }}</strong>
                @if($mutation !== '')
                  <span>Mutation: <em>{{ $mutation }}</em></span>
                @endif
                @if($traitNames->isNotEmpty())
                  <span>Traits: <em>{{ $traitNames->implode(', ') }}</em></span>
                @endif
              </span>
            </button>
          @endforeach
        </div>
      </section>
    @endforeach
  </div>
  <div class="trades-card-foot">
    <a href="/trading/{{ $listing?->public_id }}" class="trades-view-btn">View Trade</a>
  </div>
</article>
