// Admin > Theme: light/dark palettes, presets, live preview and contrast checks.
// The colour maths mirrors includes/cms.php (theme_auto_dark, theme_palette_vars): keep both in sync.
(function () {
  var form = document.getElementById('theme-form');
  var pv = document.getElementById('preview');
  if (!form || !pv) return;
  var presets = JSON.parse(form.dataset.presets), defaults = JSON.parse(form.dataset.defaults);
  var radii = JSON.parse(pv.dataset.radii), fonts = JSON.parse(pv.dataset.fonts);
  var keys = Object.keys(defaults);
  var warnBox = document.getElementById('contrast-warnings');
  var autoBox = document.getElementById('dark-auto');
  var mode = 'light';

  // ---- colour maths
  function rgb(h) { return [1, 3, 5].map(function (i) { return parseInt(h.substr(i, 2), 16); }); }
  function toHex(c) { return '#' + c.map(function (x) { return ('0' + Math.max(0, Math.min(255, Math.round(x))).toString(16)).slice(-2); }).join(''); }
  function lum(h) {
    var c = rgb(h).map(function (v) { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); });
    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
  }
  function contrast(a, b) { var x = lum(a), y = lum(b); if (x < y) { var t = x; x = y; y = t; } return (x + 0.05) / (y + 0.05); }
  function onColor(bg) { return contrast(bg, '#ffffff') >= contrast(bg, '#101828') ? '#ffffff' : '#101828'; }
  function mix(a, b, w) {
    if (w === undefined) w = 0.5;
    var x = rgb(a), y = rgb(b);
    return toHex([0, 1, 2].map(function (i) { return x[i] * (1 - w) + y[i] * w; }));
  }
  function hexHsl(h) {
    var c = rgb(h).map(function (v) { return v / 255; }), r = c[0], g = c[1], b = c[2];
    var max = Math.max(r, g, b), min = Math.min(r, g, b), l = (max + min) / 2, d = max - min;
    if (d === 0) return [0, 0, l];
    var s = l > 0.5 ? d / (2 - max - min) : d / (max + min), hh;
    if (max === r) hh = (g - b) / d + (g < b ? 6 : 0); else if (max === g) hh = (b - r) / d + 2; else hh = (r - g) / d + 4;
    return [hh * 60, s, l];
  }
  function hslHex(h, s, l) {
    h = (((h % 360) + 360) % 360) / 360; s = Math.max(0, Math.min(1, s)); l = Math.max(0, Math.min(1, l));
    if (s === 0) return toHex([l * 255, l * 255, l * 255]);
    var q = l < 0.5 ? l * (1 + s) : l + s - l * s, p = 2 * l - q;
    function f(t) {
      if (t < 0) t += 1; if (t > 1) t -= 1;
      if (t < 1 / 6) return p + (q - p) * 6 * t;
      if (t < 1 / 2) return q;
      if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
      return p;
    }
    return toHex([f(h + 1 / 3) * 255, f(h) * 255, f(h - 1 / 3) * 255]);
  }
  function lift(c, bg, min) {
    if (contrast(c, bg) >= min) return c;
    var hsl = hexHsl(c), h = hsl[0], s = hsl[1], l = hsl[2];
    for (var n = 0; n < 40 && l < 0.92 && contrast(hslHex(h, s, l), bg) < min; n++) l += 0.03;
    return hslHex(h, s, Math.min(l, 0.92));
  }
  function autoDark(L) {
    var hs = hexHsl(L.primary), h = hs[0], s = hs[1];
    var bs = Math.min(0.28, Math.max(0.10, s * 0.45));
    var bg = hslHex(h, bs, 0.085), text = hslHex(h, 0.12, 0.92);
    var primary = lift(L.primary, bg, 4.5), button = lift(L.button, bg, 3.0);
    var btext = contrast(L.button_text, button) >= 4.5 ? L.button_text : onColor(button);
    return {
      primary: primary, secondary: hslHex(h, Math.max(0.2, Math.min(s, 0.6)), 0.16), tertiary: lift(L.tertiary, bg, 3.0),
      button: button, button_text: btext, header_bg: hslHex(h, bs, 0.105), header_text: text,
      footer_bg: hslHex(h, bs, 0.055), footer_text: hslHex(h, 0.12, 0.76), text: text, link: primary,
      bg: bg, surface: hslHex(h, bs, 0.125)
    };
  }
  function paletteVars(p, dark) {
    var muted = p.text, ws = [0.4, 0.3, 0.2, 0.1, 0];
    for (var i = 0; i < ws.length; i++) { muted = mix(p.text, p.bg, ws[i]); if (contrast(muted, p.bg) >= 4.5) break; }
    return {
      '--brand': p.primary, '--brand-d': p.secondary, '--accent': p.tertiary,
      '--on-brand': onColor(p.primary), '--on-accent': onColor(p.tertiary), '--on-hero': onColor(mix(p.primary, p.secondary)),
      '--btn': p.button, '--btn-text': p.button_text, '--header-bg': p.header_bg, '--header-text': p.header_text,
      '--logo': contrast(p.primary, p.header_bg) >= 3 ? p.primary : p.header_text,
      '--footer-bg': p.footer_bg, '--footer-text': p.footer_text, '--ink': p.text, '--link': p.link,
      '--bg': p.bg, '--surface': p.surface, '--soft': mix(p.bg, p.text, 0.04), '--line': mix(p.bg, p.text, 0.14),
      '--field-border': mix(p.bg, p.text, 0.3), '--muted': muted
    };
  }

  // ---- form access
  function field(prefix, k) { return form.elements[prefix + k]; }
  function read(prefix) { var o = {}; keys.forEach(function (k) { o[k] = field(prefix, k).value.toLowerCase(); }); return o; }
  function setColor(name, val) {
    if (!/^#[0-9a-f]{6}$/i.test(val)) return;
    form.elements[name].value = val.toLowerCase();
    var hex = form.querySelector('input.hex[data-for="' + name + '"]');
    if (hex) { hex.value = val.toLowerCase(); hex.classList.remove('bad'); }
  }
  function syncAuto() {
    var auto = autoBox.checked;
    if (auto) { var d = autoDark(read('')); keys.forEach(function (k) { setColor('dark_' + k, d[k]); }); }
    keys.forEach(function (k) {
      field('dark_', k).disabled = auto;
      form.querySelector('input.hex[data-for="dark_' + k + '"]').disabled = auto;
    });
  }

  function render() {
    var light = read(''), dark = autoBox.checked ? autoDark(light) : read('dark_');
    var p = mode === 'dark' ? dark : light;
    var r = radii[form.elements.radius.value] || radii['12'];
    var v = paletteVars(p, mode === 'dark');
    v['--radius'] = r[0] + 'px'; v['--radius-btn'] = r[1] + 'px'; v['--radius-in'] = r[2] + 'px';
    var fp = fonts[form.elements.font.value] || fonts.system;
    v['--font'] = fp[0]; v['--font-head'] = fp[1];
    Object.keys(v).forEach(function (k) { pv.style.setProperty(k, v[k]); });
    pv.style.colorScheme = mode;
    document.getElementById('pv-mode').textContent = '(' + mode + ')';

    var bad = [];
    [['Light', light], ['Dark', dark]].forEach(function (pair) {
      var c = pair[1];
      [['button text on the button color', c.button_text, c.button], ['menu text on the header', c.header_text, c.header_bg],
       ['footer text on the footer', c.footer_text, c.footer_bg], ['body text on the page background', c.text, c.bg],
       ['body text on cards', c.text, c.surface], ['link color on the page background', c.link, c.bg]].forEach(function (x) {
        var cr = contrast(x[1], x[2]);
        if (cr < 4.5) bad.push(pair[0] + ' mode: ' + x[0] + ' is hard to read (contrast ' + cr.toFixed(1) + ':1, aim for 4.5:1 or more).');
      });
    });
    warnBox.hidden = !bad.length;
    warnBox.innerHTML = bad.length ? '<strong>Readability check</strong><br>' + bad.map(function (t) { return '&bull; ' + t; }).join('<br>') : '';
  }
  function update() { syncAuto(); render(); }

  // ---- events
  form.querySelectorAll('input[type=color]').forEach(function (inp) {
    inp.addEventListener('input', function () { setColor(this.name, this.value); update(); });
  });
  form.querySelectorAll('input.hex').forEach(function (h) {
    h.addEventListener('input', function () {
      var val = this.value.trim();
      if (val && val[0] !== '#') val = '#' + val;
      var ok = /^#[0-9a-f]{6}$/i.test(val);
      this.classList.toggle('bad', !ok);
      if (ok) { form.elements[this.dataset.for].value = val.toLowerCase(); update(); }
    });
    h.addEventListener('blur', function () { setColor(this.dataset.for, form.elements[this.dataset.for].value); });
  });
  form.querySelectorAll('.preset').forEach(function (b) {
    b.addEventListener('click', function () {
      var p = presets[b.dataset.name] || defaults;
      keys.forEach(function (k) { setColor(k, p[k]); });
      update();
    });
  });
  autoBox.addEventListener('change', update);
  form.elements.font.addEventListener('change', render);
  form.elements.radius.addEventListener('change', render);

  form.querySelectorAll('.tab').forEach(function (t) {
    t.addEventListener('click', function () {
      mode = t.dataset.mode;
      form.querySelectorAll('.tab').forEach(function (x) { var on = x === t; x.classList.toggle('on', on); x.setAttribute('aria-selected', on ? 'true' : 'false'); });
      form.querySelectorAll('.tabpanel').forEach(function (p) { p.hidden = p.dataset.mode !== mode; });
      render();
    });
  });

  window.__themeAutoDark = autoDark;   // used by the automated tests
  update();
})();
