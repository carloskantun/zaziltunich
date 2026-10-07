/* Widget de reserva: calendario, horarios, personas, extras y total en vivo. El servidor recalcula todo. */
(function () {
  var form = document.getElementById('zt-form');
  if (!form) return;
  var cfg = JSON.parse(form.dataset.config);
  var T = cfg.t;
  var $ = function (id) { return document.getElementById(id); };
  var elCal = $('zt-cal'), elHint = $('zt-hint'), elSlots = $('zt-slots'), submit = $('zt-submit');
  var fDate = $('f-date'), fEnd = $('f-end'), fTime = $('f-time'), fPax = $('f-pax');
  var lodging = cfg.kind === 'lodging';
  var month = new Date(); month.setDate(1);
  var avail = {}, timer = null, reqId = 0;

  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function ym(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1); }
  function iso(y, m, d) { return y + '-' + pad(m) + '-' + pad(d); }
  function todayIso() { var n = new Date(); return iso(n.getFullYear(), n.getMonth() + 1, n.getDate()); }
  function monthName(d) { return d.toLocaleDateString(cfg.lang === 'en' ? 'en-US' : 'es-MX', { month: 'long', year: 'numeric' }); }

  function loadMonth() {
    var key = ym(month);
    if (avail[key]) return render();
    fetch(cfg.api.month + '?exp=' + cfg.id + '&month=' + key).then(function (r) { return r.json(); }).then(function (j) {
      avail[key] = j.days || {}; render();
    });
  }

  function render() {
    var key = ym(month), days = avail[key] || {};
    var first = new Date(month.getFullYear(), month.getMonth(), 1);
    var offset = (first.getDay() + 6) % 7;
    var total = new Date(month.getFullYear(), month.getMonth() + 1, 0).getDate();
    var html = '<div class="cal-head"><button type="button" data-nav="-1" aria-label="' + T.prev + '">‹</button><strong>' + monthName(month) + '</strong><button type="button" data-nav="1" aria-label="' + T.next + '">›</button></div><div class="cal-grid">';
    T.days.forEach(function (d) { html += '<span class="dow">' + d + '</span>'; });
    for (var i = 0; i < offset; i++) html += '<span></span>';
    for (var d = 1; d <= total; d++) {
      var date = iso(month.getFullYear(), month.getMonth() + 1, d);
      var ok = !!days[date], cls = 'day';
      if (lodging && fDate.value && !fEnd.value && date > fDate.value && date >= todayIso()) ok = true;
      if (!ok) cls += ' off';
      if (date === fDate.value || date === fEnd.value) cls += ' sel';
      if (lodging && fDate.value && fEnd.value && date > fDate.value && date < fEnd.value) cls += ' mid';
      html += '<button type="button" class="' + cls + '" data-date="' + date + '"' + (ok ? '' : ' disabled') + '>' + d + '</button>';
    }
    elCal.innerHTML = html + '</div>';
  }

  elCal.addEventListener('click', function (ev) {
    var nav = ev.target.closest('[data-nav]');
    if (nav) { month.setMonth(month.getMonth() + parseInt(nav.dataset.nav, 10)); return loadMonth(); }
    var b = ev.target.closest('[data-date]');
    if (!b || b.disabled) return;
    var date = b.dataset.date;
    if (lodging) {
      if (!fDate.value || fEnd.value || date <= fDate.value) { fDate.value = date; fEnd.value = ''; elHint.textContent = T.checkout + '…'; }
      else { fEnd.value = date; elHint.textContent = ''; }
      render(); schedule(); return;
    }
    fDate.value = date; fTime.value = '';
    render(); loadSlots(date);
  });

  function loadSlots(date) {
    elSlots.innerHTML = '';
    fetch(cfg.api.slots + '?exp=' + cfg.id + '&date=' + date).then(function (r) { return r.json(); }).then(function (j) {
      var html = '';
      (j.slots || []).forEach(function (s) {
        var full = s.left <= 0;
        html += '<button type="button" class="slot' + (full ? ' off' : '') + '" data-time="' + s.time + '"' + (full ? ' disabled' : '') + '>' + s.time + '<small>' + (full ? T.full : T.left.replace('{n}', s.left)) + '</small></button>';
      });
      elSlots.innerHTML = html; elHint.textContent = html ? '' : T.pickDate; schedule();
    });
  }

  elSlots.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-time]');
    if (!b || b.disabled) return;
    fTime.value = b.dataset.time;
    Array.prototype.forEach.call(elSlots.children, function (c) { c.classList.toggle('sel', c === b); });
    schedule();
  });

  // Personas: pasos y filtrado de opciones por rango.
  form.addEventListener('click', function (ev) {
    var st = ev.target.closest('[data-step]');
    if (!st || !fPax) return;
    var v = (parseInt(fPax.value, 10) || 1) + parseInt(st.dataset.step, 10);
    fPax.value = Math.max(parseInt(fPax.min || 1, 10), Math.min(parseInt(fPax.max || 99, 10), v));
    filterOptions(); schedule();
  });
  function filterOptions() {
    var pax = parseInt(fPax.value, 10) || 1;
    form.querySelectorAll('[data-min],[data-max]').forEach(function (el) {
      var mn = el.dataset.min === '' ? 0 : parseInt(el.dataset.min, 10), mx = el.dataset.max === '' ? 1e9 : parseInt(el.dataset.max, 10);
      var ok = pax >= mn && pax <= mx;
      if (el.tagName === 'OPTION') { el.hidden = !ok; el.disabled = !ok; if (!ok && el.selected) el.parentNode.value = '0'; }
      else { el.hidden = !ok; if (!ok) el.querySelectorAll('input').forEach(function (i) { if (i.type === 'checkbox') i.checked = false; else i.value = 0; }); }
    });
  }
  form.addEventListener('input', function (ev) { if (ev.target.hasAttribute('data-live')) { if (ev.target === fPax) filterOptions(); schedule(); } });
  form.addEventListener('change', function (ev) { if (ev.target.hasAttribute('data-live')) schedule(); });

  function ready() {
    if (!fDate.value) return false;
    if (lodging) return !!fEnd.value;
    return !!fTime.value;
  }

  function schedule() { clearTimeout(timer); timer = setTimeout(quote, 200); }

  function quote() {
    var totals = $('zt-totals');
    if (!ready()) { totals.hidden = true; submit.disabled = true; return; }
    var my = ++reqId;
    fetch(cfg.api.quote, { method: 'POST', body: new FormData(form) }).then(function (r) { return r.json(); }).then(function (q) {
      if (my !== reqId) return;
      totals.hidden = false;
      var html = '';
      (q.lines || []).concat(q.extras || []).forEach(function (l) { html += '<p class="line"><span>' + esc(l.label) + '</span><span>' + l.amount + '</span></p>'; });
      $('zt-lines').innerHTML = html;
      $('zt-total').textContent = q.total;
      $('zt-dep').hidden = !q.has_deposit;
      $('zt-deposit').textContent = q.deposit; $('zt-balance').textContent = q.balance;
      $('zt-err').textContent = (q.errors || []).join(' ');
      submit.disabled = !q.ok;
    });
  }
  function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

  document.querySelectorAll('.tab').forEach(function (t) {
    t.addEventListener('click', function () {
      document.querySelectorAll('.tab').forEach(function (x) { x.classList.toggle('on', x === t); });
      document.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.toggle('on', p.dataset.panel === t.dataset.tab); });
    });
  });

  if (fPax) filterOptions();
  loadMonth();
})();

/* Galería de la ficha: miniaturas cambian la imagen principal. */
(function () {
  var main = document.getElementById('gal-img');
  if (!main) return;
  document.querySelectorAll('.gal-thumbs button').forEach(function (b) {
    b.addEventListener('click', function () {
      main.src = b.dataset.src;
      document.querySelectorAll('.gal-thumbs button').forEach(function (x) { x.classList.toggle('on', x === b); });
    });
  });
})();
