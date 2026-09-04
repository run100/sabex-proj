<style>
  .sab-wiki { color:#e2e8f0; }
  .sab-wiki a { text-underline-offset:3px; }
  .sab-wiki-hero { padding:0 0 .15rem; }
  .sab-wiki-title { margin:0; color:#f8fafc; font-size:clamp(1.5rem,3.2vw,2.15rem); font-weight:950; line-height:1.15; letter-spacing:-.03em; }
  .sab-wiki-lead { margin:.55rem 0 0; color:#cbd5e1; font-size:.95rem; line-height:1.55; }
  .sab-wiki-meta { margin:.45rem 0 0; color:#94a3b8; font-size:.8rem; line-height:1.45; }
  .sab-wiki-panel { margin-top:1rem; border:1px solid rgba(148,163,184,.16); border-radius:.85rem; background:rgba(15,23,42,.68); padding:.9rem; }
  .sab-wiki-panel h2,.sab-wiki-nav-title { margin:0; color:#f8fafc; font-size:1.05rem; font-weight:900; }
  .sab-wiki-panel p,.sab-wiki-panel li { color:#cbd5e1; line-height:1.55; }
  .sab-wiki-toc { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.45rem; margin:.7rem 0 0; padding:0; list-style:none; }
  .sab-wiki-toc a { display:block; border:1px solid rgba(148,163,184,.14); border-radius:.6rem; padding:.5rem .65rem; background:#020617; color:#a5f3fc; font-size:.8rem; font-weight:750; text-decoration:none; }
  .sab-wiki-toc a:hover { border-color:rgba(34,211,238,.5); color:#fff; }
  .sab-wiki-copy-grid { display:grid; gap:.85rem; margin-top:1rem; }
  .sab-wiki-definition-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.6rem; margin-top:.75rem; }
  .sab-wiki-definition { border-left:3px solid #22d3ee; padding:.65rem .75rem; background:#020617; }
  .sab-wiki-definition strong { color:#f8fafc; }
  .sab-wiki-definition p { margin:.25rem 0 0; font-size:.88rem; }
  .sab-wiki-tools { display:flex; flex-wrap:wrap; gap:.45rem; margin-top:.7rem; }
  .sab-wiki-tool { border:1px solid rgba(34,211,238,.3); border-radius:999px; padding:.4rem .7rem; color:#a5f3fc; font-size:.78rem; font-weight:800; text-decoration:none; }
  .sab-wiki-tool:hover { background:rgba(8,145,178,.18); color:#fff; }
  .sab-wiki-tool.is-active:not([class*="brainrot-rarity-"]) { border-color:#22d3ee; background:rgba(8,145,178,.22); color:#cffafe; }
  .brainrot-rarity-common { --rarity-bg:rgba(22,163,74,.86); --rarity-bg-soft:rgba(22,163,74,.22); --rarity-fg:#86efac; --rarity-fg-strong:#f0fdf4; --rarity-ring:rgba(134,239,172,.28); --rarity-line:#00a113; }
  .brainrot-rarity-rare { --rarity-bg:rgba(37,99,235,.9); --rarity-bg-soft:rgba(37,99,235,.23); --rarity-fg:#93c5fd; --rarity-fg-strong:#eff6ff; --rarity-ring:rgba(147,197,253,.3); --rarity-line:#0b63f6; }
  .brainrot-rarity-epic { --rarity-bg:rgba(147,51,234,.88); --rarity-bg-soft:rgba(147,51,234,.24); --rarity-fg:#d8b4fe; --rarity-fg-strong:#faf5ff; --rarity-ring:rgba(216,180,254,.3); --rarity-line:#a119aa; }
  .brainrot-rarity-legendary { --rarity-bg:rgba(234,179,8,.9); --rarity-bg-soft:rgba(234,179,8,.24); --rarity-fg:#fde68a; --rarity-fg-strong:#18181b; --rarity-ring:rgba(253,230,138,.3); --rarity-line:#fff200; }
  .brainrot-rarity-mythic { --rarity-bg:rgba(239,68,68,.88); --rarity-bg-soft:rgba(239,68,68,.23); --rarity-fg:#fca5a5; --rarity-fg-strong:#fff1f2; --rarity-ring:rgba(252,165,165,.3); --rarity-line:#ff4d4d; }
  .brainrot-rarity-brainrot-god { --rarity-bg:rgba(217,70,239,.9); --rarity-bg-soft:rgba(217,70,239,.23); --rarity-fg:#f5d0fe; --rarity-fg-strong:#fdf4ff; --rarity-ring:rgba(245,208,254,.32); --rarity-line:#ff00d4; }
  .brainrot-rarity-secret { --rarity-bg:rgba(226,232,240,.9); --rarity-bg-soft:rgba(226,232,240,.18); --rarity-fg:#e2e8f0; --rarity-fg-strong:#0f172a; --rarity-ring:rgba(226,232,240,.32); --rarity-line:#f8fafc; }
  .brainrot-rarity-og { --rarity-bg:rgba(250,204,21,.96); --rarity-bg-soft:rgba(250,204,21,.18); --rarity-fg:#fef08a; --rarity-fg-strong:#111827; --rarity-ring:rgba(250,204,21,.34); --rarity-line:#fff200; }
  .brainrot-rarity-default { --rarity-bg:rgba(71,85,105,.86); --rarity-bg-soft:rgba(71,85,105,.24); --rarity-fg:#cbd5e1; --rarity-fg-strong:#f8fafc; --rarity-ring:rgba(148,163,184,.22); --rarity-line:#64748b; }
  .sab-wiki-tool[class*="brainrot-rarity-"],
  .sab-wiki-rarity[class*="brainrot-rarity-"] { border-color:var(--rarity-ring); background:var(--rarity-bg-soft); color:var(--rarity-fg); }
  .sab-wiki-tool[class*="brainrot-rarity-"]:hover,
  .sab-wiki-tool[class*="brainrot-rarity-"].is-active,
  .sab-wiki-rarity[class*="brainrot-rarity-"].is-active { border-color:var(--rarity-line); background:var(--rarity-bg); color:var(--rarity-fg-strong); }
  .sab-wiki-tier[class*="brainrot-rarity-"] { background:var(--rarity-bg); color:var(--rarity-fg-strong); }
  .sab-wiki-catalog { margin-top:1rem; }
  .sab-wiki-controls { position:sticky; top:4.4rem; z-index:30; display:grid; gap:.55rem; border:1px solid rgba(34,211,238,.22); border-radius:.85rem; padding:.75rem; background:rgba(2,6,23,.96); box-shadow:0 18px 50px rgba(2,6,23,.35); backdrop-filter:blur(12px); }
  .sab-wiki-search,.sab-wiki-sort { width:100%; border:1px solid rgba(148,163,184,.25); border-radius:.75rem; background:#0f172a; color:#f8fafc; padding:.7rem .8rem; font-size:.9rem; }
  .sab-wiki-search:focus,.sab-wiki-sort:focus { outline:2px solid rgba(34,211,238,.55); outline-offset:1px; }
  .sab-wiki-rarity-tabs { display:flex; gap:.45rem; overflow:auto; padding-bottom:.1rem; scrollbar-width:none; }
  .sab-wiki-rarity-tabs::-webkit-scrollbar { display:none; }
  .sab-wiki-rarity { flex:0 0 auto; border:1px solid rgba(148,163,184,.22); border-radius:999px; background:#0f172a; color:#cbd5e1; padding:.45rem .7rem; font-size:.75rem; font-weight:850; }
  .sab-wiki-rarity.is-active { border-color:#22d3ee; background:rgba(8,145,178,.22); color:#cffafe; }
  .sab-wiki-result { color:#94a3b8; font-size:.8rem; }
  .sab-wiki-section { margin-top:1rem; scroll-margin-top:8rem; }
  .sab-wiki-section-head { display:flex; align-items:flex-end; justify-content:space-between; gap:.75rem; margin-bottom:.5rem; }
  .sab-wiki-section h2 { margin:0; color:#f8fafc; font-size:1.2rem; font-weight:950; }
  .sab-wiki-section-desc { max-width:52rem; margin:.3rem 0 0; color:#94a3b8; line-height:1.6; }
  #all-rebirth-requirements .sab-wiki-section-desc,
  #all-rebirth-rewards .sab-wiki-section-desc { max-width:none; }
  .sab-wiki-section-count { flex:0 0 auto; border-radius:999px; background:rgba(14,116,144,.22); color:#a5f3fc; padding:.35rem .65rem; font-size:.75rem; font-weight:850; }
  .sab-wiki-table-wrap { overflow:hidden; border:1px solid rgba(148,163,184,.16); border-radius:1rem; background:#020617; }
  .sab-wiki-table { width:100%; border-collapse:collapse; font-size:.82rem; }
  .sab-wiki-table th { padding:.72rem .65rem; background:#0f172a; color:#94a3b8; font-size:.7rem; letter-spacing:.055em; text-align:left; text-transform:uppercase; }
  .sab-wiki-table td { border-top:1px solid rgba(148,163,184,.1); padding:.65rem; vertical-align:middle; }
  .sab-wiki-table tbody tr:hover { background:rgba(15,23,42,.72); }
  .sab-wiki-name { display:flex; align-items:center; gap:.65rem; min-width:14rem; color:#e0f2fe; font-weight:850; text-decoration:none; }
  .sab-wiki-name:hover { color:#67e8f9; }
  .sab-wiki-name-text { display:flex; align-items:center; justify-content:space-between; gap:.55rem; min-width:0; flex:1; }
  .sab-wiki-name-meta { display:none; }
  .sab-wiki-thumb,.sab-wiki-thumb-placeholder { width:4rem; height:4rem; flex:0 0 auto; border:1px solid rgba(148,163,184,.18); border-radius:.85rem; background:#0f172a; object-fit:contain; }
  .sab-wiki-thumb-placeholder { display:grid; place-items:center; color:#67e8f9; font-size:.88rem; font-weight:900; }
  .sab-wiki-tier { display:inline-flex; border-radius:999px; padding:.3rem .55rem; background:#1e1b4b; color:#c7d2fe; font-size:.72rem; font-weight:850; white-space:nowrap; }
  .sab-wiki-rarity-cell { display:inline-flex; flex-wrap:wrap; align-items:center; gap:.35rem; }
  .sab-wiki-demand-cell { display:inline-flex; align-items:center; gap:.35rem; }
  .sab-wiki-trend-icon { width:.75rem; height:.75rem; flex-shrink:0; }
  .sab-wiki-trend-icon.is-up { color:#4ade80; }
  .sab-wiki-trend-icon.is-down { color:#fb7185; }
  .sab-wiki-trend-icon.is-flat { color:#64748b; }
  .sab-wiki-value-stack { display:inline-flex; flex-direction:column; align-items:flex-start; gap:.15rem; }
  .sab-wiki-change { font-size:.72rem; font-weight:800; }
  .sab-wiki-change.is-up { color:#4ade80; }
  .sab-wiki-change.is-down { color:#fb7185; }
  .sab-wiki-change.is-stable { color:#94a3b8; }
  .sab-wiki-demand { display:inline-flex; border-radius:999px; padding:.3rem .55rem; font-size:.72rem; font-weight:850; letter-spacing:.04em; text-transform:uppercase; white-space:nowrap; }
  .sab-wiki-demand.is-high { background:rgba(34,197,94,.12); color:#4ade80; }
  .sab-wiki-demand.is-mid { background:rgba(148,163,184,.12); color:#cbd5e1; }
  .sab-wiki-demand.is-low { background:rgba(251,146,60,.12); color:#fdba74; }
  .sab-wiki-demand.is-bad { background:rgba(244,63,94,.12); color:#fb7185; }
  .sab-wiki-demand.is-flat { background:rgba(148,163,184,.1); color:#94a3b8; }
  .sab-wiki-value { color:#f8fafc; font-variant-numeric:tabular-nums; white-space:nowrap; }
  .sab-wiki-unknown { color:#64748b; }
  .sab-wiki-estimate { display:inline-flex; margin-left:.25rem; border-radius:.35rem; background:rgba(245,158,11,.14); color:#fcd34d; padding:.12rem .28rem; font-size:.62rem; font-weight:900; text-transform:uppercase; }
  .sab-wiki-details { color:#67e8f9; font-weight:850; text-decoration:none; white-space:nowrap; }
  .sab-wiki-hub { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.45rem; margin:.7rem 0 0; }
  .sab-wiki-hub a { display:block; border:1px solid rgba(148,163,184,.14); border-radius:.6rem; padding:.55rem .65rem; background:#020617; color:#e2e8f0; text-decoration:none; }
  .sab-wiki-hub a:hover { border-color:rgba(34,211,238,.5); color:#fff; }
  .sab-wiki-hub strong { display:block; color:#a5f3fc; font-size:.86rem; }
  .sab-wiki-hub span { display:block; margin-top:.2rem; color:#94a3b8; font-size:.75rem; }
  .sab-wiki-tools-grid { grid-template-columns:1fr; }
  .sab-wiki-tools-grid a { min-height:5.25rem; }
  .sab-wiki-card-list { display:grid; gap:.45rem; margin-top:.7rem; }
  .sab-wiki-card { display:flex; align-items:center; justify-content:space-between; gap:.65rem; border:1px solid rgba(148,163,184,.14); border-radius:.7rem; background:#020617; padding:.55rem .65rem; }
  .sab-wiki-card a { color:#e0f2fe; font-weight:850; text-decoration:none; }
  .sab-wiki-card a:hover { color:#67e8f9; }
  .sab-wiki-card-meta { color:#94a3b8; font-size:.78rem; }
  .sab-wiki-empty { margin:.75rem 0 0; color:#94a3b8; }
  .sab-wiki-faq { display:grid; gap:.5rem; margin-top:.7rem; }
  .sab-wiki-faq details { border:1px solid rgba(148,163,184,.15); border-radius:.7rem; background:#020617; padding:.65rem .75rem; }
  .sab-wiki-faq summary { cursor:pointer; color:#f8fafc; font-weight:850; }
  .sab-wiki-faq p { margin:.65rem 0 0; }
  .sab-wiki-faq a { color:#67e8f9; font-weight:800; }
  .sab-wiki-faq a:hover { color:#cffafe; }
  .sab-wiki-schedule-head { display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; }
  .sab-wiki-schedule-head p { max-width:48rem; margin:.35rem 0 0; }
  .sab-wiki-status { display:inline-flex; flex:0 0 auto; border-radius:999px; padding:.35rem .65rem; color:#ecfeff; background:rgba(8,145,178,.28); font-size:.72rem; font-weight:900; white-space:nowrap; }
  .sab-wiki-status-live { background:rgba(22,163,74,.35); color:#bbf7d0; }
  .sab-wiki-status-today { background:rgba(245,158,11,.28); color:#fde68a; }
  .sab-wiki-status-not_confirmed { background:rgba(100,116,139,.28); color:#cbd5e1; }
  .sab-wiki-schedule-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem; margin-top:.8rem; }
  .sab-wiki-schedule-card { border:1px solid rgba(34,211,238,.2); border-radius:.8rem; background:#020617; padding:.8rem; }
  .sab-wiki-schedule-card h3 { margin:0; color:#a5f3fc; font-size:.9rem; font-weight:900; }
  .sab-wiki-schedule-state { margin:.45rem 0 0; color:#94a3b8; font-size:.75rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; }
  .sab-wiki-schedule-time { margin:.35rem 0 0; color:#f8fafc; font-size:1.05rem; font-weight:900; font-variant-numeric:tabular-nums; }
  .sab-wiki-schedule-local { margin:.35rem 0 0; color:#cbd5e1; font-size:.82rem; }
  .sab-wiki-schedule-local time { color:#67e8f9; font-weight:800; }
  .sab-wiki-countdown { margin:.55rem 0 0; color:#fcd34d; font-size:.9rem; font-weight:900; font-variant-numeric:tabular-nums; }
  .sab-wiki-source-note,.sab-wiki-schedule-note { margin:.7rem 0 0; color:#94a3b8; font-size:.78rem; line-height:1.5; }
  .sab-wiki-source-note a { color:#67e8f9; font-weight:800; }
  .sab-wiki-schedule-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.55rem; margin-top:.7rem; }
  .sab-wiki-schedule-list > div { padding:.7rem .75rem; border:1px solid rgba(148,163,184,.16); border-radius:.7rem; background:#020617; }
  .sab-wiki-schedule-list strong { display:block; color:#a5f3fc; }
  .sab-wiki-schedule-list span { display:block; margin-top:.25rem; color:#cbd5e1; font-size:.86rem; }
  .sab-wiki-mechanics { display:grid; gap:.45rem; margin:.7rem 0 0; padding-left:1.15rem; }
  .sab-wiki-mechanics li { color:#cbd5e1; }
  .sab-wiki-hidden { display:none!important; }
  .sab-wiki-crumbs { display:flex; flex-wrap:wrap; gap:.35rem; margin:0 0 .7rem; padding:0; list-style:none; color:#94a3b8; font-size:.78rem; }
  .sab-wiki-crumbs a { color:#a5f3fc; text-decoration:none; }
  .sab-wiki-crumbs a:hover { color:#fff; }
  .sab-wiki-rebirth-quick { scroll-margin-top:6rem; }
  .sab-wiki-rebirth-quick p,.sab-wiki-rebirth-group p,.sab-wiki-rebirth-source p { color:#cbd5e1; line-height:1.6; }
  .sab-wiki-rebirth-note { margin:.75rem 0 0; padding:.7rem .8rem; background:#020617; border-left:3px solid #f59e0b; }
  .sab-wiki-rebirth-note strong { display:block; color:#fde68a; }
  .sab-wiki-rebirth-note p { margin:.3rem 0 0; }
  .sab-wiki-rebirth-level { display:inline-flex; min-width:1.7rem; color:#ecfeff; font-size:1.05rem; font-weight:950; font-variant-numeric:tabular-nums; letter-spacing:-.02em; }
  .sab-wiki-rebirth-tag { display:inline-block; margin-left:.4rem; padding:.15rem .45rem; border-radius:999px; font-size:.68rem; font-weight:850; }
  .sab-wiki-rebirth-tag-floor { background:rgba(8,145,178,.22); color:#a5f3fc; }
  .sab-wiki-rebirth-tag-secret { background:rgba(226,232,240,.16); color:#e2e8f0; }
  .sab-wiki-rebirth-tag-max { background:rgba(250,204,21,.18); color:#fde68a; }
  .sab-wiki-rebirth-tag-default { background:rgba(71,85,105,.28); color:#cbd5e1; }
  .sab-wiki-rebirth-gain { color:#86efac; }
  .sab-wiki-rebirth-rarities { display:flex; flex-wrap:wrap; gap:.3rem; }
  .sab-wiki-rebirth-split { margin-top:.75rem; }
  .sab-wiki-rebirth-when { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.55rem; margin:.75rem 0 0; }
  .sab-wiki-rebirth-when > div { padding:.7rem .8rem; border:1px solid rgba(148,163,184,.16); border-radius:.7rem; background:#020617; }
  .sab-wiki-rebirth-when strong { display:block; color:#a5f3fc; margin-bottom:.2rem; }
  .sab-wiki-rebirth-when span { color:#94a3b8; font-size:.86rem; line-height:1.5; }
  .sab-wiki-rebirth-figure { margin:.85rem 0 0; }
  .sab-wiki-rebirth-figure img { display:block; width:100%; height:auto; border:1px solid rgba(148,163,184,.16); background:#020617; }
  .sab-wiki-rebirth-figure figcaption { margin:.45rem 0 0; color:#94a3b8; font-size:.78rem; }
  .sab-wiki-rebirth-steps { display:grid; gap:.55rem; margin:.75rem 0 0; }
  .sab-wiki-rebirth-steps > div { padding:.65rem .75rem; background:#020617; border-left:3px solid #22d3ee; }
  .sab-wiki-rebirth-steps strong { display:block; color:#f8fafc; }
  .sab-wiki-rebirth-steps span { display:block; margin-top:.2rem; color:#94a3b8; font-size:.88rem; }
  .sab-wiki-rebirth-milestones { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.55rem; margin:.75rem 0 0; }
  .sab-wiki-rebirth-milestones > div { padding:.7rem .8rem; border:1px solid rgba(148,163,184,.16); border-radius:.7rem; background:#020617; }
  .sab-wiki-rebirth-milestones strong { display:block; color:#a5f3fc; margin-bottom:.2rem; }
  .sab-wiki-rebirth-milestones span { color:#94a3b8; font-size:.86rem; }
  .sab-wiki-rebirth-tools { display:flex; gap:1rem; align-items:center; justify-content:space-between; margin:.85rem 0 0; padding:.85rem .9rem; border:1px solid rgba(34,211,238,.28); background:#020617; }
  .sab-wiki-rebirth-tools p { margin:.3rem 0 0; color:#94a3b8; }
  .sab-wiki-rebirth-button { display:inline-block; white-space:nowrap; padding:.55rem .9rem; background:#0891b2; color:#ecfeff; font-weight:850; text-decoration:none; }
  .sab-wiki-rebirth-button:hover { background:#0e7490; color:#fff; }
  .sab-wiki-rebirth-source { margin:.75rem 0 0; padding:.7rem .8rem; background:#020617; border-left:3px solid #22d3ee; }
  .sab-wiki-rebirth-source strong { display:block; color:#a5f3fc; }
  .sab-wiki-rebirth-item { color:#67e8f9; font-weight:800; text-decoration:none; }
  .sab-wiki-rebirth-item:hover { color:#cffafe; text-decoration:underline; }
  .sab-wiki-rebirth-source a,.sab-wiki-panel#what-does-rebirth-do a,.sab-wiki-panel#what-do-you-lose a,.sab-wiki-panel#rebirth-tips a,.sab-wiki-panel#rebirth-19 a, #all-rebirth-requirements .sab-wiki-section-desc a,
  .sab-wiki-panel#saturday-vs-taco-tuesday a,.sab-wiki-panel#saturday-updates a,.sab-wiki-panel#how-to-join-admin-abuse a,.sab-wiki-panel#after-admin-abuse a,.sab-wiki-panel#what-is-admin-abuse a { color:#67e8f9; font-weight:800; }
  .sab-wiki-rebirth-source a:hover,.sab-wiki-panel#what-does-rebirth-do a:hover,.sab-wiki-panel#what-do-you-lose a:hover,.sab-wiki-panel#rebirth-tips a:hover,.sab-wiki-panel#rebirth-19 a:hover, #all-rebirth-requirements .sab-wiki-section-desc a:hover,
  .sab-wiki-panel#saturday-vs-taco-tuesday a:hover,.sab-wiki-panel#how-to-join-admin-abuse a:hover,.sab-wiki-panel#after-admin-abuse a:hover { color:#cffafe; }
  .sab-wiki-rebirth-group,.sab-wiki-rebirth-table-wrap, #all-rebirth-requirements, #all-rebirth-rewards, #how-to-rebirth, #what-do-you-lose, #best-time-to-rebirth, #rebirth-tips, #rebirth-milestones, #rebirth-19,
  #admin-abuse-quick, #what-is-admin-abuse, #admin-abuse-status, #admin-abuse-timezones, #saturday-vs-taco-tuesday, #saturday-updates, #what-happens-admin-abuse, #how-to-join-admin-abuse, #after-admin-abuse, #admin-abuse-rewards { scroll-margin-top:6rem; }
  .sab-wiki-timezone-wrap { margin-top:.75rem; }
  .sab-wiki-timezone-table time { color:#e2e8f0; font-variant-numeric:tabular-nums; }
  .sab-wiki-admin-compare { margin-top:.75rem; }
  .sab-wiki-admin-example { margin:.75rem 0 0; color:#cbd5e1; line-height:1.6; }
  @media (min-width:760px) {
    .sab-wiki-copy-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .sab-wiki-toc,.sab-wiki-hub { grid-template-columns:repeat(4,minmax(0,1fr)); }
    .sab-wiki-tools-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
    .sab-wiki-card-list { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .sab-wiki-controls { grid-template-columns:minmax(16rem,1fr) minmax(12rem,.45fr); }
    .sab-wiki-rarity-tabs,.sab-wiki-result { grid-column:1/-1; }
  }
  @media (max-width:759px) {
    .sab-wiki-title { font-size:1.4rem; }
    .sab-wiki-lead { margin-top:.4rem; font-size:.9rem; line-height:1.5; }
    .sab-wiki-meta { margin-top:.4rem; }
    .sab-wiki-panel { margin-top:.75rem; padding:.75rem; }
    .sab-wiki-toc a,.sab-wiki-hub a { padding:.45rem .55rem; }
    .sab-wiki-card { padding:.5rem .55rem; }
    .sab-wiki-faq details { padding:.55rem .65rem; }
    .sab-wiki-definition-grid { grid-template-columns:1fr; }
    .sab-wiki-schedule-head { display:block; }
    .sab-wiki-status { margin-top:.65rem; }
    .sab-wiki-schedule-grid,.sab-wiki-schedule-list { grid-template-columns:1fr; }
    .sab-wiki-controls { top:3.85rem; }
    .sab-wiki-table-wrap { overflow:visible; border:0; background:transparent; }
    .sab-wiki-table { display:block; width:100%; }
    .sab-wiki-table thead { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); }
    .sab-wiki-table tbody { display:grid; gap:.65rem; }
    .sab-wiki-table tr { position:relative; display:grid; grid-template-columns:minmax(0,1.55fr) minmax(0,1fr) minmax(0,1.15fr); column-gap:.55rem; row-gap:.7rem; width:100%; border:1px solid rgba(148,163,184,.16); border-radius:.9rem; background:#020617; padding:.75rem .8rem .7rem; cursor:pointer; }
    .sab-wiki-table td { min-width:0; border:0; padding:0; }
    .sab-wiki-table td::before { content:attr(data-label); display:block; color:#64748b; font-size:.62rem; font-weight:850; letter-spacing:.04em; text-transform:uppercase; }
    .sab-wiki-table td:first-child { grid-column:1 / 3; grid-row:1; z-index:1; }
    .sab-wiki-table td:first-child::before { display:none; }
    .sab-wiki-table td[data-label="Rarity"],
    .sab-wiki-table td[data-label="Details"] { display:none; }
    .sab-wiki-table td[data-label="Trade Value"] { grid-column:3; grid-row:1; display:flex; flex-direction:column; align-items:flex-end; justify-content:center; color:#f8fafc; font-size:1.02rem; font-weight:900; line-height:1.15; text-align:right; }
    .sab-wiki-table td[data-label="Trade Value"]::before { display:none; }
    .sab-wiki-value-stack { align-items:flex-end; }
    .sab-wiki-table td[data-label="Demand"] { grid-column:1; grid-row:2; padding-top:.6rem; border-top:1px solid rgba(148,163,184,.1); }
    .sab-wiki-table td[data-label="Income"] { grid-column:2; grid-row:2; padding-top:.6rem; border-top:1px solid rgba(148,163,184,.1); }
    .sab-wiki-table td[data-label="Exist Count"] { grid-column:3; grid-row:2; padding-top:.6rem; border-top:1px solid rgba(148,163,184,.1); white-space:normal; }
    .sab-wiki-table td[data-label="Obtain"] { grid-column:1 / -1; grid-row:3; }
    .sab-wiki-table td.sab-wiki-empty { display:none; }
    .sab-wiki-name { min-width:0; width:100%; align-items:center; text-align:left; }
    .sab-wiki-name::after { content:""; position:absolute; inset:0; }
    .sab-wiki-name-text { flex-direction:column; align-items:flex-start; justify-content:center; gap:.28rem; }
    .sab-wiki-name-meta { display:inline-flex; align-items:center; gap:.28rem; }
    .sab-wiki-thumb,.sab-wiki-thumb-placeholder { width:3.25rem; height:3.25rem; }
    .sab-wiki-tier,.sab-wiki-demand { padding:.22rem .45rem; font-size:.66rem; }
    .sab-wiki-value { display:block; margin-top:.2rem; white-space:normal; }
    .sab-wiki-rebirth-milestones,.sab-wiki-rebirth-when { grid-template-columns:1fr; }
    .sab-wiki-rebirth-tools { display:block; }
    .sab-wiki-rebirth-button { margin-top:.75rem; }
    .sab-wiki-rebirth-table-wrap,.sab-wiki-timezone-wrap { overflow-x:auto; }
  }
</style>
