<?php
require __DIR__ . '/inc/bootstrap.php';
if (is_login() && !isset($_GET['pratinjau'])) redirect(base_url());

$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $_SESSION['login_try'] = array_filter($_SESSION['login_try'] ?? [], function ($x) { return $x > time() - 900; });
    $id = trim($_POST['username'] ?? '');
    $pw = (string)($_POST['password'] ?? '');
    if (count($_SESSION['login_try']) >= 5 || login_gagal_ip() >= LOGIN_MAKS_IP) {
        $err = 'Terlalu banyak percobaan login. Coba lagi 15 menit lagi.';
    } elseif ($id === '' || $pw === '' || strlen($id) > 30) {
        $err = 'Isi NIK dan password.';
    } elseif (!khanza_login($id, $pw)) {
        // Login memakai tabel `user` SIMRS Khanza (AES) — semua user Khanza boleh masuk, tanpa daftar NIK
        $_SESSION['login_try'][] = time(); login_gagal_ip(true);
        $err = 'NIK atau password salah.';
    } else {
        $peg = khanza_pegawai($id);
        $nama = mb_substr($peg['nama'] ?? $id, 0, 100);
        $u = q('SELECT * FROM nc_user_wira WHERE id_user = ?', [$id])->fetch();
        if (!$u) {
            // Pertama kali login: buat akun Nurse Call otomatis (NIK di NC_ADMIN_NIK langsung jadi admin)
            $role = is_admin_nik($id) ? 'admin' : 'user';
            q('INSERT INTO nc_user_wira (id_user, nama, role) VALUES (?, ?, ?)', [$id, $nama, $role]);
            $u = q('SELECT * FROM nc_user_wira WHERE id_user = ?', [$id])->fetch();
        } else {
            if ($peg && $peg['nama'] !== $u['nama']) q('UPDATE nc_user_wira SET nama = ? WHERE id_user = ?', [$nama, $id]); // ikut nama terbaru di Khanza
            if (is_admin_nik($id) && ($u['role'] !== 'admin' || !$u['aktif'])) { q("UPDATE nc_user_wira SET role = 'admin', aktif = 1 WHERE id_user = ?", [$id]); $u['aktif'] = 1; }
        }
        if (!$u['aktif']) {
            $err = 'Akses Nurse Call untuk NIK ini dinonaktifkan Admin.';
        } else {
            session_regenerate_id(true);
            $_SESSION['uid'] = $u['id_user'];
            $_SESSION['login_try'] = []; login_reset_ip();
            unset($_SESSION['ruang_pantau']);
            q('UPDATE nc_user_wira SET last_login = NOW() WHERE id_user = ?', [$id]);
            redirect(base_url());
        }
    }
}
$namaRs = setting('nama_rs');
$title = 'Login - ' . APP_NAME;
$bg = login_bg();
$ov = max(0, min(90, (int)setting('login_overlay', '55'))) / 100;
$bodyClass = 'lg-body';
require __DIR__ . '/inc/head.php';
?>
<style>
/* ===== Halaman login (layout layar penuh: info kiri · form kanan) ===== */
body.lg-body{margin:0;background:var(--side)}
.lg{position:relative;min-height:100vh;min-height:100dvh;display:flex;flex-direction:column;color:#fff;overflow:hidden;isolation:isolate}
.lg-bg{position:absolute;inset:-30px;z-index:-2;background:radial-gradient(70% 60% at 75% 30%,rgb(var(--pri-rgb) / .55),transparent 70%),radial-gradient(60% 60% at 10% 90%,rgb(var(--pri-rgb) / .35),transparent 70%),linear-gradient(160deg,var(--side-2),var(--side));background-size:cover;background-position:center}
.lg-bg.img{background-image:var(--img);filter:blur(6px) saturate(1.05);transform:scale(1.04)}
.lg::before{content:"";position:absolute;inset:0;z-index:-1;background:linear-gradient(rgb(var(--pri-rgb) / .16),rgb(var(--pri-rgb) / .16)),linear-gradient(90deg,rgba(8,18,22,calc(var(--ov) + .15)) 0%,rgba(8,18,22,var(--ov)) 55%,rgba(8,18,22,calc(var(--ov) + .05)) 100%)}
.lg-in{flex:1;width:100%;max-width:1280px;margin:0 auto;padding:40px 86px 0;box-sizing:border-box;display:grid;grid-template-columns:minmax(0,1fr) 1px 420px;gap:0 64px;align-items:center}
.lg-logo{display:grid;place-items:center;width:76px;height:76px;border-radius:18px;background:#fff;border:4px solid #fff;box-shadow:0 10px 30px rgba(0,0,0,.25)}
.lg-logo svg{width:56px;height:56px}
.lg-eyebrow{margin:26px 0 10px;font-size:12.5px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:var(--pri-3);color:color-mix(in srgb,var(--pri-3) 45%,#fff)}
.lg-title{margin:0;font-size:clamp(34px,4.4vw,56px);line-height:1.05;font-weight:800;letter-spacing:-.025em}
.lg-sub{margin:16px 0 0;max-width:470px;font-size:16px;line-height:1.65;color:rgba(255,255,255,.88)}
.lg-list{list-style:none;margin:24px 0 0;padding:0;display:grid;gap:13px}
.lg-list li{display:flex;align-items:center;gap:12px;font-size:14.5px;color:rgba(255,255,255,.92)}
.lg-list li i{flex:none;display:grid;place-items:center;width:18px;height:18px;border-radius:50%;border:1.6px solid var(--pri-3);color:var(--pri-3);border-color:color-mix(in srgb,var(--pri-3) 45%,#fff);color:color-mix(in srgb,var(--pri-3) 45%,#fff)}
.lg-list li i svg{width:10px;height:10px}
.lg-div{align-self:center;height:390px;background:rgba(255,255,255,.18)}
.lg-form h2{margin:0;font-size:32px;font-weight:800;letter-spacing:-.02em;color:#fff}
.lg-form .lead{margin:8px 0 26px;font-size:14px;color:rgba(255,255,255,.85)}
.lg-field{margin-bottom:22px}
.lg-field label{display:block;margin:0 0 8px;font-size:11.5px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.85)}
.lg-inp{display:flex;align-items:center;gap:12px;border-bottom:1.5px solid rgba(255,255,255,.3);padding:0 0 10px;transition:border-color .2s}
.lg-inp:focus-within{border-color:#fff}
.lg-inp svg{flex:none;opacity:.8}
.lg-inp input{flex:1;min-width:0;border:0!important;background:transparent!important;color:#fff;font:inherit;font-size:16px;padding:4px 0!important;outline:0;box-shadow:none!important;border-radius:0}
.lg-inp input::placeholder{color:rgba(255,255,255,.55)}
.lg-inp input:-webkit-autofill{-webkit-text-fill-color:#fff;transition:background-color 9999s}
.lg-eye{border:0;background:none;color:#fff;font:inherit;font-size:13px;font-weight:700;cursor:pointer;padding:4px 2px;opacity:.9}
.lg-btn{margin-top:6px;width:100%;display:flex;align-items:center;justify-content:space-between;gap:10px;border:0;border-radius:99px;background:#fff;color:var(--side);font:inherit;font-size:16px;font-weight:800;padding:9px 9px 9px 26px;cursor:pointer;box-shadow:0 10px 30px rgba(0,0,0,.25);transition:transform .15s}
.lg-btn:hover{transform:translateY(-1px)}
.lg-btn i{display:grid;place-items:center;width:36px;height:36px;border-radius:50%;background:var(--pri);color:#fff}
.lg-help{margin:16px 0 0;font-size:13px;color:rgba(255,255,255,.78)}
.lg-err{margin:0 0 20px;padding:11px 14px;border-radius:12px;background:rgba(220,60,60,.18);border:1px solid rgba(255,140,140,.45);color:#ffd9d6;font-size:14px}
.lg-install{margin-top:14px;border:1px solid rgba(255,255,255,.3);background:rgba(255,255,255,.08);color:#fff;border-radius:99px;padding:7px 14px;font:inherit;font-size:13px;cursor:pointer}
.lg-foot{width:100%;max-width:1280px;margin:0 auto;padding:26px 86px 30px;box-sizing:border-box;display:flex;justify-content:space-between;gap:16px;font-size:13px;color:rgba(255,255,255,.78)}
.lg-foot b{font-weight:600;color:#fff}
.lg-foot small{font-size:.8em;font-weight:700;letter-spacing:.05em}
@media(max-width:980px){
  .lg-in{grid-template-columns:1fr;padding:34px 24px 0;gap:34px}
  .lg-div{display:none}
  .lg-info{text-align:left}
  .lg-list{display:none}
  .lg-title{font-size:36px}.lg-sub{font-size:15px}
  .lg-form{width:100%;max-width:440px}
  .lg-foot{padding:26px 24px 24px;flex-direction:column;gap:4px}
}
@media(max-width:980px) and (max-height:760px){.lg-sub{display:none}.lg-eyebrow{margin-top:18px}}
</style>
<div class="lg" style="--ov:<?= $ov ?>">
  <div class="lg-bg<?= $bg ? ' img' : '' ?>"<?= $bg ? ' style="--img:url(\'' . e($bg) . '\')"' : '' ?>></div>
  <div class="lg-in">
    <div class="lg-info">
      <span class="lg-logo"><?= logo_svg(56) ?></span>
      <div class="lg-eyebrow"><?= e($namaRs !== '' ? $namaRs : 'SIMRS Khanza') ?> · Panel Petugas</div>
      <h1 class="lg-title"><?= e(setting('login_judul', 'Nurse Call Digital')) ?></h1>
      <p class="lg-sub"><?= e(setting('login_teks', 'Layanan cepat, pasien lebih nyaman.')) ?></p>
      <ul class="lg-list">
        <?php $cek = '<i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></i>'; ?>
        <li><?= $cek ?>Panggilan pasien langsung masuk ke ruang jaga perawat</li>
        <li><?= $cek ?>Notifikasi, alarm &amp; display TV secara real-time</li>
        <li><?= $cek ?>Terhubung langsung dengan database SIMRS Khanza</li>
      </ul>
    </div>
    <div class="lg-div"></div>
    <form method="post" class="lg-form" autocomplete="on">
      <h2>Masuk</h2>
      <p class="lead">Gunakan NIK &amp; password akun SIMRS Khanza Anda.</p>
      <?php if ($err): ?><div class="lg-err"><?= e($err) ?></div><?php endif; ?>
      <?= csrf_field() ?>
      <div class="lg-field">
        <label for="u">NIK</label>
        <div class="lg-inp"><?= icon('user', 20) ?><input id="u" name="username" required maxlength="30" autocomplete="username" autocapitalize="none" autofocus placeholder="Masukkan NIK" value="<?= e($_POST['username'] ?? '') ?>"></div>
      </div>
      <div class="lg-field">
        <label for="p">Password</label>
        <div class="lg-inp"><?= icon('key', 20) ?><input id="p" name="password" type="password" required autocomplete="current-password" placeholder="Masukkan password">
          <button type="button" class="lg-eye" onclick="var p=document.getElementById('p');p.type=p.type==='password'?'text':'password';this.textContent=p.type==='password'?'Lihat':'Sembunyi'">Lihat</button></div>
      </div>
      <button class="lg-btn" type="submit">Masuk<i><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></i></button>
      <p class="lg-help">Lupa password? Hubungi administrator SIMRS Khanza.</p>
      <button type="button" class="lg-install" data-install hidden>📲 Pasang aplikasi di perangkat ini</button>
    </form>
  </div>
  <footer class="lg-foot">
    <span><b id="lgJam"><?= date('H:i') ?></b> <small><?= e(zona()) ?></small> · <span id="lgTgl"><?= e(HARI[(int)date('w')]) ?>, <?= tgl_indo(date('Y-m-d'), false, true) ?></span></span>
    <span>© <?= date('Y') ?> Nurse Call Digital · <?= e(APP_FOOTER) ?></span>
  </footer>
</div>
<script>
(function () { // jam berjalan (waktu server + selisih jam perangkat)
  var el = document.getElementById('lgJam'), s = <?= time() ?> * 1000 - Date.now();
  function t() { var d = new Date(Date.now() + s), o = { timeZone: <?= json_encode(date_default_timezone_get()) ?>, hour: '2-digit', minute: '2-digit', hour12: false };
    try { el.textContent = d.toLocaleTimeString('id-ID', o).replace('.', ':'); } catch (e) {} }
  t(); setInterval(t, 10000);
})();
</script>
<?php require __DIR__ . '/inc/foot.php'; ?>