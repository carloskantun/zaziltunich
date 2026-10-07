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

// Portada: diapositivas de fondo que cambian solas.
document.querySelectorAll('.hero-slides').forEach(function (box) {
  var sl = box.querySelectorAll('.hs');
  if (sl.length < 2) return;
  var i = 0, ms = parseInt(box.getAttribute('data-ms'), 10) || 5000;
  setInterval(function () {
    sl[i].classList.remove('on');
    i = (i + 1) % sl.length;
    sl[i].classList.add('on');
  }, ms);
});

// Galerías: mosaico con aparición escalonada, zoom suave y visor a pantalla completa.
(function () {
  document.documentElement.classList.add('js');
  var groups = document.querySelectorAll('.gal-grid, .gallery');
  if (!groups.length) return;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  groups.forEach(function (g) {
    var imgs = g.querySelectorAll('img');
    imgs.forEach(function (img) {
      if (img.parentNode.classList.contains('gi')) return;
      var w = document.createElement('span');
      w.className = 'gi';
      w.tabIndex = 0;
      w.setAttribute('role', 'button');
      img.parentNode.insertBefore(w, img);
      w.appendChild(img);
    });
    var items = g.querySelectorAll('.gi');
    if (!('IntersectionObserver' in window) || reduce) {
      items.forEach(function (i) { i.classList.add('in'); });
    } else {
      var io = new IntersectionObserver(function (es) {
        es.forEach(function (e) {
          if (!e.isIntersecting) return;
          var idx = Array.prototype.indexOf.call(items, e.target);
          e.target.style.transitionDelay = ((idx % 4) * 90) + 'ms';
          e.target.classList.add('in');
          io.unobserve(e.target);
        });
      }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
      items.forEach(function (i) { io.observe(i); });
    }
    items.forEach(function (it, n) {
      var open = function () { lightbox(items, n); };
      it.addEventListener('click', open);
      it.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); } });
    });
  });

  function lightbox(items, start) {
    var i = start;
    var lb = document.createElement('div');
    lb.className = 'lb';
    lb.innerHTML = '<button class="lb-x" aria-label="Cerrar">×</button><button class="lb-p" aria-label="Anterior">‹</button><img alt=""><button class="lb-n" aria-label="Siguiente">›</button><span class="lb-c"></span>';
    var im = lb.querySelector('img'), c = lb.querySelector('.lb-c');
    function show() {
      var src = items[i].querySelector('img').currentSrc || items[i].querySelector('img').src;
      im.classList.remove('on');
      setTimeout(function () { im.src = src; im.onload = function () { im.classList.add('on'); }; if (im.complete) im.classList.add('on'); }, 120);
      c.textContent = (i + 1) + ' / ' + items.length;
    }
    function go(d) { i = (i + d + items.length) % items.length; show(); }
    function close() { document.removeEventListener('keydown', key); lb.classList.remove('open'); setTimeout(function () { lb.remove(); document.body.classList.remove('lb-lock'); }, 250); }
    function key(e) { if (e.key === 'Escape') close(); else if (e.key === 'ArrowRight') go(1); else if (e.key === 'ArrowLeft') go(-1); }
    lb.querySelector('.lb-x').addEventListener('click', close);
    lb.querySelector('.lb-p').addEventListener('click', function (e) { e.stopPropagation(); go(-1); });
    lb.querySelector('.lb-n').addEventListener('click', function (e) { e.stopPropagation(); go(1); });
    lb.addEventListener('click', function (e) { if (e.target === lb) close(); });
    var sx = 0;
    lb.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', function (e) { var d = e.changedTouches[0].clientX - sx; if (Math.abs(d) > 50) go(d < 0 ? 1 : -1); });
    document.addEventListener('keydown', key);
    document.body.appendChild(lb);
    document.body.classList.add('lb-lock');
    requestAnimationFrame(function () { lb.classList.add('open'); });
    show();
  }
})();
