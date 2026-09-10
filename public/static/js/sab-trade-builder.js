(async () => {
  const configEl = document.getElementById('sab-trade-builder-config');
  const root = document.querySelector('[data-calculator-root]');
  if (!configEl || !root) return;

  let config;
  try {
    config = JSON.parse(configEl.textContent || '{}');
  } catch (error) {
    return;
  }

  let data = config.data || null;
  const ui = config.ui || {};

  const chunkPromises = new Map();

  async function fetchJson(url) {
    const response = await fetch(url, { credentials: 'same-origin' });
    if (!response.ok) throw new Error(`Catalog request failed: ${response.status}`);
    return response.json();
  }

  function showCatalogError(error) {
    console.error('SAB calculator catalog failed', error);
    const message = document.createElement('p');
    message.className = 'sab-calc-catalog-error';
    message.textContent = 'Calculator data could not be loaded. Please refresh and try again.';
    root.prepend(message);
  }

  async function loadCatalog() {
    if (data?.brainrots?.length) return;
    const manifestUrl = config.catalogManifestUrl;
    if (!manifestUrl) throw new Error('Calculator catalog URL is missing.');
    const manifest = await fetchJson(manifestUrl);
    const bootstrap = await fetchJson(manifest.bootstrap);
    data = {
      ...bootstrap,
      brainrots: bootstrap.brainrots || bootstrap.items || [],
      manifest,
    };
  }

  try {
    await loadCatalog();
  } catch (error) {
    showCatalogError(error);
    return;
  }

  if (!data.brainrots || data.brainrots.length === 0) {
    showCatalogError(new Error('Calculator catalog is empty.'));
    return;
  }

  function findBrainrot(item) {
    const slug = item?.brainrot?.slug || item?.slug;
    const id = item?.brainrot?.id || item?.id;
    return data.brainrots.find((row) => row.slug === slug || String(row.id) === String(id)) || null;
  }

  async function loadBrainrotDetails(indexItem) {
    if (!indexItem) throw new Error('Brainrot is missing from the catalog.');
    if (Array.isArray(indexItem.mutations)) return indexItem;
    const chunkId = String(indexItem.chunk || '');
    const chunkUrl = data.manifest?.chunks?.[chunkId];
    if (!chunkUrl) throw new Error(`Catalog chunk is missing: ${chunkId}`);
    if (!chunkPromises.has(chunkId)) {
      chunkPromises.set(chunkId, fetchJson(chunkUrl).then((payload) => {
        (payload.brainrots || []).forEach((row) => {
          const target = data.brainrots.find((item) => item.slug === row.slug || String(item.id) === String(row.id));
          if (target) Object.assign(target, row);
        });
        return payload;
      }));
    }
    await chunkPromises.get(chunkId);
    if (!Array.isArray(indexItem.mutations)) throw new Error(`Catalog item is missing details: ${indexItem.slug}`);
    return indexItem;
  }

  const rotTheme = root.dataset.calculatorTheme === 'rot';
  const layout = root.dataset.builderLayout === 'grid' ? 'grid' : 'cards';
  const maxItems = Math.max(1, Number(config.maxItemsPerSide) || 9);
  const draftKey = config.draftKey || 'sab-trade-draft';
  const publishUrl = config.publishUrl || '/api/v1/trading/trades';
  const csrf = config.csrf || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const rarityAll = config.rarityAll || 'All';

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
  const authModal = document.querySelector('[data-roblox-auth-modal]');

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

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function renderSideCards(side) {
    const box = root.querySelector(`[data-side="${side}"]`);
    if (!box) return;
    const list = box.querySelector('[data-items]');
    const count = box.querySelector('[data-count]');
    const totalBox = box.querySelector('[data-totals]');
    if (!list) return;
    const items = state[side];
    list.innerHTML = '';
    const quantityCount = items.reduce((sum, item) => sum + item.quantity, 0);
    if (count) {
      count.textContent = quantityCount + ' ' + (quantityCount === 1 ? (ui.countSingular || 'item') : (ui.countPlural || 'items'));
    }

    items.forEach((item, index) => {
      const calc = calcItem(item);
      const row = document.createElement('div');
      row.className = 'sab-calc-item-card';
      row.innerHTML = `
        <div style="display:flex;gap:.75rem;align-items:flex-start">
          <img src="${escapeHtml(item.brainrot.image || '')}" alt="" style="width:3rem;height:3rem;flex-shrink:0;object-fit:contain">
          <div style="min-width:0;flex:1">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem">
              <div style="min-width:0">
                <p style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:1rem;font-weight:700;color:#fff">${escapeHtml(item.brainrot.name)}</p>
                <p style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.875rem;color:#94a3b8">${escapeHtml(item.mutation?.name || ui.defaultMutation || 'Default')}</p>
                <p style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.75rem;color:#64748b">${escapeHtml(item.traits.map(t => t.name).join(', '))}</p>
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
      addBtn.innerHTML = '<span aria-hidden="true" style="font-size:1rem;line-height:1">+</span><span>' + escapeHtml(ui.addItem || 'Add Item') + '</span>';
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

    bindSideActions(list, items, side);

    const total = totals(items);
    if (totalBox) {
      totalBox.innerHTML = items.length ? `
        <div style="display:flex;justify-content:space-between;gap:1rem"><span style="color:#64748b">${escapeHtml(ui.totalIncome || 'Total Income')}</span><span class="sab-calc-income" style="font-weight:900">${income(total.income).replace('+', '')}</span></div>
        <div style="display:flex;justify-content:space-between;gap:1rem"><span style="color:#64748b">${escapeHtml(ui.totalValue || 'Total Value')}</span><span class="sab-calc-value" style="font-weight:900">${money(total.value)}${total.value > 0 ? '+' : ''}</span></div>
      ` : '';
    }
  }

  function bindSideActions(scope, items, side) {
    scope.querySelectorAll('[data-edit]').forEach(btn => btn.addEventListener('click', (event) => {
      event.stopPropagation();
      openModal(side, Number(btn.dataset.edit));
    }));
    scope.querySelectorAll('[data-remove]').forEach(btn => btn.addEventListener('click', (event) => {
      event.stopPropagation();
      items.splice(Number(btn.dataset.remove), 1);
      persistDraft();
      render();
    }));
    scope.querySelectorAll('[data-dec]').forEach(btn => btn.addEventListener('click', () => {
      const item = items[Number(btn.dataset.dec)];
      item.quantity = Math.max(1, item.quantity - 1);
      persistDraft();
      render();
    }));
    scope.querySelectorAll('[data-inc]').forEach(btn => btn.addEventListener('click', () => {
      items[Number(btn.dataset.inc)].quantity += 1;
      persistDraft();
      render();
    }));
    scope.querySelectorAll('[data-add-slot]').forEach(btn => btn.addEventListener('click', () => {
      if (state[btn.dataset.addSlot].length >= maxItems) return;
      openModal(btn.dataset.addSlot);
    }));
  }

  function renderSideGrid(side) {
    const box = root.querySelector(`[data-side="${side}"]`);
    if (!box) return;
    const slots = box.querySelector('[data-slots]');
    const totalBox = box.querySelector('[data-totals]');
    const items = state[side];
    if (slots) {
      slots.innerHTML = '';
      for (let i = 0; i < maxItems; i += 1) {
        const item = items[i];
        if (item) {
          const calc = calcItem(item);
          const traitNames = (item.traits || []).map((trait) => trait.name).filter(Boolean);
          const traitsLabel = traitNames.join(', ');
          const traitsHtml = traitNames.length
            ? `<span class="trades-create-slot__traits" title="${escapeHtml(traitsLabel)}">${escapeHtml(traitsLabel)}</span>`
            : '';
          const slot = document.createElement('button');
          slot.type = 'button';
          slot.className = 'trades-create-slot is-filled';
          slot.dataset.edit = String(i);
          slot.innerHTML = `
            <img src="${escapeHtml(item.brainrot.image || '')}" alt="" class="trades-create-slot__img">
            <span class="trades-create-slot__name">${escapeHtml(item.brainrot.name)}</span>
            <span class="trades-create-slot__meta">${escapeHtml(item.mutation?.name || ui.defaultMutation || 'Default')}</span>
            ${traitsHtml}
            <span class="trades-create-slot__value">${calc.value === null ? 'N/A' : money(calc.value)}</span>
            <span class="trades-create-slot__remove" data-remove="${i}" role="button" aria-label="Remove item">×</span>
          `;
          slots.appendChild(slot);
        } else if (i === items.length) {
          const slot = document.createElement('button');
          slot.type = 'button';
          slot.className = 'trades-create-slot is-add';
          slot.dataset.addSlot = side;
          slot.setAttribute('aria-label', 'Add Item');
          slot.innerHTML = `
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <circle cx="12" cy="12" r="9"/>
              <path stroke-linecap="round" d="M12 8v8M8 12h8"/>
            </svg>
            <span>Add Item</span>
          `;
          slots.appendChild(slot);
        } else {
          const slot = document.createElement('div');
          slot.className = 'trades-create-slot is-empty';
          slot.setAttribute('aria-hidden', 'true');
          slots.appendChild(slot);
        }
      }
      bindSideActions(slots, items, side);
    }

    const total = totals(items);
    if (totalBox) {
      totalBox.innerHTML = `
        <div class="trades-create-stat">
          <span class="trades-create-stat__label">Value</span>
          <span class="trades-create-stat__value trades-create-stat__value--value">${money(total.value)}</span>
        </div>
        <div class="trades-create-stat">
          <span class="trades-create-stat__label">Income</span>
          <span class="trades-create-stat__value trades-create-stat__value--income">${income(total.income).replace('+', '')}</span>
        </div>
      `;
    }
  }

  function renderCompare() {
    const compare = root.querySelector('[data-compare]');
    if (!compare) return;
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

    compare.innerHTML = state.offer.length && state.receive.length ? `
      <div>
        <p class="text-xs uppercase tracking-wide text-slate-500">${escapeHtml(ui.incomeLabel || 'Income')}</p>
        <p class="${incomeDiff >= 0 ? 'sab-calc-income' : 'sab-calc-danger'} text-2xl font-black">${income(incomeDiff)}</p>
      </div>
      <div>
        <p class="text-xs uppercase tracking-wide text-slate-500">${escapeHtml(ui.valueLabel || 'Value')}</p>
        <p class="${valueDiff >= 0 ? 'sab-calc-income' : 'sab-calc-danger'} text-2xl font-black">${valueDiff >= 0 ? '+' : '-'}${money(Math.abs(valueDiff))}</p>
        <p class="text-sm text-slate-500">${valuePct >= 0 ? '+' : ''}${valuePct.toFixed(1)}%</p>
      </div>
      <span class="sab-calc-deal-pill ${dealClass}">${escapeHtml(deal)}</span>
    ` : `
      <div class="sab-calc-compare-status">
        <p class="text-xs uppercase tracking-wide text-slate-500">${escapeHtml(ui.wflCheckLabel || 'W/F/L Check')}</p>
        <p style="margin-top:.35rem;font-size:.95rem;line-height:1.45;font-weight:800;color:#cbd5e1">${escapeHtml(statusText)}</p>
      </div>
    `;
  }

  function updatePublishReady() {
    const ready = state.offer.length > 0 && state.receive.length > 0;
    root.querySelectorAll('[data-publish-ready-hint]').forEach((hint) => {
      hint.hidden = ready;
    });
    root.querySelectorAll('[data-publish-trade]').forEach((btn) => {
      btn.classList.toggle('is-ready', ready);
    });
  }

  function render() {
    if (layout === 'grid') {
      renderSideGrid('offer');
      renderSideGrid('receive');
    } else {
      renderSideCards('offer');
      renderSideCards('receive');
      renderCompare();
    }
    updatePublishReady();
  }

  async function openModal(side, index = null) {
    state.modalSide = side;
    state.editIndex = index;
    const existing = index === null ? null : state[side][index];
    state.selected = existing?.brainrot || data.brainrots[0];
    state.mutation = existing?.mutation || null;
    state.traits = existing ? [...existing.traits] : [];
    if (searchBrainrot) searchBrainrot.value = '';
    if (searchTrait) searchTrait.value = '';
    state.rarityFilter = '';
    state.configTab = 'mutation';
    document.body.classList.add('sab-calc-modal-open');
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    if (index === null) {
      renderModal(true);
      return;
    }

    try {
      state.selected = await loadBrainrotDetails(state.selected);
      state.mutation = existing?.mutation || state.selected.mutations[0];
      renderModal(false);
    } catch (error) {
      showCatalogError(error);
      closeModal();
    }
  }

  function closeModal() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('sab-calc-modal-open');
  }

  function renderModal(showPicker = false) {
    dialog.classList.toggle('is-picker', showPicker);
    dialog.classList.toggle('is-config', !showPicker);
    root.querySelector('[data-modal-title]')?.classList.toggle('hidden', !showPicker);
    selectedPanel?.classList.toggle('hidden', showPicker);
    pickerPanel?.classList.toggle('hidden', !showPicker);
    root.querySelector('[data-calc-sections]')?.classList.toggle('hidden', showPicker);
    root.querySelector('[data-calc-income-panel]')?.classList.toggle('hidden', showPicker);
    root.querySelector('[data-modal-footer]')?.classList.toggle('hidden', showPicker);
    if (showPicker) { renderRarityFilters(); renderBrainrotGrid(); return; }
    const selectedImage = root.querySelector('[data-selected-image]');
    if (selectedImage) selectedImage.src = state.selected.image || '';
    const selectedName = root.querySelector('[data-selected-name]');
    if (selectedName) selectedName.textContent = state.selected.name;
    const selectedMeta = root.querySelector('[data-selected-meta]');
    if (selectedMeta) {
      selectedMeta.textContent = `${ui.baseLabel || 'Base'}: ${income(Number(state.selected.baseIncome)).replace('+', '')} · ${state.selected.rarity || ''}`;
    }
    const priceEl = root.querySelector('[data-selected-price]');
    if (priceEl) {
      const rv = Number(state.selected.robuxValue);
      priceEl.textContent = Number.isFinite(rv) && rv > 0 ? money(rv) : '';
    }
    renderConfigTabs();
    renderMutations();
    renderTraits();
    renderIncomeBox();
    const saveBtn = root.querySelector('[data-save]');
    if (saveBtn) saveBtn.textContent = state.editIndex === null ? (ui.addItem || 'Add Item') : (ui.updateItem || 'Update Item');
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
    if (!brainrotGrid || !searchBrainrot) return;
    const q = searchBrainrot.value.toLowerCase().trim();
    brainrotGrid.innerHTML = '';
    const items = data.brainrots
      .filter(item => !q || item.name.toLowerCase().includes(q))
      .filter(item => !state.rarityFilter || rarityKey(item.rarity) === state.rarityFilter)
      .slice(0, 160);

    if (!items.length) {
      brainrotGrid.innerHTML = `<div class="sab-calc-empty-grid">${escapeHtml(ui.noBrainrotsFound || 'No matching Brainrots found.')}</div>`;
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
      btn.addEventListener('click', async () => {
        state.selected = item;
        state.traits = [];
        btn.disabled = true;
        try {
          const detailedBrainrot = await loadBrainrotDetails(item);
          if (state.selected !== item) return;
          state.selected = detailedBrainrot;
          state.mutation = state.selected.mutations[0];
          renderModal(false);
        } catch (error) {
          showCatalogError(error);
        } finally {
          btn.disabled = false;
        }
      });
      brainrotGrid.appendChild(btn);
    });
  }

  function renderRarityFilters() {
    if (!rarityFilters) return;
    rarityFilters.innerHTML = '';
    [{ key: '', label: rarityAll }, ...rarityOptions].forEach(option => {
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
    if (!mutationGrid || !state.selected) return;
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
    if (!traitGrid || !searchTrait) return;
    const q = searchTrait.value.toLowerCase().trim();
    const selectedIds = new Set(state.traits.map(t => t.id));
    const traits = [...data.traits]
      .filter(trait => !q || trait.name.toLowerCase().includes(q))
      .sort((a, b) => state.traitDescending ? b.multiplier - a.multiplier : a.name.localeCompare(b.name));
    const traitCount = root.querySelector('[data-trait-count]');
    if (traitCount) traitCount.textContent = state.traits.length;
    traitGrid.innerHTML = '';
    traits.forEach(trait => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'sab-calc-chip sab-calc-trait text-left';
      btn.classList.toggle('is-selected', selectedIds.has(trait.id));
      btn.appendChild(image(trait.image, '', 'sab-calc-trait-img'));
      const text = document.createElement('span');
      text.className = 'min-w-0';
      text.innerHTML = `<span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.75rem;font-weight:700;color:#fff">${escapeHtml(trait.name)}</span><span style="display:block;font-size:.65rem;color:#64748b">${escapeHtml(trait.multiplier)}x</span>`;
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
    const incomeEl = root.querySelector('[data-modal-income]');
    const box = root.querySelector('[data-income-breakdown]');
    if (!incomeEl || !box || !state.selected) return;
    const item = { brainrot: state.selected, mutation: state.mutation, traits: state.traits, quantity: 1 };
    const calc = calcItem(item);
    incomeEl.textContent = income(calc.calculatedIncome).replace('+', '');
    const rawEl = root.querySelector('[data-modal-income-raw]');
    if (rawEl) rawEl.textContent = '$' + Math.round(calc.calculatedIncome).toLocaleString() + '/s';
    const multEl = root.querySelector('[data-modal-multiplier]');
    if (multEl) multEl.textContent = calc.incomeMultiplier.toFixed(2) + 'x ' + (ui.totalMultiplier || 'Total Multiplier');

    const rowStyle = 'display:grid;grid-template-columns:52px 1fr auto;gap:.25rem .5rem;align-items:center;padding:.3rem 0;font-size:.75rem;border-bottom:1px solid rgba(255,255,255,.05)';
    const mutMult = Number(state.mutation?.multiplier || 1);
    const traitRows = state.traits.map(trait => `
      <div style="${rowStyle}">
        <span style="color:#94a3b8;font-weight:700">${escapeHtml(trait.multiplier)}</span>
        <span style="color:#fff;font-weight:600">${escapeHtml(trait.name)}</span>
        <span style="color:#64748b;font-size:.65rem">${escapeHtml(trait.multiplier)}x ${escapeHtml(ui.traitMultiplierLabel || 'trait')}</span>
      </div>`).join('');

    box.innerHTML = `
      <div style="${rowStyle}">
        <span style="color:#94a3b8">${escapeHtml(ui.baseLabel || 'Base')}</span>
        <span style="color:#fff;font-weight:700">${income(Number(state.selected.baseIncome)).replace('+', '')}</span>
        <span></span>
      </div>
      ${traitRows}
      <div style="${rowStyle}">
        <span style="color:#94a3b8;font-weight:700">+ ${escapeHtml(mutMult)}</span>
        <span style="color:#fff;font-weight:600">${escapeHtml(state.mutation?.name || ui.defaultMutation || 'Default')}</span>
        <span style="color:#64748b;font-size:.65rem">${escapeHtml(mutMult)}x ${escapeHtml(ui.mutationMultiplierLabel || 'mutation')}</span>
      </div>
      <div style="margin-top:.375rem;border-radius:.375rem;background:rgba(34,197,94,.15);padding:.375rem .625rem;display:grid;grid-template-columns:52px 1fr;gap:.5rem;align-items:center;font-size:.8rem;font-weight:700;color:#4ade80">
        <span>= ${calc.incomeMultiplier.toFixed(2)}x</span>
        <span>${escapeHtml(ui.totalMultiplier || 'Total Multiplier')}</span>
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
      <p class="mb-2 font-bold text-cyan-300">${escapeHtml(ui.traitValueBonuses || 'Trait Value Bonuses')}</p>
      <div class="space-y-2 text-sm text-slate-300">
        ${streakEntries.length ? streakEntries.map(entry => `
          <div class="flex items-center justify-between gap-3 rounded-lg border border-white/10 bg-slate-950/50 px-3 py-2">
            <span>${escapeHtml((ui.traitsSelectedCount || '{count}+ traits selected').replace('{count}', entry.threshold))}</span>
            <span class="font-bold text-emerald-400">${escapeHtml((ui.valueBonus || '{multiplier}x value bonus').replace('{multiplier}', entry.multiplier))}</span>
          </div>
        `).join('') : `<p class="text-xs text-slate-500">${escapeHtml(ui.traitBonusEmpty || 'Trait bonus data will appear after calculator sync.')}</p>`}
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
      bonusBox.innerHTML = `<p class="text-xs text-slate-500">${escapeHtml(ui.traitBonusEmpty || 'Trait bonus data will appear after calculator sync.')}</p>`;
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
                ${trait.image ? `<img src="${escapeHtml(trait.image)}" alt="">` : ''}
                <span>${escapeHtml(trait.name)}</span>
              </span>
            `).join('')}
          </div>
        </div>
      `;
    }).join('');
  }

  function serializeSide(items) {
    return items.map((item) => ({
      slug: item.brainrot.slug,
      brainrot: { slug: item.brainrot.slug, name: item.brainrot.name, image: item.brainrot.image || '' },
      mutation: item.mutation,
      traits: item.traits,
      quantity: item.quantity,
    }));
  }

  async function hydrateItems(items) {
    if (!Array.isArray(items)) return [];
    const hydrated = await Promise.all(items.map(async (item) => {
      const brainrot = findBrainrot(item);
      if (!brainrot) return null;
      const detailedBrainrot = await loadBrainrotDetails(brainrot);
      const mutation = detailedBrainrot.mutations.find((row) => row.id === item.mutation?.id || row.name === item.mutation?.name)
        || item.mutation
        || detailedBrainrot.mutations[0];
      const traits = (item.traits || []).map((trait) => (
        (data.traits || []).find((row) => row.id === trait.id || row.name === trait.name) || trait
      ));
      return {
        brainrot: detailedBrainrot,
        mutation,
        traits,
        quantity: Math.max(1, Number(item.quantity) || 1),
      };
    }));

    return hydrated.filter(Boolean).slice(0, maxItems);
  }

  function persistDraft() {
    if (layout !== 'grid') return;
    try {
      window.localStorage.setItem(draftKey, JSON.stringify({
        offer: serializeSide(state.offer),
        receive: serializeSide(state.receive),
      }));
    } catch (error) {
      // Ignore quota / private mode.
    }
  }

  function clearDraft() {
    try {
      window.localStorage.removeItem(draftKey);
    } catch (error) {
      // Ignore.
    }
  }

  async function restoreDraft() {
    if (layout !== 'grid') return;
    try {
      const raw = window.localStorage.getItem(draftKey);
      if (!raw) return;
      const draft = JSON.parse(raw);
      state.offer = await hydrateItems(draft.offer);
      state.receive = await hydrateItems(draft.receive);
    } catch (error) {
      // Ignore bad drafts.
    }
  }

  function openAuthModal() {
    if (typeof window.openSabAuthModal === 'function') {
      window.openSabAuthModal({
        title: 'Create Trade Ads',
        copy: 'Sign in to create and manage your trade advertisements'
      });
      return;
    }
    if (!authModal) return;
    authModal.classList.add('is-open');
    authModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('trades-auth-modal-open');
  }

  function closeAuthModal() {
    if (!authModal) return;
    authModal.classList.remove('is-open');
    authModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('trades-auth-modal-open');
  }

  function applyPublishGate(me) {
    const bar = root.querySelector('[data-publish-bar]');
    if (!bar) return;
    const pending = bar.querySelector('[data-publish-pending]');
    const fields = bar.querySelector('[data-publish-fields]');
    const blocked = !!(me && me.user && me.user.can_post === false);
    bar.classList.toggle('trades-publish-bar--pending', blocked);
    if (pending) {
      pending.hidden = !blocked;
      pending.textContent = blocked ? 'This account is waiting for posting approval.' : '';
    }
    if (fields) fields.hidden = blocked;
  }

  async function publishTrade() {
    const status = root.querySelector('[data-publish-status]');
    if (status) status.textContent = '';
    const me = window.__sabMe;
    persistDraft();
    if (!me || !me.user) {
      openAuthModal();
      return;
    }
    if (me.user.can_post === false) {
      applyPublishGate(me);
      return;
    }
    if (!state.offer.length || !state.receive.length) {
      if (status) status.textContent = 'Add at least one item to each side before publishing.';
      return;
    }
    const serialize = (items) => items.map((item) => {
      const calc = calcItem(item);
      const traits = (item.traits || []).filter((trait) => trait && trait.name);
      return {
        brainrot: { slug: item.brainrot.slug, name: item.brainrot.name, image: item.brainrot.image || '' },
        mutation: item.mutation,
        traits: traits.map((trait) => ({
          name: trait.name,
          multiplier: trait.multiplier,
          valueMultiplier: trait.valueMultiplier,
        })),
        trait_names: traits.map((trait) => trait.name),
        quantity: item.quantity,
        value: calc.value,
      };
    });
    try {
      const response = await fetch(publishUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || csrf,
        },
        body: JSON.stringify({
          offering: serialize(state.offer),
          looking_for: serialize(state.receive),
        }),
      });
      const payload = await response.json().catch(() => ({}));
      if (!payload.success) {
        throw new Error(payload.error?.message || payload.message || 'Publish failed');
      }
      clearDraft();
      window.location.href = payload.data?.redirect || '/';
    } catch (error) {
      if (status) status.textContent = error.message;
    }
  }

  const swapBtn = root.querySelector('[data-swap]');
  if (swapBtn) {
    swapBtn.addEventListener('click', () => {
      const old = state.offer;
      state.offer = state.receive;
      state.receive = old;
      persistDraft();
      render();
    });
  }
  root.querySelectorAll('[data-clear]').forEach((clearBtn) => {
    clearBtn.addEventListener('click', () => {
      state.offer = [];
      state.receive = [];
      clearDraft();
      render();
    });
  });
  root.querySelector('[data-close]')?.addEventListener('click', closeModal);
  root.querySelector('[data-change]')?.addEventListener('click', () => renderModal(true));
  root.querySelector('[data-recalculate]')?.addEventListener('click', () => {
    state.mutation = state.selected.mutations[0];
    state.traits = [];
    renderModal(false);
  });
  configTabs.forEach(tab => tab.addEventListener('click', () => {
    state.configTab = tab.dataset.configTab;
    renderConfigTabs();
  }));
  root.querySelector('[data-save]')?.addEventListener('click', () => {
    if (!state.selected) return;
    if (state.editIndex === null && state[state.modalSide].length >= maxItems) {
      closeModal();
      return;
    }
    const item = {
      brainrot: state.selected,
      mutation: state.mutation,
      traits: [...state.traits],
      quantity: state.editIndex === null ? 1 : state[state.modalSide][state.editIndex].quantity,
    };
    if (state.editIndex === null) state[state.modalSide].push(item);
    else state[state.modalSide][state.editIndex] = item;
    closeModal();
    persistDraft();
    render();
  });
  root.querySelector('[data-sort-traits]')?.addEventListener('click', () => {
    state.traitDescending = !state.traitDescending;
    renderTraits();
  });
  const helpToggle = root.querySelector('[data-help-toggle]');
  const helpPanel = root.querySelector('[data-help]');
  if (helpToggle && helpPanel) {
    helpToggle.addEventListener('click', () => helpPanel.classList.toggle('hidden'));
  }
  searchBrainrot?.addEventListener('input', renderBrainrotGrid);
  searchTrait?.addEventListener('input', renderTraits);
  modal?.addEventListener('click', event => { if (event.target === modal) closeModal(); });

  if (authModal) {
    authModal.querySelectorAll('[data-auth-modal-close]').forEach((btn) => {
      btn.addEventListener('click', closeAuthModal);
    });
    authModal.addEventListener('click', (event) => {
      if (event.target === authModal) closeAuthModal();
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && authModal.classList.contains('is-open')) {
        closeAuthModal();
      }
    });
    authModal.querySelectorAll('form').forEach((form) => {
      form.addEventListener('submit', persistDraft);
    });
  }

  applyPublishGate(window.__sabMe);
  window.addEventListener('sab-nav-auth', (event) => applyPublishGate(event.detail));
  root.querySelectorAll('[data-publish-trade]').forEach((publishBtn) => {
    publishBtn.addEventListener('click', publishTrade);
  });

  renderHelpSection();
  await restoreDraft();

  const initialSlug = new URLSearchParams(window.location.search).get('item');
  if (initialSlug && !state.offer.length) {
    const brainrot = data.brainrots.find(b => b.slug === initialSlug);
    if (brainrot) {
      try {
        const detailedBrainrot = await loadBrainrotDetails(brainrot);
        state.offer.push({
          brainrot: detailedBrainrot,
          mutation: detailedBrainrot.mutations[0],
          traits: [],
          quantity: 1,
        });
      } catch (error) {
        showCatalogError(error);
      }
    }
  }

  render();
})();
