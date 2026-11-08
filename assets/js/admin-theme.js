// Admin > Theme: colour pickers, presets, live preview and contrast checks.
(function () {
  var form = document.getElementById('theme-form');
  var pv = document.getElementById('preview');
  if (!form || !pv) return;
  var presets = JSON.parse(form.dataset.presets), defaults = JSON.parse(form.dataset.defaults);
  var radii = JSON.parse(pv.dataset.radii), fonts = JSON.parse(pv.dataset.fonts);
  var keys = Object.keys(defaults);
  var warnBox = document.getElementById('contrast-warnings');

  function color(k) { return form.elements[k].value.toLowerCase(); }
  function rgb(h) { return [1, 3, 5].map(function (i) { return parseInt(h.substr(i, 2), 16); }); }
  function lum(h) {
    var c = rgb(h).map(function (v) { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); });
    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
  }
  function contrast(a, b) { var x = lum(a), y = lum(b); if (x < y) { var t = x; x = y; y = t; } return (x + 0.05) / (y + 0.05); }
  function onColor(bg) { return contrast(bg, '#ffffff') >= contrast(bg, '#101828') ? '#ffffff' : '#101828'; }
  function mix(a, b) {
    var x = rgb(a), y = rgb(b);
    return '#' + [0, 1, 2].map(function (i) { return ('0' + Math.round((x[i] + y[i]) / 2).toString(16)).slice(-2); }).join('');
  }

  function render() {
    var c = {}; keys.forEach(function (k) { c[k] = color(k); });
    var r = radii[form.elements.radius.value] || radii['12'];
    var logo = contrast(c.primary, c.header_bg) >= 3 ? c.primary : c.header_text;
    var v = {
      '--brand': c.primary, '--brand-d': c.secondary, '--accent': c.tertiary,
      '--on-brand': onColor(c.primary), '--on-accent': onColor(c.tertiary), '--on-hero': onColor(mix(c.primary, c.secondary)),
      '--btn': c.button, '--btn-text': c.button_text, '--header-bg': c.header_bg, '--header-text': c.header_text, '--logo': logo,
      '--footer-bg': c.footer_bg, '--footer-text': c.footer_text, '--ink': c.text, '--link': c.link,
      '--radius': r[0] + 'px', '--radius-btn': r[1] + 'px', '--radius-in': r[2] + 'px',
      '--font': fonts[form.elements.font.value] || fonts.system
    };
    Object.keys(v).forEach(function (k) { pv.style.setProperty(k, v[k]); });

    var checks = [
      ['Button text on the button color', c.button_text, c.button, 4.5],
      ['Menu text on the header background', c.header_text, c.header_bg, 4.5],
      ['Footer text on the footer background', c.footer_text, c.footer_bg, 4.5],
      ['Body text on white', c.text, '#ffffff', 4.5],
      ['Link color on white', c.link, '#ffffff', 4.5]
    ];
    var bad = checks.filter(function (x) { return contrast(x[1], x[2]) < x[3]; }).map(function (x) {
      return x[0] + ' is hard to read (contrast ' + contrast(x[1], x[2]).toFixed(1) + ':1, aim for 4.5:1 or more).';
    });
    warnBox.hidden = !bad.length;
    warnBox.innerHTML = bad.length ? '<strong>Readability check</strong><br>' + bad.map(function (t) { return '&bull; ' + t; }).join('<br>') : '';
  }

  function setColor(k, val) {
    if (!/^#[0-9a-f]{6}$/i.test(val)) return;
    form.elements[k].value = val.toLowerCase();
    var hex = form.querySelector('input.hex[data-for="' + k + '"]');
    if (hex) { hex.value = val.toLowerCase(); hex.classList.remove('bad'); }
  }

  keys.forEach(function (k) {
    form.elements[k].addEventListener('input', function () { setColor(k, this.value); render(); });
  });
  form.querySelectorAll('input.hex').forEach(function (h) {
    h.addEventListener('input', function () {
      var val = this.value.trim();
      if (val && val[0] !== '#') val = '#' + val;
      var ok = /^#[0-9a-f]{6}$/i.test(val);
      this.classList.toggle('bad', !ok);
      if (ok) { form.elements[this.dataset.for].value = val.toLowerCase(); render(); }
    });
    h.addEventListener('blur', function () { setColor(this.dataset.for, form.elements[this.dataset.for].value); });
  });
  form.querySelectorAll('.preset').forEach(function (b) {
    b.addEventListener('click', function () {
      var p = presets[b.dataset.name] || defaults;
      keys.forEach(function (k) { setColor(k, p[k]); });
      render();
    });
  });
  form.elements.font.addEventListener('change', render);
  form.elements.radius.addEventListener('change', render);
  render();
})();
