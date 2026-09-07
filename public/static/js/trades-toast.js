(function () {
  function fallbackCopy(value) {
    var field = document.createElement('textarea');
    field.value = value;
    field.setAttribute('readonly', '');
    field.style.position = 'fixed';
    field.style.opacity = '0';
    document.body.appendChild(field);
    field.select();
    var copied = false;
    try {
      copied = document.execCommand('copy');
    } catch (error) {
      copied = false;
    }
    document.body.removeChild(field);
    return copied;
  }

  function copyText(value) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(value).then(function () {
        return true;
      }).catch(function () {
        return fallbackCopy(value);
      });
    }
    return Promise.resolve(fallbackCopy(value));
  }

  function tradesToast(message) {
    if (window.toast && typeof window.toast.success === 'function') {
      window.toast.success(message, { position: 'top-right' });
    }
  }

  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-copy]');
    if (!button) return;
    var raw = button.getAttribute('data-copy') || '';
    var value = raw === 'href' ? window.location.href : raw;
    if (!value) return;
    copyText(value).then(function (copied) {
      if (!copied) return;
      button.setAttribute('data-copied', '1');
      var label = button.querySelector('[data-share-text]');
      var original = label ? label.textContent : '';
      if (label) label.textContent = 'Copied';
      var toastMessage = button.getAttribute('data-toast');
      if (toastMessage) tradesToast(toastMessage);
      window.setTimeout(function () {
        button.removeAttribute('data-copied');
        if (label) label.textContent = original || 'Share Trade';
      }, 1600);
    });
  });
})();
