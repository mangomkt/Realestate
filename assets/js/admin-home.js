// Admin > Home page: add / remove / reorder sections, repeater rows.
(function () {
  var list = document.getElementById('hs-list');
  if (!list) return;
  var empty = document.getElementById('hs-empty');
  var counter = list.children.length + 100;

  function sync() { if (empty) empty.hidden = list.children.length > 0; }
  function newField(root) {
    // Image pickers inside new markup are wired by admin-fields.js on load; re-run if it exposes an init.
    if (window.initImageFields) window.initImageFields(root);
  }

  list.addEventListener('click', function (e) {
    var t = e.target;
    var card = t.closest('.hs-card');
    if (!card) return;
    if (t.classList.contains('hs-toggle')) {
      var body = card.querySelector('.hs-body');
      body.hidden = !body.hidden;
      t.setAttribute('aria-expanded', String(!body.hidden));
      t.textContent = body.hidden ? 'Edit' : 'Done';
    } else if (t.classList.contains('hs-up') && card.previousElementSibling) {
      list.insertBefore(card, card.previousElementSibling);
    } else if (t.classList.contains('hs-down') && card.nextElementSibling) {
      list.insertBefore(card.nextElementSibling, card);
    } else if (t.classList.contains('hs-del')) {
      if (confirm('Delete this section?')) { card.remove(); sync(); }
    } else if (t.classList.contains('rep-add')) {
      // handled below (button is inside the card body)
    }
  });

  // Repeater rows
  list.addEventListener('click', function (e) {
    var add = e.target.closest('.rep-add'), del = e.target.closest('.rep-del');
    if (add) {
      var rep = add.closest('.rep'), tpl = rep.querySelector('.rep-tpl');
      var html = tpl.innerHTML.replace(/__R__/g, String(++counter));
      var holder = document.createElement('div');
      holder.innerHTML = html;
      var row = holder.firstElementChild;
      rep.querySelector('.rep-rows').appendChild(row);
      newField(row);
      var first = row.querySelector('input,textarea');
      if (first) first.focus();
    } else if (del) {
      del.closest('.rep-row').remove();
    }
  });

  // Add a section
  document.getElementById('hs-add').addEventListener('click', function () {
    var type = document.getElementById('hs-type').value;
    var tpl = document.getElementById('hs-tpl-' + type);
    var holder = document.createElement('div');
    holder.innerHTML = tpl.innerHTML.replace(/__I__/g, String(++counter));
    var card = holder.firstElementChild;
    list.appendChild(card);
    var body = card.querySelector('.hs-body'), tg = card.querySelector('.hs-toggle');
    body.hidden = false; tg.setAttribute('aria-expanded', 'true'); tg.textContent = 'Done';
    newField(card);
    sync();
    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });

  // Drag to reorder (mouse/pen; touch users have the arrow buttons)
  var dragging = null;
  list.addEventListener('pointerdown', function (e) {
    if (e.pointerType === 'touch') return;
    var grip = e.target.closest('.hs-grip');
    if (grip) grip.closest('.hs-card').draggable = true;
  });
  list.addEventListener('dragstart', function (e) {
    var c = e.target.closest && e.target.closest('.hs-card');
    if (!c || !c.draggable) return;
    dragging = c; c.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    try { e.dataTransfer.setData('text/plain', 'section'); } catch (x) {}
  });
  list.addEventListener('dragover', function (e) {
    if (!dragging) return;
    e.preventDefault();
    var over = e.target.closest('.hs-card');
    if (!over || over === dragging) return;
    var r = over.getBoundingClientRect();
    list.insertBefore(dragging, e.clientY < r.top + r.height / 2 ? over : over.nextSibling);
  });
  list.addEventListener('dragend', function () {
    if (dragging) { dragging.classList.remove('dragging'); dragging.draggable = false; dragging = null; }
  });
})();
