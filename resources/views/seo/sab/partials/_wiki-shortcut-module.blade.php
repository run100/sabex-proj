@php
  $isCalculatorModule = ($variant ?? 'home') === 'calculator';
@endphp
@once
<style>
  .sab-wiki-shortcut-module--home {
    margin-top: 1rem;
    border: 1px solid rgba(255, 255, 255, .1);
    background: #0f172a;
    border-radius: .75rem;
    padding: 1rem;
  }
  .sab-wiki-shortcut-module--calculator {
    margin: 0 0 1.5rem;
  }
  .sab-wiki-shortcut-module__title {
    margin: 0 0 .4rem;
    color: #f1f5f9;
    font-size: 1.125rem;
    font-weight: 900;
    line-height: 1.3;
  }
  .sab-wiki-shortcut-module__lead {
    margin: 0;
    color: #94a3b8;
    font-size: .875rem;
    line-height: 1.6;
  }
  .sab-wiki-shortcut-module__pills {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    margin-top: .75rem;
  }
  .sab-wiki-shortcut-module__pill {
    display: inline-flex;
    align-items: center;
    border: 1px solid rgba(34, 211, 238, .35);
    border-radius: 9999px;
    padding: .35rem .65rem;
    background: rgba(8, 145, 178, .1);
    color: #67e8f9;
    font-size: .8125rem;
    font-weight: 800;
    line-height: 1.25;
    text-decoration: none;
  }
  .sab-wiki-shortcut-module__pill:hover {
    color: #a5f3fc;
    border-color: rgba(165, 243, 252, .55);
  }
</style>
@endonce
<section class="sab-wiki-shortcut-module {{ $isCalculatorModule ? 'sab-calc-seo-panel sab-wiki-shortcut-module--calculator' : 'sab-wiki-shortcut-module--home' }}" aria-labelledby="sab-wiki-shortcut-title">
  <h2 id="sab-wiki-shortcut-title" class="{{ $isCalculatorModule ? 'sab-calc-seo-title' : 'sab-wiki-shortcut-module__title' }}">SAB Wiki</h2>
  <p class="{{ $isCalculatorModule ? 'sab-calc-seo-text' : 'sab-wiki-shortcut-module__lead' }}">{{ $lead }}</p>
  <div class="sab-wiki-shortcut-module__pills">
    <a class="{{ $isCalculatorModule ? 'sab-calc-link-pill' : 'sab-wiki-shortcut-module__pill' }}" href="{{ $wikiHubHref }}">Wiki</a>
    <a class="{{ $isCalculatorModule ? 'sab-calc-link-pill' : 'sab-wiki-shortcut-module__pill' }}" href="{{ $adminAbuseHref }}">Admin Abuse</a>
    <a class="{{ $isCalculatorModule ? 'sab-calc-link-pill' : 'sab-wiki-shortcut-module__pill' }}" href="{{ $rebirthsHref }}">Rebirth List</a>
  </div>
</section>
