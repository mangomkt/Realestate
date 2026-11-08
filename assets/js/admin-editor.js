// Lightweight visual editor for CMS pages (contenteditable + execCommand), with an HTML source mode.
// The server sanitizes everything on save, so this only has to be convenient, not a security boundary.
(function () {
  var form = document.getElementById('page-form');
  var area = document.getElementById('ed-area');
  var source = document.getElementById('ed-source');
  if (!form || !area || !source) return;
  var statusEl = document.getElementById('ed-status');
  var fileInput = document.getElementById('ed-file');
  var sourceMode = false, saved = null;

  area.innerHTML = source.value;
  try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) {}

  function say(t, err) { statusEl.textContent = t; statusEl.style.color = err ? '#7a1c14' : ''; }
  function focusArea() { if (!sourceMode) area.focus(); }
  function exec(cmd, val) { focusArea(); document.execCommand(cmd, false, val || null); }

  // Remember the selection so toolbar clicks / prompts don't lose it.
  function saveSel() {
    var s = window.getSelection();
    if (s.rangeCount && area.contains(s.anchorNode)) saved = s.getRangeAt(0).cloneRange();
  }
  function restoreSel() {
    area.focus();
    if (saved) { var s = window.getSelection(); s.removeAllRanges(); s.addRange(saved); }
  }
  document.addEventListener('selectionchange', saveSel);

  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function embedUrl(u) {
    var m = u.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([\w-]{11})/);
    if (m) return 'https://www.youtube-nocookie.com/embed/' + m[1];
    m = u.match(/vimeo\.com\/(\d+)/);
    return m ? 'https://player.vimeo.com/video/' + m[1] : null;
  }

  var actions = {
    link: function () {
      restoreSel();
      var u = prompt('Link address (https://..., mailto:..., or /page path):', 'https://');
      if (u && u !== 'https://') document.execCommand('createLink', false, u.trim());
    },
    button: function () {
      restoreSel();
      var sel = window.getSelection().toString().trim();
      if (!sel) { say('Select the text you want as a button first.', true); return; }
      var u = prompt('Where should the button link to?', 'https://');
      if (!u || u === 'https://') return;
      document.execCommand('insertHTML', false, '<a class="btn" href="' + esc(u.trim()) + '">' + esc(sel) + '</a>&nbsp;');
    },
    image: function () { saveSel(); fileInput.click(); },
    video: function () {
      restoreSel();
      var u = prompt('Paste a YouTube or Vimeo link:');
      if (!u) return;
      var e = embedUrl(u.trim());
      if (!e) { say('Only YouTube and Vimeo links can be embedded. (Upload your own videos on a property.)', true); return; }
      document.execCommand('insertHTML', false, '<iframe src="' + e + '" title="Video" allowfullscreen></iframe><p><br></p>');
    },
    source: function (btn) {
      sourceMode = !sourceMode;
      if (sourceMode) { source.value = area.innerHTML; source.hidden = false; area.hidden = true; }
      else { area.innerHTML = source.value; area.hidden = false; source.hidden = true; }
      btn.setAttribute('aria-pressed', sourceMode ? 'true' : 'false');
      btn.classList.toggle('on', sourceMode);
      form.querySelectorAll('.ed-toolbar button').forEach(function (b) { if (b !== btn) b.disabled = sourceMode; });
    }
  };

  form.querySelector('.ed-toolbar').addEventListener('mousedown', function (e) {
    if (e.target.closest('button')) e.preventDefault();   // keep the text selection while clicking
  });
  form.querySelector('.ed-toolbar').addEventListener('click', function (e) {
    var b = e.target.closest('button');
    if (!b || b.disabled) return;
    if (b.dataset.cmd) exec(b.dataset.cmd);
    else if (b.dataset.block) exec('formatBlock', b.dataset.block);
    else if (b.dataset.act && actions[b.dataset.act]) actions[b.dataset.act](b);
  });

  // Image upload
  fileInput.addEventListener('change', function () {
    var f = fileInput.files[0];
    fileInput.value = '';
    if (!f) return;
    if (!/^image\/(jpeg|png|webp)$/.test(f.type) || f.size > 8 * 1024 * 1024) { say('Use a JPG, PNG or WebP under 8 MB.', true); return; }
    say('Uploading...');
    var fd = new FormData();
    fd.append('csrf', form.dataset.csrf);
    fd.append('file', f);
    fetch(form.dataset.upload, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        if (!res.ok || !res.j.url) throw new Error(res.j.error || 'Upload failed.');
        var alt = prompt('Describe this image (alt text, good for SEO and accessibility):', '') || '';
        restoreSel();
        document.execCommand('insertHTML', false, '<img src="' + esc(res.j.url) + '" alt="' + esc(alt) + '">');
        say('Image added.');
      })
      .catch(function (err) { say(err.message || 'Upload failed.', true); });
  });

  // Paste as plain text/basic HTML-free to avoid dragging in styles from Word / web pages.
  area.addEventListener('paste', function (e) {
    var t = (e.clipboardData || window.clipboardData);
    if (!t) return;
    e.preventDefault();
    var text = t.getData('text/plain');
    document.execCommand('insertText', false, text);
  });

  // Auto slug from title until the slug is edited by hand.
  var title = document.getElementById('p-title'), slug = document.getElementById('p-slug');
  var manual = slug.value !== '';
  function slugify(s) {
    return s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80);
  }
  slug.addEventListener('input', function () { manual = slug.value !== ''; });
  title.addEventListener('input', function () { if (!manual) slug.value = slugify(title.value); });

  // Meta description counter
  var md = document.getElementById('p-md'), count = document.getElementById('md-count');
  function updCount() { count.textContent = md.value.length; count.style.color = md.value.length > 160 ? '#b54708' : ''; }
  md.addEventListener('input', updCount); updCount();

  form.addEventListener('submit', function () { source.value = sourceMode ? source.value : area.innerHTML; });
})();
