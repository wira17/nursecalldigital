<?php
/** Data real-time nurse station. Dipanggil station.js setiap beberapa detik. ?ruang=KD_BANGSAL (kosong = semua) */
if (!is_file(__DIR__ . '/../inc/bootstrap.php')) { header('Content-Type: text/plain; charset=utf-8'); http_response_code(500); exit('Salah folder: file ' . basename(__FILE__) . ' harus diletakkan di folder api/ (nursecalldigital/api/' . basename(__FILE__) . ').'); }
require __DIR__ . '/../inc/bootstrap.php';
if (!is_login() || !bisa('station')) json_out(['ok' => false, 'login' => false], 401);
$ruang = ruang_pantau();
session_write_close(); // jangan kunci sesi selama polling
nonaktifkan_gelang_pulang(); // gelang pasien pulang -> nonaktif, panggilannya ditutup (maks. 1x/menit)
$w = $ruang !== '' ? ' AND p.kd_bangsal = ?' : ''; $par = $ruang !== '' ? [$ruang] : [];
$SQL = str_replace('FROM nc_panggilan_wira p', ', k.ikon FROM nc_panggilan_wira p LEFT JOIN nc_keluhan_wira k ON k.id = p.keluhan_id', SQL_PG);

$aktif = q("$SQL WHERE p.status IN ('baru','diproses') $w
            ORDER BY p.status = 'baru' DESC, FIELD(p.prioritas,'tinggi','sedang','rendah'), p.waktu", $par)->fetchAll();
$hariIni = q("$SQL WHERE DATE(p.waktu) = CURDATE() $w ORDER BY p.id DESC LIMIT 15", $par)->fetchAll();
$st = q("SELECT SUM(status='baru') baru, SUM(status='diproses') diproses,
                SUM(status='selesai' AND DATE(waktu)=CURDATE()) selesai,
                AVG(CASE WHEN DATE(waktu)=CURDATE() AND waktu_respon IS NOT NULL THEN TIMESTAMPDIFF(SECOND, waktu, waktu_respon) END) respon
         FROM nc_panggilan_wira p WHERE (status <> 'selesai' OR DATE(waktu) = CURDATE()) $w", $par)->fetch();

json_out([
    'ok' => true, 'now' => date('c'), 'ruang' => $ruang,
    'stat' => ['baru' => (int)$st['baru'], 'diproses' => (int)$st['diproses'], 'selesai' => (int)$st['selesai'],
               'respon' => $st['respon'] !== null ? durasi((int)round($st['respon'])) : '-'],
    'aktif' => array_map('pg_json', $aktif),
    'hari_ini' => array_map('pg_json', $hariIni),
    'codeblue' => array_map('cb_json', codeblue_aktif()), // Code Blue selalu ke SEMUA ruang
]);