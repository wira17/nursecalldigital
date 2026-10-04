/* Nurse Call - script umum: PWA, install prompt, menu, konfirmasi */
(function () {
  var BASE = window.APP_BASE || './';

  // ---- Service Worker ----
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register(BASE + 'sw.js', { scope: BASE }).catch(function (e) { console.warn('SW gagal', e); });
    });
  }

  // ---- Tombol "Pasang aplikasi" ----
  var deferred = null, bar = document.getElementById('installBar'), dismissed = false;
  try { dismissed = localStorage.getItem('nc_install_dismiss') === '1'; } catch (e) {}
  window.addEventListener('beforeinstallprompt', function (ev) {
    ev.preventDefault(); deferred = ev;
    if (bar && !dismissed) bar.classList.add('show');
    document.querySelectorAll('[data-install]').forEach(function (b) { b.hidden = false; });
  });
  function doInstall(ev) {
    if (ev) ev.preventDefault();
    if (!deferred) { alert('Untuk memasang: buka menu browser (⋮ atau tombol Bagikan) lalu pilih "Tambahkan ke Layar Utama" / "Install app".'); return; }
    deferred.prompt();
    deferred.userChoice.finally(function () { deferred = null; if (bar) bar.classList.remove('show'); });
  }
  var ib = document.getElementById('installBtn'); if (ib) ib.addEventListener('click', doInstall);
  document.querySelectorAll('[data-install],[data-install-any]').forEach(function (b) { b.addEventListener('click', doInstall); });
  var ic = document.getElementById('installClose');
  if (ic) ic.addEventListener('click', function () { bar.classList.remove('show'); try { localStorage.setItem('nc_install_dismiss', '1'); } catch (e) {} });

  // ---- Dropdown menu desktop: tutup saat klik di luar ----
  document.addEventListener('click', function (ev) {
    document.querySelectorAll('.tb-user[open]').forEach(function (d) { if (!d.contains(ev.target)) d.removeAttribute('open'); });
  });

  // ---- Sidebar desktop: perkecil / perbesar ----
  var st = document.getElementById('sideToggle');
  if (st) st.addEventListener('click', function () {
    var mini = document.documentElement.classList.toggle('side-mini');
    try { localStorage.setItem('nc_side_mini', mini ? '1' : '0'); } catch (e) {}
  });

  // ---- Jam di topbar (zona waktu server) ----
  var clk = document.getElementById('clock');
  if (clk) {
    var fmt; try { fmt = new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false, timeZone: clk.getAttribute('data-tz') }); } catch (e) { fmt = null; }
    var upd = function () { var d = new Date(); clk.textContent = fmt ? fmt.format(d).replace('.', ':') : (('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2)); };
    upd(); setInterval(upd, 5000);
  }

  // ---- Konfirmasi ----
  document.addEventListener('click', function (ev) {
    var el = ev.target.closest('[data-confirm]');
    if (el && !confirm(el.getAttribute('data-confirm'))) { ev.preventDefault(); ev.stopImmediatePropagation(); }
  }, true);

  // ---- Cegah double submit ----
  document.querySelectorAll('form[data-once]').forEach(function (f) {
    f.addEventListener('submit', function (ev) {
      if (ev.defaultPrevented) return;
      var b = ev.submitter || f.querySelector('[type=submit]');
      setTimeout(function () { f.querySelectorAll('button').forEach(function (x) { x.disabled = true; }); if (b) b.textContent = 'Memproses...'; }, 0);
    });
  });

  // ---- Waktu relatif: <span data-since="2026-09-28T10:15:00+07:00"> ----
  window.sejak = function (iso) {
    var s = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 1000));
    if (s < 60) return s + ' dtk';
    if (s < 3600) return Math.floor(s / 60) + ' mnt ' + (s % 60 < 10 ? '0' : '') + (s % 60) + ' dtk';
    return Math.floor(s / 3600) + ' jam ' + Math.floor((s % 3600) / 60) + ' mnt';
  };
  function tick() { document.querySelectorAll('[data-since]').forEach(function (el) { el.textContent = sejak(el.getAttribute('data-since')); }); }
  tick(); setInterval(tick, 1000);
})();