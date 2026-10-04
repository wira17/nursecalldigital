/* Nurse Call - Display TV
 *  - Cek perubahan tiap 1,5 detik dari api/data_tv.php
 *  - Kotak per bed: hijau (aman), merah berkedip (memanggil), biru (ditangani)
 *  - Panggilan baru -> pengumuman layar penuh 8 detik + bunyi ding-dong + suara "Panggilan perawat, Melati 1 bed A"
 *  - Jika pasien banyak, halaman berganti otomatis tiap 12 detik
 */
(function () {
  var TV = window.TV || {}, $ = function (id) { return document.getElementById(id); };
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var data = null, seen = {}, first = true, queue = [], showing = false, page = 0, pages = 1, lastSig = '';
  try { seen = JSON.parse(sessionStorage.getItem('tv_seen') || '{}'); } catch (e) {}

  /* ---------- Jam ---------- */
  var fJam, fTgl;
  try {
    fJam = new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: TV.tz });
    fTgl = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: TV.tz });
  } catch (e) {}
  function jam() { var d = new Date(); $('tvJam').textContent = fJam ? fJam.format(d).replace(/\./g, ':') : d.toLocaleTimeString(); $('tvTgl').textContent = (fTgl ? fTgl.format(d) : '') + ' · ' + (TV.zona || ''); }
  jam(); setInterval(jam, 1000);

  function lama(iso) {
    var s = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 1000));
    return s < 3600 ? Math.floor(s / 60) + ':' + ('0' + s % 60).slice(-2) : Math.floor(s / 3600) + 'j ' + Math.floor(s % 3600 / 60) + 'm';
  }
  function telat(c) { return c.status === 'baru' && (Date.now() - new Date(c.waktu).getTime()) / 1000 > (data.batas || 300); }

  /* ---------- Suara ---------- */
  var ctx = null;
  function ac() { if (!ctx) { var A = window.AudioContext || window.webkitAudioContext; if (A) ctx = new A(); } return ctx; }
  function unlocked() { return ctx && ctx.state === 'running'; }
  function unlock() { var a = ac(); if (a) a.resume().then(updUnlock, updUnlock); }
  function updUnlock() { $('tvUnlock').hidden = !TV.suara || unlocked(); }
  ['click', 'keydown', 'touchstart'].forEach(function (ev) { document.addEventListener(ev, unlock); });
  function note(f, t, d) {
    var a = ac(), o = a.createOscillator(), g = a.createGain();
    o.type = 'sine'; o.frequency.value = f;
    g.gain.setValueAtTime(0.0001, t); g.gain.exponentialRampToValueAtTime(0.5, t + 0.02); g.gain.exponentialRampToValueAtTime(0.0001, t + d);
    o.connect(g); g.connect(a.destination); o.start(t); o.stop(t + d + 0.05);
  }
  function dingdong(n) {
    if (!TV.suara || !unlocked()) return;
    var t = ctx.currentTime + 0.05;
    for (var i = 0; i < (n || 2); i++) { note(1046.5, t + i * 1.3, 0.9); note(784, t + i * 1.3 + 0.55, 1.1); }
  }
  function umumkan(c) {
    if (!TV.suara || !('speechSynthesis' in window)) return;
    var u = new SpeechSynthesisUtterance(c.ucapan || ('Panggilan perawat. ' + c.lokasi + '. ' + c.keluhan));
    u.lang = 'id-ID'; u.rate = 0.92;
    setTimeout(function () { speechSynthesis.speak(u); }, 1600);
  }
  // Pengingat: bunyi lagi tiap 45 detik selama masih ada panggilan yang belum ditangani
  setInterval(function () { if (data && data.stat.baru > 0 && !showing && !window.tvCbAktif) dingdong(1); }, 45000);

  /* ---------- Pengumuman layar penuh ---------- */
  function nextAlert() {
    if (showing || !queue.length || window.tvCbAktif) return; // Code Blue didahulukan
    var c = queue.shift(); showing = true;
    $('aLoc').textContent = c.lokasi; $('aNama').textContent = c.pasien;
    $('aKel').textContent = c.ikon + ' ' + (c.pesan && /lain/i.test(c.keluhan) ? c.pesan : c.keluhan + (c.pesan ? ' · ' + c.pesan : '')) + (c.pelapor === 'keluarga' ? ' · oleh keluarga' : '');
    $('tvAlert').hidden = false; dingdong(2); umumkan(c);
    setTimeout(function () { $('tvAlert').hidden = true; showing = false; setTimeout(nextAlert, 600); }, 8000);
  }

  /* ---------- Render ---------- */
  function statusBed(p) {
    var c = p.call;
    if (!c) return '<div class="st"><span class="dot"></span>Aman</div>';
    if (c.status === 'baru') return '<div class="st"><span class="dot"></span><span class="tx">' + esc(c.ikon + ' ' + c.keluhan) + '</span><span class="timer" data-since="' + c.waktu + '">' + lama(c.waktu) + '</span></div>';
    return '<div class="st"><span class="dot"></span><span class="tx">Ditangani ' + esc(c.perawat || '') + '</span></div>';
  }
  function renderBeds() {
    var box = $('beds');
    if (!data.pasien.length) { box.innerHTML = '<div class="tv-empty" style="grid-column:1/-1"><div>🛏️</div>Belum ada pasien dirawat.</div>'; $('tvPage').innerHTML = ''; return; }
    box.innerHTML = data.pasien.map(function (p) {
      return '<div class="bed ' + (p.call ? p.call.status : '') + '"><span class="jk ' + p.jk + '"></span>' +
        '<div class="loc">' + esc(p.lokasi) + '</div><div class="nm">' + esc(p.nama) + '</div>' +
        '<div class="sub">RM ' + esc(p.no_rm) + ' · ' + esc(p.umur) + (p.dokter ? ' · ' + esc(p.dokter) : '') + (p.alergi ? ' · ⚠️ Alergi' : '') + '</div>' + statusBed(p) + '</div>';
    }).join('');
    paginate();
  }
  function paginate() {
    var box = $('beds'), tiles = [].slice.call(box.children);
    tiles.forEach(function (t) { t.style.display = ''; });
    if (tiles.length < 2) { pages = 1; $('tvPage').innerHTML = ''; return; } // selalu dibagi per halaman (slide), di layar apa pun
    var top0 = tiles[0].offsetTop, cols = tiles.filter(function (t) { return t.offsetTop === top0; }).length;
    var h = tiles[0].offsetHeight, gap = parseFloat(getComputedStyle(box).rowGap) || 0;
    var cs = getComputedStyle(box), tinggi = box.clientHeight - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom);
    var rows = Math.max(1, Math.floor((tinggi + gap) / (h + gap))), per = rows * cols;
    pages = Math.max(1, Math.ceil(tiles.length / per)); if (page >= pages) page = 0;
    tiles.forEach(function (t, i) { t.style.display = Math.floor(i / per) === page ? '' : 'none'; });
    $('tvPage').innerHTML = pages > 1 ? Array.apply(null, Array(pages)).map(function (_, i) { return '<i class="' + (i === page ? 'on' : '') + '"></i>'; }).join('') : '';
  }
  setInterval(function () { if (pages > 1) { page = (page + 1) % pages; paginate(); } }, 12000);
  window.addEventListener('resize', function () { if (data) paginate(); });

  function renderCalls() {
    var n = data.aktif.length; $('aktifN').textContent = n; $('aktifN').classList.toggle('zero', !data.stat.baru);
    $('aktif').innerHTML = n ? data.aktif.slice(0, 8).map(function (c) {
      return '<div class="tv-call ' + c.status + (telat(c) ? ' late' : '') + '"><div class="l">' + esc(c.lokasi) + '</div><div class="n">' + esc(c.pasien) + '</div>' +
        '<div class="k">' + esc(c.ikon) + ' ' + esc(c.pesan && /lain/i.test(c.keluhan) ? c.pesan : c.keluhan) + '</div>' +
        '<div class="f"><span>' + (c.status === 'baru' ? 'Menunggu perawat' : 'Ditangani ' + esc(c.perawat || '')) + '</span><b data-since="' + c.waktu + '">' + lama(c.waktu) + '</b></div></div>';
    }).join('') + (n > 8 ? '<div class="small" style="opacity:.8;text-align:center">+ ' + (n - 8) + ' panggilan lain</div>' : '')
      : '<div class="tv-empty"><div>✅</div><b>Tidak ada panggilan</b>Semua pasien aman.</div>';
  }
  setInterval(function () {
    document.querySelectorAll('[data-since]').forEach(function (el) { el.textContent = lama(el.getAttribute('data-since')); });
    if (data) document.querySelectorAll('.tv-call').forEach(function (el, i) { var c = data.aktif[i]; if (c) el.classList.toggle('late', telat(c)); });
  }, 1000);

  /* ---------- CODE BLUE ---------- */
  var cbList = [], cbLoop = null, cbTick = null, cbBicara = false;
  function cbLama(iso) { var s = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 1000)); return Math.floor(s / 60) + ':' + ('0' + s % 60).slice(-2); }
  function cbSirene() {
    if (!TV.suara || !unlocked()) return;
    var a = ctx, t = a.currentTime + 0.05, o = a.createOscillator(), g = a.createGain();
    o.type = 'sawtooth';
    for (var i = 0; i < 4; i++) { o.frequency.setValueAtTime(620, t + i * 0.7); o.frequency.linearRampToValueAtTime(1100, t + i * 0.7 + 0.35); o.frequency.linearRampToValueAtTime(620, t + i * 0.7 + 0.7); }
    g.gain.setValueAtTime(0.0001, t); g.gain.exponentialRampToValueAtTime(0.22, t + 0.05); g.gain.setValueAtTime(0.22, t + 2.7); g.gain.exponentialRampToValueAtTime(0.0001, t + 2.8);
    o.connect(g); g.connect(a.destination); o.start(t); o.stop(t + 2.85);
  }
  function cbSiklus() {
    if (!cbList.length) return;
    cbSirene();
    setTimeout(function () {
      if (!TV.suara || !cbList.length || !('speechSynthesis' in window) || cbBicara) return;
      var u = new SpeechSynthesisUtterance(cbList[0].ucapan); u.lang = 'id-ID'; u.rate = 0.9;
      cbBicara = true; u.onend = u.onerror = function () { cbBicara = false; };
      speechSynthesis.cancel(); speechSynthesis.speak(u);
    }, 3000);
  }
  function codeBlue(list) {
    cbList = list || []; window.tvCbAktif = cbList.length > 0;
    var box = $('tvCb');
    if (!cbList.length) { box.hidden = true; clearInterval(cbLoop); cbLoop = null; clearInterval(cbTick); cbTick = null; return; }
    var c = cbList[0];
    $('tvCbLok').textContent = c.lokasi; $('tvCbPs').textContent = c.pasien ? 'Pasien: ' + c.pasien : '';
    var meta = function () { $('tvCbMeta').textContent = 'Sejak ' + c.jam + ' · ' + cbLama(c.waktu) + ' · diaktifkan ' + (c.oleh || '-'); };
    meta(); clearInterval(cbTick); cbTick = setInterval(meta, 1000);
    $('tvCbMore').innerHTML = cbList.length > 1 ? 'Juga aktif: ' + cbList.slice(1).map(function (x) { return '<b>' + esc(x.lokasi) + '</b>'; }).join(', ') : '';
    $('tvAlert').hidden = true; box.hidden = false;
    if (!cbLoop) { cbSiklus(); cbLoop = setInterval(cbSiklus, 9000); }
  }

  /* ---------- Polling ---------- */
  var live = $('tvLive'), gagal = 0, sigSrv = '', lastFull = 0, busy = false;
  // Cek perubahan tiap 1,5 detik (ringan). Data lengkap diambil hanya saat ada perubahan, atau tiap 20 detik untuk daftar pasien.
  function poll() {
    if (busy) return; busy = true;
    var full = !sigSrv || Date.now() - lastFull > 20000;
    fetch(TV.api + (full ? '' : '&sig=' + encodeURIComponent(sigSrv)), { cache: 'no-store', credentials: 'same-origin' }).then(function (r) {
      return r.text().then(function (t) {
        var d; try { d = JSON.parse(t); } catch (e) { throw new Error('Respon server bukan JSON (HTTP ' + r.status + '): ' + t.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 200)); }
        if (!d || d.ok === false) throw new Error((d && d.msg) || ('HTTP ' + r.status));
        return d;
      });
    }).then(function (d) {
      busy = false; $('tvErr').hidden = true;
      if (d.same) { gagal = 0; live.classList.remove('off'); live.lastChild.textContent = ' Real-time'; return; }
      sigSrv = d.sig || ''; lastFull = Date.now();
      codeBlue(d.codeblue || []);
      gagal = 0; live.classList.remove('off'); live.lastChild.textContent = ' Real-time';
      data = d;
      $('sPasien').textContent = d.stat.pasien; $('sBaru').textContent = d.stat.baru; $('sProses').textContent = d.stat.diproses;
      $('sHari').textContent = d.stat.hari_ini; $('sRespon').textContent = d.stat.respon;
      var sig = JSON.stringify([d.pasien.map(function (p) { return [p.id, p.nama, p.lokasi, p.call && [p.call.id, p.call.status, p.call.perawat]]; })]);
      if (sig !== lastSig) { lastSig = sig; try { renderBeds(); } catch (e) { lastSig = ''; throw new Error('Gagal menampilkan daftar pasien: ' + e.message); } }
      renderCalls(); paginate(); // ukur ulang setelah panel panggilan terisi (tinggi area pasien bisa berubah)
      d.aktif.forEach(function (c) {
        if (c.status === 'baru' && !seen[c.id]) { seen[c.id] = 1; queue.push(c); }
      });
      try { sessionStorage.setItem('tv_seen', JSON.stringify(seen)); } catch (e) {}
      first = false; nextAlert();
    }).catch(function (e) {
      busy = false;
      if (e === 0) return;
      var msg = (e && e.message) || String(e);
      if (window.console) console.warn('[NurseCall TV] gagal memuat data:', e);
      if (++gagal >= 2 || !data) {
        live.classList.add('off'); live.lastChild.textContent = ' Koneksi terputus…';
        // tampilkan penyebabnya di layar agar mudah diperbaiki
        $('tvErr').hidden = false; $('tvErrMsg').textContent = msg + ' — alamat: ' + TV.api.replace(/k=[^&]+/, 'k=…');
      }
    });
  }
  poll(); setInterval(poll, 1500);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });

  /* ---------- Layar penuh, layar tetap menyala, muat ulang berkala ---------- */
  function fs() { var el = document.documentElement; if (!document.fullscreenElement && el.requestFullscreen) el.requestFullscreen().catch(function () {}); }
  $('tvFs').addEventListener('click', fs);
  document.addEventListener('keydown', function (e) { if (e.key === 'f' || e.key === 'F') fs(); });
  var lock = null;
  function wake() { if (navigator.wakeLock && document.visibilityState === 'visible') navigator.wakeLock.request('screen').then(function (l) { lock = l; }).catch(function () {}); }
  wake(); document.addEventListener('visibilitychange', wake);
  setTimeout(function () { location.reload(); }, 6 * 3600 * 1000); // segarkan tiap 6 jam
  var a0 = ac(); if (a0) a0.resume().then(updUnlock, updUnlock);
  setTimeout(updUnlock, 800);
})();