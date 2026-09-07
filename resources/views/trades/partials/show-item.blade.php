@php
  $itemName = (string) $item->brainrot_name_snapshot;
  $mutation = trim((string) $item->mutation_name_snapshot);
  $mutationLabel = $mutation !== '' ? $mutation : '—';
  $showMutationBadge = $mutation !== '';
  $traitNames = $item->traits
      ->pluck('trait_name_snapshot')
      ->map(fn ($name) => trim((string) $name))
      ->filter()
      ->values();
  $mutationColor = '#67e8f9';
  foreach ($mutationColors as $keyword => $color) {
      if (stripos($mutation, $keyword) !== false) {
          $mutationColor = $color;
          break;
      }
  }
  $mutationIsGradient = str_starts_with($mutationColor, 'linear-gradient');
@endphp
<button type="button" class="trades-show__item" data-item-tip>
  <span class="trades-show__art">
    @if($showMutationBadge)
      <span class="trades-show__mut">{{ mb_substr($mutation, 0, 1) }}</span>
    @endif
    {{-- <span class="trades-show__qty">x1</span> --}}
    @if($item->image_url_snapshot)
      <img src="{{ $item->image_url_snapshot }}" alt="{{ $itemName }}" width="128" height="128">
    @else
      <span class="trades-show__item-name">{{ $itemName }}</span>
    @endif
  </span>
  <span class="trades-show__item-bar">
    <strong>{{ \App\Support\TradePresenter::compactValue($item->final_value_snapshot) }}</strong>
    <em>{{ $traitNames->isNotEmpty() ? $traitNames->implode(', ') : '—' }}</em>
  </span>
  <span class="trades-item-tip" hidden>
    <strong>{{ $itemName }}</strong>
    <span>Mutation:
      <em @class(['is-gradient' => $mutationIsGradient]) @if($mutationIsGradient) style="background-image: {{ $mutationColor }}" @else style="color: {{ $mutationColor }}" @endif>{{ $mutationLabel }}</em>
    </span>
    <span>Traits:</span>
    <span class="trades-item-tip__chips">
      @forelse($traitNames as $traitName)
        <em>{{ $traitName }}</em>
      @empty
        <em>—</em>
      @endforelse
    </span>
  </span>
</button>
