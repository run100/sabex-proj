@php
  $row = $row ?? [];
  $productUrlPrefix = $productUrlPrefix ?? '';
  $slug = (string) ($row['s'] ?? '');
  $name = (string) ($row['n'] ?? '');
  $rarityKey = (string) ($row['r'] ?? '');
  $rarityLabel = (string) ($row['rl'] ?? '');
  $badgeClass = match ($rarityKey) {
    'common' => 'brainrot-rarity-common',
    'rare' => 'brainrot-rarity-rare',
    'epic' => 'brainrot-rarity-epic',
    'legendary' => 'brainrot-rarity-legendary',
    'mythic' => 'brainrot-rarity-mythic',
    'brainrot god' => 'brainrot-rarity-brainrot-god',
    'secret' => 'brainrot-rarity-secret',
    'og' => 'brainrot-rarity-og',
    default => 'brainrot-rarity-default',
  };
  $canOpen = ! empty($row['link']) && $slug !== '';
  $href = $canOpen ? $productUrlPrefix . '/products/' . $slug : '';
  $img = (string) ($row['img'] ?? '');
  $kind = (string) ($row['k'] ?? 'none');
  $ecs = (string) ($row['ecs'] ?? '—');
  $mn = (string) ($row['mn'] ?? '');
  $mc = $row['mc'] ?? null;
  $tn = (string) ($row['tn'] ?? '');
  $tc = $row['tc'] ?? null;
  $mutHref = ($canOpen && $mn !== '') ? $href . '#mutation-' . \Illuminate\Support\Str::slug($mn) : '';
  $traitHref = ($canOpen && $tn !== '') ? $href . '#trait-' . \Illuminate\Support\Str::slug($tn) : '';
  $sigTone = match ((string) ($row['sk'] ?? '')) {
    'very_high' => 'brainrot-signal-high',
    'medium' => 'brainrot-signal-medium',
    default => 'brainrot-signal-low',
  };
  $sigSymbol = (string) ($row['ss'] ?? '');
  $ecSort = $row['e'] ?? '';
  $ecSortTier = $row['tier'] ?? 2;
  $searchText = (string) ($row['q'] ?? '');
@endphp
<tr class="sab-rarity-row {{ $badgeClass }}{{ $canOpen ? ' is-clickable' : '' }}"
    data-search="{{ $searchText }}"
    data-rarity="{{ $rarityKey }}"
    data-sort-exist="{{ $ecSort }}"
    data-sort-tier="{{ $ecSortTier }}"
    @if($canOpen) onclick="location.href='{{ $href }}'" @endif>
  <td class="sab-col-brainrot sab-ecl-td">
    @if($canOpen)
    <a href="{{ $href }}" class="sab-brainrot-link sab-ecl-link">
    @else
    <div class="sab-brainrot-link sab-ecl-link">
    @endif
      @if($img !== '')
      <img src="{{ $img }}" alt="{{ $name }}" width="64" height="64" class="sab-brainrot-thumb sab-ecl-thumb" loading="lazy" />
      @else
      <div class="sab-brainrot-thumb sab-ecl-thumb"></div>
      @endif
      <div class="sab-ecl-name-wrap">
        @if($rarityLabel !== '')
        <div class="sab-brainrot-mobile-meta brainrot-title-line">
          <span class="sab-brainrot-name">{{ $name }}</span>
          <span class="brainrot-rarity-pill brainrot-inline-rarity {{ $badgeClass }}">{{ $rarityLabel }}</span>
        </div>
        <div class="sab-brainrot-name-only sab-brainrot-name">{{ $name }}</div>
        @else
        <div class="sab-brainrot-name">{{ $name }}</div>
        @endif
      </div>
    @if($canOpen)</a>@else</div>@endif
  </td>
  <td class="sab-col-rarity sab-ecl-td">
    @if($rarityLabel !== '')
    <span class="brainrot-rarity-pill brainrot-table-rarity {{ $badgeClass }}">{{ $rarityLabel }}</span>
    @else
    <span class="sab-ecl-muted">—</span>
    @endif
  </td>
  <td class="sab-col-count sab-count-cell sab-ecl-td sab-ecl-count">
    @if($kind === 'known')
    <div class="sab-exist-count-main">{{ $ecs }}</div>
    @elseif($kind === 'estimated')
    <div class="sab-exist-count-main sab-ecl-est">{{ $ecs }}</div>
    <div class="sab-ecl-guess" title="{{ $name }} exist count {{ $ecs }}" aria-label="{{ $name }} exist count {{ $ecs }}">~</div>
    @else
    <div class="sab-exist-count-main sab-ecl-muted">—</div>
    @endif
  </td>
  <td class="sab-col-mut sab-mut-cell sab-ecl-td sab-ecl-count">
    @if($mn !== '' || $tn !== '')
    <div class="sab-mut-chips">
      @if($mn !== '')
        @if($mutHref !== '')
        <a href="{{ $mutHref }}" title="{{ $mn }}" onclick="event.stopPropagation()" class="sab-mut-chip">
          <span class="sab-mut-chip-label sab-mut-chip-label-m" aria-hidden="true"></span>
          @if($mc !== null)<span class="sab-mut-chip-count">{{ $mc }}</span>@endif
        </a>
        @else
        <span class="sab-mut-chip" title="{{ $mn }}">
          <span class="sab-mut-chip-label sab-mut-chip-label-m" aria-hidden="true"></span>
          @if($mc !== null)<span class="sab-mut-chip-count">{{ $mc }}</span>@endif
        </span>
        @endif
      @endif
      @if($tn !== '')
        @if($traitHref !== '')
        <a href="{{ $traitHref }}" title="{{ $tn }}" onclick="event.stopPropagation()" class="sab-mut-chip">
          <span class="sab-mut-chip-label sab-mut-chip-label-t" aria-hidden="true"></span>
          @if($tc !== null)<span class="sab-mut-chip-count">{{ $tc }}</span>@endif
        </a>
        @else
        <span class="sab-mut-chip" title="{{ $tn }}">
          <span class="sab-mut-chip-label sab-mut-chip-label-t" aria-hidden="true"></span>
          @if($tc !== null)<span class="sab-mut-chip-count">{{ $tc }}</span>@endif
        </span>
        @endif
      @endif
    </div>
    <div class="sab-mut-desktop">
      <div>
        @if($mn !== '')
          @if($mutHref !== '')
          <a href="{{ $mutHref }}" onclick="event.stopPropagation()" class="sab-ecl-mut-row group">
            <span class="sab-mut-name sab-ecl-mut-name">{{ $mn }}</span>
            @if($mc !== null)<span class="sab-mut-count">{{ $mc }}</span>@endif
          </a>
          @else
          <span class="sab-ecl-mut-row">
            <span class="sab-mut-name sab-ecl-mut-name">{{ $mn }}</span>
            @if($mc !== null)<span class="sab-mut-count">{{ $mc }}</span>@endif
          </span>
          @endif
        @else
        <span class="sab-ecl-muted">—</span>
        @endif
      </div>
      @if($tn !== '')
      <div class="sab-ecl-mut-second">
        @if($traitHref !== '')
        <a href="{{ $traitHref }}" onclick="event.stopPropagation()" class="sab-ecl-mut-row group">
          <span class="sab-mut-name sab-ecl-mut-name">{{ $tn }}</span>
          @if($tc !== null)<span class="sab-mut-count">{{ $tc }}</span>@endif
        </a>
        @else
        <span class="sab-ecl-mut-row">
          <span class="sab-mut-name sab-ecl-mut-name">{{ $tn }}</span>
          @if($tc !== null)<span class="sab-mut-count">{{ $tc }}</span>@endif
        </span>
        @endif
      </div>
      @endif
    </div>
    @else
    <span class="sab-ecl-muted">—</span>
    @endif
  </td>
  <td class="sab-col-signal sab-ecl-td">
    @if($sigSymbol !== '')
    <span class="brainrot-signal {{ $sigTone }}" data-signal-symbol="{{ $sigSymbol }}" aria-hidden="true"></span>
    @else
    <span class="sab-ecl-muted">—</span>
    @endif
  </td>
</tr>
