@php
  $urlPrefix = $urlPrefix ?? '';
  $sabHomeHref = $urlPrefix === '' ? '/' : $urlPrefix;
  $wwwOrigin = rtrim(\App\Support\SabHost::origin('www'), '/');
  $onWwwHost = request()->getHost() === \App\Support\SabHost::host('www');
  $tradeBase = $onWwwHost ? '' : $wwwOrigin;
  $loginHref = \App\Support\TradePresenter::wwwUrl('/auth/roblox');
  $englishPrefix = $productUrlPrefix
    ?? (str_starts_with((string) ($urlPrefix ?? ''), '/seo/sab/preview') ? '/seo/sab/preview' : '');
  $navNewsHref = rtrim($englishPrefix, '/') . (
    str_starts_with((string) ($englishPrefix ?? ''), '/seo/sab/preview')
      ? '/news'
      : '/news/'
  );
  $tradingPath = '/'.trim(request()->path(), '/');
  $tradingNavActive = $tradingPath === \App\Support\TradePaths::marketplace()
    || str_starts_with($tradingPath, \App\Support\TradePaths::marketplace().'/');
  $valuesHref = rtrim((string) $englishPrefix, '/').'/'.\App\Services\Seo\SabRenderService::PAGE_VALUE_LIST;
  $calcHref = rtrim((string) $urlPrefix, '/').'/'.\App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR;
  $guidesHref = rtrim((string) ($englishPrefix !== '' ? $englishPrefix : $urlPrefix), '/').'/'.\App\Services\Seo\SabRenderService::PAGE_WIKI;
  $navCodesHref = rtrim((string) $urlPrefix, '/').'/'.\App\Services\Seo\SabRenderService::PAGE_CODES;
  $existCountListHref = rtrim((string) $urlPrefix, '/').'/'.\App\Services\Seo\SabRenderService::PAGE_EXIST_COUNTS_LIST;
  $createTradeHref = $tradeBase.\App\Support\TradePaths::create();
  $viewTradesHref = $tradeBase.\App\Support\TradePaths::marketplace();
  $viewOffersHref = $tradeBase.\App\Support\TradePaths::pending();
  $valuesActive = str_contains($tradingPath, \App\Services\Seo\SabRenderService::PAGE_VALUE_LIST);
  $calcActive = str_contains($tradingPath, \App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR);
  $guidesActive = $tradingPath === '/wiki' || str_starts_with($tradingPath, '/wiki/');
  $newsActive = str_contains($tradingPath, '/news');
  $codesActive = str_contains($tradingPath, \App\Services\Seo\SabRenderService::PAGE_CODES);
  $existCountListActive = str_contains($tradingPath, \App\Services\Seo\SabRenderService::PAGE_EXIST_COUNTS_LIST);
  $headerLogo = !empty($calculatorOnly) && !empty($brand['logo_html'])
    ? $brand['logo_html']
    : 'SAB<span class="sab-brand-accent">ExistCount</span>.com';
@endphp
<header class="sab-site-header">
  <div class="sab-site-header__bar max-w-7xl mx-auto px-4">
    <div class="sab-site-header__brand">
      <button type="button" class="sab-wiki-drawer-trigger" data-wiki-drawer-open aria-expanded="false" aria-controls="sab-wiki-drawer" aria-label="Open menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
        </svg>
      </button>
      <a href="{{ $sabHomeHref }}" class="sab-site-header__logo">{!! $headerLogo !!}</a>
    </div>
    <nav class="sab-main-nav sab-main-nav--compact" aria-label="Primary">
      <a href="{{ $valuesHref }}" class="sab-main-nav__link{{ $valuesActive ? ' is-active' : '' }}">Values</a>
      <a href="{{ $tradeBase }}{{ \App\Support\TradePaths::marketplace() }}" class="sab-main-nav__link{{ $tradingNavActive ? ' is-active' : '' }}">Trades</a>
      <a href="{{ $calcHref }}" class="sab-main-nav__link{{ $calcActive ? ' is-active' : '' }}">Calculator</a>
      <a href="{{ $guidesHref }}" class="sab-main-nav__link{{ $guidesActive ? ' is-active' : '' }}">Guides</a>
      <a href="{{ $navNewsHref }}" class="sab-main-nav__link{{ $newsActive ? ' is-active' : '' }}">{{ $t['nav_news'] ?? 'News' }}</a>
    </nav>
    <nav class="sab-main-nav sab-main-nav--desktop" aria-label="Primary">
      <details class="sab-trading-nav{{ $tradingNavActive ? ' is-active' : '' }}" data-sab-trading-nav>
        <summary class="sab-main-nav__link sab-trading-nav__trigger">
          <svg class="sab-trading-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M7 8h11l-3-3M17 16H6l3 3"/>
          </svg>
          Trade Ads
          <svg class="sab-trading-nav__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="m6 9 6 6 6-6"/>
          </svg>
        </summary>
        <div class="sab-trading-nav__menu">
          <p class="sab-trading-nav__label">TRADE ADS</p>
          <a href="{{ $createTradeHref }}" class="sab-trading-nav__item sab-trading-nav__item--primary">
            <svg class="sab-trading-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Create Trade Ad
          </a>
          <a href="{{ $viewTradesHref }}" class="sab-trading-nav__item sab-trading-nav__item--primary">
            <svg class="sab-trading-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/></svg>
            View Trade Ads
          </a>
          <a href="{{ $viewOffersHref }}" class="sab-trading-nav__item">
            <svg class="sab-trading-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 8h11l-3-3M17 16H6l3 3"/></svg>
            View Offers
          </a>
        </div>
      </details>
      <a href="{{ $calcHref }}" class="sab-main-nav__link sab-main-nav__link--badge{{ $calcActive ? ' is-active' : '' }}">
        {{ $t['nav_calculator'] ?? 'Calculator' }}
        <span class="sab-nav-badge sab-nav-badge--hot">HOT</span>
      </a>
      <a href="{{ $valuesHref }}" class="sab-main-nav__link sab-main-nav__link--badge{{ $valuesActive ? ' is-active' : '' }}">
        {{ $t['nav_value_list'] ?? 'SAB Values' }}
        <span aria-hidden="true">🔥</span>
      </a>
      <a href="{{ $guidesHref }}" class="sab-main-nav__link{{ $guidesActive ? ' is-active' : '' }}">Wiki</a>
      <a href="{{ $navCodesHref }}" class="sab-main-nav__link sab-main-nav__link--badge{{ $codesActive ? ' is-active' : '' }}">
        {{ $t['nav_codes'] ?? 'Codes' }}
        <span class="sab-nav-badge sab-nav-badge--new">NEW</span>
      </a>
      <a href="{{ $navNewsHref }}" class="sab-main-nav__link{{ $newsActive ? ' is-active' : '' }}">{{ $t['nav_news'] ?? 'News' }}</a>
      <a href="{{ $existCountListHref }}" class="sab-main-nav__link{{ $existCountListActive ? ' is-active' : '' }}">{{ $t['nav_exist_counts_list'] ?? 'Exist Count List' }}</a>
    </nav>
    <div class="sab-header-end">
      <div class="sab-nav-auth" data-nav-auth data-login-href="{{ $loginHref }}">
        <span class="sab-nav-auth__loading" data-nav-auth-loading aria-busy="true" aria-label="Loading account">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="42" stroke-dashoffset="16"/>
          </svg>
        </span>
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
@include('trades.partials.roblox-auth-modal')
@once
<style>
  .sab-site-header {
    position: sticky;
    top: 0;
    z-index: 80;
    width: 100%;
    min-width: 0;
    overflow: visible;
    min-height: 68px;
    border-bottom: 1px solid var(--sab-border, #22324a);
    background: rgba(5, 11, 24, .92);
    backdrop-filter: blur(14px);
  }
  .sab-site-header__bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    width: 100%;
    min-width: 0;
    min-height: 68px;
    overflow: visible;
  }
  .sab-site-header__logo {
    color: #f8fafc;
    font-size: 1.25rem;
    font-weight: 900;
    letter-spacing: -0.025em;
    text-decoration: none;
    white-space: nowrap;
  }
  .sab-brand-accent {
    color: #67e8f9;
  }
  .sab-site-header__brand {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: .5rem;
    flex-shrink: 0;
  }
  .sab-header-end {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    flex-shrink: 0;
    margin-left: auto;
  }
  .sab-nav-auth {
    display: inline-flex;
    align-items: center;
    gap: .55rem;
    flex-shrink: 0;
  }
  .sab-nav-auth__loading {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 4.4rem;
    min-height: 2.15rem;
    color: #67e8f9;
  }
  .sab-nav-auth__loading svg {
    width: 1.75rem;
    height: 1.75rem;
    animation: sab-nav-auth-spin .8s linear infinite;
  }
  @keyframes sab-nav-auth-spin {
    to { transform: rotate(360deg); }
  }
  .sab-nav-auth__signin {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2.15rem;
    padding: .4rem 1.05rem;
    border: 0;
    border-radius: 12px;
    background: var(--sab-blue, #3b82f6);
    color: #fff;
    font-size: .875rem;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    font-family: inherit;
  }
  .sab-nav-auth__signin:hover {
    background: var(--sab-blue-hover, #2563eb);
    color: #fff;
  }
  .sab-nav-auth__out {
    color: #67e8f9;
    font-size: .875rem;
    font-weight: 700;
    text-decoration: none;
    background: none;
    border: 0;
    padding: 0;
    cursor: pointer;
    font-family: inherit;
  }
  .sab-nav-auth__link {
    position: relative;
    display: inline-flex;
    width: 1.75rem;
    height: 1.75rem;
    align-items: center;
    justify-content: center;
    color: #cbd5e1;
    text-decoration: none;
  }
  .sab-nav-auth__link svg {
    width: 1.15rem;
    height: 1.15rem;
  }
  .sab-nav-auth__link:hover,
  .sab-nav-auth__out:hover {
    color: #a5f3fc;
  }
  .sab-nav-auth__link.is-active {
    color: #67e8f9;
  }
  .sab-nav-auth__quick-link {
    display: none;
    flex-shrink: 0;
  }
  .sab-nav-auth__quick-link--calculator svg {
    width: 1.35rem;
    height: 1.35rem;
  }
  .sab-nav-auth__chip {
    display: inline-flex;
    align-items: center;
    color: #f8fafc;
    text-decoration: none;
  }
  .sab-nav-auth__avatar {
    display: block;
    width: 1.75rem;
    height: 1.75rem;
    flex-shrink: 0;
    overflow: hidden;
    border-radius: 50%;
    object-fit: cover;
  }
  .sab-nav-auth__name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .sab-nav-auth__badge {
    position: absolute;
    top: -.2rem;
    right: -.35rem;
    display: inline-flex;
    min-width: 1rem;
    align-items: center;
    justify-content: center;
    padding: .05rem .28rem;
    border-radius: 999px;
    background: var(--sab-blue, #3b82f6);
    color: #fff;
    font-size: .62rem;
    font-weight: 700;
    line-height: 1.2;
  }
  .sab-account-nav {
    position: relative;
    flex-shrink: 0;
  }
  .sab-account-nav > summary {
    list-style: none;
    cursor: pointer;
  }
  .sab-account-nav > summary::-webkit-details-marker,
  .sab-account-nav > summary::marker {
    display: none;
    content: '';
  }
  .sab-account-nav__menu {
    position: absolute;
    top: calc(100% + .45rem);
    right: 0;
    z-index: 90;
    min-width: 13.5rem;
    padding: .55rem;
    border: 1px solid var(--sab-border, #22324a);
    border-radius: .85rem;
    background: #0b1628;
    box-shadow: 0 12px 32px rgba(2, 6, 23, .45);
  }
  .sab-account-nav__head {
    display: flex;
    align-items: center;
    gap: .65rem;
    margin-bottom: .45rem;
    padding: .55rem .6rem;
    border-radius: .65rem;
    background: rgba(15, 23, 42, .72);
  }
  .sab-account-nav__photo {
    display: block;
    width: 2.5rem;
    height: 2.5rem;
    flex-shrink: 0;
    border-radius: 50%;
    object-fit: cover;
  }
  .sab-account-nav__head .sab-nav-auth__name {
    color: #f8fafc;
    font-size: .875rem;
    font-weight: 800;
  }
  .sab-account-nav__item {
    display: flex;
    width: 100%;
    align-items: center;
    gap: .55rem;
    min-height: 2.2rem;
    padding: .4rem .55rem;
    border: 0;
    border-radius: .5rem;
    background: transparent;
    color: #f8fafc;
    font-family: inherit;
    font-size: .8125rem;
    font-weight: 700;
    text-align: left;
    text-decoration: none;
    cursor: pointer;
  }
  .sab-account-nav__item svg {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
  }
  .sab-account-nav__item:hover {
    background: rgba(103, 232, 249, .08);
    color: #67e8f9;
  }
  .sab-account-nav__item--out {
    color: #fb7185;
  }
  .sab-account-nav__item--out:hover {
    background: rgba(251, 113, 133, .1);
    color: #fb7185;
  }
  .sab-wiki-drawer-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    flex-shrink: 0;
    border: 1px solid var(--sab-border, #22324a);
    border-radius: .6rem;
    background: var(--sab-panel, #0b1628);
    color: #e2e8f0;
    cursor: pointer;
  }
  .sab-wiki-drawer-trigger svg {
    width: 1.15rem;
    height: 1.15rem;
  }
  .sab-main-nav {
    display: none;
    overflow: visible;
  }
  .sab-main-nav__link {
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
    min-height: 44px;
    padding: 0 4px;
    border-bottom: 2px solid transparent;
    color: var(--sab-text-secondary, #9ca3af);
    font-size: .8125rem;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
  }
  .sab-main-nav__link--badge {
    align-items: center;
    gap: .2rem;
  }
  .sab-main-nav__link:hover {
    color: #67e8f9;
  }
  .sab-main-nav__link.is-active {
    color: #fff;
    border-bottom-color: var(--sab-blue, #3b82f6);
  }
  .sab-nav-badge {
    border-radius: 9999px;
    padding: .1rem .25rem;
    font-size: 7px;
    line-height: 1;
    font-weight: 900;
  }
  .sab-nav-badge--hot {
    background: #f43f5e;
    color: #fff;
    box-shadow: 0 1px 4px rgba(76, 5, 25, .45);
  }
  .sab-nav-badge--new {
    background: #06b6d4;
    color: #042f2e;
    box-shadow: 0 1px 4px rgba(8, 47, 73, .4);
  }
  @media (min-width: 768px) {
    .sab-site-header__logo {
      font-size: 1.5rem;
    }
    .sab-wiki-drawer-trigger {
      display: none;
    }
    .sab-main-nav--desktop {
      display: flex;
      flex: 1;
      flex-wrap: nowrap;
      justify-content: center;
      align-items: center;
      gap: .55rem .7rem;
      min-width: 0;
      overflow: visible;
    }
    .sab-trading-nav {
      position: relative;
      flex-shrink: 0;
    }
    .sab-trading-nav__trigger {
      list-style: none;
      cursor: pointer;
      gap: .3rem;
    }
    .sab-trading-nav > summary {
      list-style: none;
    }
    .sab-trading-nav__trigger::-webkit-details-marker {
      display: none;
    }
    .sab-trading-nav.is-active .sab-trading-nav__trigger,
    .sab-trading-nav[open] .sab-trading-nav__trigger {
      color: #fff;
      border-bottom-color: var(--sab-blue, #3b82f6);
    }
    .sab-trading-nav__icon,
    .sab-trading-nav__chevron {
      width: .85rem;
      height: .85rem;
      flex-shrink: 0;
    }
    .sab-trading-nav[open] .sab-trading-nav__chevron {
      transform: rotate(180deg);
    }
    .sab-trading-nav__menu {
      position: absolute;
      top: calc(100% - 2px);
      left: 0;
      z-index: 90;
      min-width: 13.5rem;
      padding: .65rem;
      border: 1px solid var(--sab-border, #22324a);
      border-radius: .75rem;
      background: #0b1628;
      box-shadow: 0 12px 32px rgba(2, 6, 23, .45);
    }
    .sab-trading-nav__label {
      margin: 0 .35rem .4rem;
      color: #94a3b8;
      font-size: .68rem;
      font-weight: 800;
      letter-spacing: .08em;
    }
    .sab-trading-nav__item {
      display: flex;
      align-items: center;
      gap: .5rem;
      min-height: 2.25rem;
      padding: .4rem .55rem;
      border-radius: .5rem;
      color: #e2e8f0;
      font-size: .8125rem;
      font-weight: 700;
      text-decoration: none;
    }
    .sab-trading-nav__item:hover {
      background: rgba(103, 232, 249, .08);
      color: #67e8f9;
    }
    .sab-trading-nav__item--primary {
      width: 100%;
      box-sizing: border-box;
      min-height: 2.55rem;
      margin-bottom: .45rem;
      border-radius: 9999px;
      background: var(--sab-blue, #3b82f6);
      color: #fff;
    }
    .sab-trading-nav__item--primary:hover {
      background: var(--sab-blue-hover, #2563eb);
      color: #fff;
    }
    .sab-header-end {
      margin-left: 0;
    }
  }
  @media (max-width: 767px) {
    .sab-nav-auth {
      gap: .3rem;
    }
    .sab-nav-auth__quick-link {
      display: inline-flex;
    }
  }
  @media (max-width: 479px) {
    .sab-site-header__bar {
      gap: 0;
    }
    .sab-nav-auth {
      gap: .2rem;
    }
    .sab-nav-auth__signin {
      padding-right: .7rem;
      padding-left: .7rem;
    }
    label.sab-language-switch--mobile {
      display: none;
    }
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
  document.addEventListener('click', function (event) {
    document.querySelectorAll('[data-sab-trading-nav][open], [data-sab-account-nav][open]').forEach(function (nav) {
      if (!nav.contains(event.target)) nav.removeAttribute('open');
    });
  });
  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('[data-sab-trading-nav][open], [data-sab-account-nav][open]').forEach(function (nav) {
      nav.removeAttribute('open');
    });
  });
</script>
@endonce
