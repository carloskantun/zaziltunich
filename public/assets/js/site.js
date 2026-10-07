// Deslizadores sencillos (flechas + desplazamiento por pasos).
document.querySelectorAll('[data-slider], .slider').forEach(function (s) {
  var track = s.querySelector('.slides');
  if (!track) return;
  var step = function (d) { track.scrollBy({ left: d * track.clientWidth, behavior: 'smooth' }); };
  var p = s.querySelector('.prev'), n = s.querySelector('.next');
  if (p) p.addEventListener('click', function () { step(-1); });
  if (n) n.addEventListener('click', function () { step(1); });
});

// Pestañas de contenido de página.
document.querySelectorAll('.wtabs').forEach(function (w) {
  var tabs = w.querySelectorAll(':scope > .wtab-list > .wtab'), panels = w.querySelectorAll(':scope > .wtab-panel');
  tabs.forEach(function (t, i) {
    t.addEventListener('click', function () {
      tabs.forEach(function (x, j) { x.classList.toggle('on', i === j); });
      panels.forEach(function (x, j) { x.classList.toggle('on', i === j); });
    });
  });
});
