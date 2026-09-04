<footer class="border-t border-white/10 bg-slate-950">
  <div class="max-w-7xl mx-auto px-4 py-10 text-sm text-slate-400">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <p>© {{ date('Y') }} {{ $t['footer_copyright_rest'] }}</p>
      <div class="flex flex-wrap gap-4">
        @php
          $legalHrefPrefix = \Illuminate\Support\Str::startsWith($urlPrefix ?? '', '/seo/sab/preview') ? '/seo/sab/preview' : null;
          $sabFooterHomeHref = (($urlPrefix ?? '') === '') ? '/' : $urlPrefix;
          $englishPrefix = $productUrlPrefix
            ?? (str_starts_with((string) ($urlPrefix ?? ''), '/seo/sab/preview') ? '/seo/sab/preview' : '');
          $footerCodesHref = rtrim((string) ($urlPrefix ?? ''), '/') . '/' . \App\Services\Seo\SabRenderService::PAGE_CODES;
          $footerNewsHref = rtrim($englishPrefix, '/') . (
            str_starts_with((string) ($englishPrefix ?? ''), '/seo/sab/preview')
              ? '/news'
              : '/news/'
          );
          $footerWikiPrefix = rtrim((string) $englishPrefix, '/');
          $footerAdminAbuseHref = $footerWikiPrefix.'/'.\App\Services\Seo\SabWikiPageDefinitions::PAGE_WIKI_ADMIN_ABUSE;
          $footerRebirthsHref = $footerWikiPrefix.'/'.\App\Services\Seo\SabWikiPageDefinitions::PAGE_WIKI_REBIRTHS;
        @endphp
        <a href="{{ $sabFooterHomeHref }}" class="hover:text-cyan-300">{{ $t['footer_home'] }}</a>
        <a href="{{ $urlPrefix }}/{{ \App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR }}" class="hover:text-cyan-300">{{ $t['nav_calculator'] ?? ($t['footer_calculator'] ?? 'Calculator') }}</a>
        <a href="{{ $englishPrefix }}/{{ \App\Services\Seo\SabRenderService::PAGE_VALUE_LIST }}" class="hover:text-cyan-300" style="display:inline-flex;align-items:center;gap:.2rem">{{ $t['nav_value_list'] ?? 'SAB Values' }} <span aria-hidden="true">🔥</span></a>
        <a href="{{ rtrim((string) $englishPrefix, '/') }}/{{ \App\Services\Seo\SabRenderService::PAGE_WIKI }}" class="hover:text-cyan-300">Wiki</a>
        <a href="{{ $footerCodesHref }}" class="hover:text-cyan-300" style="display:inline-flex;align-items:flex-start;gap:.25rem">
          {{ $t['nav_codes'] ?? 'Codes' }}
          <span style="margin-top:.05rem;border-radius:9999px;background:#06b6d4;padding:.1rem .25rem;font-size:7px;line-height:1;font-weight:900;color:#042f2e;box-shadow:0 1px 4px rgba(8,47,73,.4)">NEW</span>
        </a>
        <a href="{{ $footerNewsHref }}" class="hover:text-cyan-300">{{ $t['nav_news'] ?? 'News' }}</a>
        <a href="{{ $urlPrefix }}/{{ \App\Services\Seo\SabRenderService::PAGE_EXIST_COUNTS_LIST }}" class="hover:text-cyan-300">{{ $t['nav_exist_counts_list'] ?? 'Exist Count List' }}</a>
        <a href="{{ $englishPrefix }}/{{ \App\Services\Seo\SabRenderService::PAGE_EXIST_COUNT_GALLERY }}" class="hover:text-cyan-300">{{ $t['nav_exist_count_gallery'] ?? 'Exist Count Gallery' }}</a>
        <a href="{{ rtrim((string) $englishPrefix, '/') }}/{{ \App\Services\Seo\SabRenderService::gamesIndexPublicPath() }}" class="hover:text-cyan-300">{{ $t['nav_games'] ?? 'Games' }}</a>
        <a href="{{ $legalHrefPrefix ? $legalHrefPrefix . '/about-us' : '/about-us' }}" class="hover:text-cyan-300">{{ $t['footer_about'] }}</a>
        <a href="{{ $legalHrefPrefix ? $legalHrefPrefix . '/privacy-policy' : '/privacy-policy' }}" class="hover:text-cyan-300">{{ $t['footer_privacy'] }}</a>
        <a href="{{ $legalHrefPrefix ? $legalHrefPrefix . '/terms-of-service' : '/terms-of-service' }}" class="hover:text-cyan-300">{{ $t['footer_terms'] }}</a>
        <a href="{{ $footerAdminAbuseHref }}" class="hover:text-cyan-300">Admin Abuse</a>
        <a href="{{ $footerRebirthsHref }}" class="hover:text-cyan-300">Rebirth List</a>
      </div>
    </div>
    <p class="mt-5 text-xs leading-relaxed">{{ $t['footer_disclaimer'] }}</p>
  </div>
</footer>
