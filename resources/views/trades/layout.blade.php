<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
@include('seo.sab.partials._head')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="preload" href="/static/fonts/lato/lato-latin-400.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/static/css/sab-trades.css">
</head>
<body class="trades-app bg-slate-950 text-slate-100">
@include('seo.sab.partials._header')
@include('trades.partials.header-extras')
@include('seo.sab.partials._wiki-drawer')
@include('trades.partials.account-bar')
<main class="max-w-7xl mx-auto px-3 pt-4 pb-8 sm:px-4">
  @if(session('trade_status'))
    <p class="mb-4 rounded border border-white/10 bg-slate-900 px-4 py-3 text-sm text-slate-100">{{ session('trade_status') }}</p>
  @endif
  @if(session('trade_error'))
    <p class="mb-4 rounded border border-rose-800/50 bg-slate-900 px-4 py-3 text-sm text-rose-100">{{ session('trade_error') }}</p>
  @endif
  @yield('content')
</main>
@include('seo.sab.partials._footer')
<button class="sab-back-to-top" type="button" aria-label="Back to top" data-sab-back-to-top>
  <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
    <path d="M12 5.5 5.5 12l1.4 1.4 4.1-4.1V19h2V9.3l4.1 4.1 1.4-1.4L12 5.5Z" />
  </svg>
</button>
@yield('scripts')
<script src="/static/js/trades-card.js" defer></script>
<script>
  (function () {
    var button = document.querySelector('[data-sab-back-to-top]');
    if (!button) return;

    var ticking = false;
    var threshold = 360;

    function updateVisibility() {
      button.classList.toggle('is-visible', window.scrollY > threshold);
      ticking = false;
    }

    function requestVisibilityUpdate() {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(updateVisibility);
    }

    button.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    updateVisibility();
    window.addEventListener('scroll', requestVisibilityUpdate, { passive: true });
  })();
</script>
</body>
</html>
