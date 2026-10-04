<?php
require __DIR__ . '/inc/bootstrap.php';
require_login();
$u = user();
$pageTitle = bisa('station') ? 'Nurse Station' : 'Dashboard'; $active = 'index';

if (bisa('station')):
    $ruang = ruang_pantau();
    $ruangList = daftar_bangsal();
    $nPasien = $ruang !== '' ? (int)(array_column($ruangList, 'n', 'kd_bangsal')[$ruang] ?? 0) : array_sum(array_column($ruangList, 'n'));
    $namaRuang = $ruang !== '' ? nama_bangsal($ruang) : 'Semua ruang';
    layout_top();
?>
<div class="page-head">
  <div class="desk"><h1>Nurse Station · <?= e($namaRuang) ?></h1>
    <div class="muted"><?= e(HARI[(int)date('w')]) ?>, <?= tgl_indo(date('Y-m-d'), false, true) ?> · <?= $nPasien ?> pasien dirawat · <span class="live" data-live><i></i> Menghubungkan…</span></div></div>
  <div class="mob" style="width:100%"><b><?= e($namaRuang) ?></b> · <?= $nPasien ?> pasien<br><span class="live" data-live><i></i> Menghubungkan…</span></div>
  <form method="get" class="flex">
    <a class="btn light sm" href="<?= base_url('daftar.php') ?>"><?= icon('list', 16) ?> Daftar Panggilan</a>
    <?php if (bisa('codeblue')): ?><a class="btn sm" style="background:#1e5bd8" href="<?= base_url('codeblue.php') ?>"><?= icon('heart', 16) ?> Code Blue</a><?php endif; ?>
    <a class="btn light sm" href="<?= base_url('tv.php') ?>"><?= icon('tv', 16) ?> Display TV</a>
    <?php if (bisa('laporan')): ?><a class="btn light sm" href="<?= base_url('rekap.php') ?>"><?= icon('chart', 16) ?> Laporan</a><?php endif; ?>
    <select name="ruang" onchange="this.form.submit()" style="width:auto;min-width:170px" aria-label="Ruang dipantau">
      <option value="">Semua ruang</option>
      <?php foreach ($ruangList as $r): ?><option value="<?= e($r['kd_bangsal']) ?>" <?= $ruang === $r['kd_bangsal'] ? 'selected' : '' ?>><?= e($r['nama']) ?> (<?= (int)$r['n'] ?> pasien)</option><?php endforeach; ?>
    </select>
    <noscript><button class="btn light">Pilih</button></noscript>
  </form>
</div>

<div class="sound-bar" id="soundBar">
  <?= icon('vol', 22) ?><span id="soundText">Klik tombol ini agar alarm bisa berbunyi.</span>
  <button type="button" class="btn sm" id="soundBtn">Aktifkan suara</button>
  <button type="button" class="btn sm light" id="muteBtn" title="Matikan suara"><?= icon('mute', 16) ?></button>
</div>
<div class="flex mb small">
  <button type="button" class="btn sm light" id="voiceBtn">🗣️ Suara pengumuman</button>
  <button type="button" class="btn sm ghost" id="notifBtn" hidden>🔔 Izinkan notifikasi di perangkat ini</button>
  <button type="button" class="btn sm ghost" data-install hidden>📲 Pasang aplikasi</button>
</div>

<div class="stats">
  <div class="stat"><div class="n" style="color:var(--err)" id="st_baru">-</div><div class="l">Menunggu ditangani</div></div>
  <div class="stat"><div class="n" style="color:var(--info)" id="st_diproses">-</div><div class="l">Sedang ditangani</div></div>
  <div class="stat"><div class="n" style="color:var(--ok)" id="st_selesai">-</div><div class="l">Selesai hari ini</div></div>
  <div class="stat"><div class="n" id="st_respon">-</div><div class="l">Rata-rata waktu respon</div></div>
</div>

<div class="card-head"><h2>Panggilan aktif</h2><a class="small" href="<?= base_url('daftar.php') ?>">Semua panggilan →</a></div>
<div class="calls" id="calls"><div class="card" style="grid-column:1/-1"><?= empty_box('Memuat data…') ?></div></div>

<div class="card flush desk">
  <div style="padding:16px 20px 6px" class="card-head"><h2>Daftar Nurse Call hari ini</h2><span class="muted small">Semua laporan tercatat, mudah dipantau &amp; ditindaklanjuti</span></div>
  <div class="table-wrap"><table>
    <thead><tr><th>No</th><th>Waktu</th><th>Nama Pasien</th><th>Ruang</th><th>Keluhan</th><th>Status</th><th>Perawat</th><th>Aksi</th></tr></thead>
    <tbody id="todayBody"></tbody>
  </table></div>
</div>
<div class="mob"><div class="card-head"><h2>Selesai hari ini</h2></div><div class="list" id="todayMob"></div></div>
<?php
else:
    // ===== Dashboard pendaftaran =====
    // Semua data pasien dari Khanza (kamar_inap aktif)
    $n = q('SELECT COUNT(*) dirawat, SUM(DATE(x.tgl_masuk) = CURDATE()) masuk, SUM(x.token IS NULL) belum FROM (' . SQL_RANAP . ') x')->fetch();
    $pulang = (int)q("SELECT COUNT(DISTINCT no_rawat) FROM kamar_inap WHERE tgl_keluar = CURDATE() AND stts_pulang NOT IN ('-', 'Pindah Kamar')")->fetchColumn();
    $perRuang = array_filter(daftar_bangsal(), function ($r) { return $r['n'] > 0; });
    $baru = array_map('rapikan_pasien', q(SQL_RANAP . ' ORDER BY (g.token IS NULL) DESC, ki.tgl_masuk DESC, ki.jam_masuk DESC LIMIT 8')->fetchAll());
    $maxR = max(1, max(array_column($perRuang, 'n') ?: [0]));
    $extraJs = ['assets/js/gelang-modal.js'];
    layout_top();
?>
<div class="page-head desk">
  <div><h1>Halo, <?= e($u['nama']) ?> 👋</h1><div class="muted"><?= e(HARI[(int)date('w')]) ?>, <?= tgl_indo(date('Y-m-d'), false, true) ?> · <?= e((ROLE_LABEL[role()] ?? 'User')) ?></div></div>
  <div class="flex"><button class="btn ghost" data-install hidden>📲 Pasang Aplikasi</button><a class="btn" href="<?= base_url('ranap.php?f=belum') ?>"><?= icon('qr', 18) ?> Cetak Gelang</a></div>
</div>
<div class="stats">
  <a class="stat" href="<?= base_url('ranap.php') ?>"><div class="n" style="color:var(--pri)"><?= (int)$n['dirawat'] ?></div><div class="l">Pasien dirawat</div></a>
  <div class="stat"><div class="n" style="color:var(--ok)"><?= (int)$n['masuk'] ?></div><div class="l">Masuk hari ini</div></div>
  <a class="stat" href="<?= base_url('pasien.php?status=pulang') ?>"><div class="n"><?= $pulang ?></div><div class="l">Pulang hari ini</div></a>
  <a class="stat" href="<?= base_url('ranap.php?f=belum') ?>"><div class="n" style="color:var(--warn)"><?= (int)$n['belum'] ?></div><div class="l">Belum punya gelang</div></a>
</div>
<div class="grid3">
  <div>
    <a class="btn block mob mb" href="<?= base_url('ranap.php?f=belum') ?>"><?= icon('qr', 20) ?> Cetak Gelang Pasien</a>
    <div class="card-head"><h2>Pasien rawat inap terbaru <span class="muted small">(dari Khanza)</span></h2><a class="small" href="<?= base_url('ranap.php') ?>">Semua →</a></div>
    <?php if (!$baru): ?><div class="card"><?= empty_box('Belum ada pasien dirawat.', 'bed') ?></div><?php endif; ?>
    <div class="list">
      <?php foreach ($baru as $p): ?>
      <div class="item" data-row>
        <div class="h"><b><?= e($p['nama']) ?></b><span class="badge role"><?= e(lokasi($p)) ?></span></div>
        <div class="m">RM <?= e($p['no_rm']) ?> · <?= $p['jk'] === 'P' ? 'P' : 'L' ?>, <?= umur($p['tgl_lahir']) ?> · masuk <?= tgl_indo($p['tgl_masuk'], true) ?></div>
        <div class="f"><span><?= $p['token'] ? '<span data-gl-status class="badge st-selesai">Gelang ✓</span>' : '<span data-gl-status class="badge pr-sedang">Belum ada gelang</span>' ?></span>
          <a href="<?= base_url('gelang.php?rawat=' . rawurlencode($p['no_rawat'])) ?>" class="btn sm <?= $p['token'] ? 'ghost' : '' ?>" data-gelang="<?= e($p['no_rawat']) ?>" data-nama="<?= e($p['nama']) ?>"><?= icon('print', 16) ?> <?= $p['token'] ? 'Cetak ulang' : 'Buat gelang' ?></a></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="dash-side">
    <a class="card" href="<?= base_url('admisi.php') ?>" style="display:block;color:var(--text)"><b><?= icon('list', 18) ?> Admisi hari ini</b><div class="small muted">Buat gelang sejak pendaftaran / IGD</div></a>
    <div class="card"><h2>Pasien per ruang</h2>
      <?php foreach ($perRuang as $r): ?>
        <div style="padding:6px 0"><div class="flex" style="justify-content:space-between"><span><?= e($r['nama']) ?></span><b><?= (int)$r['n'] ?></b></div>
          <div class="hbar"><i style="width:<?= round($r['n'] / $maxR * 100) ?>%"></i></div></div>
      <?php endforeach; ?>
    </div>
    <div class="card"><h2>Alur Nurse Call</h2>
      <ol class="small" style="padding-left:18px;margin:0;line-height:1.8">
        <li>Pasien didaftarkan rawat inap di SIMRS Khanza (otomatis muncul di sini).</li><li>Cetak gelang ber-QR Code.</li><li>Pasang gelang di tangan pasien.</li>
        <li>Pasien/keluarga scan QR dengan kamera HP.</li><li>Pilih keluhan lalu kirim.</li><li>Alarm berbunyi di komputer perawat.</li><li>Perawat menuju pasien.</li>
      </ol>
    </div>
  </div>
</div>
<?php endif; ?>
<?php layout_bottom(); ?>