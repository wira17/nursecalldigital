<?php
require __DIR__ . '/inc/bootstrap.php';
require_akses('master');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach (['nama_rs', 'alamat', 'telp'] as $k) save_setting($k, mb_substr(trim($_POST[$k] ?? ''), 0, 200));
    $url = trim($_POST['url_publik'] ?? '');
    if ($url !== '' && !preg_match('#^https?://[^\s]+$#i', $url)) { flash('Alamat publik harus diawali http:// atau https://', 'err'); redirect(base_url('pengaturan.php')); }
    save_setting('url_publik', $url === '' ? '' : rtrim($url, '/') . '/');
    save_setting('jeda_lapor', (string)max(0, min(600, (int)($_POST['jeda_lapor'] ?? 60))));
    save_setting('batas_respon', (string)max(1, min(60, (int)($_POST['batas_respon'] ?? 5))));
    save_setting('suara_aktif', !empty($_POST['suara_aktif']) ? '1' : '0');
    save_setting('suara_maks', (string)max(10, min(180, (int)($_POST['suara_maks'] ?? 60))));
    flash('Pengaturan disimpan.');
    redirect(base_url('pengaturan.php'));
}
$rs = khanza_rs() ?: [];
$st = q("SELECT (SELECT COUNT(*) FROM user) u, (SELECT COUNT(*) FROM nc_user_wira) nc, (SELECT COUNT(*) FROM kamar_inap WHERE stts_pulang = '-') ranap")->fetch();
$pageTitle = 'Pengaturan'; $active = 'pengaturan'; $back = base_url('menu.php');
layout_top();
?>
<div class="page-head desk"><h1>Pengaturan</h1></div>
<div class="card" style="max-width:760px">
  <div class="card-head"><h2>Database SIMRS Khanza</h2><span class="badge st-selesai">Terhubung</span></div>
  <dl class="info">
    <dt>Database</dt><dd><?= e(DB_NAME) ?> @ <?= e(DB_HOST) ?></dd>
    <dt>Instansi</dt><dd><?= e($rs['nama_instansi'] ?? '-') ?></dd>
    <dt>Data</dt><dd><?= angka($st['u']) ?> user Khanza · <?= angka($st['nc']) ?> pengguna Nurse Call · <?= angka($st['ranap']) ?> pasien rawat inap aktif</dd>
  </dl>
  <p class="hint" style="margin-bottom:0">Nurse Call memakai 100% database Khanza. Tabel tambahan Nurse Call berawalan <code>nc_</code> dan berakhiran <code>_wira</code>.</p>
</div>
<form method="post" class="card" style="max-width:760px"><?= csrf_field() ?>
  <h2>Identitas rumah sakit</h2>
  <p class="small muted" style="margin-top:0">Kosongkan untuk memakai data dari tabel <code>setting</code> Khanza.</p>
  <label>Nama rumah sakit / klinik</label><input name="nama_rs" value="<?= e(q("SELECT v FROM nc_setting_wira WHERE k = 'nama_rs'")->fetchColumn() ?: '') ?>" placeholder="<?= e($rs['nama_instansi'] ?? '') ?>">
  <div class="row"><div><label>Alamat</label><input name="alamat" value="<?= e(q("SELECT v FROM nc_setting_wira WHERE k = 'alamat'")->fetchColumn() ?: '') ?>" placeholder="<?= e(trim(($rs['alamat_instansi'] ?? '') . ', ' . ($rs['kabupaten'] ?? ''), ', ')) ?>"></div>
    <div><label>Telepon</label><input name="telp" value="<?= e(q("SELECT v FROM nc_setting_wira WHERE k = 'telp'")->fetchColumn() ?: '') ?>" placeholder="<?= e($rs['kontak'] ?? '') ?>"></div></div>

  <h2 class="mt" style="margin-top:26px">QR Code gelang</h2>
  <label>Alamat publik aplikasi</label>
  <input name="url_publik" value="<?= e(setting('url_publik')) ?>" placeholder="<?= e(base_url()) ?>">
  <div class="hint">Alamat yang ditanam di QR Code dan dibuka HP pasien. Kosongkan untuk memakai alamat saat ini (<b><?= e(base_url()) ?></b>).
    Jika Anda membuka aplikasi lewat <i>localhost</i>, isi dengan IP server di WiFi RS (mis. <code>http://192.168.1.10/nursecall/</code>) atau domain (mis. <code>https://nursecall.rs-anda.id/</code>). Setelah diubah, cetak ulang gelang.</div>

  <h2 class="mt" style="margin-top:26px">Login pegawai Khanza</h2>
  <p class="small muted" style="margin:0">Semua user Khanza bisa login dengan NIK &amp; password Khanza.</p>
  <div class="hint">Pegawai yang pertama kali login otomatis menjadi <b>User</b> (Dashboard, Admisi, Rawat Inap). Admin bisa mengubahnya menjadi <b>Admin</b> di menu Pengguna.</div>
  <h2 class="mt" style="margin-top:26px">Aturan panggilan</h2>
  <div class="row">
    <div><label>Jeda minimal antar laporan (detik)</label><input type="number" name="jeda_lapor" min="0" max="600" value="<?= (int)setting('jeda_lapor', '60') ?>">
      <div class="hint">Mencegah pasien menekan kirim berkali-kali.</div></div>
    <div><label>Standar waktu respon (menit)</label><input type="number" name="batas_respon" min="1" max="60" value="<?= (int)setting('batas_respon', '5') ?>">
      <div class="hint">Panggilan lebih lama dari ini diberi tanda merah & dihitung di laporan.</div></div>
  </div>
  <h2 class="mt" style="margin-top:26px">Pesan suara</h2>
  <label style="display:flex;gap:10px;align-items:center;font-weight:500"><input type="checkbox" name="suara_aktif" value="1" <?= setting('suara_aktif', '1') === '1' ? 'checked' : '' ?>><span>Pasien boleh mengirim <b>pesan suara</b> (tombol mikrofon di halaman scan QR)</span></label>
  <div class="row"><div><label>Durasi maksimal rekaman (detik)</label><input type="number" name="suara_maks" min="10" max="180" value="<?= (int)setting('suara_maks', '60') ?>"></div></div>
  <div class="hint">Perekam langsung di HP pasien butuh alamat <b>HTTPS</b>. Tanpa HTTPS, pasien tetap bisa merekam memakai aplikasi perekam bawaan HP. Rekaman disimpan di folder <code>uploads/suara/</code> dan hanya bisa diputar oleh petugas yang login.</div>
  <button class="btn mt">Simpan</button>
</form>
<?php layout_bottom(); ?>