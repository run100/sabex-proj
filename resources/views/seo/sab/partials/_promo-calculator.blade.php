@php
  $promoCalculatorHref = rtrim(\App\Services\Seo\SabSiteContext::EXTERNAL_EXIST_COUNT_URL, '/')
      . '/' . \App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR;
@endphp
<div class="sab-calc-promo" role="note" data-sab-calc-promo>
  <div class="sab-calc-promo-inner">
    <span>New: Full Calculator with exist counts, value history and trade signals —</span>
    <a href="{{ $promoCalculatorHref }}" target="_blank" rel="noopener noreferrer">
      Try SABExistCount Calculator <span aria-hidden="true">→</span>
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
  var k='sab_calc_promo_closed',el=document.querySelector('[data-sab-calc-promo]');
  if(!el)return;
  if(sessionStorage.getItem(k)){el.remove();return;}
  el.querySelector('[data-sab-calc-promo-close]').addEventListener('click',function(){
    el.remove();sessionStorage.setItem(k,'1');
  });
})();
</script>
