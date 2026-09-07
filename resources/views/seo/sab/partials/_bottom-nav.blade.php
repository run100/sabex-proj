@php
  $urlPrefix = $urlPrefix ?? '';
  $englishPrefix = $productUrlPrefix
    ?? (str_starts_with((string) ($urlPrefix ?? ''), '/seo/sab/preview') ? '/seo/sab/preview' : '');
  $wwwOrigin = rtrim(\App\Support\SabHost::origin('www'), '/');
  $onWwwHost = request()->getHost() === \App\Support\SabHost::host('www');
  $tradeBase = $onWwwHost ? '' : $wwwOrigin;
  $path = '/'.trim(request()->path(), '/');
  $homeHref = $urlPrefix === '' ? '/' : $urlPrefix;
  $valuesHref = rtrim((string) $englishPrefix, '/').'/'.\App\Services\Seo\SabRenderService::PAGE_VALUE_LIST;
  $tradesHref = $tradeBase.\App\Support\TradePaths::marketplace();
  $guidesHref = rtrim((string) ($englishPrefix !== '' ? $englishPrefix : $urlPrefix), '/').'/wiki';
  $tradesActive = $path === \App\Support\TradePaths::marketplace()
    || str_starts_with($path, \App\Support\TradePaths::marketplace().'/');
  $valuesActive = str_contains($path, \App\Services\Seo\SabRenderService::PAGE_VALUE_LIST);
  $guidesActive = $path === '/wiki' || str_starts_with($path, '/wiki/');
  $homeActive = $path === '/' || $path === '';
@endphp
<nav class="sab-bottom-nav" aria-label="Primary">
  <a href="{{ $homeHref }}" class="sab-bottom-nav__item{{ $homeActive ? ' is-active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20H4z"/><path d="M9 20v-6h6v6"/></svg>
    Home
  </a>
  <a href="{{ $valuesHref }}" class="sab-bottom-nav__item{{ $valuesActive ? ' is-active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/></svg>
    Values
  </a>
  <a href="{{ $tradesHref }}" class="sab-bottom-nav__item{{ $tradesActive ? ' is-active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 8h11l-3-3M17 16H6l3 3"/></svg>
    Trades
  </a>
  <a href="{{ $guidesHref }}" class="sab-bottom-nav__item{{ $guidesActive ? ' is-active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>
    Guides
  </a>
  <button type="button" class="sab-bottom-nav__item" data-wiki-drawer-open aria-controls="sab-wiki-drawer" aria-label="Open menu">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="6" r="1.4"/><circle cx="12" cy="12" r="1.4"/><circle cx="12" cy="18" r="1.4"/></svg>
    More
  </button>
</nav>
