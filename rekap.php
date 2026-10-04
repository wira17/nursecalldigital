<?php

require __DIR__ . '/inc/bootstrap.php';
require_akses('laporan');
$f = [
    'dari'   => valid_date($_GET['dari'] ?? '') ? $_GET['dari'] : date('Y-m-01'),
    'sampai' => valid_date($_GET['sampai'] ?? '') ? $_GET['sampai'] : date('Y-m-d'),
    'ruang'  => substr(preg_replace('/[^\w.-]/', '', (string)($_GET['ruang'] ?? '')), 0, 5),
];
if ($f['sampai'] < $f['dari']) [$f['dari'], $f['sampai']] = [$f['sampai'], $f['dari']];
$qs = qs_builder($f);
$W = 'WHERE DATE(p.waktu) BETWEEN ? AND ?' . ($f['ruang'] !== '' ? ' AND p.kd_bangsal = ?' : '');
$P = $f['ruang'] !== '' ? [$f['dari'], $f['sampai'], $f['ruang']] : [$f['dari'], $f['sampai']];
$batas = (int)setting('batas_respon', '5') * 60;
$RESP = 'TIMESTAMPDIFF(SECOND, p.waktu, p.waktu_respon)';

$s = q("SELECT COUNT(*) n, SUM(p.status='selesai') selesai, AVG($RESP) respon, MAX($RESP) maks,
        AVG(TIMESTAMPDIFF(SECOND, p.waktu, p.waktu_selesai)) tuntas,
        SUM(p.waktu_respon IS NOT NULL AND $RESP <= $batas) tepat, SUM(p.waktu_respon IS NOT NULL) direspon,
        COUNT(DISTINCT p.no_rawat) pasien
        FROM nc_panggilan_wira p $W", $P)->fetch();
$pctTepat = $s['direspon'] ? round($s['tepat'] / $s['direspon'] * 100) : 0;

$perKel = q("SELECT p.keluhan nama, MAX(p.prioritas) prioritas, COUNT(*) n, AVG($RESP) respon FROM nc_panggilan_wira p $W GROUP BY p.keluhan ORDER BY n DESC", $P)->fetchAll();
$perRuang = q("SELECT b.nm_bangsal nama, COUNT(*) n, AVG($RESP) respon, SUM(p.waktu_respon IS NOT NULL AND $RESP <= $batas) tepat, SUM(p.waktu_respon IS NOT NULL) direspon
               FROM nc_panggilan_wira p LEFT JOIN bangsal b ON b.kd_bangsal = p.kd_bangsal $W GROUP BY p.kd_bangsal, b.nm_bangsal ORDER BY n DESC", $P)->fetchAll();
$perPerawat = q("SELECT COALESCE(u.nama, p.perawat) nama, COUNT(*) n, AVG($RESP) respon, SUM(p.status='selesai') selesai
                 FROM nc_panggilan_wira p LEFT JOIN nc_user_wira u ON u.id_user = p.perawat $W AND p.perawat IS NOT NULL GROUP BY p.perawat, u.nama ORDER BY n DESC", $P)->fetchAll();
$jam = array_fill(0, 24, 0);
foreach (q("SELECT HOUR(p.waktu) h, COUNT(*) n FROM nc_panggilan_wira p $W GROUP BY HOUR(p.waktu)", $P)->fetchAll() as $r) $jam[(int)$r['h']] = (int)$r['n'];
$maxJam = max(1, max($jam)); $maxKel = max(1, max(array_column($perKel, 'n') ?: [0]));
$ruangList = daftar_bangsal();
$dur = function ($v) { return $v !== null ? durasi((int)round($v)) : '-'; };

$pageTitle = 'Laporan'; $active = 'rekap'; $back = base_url('menu.php');
layout_top();
?>
<div class="page-head">
  <h1 class="desk">Laporan Nurse Call</h1>
  <div class="flex"><a class="btn light" href="<?= base_url('export.php?' . $qs()) ?>"><?= icon('dl', 18) ?> Export Excel</a>
    <button class="btn light desk" onclick="window.print()"><?= icon('print', 18) ?> Cetak</button></div>
</div>
<form class="card filters no-print" method="get">
  <div><label>Dari</label><input type="date" name="dari" value="<?= e($f['dari']) ?>"></div>
  <div><label>Sampai</label><input type="date" name="sampai" value="<?= e($f['sampai']) ?>"></div>
  <div><label>Ruang</label><select name="ruang"><option value="">Semua ruang</option>
    <?php foreach ($ruangList as $r): ?><option value="<?= e($r['kd_bangsal']) ?>" <?= $f['ruang'] === $r['kd_bangsal'] ? 'selected' : '' ?>><?= e($r['nama']) ?></option><?php endforeach; ?></select></div>
  <div class="f-act"><button class="btn">Tampilkan</button></div>
  <div class="f-quick"><span class="small muted">Cepat:</span>
    <?php foreach (['Hari ini' => [date('Y-m-d'), date('Y-m-d')], '7 hari' => [date('Y-m-d', strtotime('-6 days')), date('Y-m-d')], 'Bulan ini' => [date('Y-m-01'), date('Y-m-d')],
                    'Bulan lalu' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))]] as $l => [$a, $b]): ?>
      <a class="btn sm light" href="?<?= $qs(['dari' => $a, 'sampai' => $b]) ?>"><?= $l ?></a><?php endforeach; ?>
  </div>
</form>
<p class="muted small">Periode <b style="color:var(--text)"><?= tgl_indo($f['dari'], false, true) ?> – <?= tgl_indo($f['sampai'], false, true) ?></b> · standar waktu respon ≤ <?= $batas / 60 ?> menit</p>
<div class="stats">
  <div class="stat"><div class="n" style="color:var(--pri)"><?= angka($s['n']) ?></div><div class="l">Panggilan · <?= angka($s['pasien']) ?> pasien</div></div>
  <div class="stat"><div class="n"><?= $dur($s['respon']) ?></div><div class="l">Rata-rata waktu respon</div></div>
  <div class="stat"><div class="n" style="color:<?= $pctTepat >= 90 ? 'var(--ok)' : 'var(--warn)' ?>"><?= $pctTepat ?>%</div><div class="l">Direspon ≤ <?= $batas / 60 ?> menit</div></div>
  <div class="stat"><div class="n"><?= $dur($s['tuntas']) ?></div><div class="l">Rata-rata sampai selesai</div></div>
</div>
<div class="grid2">
  <div class="card"><h2>Keluhan terbanyak</h2>
    <?php if (!$perKel): ?><p class="muted" style="margin:0">Tidak ada data.</p><?php endif; ?>
    <?php foreach ($perKel as $k): ?>
      <div style="padding:6px 0"><div class="flex" style="justify-content:space-between"><span><?= e($k['nama']) ?> <?= badge_prio($k['prioritas']) ?></span><span><b><?= (int)$k['n'] ?></b> <span class="small muted">· respon <?= $dur($k['respon']) ?></span></span></div>
        <div class="hbar"><i style="width:<?= round($k['n'] / $maxKel * 100) ?>%"></i></div></div>
    <?php endforeach; ?>
  </div>
  <div class="card"><h2>Panggilan per jam</h2>
    <div class="bars" style="gap:2px">
      <?php foreach ($jam as $h => $n): ?>
        <div class="b" title="Jam <?= sprintf('%02d', $h) ?>: <?= $n ?> panggilan"><em><?= $n ?: '' ?></em><i style="height:<?= max(1, round($n / $maxJam * 80)) ?>%"></i><span><?= $h % 3 === 0 ? sprintf('%02d', $h) : '' ?></span></div>
      <?php endforeach; ?>
    </div>
    <p class="small muted" style="margin-bottom:0">Membantu mengatur jumlah perawat jaga di jam sibuk.</p>
  </div>
</div>
<div class="grid2">
  <div class="card flush"><div style="padding:16px 20px 6px"><h2>Per ruang</h2></div><div class="table-wrap"><table>
    <tr><th>Ruang</th><th class="right">Panggilan</th><th class="right">Rata respon</th><th class="right">≤ <?= $batas / 60 ?> mnt</th></tr>
    <?php foreach ($perRuang as $r): ?><tr><td><b><?= e(nama_rapi($r['nama'], true)) ?></b></td><td class="right"><?= (int)$r['n'] ?></td><td class="right"><?= $dur($r['respon']) ?></td>
      <td class="right"><?= $r['direspon'] ? round($r['tepat'] / $r['direspon'] * 100) . '%' : '-' ?></td></tr><?php endforeach; ?>
    <?php if (!$perRuang): ?><tr><td colspan="4" class="center muted">Tidak ada data.</td></tr><?php endif; ?>
  </table></div></div>
  <div class="card flush"><div style="padding:16px 20px 6px"><h2>Per perawat</h2></div><div class="table-wrap"><table>
    <tr><th>Perawat</th><th class="right">Ditangani</th><th class="right">Selesai</th><th class="right">Rata respon</th></tr>
    <?php foreach ($perPerawat as $r): ?><tr><td><b><?= e($r['nama']) ?></b></td><td class="right"><?= (int)$r['n'] ?></td><td class="right"><?= (int)$r['selesai'] ?></td><td class="right"><?= $dur($r['respon']) ?></td></tr><?php endforeach; ?>
    <?php if (!$perPerawat): ?><tr><td colspan="4" class="center muted">Tidak ada data.</td></tr><?php endif; ?>
  </table></div></div>
</div>
<?php layout_bottom(); ?>