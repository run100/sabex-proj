@php
  $builderConfig = [
      'data' => $calculatorData ?? ['brainrots' => [], 'traits' => [], 'streakMultipliers' => []],
      'ui' => $calculatorUi ?? $calcUi ?? [],
      'rarityAll' => $t['rarity_filter_all'] ?? 'All',
      'publishUrl' => $tradePublishUrl ?? '/api/v1/trading/trades',
      'csrf' => csrf_token(),
      'maxItemsPerSide' => (int) ($maxItemsPerSide ?? config('sab-trades.max_items_per_side', 9)),
      'draftKey' => 'sab-trade-draft',
  ];
@endphp
<script type="application/json" id="sab-trade-builder-config">{!! json_encode($builderConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
<script src="/static/js/sab-trade-builder.js?v={{ filemtime(public_path('static/js/sab-trade-builder.js')) }}" defer></script>
