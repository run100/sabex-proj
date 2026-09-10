(function () {
  var ME_URL = '/api/v1/me';
  var LOGOUT_URL = '/api/v1/auth/logout';
  var DEFAULT_AVATAR = '/static/img/trades-default-avatar.webp';

  function icon(name) {
    var paths = {
      bell: '<path d="M6 8a6 6 0 1 1 12 0c0 7 3 7 3 7H3s3 0 3-7"/><path d="M10 18a2 2 0 0 0 4 0"/>',
      calculator: '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h.01"/>',
      trades: '<path d="M8 3 4 7l4 4"/><path d="M4 7h16"/><path d="m16 21 4-4-4-4"/><path d="M20 17H4"/>',
      user: '<circle cx="12" cy="8" r="3"/><path d="M5 19a7 7 0 0 1 14 0"/>',
      settings: '<circle cx="12" cy="12" r="3"/><path d="M12 3v2M12 19v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M3 12h2M19 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
      logout: '<path d="M9 21H5V3h4"/><path d="M16 17 21 12 16 7"/><path d="M21 12H9"/>',
      login: '<path d="M15 3h4v18h-4"/><path d="M10 17 15 12 10 7"/><path d="M15 12H3"/>'
    };
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (paths[name] || '') + '</svg>';
  }

  function drawerLink(href, name, label, extra) {
    return '<a href="' + href + '" class="sab-wiki-drawer__link"' + (extra || '') + '><span class="sab-wiki-drawer__icon">' + icon(name) + '</span><span>' + label + '</span></a>';
  }

  function csrfHeaders() {
    var headers = { Accept: 'application/json' };
    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = meta ? meta.getAttribute('content') : '';
    if (token) {
      headers['X-CSRF-TOKEN'] = token;
      return headers;
    }
    var match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    if (match) {
      headers['X-XSRF-TOKEN'] = decodeURIComponent(match[1]);
    }
    return headers;
  }

  function displayName(user) {
    return user.display_name || user.username || 'Account';
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function loginHref(root) {
    return (root && root.getAttribute('data-login-href')) || '/auth/roblox';
  }

  function authModalEl() {
    return document.querySelector('[data-roblox-auth-modal]');
  }

  function openAuthModal(options) {
    var modal = authModalEl();
    if (!modal) return false;
    options = options || {};
    var title = modal.querySelector('[data-auth-modal-title]');
    var copy = modal.querySelector('[data-auth-modal-copy]');
    if (title) title.textContent = options.title || modal.getAttribute('data-default-title') || 'Sign in';
    if (copy) copy.textContent = options.copy || modal.getAttribute('data-default-copy') || 'Sign in to post and manage trade ads.';
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('trades-auth-modal-open');
    return true;
  }

  function closeAuthModal() {
    var modal = authModalEl();
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('trades-auth-modal-open');
  }

  window.openSabAuthModal = openAuthModal;

  function tradesBadge(openCount) {
    var count = Number(openCount) || 0;
    if (count <= 0) return '';
    return '<span class="sab-nav-auth__badge">' + (count > 99 ? '99+' : count) + '</span>';
  }

  function tradesLabel(openCount) {
    var count = Number(openCount) || 0;
    return count > 0 ? 'Trades, ' + count + ' open' : 'Trades';
  }

  function currentPath() {
    return (window.location.pathname || '/').replace(/\/+$/, '') || '/';
  }

  function isTradesPath(path) {
    return path === '/trading' || path.indexOf('/trading/') === 0;
  }

  function isCalcPath(path) {
    return path.indexOf('steal-a-brainrot-trading-calculator') !== -1;
  }

  function currentMark(active) {
    return active ? ' is-active" aria-current="page' : '';
  }

  function quickLinks(openCount) {
    var path = currentPath();
    var tradesActive = isTradesPath(path);
    var calcActive = isCalcPath(path);
    return '<a href="/trading" class="sab-nav-auth__link sab-nav-auth__quick-link' + currentMark(tradesActive) + '" aria-label="' + tradesLabel(openCount) + '" title="Browse and post Steal a Brainrot trade ads">' + icon('trades') + tradesBadge(openCount) + '</a>' +
      '<a href="/steal-a-brainrot-trading-calculator" class="sab-nav-auth__link sab-nav-auth__quick-link sab-nav-auth__quick-link--calculator' + currentMark(calcActive) + '" aria-label="Calculator" title="Calculator">' + icon('calculator') + '</a>';
  }

  function renderHeader(root, user, unread, openCount) {
    if (!root) return;
    if (!user) {
      root.innerHTML = quickLinks(openCount) +
        '<a href="' + escapeHtml(loginHref(root)) + '" class="sab-nav-auth__signin" data-nav-sign-in>Login</a>';
      return;
    }
    var name = escapeHtml(displayName(user));
    var avatar = escapeHtml(user.avatar_url || DEFAULT_AVATAR);
    var profile = user.profile_path || '/user';
    var badge = unread > 0 ? '<span class="sab-nav-auth__badge">' + unread + '</span>' : '';
    root.innerHTML =
      quickLinks(openCount) +
      '<a href="/notifications" class="sab-nav-auth__link" aria-label="Alerts" title="Alerts">' + icon('bell') + badge + '</a>' +
      '<details class="sab-account-nav" data-sab-account-nav>' +
        '<summary class="sab-nav-auth__chip" aria-label="' + name + '">' +
          '<img class="sab-nav-auth__avatar" src="' + avatar + '" alt="" width="28" height="28">' +
        '</summary>' +
        '<div class="sab-account-nav__menu">' +
          '<div class="sab-account-nav__head">' +
            '<img class="sab-account-nav__photo" src="' + avatar + '" alt="" width="40" height="40">' +
            '<p class="sab-nav-auth__name">' + name + '</p>' +
          '</div>' +
          '<a href="' + escapeHtml(profile) + '" class="sab-account-nav__item">' + icon('user') + '<span>Profile</span></a>' +
          '<button type="button" class="sab-account-nav__item sab-account-nav__item--out" data-nav-sign-out>' + icon('logout') + '<span>Sign out</span></button>' +
        '</div>' +
      '</details>';
  }

  function renderDrawer(root, user, unread) {
    if (!root) return;
    if (!user) {
      root.innerHTML = drawerLink(loginHref(document.querySelector('[data-nav-auth]')), 'login', 'Login', ' data-nav-sign-in');
      return;
    }
    var name = escapeHtml(displayName(user));
    var profile = user.profile_path || '/user';
    var alerts = unread > 0 ? 'Alerts (' + unread + ')' : 'Alerts';
    root.innerHTML =
      drawerLink('/notifications', 'bell', alerts) +
      drawerLink(escapeHtml(profile), 'user', name) +
      '<button type="button" class="sab-wiki-drawer__link" data-nav-sign-out><span class="sab-wiki-drawer__icon">' + icon('logout') + '</span><span>Sign out</span></button>';
  }

  function renderBar(root, user, unread) {
    if (!root) return;
    if (!user) {
      root.innerHTML = '';
      return;
    }
    var alerts = 'Alerts' + (unread > 0 ? '<span class="trades-account-bar__badge">' + unread + '</span>' : '');
    root.innerHTML =
      '<a href="/notifications">' + alerts + '</a>' +
      '<a href="/user">Account</a>' +
      '<button type="button" data-nav-sign-out>Sign out</button>';
  }

  async function logout() {
    try {
      await fetch(LOGOUT_URL, {
        method: 'POST',
        credentials: 'same-origin',
        headers: Object.assign({ 'Content-Type': 'application/json' }, csrfHeaders())
      });
    } catch (error) {
      // Fall through to reload so the session cookie is re-read.
    }
    window.location.reload();
  }

  function bindLogout(root) {
    if (!root) return;
    root.addEventListener('click', function (event) {
      var button = event.target.closest('[data-nav-sign-out]');
      if (!button) return;
      event.preventDefault();
      logout();
    });
  }

  function applyMe(payload) {
    var user = payload && payload.user ? payload.user : null;
    var unread = payload && payload.unread_count ? Number(payload.unread_count) : 0;
    var openCount = payload && payload.open_listings_count ? Number(payload.open_listings_count) : 0;
    window.__sabMe = { user: user, unread_count: unread, open_listings_count: openCount };
    renderHeader(document.querySelector('[data-nav-auth]'), user, unread, openCount);
    renderDrawer(document.querySelector('[data-nav-auth-drawer]'), user, unread);
    renderBar(document.querySelector('[data-nav-auth-bar]'), user, unread);
    window.dispatchEvent(new CustomEvent('sab-nav-auth', { detail: window.__sabMe }));
  }

  document.querySelectorAll('[data-nav-auth], [data-nav-auth-drawer], [data-nav-auth-bar]').forEach(bindLogout);

  document.addEventListener('click', function (event) {
    var signIn = event.target.closest('[data-nav-sign-in]');
    if (signIn && openAuthModal()) {
      event.preventDefault();
      return;
    }
    var modal = authModalEl();
    if (!modal || !modal.classList.contains('is-open')) return;
    if (event.target.closest('[data-auth-modal-close]') || event.target === modal) {
      closeAuthModal();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeAuthModal();
  });

  fetch(ME_URL, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
    .then(function (response) { return response.json(); })
    .then(function (body) { applyMe(body && body.data ? body.data : {}); })
    .catch(function () { applyMe({ user: null, unread_count: 0, open_listings_count: 0 }); });
})();
