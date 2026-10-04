<?php

if (!is_file(__DIR__ . '/../inc/bootstrap.php')) { header('Content-Type: text/plain; charset=utf-8'); http_response_code(500); exit('Salah folder: file ' . basename(__FILE__) . ' harus diletakkan di folder api/ (nursecalldigital/api/' . basename(__FILE__) . ').'); }
require __DIR__ . '/../inc/bootstrap.php';
$ajax = ($_POST['ajax'] ?? '') === '1';
if (!is_login() || !bisa('tangani')) $ajax ? json_out(['ok' => false, 'msg' => 'Sesi habis, silakan login ulang.'], 401) : redirect(base_url('login.php'));
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(base_url());
if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) $ajax ? json_out(['ok' => false, 'msg' => 'Sesi kedaluwarsa, muat ulang halaman.'], 419) : csrf_check();
$id = (int)($_POST['id'] ?? 0);
try {
    $msg = ubah_status($id, $_POST['aksi'] ?? '', $_POST['tindakan'] ?? '');
    if ($ajax) json_out(['ok' => true, 'msg' => $msg]);
    flash($msg);
} catch (RuntimeException $ex) {
    if ($ajax) json_out(['ok' => false, 'msg' => $ex->getMessage()], 409);
    flash($ex->getMessage(), 'err');
}
$ke = $_POST['kembali'] ?? '';
redirect(preg_match('/^[a-z_]+\.php(\?[\w=&%-]*)?$/', $ke) ? base_url($ke) : base_url('panggilan.php?id=' . $id));