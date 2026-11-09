// Admin > Navigation: add / remove / reorder menu rows (drag handle or arrow buttons).
(function () {
  var form = document.getElementById('nav-form');
  if (!form) return;
  var counter = 0;

  function setDepth(row, d) {
    row.classList.toggle('is-sub', d === 1);
    row.querySelector('.nr-depth').value = d;
  }
  // The first row can never be a sub-item (nothing above it to attach to).
  function fixDepths(list) {
    var first = list.firstElementChild;
    if (first && first.classList.contains('is-sub')) setDepth(first, 0);
  }
  function syncRow(row) {
    var type = row.querySelector('.nr-type').value;
    ['builtin', 'page', 'link'].forEach(function (t) {
      var el = row.querySelector('.t-' + t);
      el.hidden = t !== type;
      el.disabled = t !== type;
    });
    // Label the built-in/page choice for convenience when the label is still empty.
    row.dataset.type = type;
    var tgt = row.querySelector('.nr-target');
    if (tgt) tgt.hidden = type === 'none';
  }
  function syncEmpty(list) {
    var empty = list.parentNode.querySelector('.nav-empty');
    if (empty) empty.hidden = list.children.length > 0;
  }
  function suggestLabel(row) {
    var lab = row.querySelector('.nr-label');
    if (lab.value.trim() !== '' && lab.dataset.auto !== '1') return;
    var type = row.querySelector('.nr-type').value, sel = row.querySelector('.t-' + type);
    if (type === 'link' || !sel || !sel.selectedOptions[0]) return;
    lab.value = sel.selectedOptions[0].textContent.replace(/\s*\(draft\)$/, '');
    lab.dataset.auto = '1';
  }

  form.querySelectorAll('.nav-row').forEach(syncRow);

  form.addEventListener('change', function (e) {
    var row = e.target.closest('.nav-row');
    if (!row) return;
    if (e.target.matches('.nr-type, .t-builtin, .t-page')) { syncRow(row); suggestLabel(row); }
  });
  form.addEventListener('input', function (e) {
    if (e.target.matches('.nr-label')) e.target.dataset.auto = '0';
  });

  form.addEventListener('click', function (e) {
    var add = e.target.closest('.nav-add');
    if (add) {
      var loc = add.dataset.loc, list = document.getElementById('nav-' + loc);
      var html = document.getElementById('tpl-' + loc).innerHTML.replace(/__I__/g, 'n' + (++counter));
      var wrap = document.createElement('div');
      wrap.innerHTML = html.trim();
      var row = wrap.firstElementChild;
      list.appendChild(row);
      syncRow(row);
      syncEmpty(list);
      row.querySelector('.nr-label').focus();
      return;
    }
    var row2 = e.target.closest('.nav-row');
    if (!row2) return;
    var btn = e.target.closest('.mv');
    if (!btn) return;
    var list2 = row2.parentNode;
    if (btn.classList.contains('nr-in')) { if (row2.previousElementSibling) setDepth(row2, 1); return; }
    if (btn.classList.contains('nr-out')) { setDepth(row2, 0); return; }
    if (btn.classList.contains('nr-del')) {
      row2.remove();
      syncEmpty(list2);
      return;
    }
    var dir = parseInt(btn.dataset.dir, 10);
    if (dir < 0 && row2.previousElementSibling) list2.insertBefore(row2, row2.previousElementSibling);
    if (dir > 0 && row2.nextElementSibling) list2.insertBefore(row2.nextElementSibling, row2);
    fixDepths(list2);
    btn.focus();
  });

  // Drag and drop within one menu (mouse / pen); touch users have the arrow buttons.
  var dragged = null;
  form.addEventListener('pointerdown', function (e) {
    var g = e.target.closest('.grip');
    if (g && e.pointerType !== 'touch') g.closest('.nav-row').draggable = true;
  });
  document.addEventListener('pointerup', function () {
    form.querySelectorAll('.nav-row[draggable=true]').forEach(function (r) { r.draggable = false; });
  });
  form.addEventListener('dragstart', function (e) {
    var row = e.target.closest ? e.target.closest('.nav-row') : null;
    if (!row || !row.draggable) return;
    dragged = row;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', 'nav');
    setTimeout(function () { row.classList.add('dragging'); }, 0);
  });
  form.addEventListener('dragover', function (e) {
    if (!dragged) return;
    var over = e.target.closest('.nav-row');
    if (!over || over === dragged || over.parentNode !== dragged.parentNode) return;
    e.preventDefault();
    var r = over.getBoundingClientRect();
    over.parentNode.insertBefore(dragged, (e.clientY - r.top) > r.height / 2 ? over.nextSibling : over);
  });
  form.addEventListener('drop', function (e) { if (dragged) e.preventDefault(); });
  form.addEventListener('dragend', function () {
    if (dragged) { dragged.classList.remove('dragging'); dragged.draggable = false; fixDepths(dragged.parentNode); }
    dragged = null;
  });
})();
