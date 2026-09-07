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
