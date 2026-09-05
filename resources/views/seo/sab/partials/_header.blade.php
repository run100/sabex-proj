<header class="border-b border-white/10 bg-slate-950/90 sticky top-0 z-50 backdrop-blur">
  <div class="max-w-7xl mx-auto px-4 py-3 md:py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-2 md:gap-3">
    @php $sabHomeHref = (($urlPrefix ?? '') === '') ? '/' : $urlPrefix; @endphp
    <div class="flex items-center justify-between gap-3">
      <div class="flex min-w-0 items-center gap-2">
        <button type="button" class="sab-wiki-drawer-trigger" data-wiki-drawer-open aria-expanded="false" aria-controls="sab-wiki-drawer" aria-label="Open menu">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
          </svg>
        </button>
        <a href="{{ $sabHomeHref }}" class="text-xl md:text-2xl font-black tracking-tight">SAB<span class="text-cyan-400">ExistCount</span>.com</a>
      </div>
      @if(!empty($languageLinks ?? []))
      @php
        $activeLanguage = collect($languageLinks)->firstWhere('active', true);
      @endphp
      <label class="sab-language-switch sab-language-switch--mobile" aria-label="{{ $t['language_label'] ?? 'Language' }}">
        <span class="sab-language-current">{{ $activeLanguage['label'] ?? ($t['language_current'] ?? 'EN') }}</span>
        <select data-sab-language-switch>
          @foreach($languageLinks as $languageLink)
          <option value="{{ $languageLink['href'] }}" @selected($languageLink['active'])>{{ $languageLink['label'] }}</option>
          @endforeach
        </select>
      </label>
      @endif
    </div>
    @php
      $englishPrefix = $productUrlPrefix
        ?? (str_starts_with((string) ($urlPrefix ?? ''), '/seo/sab/preview') ? '/seo/sab/preview' : '');
      $navCodesHref = rtrim((string) ($urlPrefix ?? ''), '/') . '/' . \App\Services\Seo\SabRenderService::PAGE_CODES;
      $navNewsHref = rtrim($englishPrefix, '/') . (
        str_starts_with((string) ($englishPrefix ?? ''), '/seo/sab/preview')
          ? '/news'
          : '/news/'
      );
      $tradingPath = '/'.trim(request()->path(), '/');
      $tradingNavActive = $tradingPath === \App\Support\TradePaths::marketplace()
        || str_starts_with($tradingPath, \App\Support\TradePaths::marketplace().'/');
    @endphp
    <div class="flex min-w-0 flex-col gap-2 md:flex-row md:items-center md:justify-end">
    <nav class="sab-main-nav flex gap-3 overflow-x-auto whitespace-nowrap pb-1 text-sm text-slate-300 md:flex-wrap md:gap-4 md:overflow-visible md:pb-0">
      <details class="sab-trading-nav{{ $tradingNavActive ? ' is-active' : '' }}" data-sab-trading-nav>
        <summary class="sab-trading-nav__trigger">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h11l-3-3M17 16H6l3 3"/>
          </svg>
          <span>Trading</span>
          <svg class="sab-trading-nav__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
          </svg>
        </summary>
        <div class="sab-trading-nav__menu">
          <p class="sab-trading-nav__label">Trading</p>
          <a href="{{ \App\Support\TradePaths::create() }}" class="sab-trading-nav__item sab-trading-nav__item--primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <circle cx="12" cy="12" r="9"/>
              <path stroke-linecap="round" d="M12 8v8M8 12h8"/>
            </svg>
            Create Trade Ad
          </a>
          <a href="{{ \App\Support\TradePaths::marketplace() }}" class="sab-trading-nav__item sab-trading-nav__item--primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" d="M4 6h16M4 12h10M4 18h7"/>
            </svg>
            View Trade Ads
          </a>
          <a href="{{ \App\Support\TradePaths::pending() }}" class="sab-trading-nav__item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h11l-3-3M17 16H6l3 3"/>
            </svg>
            View Offers
          </a>
        </div>
      </details>
      <a href="{{ $urlPrefix }}/{{ \App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR }}" class="hover:text-cyan-300" style="display:inline-flex;align-items:flex-start;gap:.25rem">
        {{ $t['nav_calculator'] ?? 'Calculator' }}
        <span style="margin-top:.05rem;border-radius:9999px;background:#f43f5e;padding:.1rem .25rem;font-size:7px;line-height:1;font-weight:900;color:#fff;box-shadow:0 1px 4px rgba(76,5,25,.45)">HOT</span>
      </a>
      <a href="{{ $englishPrefix }}/{{ \App\Services\Seo\SabRenderService::PAGE_VALUE_LIST }}" class="hover:text-cyan-300" style="display:inline-flex;align-items:center;gap:.2rem">{{ $t['nav_value_list'] ?? 'SAB Values' }} <span aria-hidden="true">🔥</span></a>
      <a href="{{ rtrim((string) $englishPrefix, '/') }}/{{ \App\Services\Seo\SabRenderService::PAGE_WIKI }}" class="hover:text-cyan-300">Wiki</a>
      <a href="{{ $navCodesHref }}" class="hover:text-cyan-300" style="display:inline-flex;align-items:flex-start;gap:.25rem">
        {{ $t['nav_codes'] ?? 'Codes' }}
        <span style="margin-top:.05rem;border-radius:9999px;background:#06b6d4;padding:.1rem .25rem;font-size:7px;line-height:1;font-weight:900;color:#042f2e;box-shadow:0 1px 4px rgba(8,47,73,.4)">NEW</span>
      </a>
      <a href="{{ $navNewsHref }}" class="hover:text-cyan-300">{{ $t['nav_news'] ?? 'News' }}</a>
      <a href="{{ $urlPrefix }}/{{ \App\Services\Seo\SabRenderService::PAGE_EXIST_COUNTS_LIST }}" class="hover:text-cyan-300">{{ $t['nav_exist_counts_list'] ?? 'Exist Count List' }}</a>
      <a href="{{ $englishPrefix }}/{{ \App\Services\Seo\SabRenderService::PAGE_EXIST_COUNT_GALLERY }}" class="hover:text-cyan-300">{{ $t['nav_exist_count_gallery'] ?? 'Exist Count Gallery' }}</a>
      {{-- Temporarily hidden: home anchor links
      <a href="#list" class="hover:text-cyan-300">{{ $t['nav_list'] }}</a>
      <a href="#how" class="hover:text-cyan-300">{{ $t['nav_how'] }}</a>
      <a href="#rarity" class="hover:text-cyan-300">{{ $t['nav_rarity'] }}</a>
      <a href="#faq" class="hover:text-cyan-300">{{ $t['nav_faq'] }}</a>
      --}}
    </nav>
    @if(!empty($languageLinks ?? []))
    @php
      $activeLanguage = $activeLanguage ?? collect($languageLinks)->firstWhere('active', true);
    @endphp
    <label class="sab-language-switch sab-language-switch--desktop" aria-label="{{ $t['language_label'] ?? 'Language' }}">
      <span class="sab-language-globe" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <path d="M2 12h20"/>
          <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
        </svg>
      </span>
      <span class="sab-language-current">{{ $activeLanguage['label'] ?? ($t['language_current'] ?? 'EN') }}</span>
      <select data-sab-language-switch>
        @foreach($languageLinks as $languageLink)
        <option value="{{ $languageLink['href'] }}" @selected($languageLink['active'])>{{ $languageLink['label'] }}</option>
        @endforeach
      </select>
    </label>
    @endif
    </div>
  </div>
</header>
@once
<style>
  .sab-wiki-drawer-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    flex-shrink: 0;
    border: 1px solid rgba(148, 163, 184, .34);
    border-radius: .6rem;
    background: rgba(15, 23, 42, .82);
    color: #e2e8f0;
    cursor: pointer;
  }
  .sab-wiki-drawer-trigger svg {
    width: 1.15rem;
    height: 1.15rem;
  }
  @media (min-width: 768px) {
    .sab-wiki-drawer-trigger {
      display: none;
    }
  }
  .sab-main-nav {
    scrollbar-width: none;
  }
  .sab-main-nav::-webkit-scrollbar {
    display: none;
  }
  .sab-main-nav:has(.sab-trading-nav[open]) {
    overflow: visible;
  }
  .sab-trading-nav {
    position: relative;
    flex-shrink: 0;
  }
  .sab-trading-nav__trigger {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    list-style: none;
    cursor: pointer;
    color: inherit;
  }
  .sab-trading-nav__trigger::-webkit-details-marker {
    display: none;
  }
  .sab-trading-nav__trigger:hover,
  .sab-trading-nav.is-active .sab-trading-nav__trigger,
  .sab-trading-nav[open] .sab-trading-nav__trigger {
    color: #67e8f9;
  }
  .sab-trading-nav__trigger > svg:first-child,
  .sab-trading-nav__chevron {
    width: .95rem;
    height: .95rem;
    flex-shrink: 0;
  }
  .sab-trading-nav[open] .sab-trading-nav__chevron {
    transform: rotate(180deg);
  }
  .sab-trading-nav__menu {
    position: absolute;
    top: calc(100% + .45rem);
    left: 0;
    z-index: 60;
    min-width: 13.5rem;
    padding: .7rem;
    border: 1px solid rgba(148, 163, 184, .28);
    background: #0f172a;
    box-shadow: 0 12px 28px rgba(2, 6, 23, .45);
    white-space: normal;
  }
  .sab-trading-nav__label {
    margin: 0 0 .5rem;
    color: #94a3b8;
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
  }
  .sab-trading-nav__item {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-top: .4rem;
    padding: .55rem .7rem;
    border: 1px solid rgba(148, 163, 184, .28);
    background: #0b1220;
    color: #f8fafc;
    font-size: .82rem;
    font-weight: 700;
    text-decoration: none;
  }
  .sab-trading-nav__item:first-of-type {
    margin-top: 0;
  }
  .sab-trading-nav__item svg {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
  }
  .sab-trading-nav__item--primary {
    border-color: transparent;
    background: #155e75;
    color: #ecfeff;
  }
  .sab-trading-nav__item:hover {
    color: #ecfeff;
    background: #164e63;
  }
  .sab-trading-nav__item--primary:hover {
    background: #0e7490;
  }
  .sab-language-switch {
    align-items: center;
    border: 1px solid rgba(148, 163, 184, .34);
    border-radius: 9999px;
    background: rgba(15, 23, 42, .82);
    color: #e2e8f0;
    flex-shrink: 0;
    gap: .35rem;
    min-height: 2rem;
    padding: .25rem .55rem;
    position: relative;
  }
  .sab-language-switch--mobile {
    display: inline-flex;
  }
  .sab-language-switch--desktop {
    display: none;
  }
  @media (min-width: 768px) {
    .sab-language-switch--mobile {
      display: none;
    }
    .sab-language-switch--desktop {
      display: inline-flex;
    }
  }
  .sab-language-switch select {
    appearance: none;
    background: transparent;
    border: 0;
    color: inherit;
    cursor: pointer;
    font-size: .78rem;
    font-weight: 800;
    inset: 0;
    opacity: 0;
    position: absolute;
    width: 100%;
  }
  .sab-language-current {
    font-size: .78rem;
    font-weight: 900;
    line-height: 1;
  }
  .sab-language-globe {
    color: #67e8f9;
    display: flex;
    flex-shrink: 0;
    line-height: 0;
  }
  .sab-language-globe svg {
    height: .875rem;
    width: .875rem;
  }
</style>
<script>
  document.addEventListener('change', function (event) {
    var target = event.target;
    if (!target || !target.matches('[data-sab-language-switch]')) return;
    if (target.value) window.location.href = target.value;
  });
</script>
@endonce
