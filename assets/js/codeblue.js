/* CODE BLUE — layar penuh biru berkedip + sirene + pengumuman suara di SEMUA halaman petugas.
 * Data dikirim oleh station.js (api/poll.php -> d.codeblue) lewat window.ncCodeBlue(list).
 * Berbunyi terus sampai Code Blue ditandai selesai (atau disenyapkan di perangkat ini).
 */
(function () {
  var BASE = window.APP_BASE, NC = window.NC || {};
  var list = [], el = null, loop = null, tick = null, ctx = null, sedangBicara = false;
  var mute = {};
  try { mute = JSON.parse(sessionStorage.getItem('nc_cb_mute') || '{}'); } catch (e) {}
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };

  /* Dialog konfirmasi & pesan buatan aplikasi (pengganti confirm()/alert() bawaan browser yang menampilkan alamat halaman) */
  var dlg = null;
  function dialog(opt) {
    if (!dlg) {
      dlg = document.createElement('div'); dlg.className = 'nc-dlg'; dlg.hidden = true;
      dlg.innerHTML = '<div class="nc-dlg-box" role="alertdialog" aria-modal="true"><div class="nc-dlg-ic"></div><h3></h3><p></p>' +
        '<div class="nc-dlg-act"><button type="button" class="nc-dlg-no">Batal</button><button type="button" class="nc-dlg-ok">OK</button></div></div>';
      document.body.appendChild(dlg);
    }
    var ok = dlg.querySelector('.nc-dlg-ok'), no = dlg.querySelector('.nc-dlg-no');
    dlg.querySelector('h3').textContent = opt.judul || '';
    dlg.querySelector('p').textContent = opt.pesan || '';
    dlg.querySelector('.nc-dlg-ic').innerHTML = opt.ikon || '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>';
    ok.textContent = opt.ok || 'OK'; no.hidden = !opt.batal; no.textContent = opt.batal || 'Batal';
    var tutup = function (hasil) { dlg.hidden = true; ok.onclick = no.onclick = null; if (hasil && opt.lanjut) opt.lanjut(); };
    ok.onclick = function () { tutup(true); }; no.onclick = function () { tutup(false); };
    dlg.hidden = false; setTimeout(function () { ok.focus(); }, 50);
  }
  window.ncKonfirmasi = function (judul, pesan, okLabel, lanjut) { dialog({ judul: judul, pesan: pesan, ok: okLabel, batal: 'Batal', lanjut: lanjut }); };
  window.ncPesan = function (judul, pesan) { dialog({ judul: judul, pesan: pesan, ok: 'OK', ikon: '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>' }); };

  function audio() { if (!ctx) { var A = window.AudioContext || window.webkitAudioContext; if (A) ctx = new A(); } return ctx; }
  ['click', 'touchstart', 'keydown'].forEach(function (ev) { document.addEventListener(ev, function () { var a = audio(); if (a && a.state !== 'running') a.resume(); }, { passive: true }); });

  /* Sirene naik-turun (tanpa file suara) */
  function sirene() {
    var a = audio(); if (!a || a.state !== 'running') return;
    var t = a.currentTime + 0.05, o = a.createOscillator(), g = a.createGain();
    o.type = 'sawtooth';
    for (var i = 0; i < 4; i++) { o.frequency.setValueAtTime(620, t + i * 0.7); o.frequency.linearRampToValueAtTime(1100, t + i * 0.7 + 0.35); o.frequency.linearRampToValueAtTime(620, t + i * 0.7 + 0.7); }
    g.gain.setValueAtTime(0.0001, t); g.gain.exponentialRampToValueAtTime(0.16, t + 0.05); g.gain.setValueAtTime(0.16, t + 2.7); g.gain.exponentialRampToValueAtTime(0.0001, t + 2.8);
    o.connect(g); g.connect(a.destination); o.start(t); o.stop(t + 2.85);
  }
  function umumkan(c) {
    if (!('speechSynthesis' in window) || sedangBicara) return;
    var u = new SpeechSynthesisUtterance(c.ucapan); u.lang = 'id-ID'; u.rate = 0.9; u.volume = 1;
    sedangBicara = true; u.onend = u.onerror = function () { sedangBicara = false; };
    speechSynthesis.cancel(); speechSynthesis.speak(u);
  }
  function aktifDibunyikan() { return list.filter(function (c) { return !mute[c.id]; }); }
  function siklus() {
    var bunyi = aktifDibunyikan(); if (!bunyi.length) return;
    sirene(); setTimeout(function () { umumkan(bunyi[0]); }, 3000);
  }
  function lama(iso) { var s = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 1000)); return Math.floor(s / 60) + ':' + ('0' + s % 60).slice(-2); }

  function buat() {
    el = document.createElement('div'); el.className = 'cb-ov'; el.hidden = true;
    el.innerHTML = '<div class="cb-ov-in"><div class="cb-ov-ic"><svg width="70" height="70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/><path d="M3.5 12h4l1.5-3 3 6 1.5-3h7"/></svg></div>' +
      '<div class="cb-ov-t">CODE BLUE</div><div class="cb-ov-lok" id="cbOvLok"></div><div class="cb-ov-ps" id="cbOvPs"></div>' +
      '<div class="cb-ov-meta" id="cbOvMeta"></div><div class="cb-ov-more" id="cbOvMore"></div>' +
      '<div class="cb-ov-act"><button type="button" class="cb-ov-b2" id="cbOvMute">Senyapkan di perangkat ini</button>' +
      '<button type="button" class="cb-ov-b1" id="cbOvDone">Code Blue selesai</button></div></div>';
    document.body.appendChild(el);
    el.querySelector('#cbOvMute').addEventListener('click', function () {
      list.forEach(function (c) { mute[c.id] = 1; }); try { sessionStorage.setItem('nc_cb_mute', JSON.stringify(mute)); } catch (e) {}
      if ('speechSynthesis' in window) speechSynthesis.cancel(); tampil();
    });
    el.querySelector('#cbOvDone').addEventListener('click', function () {
      var c = list[0]; if (!c) return;
      window.ncKonfirmasi('Code Blue selesai?', 'Tandai CODE BLUE di ' + c.lokasi + ' sudah selesai. Alarm di semua perangkat akan berhenti.', 'Ya, selesai', function () { selesaikan(c); });
    });
    function selesaikan(c) {
      var fd = new FormData(); fd.append('csrf', NC.csrf || ''); fd.append('aksi', 'selesai'); fd.append('id', c.id);
      fetch(BASE + 'api/aksi_codeblue.php', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); })
        .then(function (d) { if (!d.ok) window.ncPesan('Gagal', d.msg); if (window.ncPoll) window.ncPoll(); if (/codeblue\.php/.test(location.pathname)) location.reload(); })
        .catch(function () { window.ncPesan('Gagal', 'Gagal terhubung ke server.'); });
    }
  }
  function tampil() {
    if (!el) buat();
    var bunyi = aktifDibunyikan();
    window.ncCbAktif = bunyi.length > 0;           // station.js menahan bunyi panggilan biasa selama Code Blue
    if (!bunyi.length) {
      el.hidden = true; clearInterval(loop); loop = null; clearInterval(tick); tick = null;
      if ('speechSynthesis' in window && sedangBicara) speechSynthesis.cancel();
      return;
    }
    var c = bunyi[0];
    el.querySelector('#cbOvLok').textContent = c.lokasi;
    el.querySelector('#cbOvPs').textContent = c.pasien ? 'Pasien: ' + c.pasien : '';
    var meta = function () { el.querySelector('#cbOvMeta').textContent = 'Diaktifkan ' + c.jam + ' oleh ' + (c.oleh || '-') + ' · berlangsung ' + lama(c.waktu); };
    meta(); clearInterval(tick); tick = setInterval(meta, 1000);
    el.querySelector('#cbOvMore').innerHTML = bunyi.length > 1 ? 'Juga aktif: ' + bunyi.slice(1).map(function (x) { return '<b>' + esc(x.lokasi) + '</b>'; }).join(', ') : '';
    el.hidden = false;
    if (!loop) { siklus(); loop = setInterval(siklus, 9000); }
  }
  window.ncCodeBlue = function (baru) {
    var lamaIds = list.map(function (c) { return c.id; }).join(','), baruIds = (baru || []).map(function (c) { return c.id; }).join(',');
    list = baru || [];
    if (lamaIds !== baruIds && list.length && 'Notification' in window && Notification.permission === 'granted') {
      try { new Notification('CODE BLUE — ' + list[0].lokasi, { body: list[0].pasien || 'Segera menuju lokasi', tag: 'cb' + list[0].id, requireInteraction: true }); } catch (e) {}
    }
    tampil();
  };
})();