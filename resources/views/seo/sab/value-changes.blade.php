@extends('seo.sab.layout')

@section('content')
@php
  $changes = collect($changes ?? [])->values();
  $topLosers = collect($topLosers ?? [])->values();
  $topGainers = collect($topGainers ?? [])->values();
  $activeDays = (int) ($days ?? 7);
  $activeDirection = $direction ?? null;
  $activeSort = $sort ?? 'recent';
@endphp

<style>
  .sab-vc-panel {
    border: 1px solid rgba(255,255,255,.1);
    border-radius: .875rem;
    background: rgba(15,23,42,.55);
    overflow: hidden;
  }
  .sab-vc-panel h2 {
    margin: 0;
    padding: .85rem 1rem;
    border-bottom: 1px solid rgba(255,255,255,.08);
    font-size: .95rem;
    font-weight: 800;
    color: #e2e8f0;
  }
  .sab-vc-top-row {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .65rem 1rem;
    border-bottom: 1px solid rgba(255,255,255,.06);
    text-decoration: none;
    color: inherit;
    transition: background .15s ease;
  }
  .sab-vc-top-row:last-child { border-bottom: 0; }
  .sab-vc-top-row:hover { background: rgba(255,255,255,.03); }
  .sab-vc-top-thumb {
    width: 48px;
    height: 48px;
    border-radius: .5rem;
    object-fit: cover;
    background: rgba(30,41,59,.8);
    flex-shrink: 0;
  }
  .sab-vc-top-meta {
    flex: 1;
    min-width: 0;
  }
  .sab-vc-top-name {
    font-size: .875rem;
    font-weight: 700;
    color: #f1f5f9;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .sab-vc-top-values {
    font-size: .75rem;
    color: #94a3b8;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  }
  .sab-vc-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 4.5rem;
    padding: .25rem .55rem;
    border-radius: 9999px;
    font-size: .72rem;
    font-weight: 800;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    flex-shrink: 0;
  }
  .sab-vc-pill.is-up {
    background: rgba(34,197,94,.15);
    color: #4ade80;
    border: 1px solid rgba(74,222,128,.25);
  }
  .sab-vc-pill.is-down {
    background: rgba(244,63,94,.12);
    color: #fb7185;
    border: 1px solid rgba(251,113,133,.25);
  }
  .sab-vc-seg {
    display: inline-flex;
    border: 1px solid rgba(148,163,184,.28);
    border-radius: .5rem;
    background: rgba(15,23,42,.72);
    overflow: hidden;
  }
  .sab-vc-seg button {
    display: inline-flex;
    align-items: center;
    padding: .42rem .75rem;
    color: #94a3b8;
    font-size: .78rem;
    font-weight: 700;
    text-decoration: none;
    border: 0;
    border-right: 1px solid rgba(148,163,184,.2);
    background: transparent;
    cursor: pointer;
  }
  .sab-vc-seg button:last-child { border-right: 0; }
  .sab-vc-seg button.is-active {
    background: rgba(8,145,178,.22);
    color: #67e8f9;
  }
  .sab-vc-search {
    width: 100%;
    max-width: 28rem;
    border: 1px solid rgba(148,163,184,.28);
    border-radius: .5rem;
    background: rgba(15,23,42,.72);
    padding: .55rem .85rem;
    color: #e2e8f0;
    font-size: .875rem;
  }
  .sab-vc-search::placeholder { color: #64748b; }
  .sab-vc-search:focus {
    outline: none;
    border-color: rgba(34,211,238,.55);
    box-shadow: 0 0 0 2px rgba(34,211,238,.12);
  }
  .sab-vc-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: .85rem 1rem;
    border: 1px solid rgba(255,255,255,.08);
    border-radius: .875rem;
    background: rgba(15,23,42,.55);
    text-decoration: none;
    color: inherit;
    transition: border-color .15s ease, background .15s ease;
  }
  .sab-vc-card:hover {
    border-color: rgba(34,211,238,.35);
    background: rgba(15,23,42,.72);
  }
  .sab-vc-card-thumb {
    width: 64px;
    height: 64px;
    border-radius: .625rem;
    object-fit: cover;
    background: rgba(30,41,59,.8);
    flex-shrink: 0;
  }
  .sab-vc-card-body {
    flex: 1;
    min-width: 0;
  }
  .sab-vc-card-name {
    font-size: .95rem;
    font-weight: 700;
    color: #f1f5f9;
    margin-bottom: .2rem;
  }
  .sab-vc-card-line {
    font-size: .78rem;
    color: #94a3b8;
    line-height: 1.45;
  }
  .sab-vc-card-line .was {
    text-decoration: line-through;
    color: #64748b;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  }
  .sab-vc-card-line .now {
    color: #e2e8f0;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 600;
  }
  .sab-vc-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 5.5rem;
    padding: .45rem .75rem;
    border-radius: .625rem;
    font-size: .95rem;
    font-weight: 800;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    flex-shrink: 0;
  }
  .sab-vc-badge.is-up {
    background: rgba(34,197,94,.14);
    color: #4ade80;
    border: 1px solid rgba(74,222,128,.28);
  }
  .sab-vc-badge.is-down {
    background: rgba(244,63,94,.12);
    color: #fb7185;
    border: 1px solid rgba(251,113,133,.28);
  }
  .sab-vc-badge.is-flat {
    background: rgba(148,163,184,.12);
    color: #cbd5e1;
    border: 1px solid rgba(148,163,184,.22);
  }
</style>

<header class="-mx-4 px-4 py-5 border-b border-white/10">
  <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-100">
    {{ $t['value_changes_h1'] ?? 'Steal a Brainrot Value Changes' }}
  </h1>
  <p class="text-sm text-slate-400 mt-2 max-w-3xl">
    {{ $t['value_changes_intro'] ?? "Track real-time value changes for every Steal a Brainrot item. See what's rising, what's falling, and the biggest movers today." }}
    {{ $t['value_changes_updated_ago'] ?? 'Updated' }} {{ $updatedAgoLabel ?? 'not yet published' }}.
  </p>
</header>

<section class="mt-8 grid gap-6 lg:grid-cols-2">
  <div class="sab-vc-panel">
    <h2>{{ $t['value_changes_top_gainers'] ?? 'Top Gainers' }}</h2>
    <div id="brainrot-change-top-gainers">
      @forelse($topGainers->take(5) as $change)
      @php
        $delta = (float) ($change['delta'] ?? 0);
        $pillClass = $delta >= 0 ? 'is-up' : 'is-down';
      @endphp
      <a href="{{ $change['productUrl'] ?? '#' }}" class="sab-vc-top-row">
        @if(!empty($change['imageSrc']))
          <img src="{{ $change['imageSrc'] }}" alt="" class="sab-vc-top-thumb" loading="lazy" width="48" height="48">
        @else
          <span class="sab-vc-top-thumb" aria-hidden="true"></span>
        @endif
        <span class="sab-vc-top-meta">
          <span class="sab-vc-top-name">{{ $change['itemName'] ?? '—' }}</span>
          <span class="sab-vc-top-values">{{ $change['before'] ?? '—' }} → {{ $change['after'] ?? '—' }}</span>
        </span>
        <span class="sab-vc-pill {{ $pillClass }}">{{ $change['deltaPctLabel'] ?? '—' }}</span>
      </a>
      @empty
      <p class="text-slate-500 text-sm py-4 px-4">No gainers in this window yet.</p>
      @endforelse
    </div>
  </div>

  <div class="sab-vc-panel">
    <h2>{{ $t['value_changes_top_losers'] ?? 'Top Losers' }}</h2>
    <div id="brainrot-change-top-losers">
      @forelse($topLosers->take(5) as $change)
      @php
        $delta = (float) ($change['delta'] ?? 0);
        $pillClass = $delta >= 0 ? 'is-up' : 'is-down';
      @endphp
      <a href="{{ $change['productUrl'] ?? '#' }}" class="sab-vc-top-row">
        @if(!empty($change['imageSrc']))
          <img src="{{ $change['imageSrc'] }}" alt="" class="sab-vc-top-thumb" loading="lazy" width="48" height="48">
        @else
          <span class="sab-vc-top-thumb" aria-hidden="true"></span>
        @endif
        <span class="sab-vc-top-meta">
          <span class="sab-vc-top-name">{{ $change['itemName'] ?? '—' }}</span>
          <span class="sab-vc-top-values">{{ $change['before'] ?? '—' }} → {{ $change['after'] ?? '—' }}</span>
        </span>
        <span class="sab-vc-pill {{ $pillClass }}">{{ $change['deltaPctLabel'] ?? '—' }}</span>
      </a>
      @empty
      <p class="text-slate-500 text-sm py-4 px-4">No losers in this window yet.</p>
      @endforelse
    </div>
  </div>
</section>

<section class="mt-10 space-y-4">
  <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <input
      id="brainrot-change-search"
      type="search"
      class="sab-vc-search"
      placeholder="Search brainrots..."
      autocomplete="off"
      aria-label="Search brainrots"
    >
    <div class="flex flex-wrap gap-3">
      <div class="sab-vc-seg" role="group" aria-label="Days filter">
        @foreach([7, 14, 30] as $dayOption)
          <button type="button" data-vc-days="{{ $dayOption }}" @class(['is-active' => $activeDays === $dayOption])>{{ $dayOption }}d</button>
        @endforeach
      </div>
      <div class="sab-vc-seg" role="group" aria-label="Direction filter">
        @foreach(['all' => null, 'up' => 'Up', 'down' => 'Down'] as $value => $label)
          <button type="button" data-vc-direction="{{ $value }}" @class(['is-active' => $activeDirection === ($value === 'all' ? null : $value)])>{{ $label }}</button>
        @endforeach
      </div>
      <div class="sab-vc-seg" role="group" aria-label="Sort filter">
        @foreach(['recent' => 'Recent', 'biggest' => 'Biggest'] as $sortKey => $sortLabel)
          <button type="button" data-vc-sort="{{ $sortKey }}" @class(['is-active' => $activeSort === $sortKey])>{{ $sortLabel }}</button>
        @endforeach
      </div>
    </div>
  </div>

  <p class="text-sm text-slate-400">
    <span id="brainrot-change-count" class="text-cyan-400 font-semibold">{{ number_format($changeCount ?? 0) }}</span>
    updates in last <span id="brainrot-change-days-label">{{ $activeDays }}</span> days
    <span id="brainrot-change-search-count" class="text-slate-500"></span>
  </p>
</section>

<section class="mt-6 mb-16">
  <div id="brainrot-change-list" class="space-y-3">
    @forelse($changes as $change)
    @php
      $delta = (float) ($change['delta'] ?? 0);
      $badgeClass = $delta > 0 ? 'is-up' : ($delta < 0 ? 'is-down' : 'is-flat');
      $searchBlob = strtolower(trim(($change['itemName'] ?? '') . ' ' . ($change['itemSlug'] ?? '')));
    @endphp
    <a
      href="{{ $change['productUrl'] ?? '#' }}"
      class="sab-vc-card"
      data-change-card
      data-search="{{ $searchBlob }}"
    >
      @if(!empty($change['imageSrc']))
        <img src="{{ $change['imageSrc'] }}" alt="" class="sab-vc-card-thumb" loading="lazy" width="64" height="64">
      @else
        <span class="sab-vc-card-thumb" aria-hidden="true"></span>
      @endif
      <span class="sab-vc-card-body">
        <span class="sab-vc-card-name">{{ $change['itemName'] ?? '—' }}</span>
        <span class="sab-vc-card-line">
          Value:
          <span class="was">{{ $change['before'] ?? '—' }}</span>
          →
          <span class="now">{{ $change['after'] ?? '—' }}</span>
        </span>
        <span class="sab-vc-card-line">
          Demand: {{ $change['demandLabel'] ?? '—' }}
          · {{ $change['observedAgo'] ?? '—' }}
        </span>
      </span>
      <span class="sab-vc-badge {{ $badgeClass }}">{{ $change['deltaPctLabel'] ?? '—' }}</span>
    </a>
    @empty
    <div class="rounded-xl border border-white/10 bg-slate-900/60 py-10 px-4 text-center text-slate-500">
      <strong class="block text-slate-300 mb-1">No value changes yet.</strong>
      <span class="text-sm">The first sync is treated as the baseline. Future rot.rocks calculator updates will appear here.</span>
    </div>
    @endforelse
  </div>

  <p id="brainrot-change-search-empty" class="hidden mt-6 rounded-xl border border-white/10 bg-slate-900/60 py-8 px-4 text-center text-slate-500">
    No brainrots match your search.
  </p>
</section>

<section id="faq" class="mb-16 scroll-mt-20 mt-10">
  <h2 class="text-xl font-black text-slate-100 mb-4">Frequently Asked Questions</h2>
  <div class="grid gap-4 md:grid-cols-2">
    @foreach($valueChangesFaqItems ?? [] as $faqItem)
      <article class="rounded-lg border border-white/10 bg-slate-900/60 p-4">
        <h3 class="font-bold text-slate-100">{{ $faqItem['question'] }}</h3>
        <p class="text-slate-400 text-sm mt-2 leading-relaxed">{!! $faqItem['answer'] !!}</p>
      </article>
    @endforeach
  </div>
</section>
@endsection

@section('scripts')
<script type="application/json" id="value-changes-data">@json($valueChangesClientViews ?? [])</script>
<script>
  (() => {
    const dataEl = document.getElementById('value-changes-data');
    const input = document.getElementById('brainrot-change-search');
    const list = document.getElementById('brainrot-change-list');
    const topGainersEl = document.getElementById('brainrot-change-top-gainers');
    const topLosersEl = document.getElementById('brainrot-change-top-losers');
    const countEl = document.getElementById('brainrot-change-count');
    const daysLabelEl = document.getElementById('brainrot-change-days-label');
    const searchCountEl = document.getElementById('brainrot-change-search-count');
    const searchEmptyEl = document.getElementById('brainrot-change-search-empty');
    if (!dataEl || !list) return;

    let payload;
    try {
      payload = JSON.parse(dataEl.textContent || '{}');
    } catch {
      return;
    }

    const allowedDays = new Set(['7', '14', '30']);
    const allowedDirections = new Set(['all', 'up', 'down']);
    const allowedSorts = new Set(['recent', 'biggest']);
    const defaultState = payload.default || { days: 7, direction: null, sort: 'recent' };

    const state = {
      days: String(defaultState.days ?? 7),
      direction: defaultState.direction ?? null,
      sort: String(defaultState.sort ?? 'recent'),
    };

    const esc = (value) => String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/"/g, '&quot;');

    const normalize = (value) => String(value ?? '').toLowerCase().replace(/\s+/g, ' ').trim();

    const directionKey = () => (state.direction ? state.direction : 'all');

    const resolveView = () => payload.views?.[state.days]?.[directionKey()]?.[state.sort] ?? null;

    const renderTopRows = (rows, emptyText) => {
      const items = (rows || []).slice(0, 5);
      if (!items.length) {
        return `<p class="text-slate-500 text-sm py-4 px-4">${esc(emptyText)}</p>`;
      }
      return items.map((change) => {
        const delta = Number(change.delta ?? 0);
        const pillClass = delta >= 0 ? 'is-up' : 'is-down';
        const thumb = change.imageSrc
          ? `<img src="${esc(change.imageSrc)}" alt="" class="sab-vc-top-thumb" loading="lazy" width="48" height="48">`
          : '<span class="sab-vc-top-thumb" aria-hidden="true"></span>';
        return `<a href="${esc(change.productUrl || '#')}" class="sab-vc-top-row">${thumb}<span class="sab-vc-top-meta"><span class="sab-vc-top-name">${esc(change.itemName || '—')}</span><span class="sab-vc-top-values">${esc(change.before || '—')} → ${esc(change.after || '—')}</span></span><span class="sab-vc-pill ${pillClass}">${esc(change.deltaPctLabel || '—')}</span></a>`;
      }).join('');
    };

    const renderChangeCard = (change) => {
      const delta = Number(change.delta ?? 0);
      const badgeClass = delta > 0 ? 'is-up' : (delta < 0 ? 'is-down' : 'is-flat');
      const searchBlob = normalize(`${change.itemName || ''} ${change.itemSlug || ''}`);
      const thumb = change.imageSrc
        ? `<img src="${esc(change.imageSrc)}" alt="" class="sab-vc-card-thumb" loading="lazy" width="64" height="64">`
        : '<span class="sab-vc-card-thumb" aria-hidden="true"></span>';
      return `<a href="${esc(change.productUrl || '#')}" class="sab-vc-card" data-change-card data-search="${esc(searchBlob)}">${thumb}<span class="sab-vc-card-body"><span class="sab-vc-card-name">${esc(change.itemName || '—')}</span><span class="sab-vc-card-line">Value: <span class="was">${esc(change.before || '—')}</span> → <span class="now">${esc(change.after || '—')}</span></span><span class="sab-vc-card-line">Demand: ${esc(change.demandLabel || '—')} · ${esc(change.observedAgo || '—')}</span></span><span class="sab-vc-badge ${badgeClass}">${esc(change.deltaPctLabel || '—')}</span></a>`;
    };

    const renderMainList = (changes) => {
      if (!changes || !changes.length) {
        return '<div class="rounded-xl border border-white/10 bg-slate-900/60 py-10 px-4 text-center text-slate-500"><strong class="block text-slate-300 mb-1">No value changes yet.</strong><span class="text-sm">The first sync is treated as the baseline. Future rot.rocks calculator updates will appear here.</span></div>';
      }
      return changes.map(renderChangeCard).join('');
    };

    const syncFilterButtons = () => {
      document.querySelectorAll('[data-vc-days]').forEach((button) => {
        button.classList.toggle('is-active', button.dataset.vcDays === state.days);
      });
      document.querySelectorAll('[data-vc-direction]').forEach((button) => {
        const value = button.dataset.vcDirection === 'all' ? null : button.dataset.vcDirection;
        button.classList.toggle('is-active', value === state.direction);
      });
      document.querySelectorAll('[data-vc-sort]').forEach((button) => {
        button.classList.toggle('is-active', button.dataset.vcSort === state.sort);
      });
    };

    const syncUrl = () => {
      const params = new URLSearchParams();
      params.set('days', state.days);
      if (state.direction) params.set('direction', state.direction);
      if (state.sort !== 'recent') params.set('sort', state.sort);
      const query = params.toString();
      const nextUrl = query ? `${window.location.pathname}?${query}` : window.location.pathname;
      window.history.replaceState(null, '', nextUrl);
    };

    let cards = [];

    const applySearch = () => {
      if (!input) return;
      const q = normalize(input.value);
      let visible = 0;
      cards.forEach((card) => {
        const match = !q || normalize(card.dataset.search || '').includes(q);
        card.classList.toggle('hidden', !match);
        if (match) visible++;
      });
      if (searchCountEl) {
        searchCountEl.textContent = q ? ` · showing ${visible} of ${cards.length}` : '';
      }
      if (searchEmptyEl) {
        searchEmptyEl.classList.toggle('hidden', visible !== 0 || cards.length === 0);
      }
    };

    const applyView = () => {
      const view = resolveView();
      if (!view) return;

      if (topGainersEl) {
        topGainersEl.innerHTML = renderTopRows(view.topGainers, 'No gainers in this window yet.');
      }
      if (topLosersEl) {
        topLosersEl.innerHTML = renderTopRows(view.topLosers, 'No losers in this window yet.');
      }
      list.innerHTML = renderMainList(view.changes);
      cards = Array.from(list.querySelectorAll('[data-change-card]'));

      if (countEl) {
        countEl.textContent = new Intl.NumberFormat().format(Number(view.changeCount ?? 0));
      }
      if (daysLabelEl) {
        daysLabelEl.textContent = String(view.days ?? state.days);
      }

      syncFilterButtons();
      syncUrl();
      applySearch();
    };

    const parseInitialState = () => {
      const params = new URLSearchParams(window.location.search);
      const days = params.get('days');
      const direction = params.get('direction');
      const sort = params.get('sort');

      if (days && allowedDays.has(days)) state.days = days;
      if (direction === 'up' || direction === 'down') {
        state.direction = direction;
      } else if (direction === 'all' || direction === null || direction === '') {
        state.direction = null;
      }
      if (sort && allowedSorts.has(sort)) state.sort = sort;
    };

    document.querySelectorAll('[data-vc-days]').forEach((button) => {
      button.addEventListener('click', () => {
        const days = button.dataset.vcDays;
        if (!days || !allowedDays.has(days)) return;
        state.days = days;
        applyView();
      });
    });

    document.querySelectorAll('[data-vc-direction]').forEach((button) => {
      button.addEventListener('click', () => {
        const value = button.dataset.vcDirection;
        if (!value || !allowedDirections.has(value)) return;
        state.direction = value === 'all' ? null : value;
        applyView();
      });
    });

    document.querySelectorAll('[data-vc-sort]').forEach((button) => {
      button.addEventListener('click', () => {
        const sort = button.dataset.vcSort;
        if (!sort || !allowedSorts.has(sort)) return;
        state.sort = sort;
        applyView();
      });
    });

    if (input) {
      input.addEventListener('input', applySearch);
    }

    cards = Array.from(list.querySelectorAll('[data-change-card]'));
    parseInitialState();
    const initialView = resolveView();
    const defaultView = payload.views?.[String(defaultState.days ?? 7)]?.[defaultState.direction ? defaultState.direction : 'all']?.[String(defaultState.sort ?? 'recent')];
    const needsRender = initialView && defaultView && (
      state.days !== String(defaultState.days ?? 7)
      || state.direction !== (defaultState.direction ?? null)
      || state.sort !== String(defaultState.sort ?? 'recent')
    );
    if (needsRender) {
      applyView();
    } else {
      syncFilterButtons();
      syncUrl();
      applySearch();
    }
  })();
</script>
@endsection
