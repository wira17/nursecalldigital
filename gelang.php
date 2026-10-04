<?php

require __DIR__ . '/inc/bootstrap.php';
require_akses('gelang');
$nr = (string)($_GET['rawat'] ?? $_POST['rawat'] ?? '');
$polos = !empty($_GET['polos']);
// 3 model label (hitam-putih, untuk kertas stiker): g200 = gelang 200×27 mm · l100 = label 100×50 mm · s33 = stiker 33×15 mm
const MODEL_LABEL = [
    'g200' => ['Gelang 200 × 27 mm', 200, 27],
    'l100' => ['Label 100 × 50 mm', 100, 50],
    's33'  => ['Stiker 33 × 15 mm', 33, 15],
];
$tipe = (string)($_GET['tipe'] ?? 'g200');
if ($tipe === 'kartu') $tipe = 'l100';                       // link lama
if (!isset(MODEL_LABEL[$tipe])) $tipe = 'g200';

$p = data_gelang($nr);
$gagal = null;
if (!$p) $gagal = 'Registrasi tidak ditemukan di Khanza.';
elseif ($p['batal']) $gagal = 'Registrasi ini berstatus Batal di Khanza.';
elseif ($p['pulang']) $gagal = 'Pasien sudah pulang dari rawat inap, gelang tidak bisa dicetak.';
if ($gagal) {
    if ($polos) { $title = 'Gelang'; $noInstall = true; require __DIR__ . '/inc/head.php'; echo '<div class="alert err" style="margin:16px">' . e($gagal) . '</div></body></html>'; exit; }
    flash($gagal, 'err'); redirect(base_url('admisi.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); q('UPDATE nc_gelang_wira SET dicetak = dicetak + 1 WHERE no_rawat = ?', [$nr]); json_out(['ok' => true]); }
// Gelang lama sudah dinonaktifkan (pasien sempat pulang/batal) tetapi kini dirawat lagi -> QR baru, QR lama tetap mati
if ($p['token'] && $p['nonaktif']) {
    q('UPDATE nc_gelang_wira SET token = ?, dibuat = NOW(), dibuat_oleh = ?, dicetak = 0, nonaktif = NULL WHERE no_rawat = ?', [bin2hex(random_bytes(12)), user()['id_user'], $nr]);
    $p['token'] = q('SELECT token FROM nc_gelang_wira WHERE no_rawat = ?', [$nr])->fetchColumn();
}
// Token QR dibuat otomatis saat gelang pertama kali dibuka
if (!$p['token']) {
    q('INSERT IGNORE INTO nc_gelang_wira (no_rawat, token, dibuat, dibuat_oleh) VALUES (?, ?, NOW(), ?)', [$nr, bin2hex(random_bytes(12)), user()['id_user']]);
    $p['token'] = q('SELECT token FROM nc_gelang_wira WHERE no_rawat = ?', [$nr])->fetchColumn();
}
$url = url_lapor($p['token']);
$lokalan = preg_match('#^https?://(localhost|127\.|\[::1\])#', $url);
$enc = rawurlencode($nr);

/** HTML label sesuai model — hitam-putih, tanpa warna (untuk kertas stiker) */
function html_gelang(array $p, string $url, string $tipe): string {
    $rs = setting('nama_rs');
    $tgl = $p['tgl_lahir'] && $p['tgl_lahir'] !== '0000-00-00' ? date('d-m-Y', strtotime($p['tgl_lahir'])) : '-';
    $jk = $p['jk'] === 'P' ? 'P' : 'L';
    $unit = $p['ranap'] ? lokasi($p) : ($p['poli'] ?: '-');
    $bel = '<svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>';
    ob_start();
    if ($tipe === 'g200'): ?>
<div class="ncl-wrap"><div class="ncl ncl-g200">
  <div class="qr" data-qr="<?= e($url) ?>"></div>
  <div class="id">
    <div class="rs"><?= e($rs) ?></div>
    <b class="nm"><?= e(mb_strtoupper($p['nama'])) ?></b>
    <table><tr><td>No. RM</td><td>: <b><?= e($p['no_rm']) ?></b></td><td>Tgl Lahir</td><td>: <?= $tgl ?> (<?= $jk ?>)</td></tr>
      <tr><td><?= $p['ranap'] ? 'Ruang' : 'Unit' ?></td><td>: <?= e($unit) ?></td><td>Masuk</td><td>: <?= date('d-m-Y', strtotime($p['tgl_masuk'])) ?></td></tr>
      <tr><td>No. Rawat</td><td colspan="3">: <?= e($p['no_rawat']) ?><?= $p['dokter'] ? ' · ' . e($p['dokter']) : '' ?></td></tr></table>
    <?php if ($p['alergi']): ?><div class="alg">ALERGI: <?= e($p['alergi']) ?></div><?php endif; ?>
  </div>
  <div class="scan"><?= $bel ?><span>SCAN UNTUK<br>PANGGIL<br>PERAWAT</span></div>
</div></div>
<?php elseif ($tipe === 'l100'): ?>
<div class="ncl-wrap"><div class="ncl ncl-l100">
  <div class="kiri"><div class="qr" data-qr="<?= e($url) ?>"></div><div class="cap"><?= $bel ?> SCAN PANGGIL PERAWAT</div></div>
  <div class="id">
    <div class="rs"><?= e($rs) ?></div>
    <b class="nm"><?= e(mb_strtoupper($p['nama'])) ?></b>
    <dl>
      <dt>No. RM</dt><dd><b><?= e($p['no_rm']) ?></b></dd>
      <dt>Tgl Lahir</dt><dd><?= $tgl ?> (<?= $jk ?>)</dd>
      <dt><?= $p['ranap'] ? 'Ruang' : 'Unit' ?></dt><dd><?= e($unit) ?></dd>
      <dt>Masuk</dt><dd><?= date('d-m-Y', strtotime($p['tgl_masuk'])) ?></dd>
      <dt>No. Rawat</dt><dd><?= e($p['no_rawat']) ?></dd>
      <?php if ($p['dokter']): ?><dt>DPJP</dt><dd><?= e($p['dokter']) ?></dd><?php endif; ?>
    </dl>
    <?php if ($p['alergi']): ?><div class="alg">ALERGI: <?= e($p['alergi']) ?></div><?php endif; ?>
  </div>
</div></div>
<?php else: ?>
<div class="ncl-wrap"><div class="ncl ncl-s33">
  <div class="qr" data-qr="<?= e($url) ?>" data-ec="L"></div>
  <div class="id">
    <b class="nm"><?= e(mb_strtoupper($p['nama'])) ?></b>
    <div>RM <?= e($p['no_rm']) ?> (<?= $jk ?>)</div>
    <div class="cap">SCAN PANGGIL PERAWAT</div>
  </div>
</div></div>
<?php endif;
    return ob_get_clean();
}
/** CSS label: ukuran nyata dalam mm, hitam-putih; saat cetak ukuran kertas = ukuran label, margin 0 */
function css_label(string $tipe): string {
    $m = MODEL_LABEL[$tipe];
    return '<style>
.ncl-wrap{display:flex;justify-content:safe center;padding:14px;overflow-x:auto;max-width:100%}
.ncl{box-sizing:border-box;background:#fff;color:#000;font-family:Arial,Helvetica,sans-serif;overflow:hidden;outline:1px dashed #9aa3b2;outline-offset:0;flex:none}
.ncl *{color:#000}
.ncl .qr svg{width:100%;height:100%;display:block}
.ncl .rs{font-size:6.5pt;font-weight:700;text-transform:uppercase;letter-spacing:.03em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ncl .nm{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ncl .alg{font-weight:700;border:0.3mm solid #000;padding:0 1mm;display:inline-block}
.ncl-g200{width:200mm;height:27mm;display:flex;align-items:center;gap:3mm;padding:0 8mm 0 10mm;border-radius:13.5mm}
.ncl-g200 .qr{width:23mm;height:23mm;flex:none}
.ncl-g200 .id{flex:1;min-width:0;font-size:8.3pt;line-height:1.28}
.ncl-g200 .nm{font-size:11pt}
.ncl-g200 .alg{font-size:7.5pt;margin-top:.3mm;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;box-sizing:border-box}
.ncl-g200 table{border-collapse:collapse;width:auto!important;margin:0}.ncl-g200 td{padding:0 5px 0 0;border:0;font-size:8.2pt;background:none;white-space:nowrap}
.ncl-g200 .scan{width:24mm;flex:none;display:flex;flex-direction:column;align-items:center;gap:.8mm;text-align:center;font-size:7.5pt;font-weight:700;line-height:1.15;border-left:0.3mm solid #000;padding-left:2mm}
.ncl-g200 .scan svg{width:6mm;height:6mm}
.ncl-l100{width:100mm;height:50mm;display:flex;gap:3mm;padding:3mm;border-radius:2mm}
.ncl-l100 .kiri{width:40mm;flex:none;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1.2mm}
.ncl-l100 .qr{width:38mm;height:38mm}
.ncl-l100 .cap{display:flex;align-items:center;gap:1mm;font-size:6.3pt;font-weight:700;white-space:nowrap}
.ncl-l100 .cap svg{width:3.2mm;height:3.2mm}
.ncl-l100 .id{flex:1;min-width:0;display:flex;flex-direction:column;justify-content:center;border-left:0.3mm solid #000;padding-left:2.5mm;font-size:7.6pt;line-height:1.3}
.ncl-l100 .nm{font-size:10.5pt;white-space:normal;line-height:1.15;margin:.8mm 0 1.2mm;max-height:2.4em}
.ncl-l100 dl{display:grid;grid-template-columns:auto 1fr;gap:0 1.5mm;margin:0}
.ncl-l100 dt{font-weight:400}.ncl-l100 dt::after{content:":"}
.ncl-l100 dd{margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ncl-l100 .alg{margin-top:1.2mm;font-size:7pt}
.ncl-s33{width:33mm;height:15mm;display:flex;align-items:center;gap:1mm;padding:1mm}
.ncl-s33 .qr{width:13mm;height:13mm;flex:none}
.ncl-s33 .id{flex:1;min-width:0;font-size:5pt;line-height:1.2}
.ncl-s33 .nm{font-size:5.6pt;white-space:normal;max-height:2.4em;overflow:hidden;line-height:1.15;margin-bottom:.3mm}
.ncl-s33 .cap{font-weight:700;font-size:4.4pt;margin-top:.4mm}
@media screen{.ncl-s33{zoom:2.2}}
@media print{
  @page{size:' . $m[1] . 'mm ' . $m[2] . 'mm;margin:0}
  html,body{margin:0!important;padding:0!important;background:#fff!important}
  .ncl-wrap{padding:0!important;display:block}
  .ncl{outline:0;border-radius:0}
}
</style>';
}
$qrJs = '<script src="' . aset('assets/js/qrcode.js') . '"></script><script>
document.querySelectorAll("[data-qr]").forEach(function (el) {
  var qr = qrcode(0, el.getAttribute("data-ec") || "M"); qr.addData(el.getAttribute("data-qr")); qr.make();
  el.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
});
function catatCetak() {
  var fd = new FormData(); fd.append("csrf", ' . json_encode(csrf_token()) . '); fd.append("rawat", ' . json_encode($nr) . ');
  fetch(' . json_encode(base_url('gelang.php')) . ', { method: "POST", body: fd, credentials: "same-origin" }).catch(function () {});
}
</script>';


if ($polos) {
    $title = 'Gelang ' . $p['nama']; $noInstall = true; $bodyClass = 'polos';
    require __DIR__ . '/inc/head.php';
    echo '<style>body.polos{background:#fff;margin:0}</style>' . css_label($tipe);
    echo html_gelang($p, $url, $tipe);
    echo $qrJs . '<script>window.cetak = function () { catatCetak(); window.focus(); window.print(); };</script></body></html>';
    exit;
}


$pageTitle = 'Cetak Gelang'; $active = $p['ranap'] ? 'ranap' : 'admisi'; $back = base_url($p['ranap'] ? 'ranap.php' : 'admisi.php');
layout_top();
?>
<div class="page-head no-print">
  <h1 class="desk">Gelang Pasien</h1>
  <div class="flex">
    <?php foreach (MODEL_LABEL as $k => $m): ?><a class="btn sm <?= $tipe === $k ? '' : 'light' ?>" href="?rawat=<?= $enc ?>&amp;tipe=<?= $k ?>"><?= e($m[0]) ?></a><?php endforeach; ?>
    <button class="btn" onclick="catatCetak(); window.print()"><?= icon('print', 18) ?> Cetak</button>
  </div>
</div>
<?php if (!$p['ranap']): ?><div class="alert info no-print">Pasien belum masuk kamar rawat inap. Gelang boleh dicetak sekarang — QR Code <b>otomatis aktif</b> begitu pasien masuk kamar di Khanza.</div><?php endif; ?>
<?php if ($lokalan): ?>
<div class="alert warn no-print">QR Code masih memakai alamat <b><?= e(parse_url($url, PHP_URL_HOST)) ?></b> yang tidak bisa dibuka dari HP pasien.
  <?= bisa('master') ? 'Isi <a href="' . base_url('pengaturan.php') . '">Alamat publik aplikasi</a> di Pengaturan' : 'Minta Admin mengisi Alamat publik aplikasi di Pengaturan' ?>, lalu cetak ulang.</div>
<?php endif; ?>
<?= css_label($tipe) ?>
<?= html_gelang($p, $url, $tipe) ?>
<p class="center muted small no-print"><?= e(MODEL_LABEL[$tipe][0]) ?> · hitam-putih untuk kertas stiker<?= $tipe === 's33' ? ' · pratinjau diperbesar, tercetak sesuai ukuran asli' : '' ?>.<br>
  Ukuran kertas cetak otomatis <?= MODEL_LABEL[$tipe][1] ?> × <?= MODEL_LABEL[$tipe][2] ?> mm dengan margin 0. Pada jendela cetak pilih printer label/stiker, skala 100% (bukan "Sesuaikan halaman").</p>
<div class="card no-print mt" style="max-width:720px;margin-left:auto;margin-right:auto">
  <h2>Uji QR Code</h2>
  <p class="small muted" style="margin-top:0">Scan QR di atas dengan kamera HP, atau buka alamat berikut. Halaman ini yang akan dilihat pasien/keluarga.</p>
  <input readonly value="<?= e($url) ?>" onclick="this.select()">
  <div class="flex mt"><a class="btn ghost sm" href="<?= e($url) ?>" target="_blank">Buka halaman pasien</a>
    <?php if ($p['ranap']): ?><a class="btn light sm" href="<?= base_url('pasien.php?rawat=' . $enc) ?>">Detail pasien</a><?php endif; ?></div>
</div>
<?= $qrJs ?>
<?php layout_bottom(); ?>