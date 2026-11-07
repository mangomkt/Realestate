// Admin photo manager: drag-and-drop ordering, previews for new uploads, alt text per photo.
(function () {
  var grid = document.getElementById('sort-grid');
  var input = document.getElementById('image-input');
  if (!grid || !input) return;
  var form = grid.closest('form');
  var msg = document.getElementById('img-msg');
  var pending = [];            // {id, file, url}
  var seq = 0;
  var MAX = 8 * 1024 * 1024, OK = ['image/jpeg', 'image/png', 'image/webp'];

  function tiles() { return Array.prototype.slice.call(grid.querySelectorAll('.tile')); }
  function refresh() {
    tiles().forEach(function (t, i) { t.classList.toggle('is-cover', i === 0); });
  }
  function syncInput() {
    // The file input only ever holds the photos still pending, in their original order;
    // each new tile carries its index into that list.
    var dt = new DataTransfer();
    pending.forEach(function (p) { dt.items.add(p.file); });
    input.files = dt.files;
    tiles().forEach(function (t) {
      if (t.dataset.pid) t.dataset.idx = String(pending.findIndex(function (p) { return p.id === t.dataset.pid; }));
    });
  }
  function say(text, err) { msg.textContent = text; msg.style.color = err ? '#7a1c14' : ''; }

  function addTile(p) {
    var t = document.createElement('div');
    t.className = 'tile is-new';
    t.dataset.pid = p.id;
    t.innerHTML =
      '<span class="cover-tag">Cover</span><span class="new-tag">New</span>' +
      '<div class="tile-img"><img alt="" draggable="false"><span class="handle" title="Drag to reorder" aria-hidden="true">⋮⋮</span></div>' +
      '<input type="text" maxlength="200" placeholder="Alt text (describe this photo)" aria-label="Alt text for new photo">' +
      '<div class="tile-actions"><button type="button" class="mv" data-dir="-1" aria-label="Move earlier">←</button>' +
      '<button type="button" class="mv" data-dir="1" aria-label="Move later">→</button>' +
      '<button type="button" class="rm-new">Remove</button></div>';
    t.querySelector('img').src = p.url;
    grid.appendChild(t);
  }

  input.addEventListener('change', function () {
    var added = Array.prototype.slice.call(input.files), bad = 0;
    added.forEach(function (f) {
      if (OK.indexOf(f.type) === -1 || f.size > MAX) { bad++; return; }
      var p = { id: 'n' + (++seq), file: f, url: URL.createObjectURL(f) };
      pending.push(p);
      addTile(p);
    });
    syncInput();
    refresh();
    say(bad ? bad + ' file(s) skipped: only JPG/PNG/WebP up to 8 MB.' : '', bad > 0);
  });

  // Buttons: move earlier/later, remove a new photo, strike-through for existing photos marked Remove
  grid.addEventListener('click', function (e) {
    var t = e.target.closest('.tile');
    if (!t) return;
    var mv = e.target.closest('.mv');
    if (mv) {
      var dir = parseInt(mv.dataset.dir, 10);
      if (dir < 0 && t.previousElementSibling) grid.insertBefore(t, t.previousElementSibling);
      if (dir > 0 && t.nextElementSibling) grid.insertBefore(t.nextElementSibling, t);
      refresh();
      mv.focus();
    }
    if (e.target.closest('.rm-new')) {
      pending = pending.filter(function (p) { if (p.id === t.dataset.pid) { URL.revokeObjectURL(p.url); return false; } return true; });
      t.remove();
      syncInput();
      refresh();
    }
  });
  grid.addEventListener('change', function (e) {
    if (e.target.matches('.rm input')) e.target.closest('.tile').classList.toggle('removed', e.target.checked);
  });

  // Drag and drop (mouse/pen). Tiles only become draggable from the handle so the alt text stays selectable.
  var dragged = null;
  grid.addEventListener('pointerdown', function (e) {
    var h = e.target.closest('.handle');
    if (h && e.pointerType !== 'touch') h.closest('.tile').draggable = true;
  });
  document.addEventListener('pointerup', function () { tiles().forEach(function (t) { t.draggable = false; }); });
  grid.addEventListener('dragstart', function (e) {
    var t = e.target.closest ? e.target.closest('.tile') : null;
    if (!t || !t.draggable) return;
    dragged = t;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', 'photo');
    setTimeout(function () { t.classList.add('dragging'); }, 0);
  });
  grid.addEventListener('dragover', function (e) {
    if (!dragged) return;
    e.preventDefault();
    var over = e.target.closest('.tile');
    if (!over || over === dragged) return;
    var r = over.getBoundingClientRect();
    var after = (e.clientX - r.left) > r.width / 2;
    grid.insertBefore(dragged, after ? over.nextSibling : over);
  });
  grid.addEventListener('drop', function (e) { if (dragged) e.preventDefault(); });
  grid.addEventListener('dragend', function () {
    if (dragged) { dragged.classList.remove('dragging'); dragged.draggable = false; }
    dragged = null;
    refresh();
  });

  // On submit, write the on-screen order and alt text of new photos into the form.
  form.addEventListener('submit', function () {
    form.querySelectorAll('input[data-gen]').forEach(function (n) { n.remove(); });
    function hidden(name, value) {
      var h = document.createElement('input');
      h.type = 'hidden'; h.name = name; h.value = value; h.setAttribute('data-gen', '1');
      form.appendChild(h);
    }
    tiles().forEach(function (t) {
      if (t.dataset.pid) {
        hidden('order[]', 'n:' + t.dataset.idx);
        hidden('new_alt[' + t.dataset.idx + ']', t.querySelector('input[type=text]').value);
      } else {
        hidden('order[]', t.dataset.token);
      }
    });
  });

  refresh();
})();
