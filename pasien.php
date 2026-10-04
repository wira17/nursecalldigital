<?php

require __DIR__ . '/inc/bootstrap.php';
require_akses('lihat_pasien');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $nr = (string)($_POST['no_rawat'] ?? '');
    if (($_POST['aksi'] ?? '') === 'token') {
        require_akses('pasien');
        if (!ranap($nr)) { flash('Pasien sudah tidak dirawat.', 'err'); redirect(base_url('ranap.php')); }
        q('INSERT INTO nc_gelang_wira (no_rawat, token, dibuat, dibuat_oleh) VALUES (?, ?, NOW(), ?)
           ON DUPLICATE KEY UPDATE token = VALUES(token), dibuat = VALUES(dibuat), dibuat_oleh = VALUES(dibuat_oleh), dicetak = 0, nonaktif = NULL',
          [$nr, bin2hex(random_bytes(12)), user()['id_user']]);
        flash('QR Code baru dibuat. QR lama tidak berlaku lagi — cetak dan pasang gelang baru.');
        redirect(base_url('gelang.php?rawat=' . rawurlencode($nr)));
    }
    redirect(base_url('ranap.php'));
}

$ruangList = daftar_bangsal();


if (isset($_GET['rawat'])) {
    $nr = (string)$_GET['rawat'];
    $p = ranap($nr);
    $pulang = null;
    if (!$p) { 
        $p = q("SELECT ki.no_rawat, rp.no_rkm_medis no_rm, ps.nm_pasien nama, ps.jk, ps.tgl_lahir, ki.kd_kamar kamar, b.nm_bangsal ruang,
                  (SELECT CONCAT(MIN(k2.tgl_masuk), ' ', MIN(k2.jam_masuk)) FROM kamar_inap k2 WHERE k2.no_rawat = ki.no_rawat) tgl_masuk,
                  ki.diagnosa_awal diagnosa, d.nm_dokter dokter, CONCAT(ki.tgl_keluar, ' ', ki.jam_keluar) tgl_pulang, ki.stts_pulang, g.token
                FROM kamar_inap ki JOIN reg_periksa rp ON rp.no_rawat = ki.no_rawat JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
                JOIN kamar k ON k.kd_kamar = ki.kd_kamar JOIN bangsal b ON b.kd_bangsal = k.kd_bangsal LEFT JOIN dokter d ON d.kd_dokter = rp.kd_dokter
                LEFT JOIN nc_gelang_wira g ON g.no_rawat = ki.no_rawat
                WHERE ki.no_rawat = ? ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC LIMIT 1", [$nr])->fetch();
        if (!$p) { flash('Data rawat inap tidak ditemukan di Khanza.', 'err'); redirect(base_url('ranap.php')); }
        $p = rapikan_pasien($p); $pulang = $p;
    }
    $alergi = alergi_map([$nr])[$nr] ?? '';
    $calls = q(SQL_PG . ' WHERE p.no_rawat = ? ORDER BY p.id DESC LIMIT 30', [$nr])->fetchAll();
    $pageTitle = 'Detail Pasien'; $active = 'ranap'; $back = base_url($pulang ? 'pasien.php?status=pulang' : 'ranap.php');
    layout_top(); ?>
<div class="page-head">
  <h1 class="desk"><?= e($p['nama']) ?></h1>
  <div class="flex">
    <a class="btn light desk" href="<?= e($back) ?>">← Daftar pasien</a>
    <?php if (!$pulang && bisa('gelang')): ?><a class="btn" href="<?= base_url('gelang.php?rawat=' . rawurlencode($nr)) ?>"><?= icon('print', 18) ?> Cetak gelang</a><?php endif; ?>
  </div>
</div>
<div class="grid3">
  <div>
    <div class="card">
      <div class="card-head"><h2><?= e($p['nama']) ?></h2><?= $pulang ? '<span class="badge st-pulang">Pulang · ' . e($p['stts_pulang']) . '</span>' : '<span class="badge st-dirawat">Dirawat</span>' ?></div>
      <dl class="info">
        <dt>No. Rawat</dt><dd><?= e($p['no_rawat']) ?></dd>
        <dt>No. RM</dt><dd><?= e($p['no_rm']) ?></dd>
        <dt>Jenis kelamin / umur</dt><dd><?= $p['jk'] === 'P' ? 'Perempuan' : 'Laki-laki' ?> · <?= $p['tgl_lahir'] ? tgl_indo($p['tgl_lahir']) . ' (' . umur($p['tgl_lahir']) . ')' : '-' ?></dd>
        <dt>Ruang / kamar</dt><dd><?= e(lokasi($p)) ?><?= !empty($p['kelas']) ? ' · ' . e($p['kelas']) : '' ?></dd>
        <dt>DPJP</dt><dd><?= e($p['dokter'] ?: '-') ?></dd>
        <dt>Diagnosa awal</dt><dd><?= e($p['diagnosa'] ?: '-') ?></dd>
        <dt>Alergi</dt><dd style="<?= $alergi ? 'color:var(--err)' : '' ?>"><?= e($alergi ?: '-') ?></dd>
        <dt>Masuk</dt><dd><?= tgl_indo($p['tgl_masuk'], true, true) ?></dd>
        <?php if ($pulang): ?><dt>Keluar</dt><dd><?= tgl_indo($p['tgl_pulang'], true, true) ?></dd><?php endif; ?>
        <dt>Gelang QR</dt><dd><?= $p['token'] ? 'Sudah dibuat' : 'Belum dibuat' ?></dd>
      </dl>
      <p class="hint" style="margin-bottom:0">Data pasien, kamar &amp; status pulang mengikuti SIMRS Khanza.</p>
    </div>
    <div class="card-head"><h2>Riwayat panggilan (<?= count($calls) ?>)</h2></div>
    <?php if (!$calls): ?><div class="card"><?= empty_box('Pasien ini belum pernah memanggil perawat.') ?></div><?php endif; ?>
    <div class="list">
    <?php foreach ($calls as $c): ?>
      <a class="item" href="<?= base_url('panggilan.php?id=' . $c['id']) ?>">
        <div class="h"><b><?= $c['audio'] ? '🎤 ' : '' ?><?= e($c['keluhan']) ?></b><?= badge_pg($c['status']) ?></div>
        <div class="m"><?= tgl_indo($c['waktu'], true) ?> · <?= e(lokasi(rapikan_pasien($c))) ?><?= $c['pesan'] ? ' · “' . e($c['pesan']) . '”' : '' ?></div>
        <div class="f"><span class="muted"><?= e($c['perawat'] ?? '-') ?></span><span class="small">Respon <?= $c['waktu_respon'] ? durasi((int)$c['detik_respon']) : '-' ?></span></div>
      </a>
    <?php endforeach; ?>
    </div>
  </div>
  <div>
    <?php if (!$pulang && bisa('pasien') && $p['token']): ?>
    <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="aksi" value="token"><input type="hidden" name="no_rawat" value="<?= e($nr) ?>">
      <h2>Gelang hilang / rusak?</h2><p class="small muted" style="margin-top:0">Buat QR Code baru. QR lama langsung tidak berlaku.</p>
      <button class="btn light block" data-confirm="Buat QR Code baru? Gelang lama tidak bisa dipakai lagi."><?= icon('qr', 18) ?> Buat QR baru</button>
    </form>
    <?php endif; ?>
    <div class="card small muted">QR Code gelang otomatis <b>tidak aktif</b> saat pasien dipulangkan di Khanza. Jika pasien pindah kamar di Khanza, panggilan otomatis tampil dengan kamar yang baru — gelang tidak perlu dicetak ulang.</div>
  </div>
</div>
<?php layout_bottom(); exit;
}


if (($_GET['status'] ?? '') === 'pulang') {
    $cari = trim($_GET['cari'] ?? '');
    $par = []; $w = '';
    if ($cari !== '') { $w = ' AND (ps.nm_pasien LIKE ? OR rp.no_rkm_medis LIKE ? OR g.no_rawat LIKE ?)'; $l = "%$cari%"; $par = [$l, $l, $l]; }
    $rows = q("SELECT g.no_rawat, rp.no_rkm_medis no_rm, ps.nm_pasien nama, ps.jk, ps.tgl_lahir, ki.kd_kamar kamar, b.nm_bangsal ruang,
                 CONCAT(ki.tgl_keluar, ' ', ki.jam_keluar) tgl_pulang, ki.stts_pulang,
                 (SELECT COUNT(*) FROM nc_panggilan_wira c WHERE c.no_rawat = g.no_rawat) n_call
               FROM nc_gelang_wira g JOIN reg_periksa rp ON rp.no_rawat = g.no_rawat JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
               JOIN kamar_inap ki ON ki.no_rawat = g.no_rawat AND ki.stts_pulang NOT IN ('-', 'Pindah Kamar')
               JOIN kamar k ON k.kd_kamar = ki.kd_kamar JOIN bangsal b ON b.kd_bangsal = k.kd_bangsal
               WHERE NOT EXISTS (SELECT 1 FROM kamar_inap a WHERE a.no_rawat = g.no_rawat AND a.stts_pulang = '-')
                 AND ki.tgl_keluar >= CURDATE() - INTERVAL 60 DAY $w
               ORDER BY ki.tgl_keluar DESC, ki.jam_keluar DESC LIMIT 200", $par)->fetchAll();
    $pageTitle = 'Pasien Pulang'; $active = 'ranap';
    layout_top(); ?>
<div class="page-head"><h1 class="desk">Pasien Pulang <span class="muted small">(60 hari terakhir, yang memakai gelang Nurse Call)</span></h1>
  <form method="get" class="flex"><input type="hidden" name="status" value="pulang"><input name="cari" value="<?= e($cari) ?>" placeholder="Nama / No. RM / No. Rawat" style="width:240px"><button class="btn light">Cari</button></form></div>
<?php if (!$rows): ?><div class="card"><?= empty_box('Belum ada pasien pulang.', 'door') ?></div><?php endif; ?>
<div class="list">
<?php foreach ($rows as $r): $r = rapikan_pasien($r); ?>
  <a class="item" href="?rawat=<?= rawurlencode($r['no_rawat']) ?>">
    <div class="h"><b><?= e($r['nama']) ?></b><span class="badge st-pulang"><?= e($r['stts_pulang']) ?></span></div>
    <div class="m">RM <?= e($r['no_rm']) ?> · <?= e($r['no_rawat']) ?> · <?= e(lokasi($r)) ?></div>
    <div class="f"><span class="muted">Pulang <?= tgl_indo($r['tgl_pulang'], true) ?></span><span><?= (int)$r['n_call'] ?> panggilan</span></div>
  </a>
<?php endforeach; ?>
</div>
<?php layout_bottom(); exit;
}

/* Daftar pasien rawat inap sekarang ada di ranap.php */
redirect(base_url('ranap.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')));