@php
  $wwwOrigin = rtrim(\App\Support\SabHost::origin('www'), '/');
  $onWwwHost = request()->getHost() === \App\Support\SabHost::host('www');
  $tradesLaunchHref = ($onWwwHost ? '' : $wwwOrigin).\App\Support\TradePaths::marketplace();
@endphp
<div class="{{ $noticeClass ?? 'mt-3' }} inline-flex max-w-full items-center gap-2 text-left text-xs font-semibold leading-5 text-cyan-200" data-sab-trades-launch-notice>
  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4 shrink-0 text-cyan-300" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M4 14h3l7 4V6L7 10H4v4Z"/>
    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9a3 3 0 0 1 0 6M20 7a6 6 0 0 1 0 10"/>
  </svg>
  <a href="{{ $tradesLaunchHref }}" class="font-semibold underline hover:text-cyan-100" data-sab-trades-launch-notice-link>New · Trade ads are live — browse open Steal a Brainrot trades</a>
</div>
<script>
  (function () {
    var link = document.querySelector('[data-sab-trades-launch-notice-link]');
    if (link) {
      link.addEventListener('click', function () {
        if (typeof gtag === 'function') {
          gtag('event', 'trade_notice_click', { destination: 'trading' });
        }
      });
    }
  })();
</script>
