<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="preconnect" href="https://pagead2.googlesyndication.com" crossorigin />
<link rel="dns-prefetch" href="https://pagead2.googlesyndication.com" />
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-9D736VVHH3"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-9D736VVHH3');
</script>
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link rel="preload" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" as="style" />
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet" />
<link rel="preload" href="/static/css/sabexistcount.css" as="style" />
<link rel="preload" href="{{ $cssHref ?? \App\Services\Seo\SabRenderService::CSS_HREF_SAB_CALCULATOR }}" as="style" />
<link rel="stylesheet" href="/static/css/sabexistcount.css" />
<link rel="stylesheet" href="{{ $cssHref ?? \App\Services\Seo\SabRenderService::CSS_HREF_SAB_CALCULATOR }}" />

<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}" />
<meta name="robots" content="{{ $robots ?? 'index,follow,max-image-preview:large' }}" />
<meta name="theme-color" content="#0a0f1a" />
<link rel="canonical" href="{{ $canonical }}" />
<link rel="icon" href="{{ \App\Services\Seo\SabSiteContext::FAVICON_SAB_CALCULATOR }}" sizes="any" />
<link rel="apple-touch-icon" href="{{ \App\Services\Seo\SabSiteContext::APPLE_TOUCH_SAB_CALCULATOR }}" />

<meta property="og:type" content="website" />
<meta property="og:title" content="{{ $seoTitle }}" />
<meta property="og:description" content="{{ $seoDescription }}" />
<meta property="og:url" content="{{ $canonical }}" />
<meta property="og:site_name" content="{{ $brand['og_site_name'] ?? 'SAB Calculator' }}" />

<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $seoTitle }}" />
<meta name="twitter:description" content="{{ $seoDescription }}" />

<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4457622474147266"
        crossorigin="anonymous"></script>

@if(!empty($websiteJsonLd ?? ''))
{!! $websiteJsonLd !!}
@endif
@if(!empty($jsonLd ?? ''))
{!! $jsonLd !!}
@endif
