
(function () {
  var R = window.REC || {}, $ = function (id) { return document.getElementById(id); };
  var modal = $('recModal'); if (!modal) return;
  var btn = $('recBtn'), hint = $('recHint'), time = $('recTime'), prev = $('recPreview'), audio = $('recAudio'),
      act = $('recAct'), err = $('recErr'), fb = $('recFallback'), cv = $('recWave'), g = cv.getContext('2d');
  var bisaRekam = !!(window.isSecureContext && navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder);
  var stream = null, rec = null, chunks = [], blob = null, detik = 0, tick = null, t0 = 0, ac = null, an = null, raf = null, mime = '';

  function fmt(s) { return Math.floor(s / 60) + ':' + ('0' + (s % 60)).slice(-2); }
  function showErr(m) { err.textContent = m; err.hidden = !m; }
  function state(s) {
    modal.setAttribute('data-state', s);
    btn.hidden = s === 'siap' || s === 'kirim' || !bisaRekam;
    prev.hidden = s !== 'siap' && s !== 'kirim';
    act.hidden = s !== 'siap' && s !== 'kirim';
    fb.hidden = bisaRekam || s === 'siap' || s === 'kirim';
    btn.classList.toggle('on', s === 'rekam');
    $('recKirim').disabled = s === 'kirim';
    $('recUlang').disabled = s === 'kirim';
    hint.textContent = {
      awal: bisaRekam ? 'Tekan tombol mikrofon, lalu silakan bicara.' : 'Rekam pesan suara Anda untuk perawat.',
      rekam: '🔴 Sedang merekam… silakan bicara. Tekan tombol lagi jika sudah selesai.',
      siap: 'Dengarkan dulu jika perlu, lalu tekan Kirim.',
      kirim: 'Mengirim pesan suara…'
    }[s];
  }
  function open() { modal.hidden = false; document.body.style.overflow = 'hidden'; showErr(''); if (!blob) { reset(); } }
  function close() { stopAll(); modal.hidden = true; document.body.style.overflow = ''; }
  function reset() { blob = null; detik = 0; time.textContent = '0:00'; audio.removeAttribute('src'); drawIdle(); state('awal'); }

  function drawIdle() { g.clearRect(0, 0, cv.width, cv.height); g.fillStyle = '#d5dbe3'; for (var i = 0; i < 40; i++) g.fillRect(i * 15 + 4, 42, 8, 6); }
  function draw() {
    var d = new Uint8Array(an.frequencyBinCount); an.getByteFrequencyData(d);
    g.clearRect(0, 0, cv.width, cv.height);
    g.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--pri') || '#3b7a67';
    for (var i = 0; i < 40; i++) { var v = d[Math.floor(i * d.length / 60)] / 255, h = Math.max(6, v * 86); g.fillRect(i * 15 + 4, 45 - h / 2, 8, h); }
    raf = requestAnimationFrame(draw);
  }
  function stopAll() {
    if (rec && rec.state !== 'inactive') { rec.onstop = null; rec.stop(); }
    if (stream) stream.getTracks().forEach(function (t) { t.stop(); });
    clearInterval(tick); cancelAnimationFrame(raf); if (ac) { ac.close().catch(function () {}); ac = null; }
    stream = null;
  }

  function mulai() {
    showErr('');
    navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true } }).then(function (s) {
      stream = s; chunks = [];
      mime = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'].filter(function (m) { return MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(m); })[0] || '';
      rec = new MediaRecorder(s, mime ? { mimeType: mime, audioBitsPerSecond: 32000 } : undefined);
      rec.ondataavailable = function (e) { if (e.data && e.data.size) chunks.push(e.data); };
      rec.onstop = function () {
        blob = new Blob(chunks, { type: rec.mimeType || mime || 'audio/webm' });
        audio.src = URL.createObjectURL(blob);
        stopAll(); state('siap');
      };
      rec.start(250);
      try { var AC = window.AudioContext || window.webkitAudioContext; ac = new AC(); an = ac.createAnalyser(); an.fftSize = 256; ac.createMediaStreamSource(s).connect(an); draw(); } catch (e) {}
      t0 = Date.now(); detik = 0; time.textContent = '0:00';
      tick = setInterval(function () {
        detik = Math.floor((Date.now() - t0) / 1000); time.textContent = fmt(detik);
        if (detik >= R.maks) selesai();
      }, 250);
      if (navigator.vibrate) navigator.vibrate(60);
      state('rekam');
    }).catch(function (e) {
      showErr(e && e.name === 'NotAllowedError' ? 'Izin mikrofon ditolak. Izinkan akses mikrofon di pengaturan browser, lalu coba lagi.' : 'Mikrofon tidak bisa dipakai di perangkat ini.');
      bisaRekam = false; state('awal');
    });
  }
  function selesai() {
    detik = Math.max(1, Math.round((Date.now() - t0) / 1000));
    if (rec && rec.state !== 'inactive') rec.stop();
    clearInterval(tick); cancelAnimationFrame(raf);
  }
  btn.addEventListener('click', function () { if (modal.getAttribute('data-state') === 'rekam') selesai(); else mulai(); });

  // Cadangan: aplikasi perekam bawaan HP
  $('recFile').addEventListener('change', function () {
    var f = this.files[0]; if (!f) return;
    if (f.size > 4 * 1024 * 1024) { showErr('Rekaman terlalu besar (maks. 4 MB). Rekam lebih singkat.'); return; }
    blob = f; audio.src = URL.createObjectURL(f); detik = 0;
    audio.onloadedmetadata = function () { if (isFinite(audio.duration)) { detik = Math.round(audio.duration); time.textContent = fmt(detik); } };
    showErr(''); state('siap');
  });

  $('recUlang').addEventListener('click', function () { reset(); if (bisaRekam) mulai(); });
  $('recKirim').addEventListener('click', function () {
    if (!blob) return;
    state('kirim');
    var ext = /mp4|aac|m4a/.test(blob.type) ? 'm4a' : (/ogg/.test(blob.type) ? 'ogg' : 'webm');
    var fd = new FormData(), pl = document.querySelector('[name=pelapor]:checked');
    fd.append('aksi', 'suara'); fd.append('csrf', R.csrf); fd.append('durasi', detik); fd.append('pelapor', pl ? pl.value : 'pasien');
    fd.append('audio', blob, blob.name || ('pesan.' + ext));
    fetch(R.url, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) { if (d.ok) { location.href = d.url; } else { showErr(d.msg); state('siap'); } })
      .catch(function () { showErr('Gagal mengirim. Periksa koneksi internet lalu coba lagi.'); state('siap'); });
  });

  document.querySelectorAll('[data-mic-open]').forEach(function (b) { b.addEventListener('click', open); });
  document.querySelectorAll('[data-rec-close]').forEach(function (b) { b.addEventListener('click', close); });
  modal.addEventListener('click', function (e) { if (e.target === modal && modal.getAttribute('data-state') !== 'rekam') close(); });
  reset();
})();
