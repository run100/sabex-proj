@php
  $wikiDrawerLocalePrefix = rtrim((string) ($urlPrefix ?? ''), '/');
  $wikiDrawerPrefix = rtrim((string) (
    $productUrlPrefix
      ?? (str_starts_with((string) ($urlPrefix ?? ''), '/seo/sab/preview') ? '/seo/sab/preview' : '')
  ), '/');
  $wikiDrawerHref = static fn (string $slug): string => ($wikiDrawerPrefix === '' ? '' : $wikiDrawerPrefix).'/'.$slug;
  $wikiDrawerLocaleHref = static fn (string $slug): string => ($wikiDrawerLocalePrefix === '' ? '' : $wikiDrawerLocalePrefix).'/'.$slug;
  $wikiDrawerNewsHref = rtrim($wikiDrawerPrefix, '/').(
    str_starts_with($wikiDrawerPrefix, '/seo/sab/preview') ? '/news' : '/news/'
  );
  $wikiDrawerActive = (string) ($wikiPageSlug ?? '');
  $wikiDrawerCanonicalPath = rtrim((string) (parse_url((string) ($canonical ?? ''), PHP_URL_PATH) ?: ''), '/');
  $wikiDrawerPathActive = static function (string $slug) use ($wikiDrawerCanonicalPath): bool {
    if ($wikiDrawerCanonicalPath === '') {
      return false;
    }

    return $wikiDrawerCanonicalPath === '/'.$slug
      || str_ends_with($wikiDrawerCanonicalPath, '/'.$slug);
  };
  $wikiDrawerIcon = static function (string $name): string {
    $paths = [
      'calculator' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h.01"/>',
      'values' => '<path d="M3 17 9 11l4 4 8-8"/><path d="M14 7h7v7"/>',
      'codes' => '<path d="M4 9a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 1 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 1 0 0-4V9Z"/><path d="M9 7v10"/>',
      'news' => '<path d="M4 6h12a2 2 0 0 1 2 2v12H6a2 2 0 0 1-2-2V6Z"/><path d="M20 8v12M8 10h6M8 14h4"/>',
      'list' => '<path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>',
      'gallery' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><path d="m21 16-5-5L5 19"/>',
      'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/>',
      'grid' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>',
      'cycle' => '<path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/>',
      'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    ];

    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">'.($paths[$name] ?? '').'</svg>';
  };
  $wikiDrawerCopy = $t ?? [];
  $wikiDrawerToolItems = [
    ['href' => $wikiDrawerLocaleHref(\App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR), 'label' => $wikiDrawerCopy['nav_calculator'] ?? 'Calculator', 'icon' => 'calculator', 'active' => $wikiDrawerPathActive(\App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR)],
    ['href' => $wikiDrawerHref(\App\Services\Seo\SabRenderService::PAGE_VALUE_LIST), 'label' => $wikiDrawerCopy['nav_value_list'] ?? 'SAB Values', 'icon' => 'values', 'active' => $wikiDrawerPathActive(\App\Services\Seo\SabRenderService::PAGE_VALUE_LIST)],
    ['href' => $wikiDrawerLocaleHref(\App\Services\Seo\SabRenderService::PAGE_CODES), 'label' => $wikiDrawerCopy['nav_codes'] ?? 'Codes', 'icon' => 'codes', 'active' => $wikiDrawerPathActive(\App\Services\Seo\SabRenderService::PAGE_CODES)],
    ['href' => $wikiDrawerNewsHref, 'label' => $wikiDrawerCopy['nav_news'] ?? 'News', 'icon' => 'news', 'active' => str_contains($wikiDrawerCanonicalPath, '/news')],
    ['href' => $wikiDrawerLocaleHref(\App\Services\Seo\SabRenderService::PAGE_EXIST_COUNTS_LIST), 'label' => $wikiDrawerCopy['nav_exist_counts_list'] ?? 'Exist Count List', 'icon' => 'list', 'active' => $wikiDrawerPathActive(\App\Services\Seo\SabRenderService::PAGE_EXIST_COUNTS_LIST)],
    ['href' => $wikiDrawerHref(\App\Services\Seo\SabRenderService::PAGE_EXIST_COUNT_GALLERY), 'label' => $wikiDrawerCopy['nav_exist_count_gallery'] ?? 'Exist Count Gallery', 'icon' => 'gallery', 'active' => $wikiDrawerPathActive(\App\Services\Seo\SabRenderService::PAGE_EXIST_COUNT_GALLERY)],
  ];
  $wikiDrawerItems = [
    ['slug' => \App\Services\Seo\SabRenderService::PAGE_WIKI, 'label' => 'Wiki hub', 'icon' => 'book'],
    ['slug' => \App\Services\Seo\SabWikiPageDefinitions::PAGE_ALL_BRAINROTS, 'label' => 'All Brainrots', 'icon' => 'grid'],
    ['slug' => \App\Services\Seo\SabWikiPageDefinitions::PAGE_WIKI_REBIRTHS, 'label' => 'Rebirth List', 'icon' => 'cycle'],
    ['slug' => \App\Services\Seo\SabWikiPageDefinitions::PAGE_WIKI_ADMIN_ABUSE, 'label' => 'Admin Abuse', 'icon' => 'clock'],
  ];
  $wikiDrawerRarityCounts = [];
  if (! empty($wikiRarityLinks)) {
    foreach ($wikiRarityLinks as $link) {
      $wikiDrawerRarityCounts[(string) ($link['key'] ?? '')] = (int) ($link['count'] ?? 0);
    }
  } else {
    $wikiDrawerRarityCounts = app(\App\Services\Seo\SabRenderService::class)->wikiPublishedRarityCounts();
  }
@endphp
<div class="sab-wiki-drawer" data-wiki-drawer-root>
  <div class="sab-wiki-drawer__backdrop" data-wiki-drawer-close tabindex="-1"></div>
  <aside
    id="sab-wiki-drawer"
    class="sab-wiki-drawer__panel"
    role="dialog"
    aria-modal="true"
    aria-labelledby="sab-wiki-drawer-title"
    aria-hidden="true"
    inert
  >
    <div class="sab-wiki-drawer__head">
      <p class="sab-wiki-drawer__title" id="sab-wiki-drawer-title">Menu</p>
      <button type="button" class="sab-wiki-drawer__close" data-wiki-drawer-close>
        Close
      </button>
    </div>
    <nav class="sab-wiki-drawer__nav" aria-label="Menu">
      <p class="sab-wiki-drawer__group">Tools</p>
      @foreach($wikiDrawerToolItems as $item)
      <a href="{{ $item['href'] }}" class="sab-wiki-drawer__link{{ $item['active'] ? ' is-active' : '' }}"><span class="sab-wiki-drawer__icon">{!! $wikiDrawerIcon($item['icon']) !!}</span><span>{{ $item['label'] }}</span></a>
      @endforeach
      <p class="sab-wiki-drawer__group">Wiki</p>
      @foreach($wikiDrawerItems as $item)
      <a href="{{ $wikiDrawerHref($item['slug']) }}" class="sab-wiki-drawer__link{{ $wikiDrawerActive === $item['slug'] ? ' is-active' : '' }}"><span class="sab-wiki-drawer__icon">{!! $wikiDrawerIcon($item['icon']) !!}</span><span>{{ $item['label'] }}</span></a>
      @endforeach
      <p class="sab-wiki-drawer__group">Rarity</p>
      @foreach(\App\Services\Seo\SabWikiPageDefinitions::RARITY_PAGE_SLUGS as $rarityKey => $raritySlug)
      @php
        $rarityLabel = $rarityKey === 'og'
          ? 'OG'
          : \App\Services\Seo\SabRenderService::canonicalRarityLabel($rarityKey);
        $rarityClass = \App\Services\Seo\SabWikiPageDefinitions::rarityCssClass($rarityKey);
        $rarityCount = (int) ($wikiDrawerRarityCounts[$rarityKey] ?? 0);
      @endphp
      <a href="{{ $wikiDrawerHref($raritySlug) }}" class="sab-wiki-drawer__link sab-wiki-drawer__link--rarity {{ $rarityClass }}{{ $wikiDrawerActive === $raritySlug ? ' is-active' : '' }}"><span class="sab-wiki-drawer__swatch" aria-hidden="true"></span><span>{{ $rarityLabel }}</span><span class="sab-wiki-drawer__count">{{ number_format($rarityCount) }}</span></a>
      @endforeach
    </nav>
  </aside>
</div>
@once
<style>
  .sab-wiki-drawer {
    display: contents;
  }
  .sab-wiki-drawer__backdrop {
    position: fixed;
    inset: 0;
    z-index: 60;
    background: rgba(2, 6, 23, .62);
    opacity: 0;
    pointer-events: none;
    transition: opacity .18s ease;
  }
  .sab-wiki-drawer__panel {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 70;
    display: flex;
    flex-direction: column;
    width: min(20rem, 86vw);
    overflow: auto;
    background: #020617;
    border-right: 1px solid rgba(255, 255, 255, .1);
    box-shadow: 16px 0 40px rgba(2, 6, 23, .45);
    transform: translateX(-105%);
    transition: transform .2s ease;
  }
  .sab-wiki-drawer.is-open .sab-wiki-drawer__backdrop {
    opacity: 1;
    pointer-events: auto;
  }
  .sab-wiki-drawer.is-open .sab-wiki-drawer__panel {
    transform: translateX(0);
  }
  .sab-wiki-drawer__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: 1rem 1rem .75rem;
    border-bottom: 1px solid rgba(255, 255, 255, .08);
  }
  .sab-wiki-drawer__title {
    margin: 0;
    color: #f8fafc;
    font-size: .95rem;
    font-weight: 900;
  }
  .sab-wiki-drawer__close {
    border: 1px solid rgba(148, 163, 184, .35);
    border-radius: 9999px;
    background: transparent;
    color: #e2e8f0;
    cursor: pointer;
    font-size: .75rem;
    font-weight: 800;
    line-height: 1;
    padding: .4rem .7rem;
  }
  .sab-wiki-drawer__nav {
    display: grid;
    gap: .15rem;
    padding: .75rem .75rem 1.25rem;
  }
  .sab-wiki-drawer__group {
    margin: .7rem .4rem .25rem;
    color: #94a3b8;
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
  }
  .sab-wiki-drawer__group:first-child {
    margin-top: .15rem;
  }
  .sab-wiki-drawer__link {
    display: flex;
    align-items: center;
    gap: .55rem;
    border-radius: .55rem;
    color: #e2e8f0;
    font-size: .9rem;
    font-weight: 700;
    padding: .55rem .65rem;
    text-decoration: none;
  }
  .sab-wiki-drawer__icon {
    display: inline-flex;
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
    color: inherit;
  }
  .sab-wiki-drawer__icon svg {
    display: block;
    width: 100%;
    height: 100%;
  }
  .sab-wiki-drawer__link:hover,
  .sab-wiki-drawer__link.is-active {
    background: rgba(8, 145, 178, .14);
    color: #67e8f9;
  }
  .brainrot-rarity-common { --rarity-bg:rgba(22,163,74,.86); --rarity-bg-soft:rgba(22,163,74,.22); --rarity-fg:#86efac; --rarity-fg-strong:#f0fdf4; --rarity-ring:rgba(134,239,172,.28); --rarity-line:#00a113; }
  .brainrot-rarity-rare { --rarity-bg:rgba(37,99,235,.9); --rarity-bg-soft:rgba(37,99,235,.23); --rarity-fg:#93c5fd; --rarity-fg-strong:#eff6ff; --rarity-ring:rgba(147,197,253,.3); --rarity-line:#0b63f6; }
  .brainrot-rarity-epic { --rarity-bg:rgba(147,51,234,.88); --rarity-bg-soft:rgba(147,51,234,.24); --rarity-fg:#d8b4fe; --rarity-fg-strong:#faf5ff; --rarity-ring:rgba(216,180,254,.3); --rarity-line:#a119aa; }
  .brainrot-rarity-legendary { --rarity-bg:rgba(234,179,8,.9); --rarity-bg-soft:rgba(234,179,8,.24); --rarity-fg:#fde68a; --rarity-fg-strong:#18181b; --rarity-ring:rgba(253,230,138,.3); --rarity-line:#fff200; }
  .brainrot-rarity-mythic { --rarity-bg:rgba(239,68,68,.88); --rarity-bg-soft:rgba(239,68,68,.23); --rarity-fg:#fca5a5; --rarity-fg-strong:#fff1f2; --rarity-ring:rgba(252,165,165,.3); --rarity-line:#ff4d4d; }
  .brainrot-rarity-brainrot-god { --rarity-bg:rgba(217,70,239,.9); --rarity-bg-soft:rgba(217,70,239,.23); --rarity-fg:#f5d0fe; --rarity-fg-strong:#fdf4ff; --rarity-ring:rgba(245,208,254,.32); --rarity-line:#ff00d4; }
  .brainrot-rarity-secret { --rarity-bg:rgba(226,232,240,.9); --rarity-bg-soft:rgba(226,232,240,.18); --rarity-fg:#e2e8f0; --rarity-fg-strong:#0f172a; --rarity-ring:rgba(226,232,240,.32); --rarity-line:#f8fafc; }
  .brainrot-rarity-og { --rarity-bg:rgba(250,204,21,.96); --rarity-bg-soft:rgba(250,204,21,.18); --rarity-fg:#fef08a; --rarity-fg-strong:#111827; --rarity-ring:rgba(250,204,21,.34); --rarity-line:#fff200; }
  .brainrot-rarity-default { --rarity-bg:rgba(71,85,105,.86); --rarity-bg-soft:rgba(71,85,105,.24); --rarity-fg:#cbd5e1; --rarity-fg-strong:#f8fafc; --rarity-ring:rgba(148,163,184,.22); --rarity-line:#64748b; }
  .sab-wiki-drawer__link--rarity {
    color: var(--rarity-fg);
  }
  .sab-wiki-drawer__swatch {
    width: .55rem;
    height: .55rem;
    flex-shrink: 0;
    border-radius: 999px;
    background: var(--rarity-line);
  }
  .sab-wiki-drawer__count {
    margin-left: auto;
    color: inherit;
    font-size: .75rem;
    font-variant-numeric: tabular-nums;
    opacity: .78;
  }
  .sab-wiki-drawer__link--rarity:hover,
  .sab-wiki-drawer__link--rarity.is-active {
    background: var(--rarity-bg-soft);
    color: var(--rarity-fg-strong);
  }
  body.is-wiki-drawer-open {
    overflow: hidden;
  }
  @media (min-width: 768px) {
    .sab-wiki-drawer {
      display: none;
    }
  }
</style>
<script>
  (function () {
    var root = document.querySelector('[data-wiki-drawer-root]');
    var trigger = document.querySelector('[data-wiki-drawer-open]');
    var panel = document.getElementById('sab-wiki-drawer');
    if (!root || !trigger || !panel) return;

    var desktop = window.matchMedia('(min-width: 768px)');
    var lastFocus = null;

    function isOpen() {
      return root.classList.contains('is-open');
    }

    function setOpen(next) {
      if (desktop.matches) next = false;
      root.classList.toggle('is-open', next);
      document.body.classList.toggle('is-wiki-drawer-open', next);
      trigger.setAttribute('aria-expanded', next ? 'true' : 'false');
      panel.setAttribute('aria-hidden', next ? 'false' : 'true');
      if (next) {
        panel.removeAttribute('inert');
        lastFocus = document.activeElement;
        var closeButton = panel.querySelector('[data-wiki-drawer-close]');
        if (closeButton) closeButton.focus();
      } else {
        panel.setAttribute('inert', '');
        if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
      }
    }

    trigger.addEventListener('click', function () {
      setOpen(!isOpen());
    });

    root.addEventListener('click', function (event) {
      if (event.target && event.target.closest('[data-wiki-drawer-close]')) {
        setOpen(false);
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && isOpen()) setOpen(false);
    });

    if (typeof desktop.addEventListener === 'function') {
      desktop.addEventListener('change', function () {
        if (desktop.matches) setOpen(false);
      });
    }
  })();
</script>
@endonce
