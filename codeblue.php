<?php
/**
 * CODE BLUE — pemetaan kamar dari SIMRS Khanza. Klik kamar -> konfirmasi -> alarm Code Blue
 * berbunyi di SEMUA nurse station & Display TV sampai ditandai selesai.
 */
require __DIR__ . '/inc/bootstrap.php';
require_akses('codeblue');

$kamar = q("SELECT k.kd_kamar, k.kd_bangsal, b.nm_bangsal, k.kelas, k.status,
              (SELECT GROUP_CONCAT(ps.nm_pasien ORDER BY ki.tgl_masuk SEPARATOR '|') FROM kamar_inap ki
                 JOIN reg_periksa rp ON rp.no_rawat = ki.no_rawat JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
                WHERE ki.kd_kamar = k.kd_kamar AND ki.stts_pulang = '-') pasien
            FROM kamar k JOIN bangsal b ON b.kd_bangsal = k.kd_bangsal
            WHERE k.statusdata = '1' AND b.status = '1'
            ORDER BY b.nm_bangsal, k.kd_kamar")->fetchAll();
$grup = [];
foreach ($kamar as $k) $grup[$k['kd_bangsal']]['nama'] = nama_rapi($k['nm_bangsal'], true);
foreach ($kamar as $k) $grup[$k['kd_bangsal']]['kamar'][] = $k;
$aktif = codeblue_aktif();
$aktifKamar = array_flip(array_filter(array_column($aktif, 'kd_kamar')));
$riwayat = q("SELECT c.*, COALESCE(u.nama, c.oleh) nama_oleh, COALESCE(us.nama, c.selesai_oleh) nama_selesai,
                TIMESTAMPDIFF(SECOND, c.waktu, c.waktu_selesai) durasi
              FROM nc_codeblue_wira c LEFT JOIN nc_user_wira u ON u.id_user = c.oleh LEFT JOIN nc_user_wira us ON us.id_user = c.selesai_oleh
              WHERE c.waktu >= CURDATE() - INTERVAL 6 DAY ORDER BY c.id DESC LIMIT 30")->fetchAll();

$pageTitle = 'Code Blue'; $active = 'codeblue';
layout_top();
?>
<style>
.cb-head{display:flex;align-items:center;gap:14px;flex-wrap:wrap;justify-content:space-between;margin:8px 0 16px}
.cb-head h1{margin:0;font-size:22px;display:flex;align-items:center;gap:10px}
.cb-head h1 i{display:grid;place-items:center;width:38px;height:38px;border-radius:12px;background:#1e5bd8;color:#fff;font-style:normal}
.cb-live{display:grid;gap:10px;margin-bottom:16px}
.cb-live .it{display:flex;align-items:center;gap:14px;background:linear-gradient(135deg,#1e5bd8,#1447b8);color:#fff;border-radius:14px;padding:14px 16px;animation:cbpulse 1.2s ease-in-out infinite}
.cb-live .it b{font-size:18px;display:block}.cb-live .it small{opacity:.9}
.cb-live .it .sp{flex:1}
.cb-live .it .btn{background:#fff;color:#1447b8}
@keyframes cbpulse{50%{box-shadow:0 0 0 6px rgba(30,91,216,.25)}}
.cb-tools{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
.cb-tools>div{flex:1 1 220px;min-width:0}.cb-tools label{margin-top:0}
.cb-tools input,.cb-tools select{height:46px}
.cb-lain{display:flex;gap:8px}.cb-lain input{flex:1;min-width:0}
.cb-lain .btn{height:46px;white-space:nowrap;background:#1e5bd8}
.cb-sec{margin:22px 0 10px;display:flex;align-items:center;gap:10px}
.cb-sec h2{margin:0;font-size:15px;text-transform:uppercase;letter-spacing:.06em;color:var(--pri-2)}
.cb-sec span{font-size:12.5px;color:var(--muted)}
.cb-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:10px}
.cb-k{position:relative;text-align:left;border:1px solid var(--line);border-radius:14px;background:#fff;padding:12px 14px 12px 16px;font:inherit;color:var(--text);cursor:pointer;min-height:92px;display:flex;flex-direction:column;gap:3px;transition:border-color .15s,box-shadow .15s,transform .1s;overflow:hidden}
.cb-k::before{content:"";position:absolute;left:0;top:10px;bottom:10px;width:4px;border-radius:0 4px 4px 0;background:#cfd6e0}
.cb-k.isi::before{background:var(--pri)}
.cb-k:hover{border-color:#1e5bd8;box-shadow:0 4px 14px rgba(30,91,216,.15)}
.cb-k:active{transform:scale(.98)}
.cb-k b{font-size:16px}.cb-k small{font-size:12px;color:var(--muted)}
.cb-k .ps{font-size:13px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cb-k .ps.kosong{color:var(--muted);font-weight:400}
.cb-k.on{background:#1e5bd8;border-color:#1e5bd8;color:#fff;animation:cbpulse 1.2s infinite}.cb-k.on small,.cb-k.on .ps{color:#fff}
.cb-k.on::before{background:#fff}
.cb-hidden{display:none!important}
/* dialog konfirmasi */
.cb-modal{position:fixed;inset:0;z-index:140;background:rgba(10,20,40,.55);display:grid;place-items:center;padding:18px}
.cb-modal[hidden]{display:none}
.cb-box{width:100%;max-width:420px;background:#fff;border-radius:22px;padding:24px 22px 20px;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.3);animation:pop .2s ease-out}
.cb-box .ic{display:inline-grid;place-items:center;width:64px;height:64px;border-radius:50%;background:#e7eefc;color:#1e5bd8}
.cb-box h2{margin:12px 0 4px;font-size:20px}
.cb-box .lok{font-size:22px;font-weight:800;color:#1447b8;margin:6px 0 2px}
.cb-box p{margin:6px 0 0;color:var(--muted);font-size:14px}
.cb-box .act{display:grid;grid-template-columns:1fr 1.4fr;gap:10px;margin-top:20px}
.cb-box .act .btn{height:50px;font-size:16px}
.cb-box .go{background:#1e5bd8}.cb-box .go:hover{background:#1447b8}
</style>

<div class="cb-head">
  <h1><i><?= icon('heart', 22) ?></i> Code Blue</h1>
  <div class="small muted">Klik kamar untuk membunyikan alarm <b>Code Blue</b> di semua nurse station &amp; Display TV.</div>
</div>

<div class="cb-live" id="cbLive">
  <?php foreach ($aktif as $c): ?>
    <div class="it"><?= icon('alert', 26) ?><div><b>CODE BLUE · <?= e($c['lokasi']) ?></b>
      <small>Sejak <?= date('H:i', strtotime($c['waktu'])) ?> · oleh <?= e($c['nama_oleh']) ?><?= $c['pasien'] ? ' · ' . e($c['pasien']) : '' ?></small></div>
      <span class="sp"></span><button type="button" class="btn sm" data-cb-selesai="<?= (int)$c['id'] ?>">Tandai selesai</button></div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="cb-tools">
    <div><label for="cbCari">Cari kamar / ruang / pasien</label><input id="cbCari" placeholder="mis. Aster, A 3A, nama pasien…" autocomplete="off"></div>
    <div style="flex:0 1 240px"><label for="cbRuang">Ruang</label><select id="cbRuang"><option value="">Semua ruang</option>
      <?php foreach ($grup as $kd => $g): ?><option value="<?= e($kd) ?>"><?= e($g['nama']) ?></option><?php endforeach; ?></select></div>
    <div><label for="cbLain">Lokasi lain (di luar kamar)</label>
      <div class="cb-lain"><input id="cbLain" maxlength="120" placeholder="mis. Lobi IGD, Ruang Tunggu Poli"><button type="button" class="btn" id="cbLainBtn">Code Blue</button></div></div>
  </div>
</div>

<?php if (!$grup): ?><div class="card"><?= empty_box('Belum ada kamar aktif di Khanza.', 'bed') ?></div><?php endif; ?>
<?php foreach ($grup as $kd => $g): ?>
  <section data-ruang="<?= e($kd) ?>">
    <div class="cb-sec"><h2><?= e($g['nama']) ?></h2><span><?= count($g['kamar']) ?> kamar</span></div>
    <div class="cb-grid">
      <?php foreach ($g['kamar'] as $k): $ps = $k['pasien'] ? array_map('nama_rapi', explode('|', $k['pasien'])) : []; ?>
        <button type="button" class="cb-k <?= $ps ? 'isi' : '' ?> <?= isset($aktifKamar[$k['kd_kamar']]) ? 'on' : '' ?>"
          data-kamar="<?= e($k['kd_kamar']) ?>" data-lokasi="<?= e($g['nama'] . ' · ' . $k['kd_kamar']) ?>" data-pasien="<?= e(implode(', ', $ps)) ?>"
          data-cari="<?= e(mb_strtolower($g['nama'] . ' ' . $k['kd_kamar'] . ' ' . implode(' ', $ps))) ?>">
          <b><?= e($k['kd_kamar']) ?></b>
          <small><?= e($k['kelas'] ?: '') ?></small>
          <span class="ps <?= $ps ? '' : 'kosong' ?>"><?= $ps ? e(implode(', ', $ps)) : 'Kosong' ?></span>
        </button>
      <?php endforeach; ?>
    </div>
  </section>
<?php endforeach; ?>

<div class="card flush mt" style="margin-top:24px">
  <div class="card-head" style="padding:16px 18px 0"><h2 style="margin:0">Riwayat Code Blue (7 hari)</h2></div>
  <?php if (!$riwayat): ?><div style="padding:16px 18px" class="muted small">Belum ada Code Blue.</div><?php else: ?>
  <div class="table-wrap"><table>
    <tr><th>Waktu</th><th>Lokasi</th><th>Pasien</th><th>Diaktifkan oleh</th><th>Selesai</th><th>Durasi</th></tr>
    <?php foreach ($riwayat as $r): ?>
      <tr><td class="nowrap"><?= tgl_indo($r['waktu'], true) ?></td><td><b><?= e($r['lokasi']) ?></b></td><td class="small"><?= e($r['pasien'] ?: '-') ?></td>
        <td class="small"><?= e($r['nama_oleh']) ?></td>
        <td class="small"><?= $r['status'] === 'aktif' ? '<span class="badge st-baru">AKTIF</span>' : e(($r['nama_selesai'] ?: '-') . ($r['catatan'] ? ' · ' . $r['catatan'] : '')) ?></td>
        <td class="small nowrap"><?= $r['durasi'] !== null ? durasi((int)$r['durasi']) : '-' ?></td></tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</div>

<div class="cb-modal" id="cbModal" hidden>
  <div class="cb-box" role="alertdialog" aria-labelledby="cbJudul">
    <span class="ic"><?= icon('alert', 32) ?></span>
    <h2 id="cbJudul">Aktifkan CODE BLUE?</h2>
    <div class="lok" id="cbLok"></div>
    <p id="cbPs"></p>
    <p>Alarm akan berbunyi di <b>semua nurse station</b> dan <b>Display TV</b> sampai ditandai selesai.</p>
    <div class="act"><button type="button" class="btn light" data-cb-batal>Batal</button><button type="button" class="btn go" id="cbGo"><?= icon('alert', 18) ?> AKTIFKAN</button></div>
  </div>
</div>

<script>
(function () {
  var BASE = <?= json_encode(base_url()) ?>, CSRF = <?= json_encode(csrf_token()) ?>, pilih = null;
  var m = document.getElementById('cbModal');
  // Dialog buatan aplikasi (dari codeblue.js); cadangan ke dialog browser bila skrip belum termuat
  function konfirmasi(j, p, ok, lanjut) { if (window.ncKonfirmasi) window.ncKonfirmasi(j, p, ok, lanjut); else if (confirm(p)) lanjut(); }
  function pesan(j, p) { if (window.ncPesan) window.ncPesan(j, p); else alert(p); }
  function kirim(data, ok) {
    var fd = new FormData(); fd.append('csrf', CSRF); Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
    fetch(BASE + 'api/aksi_codeblue.php', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); })
      .then(function (d) { if (!d.ok) { pesan('Gagal', d.msg); return; } if (ok) ok(d); }).catch(function () { pesan('Gagal', 'Gagal terhubung ke server.'); });
  }
  function buka(lok, ps, data) { pilih = data; document.getElementById('cbLok').textContent = lok; document.getElementById('cbPs').textContent = ps ? 'Pasien: ' + ps : ''; m.hidden = false; }
  document.querySelectorAll('.cb-k').forEach(function (b) {
    b.addEventListener('click', function () { buka(b.getAttribute('data-lokasi'), b.getAttribute('data-pasien'), { aksi: 'aktifkan', kd_kamar: b.getAttribute('data-kamar') }); });
  });
  document.getElementById('cbLainBtn').addEventListener('click', function () {
    var v = document.getElementById('cbLain').value.trim(); if (!v) { document.getElementById('cbLain').focus(); return; }
    buka(v, '', { aksi: 'aktifkan', lokasi: v });
  });
  m.addEventListener('click', function (e) { if (e.target === m || e.target.closest('[data-cb-batal]')) m.hidden = true; });
  document.getElementById('cbGo').addEventListener('click', function () {
    var b = this; b.disabled = true;
    kirim(pilih, function () { m.hidden = true; if (window.ncPoll) window.ncPoll(); setTimeout(function () { location.reload(); }, 900); });
    setTimeout(function () { b.disabled = false; }, 2000);
  });
  document.addEventListener('click', function (e) {
    var s = e.target.closest('[data-cb-selesai]'); if (!s) return;
    var id = s.getAttribute('data-cb-selesai');
    konfirmasi('Code Blue selesai?', 'Tandai Code Blue ini sudah selesai. Alarm di semua perangkat akan berhenti.', 'Ya, selesai', function () {
      kirim({ aksi: 'selesai', id: id }, function () { location.reload(); });
    });
  });
  // pencarian & filter ruang
  var cari = document.getElementById('cbCari'), ruang = document.getElementById('cbRuang');
  function saring() {
    var q = cari.value.trim().toLowerCase(), r = ruang.value;
    document.querySelectorAll('section[data-ruang]').forEach(function (sec) {
      var tampil = 0, cocokRuang = !r || sec.getAttribute('data-ruang') === r;
      sec.querySelectorAll('.cb-k').forEach(function (k) { var ok = cocokRuang && (!q || k.getAttribute('data-cari').indexOf(q) >= 0); k.classList.toggle('cb-hidden', !ok); if (ok) tampil++; });
      sec.classList.toggle('cb-hidden', !tampil);
    });
  }
  cari.addEventListener('input', saring); ruang.addEventListener('change', saring);
})();
</script>
<?php layout_bottom(); ?>