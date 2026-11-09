(function () {
  // Mobile nav
  var t = document.querySelector('.nav-toggle'), n = document.getElementById('nav');
  if (t && n) t.addEventListener('click', function () {
    var open = n.classList.toggle('open');
    t.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  // Dropdown menus: click/tap to open (hover and keyboard focus also work through CSS)
  document.querySelectorAll('.nav-dd').forEach(function (dd) {
    var t = dd.querySelector('.dd-toggle');
    if (!t) return;
    t.addEventListener('click', function (e) {
      e.preventDefault();
      var open = dd.classList.toggle('open');
      t.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });
  document.addEventListener('click', function (e) {
    document.querySelectorAll('.nav-dd.open').forEach(function (dd) {
      if (!dd.contains(e.target)) { dd.classList.remove('open'); var t = dd.querySelector('.dd-toggle'); if (t) t.setAttribute('aria-expanded', 'false'); }
    });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.nav-dd').forEach(function (dd) {
      var t = dd.querySelector('.dd-toggle');
      var active = dd.classList.contains('open') || dd.contains(document.activeElement);
      if (!active) return;
      dd.classList.remove('open');
      dd.classList.add('dismissed'); // beats :hover / :focus-within until the pointer or focus leaves
      if (t) { t.setAttribute('aria-expanded', 'false'); t.focus(); }
    });
  });
  document.querySelectorAll('.nav-dd').forEach(function (dd) {
    function clear() { dd.classList.remove('dismissed'); }
    dd.addEventListener('mouseleave', clear);
    dd.addEventListener('focusout', function (e) { if (!dd.contains(e.relatedTarget)) clear(); });
  });

  // Light / dark toggle: remembers the visitor's choice; otherwise follows the site default.
  var tt = document.getElementById('theme-toggle');
  if (tt) {
    var root = document.documentElement;
    var mq = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    var current = function () {
      var a = root.getAttribute('data-theme');
      if (a === 'light' || a === 'dark') return a;
      return (root.getAttribute('data-default') === 'auto' && mq && mq.matches) ? 'dark' : 'light';
    };
    var paint = function () {
      var dark = current() === 'dark';
      tt.setAttribute('aria-pressed', dark ? 'true' : 'false');
      tt.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
      tt.title = tt.getAttribute('aria-label');
      tt.firstChild.textContent = dark ? '\u2600' : '\u263E';
    };
    tt.hidden = false;
    paint();
    tt.addEventListener('click', function () {
      var next = current() === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try { localStorage.setItem('re_theme', next); } catch (e) {}
      paint();
    });
    if (mq && mq.addEventListener) mq.addEventListener('change', paint);
  }

  // Mortgage estimate (Canadian mortgages compound semi-annually; others monthly)
  var mg = document.getElementById('mortgage');
  if (mg) {
    var ids = ['m-price', 'm-down', 'm-rate', 'm-years'].map(function (i) { return document.getElementById(i); });
    var out = document.getElementById('m-pay'), ca = mg.dataset.ca === '1';
    var calc = function () {
      var price = parseFloat(String(ids[0].value).replace(/[^\d.]/g, '')) || 0;
      var down = Math.min(100, Math.max(0, parseFloat(ids[1].value) || 0));
      var rate = Math.max(0, parseFloat(ids[2].value) || 0) / 100, n = (parseInt(ids[3].value, 10) || 25) * 12;
      var loan = price * (1 - down / 100);
      if (loan <= 0) { out.textContent = '$0'; return; }
      var i = ca ? Math.pow(1 + rate / 2, 1 / 6) - 1 : rate / 12;
      var pay = i === 0 ? loan / n : loan * i / (1 - Math.pow(1 + i, -n));
      out.textContent = '$' + Math.round(pay).toLocaleString('en-US') + ' / month';
    };
    ids.forEach(function (el) { el.addEventListener('input', calc); el.addEventListener('change', calc); });
    calc();
  }

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
