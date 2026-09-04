@extends('seo.sab.layout-calculator')

@section('content')
@php
  $brainrots = $calculatorData['brainrots'] ?? [];
  $totalCount = count($brainrots);

  // Collect unique rarities preserving encounter order
  $rarities = [];
  foreach ($brainrots as $br) {
    $r = strtolower(trim($br['rarity'] ?? ''));
    if ($r !== '' && !in_array($r, $rarities, true)) {
      $rarities[] = $r;
    }
  }

  // Helper: rarity display label
  $rarityLabel = fn(string $r): string => match($r) {
    'brainrot god' => 'Brainrot God',
    default => ucfirst($r),
  };

  $itemSlug = fn(string $name): string => \Illuminate\Support\Str::slug($name);
  $mainSiteBase = 'https://sabexistcount.com';

  // Local detail page URLs are always extensionless (hosting rewrites *.html → /products/{slug}).
  $localProductBase   = ($urlPrefix ?? '') === '' ? '/products' : rtrim($urlPrefix, '/') . '/products';
  $localProductSuffix = '';
@endphp

<style>
/* Card is now an <a> tag — reset browser link defaults */
a.sab-br-card {
  display: block;
  text-decoration: none;
  color: inherit;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
}
a.sab-br-card:hover,
a.sab-br-card:focus-visible {
  outline: none;
}
</style>

<div class="sab-br-page">

  {{-- Toolbar --}}
  <div class="sab-br-toolbar">
    <div>
      <span class="sab-br-title">brainrot index</span>
      <span class="sab-br-count" id="brCount">{{ $totalCount }} brainrots</span>
    </div>
    <div class="sab-br-sort-tabs" role="group" aria-label="Sort by">
      <button class="sab-br-stab is-active" data-sort="income">Income</button>
      <button class="sab-br-stab" data-sort="value">Value</button>
      <button class="sab-br-stab" data-sort="cost">Cost</button>
      <button class="sab-br-stab" data-sort="az">A-Z</button>
    </div>
  </div>

  {{-- Rarity filter tabs --}}
  <div class="sab-br-rarity-tabs" role="group" aria-label="Filter by rarity">
    <button class="sab-br-rtab is-active" data-rarity="all">All</button>
    @foreach ($rarities as $rarity)
      <button class="sab-br-rtab" data-rarity="{{ $rarity }}">{{ $rarityLabel($rarity) }}</button>
    @endforeach
  </div>

  {{-- Card grid --}}
  <div class="sab-br-grid" id="brGrid"></div>

  {{-- Pagination --}}
  <div class="sab-br-pagination" id="brPagination"></div>
  <div class="sab-br-page-info" id="brPageInfo"></div>
</div>

{{-- Detail Modal --}}
<div class="sab-br-modal" id="brModal" role="dialog" aria-modal="true" aria-label="Brainrot detail">
  <div class="sab-br-dialog" id="brDialog">
    <button class="sab-br-dialog-close" id="brModalClose" aria-label="Close">&#x2715;</button>
    <div class="sab-br-dialog-img-wrap">
      <img class="sab-br-dialog-img" id="brDialogImg" src="" alt="" loading="lazy">
    </div>
    <div class="sab-br-dialog-body">
      <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
        <span class="sab-br-dialog-name" id="brDialogName"></span>
        <span class="sab-br-rarity-badge" id="brDialogRarity"></span>
      </div>

      {{-- Stats row --}}
      <div class="sab-br-dialog-stats" id="brDialogStats">
        <div class="sab-br-dialog-stat-item">
          <span class="sab-br-dialog-stat-icon">$</span>
          <div>
            <div class="sab-br-dialog-stat-label">Value</div>
            <div class="sab-br-dialog-stat-white" id="brDialogValue">—</div>
          </div>
        </div>
        <div class="sab-br-dialog-stat-item">
          <span class="sab-br-dialog-stat-icon">↗</span>
          <div>
            <div class="sab-br-dialog-stat-label">Income/s</div>
            <div class="sab-br-dialog-stat-green" id="brDialogIncome">—</div>
          </div>
        </div>
        <div class="sab-br-dialog-stat-item">
          <span class="sab-br-dialog-stat-icon">💎</span>
          <div>
            <div class="sab-br-dialog-stat-label">Mutation Value</div>
            <div class="sab-br-dialog-stat-yellow" id="brDialogMutValue">—</div>
          </div>
        </div>
      </div>

      {{-- Mutation tabs --}}
      <div class="sab-br-mutation-tabs" id="brMutTabs"></div>

      {{-- 30D Chart --}}
      <div class="sab-br-chart-wrap" id="brChartWrap">
        <div class="sab-br-chart-header">
          <span class="sab-br-chart-label">30D PRICE</span>
          <div class="sab-br-chart-meta">
            <span class="sab-br-chart-signal" id="brChartSignal"></span>
            <span class="sab-br-chart-pct" id="brChartPct"></span>
          </div>
        </div>
        <canvas class="sab-br-chart-canvas" id="brChartCanvas" width="460" height="80"></canvas>
        <div class="sab-br-chart-footer">
          <span id="brChartDays"></span>
          <span class="sab-br-chart-cur-val" id="brChartCurVal"></span>
        </div>
        <div class="sab-br-chart-empty" id="brChartEmpty" style="display:none">No price history yet</div>
      </div>

      {{-- Learn more link --}}
      <a class="sab-br-learn-more" id="brLearnMore" href="#">
        Learn more about <span id="brLearnName"></span> ↗
      </a>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
(function () {
  'use strict';

  /* ── Data ─────────────────────────────────────────── */
  const ALL_ITEMS = @json($brainrots);
  const MAIN_SITE = '{{ $mainSiteBase }}';
  const LOCAL_PRODUCT_BASE   = '{{ $localProductBase }}';
  const LOCAL_PRODUCT_SUFFIX = '{{ $localProductSuffix }}';
  const PER_PAGE = 24;

  /* ── State ────────────────────────────────────────── */
  let activeRarity = 'all';
  let activeSort   = 'income';
  let currentPage  = 1;
  let filteredList = [];

  /* ── Rarity CSS class map ─────────────────────────── */
  function rarityClass(r) {
    const m = {
      'og': 'sab-br-r-og',
      'secret': 'sab-br-r-secret',
      'mythic': 'sab-br-r-mythic',
      'legendary': 'sab-br-r-legendary',
      'epic': 'sab-br-r-epic',
      'rare': 'sab-br-r-rare',
      'common': 'sab-br-r-common',
      'brainrot god': 'sab-br-r-god',
      'admin': 'sab-br-r-admin',
    };
    return m[(r || '').toLowerCase()] || 'sab-br-r-default';
  }

  /* ── Number formatting ─────────────────────────────── */
  function fmtIncome(n) {
    if (!n && n !== 0) return '—';
    if (n >= 1e9) return '$' + (n / 1e9).toFixed(1) + 'B/s';
    if (n >= 1e6) return '$' + (n / 1e6).toFixed(1) + 'M/s';
    if (n >= 1e3) return '$' + (n / 1e3).toFixed(1) + 'K/s';
    return '$' + n.toFixed(0) + '/s';
  }

  function fmtValue(n) {
    if (!n && n !== 0) return '—';
    if (n >= 1e9) return '$' + (n / 1e9).toFixed(2) + 'B';
    if (n >= 1e6) return '$' + (n / 1e6).toFixed(2) + 'M';
    if (n >= 1e3) return '$' + (n / 1e3).toFixed(1) + 'K';
    return '$' + n.toFixed(2);
  }

  /* ── Filter + Sort ─────────────────────────────────── */
  function applyFilter() {
    filteredList = ALL_ITEMS.filter(br => {
      if (activeRarity === 'all') return true;
      return (br.rarity || '').toLowerCase() === activeRarity;
    });

    filteredList.sort((a, b) => {
      if (activeSort === 'income') return (b.baseIncome || 0) - (a.baseIncome || 0);
      if (activeSort === 'value')  return (b.robuxValue || 0) - (a.robuxValue || 0);
      if (activeSort === 'cost') {
        const ca = a.robuxCost, cb = b.robuxCost;
        if (cb == null && ca == null) return 0;
        if (cb == null) return -1;
        if (ca == null) return 1;
        return cb - ca;
      }
      return (a.name || '').localeCompare(b.name || '');
    });

    currentPage = 1;
    render();
  }

  /* ── Render grid ───────────────────────────────────── */
  function render() {
    const grid = document.getElementById('brGrid');
    const pag  = document.getElementById('brPagination');
    const info = document.getElementById('brPageInfo');
    const countEl = document.getElementById('brCount');

    const total   = filteredList.length;
    const pages   = Math.max(1, Math.ceil(total / PER_PAGE));
    currentPage   = Math.min(currentPage, pages);
    const start   = (currentPage - 1) * PER_PAGE;
    const slice   = filteredList.slice(start, start + PER_PAGE);

    countEl.textContent = total + ' brainrots';

    // Cards
    if (slice.length === 0) {
      grid.innerHTML = '<div class="sab-br-empty">No brainrots found</div>';
    } else {
      grid.innerHTML = slice.map((br, i) => cardHtml(br, start + i)).join('');
    }

    // Pagination
    renderPagination(pag, pages);
    info.textContent = total > 0
      ? `${start + 1}–${Math.min(start + PER_PAGE, total)} of ${total} results`
      : '';

    // Cards navigate to local detail page on click; hover (desktop) shows modal preview.
    // We only open the modal on hover — the grid-level mouseleave handler is intentionally
    // omitted to avoid the open/close flicker loop caused by the modal overlay itself
    // triggering mouseleave events on the grid.
    let hoverTimer = null;
    grid.querySelectorAll('.sab-br-card').forEach(el => {
      el.addEventListener('mouseenter', () => {
        clearTimeout(hoverTimer);
        hoverTimer = setTimeout(() => {
          // Closing the fullscreen overlay re-targets the card under the cursor and
          // synthesizes mouseenter — suppressHoverOpenUntil blocks that immediate reopen.
          if (Date.now() < suppressHoverOpenUntil) return;
          if (document.getElementById('brModal').classList.contains('is-open')) return;
          const br = ALL_ITEMS.find(b => b.slug === el.dataset.slug);
          if (br) openModal(br);
        }, 160);
      });
      // Cancel pending open when cursor leaves the card, but do NOT close the already-open modal.
      el.addEventListener('mouseleave', () => clearTimeout(hoverTimer));
    });
  }

  function cardHtml(br) {
    const imgTag = br.image
      ? `<img class="sab-br-img" src="${escHtml(br.image)}" alt="${escHtml(br.name)}" loading="lazy">`
      : '';
    const href = LOCAL_PRODUCT_BASE + '/' + encodeURIComponent(br.slug) + LOCAL_PRODUCT_SUFFIX;
    return `
      <a class="sab-br-card" href="${escHtml(href)}" data-slug="${escHtml(br.slug)}" title="${escHtml(br.name)}">
        <div class="sab-br-img-area">${imgTag}</div>
        <div class="sab-br-card-body">
          <div class="sab-br-name">${escHtml(br.name)}</div>
          <div class="sab-br-rarity-label ${rarityClass(br.rarity)}">${escHtml(br.rarity || '—')}</div>
          <div class="sab-br-card-stats">
            <span class="sab-br-income-val">↗ ${fmtIncome(br.baseIncome)}</span>
            <span class="sab-br-cost-val">💎 ${fmtValue(br.robuxValue)}</span>
          </div>
        </div>
      </a>`;
  }

  /* ── Pagination ─────────────────────────────────────── */
  function renderPagination(container, pages) {
    if (pages <= 1) { container.innerHTML = ''; return; }
    const MAX_VISIBLE = 7;
    let btns = [];

    if (pages <= MAX_VISIBLE) {
      for (let i = 1; i <= pages; i++) btns.push(i);
    } else {
      btns = [1];
      let lo = Math.max(2, currentPage - 2);
      let hi = Math.min(pages - 1, currentPage + 2);
      if (lo > 2) btns.push('…');
      for (let i = lo; i <= hi; i++) btns.push(i);
      if (hi < pages - 1) btns.push('…');
      btns.push(pages);
    }

    container.innerHTML = btns.map(b =>
      b === '…'
        ? `<span class="sab-br-page-btn" style="cursor:default;opacity:.4">…</span>`
        : `<button class="sab-br-page-btn${b === currentPage ? ' is-active' : ''}" data-page="${b}">${b}</button>`
    ).join('');

    container.querySelectorAll('[data-page]').forEach(btn => {
      btn.addEventListener('click', () => {
        currentPage = +btn.dataset.page;
        render();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    });
  }

  /* ── Escape HTML ─────────────────────────────────────── */
  function escHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  /* ── Modal ───────────────────────────────────────────── */
  let currentBr = null;
  let currentMutIdx = 0;
  // After close, ignore hover-open briefly so the card under the cursor cannot reopen the modal.
  let suppressHoverOpenUntil = 0;

  function openModal(br) {
    if (!br) return;
    currentBr = br;
    currentMutIdx = 0;

    document.getElementById('brDialogImg').src = br.image || '';
    document.getElementById('brDialogImg').alt = br.name;
    document.getElementById('brDialogName').textContent = br.name;

    const rEl = document.getElementById('brDialogRarity');
    rEl.textContent = br.rarity || '';
    rEl.className = 'sab-br-rarity-badge ' + rarityClass(br.rarity);

    document.getElementById('brDialogIncome').textContent = fmtIncome(br.baseIncome);
    document.getElementById('brDialogValue').textContent  = fmtValue(br.robuxValue);

    renderMutationTabs(br);
    renderChart(br);

    const learnUrl = LOCAL_PRODUCT_BASE + '/' + encodeURIComponent(br.slug || '') + LOCAL_PRODUCT_SUFFIX;
    document.getElementById('brLearnMore').href = learnUrl;
    document.getElementById('brLearnName').textContent = br.name;

    document.getElementById('brModal').classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    document.getElementById('brModal').classList.remove('is-open');
    document.body.style.overflow = '';
    suppressHoverOpenUntil = Date.now() + 400;
  }

  function renderMutationTabs(br) {
    const container = document.getElementById('brMutTabs');
    const muts = br.mutations || [];
    if (muts.length === 0) { container.innerHTML = ''; return; }

    container.innerHTML = muts.map((m, i) => {
      const imgTag = m.image ? `<img src="${escHtml(m.image)}" alt="">` : '';
      return `<button class="sab-br-mut-tab${i === 0 ? ' is-active' : ''}" data-mut-idx="${i}">${imgTag}${escHtml(m.name)}</button>`;
    }).join('');

    container.querySelectorAll('.sab-br-mut-tab').forEach(btn => {
      btn.addEventListener('click', () => {
        currentMutIdx = +btn.dataset.mutIdx;
        container.querySelectorAll('.sab-br-mut-tab').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        const mut = muts[currentMutIdx];
        const val = (mut && mut.robuxValue != null) ? mut.robuxValue : (br.robuxValue ?? null);
        document.getElementById('brDialogMutValue').textContent = fmtValue(val);
      });
    });

    // Set initial mutation value
    const first = muts[0];
    const initVal = (first && first.robuxValue != null) ? first.robuxValue : (br.robuxValue ?? null);
    document.getElementById('brDialogMutValue').textContent = fmtValue(initVal);
  }

  /* ── 30D Chart (Canvas) ─────────────────────────────── */
  function renderChart(br) {
    const canvas  = document.getElementById('brChartCanvas');
    const emptyEl = document.getElementById('brChartEmpty');
    const signalEl = document.getElementById('brChartSignal');
    const pctEl   = document.getElementById('brChartPct');
    const daysEl  = document.getElementById('brChartDays');
    const curEl   = document.getElementById('brChartCurVal');

    const history = br.priceHistory || [];

    if (history.length === 0) {
      canvas.style.display = 'none';
      emptyEl.style.display = 'block';
      signalEl.textContent = '';
      pctEl.textContent = '';
      daysEl.textContent = '';
      curEl.textContent = '';
      return;
    }

    if (history.length === 1) {
      canvas.style.display = 'none';
      emptyEl.style.display = 'none';
      signalEl.textContent = '';
      pctEl.textContent = '';
      daysEl.textContent = '1 price point · ' + fmtDate(history[0].date);
      curEl.textContent = fmtValue(history[0].value);
      return;
    }

    canvas.style.display = 'block';
    emptyEl.style.display = 'none';

    const values = history.map(p => p.value);
    const minVal = Math.min(...values);
    const maxVal = Math.max(...values);
    const firstVal = values[0];
    const lastVal  = values[values.length - 1];
    const pctChange = firstVal > 0 ? ((lastVal - firstVal) / firstVal) * 100 : 0;

    // Signal
    const isLow = lastVal <= minVal + (maxVal - minVal) * 0.2;
    const isHigh = lastVal >= minVal + (maxVal - minVal) * 0.8;
    signalEl.textContent = isLow ? '△ Low' : (isHigh ? '▲ High' : '');
    signalEl.className = 'sab-br-chart-signal ' + (isLow ? 'sab-br-chart-signal-low' : 'sab-br-chart-signal-high');
    if (!isLow && !isHigh) signalEl.className = 'sab-br-chart-signal sab-br-chart-signal-low'; // neutral fallback hidden

    const pctStr = (pctChange >= 0 ? '+' : '') + pctChange.toFixed(1) + '%';
    pctEl.textContent = pctStr;
    pctEl.className = 'sab-br-chart-pct ' + (pctChange >= 0 ? 'sab-br-chart-pct-pos' : 'sab-br-chart-pct-neg');

    // Footer
    const days = history.length;
    const firstDate = history[0].date;
    const lastDate  = history[history.length - 1].date;
    daysEl.textContent = days + ' days · ' + fmtDate(firstDate) + ' – ' + fmtDate(lastDate);
    curEl.textContent = fmtValue(lastVal);

    // Draw canvas
    const dpr = window.devicePixelRatio || 1;
    const W   = canvas.offsetWidth || 460;
    const H   = 80;
    canvas.width  = W * dpr;
    canvas.height = H * dpr;
    canvas.style.width  = W + 'px';
    canvas.style.height = H + 'px';

    const ctx2d = canvas.getContext('2d');
    ctx2d.scale(dpr, dpr);

    const padX = 8, padY = 10;
    const cW = W - padX * 2;
    const cH = H - padY * 2;
    const range = maxVal - minVal || 1;

    const pts = values.map((v, i) => ({
      x: padX + (i / (values.length - 1)) * cW,
      y: padY + (1 - (v - minVal) / range) * cH,
    }));

    // Gradient fill
    const grad = ctx2d.createLinearGradient(0, padY, 0, H);
    const lineColor = pctChange >= 0 ? '#22c55e' : '#ef4444';
    const gradColor = pctChange >= 0 ? 'rgba(34,197,94,' : 'rgba(239,68,68,';
    grad.addColorStop(0, gradColor + '0.25)');
    grad.addColorStop(1, gradColor + '0.02)');

    ctx2d.beginPath();
    ctx2d.moveTo(pts[0].x, pts[0].y);
    for (let i = 1; i < pts.length; i++) {
      const cpX = (pts[i - 1].x + pts[i].x) / 2;
      ctx2d.bezierCurveTo(cpX, pts[i - 1].y, cpX, pts[i].y, pts[i].x, pts[i].y);
    }
    ctx2d.lineTo(pts[pts.length - 1].x, H);
    ctx2d.lineTo(pts[0].x, H);
    ctx2d.closePath();
    ctx2d.fillStyle = grad;
    ctx2d.fill();

    // Line
    ctx2d.beginPath();
    ctx2d.moveTo(pts[0].x, pts[0].y);
    for (let i = 1; i < pts.length; i++) {
      const cpX = (pts[i - 1].x + pts[i].x) / 2;
      ctx2d.bezierCurveTo(cpX, pts[i - 1].y, cpX, pts[i].y, pts[i].x, pts[i].y);
    }
    ctx2d.strokeStyle = lineColor;
    ctx2d.lineWidth   = 2;
    ctx2d.stroke();

    // Min/Max dots
    const minIdx = values.indexOf(minVal);
    const maxIdx = values.indexOf(maxVal);
    [[minIdx, '#ef4444'], [maxIdx, '#22c55e']].forEach(([idx, color]) => {
      ctx2d.beginPath();
      ctx2d.arc(pts[idx].x, pts[idx].y, 3, 0, Math.PI * 2);
      ctx2d.fillStyle = color;
      ctx2d.fill();
    });
  }

  function fmtDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return (d.getMonth() + 1).toString().padStart(2, '0') + '-' + d.getDate().toString().padStart(2, '0');
  }

  /* ── Event wiring ────────────────────────────────────── */

  // Rarity tabs
  document.querySelectorAll('.sab-br-rtab').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.sab-br-rtab').forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');
      activeRarity = btn.dataset.rarity;
      applyFilter();
    });
  });

  // Sort tabs
  document.querySelectorAll('.sab-br-stab').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.sab-br-stab').forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');
      activeSort = btn.dataset.sort;
      applyFilter();
    });
  });

  // Modal close
  document.getElementById('brModalClose').addEventListener('click', closeModal);
  document.getElementById('brModal').addEventListener('click', e => {
    if (e.target === document.getElementById('brModal')) closeModal();
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

  // Initial render
  applyFilter();
})();
</script>
@endsection
