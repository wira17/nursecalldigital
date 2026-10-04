/* Nurse Call - Nurse Station real-time
 * Dimuat di SEMUA halaman untuk role perawat/karu/admin, sehingga alarm tetap berbunyi
 * walau perawat sedang membuka halaman lain.
 *  - Polling api/poll.php setiap 4 detik
 *  - Panggilan baru -> pop-up NURSE CALL + bunyi alarm + suara (opsional) + notifikasi HP/PC
 *  - Alarm terus berbunyi selama masih ada panggilan berstatus "Baru"
 */
(function () {
  var BASE = window.APP_BASE, NC = window.NC || {};
  var INTERVAL = 2000;
  var $ = function (id) { return document.getElementById(id); };
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var store = {
    get: function (k, d) { try { var v = localStorage.getItem(k); return v === null ? d : v; } catch (e) { return d; } },
    set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
  };
  var dismissed = {};
  try { dismissed = JSON.parse(sessionStorage.getItem('nc_dismiss') || '{}'); } catch (e) {}
  function saveDismiss() { try { sessionStorage.setItem('nc_dismiss', JSON.stringify(dismissed)); } catch (e) {} }
  var announced = {};
  try { announced = JSON.parse(sessionStorage.getItem('nc_announced') || '{}'); } catch (e) {}
  function saveAnnounced() { try { sessionStorage.setItem('nc_announced', JSON.stringify(announced)); } catch (e) {} }

  var soundOn = store.get('nc_sound', '1') === '1';
  var voiceOn = store.get('nc_voice', '1') === '1';
  var lastSig = '', data = null, current = null, firstLoad = true, baseTitle = document.title;

  /* ================= Suara alarm (Web Audio, tanpa file mp3) ================= */
  var ctx = null;
  function audio() {
    if (!ctx) { var AC = window.AudioContext || window.webkitAudioContext; if (!AC) return null; ctx = new AC(); }
    return ctx;
  }
  function unlock() { var a = audio(); if (a && a.state !== 'running') a.resume().then(updSoundBar, updSoundBar); }
  ['click', 'touchstart', 'keydown'].forEach(function (ev) { document.addEventListener(ev, unlock, { passive: true }); });
  function tone(f, t0, dur) {
    var a = audio(), o = a.createOscillator(), g = a.createGain();
    o.type = 'square'; o.frequency.value = f;
    g.gain.setValueAtTime(0.0001, t0); g.gain.exponentialRampToValueAtTime(0.18, t0 + 0.02);
    g.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
    o.connect(g); g.connect(a.destination); o.start(t0); o.stop(t0 + dur + 0.02);
  }
  function suaraDiputar() { return [].some.call(document.querySelectorAll('audio'), function (x) { return !x.paused && !x.ended; }); }
  function voiceHtml(c, auto) {
    if (!c.audio) return '';
    return '<div class="voice"><audio controls preload="' + (auto ? 'auto' : 'none') + '" src="' + esc(c.audio) + '"></audio>' +
      (c.durasi_audio ? '<span class="dur">🎤 ' + Math.floor(c.durasi_audio / 60) + ':' + ('0' + c.durasi_audio % 60).slice(-2) + '</span>' : '') + '</div>';
  }
  function beep() {
    var a = audio(); if (!a || a.state !== 'running' || !soundOn || suaraDiputar() || window.ncCbAktif) return; // Code Blue didahulukan
    var t = a.currentTime + 0.02;
    for (var i = 0; i < 3; i++) { tone(988, t + i * 0.5, 0.22); tone(784, t + i * 0.5 + 0.24, 0.22); }
  }
  function speak(c) {
    if (!voiceOn || !soundOn || !('speechSynthesis' in window)) return;
    var u = new SpeechSynthesisUtterance(c.ucapan || ('Panggilan perawat. ' + c.lokasi + '. ' + c.pasien + '. ' + c.keluhan));
    u.lang = 'id-ID'; u.rate = 0.95;
    setTimeout(function () { speechSynthesis.speak(u); }, 1700);
  }
  setInterval(function () { if (data && data.stat.baru > 0) beep(); }, 3500);

  function updSoundBar() {
    var bar = $('soundBar'); if (!bar) return;
    var running = ctx && ctx.state === 'running';
    bar.classList.toggle('on', !!(running && soundOn));
    $('soundText').innerHTML = !soundOn ? 'Suara alarm <b>dimatikan</b>.' :
      (running ? 'Suara alarm <b>aktif</b>. Biarkan halaman ini terbuka di komputer nurse station.' : 'Klik tombol ini agar alarm bisa berbunyi (aturan browser).');
    $('soundBtn').textContent = !soundOn ? 'Nyalakan suara' : (running ? 'Tes bunyi' : 'Aktifkan suara');
    $('muteBtn').hidden = !soundOn;
    var vb = $('voiceBtn'); if (vb) vb.textContent = voiceOn ? '🗣️ Suara pengumuman: ON' : '🗣️ Suara pengumuman: OFF';
  }
  if ($('soundBtn')) {
    $('soundBtn').addEventListener('click', function () {
      soundOn = true; store.set('nc_sound', '1');
      var a = audio(); if (a) a.resume().then(function () { beep(); updSoundBar(); });
      updSoundBar();
    });
    $('muteBtn').addEventListener('click', function () { soundOn = false; store.set('nc_sound', '0'); updSoundBar(); });
    if ($('voiceBtn')) $('voiceBtn').addEventListener('click', function () { voiceOn = !voiceOn; store.set('nc_voice', voiceOn ? '1' : '0'); updSoundBar(); });
    updSoundBar();
  }

  /* ================= Notifikasi sistem (HP / PC) ================= */
  var nb = $('notifBtn');
  function updNotif() {
    if (!nb) return;
    if (!('Notification' in window)) { nb.hidden = true; return; }
    nb.hidden = Notification.permission === 'granted';
    if (Notification.permission === 'denied') nb.textContent = 'Notifikasi diblokir browser';
  }
  if (nb) nb.addEventListener('click', function () { Notification.requestPermission().then(updNotif); });
  updNotif();
  function notify(c) {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;
    var title = '🔔 NURSE CALL - ' + c.lokasi, opt = { body: c.pasien + ' (' + c.no_rm + ')\n' + (c.audio ? '🎤 ' : '') + c.keluhan + (c.pesan ? ' - ' + c.pesan : ''),
      tag: 'nc-' + c.id, renotify: true, requireInteraction: true, icon: BASE + 'assets/icons/icon-192.png', badge: BASE + 'assets/icons/icon-192.png',
      vibrate: [400, 150, 400, 150, 400], data: { url: BASE + 'panggilan.php?id=' + c.id } };
    if (navigator.serviceWorker && navigator.serviceWorker.ready) {
      navigator.serviceWorker.ready.then(function (r) { r.showNotification(title, opt); }).catch(function () { try { new Notification(title, opt); } catch (e) {} });
    } else { try { new Notification(title, opt); } catch (e) {} }
  }

  /* ================= Pop-up alarm ================= */
  var al = $('alarm');
  function showAlarm() {
    if (!al || !data) return;
    var baru = data.aktif.filter(function (c) { return c.status === 'baru' && !dismissed[c.id]; });
    if (!baru.length) { al.hidden = true; current = null; al.querySelectorAll('audio').forEach(function (x) { x.pause(); }); return; }
    var c = baru[0];
    if (!current || current.id !== c.id) {
      current = c;
      $('alNama').textContent = c.pasien + ' (' + (c.jk === 'P' ? 'P' : 'L') + ', ' + c.umur + ')';
      $('alRm').textContent = c.no_rm;
      $('alRuang').textContent = c.lokasi;
      $('alKel').textContent = c.ikon + ' ' + c.keluhan;
      $('alPesan').textContent = c.pesan || '-';
      $('alPesan').previousElementSibling.hidden = $('alPesan').hidden = !c.pesan;
      $('alWaktu').textContent = c.tgl + (c.pelapor === 'keluarga' ? ' · oleh keluarga' : '');
      var av = $('alVoice'); if (av) { av.innerHTML = voiceHtml(c, true); av.hidden = !c.audio; }
    }
    var m = $('alMore'); m.hidden = baru.length < 2; m.textContent = 'dan ' + (baru.length - 1) + ' panggilan baru lainnya';
    al.hidden = false;
  }
  if (al) {
    $('alTutup').addEventListener('click', function () { if (current) { dismissed[current.id] = 1; saveDismiss(); } current = null; showAlarm(); });
    $('alTangani').addEventListener('click', function () { if (current) aksi(current.id, 'tangani'); });
  }

  /* ================= Aksi Tangani / Selesai ================= */
  function aksi(id, jenis, btn) {
    var tindakan = '';
    if (jenis === 'selesai') {
      tindakan = prompt('Tindakan yang dilakukan (boleh dikosongkan):', '');
      if (tindakan === null) return;
    }
    if (btn) btn.disabled = true;
    var fd = new FormData(); fd.append('ajax', '1'); fd.append('csrf', NC.csrf); fd.append('id', id); fd.append('aksi', jenis); fd.append('tindakan', tindakan);
    fetch(BASE + 'api/aksi.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) { if (!d.ok) alert(d.msg); dismissed[id] = 1; saveDismiss(); current = null; if (!box) { location.reload(); return; } poll(); })
      .catch(function () { alert('Gagal terhubung ke server.'); if (btn) btn.disabled = false; });
  }
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-act]'); if (!b) return;
    ev.preventDefault(); aksi(+b.getAttribute('data-id'), b.getAttribute('data-act'), b);
  });

  /* ================= Render nurse station ================= */
  var box = $('calls');
  function card(c) {
    var late = c.status === 'baru' && (Date.now() - new Date(c.waktu).getTime()) / 1000 > (NC.batas || 300);
    return '<div class="call ' + c.status + (late ? ' late' : '') + '">' +
      '<div class="top"><div><div class="room">' + esc(c.lokasi) + '</div><div class="who">' + esc(c.pasien) + '</div>' +
      '<div class="rm">RM ' + esc(c.no_rm) + ' · ' + (c.jk === 'P' ? 'Perempuan' : 'Laki-laki') + ', ' + esc(c.umur) + '</div></div>' +
      '<div class="right"><span class="badge st-' + c.status + '">' + (c.status === 'baru' ? 'Baru' : 'Diproses') + '</span><div class="mt" style="margin-top:6px"><span class="badge pr-' + c.prioritas + '">' + c.prioritas.charAt(0).toUpperCase() + c.prioritas.slice(1) + '</span></div></div></div>' +
      '<div class="kel"><span class="ic">' + esc(c.ikon) + '</span>' + esc(c.keluhan) + '</div>' +
      (c.pesan ? '<div class="msg">💬 ' + esc(c.pesan) + '</div>' : '') + voiceHtml(c) +
      '<div class="meta"><span>' + esc(c.jam) + (c.pelapor === 'keluarga' ? ' · oleh keluarga' : '') + (c.perawat ? ' · <b>' + esc(c.perawat) + '</b>' : '') + '</span>' +
      '<span>' + (c.status === 'baru' ? 'Menunggu <span class="timer" data-since="' + c.waktu + '">' + sejak(c.waktu) + '</span>' : 'Respon ' + (c.detik_respon < 60 ? c.detik_respon + ' dtk' : Math.floor(c.detik_respon / 60) + ' mnt')) + '</span></div>' +
      '<div class="act">' + (c.status === 'baru' ? '<button class="btn ok" data-act="tangani" data-id="' + c.id + '">Tangani</button>' : '') +
      '<button class="btn ' + (c.status === 'baru' ? 'light' : '') + '" data-act="selesai" data-id="' + c.id + '">Selesai</button>' +
      '<a class="btn light" href="' + BASE + 'panggilan.php?id=' + c.id + '" style="flex:0">Detail</a></div></div>';
  }
  var ST = { baru: 'Baru', diproses: 'Diproses', selesai: 'Selesai' };
  function render() {
    ['baru', 'diproses', 'selesai', 'respon'].forEach(function (k) { var el = $('st_' + k); if (el) el.textContent = data.stat[k]; });
    // Kartu hanya digambar ulang jika datanya berubah (agar pesan suara yang sedang diputar tidak terhenti)
    var sig = JSON.stringify(data.aktif.map(function (c) { return [c.id, c.status, c.perawat, c.status === 'baru' && (Date.now() - new Date(c.waktu).getTime()) / 1000 > (NC.batas || 300)]; }));
    if (box && sig !== lastSig && ![].some.call(box.querySelectorAll('audio'), function (x) { return !x.paused && !x.ended; })) { lastSig = sig; box.innerHTML = data.aktif.length ? data.aktif.map(card).join('') :
      '<div class="card" style="grid-column:1/-1"><div class="empty"><div style="font-size:42px">✅</div><div><b>Tidak ada panggilan aktif.</b><br>Semua pasien sudah ditangani.</div></div></div>'; }
    var tb = $('todayBody');
    if (tb) tb.innerHTML = data.hari_ini.length ? data.hari_ini.map(function (c, i) {
      return '<tr><td>' + (i + 1) + '</td><td class="nowrap">' + esc(c.jam) + '</td><td><b>' + esc(c.pasien) + '</b><div class="small muted">' + esc(c.no_rm) + '</div></td><td>' + esc(c.lokasi) + '</td>' +
        '<td>' + esc(c.ikon + ' ' + c.keluhan) + '</td><td><span class="badge st-' + c.status + '">' + ST[c.status] + '</span></td><td class="small">' + esc(c.perawat || '-') + '</td>' +
        '<td class="nowrap">' + (c.status === 'baru' ? '<button class="btn sm ok" data-act="tangani" data-id="' + c.id + '">Tangani</button> ' : '') +
        '<a class="btn sm light" href="' + BASE + 'panggilan.php?id=' + c.id + '">Lihat</a></td></tr>';
    }).join('') : '<tr><td colspan="8" class="center muted">Belum ada panggilan hari ini.</td></tr>';
    var tm = $('todayMob');
    if (tm) tm.innerHTML = data.hari_ini.filter(function (c) { return c.status === 'selesai'; }).slice(0, 6).map(function (c) {
      return '<a class="item" href="' + BASE + 'panggilan.php?id=' + c.id + '"><div class="h"><b>' + esc(c.pasien) + '</b><span class="badge st-selesai">Selesai</span></div>' +
        '<div class="m">' + esc(c.jam) + ' · ' + esc(c.lokasi) + ' · ' + esc(c.keluhan) + (c.perawat ? ' · ' + esc(c.perawat) : '') + '</div></a>';
    }).join('') || '<div class="card"><div class="empty small" style="padding:10px">Belum ada panggilan selesai hari ini.</div></div>';
  }

  /* ================= Polling ================= */
  var live = document.querySelectorAll('[data-live]');
  function setLive(ok, why) { live.forEach(function (el) { el.classList.toggle('off', !ok); el.title = why || ''; el.lastChild.textContent = ok ? ' Terhubung · real-time' : ' Koneksi terputus, mencoba lagi…' + (why && why.indexOf('Failed to fetch') < 0 ? ' (' + why + ')' : ''); }); }
  var busy = false;
  function poll() {
    if (busy) return; busy = true;
    fetch(BASE + 'api/poll.php', { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) {
        if (r.status === 401 || (r.redirected && /login\.php/.test(r.url))) { location.href = BASE + 'login.php'; throw 0; }
        return r.text().then(function (t) {
          var d; try { d = JSON.parse(t); } catch (e) { throw new Error('Respon server bukan JSON: ' + t.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 160)); }
          if (!d || d.ok === false) throw new Error((d && d.msg) || ('HTTP ' + r.status));
          return d;
        });
      })
      .then(function (d) {
        busy = false; setLive(true);
        if (window.ncCodeBlue) window.ncCodeBlue(d.codeblue || []);
        data = d;
        d.aktif.forEach(function (c) {
          if (c.status === 'baru' && !announced[c.id]) {
            announced[c.id] = 1;
            if (!firstLoad || !dismissed[c.id]) { notify(c); speak(c); }
          }
        });
        saveAnnounced();
        firstLoad = false;
        document.querySelectorAll('[data-live-count]').forEach(function (el) { el.textContent = d.stat.baru; el.hidden = !d.stat.baru; });
        document.title = d.stat.baru ? '(' + d.stat.baru + ') 🔔 NURSE CALL' : baseTitle;
        render(); showAlarm();
      })
      .catch(function (e) { busy = false; if (e !== 0) { setLive(false, e && e.message); if (window.console) console.warn('[NurseCall] poll gagal:', e); } });
  }
  window.ncPoll = poll;
  poll();
  setInterval(poll, INTERVAL);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
})();