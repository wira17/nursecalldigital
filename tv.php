<?php

require __DIR__ . '/inc/bootstrap.php';
require_akses('station');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require_akses('master');
    if (($_POST['aksi'] ?? '') === 'kunci') {
        display_key(true);
        flash('Kunci display dibuat ulang. Link lama tidak berlaku — buka link baru di setiap TV.');
    } else {
        save_setting('display_samarkan', !empty($_POST['display_samarkan']) ? '1' : '0');
        save_setting('display_suara', !empty($_POST['display_suara']) ? '1' : '0');
        save_setting('display_teks', mb_substr(trim($_POST['display_teks'] ?? ''), 0, 300));
        flash('Pengaturan display TV disimpan. TV akan memakai pengaturan baru saat dimuat ulang.');
    }
    redirect(base_url('tv.php'));
}
$ruangList = daftar_bangsal();
$links = [['Semua ruang', url_display(''), array_sum(array_column($ruangList, 'n'))]];
foreach ($ruangList as $r) $links[] = ['Ruang ' . $r['nama'], url_display($r['kd_bangsal']), (int)$r['n']];

$pageTitle = 'Display TV'; $active = 'tv'; $back = base_url('menu.php');
layout_top();
?>
<div class="page-head desk"><div><h1>Display TV</h1><div class="muted">Layar besar untuk ruang perawat: daftar pasien per bed dan panggilan yang masuk, diperbarui otomatis.</div></div></div>
<div class="grid3">
  <div>
    <div class="card flush"><div class="table-wrap"><table>
      <tr><th>Layar</th><th class="right">Pasien</th><th>Link untuk dibuka di TV</th><th></th></tr>
      <?php foreach ($links as [$nama, $url, $n]): ?>
      <tr><td><b><?= e($nama) ?></b></td><td class="right"><?= (int)$n ?></td>
        <td style="max-width:360px"><input readonly value="<?= e($url) ?>" onclick="this.select()" style="padding:8px 10px;font-size:13px"></td>
        <td class="nowrap"><button type="button" class="btn sm light" data-copy="<?= e($url) ?>"><?= icon('copy', 16) ?> Salin</button>
          <a class="btn sm" href="<?= e($url) ?>" target="_blank"><?= icon('tv', 16) ?> Buka</a></td></tr>
      <?php endforeach; ?>
    </table></div></div>
    <div class="card"><h2>Cara memasang di TV</h2>
      <ol class="small" style="padding-left:18px;margin:0;line-height:1.9">
        <li>Hubungkan TV ke mini PC / Android TV Box / Smart TV yang terhubung ke jaringan RS.</li>
        <li>Buka browser (Chrome disarankan), lalu buka <b>link ruang</b> di atas (link bisa dijadikan bookmark / halaman awal).</li>
        <li>Tekan <b>F</b> atau tombol ⛶ di kanan bawah untuk layar penuh.</li>
        <li><b>Klik sekali / tekan OK pada remote</b> agar suara pengumuman aktif (aturan browser).</li>
        <li>Agar suara langsung aktif tanpa klik dan layar penuh otomatis (mode kios), jalankan Chrome dengan:<br>
          <code style="font-size:12px;word-break:break-all">chrome --kiosk --autoplay-policy=no-user-gesture-required "LINK_DISPLAY"</code></li>
        <li>Matikan mode hemat daya / screensaver TV agar layar selalu menyala.</li>
      </ol>
      <p class="small muted" style="margin-bottom:0">Link berisi kunci akses, jadi TV tidak perlu login. Jangan bagikan link ke luar RS. Jika bocor, Admin bisa membuat kunci baru.</p>
    </div>
  </div>
  <div class="dash-side">
    <?php if (bisa('master')): ?>
    <form method="post" class="card"><?= csrf_field() ?>
      <h2>Pengaturan display</h2>
      <label style="display:flex;gap:10px;align-items:flex-start;font-weight:500"><input type="checkbox" name="display_suara" value="1" <?= setting('display_suara', '1') === '1' ? 'checked' : '' ?> style="margin-top:4px"><span>Bunyi ding-dong &amp; suara pengumuman saat ada panggilan</span></label>
      <label style="display:flex;gap:10px;align-items:flex-start;font-weight:500"><input type="checkbox" name="display_samarkan" value="1" <?= setting('display_samarkan', '0') === '1' ? 'checked' : '' ?> style="margin-top:4px"><span>Samarkan nama pasien (mis. <i>Andi P.</i>) — disarankan jika TV terlihat pengunjung</span></label>
      <label>Teks berjalan (bawah layar)</label>
      <textarea name="display_teks" maxlength="300" style="min-height:70px"><?= e(setting('display_teks')) ?></textarea>
      <button class="btn mt">Simpan</button>
    </form>
    <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="aksi" value="kunci">
      <h2>Kunci akses</h2>
      <p class="small muted" style="margin-top:0">Buat kunci baru jika link display tersebar. Semua TV harus dibuka ulang dengan link baru.</p>
      <button class="btn light block" data-confirm="Buat kunci baru? Semua link display lama berhenti bekerja.">Buat ulang kunci</button>
    </form>
    <?php else: ?>
    <div class="card small muted">Pengaturan display (suara, nama disamarkan, teks berjalan) diatur oleh Admin.</div>
    <?php endif; ?>
  </div>
</div>
<script>
document.querySelectorAll('[data-copy]').forEach(function (b) {
  b.addEventListener('click', function () {
    var t = b.getAttribute('data-copy'), done = function () { var o = b.innerHTML; b.textContent = '✓ Disalin'; setTimeout(function () { b.innerHTML = o; }, 1500); };
    if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(t).then(done);
    else { var i = b.closest('tr').querySelector('input'); i.select(); document.execCommand('copy'); done(); }
  });
});
</script>
<?php layout_bottom(); ?>