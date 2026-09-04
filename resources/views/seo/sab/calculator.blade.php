@extends(($calculatorOnly ?? false) ? 'seo.sab.layout-calculator' : 'seo.sab.layout')

@section('content')
@php
  $calcCopy = $calculatorCopy ?? [];
  $calcH1 = $calcCopy['h1'] ?? 'SAB Trade Calculator for Steal a Brainrot';
  $calcIntro = $calcCopy['intro'] ?? 'Compare values before you trade';
  $isRotTheme = (bool) ($calculatorOnly ?? false);
  $calcUi = $calculatorUi ?? [];
@endphp
<style>
  .sab-calc-card { border: 1px solid rgba(255,255,255,.12); background: #111827; border-radius: 1rem; }
  .sab-calc-soft { background: rgba(15,23,42,.72); }
  .sab-calc-icon { width: 3rem; height: 3rem; object-fit: contain; flex-shrink: 0; }
  .sab-calc-trade-grid { display: grid; grid-template-columns: minmax(0, 1fr); }
  .sab-calc-trade-side { min-width: 0; }
  .sab-calc-side-title { display: flex; align-items: center; gap: .5rem; }
  .sab-calc-side-badge { display: inline-flex; align-items: center; justify-content: center; width: 1.6rem; height: 1.6rem; border-radius: 9999px; flex-shrink: 0; }
  .sab-calc-side-badge svg { width: .9rem; height: .9rem; }
  [data-side="offer"] .sab-calc-side-badge { background: rgba(8,145,178,.18); color: #67e8f9; border: 1px solid rgba(34,211,238,.35); }
  [data-side="receive"] .sab-calc-side-badge { background: rgba(22,163,74,.2); color: #4ade80; border: 1px solid rgba(34,197,94,.4); }
  [data-side="receive"] { background: rgba(16,185,129,.05); }
  .sab-calc-compare-panel { border-top: 1px solid rgba(255,255,255,.15); border-bottom: 1px solid rgba(255,255,255,.15); }
  .sab-calc-item-card { border: 1px solid rgba(148,163,184,.12); border-radius: .75rem; background: rgba(15,23,42,.72); padding: .75rem; }
  .sab-calc-slot-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
  .sab-calc-slot-grid.has-items { margin-top: .75rem; }
  .sab-calc-add-slot { display: flex; align-items: center; justify-content: center; width: min(4.25rem, 100%); aspect-ratio: 1; margin: 0 auto; border: 1px dashed rgba(148,163,184,.55); border-radius: 9999px; color: #94a3b8; background: rgba(15,23,42,.42); font-size: 1.75rem; line-height: 1; transition: border-color .15s, color .15s, background .15s, transform .15s; }
  .sab-calc-add-slot:hover { border-color: rgba(34,211,238,.9); color: #67e8f9; background: rgba(8,145,178,.12); transform: translateY(-1px); }
  .sab-calc-swap-btn { display: inline-flex; align-items: center; justify-content: center; width: 2.75rem; height: 2.75rem; margin: 0 auto .25rem; border-radius: 9999px; color: #10b981; }
  .sab-calc-swap-btn:hover { background: rgba(16,185,129,.1); }
  .sab-calc-swap-icon { width: 1.75rem; height: 1.75rem; }
  .sab-calc-compare-status { border-radius: .875rem; border: 1px solid rgba(148,163,184,.18); background: rgba(15,23,42,.45); padding: .75rem; }
  .sab-calc-deal-pill { display: inline-flex; align-items: center; justify-content: center; min-width: 8.5rem; border-radius: 9999px; padding: .55rem 1.25rem; font-size: 1.05rem; font-weight: 900; letter-spacing: .02em; }
  .sab-calc-deal-good { border: 1.5px solid #22c55e; background: rgba(22,163,74,.25); color: #4ade80; box-shadow: 0 0 12px rgba(34,197,94,.35); }
  .sab-calc-deal-fair { border: 1.5px solid rgba(148,163,184,.45); background: rgba(100,116,139,.18); color: #cbd5e1; }
  .sab-calc-deal-bad { border: 1.5px solid #f43f5e; background: rgba(225,29,72,.18); color: #fb7185; box-shadow: 0 0 12px rgba(244,63,94,.28); }
  body.sab-calc-modal-open .sab-calc-shell { z-index: 120; }
  .sab-calc-modal { position: fixed; inset: 0; z-index: 80; display: none; align-items: flex-start; justify-content: center; overflow: auto; background: rgba(2,6,23,.78); padding: .75rem; }
  .sab-calc-modal.is-open { display: flex; }
  .sab-calc-dialog { width: min(760px, 100%); max-height: calc(100vh - 1.5rem); overflow: hidden; display: flex; flex-direction: column; border: 1px solid rgba(255,255,255,.72); border-radius: 1rem; background: #050914; box-shadow: 0 24px 80px rgba(0,0,0,.5); }
  .sab-calc-dialog.is-picker { width: min(820px, 100%); }
  .sab-calc-dialog.is-config { position: relative; width: min(680px, 100%); height: auto; max-height: calc(100dvh - 1.5rem); }
  .sab-calc-dialog.is-config .sab-calc-modal-header { position: absolute; top: 1rem; right: 1rem; z-index: 10; border-bottom: 0 !important; padding: 0 !important; }
  .sab-calc-dialog.is-config [data-close] { display: inline-flex; width: 36px; height: 36px; align-items: center; justify-content: center; border: 1px solid rgba(148,163,184,.18); border-radius: 9999px; background: rgba(5,9,20,.9); color: #cbd5e1; font-size: 1.35rem; line-height: 1; box-shadow: 0 8px 24px rgba(0,0,0,.28); }
  .sab-calc-dialog.is-config [data-close]:hover,
  .sab-calc-dialog.is-config [data-close]:focus-visible { border-color: rgba(226,232,240,.48); background: rgba(15,23,42,.96); color: #fff; outline: none; }
  .sab-calc-dialog.is-config .sab-calc-modal-body { overflow-y: auto; }
  .sab-calc-dialog.is-config .sab-calc-selected-panel { margin-right: 3.1rem; }
  .sab-calc-modal-body { flex: 1; min-height: 0; overflow-y: auto; padding: 1rem; }
  .sab-calc-picker-panel { display: flex; min-height: 0; flex-direction: column; gap: .75rem; }
  .sab-calc-picker-panel.hidden { display: none; }
  .sab-calc-search-wrap { position: relative; }
  .sab-calc-search-icon { pointer-events: none; position: absolute; left: .75rem; top: 50%; width: 1rem; height: 1rem; transform: translateY(-50%); color: #94a3b8; }
  .sab-calc-search-input { width: 100%; box-sizing: border-box; border: 1px solid rgba(71,85,105,.75); border-radius: 9999px; background: #0f172a; padding: .55rem .9rem .55rem 2.3rem; color: #fff; font-size: .92rem; outline: none; }
  .sab-calc-search-input:focus { border-color: rgba(34,211,238,.65); box-shadow: 0 0 0 3px rgba(34,211,238,.12); }
  .sab-calc-rarity-filters { display: flex; gap: .5rem; overflow-x: auto; padding-bottom: .125rem; scrollbar-width: thin; scrollbar-color: rgba(148,163,184,.35) transparent; }
  .sab-calc-rarity-chip { flex: 0 0 auto; border: 1px solid rgba(148,163,184,.28); border-radius: 9999px; background: rgba(15,23,42,.7); padding: .38rem .85rem; color: #cbd5e1; font-size: .78rem; font-weight: 800; }
  .sab-calc-rarity-chip.is-selected { border-color: rgba(34,211,238,.8); background: rgba(8,145,178,.18); color: #67e8f9; }
  .sab-calc-picker-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(82px, 1fr)); gap: .75rem; max-height: min(26rem, calc(100dvh - 14rem)); overflow-y: auto; padding: .1rem .1rem .25rem; scrollbar-color: rgb(34,197,94) transparent; }
  .sab-calc-brainrot-btn { display: flex; min-width: 0; flex-direction: column; align-items: center; gap: .35rem; border: 1px solid rgba(148,163,184,.16); border-radius: .875rem; background: rgba(15,23,42,.72); padding: .55rem .4rem .6rem; text-align: center; cursor: pointer; transition: border-color .15s, background .15s, transform .15s; }
  .sab-calc-brainrot-btn { position: relative; }
  .sab-calc-brainrot-btn:hover { border-color: rgba(34,211,238,.55); background: rgba(15,23,42,.95); transform: translateY(-1px); }
  .sab-calc-brainrot-btn.is-selected { border-color: rgb(34,197,94); background: rgba(6,78,59,.3); }
  .sab-calc-new-badge { position: absolute; right: .45rem; top: .45rem; border: 1px solid rgba(34,197,94,.75); border-radius: 9999px; background: rgba(6,78,59,.88); padding: .12rem .34rem; color: #86efac; font-size: .54rem; font-weight: 900; line-height: 1; letter-spacing: .04em; }
  .sab-calc-brainrot-img-wrap { display: flex; align-items: center; justify-content: center; width: 3.4rem; height: 3.4rem; border: 1px solid rgba(255,255,255,.12); border-radius: 9999px; background: rgba(255,255,255,.05); overflow: hidden; }
  .sab-calc-brainrot-img { width: 2.75rem; height: 2.75rem; object-fit: contain; display: block; }
  .sab-calc-brainrot-name { display: -webkit-box; min-height: 2.05rem; overflow: hidden; -webkit-box-orient: vertical; -webkit-line-clamp: 2; color: #fff; font-size: .78rem; font-weight: 900; line-height: 1.28; overflow-wrap: anywhere; }
  .sab-calc-brainrot-rarity { color: #64748b; font-size: .66rem; font-weight: 700; line-height: 1.1; }
  .sab-calc-empty-grid { grid-column: 1 / -1; border: 1px dashed rgba(148,163,184,.28); border-radius: .875rem; padding: 1rem; text-align: center; color: #94a3b8; font-size: .85rem; }
  .sab-calc-selected-panel { border: 1px solid rgba(34,197,94,.45); border-radius: .875rem; background: rgba(6,78,59,.12); padding: .75rem; }
  .sab-calc-selected-img { width: 3.4rem; height: 3.4rem; border-radius: 9999px; border: 1px solid rgba(255,255,255,.14); background: rgba(255,255,255,.05); object-fit: contain; flex-shrink: 0; }
  .sab-calc-config-tabs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .4rem; margin-bottom: .75rem; border: 1px solid rgba(148,163,184,.18); border-radius: 9999px; background: rgba(15,23,42,.55); padding: .25rem; }
  .sab-calc-config-tab { border: 0; border-radius: 9999px; background: transparent; padding: .5rem .75rem; color: #94a3b8; font-size: .82rem; font-weight: 900; cursor: pointer; }
  .sab-calc-config-tab.is-active { background: rgba(8,145,178,.22); color: #67e8f9; box-shadow: inset 0 0 0 1px rgba(34,211,238,.38); }
  .sab-calc-config-panel { display: none; }
  .sab-calc-config-panel.is-active { display: block; }
  .sab-calc-mutation-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .45rem; }
  .sab-calc-chip { border: 1px solid transparent; background: rgba(15,23,42,.72); border-radius: .5rem; }
  .sab-calc-chip.is-selected { border-color: rgb(34,197,94); background: rgba(22,101,52,.24); color: #fff; }
  .sab-calc-mutation-chip { position: relative; }
  .sab-calc-mutation-badge { position: absolute; top: -5px; right: -5px; width: 14px; height: 14px; border-radius: 50%; background: #16a34a; display: none; align-items: center; justify-content: center; font-size: .45rem; font-weight: 700; color: #fff; }
  .sab-calc-chip.is-selected .sab-calc-mutation-badge { display: flex; }
  .sab-calc-mutation-btn { display: flex; align-items: center; gap: .375rem; padding: .3rem .5rem; }
  .sab-calc-mutation-img { width: 1.125rem; height: 1.125rem; object-fit: contain; flex-shrink: 0; }
  .sab-calc-trait-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .35rem; max-height: min(22rem, calc(100dvh - 25rem)); overflow-y: auto; padding-right: .1rem; scrollbar-color: rgb(34,197,94) transparent; }
  .sab-calc-trait-search { min-height: 2.7rem; padding: .7rem .85rem; font-size: .9rem; }
  .sab-calc-trait { display: grid; grid-template-columns: 22px 1fr 18px; align-items: center; gap: .25rem .35rem; min-width: 0; padding: .42rem .45rem; }
  .sab-calc-trait-img { width: 1.25rem; height: 1.25rem; object-fit: contain; flex-shrink: 0; }
  .sab-calc-income-panel { flex-shrink: 0; border-top: 1px solid rgba(255,255,255,.08); padding: .6rem .75rem; }
  .sab-calc-income-card { border-radius: .75rem; border: 1px solid rgba(22,163,74,.5); background: rgba(6,78,59,.12); padding: .7rem .8rem; }
  .sab-calc-income-details { margin-top: .45rem; }
  .sab-calc-income-details summary { cursor: pointer; list-style: none; color: #94a3b8; font-size: .72rem; font-weight: 800; }
  .sab-calc-income-details summary::-webkit-details-marker { display: none; }
  .sab-calc-income-breakdown { margin-top: .45rem; }
  .sab-calc-scroll { max-height: 18rem; overflow: auto; scrollbar-color: rgb(34,197,94) transparent; }
  .sab-calc-value { color: #facc15; }
  .sab-calc-income { color: #00f078; }
  .sab-calc-danger { color: #fb7185; }
  .sab-calc-muted { color: #64748b; }
  .sab-calc-help-panel { margin-top: 1rem; overflow: hidden; border: 1px solid rgba(148,163,184,.16); background: rgba(15,23,42,.44); border-radius: .875rem; }
  .sab-calc-help-toggle { display: flex; width: 100%; align-items: center; justify-content: space-between; gap: 1rem; padding: .85rem 1rem; text-align: left; }
  .sab-calc-help-chevron { flex-shrink: 0; color: #94a3b8; font-size: 1rem; line-height: 1; }
  .sab-calc-help-card { border: 1px solid rgba(148,163,184,.12); background: rgba(2,6,23,.42); border-radius: .75rem; padding: 1rem; }
  .sab-calc-bonus-row { display: flex; align-items: flex-start; gap: .75rem; padding: .625rem 0; border-bottom: 1px solid rgba(255,255,255,.06); }
  .sab-calc-bonus-row:last-child { border-bottom: none; padding-bottom: 0; }
  .sab-calc-bonus-pct { flex-shrink: 0; min-width: 3.25rem; border-radius: .5rem; padding: .25rem .5rem; font-size: .75rem; font-weight: 800; text-align: center; }
  .sab-calc-bonus-tags { display: flex; flex-wrap: wrap; gap: .375rem; min-width: 0; }
  .sab-calc-bonus-tag { display: inline-flex; align-items: center; gap: .3rem; border-radius: 9999px; border: 1px solid rgba(148,163,184,.35); background: rgba(15,23,42,.85); padding: .2rem .55rem .2rem .35rem; font-size: .7rem; font-weight: 600; color: #e2e8f0; white-space: nowrap; }
  .sab-calc-bonus-tag img { width: 1rem; height: 1rem; object-fit: contain; flex-shrink: 0; }
  .sab-calc-content { margin: 1rem 0 2rem; }
  .sab-calc-copy { color: #cbd5e1; line-height: 1.6; }
  .sab-calc-copy a { color: #67e8f9; font-weight: 700; }
  .sab-calc-copy a:hover { color: #a5f3fc; }
  .sab-calc-seo-grid { display: grid; gap: 1rem; }
  .sab-calc-seo-panel { border: 1px solid rgba(148,163,184,.14); background: rgba(15,23,42,.58); border-radius: .875rem; padding: 1rem; box-shadow: inset 0 1px 0 rgba(255,255,255,.03); }
  .sab-calc-link-pill { display: inline-flex; align-items: center; border: 1px solid rgba(34,211,238,.35); border-radius: 9999px; padding: .35rem .65rem; background: rgba(8,145,178,.1); color: #67e8f9; font-size: .8125rem; font-weight: 800; line-height: 1.25; }
  .sab-calc-seo-title { margin-bottom: .55rem; font-size: .96rem; line-height: 1.35; font-weight: 900; color: #fff; }
  .sab-calc-seo-text { font-size: .84rem; line-height: 1.65; color: #cbd5e1; }
  .sab-calc-step-list { counter-reset: sab-step; list-style: none; margin: 0; padding: 0; display: grid; gap: .45rem; }
  .sab-calc-step-list li { counter-increment: sab-step; display: grid; grid-template-columns: 1.45rem minmax(0, 1fr); gap: .55rem; align-items: start; }
  .sab-calc-step-list li::before { content: counter(sab-step); display: inline-flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; border-radius: 9999px; background: rgba(34,211,238,.12); color: #67e8f9; font-size: .68rem; font-weight: 900; line-height: 1; }
  .sab-calc-tip-list { list-style: none; margin: 0; padding: 0; display: grid; gap: .45rem; }
  .sab-calc-tip-list li { display: grid; grid-template-columns: 1.35rem minmax(0, 1fr); gap: .55rem; align-items: start; }
  .sab-calc-tip-list li::before { content: "✓"; display: inline-flex; align-items: center; justify-content: center; width: 1.1rem; height: 1.1rem; margin-top: .1rem; border-radius: 9999px; background: rgba(34,197,94,.12); color: #4ade80; font-size: .68rem; font-weight: 900; line-height: 1; }
  .sab-calc-faq-list { display: grid; gap: .85rem; }
  .sab-calc-faq-item { border-top: 1px solid rgba(148,163,184,.1); padding-top: .85rem; }
  .sab-calc-faq-item:first-child { border-top: 0; padding-top: 0; }
  .sab-calc-intro-title { font-size: .875rem; line-height: 1.35; font-weight: 800; color: #e2e8f0; }
  .sab-calc-intro-copy { max-width: 56rem; font-size: .8125rem; line-height: 1.55; color: #94a3b8; }
  @media (min-width: 1024px) {
    .sab-calc-trade-grid { grid-template-columns: minmax(0, 1fr) 220px minmax(0, 1fr); }
    .sab-calc-compare-panel { border-top: 0; border-bottom: 0; border-left: 1px solid rgba(255,255,255,.15); border-right: 1px solid rgba(255,255,255,.15); }
  }
  @media (max-width: 760px) {
    .sab-calc-icon { width: 2.5rem; height: 2.5rem; }
    .sab-calc-slot-grid { gap: .55rem; }
    .sab-calc-add-slot { width: min(3.5rem, 100%); font-size: 1.45rem; }
    .sab-calc-modal { padding: .5rem .5rem calc(5.75rem + env(safe-area-inset-bottom)); align-items: flex-start; }
    .sab-calc-dialog { max-height: calc(100dvh - 1rem); border-radius: 1rem; width: 100%; }
    .sab-calc-dialog.is-config { height: calc(100dvh - 6.25rem - env(safe-area-inset-bottom)); max-height: calc(100dvh - 6.25rem - env(safe-area-inset-bottom)); }
    .sab-calc-modal-body { padding: .75rem; }
    .sab-calc-picker-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .55rem; max-height: calc(100dvh - 13.5rem); }
    .sab-calc-brainrot-btn { padding: .45rem .25rem .5rem; border-radius: .75rem; }
    .sab-calc-brainrot-img-wrap { width: 3rem; height: 3rem; }
    .sab-calc-brainrot-img { width: 2.45rem; height: 2.45rem; }
    .sab-calc-brainrot-name { font-size: .68rem; min-height: 1.78rem; }
    .sab-calc-brainrot-rarity { font-size: .58rem; }
    .sab-calc-selected-panel { padding: .6rem; }
    .sab-calc-selected-img { width: 2.9rem; height: 2.9rem; }
    .sab-calc-config-tabs { margin-bottom: .6rem; }
    .sab-calc-mutation-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .sab-calc-trait-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); max-height: clamp(9rem, calc(100dvh - 34rem), 16rem); }
    .sab-calc-trait { padding: .5rem .45rem; }
    .sab-calc-income-panel { padding: .5rem .75rem; }
    .sab-calc-income-card { padding: .6rem .7rem; }
  }
  .sab-calc-today {
    margin: 5px 0 1.5rem;
    border: 1px solid rgba(148, 163, 184, .14);
    border-radius: .875rem;
    background: rgba(15, 23, 42, .58);
    padding: 1rem;
  }
  .sab-calc-today__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
  }
  .sab-calc-today__title {
    margin: 0;
    color: #cbd5e1;
    font-size: .6875rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
  }
  .sab-calc-today__more {
    color: #67e8f9;
    font-size: .75rem;
    font-weight: 800;
    text-decoration: none;
  }
  .sab-calc-today__more:hover { color: #a5f3fc; text-decoration: underline; }
  .sab-calc-today__grid {
    display: grid;
    gap: 1rem;
    margin-top: .75rem;
  }
  @media (min-width: 768px) {
    .sab-calc-today__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; }
  }
  .sab-calc-today__col-title {
    margin: 0 0 .5rem;
    color: #e2e8f0;
    font-size: .8125rem;
    font-weight: 800;
  }
  .sab-calc-today__list {
    display: grid;
    gap: .4rem;
    margin: 0;
    padding: 0;
    list-style: none;
  }
  .sab-calc-today__row {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: .3rem .5rem;
    color: #94a3b8;
    font-size: .8125rem;
  }
  .sab-calc-today__rank {
    min-width: 1.1rem;
    color: #64748b;
    font-weight: 700;
  }
  .sab-calc-today__name {
    color: #e2e8f0;
    font-weight: 700;
    text-decoration: none;
  }
  a.sab-calc-today__name:hover { color: #67e8f9; }
  .sab-calc-today__pct.is-up { color: #4ade80; font-weight: 800; }
  .sab-calc-today__pct.is-down { color: #fb7185; font-weight: 800; }
  .sab-calc-today__values { color: #94a3b8; }
  .sab-calc-today__empty {
    margin: .75rem 0 0;
    color: #64748b;
    font-size: .8125rem;
  }
</style>

@php
  $existCountListHref = $urlPrefix . '/' . \App\Services\Seo\SabRenderService::PAGE_EXIST_COUNTS_LIST;
  $valueListHref = ($productUrlPrefix ?? '') . '/' . \App\Services\Seo\SabRenderService::PAGE_VALUE_LIST;
  $august19NewsHref = rtrim((string) ($urlPrefix ?? ''), '/') . '/news/steal-a-brainrot-sab-values-market-watch-headless-horseman-rebounds-dragon-cannelloni-drops-august-19-2026';
  $calculatorFaqItems = $calculatorFaqItems ?? [];
  $popularTradeItems = $popularTradeItems ?? collect();
  $showValuesTip = empty($calculatorOnly);
  $todayTopGainers = collect($todayTopGainers ?? [])->values();
  $todayTopLosers = collect($todayTopLosers ?? [])->values();
  $showTodaySummary = $showValuesTip;
@endphp

@if($isRotTheme)
<header class="mb-6 flex items-center gap-3">
  <div class="sab-calc-hero-icon" aria-hidden="true">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-green-400">
      <path d="m16 3 4 4-4 4"></path><path d="M20 7H4"></path><path d="m8 21-4-4 4-4"></path><path d="M4 17h16"></path>
    </svg>
  </div>
  <div>
    <h1 class="text-xl font-bold text-white tracking-tight">{{ $calcH1 }}</h1>
    <p class="text-sm text-slate-500 font-normal">{{ $calcIntro }}</p>
    @if(!empty($calculatorLastUpdatedLabel))
    <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-600">
      {{ $lastUpdateLabel ?? 'Last update' }}: <time datetime="{{ $calculatorLastUpdatedAt }}">{{ $calculatorLastUpdatedLabel }}</time>
    </p>
    @endif
  </div>
</header>
@else
<header class="-mx-3 border-b border-white/10 px-3 py-3 sm:-mx-4 sm:px-4">
  <div>
    <h1 class="sab-calc-intro-title">{{ $calcH1 }}</h1>
    <p class="mt-1 sab-calc-intro-copy">{{ $calcIntro }}</p>
    @if(!empty($calculatorLastUpdatedLabel))
    <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
      {{ $lastUpdateLabel ?? 'Last update' }}: <time datetime="{{ $calculatorLastUpdatedAt }}">{{ $calculatorLastUpdatedLabel }}</time>
    </p>
    @endif
    {{--
    @if(($locale ?? 'en') === 'en')
    <div class="mt-2 inline-flex max-w-full items-center gap-2 text-left text-xs font-semibold leading-5 text-cyan-200">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4 shrink-0 text-cyan-300" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 14h3l7 4V6L7 10H4v4Z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9a3 3 0 0 1 0 6M20 7a6 6 0 0 1 0 10"/>
      </svg>
      <a href="{{ \App\Services\Seo\SabSiteContext::EXTERNAL_MM2_CALCULATOR_URL }}" target="_blank" rel="noopener noreferrer" class="font-semibold underline hover:text-cyan-100">New tool · MM2 Value Calculator</a>
    </div>
    @endif
    --}}
  </div>
</header>
@endif

@if($showValuesTip)
<div class="mb-3 space-y-1">
  {{--
  <div class="flex max-w-full items-center gap-2 text-left text-xs font-semibold leading-5 text-cyan-200">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4 shrink-0 text-cyan-300" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 14h3l7 4V6L7 10H4v4Z"/>
      <path stroke-linecap="round" stroke-linejoin="round" d="M17 9a3 3 0 0 1 0 6M20 7a6 6 0 0 1 0 10"/>
    </svg>
    <a href="{{ $valueListHref }}" class="font-semibold underline hover:text-cyan-100">
      {{ $valuesTipLabel ?? 'SAB Values is live — see what moved today' }}
    </a>
  </div>
  --}}
  <div class="flex max-w-full items-center gap-2 text-left text-xs font-semibold leading-5 text-cyan-200">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4 shrink-0 text-cyan-300" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 14h3l7 4V6L7 10H4v4Z"/>
      <path stroke-linecap="round" stroke-linejoin="round" d="M17 9a3 3 0 0 1 0 6M20 7a6 6 0 0 1 0 10"/>
    </svg>
    <a href="{{ $august19NewsHref }}" class="font-semibold underline hover:text-cyan-100">
      News · Aug 19 — SAB Values: Headless Horseman rebounds, Dragon Cannelloni drops
    </a>
  </div>
</div>
@endif

<section id="sab-calculator" class="my-8" data-calculator-root data-calculator-theme="{{ $isRotTheme ? 'rot' : 'default' }}">
  <div class="sab-calc-card overflow-hidden">
    <div class="sab-calc-trade-grid">
      <div class="sab-calc-trade-side p-4 sm:p-6" data-side="offer">
        <div class="mb-5 flex items-center justify-between">
          <h2 class="sab-calc-side-title text-lg font-bold text-white">
            <span class="sab-calc-side-badge" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17 17 7"></path><path d="M8 7h9v9"></path></svg>
            </span>
            {{ $calcUi['offerTitle'] ?? 'Your Offer' }}
          </h2>
          <span class="text-sm text-slate-500" data-count>0 {{ $calcUi['countPlural'] ?? 'items' }}</span>
        </div>
        <div class="space-y-3" data-items></div>
        <div class="mt-5 space-y-2 text-sm" data-totals></div>
      </div>

      <div class="sab-calc-compare-panel p-4 text-center">
        <button type="button" class="sab-calc-swap-btn" data-swap title="{{ $calcUi['swapTitle'] ?? 'Swap sides' }}">
          <svg viewBox="0 0 24 24" class="sab-calc-swap-icon" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m16 3 4 4-4 4"></path><path d="M20 7H4"></path><path d="m8 21-4-4 4-4"></path><path d="M4 17h16"></path>
          </svg>
        </button>
        <div class="space-y-2" data-compare>
          <p class="text-sm text-slate-500 sab-calc-compare-hint">{{ $calcUi['compareEmpty'] ?? 'Add items to compare trades' }}</p>
        </div>
      </div>

      <div class="sab-calc-trade-side p-4 sm:p-6" data-side="receive">
        <div class="mb-5 flex items-center justify-between">
          <h2 class="sab-calc-side-title text-lg font-bold text-white">
            <span class="sab-calc-side-badge" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 7 7 17"></path><path d="M16 17H7V8"></path></svg>
            </span>
            {{ $calcUi['receiveTitle'] ?? 'You Receive' }}
          </h2>
          <span class="text-sm text-slate-500" data-count>0 {{ $calcUi['countPlural'] ?? 'items' }}</span>
        </div>
        <div class="space-y-3" data-items></div>
        <div class="mt-5 space-y-2 text-sm" data-totals></div>
      </div>
    </div>
    <div class="border-t border-white/15 p-4 text-center">
      <button type="button" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-slate-500 hover:text-rose-300" data-clear>
        <span>↻</span><span>{{ $calcUi['clearAll'] ?? 'Clear All' }}</span>
      </button>
    </div>
  </div>

  <div class="sab-calc-help-panel">
    <button type="button" class="sab-calc-help-toggle" data-help-toggle>
      <span>
        <span class="block text-base font-bold text-white">{{ $calcUi['helpTitle'] ?? (($calculatorOnly ?? false) ? 'How the calculator reads a trade' : 'How values are calculated') }}</span>
        <span class="text-sm text-slate-500">{{ $calcUi['helpSubtitle'] ?? (($calculatorOnly ?? false) ? 'Value, income, mutations, and trait effects' : 'Formulas and trait bonuses') }}</span>
      </span>
      <span class="sab-calc-help-chevron">⌄</span>
    </button>
    <div class="hidden space-y-4 border-t border-white/10 p-4" data-help>
      <div class="grid gap-4 md:grid-cols-2">
        <div class="sab-calc-help-card">
          <p class="mb-2 font-bold text-emerald-400">{{ $calcUi['incomeCheckTitle'] ?? 'Income Formula' }}</p>
          <p class="font-mono text-sm text-slate-300">{{ $calcUi['incomeFormula'] ?? 'Base × (Mutation + Traits)' }}</p>
          <p class="mt-2 text-xs leading-relaxed text-slate-500">{{ $calcUi['incomeCheckBody'] ?? 'Trait income multipliers stack additively with the mutation multiplier.' }}</p>
        </div>
        <div class="sab-calc-help-card">
          <p class="mb-2 font-bold text-yellow-400">{{ $calcUi['valueCheckTitle'] ?? 'Value Formula' }}</p>
          <p class="font-mono text-sm text-slate-300">{{ $calcUi['valueFormula'] ?? 'Mutation Value × (1 + Trait Bonuses × Streak)' }}</p>
          <p class="mt-2 text-xs leading-relaxed text-slate-500">{{ $calcUi['valueCheckBody'] ?? 'Each trait adds a percentage bonus to the mutation value.' }}</p>
        </div>
      </div>
      <div class="sab-calc-help-card" data-streak-help></div>
      <div class="sab-calc-help-card">
        <p class="mb-1 font-bold text-white">{{ $calcUi['traitValueBonuses'] ?? 'Trait Value Bonuses' }}</p>
        <p class="mb-3 text-xs text-slate-500">{{ $calcUi['bonusStackNote'] ?? 'Bonuses stack additively.' }}</p>
        <div data-trait-value-bonuses></div>
      </div>
    </div>
  </div>

  <div class="sab-calc-modal" data-modal aria-hidden="true">
    <div class="sab-calc-dialog">
      <div class="sab-calc-modal-header flex items-center justify-between border-b border-white/60 px-4 py-3">
        <h2 class="text-sm font-bold text-white" data-modal-title>{{ $calcUi['selectBrainrot'] ?? 'Select Brainrot' }}</h2>
        <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-white/5 hover:text-white" data-close>×</button>
      </div>
      <div class="sab-calc-modal-body">
        <div class="sab-calc-selected-panel mb-3" data-selected-panel>
          <div class="flex items-center gap-3">
            <img src="" alt="" class="sab-calc-selected-img" data-selected-image>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-bold text-white" data-selected-name></p>
              <p class="text-xs" style="color:#94a3b8">
                <span data-selected-meta></span> ·
                <button type="button" style="color:#4ade80;font-weight:700" data-change>{{ $calcUi['change'] ?? 'Change' }}</button>
              </p>
              <p class="text-sm font-bold sab-calc-value" data-selected-price></p>
            </div>
          </div>
        </div>

        <div class="sab-calc-picker-panel mb-3 hidden" data-picker-panel>
          <div class="sab-calc-search-wrap">
            <svg class="sab-calc-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input type="search" class="sab-calc-search-input" placeholder="{{ $calcUi['searchBrainrots'] ?? 'Search brainrots...' }}" data-search-brainrot>
          </div>
          <div class="sab-calc-rarity-filters" data-rarity-filters></div>
          <div class="sab-calc-picker-grid" data-brainrot-grid></div>
        </div>

        <div data-calc-sections>
          <div class="sab-calc-config-tabs" role="tablist" aria-label="{{ $calcUi['configureItemLabel'] ?? 'Configure item' }}">
            <button type="button" class="sab-calc-config-tab" data-config-tab="mutation" role="tab">{{ $calcUi['mutation'] ?? 'Mutation' }}</button>
            <button type="button" class="sab-calc-config-tab" data-config-tab="traits" role="tab">{{ $calcUi['traits'] ?? 'Traits' }}</button>
          </div>

          <div class="sab-calc-config-panel" data-config-panel="mutation" role="tabpanel">
            <h3 class="mb-2 text-sm font-bold text-white">{{ $calcUi['mutation'] ?? 'Mutation' }}</h3>
            <div class="sab-calc-mutation-grid" data-mutation-grid></div>
          </div>

          <div class="sab-calc-config-panel" data-config-panel="traits" role="tabpanel">
            <div class="mb-2 flex items-center justify-between gap-2">
              <h3 class="text-sm font-bold text-white">{{ $calcUi['traits'] ?? 'Traits' }} (<span data-trait-count>0</span> {{ $calcUi['selectedLabel'] ?? 'selected' }})</h3>
              <button type="button" class="text-xs text-slate-400 hover:text-white" data-sort-traits>⇅</button>
            </div>
            <input type="search" class="sab-calc-trait-search mb-2 w-full rounded-lg border border-slate-700 bg-slate-900 text-white outline-none focus:border-emerald-500" placeholder="{{ $calcUi['searchTraits'] ?? 'Search traits...' }}" data-search-trait>
            <div class="sab-calc-trait-grid" data-trait-grid></div>
          </div>
        </div>
      </div>
      <div class="sab-calc-income-panel" data-calc-income-panel>
        <div class="sab-calc-income-card">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem">
            <div>
              <h3 style="font-size:.75rem;font-weight:700;color:#94a3b8;padding-top:.25rem">{{ $calcUi['calculatedIncome'] ?? 'Calculated Income' }}</h3>
              <button type="button" style="margin-top:.45rem;border:1px solid rgba(34,197,94,.45);border-radius:9999px;background:rgba(34,197,94,.08);padding:.2rem .55rem;color:#4ade80;font-size:.68rem;font-weight:800;cursor:pointer" data-recalculate>
                {{ $calcUi['recalculate'] ?? 'Recalculate' }}
              </button>
            </div>
            <div style="text-align:right">
              <p style="font-size:1.75rem;font-weight:900;color:#00f078;line-height:1" data-modal-income></p>
              <p style="font-family:monospace;font-size:.7rem;color:#64748b;margin-top:.125rem" data-modal-income-raw></p>
              <p style="font-size:.75rem;font-weight:800;color:#4ade80;margin-top:.2rem" data-modal-multiplier></p>
            </div>
          </div>
          <div class="sab-calc-income-breakdown" data-income-breakdown></div>
        </div>
      </div>
      <div style="flex-shrink:0;border-top:1px solid rgba(255,255,255,.6);padding:.75rem" data-modal-footer>
        <button type="button" style="width:100%;border-radius:9999px;background:#22c55e;padding:.625rem 1rem;font-size:.9375rem;font-weight:900;color:#fff;cursor:pointer;border:none" data-save>{{ $calcUi['addItem'] ?? 'Add Item' }}</button>
      </div>
    </div>
  </div>
</section>

@if($showTodaySummary)
<section class="sab-calc-today" aria-labelledby="sab-calc-today-title">
  <div class="sab-calc-today__head">
    <h2 id="sab-calc-today-title" class="sab-calc-today__title">{{ $t['calculator_today_summary_title'] ?? "Today's summary" }}</h2>
    <a href="{{ $valueListHref }}" class="sab-calc-today__more">{{ $t['calculator_today_more'] ?? 'More' }}</a>
  </div>
  @if($todayTopGainers->isNotEmpty() || $todayTopLosers->isNotEmpty())
  <div class="sab-calc-today__grid">
    <div>
      <h3 class="sab-calc-today__col-title">{{ $t['calculator_today_top_gainers'] ?? 'Top gainers' }}</h3>
      <ol class="sab-calc-today__list">
        @forelse($todayTopGainers as $change)
        <li class="sab-calc-today__row">
          <span class="sab-calc-today__rank">{{ $loop->iteration }}.</span>
          @if(!empty($change['productUrl']))
          <a class="sab-calc-today__name" href="{{ $change['productUrl'] }}">{{ $change['itemName'] ?? '—' }}</a>
          @else
          <span class="sab-calc-today__name">{{ $change['itemName'] ?? '—' }}</span>
          @endif
          <span class="sab-calc-today__pct is-up">{{ $change['deltaPctLabel'] ?? '—' }}</span>
          <span class="sab-calc-today__values">{{ $change['before'] ?? '—' }} → {{ $change['after'] ?? '—' }}</span>
        </li>
        @empty
        <li class="sab-calc-today__row"><span>—</span></li>
        @endforelse
      </ol>
    </div>
    <div>
      <h3 class="sab-calc-today__col-title">{{ $t['calculator_today_top_losers'] ?? 'Top losers' }}</h3>
      <ol class="sab-calc-today__list">
        @forelse($todayTopLosers as $change)
        <li class="sab-calc-today__row">
          <span class="sab-calc-today__rank">{{ $loop->iteration }}.</span>
          @if(!empty($change['productUrl']))
          <a class="sab-calc-today__name" href="{{ $change['productUrl'] }}">{{ $change['itemName'] ?? '—' }}</a>
          @else
          <span class="sab-calc-today__name">{{ $change['itemName'] ?? '—' }}</span>
          @endif
          <span class="sab-calc-today__pct is-down">{{ $change['deltaPctLabel'] ?? '—' }}</span>
          <span class="sab-calc-today__values">{{ $change['before'] ?? '—' }} → {{ $change['after'] ?? '—' }}</span>
        </li>
        @empty
        <li class="sab-calc-today__row"><span>—</span></li>
        @endforelse
      </ol>
    </div>
  </div>
  @else
  <p class="sab-calc-today__empty">{{ $t['calculator_today_no_movers'] ?? 'No movers today yet.' }}</p>
  @endif
</section>
@endif

<section class="sab-calc-content sab-calc-copy">
  @if($calculatorOnly ?? false)
  <article class="sab-calc-guide sab-calc-guide-long">
    <section>
      <h2>What is the Steal a Brainrot Trade Calculator?</h2>
      <p>The Steal a Brainrot Trade Calculator is a practical trade checker for comparing both sides of a deal before you accept it in game. Instead of judging a trade by item names alone, you can add the Brainrots in your offer, add the Brainrots you would receive, and see how the totals compare. The calculator looks at value, income, mutation, traits, and quantity together, because a trade can look fair by name but change quickly once the exact setup is added.</p>
      <p>This is especially useful when a trade includes several Brainrots, mixed rarities, or items with different mutations. A player might offer one high-value Brainrot for multiple lower-value ones, or trade a strong Brainrot income item for something that is more desirable in Steal a Brainrot trading. By checking both total value and total income, the calculator works like a Brainrot value calculator that shows what each side is bringing to the trade.</p>
    </section>

    <section>
      <h2>How to use the SAB Trade Calculator</h2>
      <p>Start by adding the Brainrots you are offering on the left side. Then add the Brainrots you would receive on the right side. For each item, select the mutation that matches the item in the trade, then add any traits that are visible in game. If there are multiple copies of the same Brainrot, adjust the quantity so the total matches the real offer.</p>
      <p>After both sides are entered, compare total value and total income. Value is useful for checking whether the trade is balanced as a player-to-player deal. Income is useful for understanding how much each side can earn over time. The W/F/L result should be treated as a quick W/F/L trade checker before accepting, not as a final rule. A trade can still depend on demand, personal goals, rarity, and how badly each player wants the item.</p>
    </section>

    <section>
      <h2>Why mutation and traits affect trade value</h2>
      <p>Mutation and traits can change the way a Brainrot is judged in a trade. A plain version of an item may not compare the same way as a mutated version, even when the name is identical. Mutations such as Gold, Diamond, Rainbow, Candy, Bloodrot, and Galaxy can affect income, perceived rarity, SAB values, and the value players expect in return. Some mutations are mainly checked because they improve income, while others can matter because players view them as harder to get or more desirable.</p>
      <p>Traits add another layer. Several traits can stack into a stronger result, and some combinations make an item more attractive than a basic version of the same Brainrot. This is why the calculator asks for the exact mutation and traits instead of only the Brainrot name. If either side of the trade has a special setup, entering it correctly can change whether the trade looks like a win, fair trade, or lose.</p>
    </section>

    <section>
      <h2>Value vs Income: Which one matters more?</h2>
      <p>Value and income answer different questions. Steal a Brainrot value helps you judge the trade itself: whether the Brainrots you receive are likely to be worth more, less, or about the same as what you give away. Income helps you judge long-term usefulness: whether the items you receive can produce more money over time. A high-income item may be useful even when its trade value is not the highest, while a high-value item may be better for future trading even if its income is lower.</p>
      <p>For most trades, check both numbers. If value and income point in the same direction, the trade is easier to judge. If they disagree, think about your goal. Players who want faster earning may care more about income. Players who want rare items, demand, or future trading power may care more about value. The best use of the calculator is to make that tradeoff visible before you click accept.</p>
    </section>
  </article>
  @else
  <div class="sab-calc-seo-grid md:grid-cols-2">
    <article class="sab-calc-seo-panel">
      <h2 class="sab-calc-seo-title">{{ $calcUi['seoTitle'] ?? 'How to Use the SAB Calculator' }}</h2>
      <ol class="sab-calc-step-list sab-calc-seo-text">
        @foreach(($calcUi['seoSteps'] ?? []) as $step)
        <li>{{ $step }}</li>
        @endforeach
      </ol>
    </article>

    <article class="sab-calc-seo-panel">
      <h2 class="sab-calc-seo-title">{{ $calcUi['valuesTitle'] ?? 'What Are SAB Trading Values?' }}</h2>
      <p class="sab-calc-seo-text">{{ $calcUi['valuesBody'] ?? '' }}</p>
    </article>

    <article class="sab-calc-seo-panel">
      <h2 class="sab-calc-seo-title">{{ $calcUi['wflTitle'] ?? 'How to Check W/F/L in Steal a Brainrot' }}</h2>
      <p class="sab-calc-seo-text">{{ $calcUi['wflBody'] ?? '' }}</p>
    </article>

    <article class="sab-calc-seo-panel">
      <h2 class="sab-calc-seo-title">{{ $calcUi['mutationsTitle'] ?? 'How Mutations and Traits Affect SAB Trade Value' }}</h2>
      <p class="sab-calc-seo-text">{{ $calcUi['mutationsBody'] ?? '' }}</p>
    </article>

    <article class="sab-calc-seo-panel">
      <h2 class="sab-calc-seo-title">{{ $calcUi['existCountTitle'] ?? 'Why Exist Count Matters in SAB Trading' }}</h2>
      <p class="sab-calc-seo-text">{{ $calcUi['existCountBody'] ?? '' }}</p>
      @if(!($calculatorOnly ?? false))
      <div class="mt-4 flex flex-wrap gap-2">
        <a class="sab-calc-link-pill" href="{{ $existCountListHref }}">{{ $calcUi['existCountLink'] ?? 'View Steal a Brainrot Exist Count List' }}</a>
        <a class="sab-calc-link-pill" href="{{ $valueListHref }}">{{ $calcUi['valueListLink'] ?? 'View Steal a Brainrot Value List' }}</a>
      </div>
      @endif
    </article>

    <article class="sab-calc-seo-panel">
      <h2 class="sab-calc-seo-title">{{ $calcUi['tipsTitle'] ?? 'SAB Trading Tips' }}</h2>
      <ul class="sab-calc-tip-list sab-calc-seo-text">
        @foreach(($calcUi['tips'] ?? []) as $tip)
        <li>{{ $tip }}</li>
        @endforeach
      </ul>
    </article>
  </div>
  @endif

  @if(!($calculatorOnly ?? false) && $popularTradeItems->isNotEmpty())
    <section class="sab-calc-seo-panel mt-4">
      <h2 class="sab-calc-seo-title">{{ $calcUi['popularTitle'] ?? 'Popular Brainrots to Check Before Trading' }}</h2>
      <p class="mb-3 sab-calc-seo-text">{{ $calcUi['popularBody'] ?? '' }}</p>
      <div class="flex flex-wrap gap-2">
        @foreach($popularTradeItems as $popularItem)
          @if(\App\Services\Seo\SabRenderService::shouldLinkProduct($popularItem))
          <a class="sab-calc-link-pill" href="{{ $productUrlPrefix }}/products/{{ \App\Services\Seo\SabRenderService::productPublicSlug($popularItem->slug ?? '') }}">{{ $popularItem->name }}</a>
          @endif
        @endforeach
      </div>
    </section>
  @endif

  @if(($calculatorOnly ?? false) && !empty($faqHref))
  <section id="faq" class="sab-calc-seo-panel mt-4">
    <h2 class="mb-3 sab-calc-seo-title">FAQ</h2>
    <p class="sab-calc-seo-text">
      Read how the trade checker works, how income and values are estimated, and why mutations and traits matter before you accept.
    </p>
    <p class="mt-3">
      <a class="sab-calc-link-pill" href="{{ $faqHref }}">Read FAQ</a>
    </p>
  </section>
  @elseif(!($calculatorOnly ?? false))
  <section id="faq" class="sab-calc-seo-panel mt-4">
    <h2 class="mb-3 sab-calc-seo-title">{{ $calcUi['faqTitle'] ?? 'SAB Calculator FAQ' }}</h2>
    <div class="sab-calc-faq-list">
      @foreach($calculatorFaqItems as $faqItem)
        <article class="sab-calc-faq-item">
          <h3 class="text-sm font-bold text-slate-100">{{ $faqItem['question'] }}</h3>
          <p class="mt-1 sab-calc-seo-text">{{ $faqItem['answer'] }}</p>
        </article>
      @endforeach
    </div>
  </section>
  @endif
</section>
@endsection

@section('scripts')
<script>
(() => {
  const data = @json($calculatorData);
  const ui = @json($calcUi);
  const root = document.querySelector('[data-calculator-root]');
  if (!root || !data.brainrots || data.brainrots.length === 0) return;
  const rotTheme = root.dataset.calculatorTheme === 'rot';

  const state = {
    offer: [],
    receive: [],
    modalSide: 'offer',
    editIndex: null,
    selected: null,
    mutation: null,
    traits: [],
    traitDescending: true,
    rarityFilter: '',
    configTab: 'mutation',
  };

  const byId = new Map(data.brainrots.map(item => [item.id, item]));
  const modal = root.querySelector('[data-modal]');
  const dialog = root.querySelector('.sab-calc-dialog');
  const selectedPanel = root.querySelector('[data-selected-panel]');
  const pickerPanel = root.querySelector('[data-picker-panel]');
  const brainrotGrid = root.querySelector('[data-brainrot-grid]');
  const rarityFilters = root.querySelector('[data-rarity-filters]');
  const mutationGrid = root.querySelector('[data-mutation-grid]');
  const traitGrid = root.querySelector('[data-trait-grid]');
  const configTabs = Array.from(root.querySelectorAll('[data-config-tab]'));
  const configPanels = Array.from(root.querySelectorAll('[data-config-panel]'));
  const searchBrainrot = root.querySelector('[data-search-brainrot]');
  const searchTrait = root.querySelector('[data-search-trait]');

  function money(value) {
    if (!Number.isFinite(value)) return 'N/A';
    return '$' + (value / 100).toFixed(2);
  }

  function income(value) {
    if (!Number.isFinite(value)) return '$0/s';
    const abs = Math.abs(value);
    const sign = value > 0 ? '+' : value < 0 ? '-' : '';
    const v = abs >= 1e9 ? (abs / 1e9).toFixed(1) + 'B' : abs >= 1e6 ? (abs / 1e6).toFixed(1) + 'M' : abs >= 1e3 ? (abs / 1e3).toFixed(1) + 'K' : Math.round(abs).toString();
    return sign + '$' + v.replace('.0', '') + '/s';
  }

  function image(src, alt, cls) {
    const img = document.createElement('img');
    img.src = src || '';
    img.alt = alt || '';
    img.className = cls || '';
    img.loading = 'lazy';
    return img;
  }

  function rarityKey(value) {
    return String(value || '').trim().toLowerCase();
  }

  const rarityOptions = Array.from(new Map(
    data.brainrots
      .map(item => String(item.rarity || '').trim())
      .filter(Boolean)
      .map(label => [rarityKey(label), label])
  ).entries()).map(([key, label]) => ({ key, label }));

  function calcItem(item) {
    const mutation = item.mutation || { name: ui.defaultMutation || 'Default', multiplier: 1, robuxValue: item.brainrot.robuxValue };
    // Additive traits (multiplier >= 1) sum into the income multiplier.
    // Multiplicative traits (multiplier < 1, e.g. Sleepy at 0.5) are applied as a
    // separate product AFTER the additive sum — matching rot.rocks calculator behaviour.
    let additiveTraitMult = 0;
    let multiplicativeTraitMult = 1;
    item.traits.forEach(trait => {
      const m = Number(trait.multiplier || 0);
      if (m < 1) { multiplicativeTraitMult *= m; } else { additiveTraitMult += m; }
    });
    const incomeMultiplier = (Number(mutation.multiplier || 1) + additiveTraitMult) * multiplicativeTraitMult;
    const calculatedIncome = Number(item.brainrot.baseIncome || 0) * incomeMultiplier * item.quantity;
    const baseValue = Number.isFinite(Number(mutation.robuxValue)) ? Number(mutation.robuxValue) : Number(item.brainrot.robuxValue);
    const streak = streakMultiplier(item.traits.length);
    const traitValueMultiplier = Math.max(0.1, 1 + item.traits.reduce((sum, trait) => sum + (Number(trait.valueMultiplier || 1) - 1), 0) * streak);
    const value = Number.isFinite(baseValue) ? Math.round(baseValue * traitValueMultiplier) * item.quantity : null;
    return { calculatedIncome, incomeMultiplier, value, baseValue, traitValueMultiplier };
  }

  function streakMultiplier(count) {
    let result = 1;
    Object.entries(data.streakMultipliers || {}).forEach(([threshold, multiplier]) => {
      if (count >= Number(threshold) && Number(multiplier) > result) result = Number(multiplier);
    });
    return result;
  }

  function totals(items) {
    return items.reduce((acc, item) => {
      const calc = calcItem(item);
      acc.income += calc.calculatedIncome;
      if (calc.value !== null) acc.value += calc.value;
      return acc;
    }, { income: 0, value: 0 });
  }

  function renderSide(side) {
    const box = root.querySelector(`[data-side="${side}"]`);
    const list = box.querySelector('[data-items]');
    const count = box.querySelector('[data-count]');
    const totalBox = box.querySelector('[data-totals]');
    const items = state[side];
    list.innerHTML = '';
    const quantityCount = items.reduce((sum, item) => sum + item.quantity, 0);
    count.textContent = quantityCount + ' ' + (quantityCount === 1 ? (ui.countSingular || 'item') : (ui.countPlural || 'items'));

    items.forEach((item, index) => {
      const calc = calcItem(item);
      const row = document.createElement('div');
      row.className = 'sab-calc-item-card';
      row.innerHTML = `
        <div style="display:flex;gap:.75rem;align-items:flex-start">
          <img src="${item.brainrot.image || ''}" alt="" style="width:3rem;height:3rem;flex-shrink:0;object-fit:contain">
          <div style="min-width:0;flex:1">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem">
              <div style="min-width:0">
                <p style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:1rem;font-weight:700;color:#fff">${item.brainrot.name}</p>
                <p style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.875rem;color:#94a3b8">${item.mutation?.name || ui.defaultMutation || 'Default'}</p>
                <p style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.75rem;color:#64748b">${item.traits.map(t => t.name).join(', ')}</p>
              </div>
              <div style="display:flex;gap:.25rem;flex-shrink:0">
                <button type="button" style="padding:0 .5rem;color:#64748b;cursor:pointer;background:none;border:none" data-edit="${index}">✎</button>
                <button type="button" style="padding:0 .5rem;color:#64748b;cursor:pointer;background:none;border:none" data-remove="${index}">×</button>
              </div>
            </div>
            <div style="margin-top:.75rem;display:flex;flex-wrap:wrap;align-items:center;gap:.75rem">
              <span class="sab-calc-income" style="font-weight:900">${income(calc.calculatedIncome).replace('+', '')}</span>
              <span class="sab-calc-value" style="font-weight:900">${calc.value === null ? 'N/A' : money(calc.value) + '+'}</span>
              <button type="button" style="padding:0 .5rem;color:#94a3b8;cursor:pointer;background:none;border:none" data-dec="${index}">−</button>
              <span style="font-weight:700;color:#fff">${item.quantity}</span>
              <button type="button" style="padding:0 .5rem;color:#94a3b8;cursor:pointer;background:none;border:none" data-inc="${index}">+</button>
            </div>
          </div>
        </div>`;
      list.appendChild(row);
    });

    if (rotTheme) {
      const addBtn = document.createElement('button');
      addBtn.type = 'button';
      addBtn.className = 'sab-calc-add-row';
      addBtn.dataset.addSlot = side;
      addBtn.innerHTML = '<span aria-hidden="true" style="font-size:1rem;line-height:1">+</span><span>' + (ui.addItem || 'Add Item') + '</span>';
      list.appendChild(addBtn);
    } else {
      const slotGrid = document.createElement('div');
      slotGrid.className = 'sab-calc-slot-grid' + (items.length ? ' has-items' : '');
      const slotCount = items.length < 4 ? 4 - items.length : 1;
      for (let i = 0; i < slotCount; i += 1) {
        const slot = document.createElement('button');
        slot.type = 'button';
        slot.className = 'sab-calc-add-slot';
        slot.setAttribute('aria-label', side === 'offer' ? (ui.addOfferItem || 'Add offer item') : (ui.addReceiveItem || 'Add receive item'));
        slot.dataset.addSlot = side;
        slot.textContent = '+';
        slotGrid.appendChild(slot);
      }
      list.appendChild(slotGrid);
    }

    list.querySelectorAll('[data-edit]').forEach(btn => btn.addEventListener('click', () => openModal(side, Number(btn.dataset.edit))));
    list.querySelectorAll('[data-remove]').forEach(btn => btn.addEventListener('click', () => { items.splice(Number(btn.dataset.remove), 1); render(); }));
    list.querySelectorAll('[data-dec]').forEach(btn => btn.addEventListener('click', () => { const item = items[Number(btn.dataset.dec)]; item.quantity = Math.max(1, item.quantity - 1); render(); }));
    list.querySelectorAll('[data-inc]').forEach(btn => btn.addEventListener('click', () => { items[Number(btn.dataset.inc)].quantity += 1; render(); }));
    list.querySelectorAll('[data-add-slot]').forEach(btn => btn.addEventListener('click', () => openModal(btn.dataset.addSlot)));

    const total = totals(items);
    totalBox.innerHTML = items.length ? `
      <div style="display:flex;justify-content:space-between;gap:1rem"><span style="color:#64748b">${ui.totalIncome || 'Total Income'}</span><span class="sab-calc-income" style="font-weight:900">${income(total.income).replace('+', '')}</span></div>
      <div style="display:flex;justify-content:space-between;gap:1rem"><span style="color:#64748b">${ui.totalValue || 'Total Value'}</span><span class="sab-calc-value" style="font-weight:900">${money(total.value)}${total.value > 0 ? '+' : ''}</span></div>
    ` : '';
  }

  function renderCompare() {
    const offer = totals(state.offer);
    const receive = totals(state.receive);
    const incomeDiff = receive.income - offer.income;
    const valueDiff = receive.value - offer.value;
    const valuePct = offer.value > 0 ? (valueDiff / offer.value) * 100 : (receive.value > 0 ? 100 : 0);
    let deal = ui.fairTrade || 'Fair trade';
    if (state.offer.length && state.receive.length && valuePct > 10) { deal = ui.winTrade || 'Win trade'; }
    if (state.offer.length && state.receive.length && valuePct < -10) { deal = ui.loseTrade || 'Lose trade'; }
    const dealClass = deal === (ui.winTrade || 'Win trade') ? 'sab-calc-deal-good' : (deal === (ui.loseTrade || 'Lose trade') ? 'sab-calc-deal-bad' : 'sab-calc-deal-fair');
    let statusText = rotTheme ? (ui.compareEmpty || 'Add items to compare trades') : (ui.compareBothEmpty || 'Add items to both sides to check W/F/L.');
    if (!rotTheme && state.offer.length && !state.receive.length) statusText = ui.compareNeedReceive || 'Add items to You Receive to compare the trade.';
    if (!rotTheme && !state.offer.length && state.receive.length) statusText = ui.compareNeedOffer || 'Add items to Your Offer to compare the trade.';
    if (rotTheme && (state.offer.length || state.receive.length) && !(state.offer.length && state.receive.length)) {
      statusText = ui.compareBothEmpty || 'Add items to both sides to compare trades';
    }

    root.querySelector('[data-compare]').innerHTML = state.offer.length && state.receive.length ? `
      <div>
        <p class="text-xs uppercase tracking-wide text-slate-500">${ui.incomeLabel || 'Income'}</p>
        <p class="${incomeDiff >= 0 ? 'sab-calc-income' : 'sab-calc-danger'} text-2xl font-black">${income(incomeDiff)}</p>
      </div>
      <div>
        <p class="text-xs uppercase tracking-wide text-slate-500">${ui.valueLabel || 'Value'}</p>
        <p class="${valueDiff >= 0 ? 'sab-calc-income' : 'sab-calc-danger'} text-2xl font-black">${valueDiff >= 0 ? '+' : '-'}${money(Math.abs(valueDiff))}</p>
        <p class="text-sm text-slate-500">${valuePct >= 0 ? '+' : ''}${valuePct.toFixed(1)}%</p>
      </div>
      <span class="sab-calc-deal-pill ${dealClass}">${deal}</span>
    ` : `
      <div class="sab-calc-compare-status">
        <p class="text-xs uppercase tracking-wide text-slate-500">${ui.wflCheckLabel || 'W/F/L Check'}</p>
        <p style="margin-top:.35rem;font-size:.95rem;line-height:1.45;font-weight:800;color:#cbd5e1">${statusText}</p>
      </div>
    `;
  }

  function render() {
    renderSide('offer');
    renderSide('receive');
    renderCompare();
  }

  function openModal(side, index = null) {
    state.modalSide = side;
    state.editIndex = index;
    const existing = index === null ? null : state[side][index];
    state.selected = existing?.brainrot || data.brainrots[0];
    state.mutation = existing?.mutation || state.selected.mutations[0];
    state.traits = existing ? [...existing.traits] : [];
    searchBrainrot.value = '';
    searchTrait.value = '';
    state.rarityFilter = '';
    state.configTab = 'mutation';
    document.body.classList.add('sab-calc-modal-open');
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    renderModal(index === null);
  }

  function closeModal() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('sab-calc-modal-open');
  }

  function renderModal(showPicker = false) {
    dialog.classList.toggle('is-picker', showPicker);
    dialog.classList.toggle('is-config', !showPicker);
    root.querySelector('[data-modal-title]').classList.toggle('hidden', !showPicker);
    selectedPanel.classList.toggle('hidden', showPicker);
    pickerPanel.classList.toggle('hidden', !showPicker);
    root.querySelector('[data-calc-sections]').classList.toggle('hidden', showPicker);
    root.querySelector('[data-calc-income-panel]').classList.toggle('hidden', showPicker);
    root.querySelector('[data-modal-footer]').classList.toggle('hidden', showPicker);
    if (showPicker) { renderRarityFilters(); renderBrainrotGrid(); return; }
    root.querySelector('[data-selected-image]').src = state.selected.image || '';
    root.querySelector('[data-selected-name]').textContent = state.selected.name;
    root.querySelector('[data-selected-meta]').textContent = `${ui.baseLabel || 'Base'}: ${income(Number(state.selected.baseIncome)).replace('+', '')} · ${state.selected.rarity || ''}`;
    const priceEl = root.querySelector('[data-selected-price]');
    if (priceEl) {
      const rv = Number(state.selected.robuxValue);
      priceEl.textContent = Number.isFinite(rv) && rv > 0 ? money(rv) : '';
    }
    renderConfigTabs();
    renderMutations();
    renderTraits();
    renderIncomeBox();
    root.querySelector('[data-save]').textContent = state.editIndex === null ? (ui.addItem || 'Add Item') : (ui.updateItem || 'Update Item');
  }

  function renderConfigTabs() {
    configTabs.forEach(tab => {
      const active = tab.dataset.configTab === state.configTab;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    configPanels.forEach(panel => {
      panel.classList.toggle('is-active', panel.dataset.configPanel === state.configTab);
    });
  }

  function renderBrainrotGrid() {
    const q = searchBrainrot.value.toLowerCase().trim();
    brainrotGrid.innerHTML = '';
    const items = data.brainrots
      .filter(item => !q || item.name.toLowerCase().includes(q))
      .filter(item => !state.rarityFilter || rarityKey(item.rarity) === state.rarityFilter)
      .slice(0, 160);

    if (!items.length) {
      brainrotGrid.innerHTML = `<div class="sab-calc-empty-grid">${ui.noBrainrotsFound || 'No matching Brainrots found.'}</div>`;
      return;
    }

    items.forEach(item => {
      const btn = document.createElement('button');
      btn.type = 'button';
      const isSelected = state.selected?.id === item.id;
      btn.className = `sab-calc-brainrot-btn${isSelected ? ' is-selected' : ''}`;
      const imageWrap = document.createElement('span');
      imageWrap.className = 'sab-calc-brainrot-img-wrap';
      imageWrap.appendChild(image(item.image, item.name, 'sab-calc-brainrot-img'));
      btn.appendChild(imageWrap);
      const name = document.createElement('p');
      name.className = 'sab-calc-brainrot-name';
      name.textContent = item.name;
      btn.appendChild(name);
      const rarity = document.createElement('p');
      rarity.className = 'sab-calc-brainrot-rarity';
      if (item.rarityColor) rarity.style.color = item.rarityColor;
      rarity.textContent = item.rarity || '';
      btn.appendChild(rarity);
      btn.addEventListener('click', () => {
        state.selected = item;
        state.mutation = item.mutations[0];
        state.traits = [];
        renderModal(false);
      });
      brainrotGrid.appendChild(btn);
    });
  }

  function renderRarityFilters() {
    rarityFilters.innerHTML = '';
    [{ key: '', label: @json($t['rarity_filter_all'] ?? 'All') }, ...rarityOptions].forEach(option => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'sab-calc-rarity-chip';
      btn.classList.toggle('is-selected', state.rarityFilter === option.key);
      btn.textContent = option.label;
      btn.addEventListener('click', () => {
        state.rarityFilter = option.key;
        renderRarityFilters();
        renderBrainrotGrid();
      });
      rarityFilters.appendChild(btn);
    });
  }

  function renderMutations() {
    mutationGrid.innerHTML = '';
    state.selected.mutations.forEach(mutation => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'sab-calc-chip sab-calc-mutation-btn sab-calc-mutation-chip text-sm font-bold text-slate-300';
      btn.classList.toggle('is-selected', state.mutation?.id === mutation.id);
      if (mutation.image) btn.appendChild(image(mutation.image, mutation.name, 'sab-calc-mutation-img'));
      const label = document.createElement('span');
      label.textContent = `${mutation.name} (${mutation.multiplier}x)`;
      btn.appendChild(label);
      const badge = document.createElement('span');
      badge.className = 'sab-calc-mutation-badge';
      badge.textContent = '✓';
      btn.appendChild(badge);
      btn.addEventListener('click', () => {
        state.mutation = mutation;
        state.configTab = 'traits';
        renderModal(false);
      });
      mutationGrid.appendChild(btn);
    });
  }

  function renderTraits() {
    const q = searchTrait.value.toLowerCase().trim();
    const selectedIds = new Set(state.traits.map(t => t.id));
    const traits = [...data.traits]
      .filter(trait => !q || trait.name.toLowerCase().includes(q))
      .sort((a, b) => state.traitDescending ? b.multiplier - a.multiplier : a.name.localeCompare(b.name));
    root.querySelector('[data-trait-count]').textContent = state.traits.length;
    traitGrid.innerHTML = '';
    traits.forEach(trait => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'sab-calc-chip sab-calc-trait text-left';
      btn.classList.toggle('is-selected', selectedIds.has(trait.id));
      btn.appendChild(image(trait.image, '', 'sab-calc-trait-img'));
      const text = document.createElement('span');
      text.className = 'min-w-0';
      text.innerHTML = `<span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.75rem;font-weight:700;color:#fff">${trait.name}</span><span style="display:block;font-size:.65rem;color:#64748b">${trait.multiplier}x</span>`;
      btn.appendChild(text);
      const mark = document.createElement('span');
      const sel = selectedIds.has(trait.id);
      if (sel) {
        mark.style.cssText = 'width:18px;height:18px;border-radius:50%;background:#16a34a;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:700;color:#fff;flex-shrink:0';
        mark.textContent = '✓';
      } else {
        mark.style.cssText = 'font-size:.75rem;color:#475569;flex-shrink:0;line-height:1';
        mark.textContent = '+';
      }
      btn.appendChild(mark);
      btn.addEventListener('click', () => {
        const idx = state.traits.findIndex(row => row.id === trait.id);
        if (idx >= 0) state.traits.splice(idx, 1);
        else state.traits.push(trait);
        renderModal(false);
      });
      traitGrid.appendChild(btn);
    });
  }

  function renderIncomeBox() {
    const item = { brainrot: state.selected, mutation: state.mutation, traits: state.traits, quantity: 1 };
    const calc = calcItem(item);
    root.querySelector('[data-modal-income]').textContent = income(calc.calculatedIncome).replace('+', '');
    root.querySelector('[data-modal-income-raw]').textContent = '$' + Math.round(calc.calculatedIncome).toLocaleString() + '/s';
    root.querySelector('[data-modal-multiplier]').textContent = calc.incomeMultiplier.toFixed(2) + 'x ' + (ui.totalMultiplier || 'Total Multiplier');
    const box = root.querySelector('[data-income-breakdown]');

    const rowStyle = 'display:grid;grid-template-columns:52px 1fr auto;gap:.25rem .5rem;align-items:center;padding:.3rem 0;font-size:.75rem;border-bottom:1px solid rgba(255,255,255,.05)';
    const mutMult = Number(state.mutation?.multiplier || 1);

    const traitRows = state.traits.map(trait => `
      <div style="${rowStyle}">
        <span style="color:#94a3b8;font-weight:700">${trait.multiplier}</span>
        <span style="color:#fff;font-weight:600">${trait.name}</span>
        <span style="color:#64748b;font-size:.65rem">${trait.multiplier}x ${ui.traitMultiplierLabel || 'trait'}</span>
      </div>`).join('');

    box.innerHTML = `
      <div style="${rowStyle}">
        <span style="color:#94a3b8">${ui.baseLabel || 'Base'}</span>
        <span style="color:#fff;font-weight:700">${income(Number(state.selected.baseIncome)).replace('+', '')}</span>
        <span></span>
      </div>
      ${traitRows}
      <div style="${rowStyle}">
        <span style="color:#94a3b8;font-weight:700">+ ${mutMult}</span>
        <span style="color:#fff;font-weight:600">${state.mutation?.name || ui.defaultMutation || 'Default'}</span>
        <span style="color:#64748b;font-size:.65rem">${mutMult}x ${ui.mutationMultiplierLabel || 'mutation'}</span>
      </div>
      <div style="margin-top:.375rem;border-radius:.375rem;background:rgba(34,197,94,.15);padding:.375rem .625rem;display:grid;grid-template-columns:52px 1fr;gap:.5rem;align-items:center;font-size:.8rem;font-weight:700;color:#4ade80">
        <span>= ${calc.incomeMultiplier.toFixed(2)}x</span>
        <span>${ui.totalMultiplier || 'Total Multiplier'}</span>
      </div>
    `;
  }

  function bonusPctStyle(pct) {
    if (pct >= 50) return 'background:rgba(22,163,74,.22);color:#4ade80;border:1px solid rgba(34,197,94,.45)';
    if (pct >= 30) return 'background:rgba(16,185,129,.18);color:#34d399;border:1px solid rgba(52,211,153,.4)';
    if (pct >= 15) return 'background:rgba(20,184,166,.16);color:#2dd4bf;border:1px solid rgba(45,212,191,.35)';
    if (pct >= 0) return 'background:rgba(100,116,139,.18);color:#94a3b8;border:1px solid rgba(100,116,139,.35)';
    return 'background:rgba(244,63,94,.14);color:#fb7185;border:1px solid rgba(244,63,94,.35)';
  }

  function renderHelpSection() {
    const streakBox = root.querySelector('[data-streak-help]');
    const bonusBox = root.querySelector('[data-trait-value-bonuses]');
    if (!streakBox || !bonusBox) return;

    const streakEntries = Object.entries(data.streakMultipliers || {})
      .map(([threshold, multiplier]) => ({ threshold: Number(threshold), multiplier: Number(multiplier) }))
      .filter(entry => entry.threshold > 0 && entry.multiplier > 1)
      .sort((a, b) => a.threshold - b.threshold);

    streakBox.innerHTML = `
      <p class="mb-2 font-bold text-cyan-300">${ui.traitValueBonuses || 'Trait Value Bonuses'}</p>
      <div class="space-y-2 text-sm text-slate-300">
        ${streakEntries.length ? streakEntries.map(entry => `
          <div class="flex items-center justify-between gap-3 rounded-lg border border-white/10 bg-slate-950/50 px-3 py-2">
            <span>${(ui.traitsSelectedCount || '{count}+ traits selected').replace('{count}', entry.threshold)}</span>
            <span class="font-bold text-emerald-400">${(ui.valueBonus || '{multiplier}x value bonus').replace('{multiplier}', entry.multiplier)}</span>
          </div>
        `).join('') : `<p class="text-xs text-slate-500">${ui.traitBonusEmpty || 'Trait bonus data will appear after calculator sync.'}</p>`}
      </div>
    `;

    const tiers = {};
    (data.traits || []).forEach(trait => {
      const pct = Math.round((Number(trait.valueMultiplier || 1) - 1) * 100);
      tiers[pct] = tiers[pct] || [];
      tiers[pct].push(trait);
    });

    const sortedPcts = Object.keys(tiers).map(Number).sort((a, b) => b - a);
    if (!sortedPcts.length) {
      bonusBox.innerHTML = `<p class="text-xs text-slate-500">${ui.traitBonusEmpty || 'Trait bonus data will appear after calculator sync.'}</p>`;
      return;
    }

    bonusBox.innerHTML = sortedPcts.map(pct => {
      const traits = tiers[pct].slice().sort((a, b) => a.name.localeCompare(b.name));
      const label = `${pct >= 0 ? '+' : ''}${pct}%`;
      return `
        <div class="sab-calc-bonus-row">
          <span class="sab-calc-bonus-pct" style="${bonusPctStyle(pct)}">${label}</span>
          <div class="sab-calc-bonus-tags">
            ${traits.map(trait => `
              <span class="sab-calc-bonus-tag">
                ${trait.image ? `<img src="${trait.image}" alt="">` : ''}
                <span>${trait.name}</span>
              </span>
            `).join('')}
          </div>
        </div>
      `;
    }).join('');
  }

  root.querySelector('[data-swap]').addEventListener('click', () => { const old = state.offer; state.offer = state.receive; state.receive = old; render(); });
  root.querySelector('[data-clear]').addEventListener('click', () => { state.offer = []; state.receive = []; render(); });
  root.querySelector('[data-close]').addEventListener('click', closeModal);
  root.querySelector('[data-change]').addEventListener('click', () => renderModal(true));
  root.querySelector('[data-recalculate]').addEventListener('click', () => {
    state.mutation = state.selected.mutations[0];
    state.traits = [];
    renderModal(false);
  });
  configTabs.forEach(tab => tab.addEventListener('click', () => {
    state.configTab = tab.dataset.configTab;
    renderConfigTabs();
  }));
  root.querySelector('[data-save]').addEventListener('click', () => {
    const item = { brainrot: state.selected, mutation: state.mutation, traits: [...state.traits], quantity: state.editIndex === null ? 1 : state[state.modalSide][state.editIndex].quantity };
    if (state.editIndex === null) state[state.modalSide].push(item);
    else state[state.modalSide][state.editIndex] = item;
    closeModal();
    render();
  });
  root.querySelector('[data-sort-traits]').addEventListener('click', () => { state.traitDescending = !state.traitDescending; renderTraits(); });
  root.querySelector('[data-help-toggle]').addEventListener('click', () => root.querySelector('[data-help]').classList.toggle('hidden'));
  searchBrainrot.addEventListener('input', renderBrainrotGrid);
  searchTrait.addEventListener('input', renderTraits);
  modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });

  renderHelpSection();

  const initialSlug = new URLSearchParams(window.location.search).get('item');
  if (initialSlug) {
    const brainrot = data.brainrots.find(b => b.slug === initialSlug);
    if (brainrot) {
      state.offer.push({
        brainrot,
        mutation: brainrot.mutations[0],
        traits: [],
        quantity: 1,
      });
    }
  }

  render();
})();
</script>
@endsection
