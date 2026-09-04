<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
@include('seo.sab.partials._head')
</head>
<body class="bg-slate-950 text-slate-100">
@include('seo.sab.partials._header')
@include('seo.sab.partials._wiki-drawer')
<main class="max-w-7xl mx-auto px-3 pt-4 pb-8 sm:px-4">
  @yield('content')
</main>
@include('seo.sab.partials._footer')
<button class="sab-back-to-top" type="button" aria-label="Back to top" data-sab-back-to-top>
  <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
    <path d="M12 5.5 5.5 12l1.4 1.4 4.1-4.1V19h2V9.3l4.1 4.1 1.4-1.4L12 5.5Z" />
  </svg>
</button>
@yield('scripts')
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
