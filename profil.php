<?php
require __DIR__ . '/inc/bootstrap.php';
require_login();
$u = user();
$pageTitle = 'Profil'; $active = 'profil'; $back = base_url('menu.php');
layout_top();
?>
<div class="page-head desk"><h1>Profil Saya</h1></div>
<div class="grid2">
  <div class="card">
    <div class="flex" style="flex-wrap:nowrap;gap:14px"><span class="avatar lg"><?= e(inisial($u['nama'])) ?></span>
      <div><b style="font-size:18px"><?= e($u['nama']) ?></b><div class="small muted">NIK <?= e($u['id_user']) ?><?= $u['ruang'] ? ' · Ruang ' . e($u['ruang']) : '' ?></div><span class="badge role"><?= e((ROLE_LABEL[role()] ?? 'User')) ?></span></div></div>
  </div>
  <div class="card"><h2>Password</h2><p class="muted" style="margin:0">Anda login memakai akun <b>SIMRS Khanza</b> (NIK <?= e($u['id_user']) ?>). Untuk mengganti password, ubah melalui Khanza — password baru langsung berlaku di Nurse Call.</p></div>
</div>
<?php layout_bottom(); ?>