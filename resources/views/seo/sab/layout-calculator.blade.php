<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
@include('seo.sab.partials._head-calculator')
</head>
<body class="sab-site-calculator">
<div class="sab-calc-bg-glow" aria-hidden="true">
  <span class="sab-calc-glow sab-calc-glow-a"></span>
  <span class="sab-calc-glow sab-calc-glow-b"></span>
  <span class="sab-calc-glow sab-calc-glow-c"></span>
</div>
@include('seo.sab.partials._header')
@include('seo.sab.partials._wiki-drawer')
@include('seo.sab.partials._promo-calculator')
<main class="sab-calc-shell">
  @yield('content')
</main>
@include('seo.sab.partials._bottom-nav')
@include('seo.sab.partials._footer-calculator')
@yield('scripts')
<script src="/static/js/sab-nav-auth.js" defer></script>
</body>
</html>
