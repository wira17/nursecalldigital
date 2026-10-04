<?php

require __DIR__ . '/inc/bootstrap.php';
require_akses('station');
$c = q(str_replace('FROM nc_panggilan_wira p', ', k.ikon FROM nc_panggilan_wira p LEFT JOIN nc_keluhan_wira k ON k.id = p.keluhan_id', SQL_PG) . ' WHERE p.id = ?', [(int)($_GET['id'] ?? 0)])->fetch();
if (!$c) { flash('Panggilan tidak ditemukan.', 'err'); redirect(base_url('daftar.php')); }
$c = rapikan_pasien($c);
$rn = ranap($c['no_rawat']); // data rawat inap saat ini dari Khanza (null = sudah pulang)
$c['dokter'] = $rn['dokter'] ?? ''; $c['diagnosa'] = $rn['diagnosa'] ?? '';
$c['alergi'] = alergi_map([$c['no_rawat']])[$c['no_rawat']] ?? '';
$pageTitle = 'Detail Panggilan'; $active = 'daftar'; $back = base_url('daftar.php');
layout_top();
?>
<div class="page-head">
  <h1 class="desk">Panggilan #<?= $c['id'] ?> · <?= e(lokasi($c)) ?></h1>
  <div class="flex"><a class="btn light desk" href="<?= base_url() ?>">← Nurse station</a><a class="btn light" href="<?= base_url('pasien.php?rawat=' . rawurlencode($c['no_rawat'])) ?>">Data pasien</a></div>
</div>
<div class="grid3">
  <div>
    <div class="call <?= e($c['status']) ?>" style="margin-bottom:16px;animation:none">
      <div class="top"><div><div class="room"><?= e(lokasi($c)) ?></div><div class="who"><?= e($c['pasien']) ?></div>
        <div class="rm">RM <?= e($c['no_rm']) ?> · <?= $c['jk'] === 'P' ? 'Perempuan' : 'Laki-laki' ?>, <?= umur($c['tgl_lahir']) ?></div></div>
        <div class="right"><?= badge_pg($c['status']) ?><div style="margin-top:6px"><?= badge_prio($c['prioritas']) ?></div></div></div>
      <div class="kel"><span class="ic"><?= $c['audio'] ? '🎤' : e($c['ikon'] ?? '🔔') ?></span><?= e($c['keluhan']) ?></div>
      <?php if ($c['pesan']): ?><div class="msg">💬 <?= e($c['pesan']) ?></div><?php endif; ?>
      <?php if ($c['audio']): ?>
      <div class="voice"><audio controls preload="metadata" src="<?= base_url('api/audio.php?id=' . $c['id']) ?>"></audio>
        <?php if ($c['durasi_audio']): ?><span class="dur">🎤 <?= gmdate('i:s', (int)$c['durasi_audio']) ?></span><?php endif; ?>
        <a class="btn sm light" href="<?= base_url('api/audio.php?unduh=1&id=' . $c['id']) ?>" title="Unduh rekaman"><?= icon('dl', 16) ?></a></div>
      <?php endif; ?>
    </div>
    <div class="card">
      <h2>Perjalanan panggilan</h2>
      <ul class="track">
        <li class="done"><span class="dot">1</span><div><b>Laporan masuk</b><small><?= tgl_indo($c['waktu'], true) ?> · dari <?= $c['pelapor'] === 'keluarga' ? 'keluarga pasien' : 'pasien' ?></small></div></li>
        <li class="<?= $c['waktu_respon'] ? 'done' : 'now' ?>"><span class="dot">2</span><div><b>Ditangani<?= $c['perawat'] ? ' oleh ' . e($c['perawat']) : '' ?></b>
          <small><?= $c['waktu_respon'] ? date('H:i', strtotime($c['waktu_respon'])) . ' · waktu respon ' . durasi((int)$c['detik_respon']) : 'Menunggu sejak <span data-since="' . e(iso($c['waktu'])) . '"></span>' ?></small></div></li>
        <li class="<?= $c['status'] === 'selesai' ? 'done' : ($c['status'] === 'diproses' ? 'now' : '') ?>"><span class="dot">3</span><div><b>Selesai<?= $c['penyelesai'] ? ' · ' . e($c['penyelesai']) : '' ?></b>
          <small><?= $c['waktu_selesai'] ? date('H:i', strtotime($c['waktu_selesai'])) . ' · total ' . durasi((int)$c['detik_selesai']) : '-' ?></small></div></li>
      </ul>
      <?php if ($c['tindakan']): ?><div class="alert ok mt" style="margin-bottom:0"><b>Tindakan:</b> <?= e($c['tindakan']) ?></div><?php endif; ?>
    </div>
  </div>
  <div class="dash-side">
    <?php if ($c['status'] !== 'selesai' && bisa('tangani')): ?>
    <div class="card">
      <h2>Tindak lanjut</h2>
      <?php if ($c['status'] === 'baru'): ?>
      <form method="post" action="<?= base_url('api/aksi.php') ?>" data-once><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><input type="hidden" name="aksi" value="tangani">
        <button class="btn ok block"><?= icon('run', 20) ?> Tandai Ditangani</button></form>
      <?php endif; ?>
      <form method="post" action="<?= base_url('api/aksi.php') ?>" class="mt" data-once><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><input type="hidden" name="aksi" value="selesai">
        <label style="margin-top:0">Tindakan yang dilakukan</label>
        <textarea name="tindakan" maxlength="255" style="min-height:70px" placeholder="mis. Injeksi analgetik sesuai advis DPJP, nyeri berkurang"></textarea>
        <button class="btn block mt"><?= icon('check', 20) ?> Selesai</button></form>
    </div>
    <?php endif; ?>
    <div class="card"><h2>Info pasien</h2>
      <dl class="info" style="grid-template-columns:90px 1fr">
        <dt>DPJP</dt><dd><?= e($c['dokter'] ?: '-') ?></dd>
        <dt>Diagnosa</dt><dd><?= e($c['diagnosa'] ?: '-') ?></dd>
        <dt>Alergi</dt><dd style="<?= $c['alergi'] ? 'color:var(--err)' : '' ?>"><?= e($c['alergi'] ?: '-') ?></dd>
        <dt>Status</dt><dd><?= $rn ? 'Dirawat · ' . e(lokasi($rn)) : 'Sudah pulang' ?></dd>
      </dl>
    </div>
  </div>
</div>
<?php layout_bottom(); ?>