// ยืนยันก่อนทำรายการสำคัญ: <form data-confirm="..."> หรือ <button data-confirm="...">
document.addEventListener('submit', function (ev) {
  var btn = ev.submitter;
  var msg = (btn && btn.getAttribute('data-confirm')) || ev.target.getAttribute('data-confirm');
  if (msg && !window.confirm(msg)) {
    ev.preventDefault();
  }
});

// แสดงตัวอย่างรูปก่อนอัปโหลด: <input type="file" data-preview="#img">
document.querySelectorAll('input[type=file][data-preview]').forEach(function (input) {
  input.addEventListener('change', function () {
    var img = document.querySelector(input.getAttribute('data-preview'));
    if (img && input.files[0]) {
      img.src = URL.createObjectURL(input.files[0]);
      img.classList.remove('d-none');
    }
  });
});

// สลับช่องกรอกตามประเภทสินค้าในหน้าลงขาย
(function () {
  var radios = document.querySelectorAll('input[name=type][data-type-toggle]');
  if (!radios.length) return;
  function apply() {
    var type = document.querySelector('input[name=type]:checked').value;
    document.querySelectorAll('[data-for-type]').forEach(function (el) {
      var show = el.getAttribute('data-for-type') === type;
      el.classList.toggle('d-none', !show);
      el.querySelectorAll('select, input').forEach(function (f) { f.disabled = !show; });
    });
  }
  radios.forEach(function (r) { r.addEventListener('change', apply); });
  apply();
})();

// แชท: เลื่อนลงล่างสุดและดึงข้อความใหม่ทุก 4 วินาที
(function () {
  var box = document.querySelector('.chat-box[data-poll]');
  if (!box) return;
  box.scrollTop = box.scrollHeight;
  setInterval(function () {
    fetch(box.getAttribute('data-poll'), { credentials: 'same-origin' })
      .then(function (r) { return r.ok ? r.text() : null; })
      .then(function (html) {
        if (html !== null && html !== box.innerHTML) {
          var atBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 60;
          box.innerHTML = html;
          if (atBottom) box.scrollTop = box.scrollHeight;
        }
      })
      .catch(function () {});
  }, 4000);
})();
