<?php

require __DIR__ . '/inc/bootstrap.php';
require_login();
$u = user();
$pageTitle = 'Menu'; $active = 'menu';
layout_top();
?>
<div class="card flex" style="flex-wrap:nowrap;gap:14px">
  <span class="avatar lg"><?= e(inisial($u['nama'])) ?></span>
  <div style="flex:1;min-width:0"><b style="font-size:17px"><?= e($u['nama']) ?></b>
    <div class="small muted">NIK <?= e($u['id_user']) ?><?= $u['ruang'] ? ' · Ruang ' . e($u['ruang']) : '' ?></div><span class="badge role" style="margin-top:6px"><?= e((ROLE_LABEL[role()] ?? 'User')) ?></span></div>
</div>
<?php foreach (menu_groups() as $gk => [$judul, $gic, $items]): ?>
  <div class="menu-sec"><?= e($judul) ?></div>
  <div class="menu-grid">
    <?php foreach ($items as $key => [$label, $url, $ic]): ?>
      <a <?= menu_href($url) ?>><i><?= icon($ic) ?></i><?= e($label) ?><?php if ($key === 'index' && bisa('station')): ?><span class="count" data-live-count hidden></span><?php endif; ?></a>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>
<div class="menu-sec">Akun</div>
<div class="menu-grid">
  <a href="<?= base_url('profil.php') ?>"><i><?= icon('user') ?></i>Profil &amp; Password</a>
  <a href="#" data-install hidden><i><?= icon('phone') ?></i>Pasang Aplikasi</a>
  <a href="<?= base_url('logout.php') ?>" data-confirm="Keluar dari aplikasi?"><i style="background:var(--err-soft);color:var(--err)"><?= icon('out') ?></i>Keluar</a>
</div>
<footer class="foot"><?= e(APP_NAME) ?> Digital · <?= e(setting('nama_rs')) ?></footer>
<?php layout_bottom(); ?>