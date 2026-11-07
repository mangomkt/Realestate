(function () {
  // Mobile nav
  var t = document.querySelector('.nav-toggle'), n = document.getElementById('nav');
  if (t && n) t.addEventListener('click', function () {
    var open = n.classList.toggle('open');
    t.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  // Gallery thumbnails
  var main = document.getElementById('main-photo');
  document.querySelectorAll('.thumbs button').forEach(function (b, i) {
    if (i === 0) b.classList.add('on');
    b.addEventListener('click', function () {
      main.src = b.dataset.full;
      if (b.dataset.alt) main.alt = b.dataset.alt;
      document.querySelectorAll('.thumbs button').forEach(function (x) { x.classList.remove('on'); });
      b.classList.add('on');
    });
  });

  // Confirm destructive admin actions
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!confirm(f.dataset.confirm)) e.preventDefault();
    });
  });
})();
