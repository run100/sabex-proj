@php
  $wwwOrigin = rtrim(\App\Support\SabHost::origin('www'), '/');
  $onWwwHost = request()->getHost() === \App\Support\SabHost::host('www');
  $promoTradesHref = ($onWwwHost ? '' : $wwwOrigin).\App\Support\TradePaths::marketplace();
@endphp
<div class="sab-calc-promo" role="note" data-sab-calc-promo>
  <div class="sab-calc-promo-inner">
    <span>New: Trade ads are live — post what you have or browse open trades</span>
    <a href="{{ $promoTradesHref }}">
      Open Trades <span aria-hidden="true">→</span>
    </a>
  </div>
  <button type="button" class="sab-calc-promo-close" aria-label="Close" data-sab-calc-promo-close>
    <svg viewBox="0 0 20 20" width="16" height="16" aria-hidden="true">
      <path d="M6 6l8 8M14 6l-8 8" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
    </svg>
  </button>
</div>
<script>
(function(){
  var k='sab_trades_promo_closed',el=document.querySelector('[data-sab-calc-promo]');
  if(!el)return;
  if(sessionStorage.getItem(k)){el.remove();return;}
  el.querySelector('[data-sab-calc-promo-close]').addEventListener('click',function(){
    el.remove();sessionStorage.setItem(k,'1');
  });
})();
</script>
