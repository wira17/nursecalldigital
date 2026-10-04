<?php

require __DIR__ . '/inc/bootstrap.php';
require_akses('master');
$list = q("SELECT b.kd_bangsal, b.nm_bangsal,
             (SELECT COUNT(*) FROM kamar k WHERE k.kd_bangsal = b.kd_bangsal AND k.statusdata = '1') kamar,
             (SELECT COUNT(*) FROM kamar_inap ki JOIN kamar k2 ON k2.kd_kamar = ki.kd_kamar WHERE ki.stts_pulang = '-' AND k2.kd_bangsal = b.kd_bangsal) pasien,
             (SELECT COUNT(*) FROM nc_user_wira u WHERE u.kd_bangsal = b.kd_bangsal AND u.aktif = 1) petugas,
             (SELECT COUNT(*) FROM nc_panggilan_wira p WHERE p.kd_bangsal = b.kd_bangsal AND DATE(p.waktu) = CURDATE()) hari_ini
           FROM bangsal b WHERE b.status = '1' AND EXISTS (SELECT 1 FROM kamar k WHERE k.kd_bangsal = b.kd_bangsal AND k.statusdata = '1')
           ORDER BY b.nm_bangsal")->fetchAll();
$pageTitle = 'Ruang (Khanza)'; $active = 'ruang'; $back = base_url('menu.php');
layout_top();
?>
<div class="page-head desk"><div><h1>Ruang Rawat Inap</h1><div class="muted small">Diambil dari tabel <code>bangsal</code> &amp; <code>kamar</code> SIMRS Khanza. Tambah / ubah ruang dan kamar dilakukan di Khanza.</div></div></div>
<?php if (!$list): ?><div class="card"><?= empty_box('Tidak ada bangsal aktif yang memiliki kamar di Khanza.', 'door') ?></div><?php else: ?>
<div class="card flush"><div class="table-wrap"><table>
  <tr><th>Kode</th><th>Nama ruang</th><th class="right">Kamar / bed</th><th class="right">Pasien dirawat</th><th class="right">Petugas</th><th class="right">Panggilan hari ini</th><th></th></tr>
  <?php foreach ($list as $r): ?>
  <tr><td><code><?= e($r['kd_bangsal']) ?></code></td><td><b><?= e(nama_rapi($r['nm_bangsal'], true)) ?></b></td>
    <td class="right"><?= (int)$r['kamar'] ?></td><td class="right"><a href="<?= base_url('pasien.php?ruang=' . rawurlencode($r['kd_bangsal'])) ?>"><?= (int)$r['pasien'] ?></a></td>
    <td class="right"><?= (int)$r['petugas'] ?></td><td class="right"><?= (int)$r['hari_ini'] ?></td>
    <td class="aksi"><a class="btn sm light" href="<?= base_url('index.php?ruang=' . rawurlencode($r['kd_bangsal'])) ?>">Pantau</a>
      <a class="btn sm light" href="<?= e(url_display($r['kd_bangsal'])) ?>" target="_blank">Display TV</a></td></tr>
  <?php endforeach; ?>
</table></div></div>
<?php endif; ?>
<p class="small muted">Hanya bangsal berstatus aktif yang memiliki kamar aktif (<code>kamar.statusdata = '1'</code>) yang ditampilkan.</p>
<?php layout_bottom(); ?>