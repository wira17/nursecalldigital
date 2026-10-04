/* Pop-up "Buat Gelang": tombol [data-gelang="NO_RAWAT"] membuka gelang siap cetak di dalam pop-up */
(function () {
  var BASE = window.APP_BASE, m = null, fr, box, nama, nr = '', srcBtn = null;
  var MODEL = { g200: ['Gelang 200 × 27 mm', 190], l100: ['Label 100 × 50 mm', 235], s33: ['Stiker 33 × 15 mm', 150] };
  var tipe = 'g200'; try { var t0 = localStorage.getItem('nc_label'); if (MODEL[t0]) tipe = t0; } catch (e) {}
  function buat() {
    m = document.createElement('div'); m.className = 'gl-modal'; m.hidden = true;
    m.innerHTML = '<div class="gl-box"><div class="gl-head"><b id="glNama"></b>' +
      '<button type="button" class="btn sm light" data-t="g200">200×27</button><button type="button" class="btn sm light" data-t="l100">100×50</button><button type="button" class="btn sm light" data-t="s33">33×15</button>' +
      '<button type="button" class="btn sm light" data-x aria-label="Tutup">✕</button></div>' +
      '<iframe title="Pratinjau gelang"></iframe>' +
      '<div class="gl-foot"><span class="small muted" id="glInfo">Memuat…</span><span class="sp"></span>' +
      '<a class="btn light" id="glFull" target="_blank">Buka halaman</a><button type="button" class="btn" id="glCetak">🖨️ Cetak</button></div></div>';
    document.body.appendChild(m);
    fr = m.querySelector('iframe'); box = m.querySelector('.gl-box'); nama = m.querySelector('#glNama');
    m.addEventListener('click', function (e) { if (e.target === m || e.target.closest('[data-x]')) tutup(); });
    m.querySelectorAll('[data-t]').forEach(function (b) { b.addEventListener('click', function () { tipe = b.getAttribute('data-t'); try { localStorage.setItem('nc_label', tipe); } catch (e) {} muat(); }); });
    m.querySelector('#glCetak').addEventListener('click', function () {
      try { fr.contentWindow.cetak(); if (srcBtn) { srcBtn.textContent = '🖨️ Cetak ulang'; srcBtn.classList.add('ghost'); var bd = srcBtn.closest('[data-row]'); if (bd) bd.querySelectorAll('[data-gl-status]').forEach(function (s) { s.className = 'badge st-selesai'; s.textContent = 'Gelang ✓'; }); } }
      catch (e) { alert('Gelang belum selesai dimuat.'); }
    });
    fr.addEventListener('load', function () {
      var ok = false; try { ok = !!fr.contentWindow.cetak; } catch (e) {}
      m.querySelector('#glInfo').textContent = ok ? MODEL[tipe][0] + ' — hitam-putih, siap dicetak di kertas stiker' : 'Gelang tidak bisa dibuat untuk pasien ini.';
      m.querySelector('#glCetak').disabled = !ok;
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && m && !m.hidden) tutup(); });
  }
  function muat() {
    var q = 'gelang.php?rawat=' + encodeURIComponent(nr) + '&tipe=' + tipe;
    fr.style.height = MODEL[tipe][1] + 'px';
    m.querySelectorAll('[data-t]').forEach(function (b) { b.classList.toggle('light', b.getAttribute('data-t') !== tipe); });
    m.querySelector('#glInfo').textContent = 'Memuat…'; m.querySelector('#glCetak').disabled = true;
    fr.src = BASE + q + '&polos=1'; m.querySelector('#glFull').href = BASE + q;
  }
  function tutup() { m.hidden = true; fr.src = 'about:blank'; document.body.style.overflow = ''; }
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-gelang]'); if (!b || e.ctrlKey || e.metaKey || e.shiftKey || e.button > 0) return;
    e.preventDefault(); if (!m) buat();
    srcBtn = b; nr = b.getAttribute('data-gelang');
    nama.textContent = 'Gelang · ' + (b.getAttribute('data-nama') || nr);
    m.hidden = false; document.body.style.overflow = 'hidden'; muat();
  });
})();