@extends('seo.sab.layout')

@section('content')
@php
  $data = $calculatorData ?? [];
  $crops = array_values($data['crops'] ?? []);
  $mutations = array_values($data['mutations'] ?? []);
  $pets = array_values($data['pets'] ?? []);
  $gears = array_values($data['gears'] ?? []);
  $bestCrops = array_values($data['best_crops'] ?? []);
  if (empty($bestCrops)) {
    $bestCrops = $crops;
    usort($bestCrops, fn ($a, $b) => ((float) ($b['base_value'] ?? 0)) <=> ((float) ($a['base_value'] ?? 0)));
    $bestCrops = array_slice($bestCrops, 0, 12);
  }
  $payload = [
    'crops' => $crops,
    'mutations' => $mutations,
    'pets' => $pets,
    'gears' => $gears,
    'best_crops' => $bestCrops,
    'growth' => $data['growth'] ?? [],
  ];
  $articleHref = ($urlPrefix ?? '') . '/news/grow-a-garden-2-calculator-values-best-crops-guide';
  $sabCalcHref = ($urlPrefix ?? '') . '/' . \App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR;
  $existHref = ($urlPrefix ?? '') . '/' . \App\Services\Seo\SabRenderService::PAGE_EXIST_COUNTS_LIST;
  $valueHref = ($urlPrefix ?? '') . '/' . \App\Services\Seo\SabRenderService::PAGE_VALUE_LIST;
@endphp

<style>
  .gag2-sab-page { display: grid; gap: 1rem; max-width: 980px; margin: 0 auto; }
  .gag2-sab-hero { display: grid; grid-template-columns: minmax(0, 1fr) 220px; gap: 1rem; align-items: stretch; border: 1px solid rgba(148,163,184,.18); background: linear-gradient(135deg, rgba(15,23,42,.94), rgba(20,83,45,.34)); border-radius: .75rem; padding: 1.1rem; }
  .gag2-sab-hero h1 { font-size: clamp(1.8rem, 3vw, 3rem); line-height: 1.02; margin: .25rem 0 .5rem; }
  .gag2-sab-kicker { color: #67e8f9; font-weight: 800; text-transform: uppercase; font-size: .72rem; letter-spacing: .08em; }
  .gag2-sab-lead { color: #cbd5e1; max-width: 760px; }
  .gag2-sab-hero-stat { border: 1px solid rgba(148,163,184,.22); background: rgba(2,6,23,.56); border-radius: .6rem; padding: 1rem; display: grid; align-content: center; gap: .25rem; }
  .gag2-sab-hero-stat strong { font-size: 2rem; color: #fff; }
  .gag2-sab-tabs { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
  .gag2-sab-tabs button, .gag2-sab-link-btn { min-height: 5.5rem; border: 1px solid rgba(148,163,184,.18); background: linear-gradient(180deg, rgba(20,20,20,.94), rgba(12,12,12,.94)); color: #cbd5e1; border-radius: .7rem; padding: .8rem .6rem; font-weight: 800; text-align: center; box-shadow: inset 0 0 0 1px rgba(255,255,255,.02); }
  .gag2-sab-tabs button.is-active { background: linear-gradient(180deg, rgba(20,83,45,.52), rgba(12,28,18,.94)); border-color: #22c55e; color: #86efac; }
  .gag2-sab-tab-title { display: block; font-size: 1.1rem; line-height: 1.1; }
  .gag2-sab-tab-sub { display: block; margin-top: .35rem; color: #7a7a7a; font-size: .78rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
  .gag2-sab-tabs button.is-active .gag2-sab-tab-sub { color: #65d892; }
  .gag2-sab-tool { display: none; gap: 1rem; }
  .gag2-sab-tool.is-active { display: grid; }
  .gag2-sab-panel, .gag2-sab-side { border: 1px solid rgba(148,163,184,.18); background: rgba(15,23,42,.8); border-radius: .7rem; padding: 1rem; }
  .gag2-sab-panel { display: grid; gap: .85rem; }
  .gag2-sab-side { max-height: none; overflow: visible; }
  .gag2-sab-calc { background: transparent; border: 0; padding: 0; gap: .75rem; }
  .gag2-sab-toolbar { display: grid; grid-template-columns: minmax(0, 1fr) 180px 150px; gap: .5rem; }
  .gag2-sab-options { border: 1px solid rgba(148,163,184,.14); background: rgba(15,23,42,.72); border-radius: .65rem; padding: .85rem; display: grid; gap: .75rem; }
  .gag2-sab-options-row { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr); gap: .75rem; align-items: start; }
  .gag2-sab-field { display: grid; gap: .35rem; color: #cbd5e1; font-weight: 700; font-size: .9rem; }
  .gag2-sab-input, .gag2-sab-select { width: 100%; border: 1px solid rgba(148,163,184,.25); background: rgba(10,10,10,.9); color: #f8fafc; border-radius: .7rem; padding: .75rem .9rem; }
  .gag2-sab-grid { display: grid; gap: .75rem; }
  .gag2-sab-form-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; }
  .gag2-sab-list { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .15rem; }
  .gag2-sab-choice { width: 100%; min-height: 9.6rem; display: grid; align-content: start; justify-items: center; gap: .25rem; text-align: center; border: 1px solid rgba(148,163,184,.06); background: linear-gradient(180deg, rgba(24,24,24,.88), rgba(8,8,8,.92)); color: #f8fafc; border-radius: .2rem; padding: .55rem .25rem .65rem; }
  .gag2-sab-choice.is-selected { border-color: #b9c0cf; background: linear-gradient(180deg, rgba(31,41,55,.72), rgba(10,10,10,.95)); box-shadow: inset 0 0 0 1px rgba(226,232,240,.45); }
  .gag2-sab-choice img { width: 3.8rem; height: 3.8rem; object-fit: contain; }
  .gag2-sab-choice strong { display: block; min-height: 2.15rem; color: #60a5fa; font-size: .88rem; line-height: 1.05; overflow-wrap: normal; word-break: normal; }
  .gag2-sab-choice small { display: block; line-height: 1.1; }
  .gag2-sab-choice small, .gag2-sab-muted { color: #94a3b8; }
  .gag2-sab-filter-row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: .65rem; }
  .gag2-sab-mut-list { display: flex; flex-wrap: wrap; gap: .45rem; }
  .gag2-sab-mut-list button { border: 1px solid rgba(148,163,184,.2); background: rgba(2,6,23,.7); color: #e2e8f0; border-radius: 999px; padding: .45rem .7rem; }
  .gag2-sab-mut-list button.is-active { background: #22c55e; color: #052e16; border-color: #86efac; }
  .gag2-sab-result { display: grid; gap: .75rem; border: 1px solid rgba(148,163,184,.18); border-top: 4px solid #22c55e; background: linear-gradient(135deg, rgba(34,197,94,.08), rgba(11,11,11,.96) 58%); border-radius: .7rem; padding: 1rem; }
  .gag2-sab-result-placeholder { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 1rem; align-items: center; min-height: 6.6rem; }
  .gag2-sab-result-placeholder b { display: block; color: rgba(255,255,255,.42); font-size: 1.35rem; }
  .gag2-sab-result-tba { display: inline-grid; justify-items: center; gap: .1rem; color: #94a3b8; font-size: .8rem; }
  .gag2-sab-result-tba strong { font-size: 2.2rem; line-height: 1; color: rgba(255,255,255,.42); }
  .gag2-sab-result strong { font-size: 2rem; color: #d1d5db; }
  .gag2-sab-result-card { display: grid; grid-template-columns: 120px minmax(0, 1fr); gap: 1rem; align-items: center; }
  .gag2-sab-selected-img { width: 110px; height: 110px; object-fit: contain; border: 2px solid rgba(148,163,184,.35); border-radius: .65rem; background: radial-gradient(circle, rgba(255,255,255,.06), rgba(255,255,255,0)); padding: .75rem; }
  .gag2-sab-result-name { font-size: 1.55rem; font-weight: 900; color: #d1d5db; }
  .gag2-sab-big-number { font-size: 2rem; font-weight: 900; letter-spacing: .08em; color: #b7beca; }
  .gag2-sab-mini-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .65rem; }
  .gag2-sab-mini { border: 1px solid rgba(148,163,184,.16); background: rgba(2,6,23,.46); border-radius: .55rem; padding: .75rem; }
  .gag2-sab-mini span { display: block; color: #94a3b8; font-size: .8rem; }
  .gag2-sab-mini b { color: #f8fafc; }
  .gag2-sab-section { border: 1px solid rgba(148,163,184,.16); background: rgba(15,23,42,.72); border-radius: .7rem; padding: 1rem; }
  .gag2-sab-rank, .gag2-sab-card-grid { display: grid; gap: .65rem; }
  .gag2-sab-rank { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .gag2-sab-item { display: grid; grid-template-columns: 44px minmax(0, 1fr); gap: .65rem; align-items: center; border: 1px solid rgba(148,163,184,.16); background: rgba(2,6,23,.48); border-radius: .55rem; padding: .65rem; }
  .gag2-sab-card-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .gag2-sab-copy { color: #cbd5e1; display: grid; gap: .75rem; }
  @media (max-width: 900px) {
    body > main { padding-left: .45rem; padding-right: .45rem; padding-top: .55rem; }
    body > header nav {
      scrollbar-width: none;
    }
    body > header nav::-webkit-scrollbar {
      display: none;
    }
    .gag2-sab-page { gap: .45rem; max-width: 640px; }
    .gag2-sab-hero { display: none; }
    .gag2-sab-tabs { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .38rem; }
    .gag2-sab-tabs button { min-height: 3.85rem; border-radius: .42rem; padding: .5rem .35rem; }
    .gag2-sab-tab-title { font-size: .98rem; }
    .gag2-sab-tab-sub { margin-top: .2rem; font-size: .64rem; letter-spacing: .06em; }
    .gag2-sab-tool { grid-template-columns: 1fr; }
    .gag2-sab-panel, .gag2-sab-side, .gag2-sab-section { border-radius: .46rem; padding: .55rem; }
    .gag2-sab-calc { padding: 0; gap: .42rem; }
    .gag2-sab-toolbar { grid-template-columns: 1fr; gap: .35rem; }
    .gag2-sab-options { border-radius: .45rem; padding: .5rem; gap: .5rem; }
    .gag2-sab-options-row { grid-template-columns: 1fr; gap: .45rem; }
    .gag2-sab-panel { gap: .5rem; }
    .gag2-sab-side { border: 0; background: transparent; padding: 0; }
    .gag2-sab-form-grid, .gag2-sab-mini-grid, .gag2-sab-rank, .gag2-sab-card-grid { grid-template-columns: 1fr; }
    .gag2-sab-list { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .08rem; }
    .gag2-sab-choice { min-height: 6.65rem; border-radius: .14rem; padding: .35rem .15rem .4rem; gap: .15rem; }
    .gag2-sab-choice img { width: 2.55rem; height: 2.55rem; }
    .gag2-sab-choice strong { min-height: 1.75rem; font-size: .74rem; }
    .gag2-sab-choice small { font-size: .68rem; }
    .gag2-sab-input, .gag2-sab-select { border-radius: .45rem; padding: .55rem .65rem; }
    .gag2-sab-field { gap: .25rem; font-size: .82rem; }
    .gag2-sab-filter-row { gap: .38rem; }
    .gag2-sab-mut-list { gap: .3rem; }
    .gag2-sab-mut-list button { padding: .32rem .48rem; font-size: .78rem; }
    .gag2-sab-result { gap: .45rem; border-top-width: 2px; border-radius: .45rem; padding: .55rem; }
    .gag2-sab-result-placeholder { min-height: 4.35rem; gap: .5rem; }
    .gag2-sab-result-placeholder b { font-size: 1rem; }
    .gag2-sab-result-tba strong { font-size: 1.45rem; }
    .gag2-sab-result strong, .gag2-sab-big-number { font-size: 1.32rem; letter-spacing: .04em; }
    .gag2-sab-result-card { grid-template-columns: 70px minmax(0, 1fr); gap: .55rem; }
    .gag2-sab-selected-img { width: 64px; height: 64px; border-width: 1px; border-radius: .38rem; padding: .35rem; }
    .gag2-sab-result-name { font-size: 1.05rem; }
    .gag2-sab-mini { border-radius: .38rem; padding: .5rem; }
    .gag2-sab-section h2, .gag2-sab-panel h2 { font-size: 1.05rem; }
    .gag2-sab-chance-controls { display: none; }
  }
</style>

<section class="gag2-sab-page" data-gag2-sab-calculator data-payload='@json($payload, JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT)'>
  <div class="gag2-sab-hero">
    <div>
      <div class="gag2-sab-kicker">Grow a Garden 2 tool</div>
      <h1>Grow a Garden 2 Calculator</h1>
      <p class="gag2-sab-lead">Calculate crop sell value, compare mutations, estimate growth, and scan the best crops, pets, and gear from the current Grow a Garden 2 calculator data.</p>
    </div>
    <div class="gag2-sab-hero-stat">
      <span class="gag2-sab-muted">Collected calculator rows</span>
      <strong>{{ number_format(count($crops) + count($pets) + count($gears)) }}</strong>
      <span class="gag2-sab-muted">{{ number_format(count($crops)) }} crops, {{ number_format(count($mutations)) }} mutations</span>
    </div>
  </div>

  <div class="gag2-sab-tabs" role="tablist" aria-label="Grow a Garden 2 calculator tools">
    <button type="button" class="is-active" data-gag2-tab="crops">
      <span class="gag2-sab-tab-title">Plant Value</span>
      <span class="gag2-sab-tab-sub">Sell Price</span>
    </button>
    <button type="button" data-gag2-tab="chance">
      <span class="gag2-sab-tab-title">Weight Chance</span>
      <span class="gag2-sab-tab-sub">Size Odds</span>
    </button>
    <button type="button" data-gag2-tab="growth">
      <span class="gag2-sab-tab-title">Plant Growth</span>
      <span class="gag2-sab-tab-sub">Size Over Time</span>
    </button>
    <button type="button" data-gag2-tab="strategy">
      <span class="gag2-sab-tab-title">Strategy</span>
      <span class="gag2-sab-tab-sub">Best Crops & Tips</span>
    </button>
  </div>

  <div class="gag2-sab-tool is-active" data-gag2-panel="crops">
    <div class="gag2-sab-panel gag2-sab-calc">
      <div class="gag2-sab-result" data-crop-result>
        <span class="gag2-sab-muted">Total value</span>
        <strong>Select a crop</strong>
      </div>
      <div class="gag2-sab-toolbar">
        <input class="gag2-sab-input" type="search" placeholder="Search crops..." aria-label="Search crops" data-crop-search>
        <select class="gag2-sab-select" data-sort-filter aria-label="Sort crops">
          <option value="default">Rarity: Common → Super</option>
          <option value="rarity-desc">Rarity: Super → Common</option>
          <option value="value-desc">Value: High → Low</option>
          <option value="value-asc">Value: Low → High</option>
          <option value="name">Name: A → Z</option>
          <option value="name-desc">Name: Z → A</option>
        </select>
        <select class="gag2-sab-select" data-rarity-filter aria-label="Rarity filter">
          <option value="">All</option>
          <option value="Common">Common</option>
          <option value="Uncommon">Uncommon</option>
          <option value="Rare">Rare</option>
          <option value="Epic">Epic</option>
          <option value="Legendary">Legendary</option>
          <option value="Mythic">Mythic</option>
          <option value="Super">Super</option>
          <option value="Unknown">Unknown</option>
        </select>
      </div>
      <div class="gag2-sab-list" data-crop-list></div>
      <div class="gag2-sab-options">
        <div>
          <h2>Mutation</h2>
          <div class="gag2-sab-mut-list" data-mutation-list></div>
        </div>
        <div class="gag2-sab-options-row">
          <div class="gag2-sab-form-grid">
            <label class="gag2-sab-field">
              Weight (kg)
              <input class="gag2-sab-input" type="number" min="0" step="0.01" value="1.00" data-weight>
            </label>
            <label class="gag2-sab-field">
              Quantity
              <input class="gag2-sab-input" type="number" min="1" step="1" value="1" data-amount>
            </label>
          </div>
          <label class="gag2-sab-field">
            Friends: <span data-boost-label>0%</span>
            <input type="range" min="0" max="100" step="5" value="0" data-boost-range>
            <input class="gag2-sab-input" type="number" min="0" max="1000" step="1" value="0" data-boost>
          </label>
        </div>
      </div>
    </div>
  </div>

  <div class="gag2-sab-tool" data-gag2-panel="chance">
    <div class="gag2-sab-panel gag2-sab-grid">
      <div class="gag2-sab-result" data-chance-result>
        <span class="gag2-sab-muted">Weight chance</span>
        <strong>Select a crop</strong>
      </div>
      <div class="gag2-sab-form-grid gag2-sab-chance-controls">
        <label class="gag2-sab-field">
          Target weight (kg)
          <input class="gag2-sab-input" type="number" min="0.1" step="0.1" value="3.00" data-target-weight>
        </label>
        <label class="gag2-sab-field">
          Size luck
          <input class="gag2-sab-input" type="number" min="0" step="1" value="0" data-size-luck>
        </label>
        <label class="gag2-sab-field">
          Double chance (%)
          <input class="gag2-sab-input" type="number" min="0" step="0.1" value="1.0" data-double-chance>
        </label>
      </div>
      <label class="gag2-sab-field">
        Search crops
        <input class="gag2-sab-input" type="search" placeholder="Search crops..." data-chance-crop-search>
      </label>
      <div class="gag2-sab-filter-row">
        <select class="gag2-sab-select" data-chance-rarity-filter aria-label="Rarity filter">
          <option value="">Rarity: All</option>
          <option value="Common">Rarity: Common</option>
          <option value="Uncommon">Rarity: Uncommon</option>
          <option value="Rare">Rarity: Rare</option>
          <option value="Epic">Rarity: Epic</option>
          <option value="Legendary">Rarity: Legendary</option>
          <option value="Mythic">Rarity: Mythic</option>
          <option value="Super">Rarity: Super</option>
          <option value="Unknown">Rarity: Unknown</option>
        </select>
        <select class="gag2-sab-select" data-chance-sort-filter aria-label="Sort crops">
          <option value="default">All</option>
          <option value="value-desc">Highest Value</option>
          <option value="name">Name</option>
        </select>
      </div>
      <div class="gag2-sab-list" data-chance-crop-list></div>
    </div>
  </div>

  <div class="gag2-sab-tool" data-gag2-panel="growth">
    <div class="gag2-sab-panel gag2-sab-grid">
      <h2>Plant Growth Calculator</h2>
      <p class="gag2-sab-muted">Enter the current plant weight and compare estimated future weight by time window.</p>
      <label class="gag2-sab-field">
        Current plant weight (kg)
        <input class="gag2-sab-input" type="number" min="0" step="0.01" value="1.00" data-growth-weight>
      </label>
      <div class="gag2-sab-mini-grid" data-growth-result></div>
    </div>
    <div class="gag2-sab-section gag2-sab-copy">
      <h2>Money Methods</h2>
      <p>Higher-value crops matter most when their weight scales well and the mutation multiplier is high. Use the crop value panel before choosing which plants to grow, boost, or sell.</p>
      <p>Re-check values after updates because new crops, event mutations, and gear changes can shift the best money route quickly.</p>
    </div>
  </div>

  <div class="gag2-sab-tool" data-gag2-panel="strategy">
    <section class="gag2-sab-section">
      <h2>Best Crops</h2>
      <p class="gag2-sab-muted">Ranked from collected calculator values. Use this as a quick money guide, then calculate exact value with weight and mutations above.</p>
      <div class="gag2-sab-rank">
        @foreach (array_slice($bestCrops, 0, 12) as $crop)
          <div class="gag2-sab-item">
            @if (!empty($crop['local_image_url']))
              <img src="{{ $crop['local_image_url'] }}" alt="{{ $crop['name'] ?? 'Crop' }}" loading="lazy">
            @else
              <span></span>
            @endif
            <div>
              <strong>{{ $crop['name'] ?? '' }}</strong>
              <div class="gag2-sab-muted">{{ $crop['price_label'] ?? number_format((float) ($crop['base_value'] ?? 0)) . ' @1kg' }}</div>
            </div>
          </div>
        @endforeach
      </div>
    </section>
    <div class="gag2-sab-panel">
      <label class="gag2-sab-field">
        Pet or gear search
        <input class="gag2-sab-input" type="search" placeholder="Search pets or gear" data-companion-search>
      </label>
      <div class="gag2-sab-list" data-companion-list></div>
      <div class="gag2-sab-result" data-companion-result>
        <span class="gag2-sab-muted">Selected pet or gear</span>
        <strong>Select an item</strong>
      </div>
    </div>
  </div>

  <section class="gag2-sab-section gag2-sab-copy">
    <h2>Grow a Garden 2 Calculator Guide</h2>
    <p>This calculator follows the same practical flow as the popular reference page: pick a crop, enter weight and amount, apply a mutation, add friend boost, then compare the estimated sell value. The growth tab helps estimate future size, while the pets and gear tab keeps support items close to the calculator.</p>
    <p>For Steal a Brainrot players, the same rule applies: do not trust a value by name alone. Compare the exact modifiers before you accept a trade or sell an item.</p>
    <p><a href="{{ $articleHref }}" class="text-cyan-300 hover:underline">Read the full Grow a Garden 2 calculator guide</a> · <a href="{{ $sabCalcHref }}" class="text-cyan-300 hover:underline">Open SAB Calculator</a> · <a href="{{ $valueHref }}" class="text-cyan-300 hover:underline">View SAB Value List</a> · <a href="{{ $existHref }}" class="text-cyan-300 hover:underline">View Exist Count List</a></p>
  </section>

  <section class="gag2-sab-section">
    <h2>FAQ</h2>
    <div class="gag2-sab-card-grid">
      @foreach ($faqItems as $item)
        <div class="gag2-sab-mini">
          <b>{{ $item['question'] }}</b>
          <p class="gag2-sab-muted">{{ $item['answer'] }}</p>
        </div>
      @endforeach
    </div>
  </section>
</section>
@endsection

@section('scripts')
<script>
(function () {
  var root = document.querySelector('[data-gag2-sab-calculator]');
  if (!root) return;
  var data = {};
  try { data = JSON.parse(root.getAttribute('data-payload') || '{}'); } catch (e) { data = {}; }
  var crops = data.crops || [];
  var mutations = data.mutations || [];
  var companions = (data.pets || []).map(function (row) { row.type = 'Pet'; return row; })
    .concat((data.gears || []).map(function (row) { row.type = 'Gear'; return row; }));
  var growth = data.growth || {};
  var state = {
    panel: 'crops',
    crop: '',
    mutation: 'none',
    query: '',
    rarity: '',
    sort: 'default',
    chanceQuery: '',
    chanceRarity: '',
    chanceSort: 'default',
    companion: companions[0] ? companions[0].slug : '',
    companionQuery: ''
  };

  var tabs = Array.prototype.slice.call(root.querySelectorAll('[data-gag2-tab]'));
  var panels = Array.prototype.slice.call(root.querySelectorAll('[data-gag2-panel]'));
  var cropList = root.querySelector('[data-crop-list]');
  var chanceCropList = root.querySelector('[data-chance-crop-list]');
  var cropSelect = root.querySelector('[data-crop-select]');
  var mutationList = root.querySelector('[data-mutation-list]');
  var cropResult = root.querySelector('[data-crop-result]');
  var chanceResult = root.querySelector('[data-chance-result]');
  var boostInput = root.querySelector('[data-boost]');
  var boostRange = root.querySelector('[data-boost-range]');
  var boostLabel = root.querySelector('[data-boost-label]');
  var companionList = root.querySelector('[data-companion-list]');
  var companionResult = root.querySelector('[data-companion-result]');

  function esc(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch];
    });
  }
  function find(rows, slug) { return rows.find(function (row) { return row.slug === slug; }) || null; }
  function money(value) {
    var number = Number(value) || 0;
    if (number <= 0) return '$0';
    return '~$' + Math.ceil(number).toLocaleString('en-US');
  }
  function range(value) {
    var high = Number(value) || 0;
    if (!high) return '$0';
    return money(high * 0.8) + ' - ' + money(high);
  }
  function kg(value) {
    return (Math.max(0, Number(value) || 0)).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + 'kg';
  }
  function image(row) {
    return row && row.local_image_url ? '<img src="' + esc(row.local_image_url) + '" alt="" loading="lazy">' : '<span></span>';
  }
  function cropImage(row) {
    return row && row.local_image_url ? '<img class="gag2-sab-selected-img" src="' + esc(row.local_image_url) + '" alt="" loading="lazy">' : '<span class="gag2-sab-selected-img"></span>';
  }
  function cropColor(crop) {
    var value = Number(crop && crop.base_value) || 0;
    if (value >= 1000) return '#4ade80';
    if (value >= 100) return '#60a5fa';
    return '#a1a1aa';
  }
  function setPanel(panel) {
    state.panel = panel;
    tabs.forEach(function (tab) { tab.classList.toggle('is-active', tab.getAttribute('data-gag2-tab') === panel); });
    panels.forEach(function (node) { node.classList.toggle('is-active', node.getAttribute('data-gag2-panel') === panel); });
  }
  function filteredCrops(query, rarity, sort) {
    var q = String(query || '').toLowerCase();
    var rows = crops.filter(function (crop) {
      var rarityOk = !rarity || String(crop.rarity || 'Unknown') === rarity;
      var queryOk = !q || String(crop.name || '').toLowerCase().indexOf(q) !== -1;
      return rarityOk && queryOk;
    });
    if (sort === 'rarity-desc') {
      var order = ['Unknown', 'Super', 'Mythic', 'Legendary', 'Epic', 'Rare', 'Uncommon', 'Common'];
      rows = rows.slice().sort(function (a, b) { return order.indexOf(String(a.rarity || 'Unknown')) - order.indexOf(String(b.rarity || 'Unknown')); });
    } else if (sort === 'value-desc') {
      rows = rows.slice().sort(function (a, b) { return (Number(b.base_value) || 0) - (Number(a.base_value) || 0); });
    } else if (sort === 'value-asc') {
      rows = rows.slice().sort(function (a, b) { return (Number(a.base_value) || 0) - (Number(b.base_value) || 0); });
    } else if (sort === 'name') {
      rows = rows.slice().sort(function (a, b) { return String(a.name || '').localeCompare(String(b.name || '')); });
    } else if (sort === 'name-desc') {
      rows = rows.slice().sort(function (a, b) { return String(b.name || '').localeCompare(String(a.name || '')); });
    }
    return rows;
  }
  function renderCropGrid(target, rows, selectedSlug) {
    if (!target) return;
    target.innerHTML = rows.map(function (crop) {
      return '<button type="button" class="gag2-sab-choice ' + (crop.slug === selectedSlug ? 'is-selected' : '') + '" data-crop="' + esc(crop.slug) + '">' +
        image(crop) + '<strong style="color:' + cropColor(crop) + '">' + esc(crop.name) + '</strong><small>' + esc(crop.price_label || '') + '</small></button>';
    }).join('') || '<p class="gag2-sab-muted">No matching crops.</p>';
  }
  function renderCropList() {
    var rows = filteredCrops(state.query, state.rarity, state.sort);
    renderCropGrid(cropList, rows, state.crop);
    renderCropGrid(chanceCropList, filteredCrops(state.chanceQuery, state.chanceRarity, state.chanceSort), state.crop);
    if (cropSelect) {
      cropSelect.innerHTML = crops.map(function (crop) {
        return '<option value="' + esc(crop.slug) + '"' + (crop.slug === state.crop ? ' selected' : '') + '>' + esc(crop.name) + '</option>';
      }).join('');
    }
  }
  function renderMutations() {
    if (!mutationList) return;
    mutationList.innerHTML = mutations.map(function (mutation) {
      var mult = Number(mutation.multiplier) || 1;
      return '<button type="button" class="' + (mutation.slug === state.mutation ? 'is-active' : '') + '" data-mutation="' + esc(mutation.slug) + '">' +
        esc(mutation.name) + ' (' + mult + 'x)' + (mutation.status === 'soon' ? ' soon' : '') + '</button>';
    }).join('');
  }
  function cropBase(crop, weight) {
    var formula = crop.value_formula || {};
    var coefficient = Number(formula.coefficient || crop.base_value || 0);
    return coefficient * Math.pow(Math.max(0, Number(weight) || 0), 2);
  }
  function renderCropResult() {
    if (!cropResult) return;
    var crop = find(crops, state.crop);
    if (!crop) {
      cropResult.innerHTML = '<div class="gag2-sab-result-placeholder"><div><b>Select a crop below</b><span class="gag2-sab-muted">Pick a crop from the grid to calculate its value</span></div><div class="gag2-sab-result-tba"><strong>-</strong><span>No crop selected</span></div></div>';
      return;
    }
    var mutation = find(mutations, state.mutation) || { multiplier: 1, name: 'None' };
    var weight = Number((root.querySelector('[data-weight]') || {}).value) || 0;
    var amount = Math.max(1, Number((root.querySelector('[data-amount]') || {}).value) || 1);
    var boost = Math.max(0, Number((boostInput || {}).value) || 0);
    var base = cropBase(crop, weight) * amount;
    var afterMutation = base * (Number(mutation.multiplier) || 1);
    var total = afterMutation * (1 + (boost / 100));
    if (boostLabel) boostLabel.textContent = boost + '%';
    if (boostRange) boostRange.value = String(Math.min(100, boost));
    cropResult.innerHTML = '<div class="gag2-sab-result-card">' + cropImage(crop) + '<div><div class="gag2-sab-result-name">' + esc(crop.name) + '</div>' +
      '<p class="gag2-sab-muted">Weight ' + esc(weight.toFixed ? weight.toFixed(2) : weight) + ' kg · ' + esc(mutation.name || 'None') + ' mutation · Boost ' + esc(boost) + '%</p>' +
      '<strong>' + esc(range(total)) + '</strong></div></div>' +
      '<div class="gag2-sab-mini-grid"><div class="gag2-sab-mini"><span>Base x weight</span><b>' + esc(range(base)) + '</b></div>' +
      '<div class="gag2-sab-mini"><span>After mutation</span><b>' + esc(range(afterMutation)) + '</b></div>' +
      '<div class="gag2-sab-mini"><span>After boost</span><b>' + esc(range(total)) + '</b></div></div>';
  }
  function renderChanceResult() {
    if (!chanceResult) return;
    var crop = find(crops, state.crop);
    if (!crop) {
      chanceResult.innerHTML = '<span class="gag2-sab-muted">Weight chance</span><strong>Select a crop</strong>';
      return;
    }
    var target = Math.max(0.1, Number((root.querySelector('[data-target-weight]') || {}).value) || 3);
    var sizeLuck = Math.max(0, Number((root.querySelector('[data-size-luck]') || {}).value) || 0);
    var doubleChance = Math.max(0, Number((root.querySelector('[data-double-chance]') || {}).value) || 1);
    var baseWeight = Math.max(1, Number(crop.base_weight || 1));
    var ratio = target / baseWeight;
    var odds = Math.max(0.001, Math.min(99.9, (doubleChance * (1 + (sizeLuck / 100))) / Math.pow(Math.max(1, ratio), 6)));
    var oneIn = Math.max(1, Math.round(100 / odds));
    var worth = cropBase(crop, target);
    chanceResult.innerHTML = '<div class="gag2-sab-result-card">' + cropImage(crop) + '<div><div class="gag2-sab-result-name">' + esc(crop.name) + '</div>' +
      '<p class="gag2-sab-muted">Base weight ' + baseWeight.toFixed(2) + ' kg Size luck ' + esc(sizeLuck) + '</p>' +
      '<p class="gag2-sab-muted">Double chance ' + esc(doubleChance.toFixed ? doubleChance.toFixed(2) : doubleChance) + '%</p>' +
      '<p class="gag2-sab-muted">Chance to reach <b style="color:#f8fafc">' + esc(target.toFixed(2)) + ' kg</b> or heavier</p></div></div>' +
      '<div><span class="gag2-sab-big-number">' + odds.toFixed(3) + '%</span> <span class="gag2-sab-muted">1 in ' + oneIn.toLocaleString('en-US') + ' &nbsp; worth ' + esc(money(worth)) + '</span></div>';
  }
  function renderGrowth() {
    var target = root.querySelector('[data-growth-result]');
    if (!target) return;
    var start = Number((root.querySelector('[data-growth-weight]') || {}).value) || 0;
    var percent = Number(growth.percent_per_step) || 0.00025;
    var step = Number(growth.step_seconds) || 10;
    var durations = growth.durations || [];
    target.innerHTML = durations.map(function (duration) {
      var steps = (Number(duration.seconds) || 0) / step;
      var value = start + (start * percent * steps);
      return '<div class="gag2-sab-mini"><span>' + esc(duration.label) + '</span><b>' + esc(kg(value)) + '</b></div>';
    }).join('');
  }
  function renderCompanions() {
    var query = state.companionQuery.toLowerCase();
    var rows = companions.filter(function (row) { return !query || String(row.name + ' ' + row.type).toLowerCase().indexOf(query) !== -1; });
    if (companionList) {
      companionList.innerHTML = rows.map(function (row) {
        return '<button type="button" class="gag2-sab-choice ' + (row.slug === state.companion ? 'is-selected' : '') + '" data-companion="' + esc(row.slug) + '">' +
          image(row) + '<span><strong>' + esc(row.name) + '</strong><small>' + esc(row.type + ' · ' + (row.rarity || 'Unknown')) + '</small></span><small>' + esc(row.price || '') + '</small></button>';
      }).join('') || '<p class="gag2-sab-muted">No matching pets or gear.</p>';
    }
    var row = find(companions, state.companion);
    if (companionResult) {
      companionResult.innerHTML = row ? '<span class="gag2-sab-muted">' + esc(row.type) + '</span><strong>' + esc(row.name) + '</strong>' +
        '<div class="gag2-sab-mini-grid"><div class="gag2-sab-mini"><span>Rarity</span><b>' + esc(row.rarity || 'Unknown') + '</b></div>' +
        '<div class="gag2-sab-mini"><span>Price</span><b>' + esc(row.price || 'TBA') + '</b></div><div class="gag2-sab-mini"><span>Stock</span><b>' + esc(row.stock || 'TBA') + '</b></div></div>' +
        '<p class="gag2-sab-muted">' + esc(row.description || 'Ability, price, or rarity may change after updates.') + '</p>' :
        '<span class="gag2-sab-muted">Selected pet or gear</span><strong>Select an item</strong>';
    }
  }
  function renderAll() {
    renderCropList();
    renderMutations();
    renderCropResult();
    renderChanceResult();
    renderGrowth();
    renderCompanions();
  }
  root.addEventListener('click', function (event) {
    var tab = event.target.closest('[data-gag2-tab]');
    if (tab) setPanel(tab.getAttribute('data-gag2-tab') || 'crops');
    var crop = event.target.closest('[data-crop]');
    if (crop) { state.crop = crop.getAttribute('data-crop') || ''; renderAll(); }
    var mutation = event.target.closest('[data-mutation]');
    if (mutation) { state.mutation = mutation.getAttribute('data-mutation') || 'none'; renderAll(); }
    var companion = event.target.closest('[data-companion]');
    if (companion) { state.companion = companion.getAttribute('data-companion') || ''; renderAll(); }
  });
  var cropSearch = root.querySelector('[data-crop-search]');
  if (cropSearch) cropSearch.addEventListener('input', function () { state.query = cropSearch.value; renderCropList(); });
  var rarityFilter = root.querySelector('[data-rarity-filter]');
  if (rarityFilter) rarityFilter.addEventListener('change', function () { state.rarity = rarityFilter.value; renderCropList(); });
  var sortFilter = root.querySelector('[data-sort-filter]');
  if (sortFilter) sortFilter.addEventListener('change', function () { state.sort = sortFilter.value; renderCropList(); });
  var chanceCropSearch = root.querySelector('[data-chance-crop-search]');
  if (chanceCropSearch) chanceCropSearch.addEventListener('input', function () { state.chanceQuery = chanceCropSearch.value; renderCropList(); });
  var chanceRarityFilter = root.querySelector('[data-chance-rarity-filter]');
  if (chanceRarityFilter) chanceRarityFilter.addEventListener('change', function () { state.chanceRarity = chanceRarityFilter.value; renderCropList(); });
  var chanceSortFilter = root.querySelector('[data-chance-sort-filter]');
  if (chanceSortFilter) chanceSortFilter.addEventListener('change', function () { state.chanceSort = chanceSortFilter.value; renderCropList(); });
  var companionSearch = root.querySelector('[data-companion-search]');
  if (companionSearch) companionSearch.addEventListener('input', function () { state.companionQuery = companionSearch.value; renderCompanions(); });
  if (cropSelect) cropSelect.addEventListener('change', function () { state.crop = cropSelect.value; renderAll(); });
  if (boostRange) boostRange.addEventListener('input', function () { if (boostInput) boostInput.value = boostRange.value; renderCropResult(); });
  ['data-weight', 'data-amount', 'data-boost', 'data-growth-weight', 'data-target-weight', 'data-size-luck', 'data-double-chance'].forEach(function (attr) {
    var el = root.querySelector('[' + attr + ']');
    if (el) el.addEventListener('input', renderAll);
  });
  setPanel('crops');
  renderAll();
})();
</script>
@endsection
