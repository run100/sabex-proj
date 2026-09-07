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
  $guidesHref = rtrim((string) ($englishPrefix !== '' ? $englishPrefix : $urlPrefix), '/').'/wiki';
  $valuesActive = str_contains($tradingPath, \App\Services\Seo\SabRenderService::PAGE_VALUE_LIST);
  $calcActive = str_contains($tradingPath, \App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR);
  $guidesActive = $tradingPath === '/wiki' || str_starts_with($tradingPath, '/wiki/');
  $newsActive = str_contains($tradingPath, '/news');
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
    <nav class="sab-main-nav" aria-label="Primary">
      <a href="{{ $valuesHref }}" class="sab-main-nav__link{{ $valuesActive ? ' is-active' : '' }}">Values</a>
      <a href="{{ $tradeBase }}{{ \App\Support\TradePaths::marketplace() }}" class="sab-main-nav__link{{ $tradingNavActive ? ' is-active' : '' }}">Trades</a>
      <a href="{{ $calcHref }}" class="sab-main-nav__link{{ $calcActive ? ' is-active' : '' }}">Calculator</a>
      <a href="{{ $guidesHref }}" class="sab-main-nav__link{{ $guidesActive ? ' is-active' : '' }}">Guides</a>
      <a href="{{ $navNewsHref }}" class="sab-main-nav__link{{ $newsActive ? ' is-active' : '' }}">{{ $t['nav_news'] ?? 'News' }}</a>
    </nav>
    <div class="sab-header-end">
      <div class="sab-nav-auth" data-nav-auth data-login-href="{{ $loginHref }}">
        <a href="{{ $loginHref }}" class="sab-nav-auth__signin" data-nav-sign-in>Login</a>
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
    min-height: 68px;
  }
  .sab-site-header__logo {
    color: var(--sab-text, #f8fafc);
    font-size: 1.25rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    text-decoration: none;
  }
  .sab-brand-accent {
    color: var(--sab-blue-light, #60a5fa);
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
  .sab-wiki-drawer-trigger {
    display: none;
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
    min-height: 44px;
    padding: 0 4px;
    border-bottom: 2px solid transparent;
    color: var(--sab-text-secondary, #9ca3af);
    font-size: .875rem;
    font-weight: 700;
    text-decoration: none;
  }
  .sab-main-nav__link:hover {
    color: var(--sab-text, #f8fafc);
  }
  .sab-main-nav__link.is-active {
    color: #fff;
    border-bottom-color: var(--sab-blue, #3b82f6);
  }
  @media (min-width: 768px) {
    .sab-wiki-drawer-trigger {
      display: inline-flex;
    }
    .sab-main-nav {
      display: flex;
      flex: 1;
      justify-content: center;
      gap: 1.25rem;
      min-width: 0;
    }
    .sab-header-end {
      margin-left: 0;
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
</script>
@endonce
