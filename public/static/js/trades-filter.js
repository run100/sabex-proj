(function () {
  var form = document.querySelector('[data-trades-filter]');
  if (!form) return;

  form.querySelectorAll('[data-filter-field]').forEach(bindField);

  function bindField(field) {
    var hidden = field.querySelector('input[type="hidden"]');
    var input = field.querySelector('[data-filter-q]');
    var list = field.querySelector('[data-filter-results]');
    var clearBtn = field.querySelector('[data-filter-clear]');
    var timer = 0;
    var lastQ = '';

    function toggleClear() {
      var selected = !!(hidden && hidden.value);
      field.classList.toggle('is-active', selected);
      if (!clearBtn) return;
      clearBtn.hidden = !selected && !String(input.value || '').trim();
    }

    function hideList() {
      list.hidden = true;
    }

    function search(q) {
      if (q === lastQ && list.childElementCount) {
        list.hidden = false;
        return;
      }
      lastQ = q;
      fetch('/api/v1/brainrots/search?q=' + encodeURIComponent(q) + '&limit=8', {
        headers: { Accept: 'application/json' },
      })
        .then(function (response) { return response.json(); })
        .then(function (payload) {
          var items = payload && payload.data && payload.data.items ? payload.data.items : [];
          render(items);
        })
        .catch(hideList);
    }

    function render(items) {
      list.replaceChildren();
      if (!items.length) {
        hideList();
        return;
      }
      items.forEach(function (item) {
        var li = document.createElement('li');
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'trades-filter__option';
        if (item.image) {
          var img = document.createElement('img');
          img.src = item.image;
          img.alt = '';
          img.width = 28;
          img.height = 28;
          button.appendChild(img);
        }
        var name = document.createElement('span');
        name.textContent = item.name || item.slug || '';
        button.appendChild(name);
        button.addEventListener('click', function () {
          hidden.value = String(item.id || '');
          input.value = item.name || item.slug || '';
          hideList();
          toggleClear();
          form.submit();
        });
        li.appendChild(button);
        list.appendChild(li);
      });
      list.hidden = false;
    }

    input.addEventListener('input', function () {
      hidden.value = '';
      toggleClear();
      window.clearTimeout(timer);
      var q = String(input.value || '').trim();
      if (q.length < 1) {
        hideList();
        return;
      }
      timer = window.setTimeout(function () { search(q); }, 220);
    });

    input.addEventListener('focus', function () {
      var q = String(input.value || '').trim();
      if (q && !hidden.value) search(q);
    });

    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        hidden.value = '';
        input.value = '';
        hideList();
        toggleClear();
        form.submit();
      });
    }

    document.addEventListener('click', function (event) {
      if (!field.contains(event.target)) hideList();
    });

    toggleClear();
  }
})();
