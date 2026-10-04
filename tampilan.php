<?php
require __DIR__ . '/inc/bootstrap.php';
require_akses('master');
const MAX_BG = 4 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'warna') {
        $c = strtolower(trim($_POST['tema_warna'] ?? ''));
        if (!preg_match('/^#[0-9a-f]{6}$/', $c)) flash('Kode warna tidak valid.', 'err');
        else { save_setting('tema_warna', $c); flash('Warna tema disimpan.' . (warna_tema() !== $c ? ' Warna terlalu terang, jadi otomatis sedikit digelapkan agar tulisan tetap terbaca.' : '')); }
    } elseif ($aksi === 'login') {
        save_setting('login_judul', mb_substr(trim($_POST['login_judul'] ?? ''), 0, 80));
        save_setting('login_teks', mb_substr(trim($_POST['login_teks'] ?? ''), 0, 200));
        save_setting('login_overlay', (string)max(0, min(90, (int)($_POST['login_overlay'] ?? 55))));
        $f = $_FILES['login_bg'] ?? null;
        if ($f && $f['error'] !== UPLOAD_ERR_NO_FILE) {
            $info = $f['error'] === UPLOAD_ERR_OK ? @getimagesize($f['tmp_name']) : false;
            $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2] ?? 0] ?? null;
            if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > MAX_BG) { flash('Gambar terlalu besar (maks. 4 MB).', 'err'); redirect(base_url('tampilan.php')); }
            if (!$ext) { flash('File harus gambar JPG, PNG, atau WEBP.', 'err'); redirect(base_url('tampilan.php')); }
            $nama = 'login-bg-' . bin2hex(random_bytes(6)) . '.' . $ext;
            if (!is_dir(__DIR__ . '/uploads')) @mkdir(__DIR__ . '/uploads', 0755);
            if (!move_uploaded_file($f['tmp_name'], __DIR__ . '/uploads/' . $nama)) { flash('Gagal menyimpan gambar. Pastikan folder uploads/ bisa ditulis.', 'err'); redirect(base_url('tampilan.php')); }
            $lama = setting('login_bg');
            if ($lama !== '' && preg_match('/^login-bg-[\w]+\.\w+$/', $lama)) @unlink(__DIR__ . '/uploads/' . $lama);
            save_setting('login_bg', $nama);
        }
        flash('Tampilan halaman login disimpan.');
    } elseif ($aksi === 'hapus_bg') {
        $lama = setting('login_bg');
        if ($lama !== '' && preg_match('/^login-bg-[\w]+\.\w+$/', $lama)) @unlink(__DIR__ . '/uploads/' . $lama);
        save_setting('login_bg', '');
        flash('Gambar latar dihapus. Halaman login memakai gradasi warna tema.');
    }
    redirect(base_url('tampilan.php'));
}
$warna = strtolower(setting('tema_warna', '#3b7a67'));
$bg = login_bg();
$pageTitle = 'Tampilan & Tema'; $active = 'tampilan'; $back = base_url('menu.php');
layout_top();
?>
<div class="page-head desk"><div><h1>Tampilan &amp; Tema</h1><div class="muted">Atur warna aplikasi dan halaman login. Berlaku untuk semua pengguna, termasuk halaman scan QR pasien.</div></div>
  <a class="btn light" href="<?= base_url('login.php?pratinjau=1') ?>" target="_blank"><?= icon('image', 18) ?> Lihat halaman login</a></div>

<div class="grid2">
  <form method="post" class="card" id="fWarna"><?= csrf_field() ?><input type="hidden" name="aksi" value="warna">
    <h2>Warna tema aplikasi</h2>
    <p class="small muted" style="margin-top:0">Pilih salah satu warna, atau tentukan warna sendiri. Menu samping, tombol, header HP dan halaman pasien ikut berubah.</p>
    <div class="swatches">
      <?php foreach (TEMA_PRESET as $hex => $nama): ?>
        <label title="<?= e($nama) ?>"><input type="radio" name="preset" value="<?= $hex ?>" <?= $warna === $hex ? 'checked' : '' ?>><i style="background:<?= $hex ?>"></i><span><?= e($nama) ?></span></label>
      <?php endforeach; ?>
    </div>
    <label for="tw">Warna sendiri</label>
    <div class="flex" style="flex-wrap:nowrap">
      <input type="color" id="twPick" value="<?= e($warna) ?>" style="width:64px;height:48px;padding:4px;flex:none">
      <input id="tw" name="tema_warna" value="<?= e($warna) ?>" pattern="#[0-9a-fA-F]{6}" maxlength="7" required style="font-family:monospace">
    </div>
    <div class="prev mt" id="prev">
      <div class="p-side"><i></i><i class="on"></i><i></i><i></i></div>
      <div class="p-main"><div class="p-top"></div><div class="p-row"><span class="p-btn">Tombol</span><span class="p-soft">Label</span></div><div class="p-hero">Header HP</div></div>
    </div>
    <button class="btn mt">Simpan warna</button>
  </form>

  <div>
  <form method="post" enctype="multipart/form-data" class="card" data-once><?= csrf_field() ?><input type="hidden" name="aksi" value="login">
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= MAX_BG ?>">
    <h2>Halaman login</h2>
    <div class="login-thumb <?= $bg ? 'img' : '' ?>" style="<?= $bg ? "background-image:url('" . e($bg) . "');" : '' ?>--ov:<?= (int)setting('login_overlay', '55') / 100 ?>">
      <div><b><?= e(setting('login_judul', 'Nurse Call Digital')) ?></b><small><?= e(setting('login_teks', 'Layanan cepat, pasien lebih nyaman.')) ?></small></div><span class="lc"></span>
    </div>
    <label>Gambar latar <span class="muted small">(JPG / PNG / WEBP, maks. 4 MB, disarankan 1920×1080)</span></label>
    <input type="file" name="login_bg" accept="image/jpeg,image/png,image/webp">
    <div class="hint"><?= $bg ? 'Sekarang memakai gambar. Upload gambar baru untuk mengganti.' : 'Sekarang memakai gradasi warna tema. Upload foto (mis. gedung RS / ruang perawatan) untuk mengganti.' ?></div>
    <label>Gelapkan gambar (overlay warna tema): <b id="ovVal"><?= (int)setting('login_overlay', '55') ?>%</b></label>
    <input type="range" name="login_overlay" min="0" max="90" step="5" value="<?= (int)setting('login_overlay', '55') ?>" oninput="document.getElementById('ovVal').textContent=this.value+'%';document.querySelector('.login-thumb').style.setProperty('--ov',this.value/100)" style="padding:0">
    <div class="hint">Makin tinggi, tulisan di atas foto makin mudah dibaca.</div>
    <label>Judul</label><input name="login_judul" maxlength="80" value="<?= e(setting('login_judul', 'Nurse Call Digital')) ?>">
    <label>Teks di bawah judul</label><input name="login_teks" maxlength="200" value="<?= e(setting('login_teks', 'Layanan cepat, pasien lebih nyaman.')) ?>">
    <button class="btn mt">Simpan halaman login</button>
  </form>
  <?php if ($bg): ?>
  <form method="post" class="card" style="margin-top:-6px"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus_bg">
    <button class="btn light block" data-confirm="Hapus gambar latar login?">Hapus gambar, pakai gradasi warna tema</button></form>
  <?php endif; ?>
  </div>
</div>
<script>
(function () {
  var tw = document.getElementById('tw'), pk = document.getElementById('twPick'), pv = document.getElementById('prev');
  function mix(h, w, t) { var a = [1, 3, 5].map(function (i) { return parseInt(h.substr(i, 2), 16); }), b = [1, 3, 5].map(function (i) { return parseInt(w.substr(i, 2), 16); });
    return 'rgb(' + a.map(function (v, i) { return Math.round(v + (b[i] - v) * t); }).join(',') + ')'; }
  function apply(h) {
    if (!/^#[0-9a-f]{6}$/i.test(h)) return;
    pv.style.setProperty('--p', h); pv.style.setProperty('--ps', mix(h, '#000000', .66)); pv.style.setProperty('--pl', mix(h, '#ffffff', .88)); pv.style.setProperty('--p3', mix(h, '#ffffff', .22));
    pk.value = h.toLowerCase();
    document.querySelectorAll('.swatches input').forEach(function (r) { r.checked = r.value === h.toLowerCase(); });
  }
  document.querySelectorAll('.swatches input').forEach(function (r) { r.addEventListener('change', function () { tw.value = r.value; apply(r.value); }); });
  pk.addEventListener('input', function () { tw.value = pk.value; apply(pk.value); });
  tw.addEventListener('input', function () { apply(tw.value.trim()); });
  apply(tw.value);
})();
</script>
<?php layout_bottom(); ?>