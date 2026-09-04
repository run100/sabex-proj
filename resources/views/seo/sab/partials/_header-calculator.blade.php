@php
  $logoHtml = $brand['logo_html'] ?? 'SAB<span class="sab-brand-accent">Calculator</span>.com';
  $homeHref = ($urlPrefix ?? '') === '' ? '/' : rtrim($urlPrefix ?? '', '/');
  $faqHref = $faqHref ?? \App\Services\Seo\SabRenderService::calculatorStaticPageHref($urlPrefix ?? '', 'faq');
@endphp
<header class="sab-calc-site-header">
  <div class="sab-calc-site-nav">
    <a href="{{ $homeHref }}" class="sab-calc-site-logo">{!! $logoHtml !!}</a>
    <nav class="sab-calc-site-links" aria-label="Calculator navigation">
      <a href="{{ $homeHref }}">Calculator</a>
      <a href="{{ rtrim($urlPrefix ?? '', '/') }}/{{ \App\Services\Seo\SabRenderService::PAGE_BRAINROTS_LIST }}">Brainrots</a>
      <a href="{{ \App\Services\Seo\SabSiteContext::EXTERNAL_EXIST_COUNT_URL }}" target="_blank" rel="noopener noreferrer">Exist Count</a>
      <a href="{{ \App\Services\Seo\SabSiteContext::EXTERNAL_MM2_CALCULATOR_URL }}" target="_blank" rel="noopener noreferrer">MM2 Calculator</a>
      <a href="{{ $faqHref }}">FAQ</a>
    </nav>
  </div>
</header>
