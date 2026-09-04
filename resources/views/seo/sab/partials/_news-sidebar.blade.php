@php
  $pagePrefix = rtrim((string) ($urlPrefix ?? ''), '/');
  $pageHref = fn (string $slug): string => $pagePrefix . '/' . $slug;
  $newsHref = fn (string $slug): string => $pagePrefix . '/news/' . $slug;
  $currentSlug = (string) ($article->slug ?? '');
  $popularGuides = array_values(array_filter(
    \App\Services\Seo\SabRenderService::NEWS_POPULAR_GUIDES,
    fn (array $guide): bool => $guide['slug'] !== $currentSlug
  ));
@endphp
<aside class="sab-news-sidebar" aria-label="{{ $t['news_sidebar_aria'] ?? 'On this page, SAB tools and popular guides' }}">
  @if(!empty($toc))
  <section class="sab-news-side-panel sab-news-sidebar-toc" aria-labelledby="sab-news-toc-title">
    <div class="sab-news-side-head">
      <h2 class="sab-news-side-title" id="sab-news-toc-title">{{ $t['news_toc_title'] ?? 'On this page' }}</h2>
    </div>
    @include('seo.sab.partials._news-toc')
  </section>
  @endif

  <section class="sab-news-side-panel" aria-labelledby="sab-news-tools-title">
    <div class="sab-news-side-head">
      <h2 class="sab-news-side-title" id="sab-news-tools-title">{{ $t['news_tools_title'] ?? 'SAB Tools' }}</h2>
      <p class="sab-news-side-sub">{{ $t['news_tools_subtitle'] ?? 'Check values, trades and exist counts' }}</p>
    </div>
    <div class="sab-news-tool-list">
      <a href="{{ $pageHref(\App\Services\Seo\SabRenderService::PAGE_VALUE_LIST) }}" class="sab-news-tool-link">
        <span class="sab-news-tool-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l4-5h10l4 5-9 11L3 9z"></path><path d="M3 9h18"></path><path d="M8 4l4 16 4-16"></path></svg>
        </span>
        <span class="sab-news-tool-copy">
          <span class="sab-news-tool-name">{{ $t['news_tools_value_list'] ?? 'SAB Values' }}</span>
          <span class="sab-news-tool-desc">{{ $t['news_tools_value_list_desc'] ?? 'Current trading values & trends' }}</span>
        </span>
        <svg class="sab-news-tool-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
      </a>
      <a href="{{ $pageHref(\App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR) }}" class="sab-news-tool-link">
        <span class="sab-news-tool-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2.5" width="14" height="19" rx="2"></rect><path d="M8 6h8v3H8z"></path><path d="M8 13h1M12 13h1M16 13h1M8 17h1M12 17h1M16 17h1"></path></svg>
        </span>
        <span class="sab-news-tool-copy">
          <span class="sab-news-tool-name">{{ $t['news_tools_calculator'] ?? 'Trading Calculator' }}</span>
          <span class="sab-news-tool-desc">{{ $t['news_tools_calculator_desc'] ?? 'Compare offers and check W/F/L' }}</span>
        </span>
        <svg class="sab-news-tool-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
      </a>
      <a href="{{ $pageHref(\App\Services\Seo\SabRenderService::PAGE_EXIST_COUNTS_LIST) }}" class="sab-news-tool-link">
        <span class="sab-news-tool-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3"></circle><path d="M6.5 20v-2a5.5 5.5 0 0111 0v2"></path><circle cx="5" cy="10" r="2"></circle><circle cx="19" cy="10" r="2"></circle><path d="M2 19v-1a4 4 0 014-4"></path><path d="M22 19v-1a4 4 0 00-4-4"></path></svg>
        </span>
        <span class="sab-news-tool-copy">
          <span class="sab-news-tool-name">{{ $t['news_tools_exist_count'] ?? 'Exist Count List' }}</span>
          <span class="sab-news-tool-desc">{{ $t['news_tools_exist_count_desc'] ?? 'Check supply and rarity signals' }}</span>
        </span>
        <svg class="sab-news-tool-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
      </a>
      <a href="{{ $pageHref(\App\Services\Seo\SabRenderService::PAGE_CODES) }}" class="sab-news-tool-link">
        <span class="sab-news-tool-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="9" width="18" height="12" rx="2"></rect><path d="M12 9v12M3 13h18"></path><path d="M12 9H8.5A2.5 2.5 0 118.5 4C11 4 12 9 12 9z"></path><path d="M12 9h3.5a2.5 2.5 0 100-5C13 4 12 9 12 9z"></path></svg>
        </span>
        <span class="sab-news-tool-copy">
          <span class="sab-news-tool-name">{{ $t['news_tools_codes'] ?? 'Steal a Brainrot Codes' }}</span>
          <span class="sab-news-tool-desc">{{ $t['news_tools_codes_desc'] ?? 'Latest codes, rewards and updates' }}</span>
        </span>
        <svg class="sab-news-tool-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
      </a>
      <a href="{{ $pageHref(\App\Services\Seo\SabRenderService::PAGE_EXIST_COUNT_GALLERY) }}" class="sab-news-tool-link">
        <span class="sab-news-tool-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"></rect><circle cx="8.5" cy="9" r="1.5"></circle><path d="M4 17l5-5 4 4 2-2 5 4"></path></svg>
        </span>
        <span class="sab-news-tool-copy">
          <span class="sab-news-tool-name">{{ $t['news_tools_gallery'] ?? 'Exist Count Gallery' }}</span>
          <span class="sab-news-tool-desc">{{ $t['news_tools_gallery_desc'] ?? 'Browse Brainrots visually' }}</span>
        </span>
        <svg class="sab-news-tool-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
      </a>
    </div>
    <a href="{{ $pageHref(\App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR) }}" class="sab-news-tool-cta">
      {{ $t['news_tools_cta'] ?? 'Check Your Trade' }}
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
    </a>
  </section>

  @if($popularGuides !== [])
  <section class="sab-news-side-panel" aria-labelledby="sab-news-guides-title">
    <div class="sab-news-side-head">
      <h2 class="sab-news-side-title" id="sab-news-guides-title">{{ $t['news_guides_title'] ?? 'Popular Guides' }}</h2>
      <p class="sab-news-side-sub">{{ $t['news_guides_subtitle'] ?? 'Trading, values and rarity guides' }}</p>
    </div>
    <div class="sab-news-tool-list">
      @foreach($popularGuides as $guide)
      <a href="{{ $newsHref($guide['slug']) }}" class="sab-news-tool-link">
        <span class="sab-news-tool-copy">
          <span class="sab-news-tool-name">{{ $guide['title'] }}</span>
          <span class="sab-news-tool-desc">{{ $guide['desc'] }}</span>
        </span>
        <svg class="sab-news-tool-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
      </a>
      @endforeach
    </div>
    <a href="{{ $hrefNewsIndex }}" class="sab-news-guides-all">
      <span>{{ $t['news_guides_all'] ?? 'View All SAB Guides' }}</span>
      <span aria-hidden="true">→</span>
    </a>
  </section>
  @endif
</aside>
