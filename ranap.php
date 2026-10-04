<?php

require __DIR__ . '/inc/bootstrap.php';
require_akses('lihat_pasien');

$f = [
    'ruang' => substr(preg_replace('/[^\w.-]/', '', (string)($_GET['ruang'] ?? '')), 0, 5),
    'cari'  => trim($_GET['cari'] ?? ''),
    'f'     => ($_GET['f'] ?? '') === 'belum' ? 'belum' : '',
];
$where = "kamar_inap.stts_pulang = '-'"; $par = [];
if ($f['ruang'] !== '') { $where .= ' AND kamar.kd_bangsal = ?'; $par[] = $f['ruang']; }
if ($f['f'] === 'belum') $where .= ' AND g.token IS NULL';
if ($f['cari'] !== '') {
    $where .= ' AND (kamar_inap.no_rawat LIKE ? OR reg_periksa.no_rkm_medis LIKE ? OR pasien.nm_pasien LIKE ? OR bangsal.nm_bangsal LIKE ?
                OR kamar_inap.kd_kamar LIKE ? OR dokter.nm_dokter LIKE ? OR kamar_inap.diagnosa_awal LIKE ?)';
    $l = '%' . $f['cari'] . '%'; for ($i = 0; $i < 7; $i++) $par[] = $l;
}
$rows = q("SELECT kamar_inap.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien,
             CONCAT(reg_periksa.umurdaftar, ' ', reg_periksa.sttsumur) AS umur, pasien.jk,
             kamar_inap.kd_kamar, bangsal.nm_bangsal, kamar.kd_bangsal,
             kamar_inap.diagnosa_awal, kamar_inap.tgl_masuk, kamar_inap.jam_masuk,
             DATEDIFF(CURDATE(), kamar_inap.tgl_masuk) + 1 AS lama, dokter.nm_dokter, penjab.png_jawab, g.token,
             (SELECT COUNT(*) FROM nc_panggilan_wira c WHERE c.no_rawat = kamar_inap.no_rawat AND c.status <> 'selesai') n_aktif
           FROM kamar_inap
           INNER JOIN reg_periksa ON kamar_inap.no_rawat = reg_periksa.no_rawat
           INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
           INNER JOIN kamar ON kamar_inap.kd_kamar = kamar.kd_kamar
           INNER JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal
           LEFT JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
           LEFT JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
           LEFT JOIN nc_gelang_wira g ON g.no_rawat = kamar_inap.no_rawat
           WHERE $where
           ORDER BY bangsal.nm_bangsal, kamar_inap.kd_kamar", $par)->fetchAll();
$ruangList = daftar_bangsal();
$belum = count(array_filter($rows, function ($r) { return !$r['token']; }));
$qs = qs_builder($f);

$pageTitle = 'Rawat Inap'; $active = 'ranap';
$extraJs = ['assets/js/gelang-modal.js'];
layout_top();
?>
<div class="page-head">
  <div class="desk"><h1>Rawat Inap</h1><div class="muted small">Pasien yang masih dirawat (belum pulang) · <?= count($rows) ?> pasien · data SIMRS Khanza</div></div>
  <div class="flex">
    <a class="btn sm <?= $f['f'] ? 'light' : '' ?>" href="?<?= $qs(['f' => '']) ?>">Semua</a>
    <a class="btn sm <?= $f['f'] ? '' : 'light' ?>" href="?<?= $qs(['f' => 'belum']) ?>">Belum punya gelang</a>
  </div>
</div>
<form class="card filters" method="get">
  <input type="hidden" name="f" value="<?= e($f['f']) ?>">
  <div><label>Cari</label><input name="cari" value="<?= e($f['cari']) ?>" placeholder="Nama, No. RM, kamar, bangsal, dokter, diagnosa…"></div>
  <div class="f-md"><label>Ruang</label><select name="ruang" onchange="this.form.submit()"><option value="">Semua ruang</option>
    <?php foreach ($ruangList as $b): ?><option value="<?= e($b['kd_bangsal']) ?>" <?= $f['ruang'] === $b['kd_bangsal'] ? 'selected' : '' ?>><?= e($b['nama']) ?> (<?= (int)$b['n'] ?>)</option><?php endforeach; ?></select></div>
  <div class="f-act"><button class="btn"><?= icon('search', 18) ?> Cari</button><a class="btn light" href="?">Reset</a></div>
</form>
<?php if (!$f['f'] && $belum): ?><div class="alert warn"><?= $belum ?> pasien belum punya gelang. <a href="?<?= $qs(['f' => 'belum']) ?>">Tampilkan</a></div><?php endif; ?>

<?php if (!$rows): ?><div class="card"><?= empty_box($f['cari'] ? 'Tidak ditemukan.' : 'Tidak ada pasien rawat inap saat ini.', 'bed') ?></div><?php else: ?>
<div class="card flush desk"><div class="table-wrap"><table>
  <tr><th>Kamar</th><th>No. RM / No. Rawat</th><th>Nama pasien</th><th>Dokter</th><th>Masuk</th><th>Cara bayar</th><th>Gelang</th><th></th></tr>
  <?php foreach ($rows as $r): $nama = nama_rapi($r['nm_pasien']); ?>
  <tr data-row>
    <td><b><?= e(nama_rapi($r['nm_bangsal'], true)) ?></b><div class="small muted"><?= e($r['kd_kamar']) ?></div></td>
    <td><code><?= e($r['no_rkm_medis']) ?></code><div class="small muted"><?= e($r['no_rawat']) ?></div></td>
    <td><b><?= e($nama) ?></b> <?= $r['n_aktif'] ? '<span class="badge st-baru">🔔 ' . (int)$r['n_aktif'] . '</span>' : '' ?>
      <div class="small muted"><?= $r['jk'] === 'P' ? 'Perempuan' : 'Laki-laki' ?>, <?= e(trim($r['umur']) ?: '-') ?><?= $r['diagnosa_awal'] ? ' · ' . e($r['diagnosa_awal']) : '' ?></div></td>
    <td class="small"><?= e($r['nm_dokter'] ?? '-') ?></td>
    <td class="small nowrap"><?= tgl_indo($r['tgl_masuk']) ?><div class="muted"><?= (int)$r['lama'] ?> hari</div></td>
    <td class="small"><?= e($r['png_jawab'] ?? '-') ?></td>
    <td><span data-gl-status class="badge <?= $r['token'] ? 'st-selesai' : 'pr-sedang' ?>"><?= $r['token'] ? 'Gelang ✓' : 'Belum' ?></span></td>
    <td class="aksi"><a class="btn sm light" href="<?= base_url('pasien.php?rawat=' . rawurlencode($r['no_rawat'])) ?>">Detail</a>
      <?php if (bisa('gelang')): ?><a href="<?= base_url('gelang.php?rawat=' . rawurlencode($r['no_rawat'])) ?>" class="btn sm <?= $r['token'] ? 'ghost' : '' ?>" data-gelang="<?= e($r['no_rawat']) ?>" data-nama="<?= e($nama) ?>"><?= $r['token'] ? icon('print', 16) . ' Cetak ulang' : icon('qr', 16) . ' Buat Gelang' ?></a><?php endif; ?></td>
  </tr>
  <?php endforeach; ?>
</table></div></div>

<div class="list mob">
  <?php foreach ($rows as $r): $nama = nama_rapi($r['nm_pasien']); ?>
  <div class="item" data-row>
    <div class="h"><a href="<?= base_url('pasien.php?rawat=' . rawurlencode($r['no_rawat'])) ?>" style="color:inherit"><b><?= e($nama) ?></b></a>
      <span class="badge role"><?= e(nama_rapi($r['nm_bangsal'], true)) ?> · <?= e($r['kd_kamar']) ?></span></div>
    <div class="m">RM <?= e($r['no_rkm_medis']) ?> · <?= $r['jk'] === 'P' ? 'P' : 'L' ?>, <?= e(trim($r['umur'])) ?> · <?= e($r['nm_dokter'] ?? '') ?></div>
    <div class="m">Masuk <?= tgl_indo($r['tgl_masuk']) ?> · <?= (int)$r['lama'] ?> hari · <?= e($r['png_jawab'] ?? '') ?><?= $r['diagnosa_awal'] ? ' · ' . e($r['diagnosa_awal']) : '' ?></div>
    <div class="f"><span><span data-gl-status class="badge <?= $r['token'] ? 'st-selesai' : 'pr-sedang' ?>"><?= $r['token'] ? 'Gelang ✓' : 'Belum gelang' ?></span>
      <?= $r['n_aktif'] ? '<span class="badge st-baru">🔔 ' . (int)$r['n_aktif'] . ' panggilan</span>' : '' ?></span>
      <?php if (bisa('gelang')): ?><a href="<?= base_url('gelang.php?rawat=' . rawurlencode($r['no_rawat'])) ?>" class="btn sm <?= $r['token'] ? 'ghost' : '' ?>" data-gelang="<?= e($r['no_rawat']) ?>" data-nama="<?= e($nama) ?>"><?= $r['token'] ? icon('print', 16) . ' Cetak ulang' : icon('qr', 16) . ' Buat Gelang' ?></a><?php endif; ?></div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php layout_bottom(); ?>