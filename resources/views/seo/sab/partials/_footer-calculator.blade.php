@php
  $homeHref = ($urlPrefix ?? '') === '' ? '/' : rtrim($urlPrefix ?? '', '/');
  $aboutHref = \App\Services\Seo\SabRenderService::calculatorStaticPageHref($urlPrefix ?? '', 'about-us');
  $privacyHref = \App\Services\Seo\SabRenderService::calculatorStaticPageHref($urlPrefix ?? '', 'privacy-policy');
  $termsHref = \App\Services\Seo\SabRenderService::calculatorStaticPageHref($urlPrefix ?? '', 'terms-of-service');
  $faqHref = $faqHref ?? \App\Services\Seo\SabRenderService::calculatorStaticPageHref($urlPrefix ?? '', 'faq');
  $footerWikiPrefix = rtrim((string) ($productUrlPrefix ?? ''), '/');
  $footerAdminAbuseHref = $footerWikiPrefix.'/'.\App\Services\Seo\SabWikiPageDefinitions::PAGE_WIKI_ADMIN_ABUSE;
  $footerRebirthsHref = $footerWikiPrefix.'/'.\App\Services\Seo\SabWikiPageDefinitions::PAGE_WIKI_REBIRTHS;
@endphp
<footer class="sab-calc-site-footer">
  <div class="sab-calc-site-footer-inner">
    <div>
      <p class="sab-calc-footer-brand">{!! $brand['site_name'] ?? 'SAB Calculator' !!}</p>
      <p class="sab-calc-footer-copy">Compare Steal a Brainrot trades before you accept.</p>
    </div>
    <div class="sab-calc-footer-links">
      <a href="{{ $homeHref }}">Calculator</a>
      <a href="{{ $faqHref }}">FAQ</a>
      <a href="{{ $aboutHref }}">{{ $t['footer_about'] ?? 'About Us' }}</a>
      <a href="{{ $privacyHref }}">{{ $t['footer_privacy'] ?? 'Privacy Policy' }}</a>
      <a href="{{ $termsHref }}">{{ $t['footer_terms'] ?? 'Terms of Service' }}</a>
      @if(empty($calculatorOnly))
      <a href="{{ $footerAdminAbuseHref }}">Admin Abuse</a>
      <a href="{{ $footerRebirthsHref }}">Rebirth List</a>
      @endif
    </div>
  </div>
  <p class="sab-calc-footer-disclaimer">
    SAB Calculator is an independent fan-made site and is not affiliated with Roblox or Steal a Brainrot.<br>
    Names, images, and game references belong to their owners and are used for identification only. See <a href="{{ $aboutHref }}">About</a> for asset sources.
  </p>
</footer>
