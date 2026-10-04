<?php

$title = $title ?? APP_NAME;
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="theme-color" content="<?= e(warna_tema()) ?>">
<meta name="description" content="Nurse Call Digital <?= e(setting('nama_rs')) ?> - panggil perawat lewat QR Code gelang pasien">
<?php if (empty($noInstall)): ?><link rel="manifest" href="<?= base_url('manifest.php') ?>"><?php endif; ?>
<link rel="icon" href="<?= base_url('assets/icons/icon-192.png') ?>">
<link rel="apple-touch-icon" href="<?= base_url('assets/icons/icon-192.png') ?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= e(APP_NAME) ?>">
<link rel="stylesheet" href="<?= aset('assets/app.css') ?>">
<style><?= tema_css() ?></style>
<script>try{if(localStorage.getItem('nc_side_mini')==='1')document.documentElement.classList.add('side-mini')}catch(e){}</script>
</head>
<body class="<?= e($bodyClass ?? '') ?>">