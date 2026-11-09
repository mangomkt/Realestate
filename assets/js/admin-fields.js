// Shared admin widgets: image fields (upload on choose) and confirm prompts.
(function () {
  function initImageFields(root) {
    (root || document).querySelectorAll('.img-field:not([data-init])').forEach(function (box) {
      box.setAttribute('data-init', '1');
      var hidden = box.querySelector('input[type=hidden]'), file = box.querySelector('input[type=file]');
      var img = box.querySelector('.imf-preview img'), choose = box.querySelector('.imf-choose');
      var clear = box.querySelector('.imf-clear'), msg = box.querySelector('.imf-msg');
      function show(url) {
        hidden.value = url || '';
        if (url) { img.src = url; img.hidden = false; clear.hidden = false; } else { img.removeAttribute('src'); img.hidden = true; clear.hidden = true; }
        hidden.dispatchEvent(new Event('change', { bubbles: true }));
      }
      choose.addEventListener('click', function () { file.click(); });
      clear.addEventListener('click', function () { show(''); msg.textContent = ''; });
      file.addEventListener('change', function () {
        var f = file.files[0]; file.value = '';
        if (!f) return;
        if (!/^image\/(jpeg|png|webp)$/.test(f.type) || f.size > 8 * 1024 * 1024) { msg.textContent = 'Use a JPG, PNG or WebP under 8 MB.'; msg.style.color = '#c0362c'; return; }
        msg.textContent = 'Uploading...'; msg.style.color = '';
        var fd = new FormData(); fd.append('csrf', box.dataset.csrf); fd.append('file', f);
        fetch(box.dataset.upload, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
          .then(function (res) { if (!res.ok || !res.j.url) throw new Error(res.j.error || 'Upload failed.'); show(res.j.url); msg.textContent = ''; })
          .catch(function (e) { msg.textContent = e.message; msg.style.color = '#c0362c'; });
      });
    });
  }
  window.initImageFields = initImageFields;
  initImageFields(document);
})();
