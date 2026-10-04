<?php

if (!is_file(__DIR__ . '/../inc/bootstrap.php')) { header('Content-Type: text/plain; charset=utf-8'); http_response_code(500); exit('Salah folder: file ' . basename(__FILE__) . ' harus diletakkan di folder api/ (nursecalldigital/api/' . basename(__FILE__) . ').'); }
require __DIR__ . '/../inc/bootstrap.php';
if (!is_login() || !bisa('station')) { http_response_code(403); exit; }
session_write_close();
$a = q('SELECT audio FROM nc_panggilan_wira WHERE id = ?', [(int)($_GET['id'] ?? 0)])->fetchColumn();
$file = $a && preg_match('/^[\w.-]+$/', $a) ? __DIR__ . '/../uploads/suara/' . $a : '';
if (!$file || !is_file($file)) { http_response_code(404); exit('Rekaman tidak ditemukan.'); }
$size = filesize($file); $start = 0; $end = $size - 1;
header('Content-Type: ' . (MIME_AUDIO[pathinfo($file, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
header('Accept-Ranges: bytes');
header('Cache-Control: private, max-age=86400');
if (isset($_GET['unduh'])) header('Content-Disposition: attachment; filename="pesan-suara-' . basename($file) . '"');
if (preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'] ?? '', $m)) {
    if ($m[1] === '' && $m[2] !== '') { $start = max(0, $size - (int)$m[2]); }
    else { $start = (int)$m[1]; if ($m[2] !== '') $end = min((int)$m[2], $size - 1); }
    if ($start > $end || $start >= $size) { http_response_code(416); header("Content-Range: bytes */$size"); exit; }
    http_response_code(206);
    header("Content-Range: bytes $start-$end/$size");
}
header('Content-Length: ' . ($end - $start + 1));
$fp = fopen($file, 'rb'); fseek($fp, $start); $left = $end - $start + 1;
while ($left > 0 && !feof($fp)) { $chunk = fread($fp, min(65536, $left)); echo $chunk; $left -= strlen($chunk); flush(); }
fclose($fp);