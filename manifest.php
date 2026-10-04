<?php
require __DIR__ . '/inc/bootstrap.php';
header('Content-Type: application/manifest+json; charset=utf-8');
echo json_encode([
    'name'             => 'Nurse Call Digital - ' . setting('nama_rs'),
    'short_name'       => APP_NAME,
    'description'      => 'Panggilan perawat via QR Code gelang pasien',
    'start_url'        => './index.php?source=pwa',
    'scope'            => './',
    'display'          => 'standalone',
    'orientation'      => 'any',
    'background_color' => '#f4f6fb',
    'theme_color'      => '#0f6e8c',
    'lang'             => 'id',
    'icons' => [
        ['src' => 'assets/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => 'assets/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => 'assets/icons/maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
    ],
    'shortcuts' => [
        ['name' => 'Nurse Station', 'url' => './index.php'],
        ['name' => 'Daftar Panggilan', 'url' => './daftar.php'],
        ['name' => 'Daftarkan Pasien', 'url' => './pasien.php?baru=1'],
    ],
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
