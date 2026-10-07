// Deslizadores sencillos (flechas + desplazamiento por pasos).
document.querySelectorAll('[data-slider], .slider').forEach(function (s) {
  var track = s.querySelector('.slides');
  if (!track) return;
  var step = function (d) { track.scrollBy({ left: d * track.clientWidth, behavior: 'smooth' }); };
  var p = s.querySelector('.prev'), n = s.querySelector('.next');
  if (p) p.addEventListener('click', function () { step(-1); });
  if (n) n.addEventListener('click', function () { step(1); });
});
