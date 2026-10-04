<?php
/**
 * DISPLAY TV — layar besar di ruang perawat / nurse station.
 * Menampilkan semua pasien dirawat (per bed) dan panggilan yang masuk secara real-time.
 * Buka di browser TV / mini PC / Android TV Box:  display.php?k=KUNCI&ruang=KD_BANGSAL  (link ada di menu Display TV)
 */
if (!is_file(__DIR__ . '/inc/bootstrap.php')) { header('Content-Type: text/plain; charset=utf-8'); exit('Salah folder: display.php (halaman TV) harus di folder utama nursecalldigital/, bukan di folder api/.'); }
require __DIR__ . '/inc/bootstrap.php';
$key = (string)($_GET['k'] ?? '');
$ok = setting('display_key') !== '' && hash_equals(setting('display_key'), $key);
if (!$ok && is_login() && bisa('station')) { $ok = true; $key = display_key(); } // petugas yang login boleh membuka tanpa kunci
$ruang = substr(preg_replace('/[^\w.-]/', '', (string)($_GET['ruang'] ?? '')), 0, 5);
$r = $ruang !== '' ? q('SELECT kd_bangsal, nm_bangsal nama, NULL lantai FROM bangsal WHERE kd_bangsal = ?', [$ruang])->fetch() : null;
if ($r) $r['nama'] = nama_rapi($r['nama'], true);
if ($ruang !== '' && !$r) $ok = false;
$title = 'Display TV' . ($r ? ' ' . $r['nama'] : '') . ' - ' . APP_NAME;
$bodyClass = 'tv'; $noInstall = true;
require __DIR__ . '/inc/head.php';
?>
<link rel="stylesheet" href="<?= aset('assets/display.css') ?>">
<?php if (!$ok): ?>
<div class="tv-invalid"><?= logo_svg(64) ?><h1>Link display tidak valid</h1><p>Minta Admin / Kepala Ruangan membuka menu <b>Display TV</b> lalu salin link untuk layar ini.</p></div>
</body></html>
<?php exit; endif; ?>

<header class="tv-top">
  <div class="tv-brand"><span class="tv-logo"><?= logo_svg(46) ?></span>
    <div><b>NURSE CALL</b><span><?= e(setting('nama_rs')) ?> · <?= $r ? 'Ruang ' . e($r['nama']) . ($r['lantai'] ? ' · ' . e($r['lantai']) : '') : 'Semua Ruang' ?></span></div></div>
  <div class="tv-stats">
    <div><b id="sPasien">-</b><span>Pasien</span></div>
    <div class="red"><b id="sBaru">-</b><span>Menunggu</span></div>
    <div class="blue"><b id="sProses">-</b><span>Ditangani</span></div>
    <div><b id="sHari">-</b><span>Panggilan hari ini</span></div>
    <div><b id="sRespon">-</b><span>Rata-rata respon</span></div>
  </div>
  <div class="tv-clock"><b id="tvJam">--:--</b><span id="tvTgl"></span></div>
</header>

<main class="tv-main">
  <section class="tv-beds-wrap">
    <div class="tv-sec"><h2>Daftar Pasien</h2><span class="tv-legend"><i class="lg-ok"></i>Aman <i class="lg-baru"></i>Memanggil <i class="lg-proses"></i>Ditangani</span><span id="tvPage" class="tv-page"></span></div>
    <div class="tv-beds" id="beds"></div>
  </section>
  <aside class="tv-side">
    <div class="tv-sec"><h2>Panggilan Aktif</h2><span class="tv-count" id="aktifN">0</span></div>
    <div class="tv-calls" id="aktif"></div>
  </aside>
</main>

<footer class="tv-foot">
  <span class="tv-live" id="tvLive"><i></i> Real-time</span>
  <div class="tv-marquee"><span><?= e(setting('display_teks')) ?></span></div>
  <span class="tv-copy">© <?= date('Y') ?> <b>Nurse Call Digital</b> · Powered by www.fixdigitech.com · 082177846209</span>
</footer>

<!-- Pengumuman panggilan baru (layar penuh) -->
<div class="tv-alert" id="tvAlert" hidden>
  <div class="box">
    <div class="bell"><?= icon('bell', 90) ?></div>
    <div class="t">PANGGILAN PERAWAT</div>
    <div class="loc" id="aLoc"></div>
    <div class="nm" id="aNama"></div>
    <div class="kel" id="aKel"></div>
  </div>
</div>
<div class="tv-err" id="tvErr" hidden><b>⚠️ Data display gagal dimuat</b><span id="tvErrMsg"></span><small>Layar akan mencoba lagi otomatis setiap 4 detik. Foto pesan ini untuk petugas IT.</small></div>
<!-- CODE BLUE (layar penuh biru berkedip, berbunyi sampai ditandai selesai) -->
<div class="tv-cb" id="tvCb" hidden>
  <div class="tv-cb-in">
    <div class="tv-cb-t">CODE BLUE</div>
    <div class="tv-cb-lok" id="tvCbLok"></div>
    <div class="tv-cb-ps" id="tvCbPs"></div>
    <div class="tv-cb-meta" id="tvCbMeta"></div>
    <div class="tv-cb-more" id="tvCbMore"></div>
  </div>
</div>
<button class="tv-unlock" id="tvUnlock" hidden>🔊 Klik / tekan OK pada remote untuk mengaktifkan suara</button>
<button class="tv-fs" id="tvFs" title="Layar penuh (F)">⛶</button>

<script>window.TV = <?= json_encode([
    'api' => 'api/data_tv.php?k=' . rawurlencode($key) . ($ruang !== '' ? '&ruang=' . rawurlencode($ruang) : ''), // relatif: tetap jalan walau dibuka lewat IP / nama host lain
    'suara' => setting('display_suara', '1') === '1', 'tz' => date_default_timezone_get(), 'zona' => zona(),
]) ?>;</script>
<script src="<?= aset('assets/js/display.js') ?>"></script>
</body>
</html>