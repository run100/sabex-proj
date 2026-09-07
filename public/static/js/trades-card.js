(function () {
  var openTip = null;

  function hideTip(slot) {
    if (!slot) return;
    var tip = slot.querySelector('.trades-item-tip');
    if (tip) tip.hidden = true;
    slot.classList.remove('is-tip-open');
    if (openTip === slot) openTip = null;
  }

  function showTip(slot) {
    if (openTip && openTip !== slot) hideTip(openTip);
    var tip = slot.querySelector('.trades-item-tip');
    if (!tip) return;
    tip.hidden = false;
    slot.classList.add('is-tip-open');
    openTip = slot;
  }

  document.addEventListener('click', function (event) {
    var slot = event.target.closest('[data-item-tip]');
    if (!slot || !document.body.contains(slot)) {
      hideTip(openTip);
      return;
    }
    if (slot.classList.contains('is-tip-open')) {
      hideTip(slot);
      return;
    }
    showTip(slot);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') hideTip(openTip);
  });
})();
