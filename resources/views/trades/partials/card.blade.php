@php
  $listing = $listing ?? null;
  $status = (string) ($listing?->status ?? '');
  $statusLabel = match ($status) {
    'pending', 'pending_confirmation' => 'In Progress',
    'completed' => 'Completed',
    'failed' => 'Failed',
    'disputed' => 'Disputed',
    'cancelled' => 'Cancelled',
    'expired' => 'Expired',
    'hidden' => 'Hidden',
    default => 'Open',
  };
  $offering = $listing?->items?->where('side', 'offering')->keyBy('slot_no') ?? collect();
  $looking = $listing?->items?->where('side', 'looking_for')->keyBy('slot_no') ?? collect();
@endphp
<article class="trades-card-board rounded-xl border border-white/10 bg-slate-900/70 p-3">
  <div class="trades-card-board__head">
    <div class="trades-card-board__who">
      @include('trades.partials.avatar', ['url' => $listing?->owner?->avatar_url, 'size' => 28])
      @if(\App\Support\TradeProfileAccess::canAccessProfile($listing?->owner))
        <a href="{{ $listing->owner->profilePath() }}" class="trades-card-board__name">{{ $listing->owner->display_name ?: $listing->owner->username }}</a>
      @else
        <span class="trades-card-board__name">{{ $listing?->owner?->display_name ?: $listing?->owner?->username ?: 'Trader' }}</span>
      @endif
      <span class="trades-card-board__status trades-card-board__status--{{ $status !== '' ? $status : 'open' }}">{{ $statusLabel }}</span>
    </div>
    <span class="trades-card-board__time">{{ optional($listing?->created_at)->diffForHumans() }}</span>
  </div>
  <div class="trades-card-board__sides">
    @foreach(['Offering' => $offering, 'Looking for' => $looking] as $sideLabel => $sideItems)
      <div class="trades-card-board__side">
        <h2>{{ $sideLabel }}</h2>
        <div class="trades-item-grid">
          @for($slot = 1; $slot <= 9; $slot++)
            @php $item = $sideItems->get($slot); @endphp
            @if($item)
              @php
                $itemName = (string) $item->brainrot_name_snapshot;
                $mutation = trim((string) $item->mutation_name_snapshot);
                $showMutation = $mutation !== '' && strcasecmp($mutation, 'Default') !== 0;
              @endphp
              <button type="button" class="trades-item-slot trades-item-slot--filled" data-item-tip>
                @if($item->image_url_snapshot)
                  <img src="{{ $item->image_url_snapshot }}" alt="{{ $itemName }}">
                @else
                  <span>{{ $itemName }}</span>
                @endif
                <span class="trades-item-tip" hidden>
                  <strong>{{ $itemName }}</strong>
                  @if($showMutation)
                    <span>Mutation: <em>{{ $mutation }}</em></span>
                  @endif
                </span>
              </button>
            @else
              <div class="trades-item-slot trades-item-slot--empty" aria-hidden="true"></div>
            @endif
          @endfor
        </div>
      </div>
    @endforeach
  </div>
  <div class="trades-card-foot">
    <a href="/trading/{{ $listing?->public_id }}" class="trades-view-btn">View Trade</a>
  </div>
</article>
