<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<link rel="preconnect" href="https://www.googletagmanager.com" crossorigin />
<link rel="dns-prefetch" href="https://www.googletagmanager.com" />
<link rel="preconnect" href="https://www.clarity.ms" crossorigin />
<link rel="dns-prefetch" href="https://www.clarity.ms" />
<link rel="preconnect" href="https://pagead2.googlesyndication.com" crossorigin />
<link rel="dns-prefetch" href="https://pagead2.googlesyndication.com" />
<link rel="preconnect" href="https://pagead2.googlesyndication.com" crossorigin />
<link rel="dns-prefetch" href="https://pagead2.googlesyndication.com" />
<link rel="preload" href="{{ $cssHref ?? \App\Services\Seo\SabRenderService::CSS_HREF_LARAVEL }}" as="style" />

<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}" />
@php $keywords = $seoKeywords ?? ($t['meta_keywords'] ?? ''); @endphp
@if($keywords !== '')
<meta name="keywords" content="{{ $keywords }}" />
@endif
<meta name="robots" content="{{ $robots ?? 'index,follow,max-image-preview:large' }}" />
<meta name="theme-color" content="#020617" />
<link rel="canonical" href="{{ $canonical }}" />
@foreach($hreflangLinks as $lang => $href)
<link rel="alternate" hreflang="{{ $lang }}" href="{{ $href }}" />
@endforeach
<link rel="icon" href="/uploads/images/sab/favicon.ico" sizes="any" />
<link rel="icon" type="image/png" href="/uploads/images/sab/favicon-512.png" sizes="512x512" />
<link rel="apple-touch-icon" href="/uploads/images/sab/apple-touch-icon.png" />

<meta property="og:type" content="website" />
<meta property="og:title" content="{{ $seoTitle }}" />
<meta property="og:description" content="{{ $seoDescription }}" />
<meta property="og:url" content="{{ $canonical }}" />
<meta property="og:site_name" content="SAB Exist Count" />
<meta property="og:image" content="{{ $ogImage ?? 'https://sabexistcount.com/uploads/images/sab/og-image.png' }}" />
<meta property="og:image:secure_url" content="{{ $ogImage ?? 'https://sabexistcount.com/uploads/images/sab/og-image.png' }}" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta property="og:image:alt" content="{{ $ogImageAlt ?? 'SAB Exist Count tracker showing known Steal a Brainrot counts and rarity signals.' }}" />

<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $seoTitle }}" />
<meta name="twitter:description" content="{{ $seoDescription }}" />
<meta name="twitter:image" content="{{ $ogImage ?? 'https://sabexistcount.com/uploads/images/sab/twitter-image.png' }}" />
<meta name="twitter:image:alt" content="{{ $ogImageAlt ?? 'SAB Exist Count tracker showing known Steal a Brainrot counts and rarity signals.' }}" />

<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-XL8FPK8RZK');
  (function(){
    function loadGtag(){
      var s = document.createElement('script');
      s.src = 'https://www.googletagmanager.com/gtag/js?id=G-XL8FPK8RZK';
      document.head.appendChild(s);
    }
    if ('requestIdleCallback' in window) {
      requestIdleCallback(loadGtag);
    } else {
      setTimeout(loadGtag, 2000);
    }
  })();
</script>

{{-- Microsoft Clarity --}}
<script type="text/javascript">
    (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "wopc01npsc");
</script>

<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4457622474147266"
        crossorigin="anonymous"></script>

<link rel="stylesheet" href="/static/css/sab-tokens.css" />
<link rel="stylesheet" href="{{ $cssHref ?? \App\Services\Seo\SabRenderService::CSS_HREF_LARAVEL }}" />

{!! $websiteJsonLd ?? '' !!}
@isset($jsonLd)
{!! $jsonLd !!}
@endisset
