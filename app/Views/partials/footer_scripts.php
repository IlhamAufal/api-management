<script defer src="<?= base_url('assets/js/bundle.js') ?>"></script>
<script>
(function () {
  // Cegah bug Chromium di mana triple-click meluber dan memblok dari awal container/navbar
  document.addEventListener('mousedown', function (e) {
    if (e.button !== 0 || e.detail < 3) return;

    var el = e.target;
    if (el.closest('input, textarea, select, button, a')) return;

    var target = el.closest('.select-text, code, pre, p, h1, h2, h3, h4, h5, h6, li, [data-select-isolated]')
              || (el.nodeType === Node.TEXT_NODE ? el.parentElement : el);

    var cell = el.closest('td, th');
    if (cell && (!target || target === cell || target.tagName === 'TR' || target.tagName === 'TBODY' || target.tagName === 'TABLE')) {
      target = el.closest('code, span, p, b, strong') || cell;
    }

    if (target && target !== document.body && target !== document.documentElement) {
      e.preventDefault();
      var sel = window.getSelection();
      if (sel) {
        var range = document.createRange();
        range.selectNodeContents(target);
        sel.removeAllRanges();
        sel.addRange(range);
      }
    }
  });
})();
</script>
