(function () {
  if (!window.Vue) return;

  var app = Vue.createApp({
    template: '<Toaster theme="dark" position="top-right" :rich-colors="true" :offset="{ top: 16, right: 0 }" :mobile-offset="{ top: 16, right: 0 }" />',
  });
  if (window.ElementPlus) {
    app.use(ElementPlus);
  }
  if (window.VueSonner) {
    app.use(VueSonner);
  }
  app.mount('#trades-ui');

  window.tradesToast = function (message) {
    if (window.toast && typeof window.toast.success === 'function') {
      window.toast.success(message, { position: 'top-right' });
    }
  };
})();
