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


/* Carrusel de reseñas: bucle infinito, 4/2/1 visibles, avance cada 5 s, puntos */
(function () {
  var box = document.querySelector('.rv-slider');
  if (!box) return;
  var track = box.querySelector('.rv-track'), dotsBox = box.querySelector('.rv-dots');
  var slides = [].slice.call(track.children), n = slides.length;
  if (!n) return;
  var ms = parseInt(box.getAttribute('data-ms') || '5000', 10), i = 0, vis = 4, timer = null, paused = false;
  function perView() { var w = window.innerWidth; return w <= 640 ? 1 : (w <= 1100 ? 2 : 4); }
  slides.forEach(function (_, k) {
    var b = document.createElement('button'); b.type = 'button'; b.setAttribute('aria-label', String(k + 1));
    b.addEventListener('click', function () { go(k); restart(); });
    dotsBox.appendChild(b);
  });
  function build() {
    [].slice.call(track.querySelectorAll('.clone')).forEach(function (c) { c.remove(); });
    vis = perView();
    for (var k = 0; k < Math.min(vis, n); k++) { var c = slides[k].cloneNode(true); c.className += ' clone'; c.setAttribute('aria-hidden', 'true'); track.appendChild(c); }
    set(i, false);
  }
  function set(idx, anim) {
    track.classList.toggle('snap', !anim);
    track.style.transform = 'translateX(' + (-idx * 100 / vis) + '%)';
    var act = ((idx % n) + n) % n;
    [].forEach.call(dotsBox.children, function (d, k) { d.classList.toggle('on', k === act); });
  }
  function go(idx) { i = idx; set(i, true); }
  function next() {
    i += 1; set(i, true);
    if (i >= n) { setTimeout(function () { i = 0; set(0, false); }, 520); }
  }
  function restart() { clearInterval(timer); if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) timer = setInterval(function () { if (!paused) next(); }, ms); }
  box.addEventListener('mouseenter', function () { paused = true; });
  box.addEventListener('mouseleave', function () { paused = false; });
  window.addEventListener('resize', build);
  build(); restart();
})();
