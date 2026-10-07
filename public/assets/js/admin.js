/* Panel: filas repetibles (horarios, tarifas, variantes, extras). Cada contenedor [data-repeater] tiene un <template>. */
(function () {
  document.addEventListener('click', function (ev) {
    var add = ev.target.closest('.rep-add');
    if (add) {
      var box = add.closest('[data-repeater]');
      var tpl = box && box.querySelector(':scope > template');
      if (!tpl) return;
      var token = box.getAttribute('data-token') || '__i__';
      var idx = Date.now().toString(36) + Math.floor(Math.random() * 1000);
      var wrap = document.createElement('div');
      wrap.innerHTML = tpl.innerHTML.split(token).join(idx);
      while (wrap.firstElementChild) box.insertBefore(wrap.firstElementChild, add);
      return;
    }
    var del = ev.target.closest('.rep-del');
    if (del) {
      var row = del.closest('.rep-row');
      if (row) row.remove();
    }
  });
})();
