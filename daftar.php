<?php

require __DIR__ . '/inc/bootstrap.php';
require_akses('station');
require __DIR__ . '/inc/filter_pg.php';
$qs = qs_builder($f);
$sum = q("SELECT COUNT(*) n, SUM(p.status='baru') baru, SUM(p.status='diproses') proses, SUM(p.status='selesai') selesai,
          AVG(CASE WHEN p.waktu_respon IS NOT NULL THEN TIMESTAMPDIFF(SECOND, p.waktu, p.waktu_respon) END) respon
          FROM nc_panggilan_wira p LEFT JOIN pasien ps ON ps.no_rkm_medis = p.no_rkm_medis $where", $params)->fetch();
$perPage = 30; $pages = max(1, (int)ceil($sum['n'] / $perPage)); $page = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
$rows = array_map('rapikan_pasien', q(SQL_PG . " $where ORDER BY p.id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage), $params)->fetchAll());
$ruangList = daftar_bangsal();
$kelList = q('SELECT id, nama FROM nc_keluhan_wira ORDER BY urut, nama')->fetchAll();

$pageTitle = 'Daftar Panggilan'; $active = 'daftar';
layout_top();
?>
<div class="page-head">
  <h1 class="desk">Daftar Nurse Call</h1>
  <a class="btn light" href="<?= base_url('export.php?' . $qs()) ?>"><?= icon('dl', 18) ?> Export Excel</a>
</div>
<form class="card filters" method="get">
  <div><label>Dari</label><input type="date" name="dari" value="<?= e($f['dari']) ?>"></div>
  <div><label>Sampai</label><input type="date" name="sampai" value="<?= e($f['sampai']) ?>"></div>
  <div><label>Ruang</label><select name="ruang"><option value="">Semua</option>
    <?php foreach ($ruangList as $r): ?><option value="<?= e($r['kd_bangsal']) ?>" <?= $f['ruang'] === $r['kd_bangsal'] ? 'selected' : '' ?>><?= e($r['nama']) ?></option><?php endforeach; ?></select></div>
  <div><label>Status</label><select name="status"><option value="">Semua</option>
    <?php foreach (STATUS_PG as $k => $v): ?><option value="<?= $k ?>" <?= $f['status'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
  <div><label>Keluhan</label><select name="keluhan"><option value="">Semua</option>
    <?php foreach ($kelList as $k): ?><option value="<?= $k['id'] ?>" <?= $f['keluhan'] == $k['id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option><?php endforeach; ?></select></div>
  <div><label>Cari</label><input name="cari" value="<?= e($f['cari']) ?>" placeholder="Pasien, RM, pesan"></div>
  <div class="f-act"><button class="btn">Filter</button><a class="btn light" href="?">Reset</a></div>
</form>
<div class="stats">
  <div class="stat"><div class="n"><?= angka($sum['n']) ?></div><div class="l">Total panggilan</div></div>
  <div class="stat"><div class="n" style="color:var(--err)"><?= (int)$sum['baru'] + (int)$sum['proses'] ?></div><div class="l">Belum selesai</div></div>
  <div class="stat"><div class="n" style="color:var(--ok)"><?= angka($sum['selesai']) ?></div><div class="l">Selesai</div></div>
  <div class="stat"><div class="n"><?= $sum['respon'] !== null ? durasi((int)round($sum['respon'])) : '-' ?></div><div class="l">Rata-rata respon</div></div>
</div>
<?php if (!$rows): ?><div class="card"><?= empty_box('Tidak ada panggilan pada periode ini.') ?></div><?php else: ?>
<div class="card flush desk"><div class="table-wrap"><table>
  <tr><th>No</th><th>Waktu</th><th>Nama pasien</th><th>Ruang</th><th>Keluhan</th><th>Status</th><th>Respon</th><th>Perawat</th><th>Aksi</th></tr>
  <?php foreach ($rows as $i => $r): ?>
  <tr>
    <td class="muted"><?= ($page - 1) * $perPage + $i + 1 ?></td>
    <td class="nowrap small"><?= date('d-m-Y H:i', strtotime($r['waktu'])) ?></td>
    <td><b><?= e($r['pasien']) ?></b><div class="small muted"><?= e($r['no_rm']) ?></div></td>
    <td class="nowrap"><?= e(lokasi($r)) ?></td>
    <td><?= $r['audio'] ? '🎤 ' : '' ?><?= e($r['keluhan']) ?><?= $r['audio'] ? '<div class="voice" style="padding:4px 6px;min-width:230px"><audio controls preload="none" src="' . base_url('api/audio.php?id=' . $r['id']) . '"></audio></div>' : '' ?><?= $r['pesan'] ? '<div class="small muted">“' . e(mb_strimwidth($r['pesan'], 0, 60, '…')) . '”</div>' : '' ?></td>
    <td><?= badge_pg($r['status']) ?></td>
    <td class="small nowrap"><?= $r['waktu_respon'] ? durasi((int)$r['detik_respon']) : '<span data-since="' . e(iso($r['waktu'])) . '" style="color:var(--err)"></span>' ?></td>
    <td class="small"><?= e($r['perawat'] ?? '-') ?></td>
    <td class="nowrap"><?php if ($r['status'] === 'baru'): ?><button class="btn sm ok" data-act="tangani" data-id="<?= $r['id'] ?>">Tangani</button> <?php endif; ?><a class="btn sm light" href="<?= base_url('panggilan.php?id=' . $r['id']) ?>">Lihat</a></td>
  </tr>
  <?php endforeach; ?>
</table></div></div>
<div class="list mob">
  <?php foreach ($rows as $r): ?>
  <a class="item" href="<?= base_url('panggilan.php?id=' . $r['id']) ?>" style="border-left:4px solid <?= ['baru' => 'var(--err)', 'diproses' => 'var(--info)', 'selesai' => 'var(--ok)'][$r['status']] ?>">
    <div class="h"><b><?= e($r['pasien']) ?></b><?= badge_pg($r['status']) ?></div>
    <div class="m"><?= e(lokasi($r)) ?> · <?= $r['audio'] ? '🎤 ' : '' ?><?= e($r['keluhan']) ?><?= $r['durasi_audio'] ? ' (' . gmdate('i:s', (int)$r['durasi_audio']) . ')' : '' ?></div>
    <div class="f"><span class="muted"><?= date('d/m H:i', strtotime($r['waktu'])) ?><?= $r['perawat'] ? ' · ' . e($r['perawat']) : '' ?></span><span class="small">Respon <?= $r['waktu_respon'] ? durasi((int)$r['detik_respon']) : '-' ?></span></div>
  </a>
  <?php endforeach; ?>
</div>
<?php pager($page, $pages, $qs); endif; ?>
<?php layout_bottom(); ?>