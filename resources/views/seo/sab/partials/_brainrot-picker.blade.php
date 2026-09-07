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
            <svg class="sab-calc-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
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
