<script>
(function () {
  'use strict';
  var root = document.querySelector('.sab-wiki');
  if (!root) return;

  var search = root.querySelector('[data-wiki-search]');
  var sort = root.querySelector('[data-wiki-sort]');
  var result = root.querySelector('[data-wiki-result]');
  var buttons = Array.from(root.querySelectorAll('[data-wiki-rarity]'));
  var sections = Array.from(root.querySelectorAll('[data-wiki-section]'));

  // Schedule cards are server-rendered first; local timezone and countdown
  // are progressive enhancements and never the only way to read the time.
  var localTimes = Array.from(root.querySelectorAll('[data-admin-abuse-local]'));
  localTimes.forEach(function (time) {
    var target = new Date(time.getAttribute('datetime') || '');
    if (Number.isNaN(target.getTime())) return;
    try {
      time.textContent = new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium', timeStyle: 'short'
      }).format(target);
    } catch (error) {
      time.textContent = target.toLocaleString();
    }
  });
  var countdowns = Array.from(root.querySelectorAll('[data-admin-abuse-countdown]'));
  function renderCountdown(node) {
    var target = new Date(node.getAttribute('data-target') || '');
    if (Number.isNaN(target.getTime())) {
      node.textContent = 'Not confirmed';
      return;
    }
    var seconds = Math.floor((target.getTime() - Date.now()) / 1000);
    if (seconds <= 0) {
      node.textContent = 'Live now or starting soon';
      return;
    }
    var days = Math.floor(seconds / 86400);
    seconds %= 86400;
    var hours = Math.floor(seconds / 3600);
    seconds %= 3600;
    var minutes = Math.floor(seconds / 60);
    var parts = [];
    if (days) parts.push(days + 'd');
    if (hours || days) parts.push(hours + 'h');
    parts.push(minutes + 'm');
    node.textContent = 'Starts in ' + parts.join(' ');
  }
  countdowns.forEach(renderCountdown);
  if (countdowns.length) window.setInterval(function () { countdowns.forEach(renderCountdown); }, 30000);

  if (!search || !sort || !result || sections.length === 0) return;
  var activeRarity = 'all';

  function numeric(row, key) {
    var raw = row.getAttribute('data-sort-' + key);
    if (raw === null || raw === '') return null;
    var value = Number(raw);
    return Number.isFinite(value) ? value : null;
  }

  function compareRows(a, b, mode) {
    if (mode === 'name-asc') return (a.dataset.name || '').localeCompare(b.dataset.name || '');
    var parts = mode.split('-');
    var av = numeric(a, parts[0]);
    var bv = numeric(b, parts[0]);
    if (av === null && bv === null) return (a.dataset.name || '').localeCompare(b.dataset.name || '');
    if (av === null) return 1;
    if (bv === null) return -1;
    var delta = parts[1] === 'asc' ? av - bv : bv - av;
    return delta || (a.dataset.name || '').localeCompare(b.dataset.name || '');
  }

  function apply() {
    var query = (search.value || '').trim().toLowerCase();
    var shown = 0;
    sections.forEach(function (section) {
      var rarityMatches = activeRarity === 'all' || section.dataset.rarity === activeRarity;
      var body = section.querySelector('[data-wiki-tbody]');
      var rows = Array.from(body.querySelectorAll('[data-wiki-row]'));
      rows.sort(function (a, b) { return compareRows(a, b, sort.value); }).forEach(function (row) { body.appendChild(row); });
      var sectionShown = 0;
      rows.forEach(function (row) {
        var matches = rarityMatches && (!query || (row.dataset.search || '').includes(query));
        row.classList.toggle('sab-wiki-hidden', !matches);
        if (matches) { sectionShown += 1; shown += 1; }
      });
      section.classList.toggle('sab-wiki-hidden', sectionShown === 0);
      var count = section.querySelector('[data-section-count]');
      if (count) count.textContent = sectionShown.toLocaleString();
    });
    result.textContent = shown.toLocaleString() + (shown === 1 ? ' Brainrot shown' : ' Brainrots shown');
  }

  search.addEventListener('input', apply);
  sort.addEventListener('change', apply);
  buttons.forEach(function (button) {
    button.addEventListener('click', function () {
      activeRarity = button.dataset.wikiRarity || 'all';
      buttons.forEach(function (candidate) { candidate.classList.toggle('is-active', candidate === button); });
      apply();
    });
  });
  apply();
})();
</script>
