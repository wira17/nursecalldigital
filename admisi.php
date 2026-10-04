<?php

require __DIR__ . '/inc/bootstrap.php';
require_akses('lihat_pasien');

$f = [
    'tgl'   => valid_date($_GET['tgl'] ?? '') ? $_GET['tgl'] : date('Y-m-d'),
    'jenis' => in_array($_GET['jenis'] ?? '', ['ralan', 'ranap', 'igd'], true) ? $_GET['jenis'] : '',
    'cari'  => trim($_GET['cari'] ?? ''),
];
$sql = "SELECT rp.no_rawat, rp.jam_reg, rp.stts, rp.stts_daftar, rp.status_lanjut, rp.status_bayar,
          p.no_rkm_medis, p.nm_pasien, p.jk, p.tgl_lahir, pol.nm_poli, dok.nm_dokter, pj.png_jawab,
          g.token, g.dicetak,
          (SELECT CONCAT(b.nm_bangsal, ' · ', ki.kd_kamar) FROM kamar_inap ki JOIN kamar k ON k.kd_kamar = ki.kd_kamar
             JOIN bangsal b ON b.kd_bangsal = k.kd_bangsal WHERE ki.no_rawat = rp.no_rawat AND ki.stts_pulang = '-' LIMIT 1) kamar_aktif
        FROM reg_periksa rp
        JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
        LEFT JOIN poliklinik pol ON pol.kd_poli = rp.kd_poli
        LEFT JOIN dokter dok ON dok.kd_dokter = rp.kd_dokter
        LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
        LEFT JOIN nc_gelang_wira g ON g.no_rawat = rp.no_rawat
        WHERE rp.tgl_registrasi = ?";
$par = [$f['tgl']];
if ($f['jenis'] === 'igd')   { $sql .= " AND pol.nm_poli LIKE ?"; $par[] = '%IGD%'; }
if ($f['jenis'] === 'ralan') { $sql .= " AND rp.status_lanjut = 'Ralan' AND (pol.nm_poli IS NULL OR pol.nm_poli NOT LIKE ?)"; $par[] = '%IGD%'; }
if ($f['jenis'] === 'ranap') { $sql .= " AND rp.status_lanjut = 'Ranap'"; }
if ($f['cari'] !== '') { $sql .= " AND (p.nm_pasien LIKE ? OR p.no_rkm_medis LIKE ? OR rp.no_rawat LIKE ?)"; $l = '%' . $f['cari'] . '%'; array_push($par, $l, $l, $l); }
$sql .= " ORDER BY rp.jam_reg DESC";
$rows = q($sql, $par)->fetchAll();
$qs = qs_builder($f);
$jml = ['semua' => count($rows), 'gelang' => count(array_filter($rows, function ($r) { return $r['token']; }))];

$pageTitle = 'Admisi'; $active = 'admisi';
$extraJs = ['assets/js/gelang-modal.js'];
layout_top();
?>
<div class="page-head">
  <div class="desk"><h1>Admisi</h1><div class="muted small">Pendaftaran pasien <?= e(HARI[(int)date('w', strtotime($f['tgl']))]) ?>, <?= tgl_indo($f['tgl'], false, true) ?> · data SIMRS Khanza</div></div>
  <div class="flex">
    <?php foreach (['' => 'Semua', 'igd' => 'IGD', 'ralan' => 'Rawat Jalan', 'ranap' => 'Rawat Inap'] as $k => $l): ?>
      <a class="btn sm <?= $f['jenis'] === $k ? '' : 'light' ?>" href="?<?= $qs(['jenis' => $k]) ?>"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
</div>
<form class="card filters" method="get">
  <input type="hidden" name="jenis" value="<?= e($f['jenis']) ?>">
  <div class="f-sm"><label>Tanggal</label><input type="date" name="tgl" value="<?= e($f['tgl']) ?>" onchange="this.form.submit()"></div>
  <div><label>Cari</label><input name="cari" value="<?= e($f['cari']) ?>" placeholder="Nama / No. RM / No. Rawat"></div>
  <div class="f-act"><button class="btn"><?= icon('search', 18) ?> Cari</button><a class="btn light" href="?">Hari ini</a></div>
</form>
<p class="muted small"><?= $jml['semua'] ?> pendaftaran · <?= $jml['gelang'] ?> sudah dibuatkan gelang</p>

<?php if (!$rows): ?><div class="card"><?= empty_box('Belum ada pendaftaran pada tanggal ini.', 'list') ?></div><?php else: ?>
<div class="card flush desk"><div class="table-wrap"><table>
  <tr><th>Jam</th><th>No. RM / No. Rawat</th><th>Nama pasien</th><th>Unit / Dokter</th><th>Jenis</th><th>Cara bayar</th><th>Gelang</th><th></th></tr>
  <?php foreach ($rows as $r): $igd = stripos($r['nm_poli'] ?? '', 'IGD') !== false; $nama = nama_rapi($r['nm_pasien']); $batal = $r['stts'] === 'Batal'; ?>
  <tr data-row style="<?= $batal ? 'opacity:.5' : '' ?>">
    <td class="nowrap"><?= e(substr($r['jam_reg'], 0, 5)) ?></td>
    <td><code><?= e($r['no_rkm_medis']) ?></code><div class="small muted"><?= e($r['no_rawat']) ?></div></td>
    <td><b><?= e($nama) ?></b><div class="small muted"><?= $r['jk'] === 'P' ? 'Perempuan' : 'Laki-laki' ?>, <?= umur($r['tgl_lahir']) ?>
      · <?= e(($r['stts_daftar'] ?? '') ?: 'Lama') ?><?= $batal ? ' · <b style="color:var(--err)">BATAL</b>' : '' ?></div></td>
    <td><?= e(nama_rapi($r['nm_poli'] ?? '-', true)) ?><div class="small muted"><?= e($r['nm_dokter'] ?? '') ?></div></td>
    <td><?php if ($r['kamar_aktif']): ?><span class="badge st-dirawat">Dirawat</span><div class="small muted"><?= e(nama_rapi($r['kamar_aktif'], true)) ?></div>
      <?php elseif ($igd): ?><span class="badge st-baru">IGD</span>
      <?php elseif ($r['status_lanjut'] === 'Ranap'): ?><span class="badge pr-sedang">Rawat Inap</span>
      <?php else: ?><span class="badge gray">Rawat Jalan</span><?php endif; ?></td>
    <td class="small"><?= e($r['png_jawab'] ?? '-') ?><div class="muted"><?= e($r['status_bayar'] ?? '') ?></div></td>
    <td><span data-gl-status class="badge <?= $r['token'] ? 'st-selesai' : 'gray' ?>"><?= $r['token'] ? 'Gelang ✓' : 'Belum' ?></span></td>
    <td class="aksi"><?php if (!$batal && bisa('gelang')): ?>
      <a href="<?= base_url('gelang.php?rawat=' . rawurlencode($r['no_rawat'])) ?>" class="btn sm <?= $r['token'] ? 'ghost' : '' ?>" data-gelang="<?= e($r['no_rawat']) ?>" data-nama="<?= e($nama) ?>"><?= $r['token'] ? icon('print', 16) . ' Cetak ulang' : icon('qr', 16) . ' Buat Gelang' ?></a>
    <?php endif; ?></td>
  </tr>
  <?php endforeach; ?>
</table></div></div>

<div class="list mob">
  <?php foreach ($rows as $r): $igd = stripos($r['nm_poli'] ?? '', 'IGD') !== false; $nama = nama_rapi($r['nm_pasien']); $batal = $r['stts'] === 'Batal'; ?>
  <div class="item" data-row style="<?= $batal ? 'opacity:.55' : '' ?>">
    <div class="h"><b><?= e($nama) ?></b><span class="small muted nowrap"><?= e(substr($r['jam_reg'], 0, 5)) ?></span></div>
    <div class="m">RM <?= e($r['no_rkm_medis']) ?> · <?= $r['jk'] === 'P' ? 'P' : 'L' ?>, <?= umur($r['tgl_lahir']) ?> · <?= e(nama_rapi($r['nm_poli'] ?? '-', true)) ?><?= $r['nm_dokter'] ? ' · ' . e($r['nm_dokter']) : '' ?></div>
    <div class="f">
      <span><?php if ($r['kamar_aktif']): ?><span class="badge st-dirawat"><?= e(nama_rapi($r['kamar_aktif'], true)) ?></span>
        <?php elseif ($igd): ?><span class="badge st-baru">IGD</span><?php elseif ($r['status_lanjut'] === 'Ranap'): ?><span class="badge pr-sedang">Rawat Inap</span><?php else: ?><span class="badge gray">Rawat Jalan</span><?php endif; ?>
        <span data-gl-status class="badge <?= $r['token'] ? 'st-selesai' : 'gray' ?>"><?= $r['token'] ? 'Gelang ✓' : 'Belum gelang' ?></span>
        <?= $batal ? '<span class="badge st-baru">Batal</span>' : '' ?></span>
      <?php if (!$batal && bisa('gelang')): ?><a href="<?= base_url('gelang.php?rawat=' . rawurlencode($r['no_rawat'])) ?>" class="btn sm <?= $r['token'] ? 'ghost' : '' ?>" data-gelang="<?= e($r['no_rawat']) ?>" data-nama="<?= e($nama) ?>"><?= $r['token'] ? icon('print', 16) . ' Cetak ulang' : icon('qr', 16) . ' Buat Gelang' ?></a><?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<p class="small muted mt">Gelang bisa dibuat sejak admisi / IGD. QR Code otomatis <b>aktif</b> saat pasien masuk kamar rawat inap di Khanza dan otomatis <b>tidak berlaku</b> saat pasien pulang.</p>
<?php layout_bottom(); ?>