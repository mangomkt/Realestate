// SEO helpers for the admin: live Google-style preview that fills the same {tokens} the server uses.
// The template/clean-up logic mirrors seo_fill() and seo_property_tokens() in includes/seo.php.
(function () {
  function fill(tpl, t) {
    var s = tpl.replace(/\{([a-z_]+)\}/g, function (m, k) { return t[k] != null ? String(t[k]) : ''; });
    s = s.replace(/\s+/g, ' ');
    s = s.replace(/\s*[-–|]\s*(?=[-–|])/g, ' ');
    s = s.replace(/\s*,(\s*,)+/g, ',');
    s = s.replace(/:\s*,/g, ':');
    s = s.replace(/,\s*\./g, '.');
    s = s.replace(/\.(\s*\.)+/g, '.');
    s = s.replace(/\s+([.,:;])/g, '$1');
    return s.replace(/^[\s,:\-–|]+|[\s,:\-–|]+$/g, '');
  }
  function cut(s, max) {
    s = s.trim();
    if (s.length <= max) return s;
    var c = s.slice(0, max - 1), sp = c.lastIndexOf(' ');
    if (sp > max * 0.6) c = c.slice(0, sp);
    return c.replace(/[\s,.;:\-]+$/, '') + '…';
  }
  function num(n, unit) { n = parseFloat(n) || 0; return n > 0 ? String(Math.round(n * 10) / 10) + ' ' + unit : ''; }
  function plain(html, len) {
    var t = String(html || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    return t.length > len ? t.slice(0, len - 1).replace(/\s+$/, '') + '…' : t;
  }
  function formTokens(form, st) {
    function v(n) { var el = form.elements[n]; return el ? el.value.trim() : ''; }
    var price = parseInt(v('price').replace(/\D/g, ''), 10) || 0;
    var status = v('status') || 'for_sale', type = v('type') || 'house', sqft = parseInt(v('sqft'), 10) || 0;
    var city = v('city');
    var t = {
      site: st.site, tagline: st.tagline, title: v('title'), address: v('address'), city: city, in_city: city ? 'in ' + city : '',
      province: v('province'), postal: v('postal'), mls: v('mls_number'),
      price_text: price > 0 ? '$' + price.toLocaleString('en-US') + (status === 'for_rent' ? '/mo' : '') : '',
      beds_text: num(v('bedrooms'), 'bed'), baths_text: num(v('bathrooms'), 'bath'),
      sqft_text: sqft > 0 ? sqft.toLocaleString('en-US') + ' sq ft' : '',
      type: st.types[type] || '', status: st.statuses[status] || '', excerpt: plain(v('description'), 110)
    };
    t.status_lc = t.status.toLowerCase();
    return t;
  }

  document.querySelectorAll('.seo-box').forEach(function (box) {
    var st = JSON.parse(box.dataset.static || '{}');
    var form = box.closest('form');
    var ti = document.querySelector(box.dataset.titleInput), di = document.querySelector(box.dataset.descInput);
    var sample = box.dataset.sample ? JSON.parse(box.dataset.sample) : null;
    var kind = box.dataset.kind || 'property';       // property | page | home
    var serpT = box.querySelector('.serp-title'), serpD = box.querySelector('.serp-desc'), serpU = box.querySelector('.serp-url');
    var cT = box.querySelector('.cnt-title'), cD = box.querySelector('.cnt-desc');

    function tokens() { return sample || formTokens(form, st); }
    function compute() {
      var t = tokens();
      var tt = (ti.value.trim() || box.dataset.titleDefault || '');
      var dt = (di.value.trim() || box.dataset.descDefault || '');
      var title = cut(fill(tt, t), 120), desc = cut(fill(dt, t), 160);
      // The template editor shows the effective template as placeholder; override fields show the generated text.
      if (!sample) { ti.placeholder = title || 'Generated from the listing'; di.placeholder = desc || 'Generated from the listing'; }
      serpT.textContent = title || 'Untitled';
      serpD.textContent = desc;
      if (serpU && box.dataset.url) serpU.textContent = box.dataset.url;
      var tl = title.length, dl = desc.length;
      cT.textContent = tl + ' characters' + (tl > 60 ? ' (Google may cut it off after ~60)' : '');
      cT.style.color = tl > 60 || tl === 0 ? '#b54708' : '';
      cD.textContent = dl + ' characters' + (dl > 160 ? ' (too long)' : dl < 70 && dl > 0 ? ' (a little short)' : '');
      cD.style.color = dl > 160 ? '#b54708' : '';
    }
    [ti, di].forEach(function (el) { el.addEventListener('input', compute); });
    if (form && !sample) form.addEventListener('input', compute);
    compute();
  });
})();
