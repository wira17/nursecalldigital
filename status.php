<?php
$NO_SESSION = true;
require __DIR__ . '/../inc/bootstrap.php';
$t = preg_replace('/[^a-f0-9]/', '', $_GET['t'] ?? '');
$r = q("SELECT p.status, p.waktu, p.waktu_respon, p.waktu_selesai, COALESCE(u.nama, p.perawat) perawat FROM nc_panggilan_wira p JOIN nc_gelang_wira g ON g.no_rawat = p.no_rawat
        LEFT JOIN nc_user_wira u ON u.id_user = p.perawat WHERE p.id = ? AND g.token = ?", [(int)($_GET['id'] ?? 0), $t])->fetch();
if (!$r) json_out(['ok' => false], 404);
json_out(['ok' => true, 'status' => $r['status'], 'perawat' => $r['perawat'],
          'respon' => $r['waktu_respon'] ? date('H:i', strtotime($r['waktu_respon'])) : null,
          'selesai' => $r['waktu_selesai'] ? date('H:i', strtotime($r['waktu_selesai'])) : null]);