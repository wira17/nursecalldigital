<?php
/** Data real-time untuk layar TV (display.php di folder utama). File ini: api/data_tv.php. Akses: ?k=KUNCI_DISPLAY&ruang=KD_BANGSAL (kosong = semua ruang) */
$NO_SESSION = true;
if (!is_file(__DIR__ . '/../inc/bootstrap.php')) { header('Content-Type: text/plain; charset=utf-8'); http_response_code(500); exit('Salah folder: file ' . basename(__FILE__) . ' harus diletakkan di folder api/ (nursecalldigital/api/' . basename(__FILE__) . ').'); }
require __DIR__ . '/../inc/bootstrap.php';
if (!hash_equals(setting('display_key'), (string)($_GET['k'] ?? '')) || setting('display_key') === '') json_out(['ok' => false, 'msg' => 'Kunci display tidak valid.'], 403);
$ruang = substr(preg_replace('/[^\w.-]/', '', (string)($_GET['ruang'] ?? '')), 0, 5); // kd_bangsal Khanza, kosong = semua
$sensor = setting('display_samarkan', '0') === '1';
$nm = function ($n) use ($sensor) { return $sensor ? samarkan($n) : $n; };

nonaktifkan_gelang_pulang(); // gelang pasien pulang -> nonaktif (maks. 1x/menit)
// 1) Cek cepat: ada perubahan panggilan? (query ringan ke tabel nc_panggilan_wira saja)
//    Layar TV mengirim ?sig= terakhir tiap 1,5 detik; jika sama -> jawab singkat tanpa query berat ke Khanza.
$sig = (string)q("SELECT CONCAT(COUNT(*), '-', COALESCE(MAX(id), 0), '-', COALESCE(MAX(UNIX_TIMESTAMP(GREATEST(waktu, COALESCE(waktu_respon, waktu), COALESCE(waktu_selesai, waktu)))), 0))
                  FROM nc_panggilan_wira WHERE status <> 'selesai' OR waktu >= CURDATE()")->fetchColumn();
$cbAktif = codeblue_aktif();
$sig .= '|cb' . implode(',', array_column($cbAktif, 'id'));   // Code Blue baru / selesai -> data dikirim ulang
if (isset($_GET['sig']) && $_GET['sig'] === $sig) json_out(['ok' => true, 'same' => true, 'sig' => $sig]);

// 2) Pasien rawat inap aktif dari Khanza — disimpan sementara 20 detik agar panggilan baru langsung tampil tanpa menunggu query besar
$cacheFile = data_dir() . '/tv_' . md5(DB_NAME . '|' . $ruang) . '.json'; // folder data/ (tertutup dari browser)
$cache = is_file($cacheFile) && filemtime($cacheFile) > time() - 20 ? json_decode((string)@file_get_contents($cacheFile), true) : null;
if (is_array($cache) && isset($cache['pasien'], $cache['alergi'])) {
    $pasien = $cache['pasien']; $alergi = $cache['alergi'];
} else {
    $pasien = array_map('rapikan_pasien', q(SQL_RANAP . ($ruang !== '' ? ' AND k.kd_bangsal = ?' : '') . ' ORDER BY b.nm_bangsal, ki.kd_kamar', $ruang !== '' ? [$ruang] : [])->fetchAll());
    $alergi = alergi_map(array_column($pasien, 'no_rawat'));
    @file_put_contents($cacheFile, json_encode(['pasien' => $pasien, 'alergi' => $alergi], JSON_UNESCAPED_UNICODE | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0)), LOCK_EX);
}
$byRawat = []; foreach ($pasien as $p) $byRawat[$p['no_rawat']] = $p;

// Panggilan yang belum selesai, per pasien diambil yang paling mendesak
$open = q("SELECT p.*, k.ikon, COALESCE(u.nama, p.perawat) perawat FROM nc_panggilan_wira p LEFT JOIN nc_keluhan_wira k ON k.id = p.keluhan_id
           LEFT JOIN nc_user_wira u ON u.id_user = p.perawat WHERE p.status <> 'selesai'
           ORDER BY p.status = 'baru' DESC, FIELD(p.prioritas,'tinggi','sedang','rendah'), p.waktu")->fetchAll();
// Pasien baru masuk rawat inap (belum ada di cache) yang memanggil -> ambil ulang daftar pasien saat ini juga
if ($cache) {
    $kurang = false; foreach ($open as $c) if (!isset($byRawat[$c['no_rawat']]) && ($ruang === '' || $c['kd_bangsal'] === $ruang)) $kurang = true;
    if ($kurang) {
        @unlink($cacheFile);
        $pasien = array_map('rapikan_pasien', q(SQL_RANAP . ($ruang !== '' ? ' AND k.kd_bangsal = ?' : '') . ' ORDER BY b.nm_bangsal, ki.kd_kamar', $ruang !== '' ? [$ruang] : [])->fetchAll());
        $alergi = alergi_map(array_column($pasien, 'no_rawat'));
        $byRawat = []; foreach ($pasien as $p) $byRawat[$p['no_rawat']] = $p;
    }
}
$callOf = [];
foreach ($open as $c) if (!isset($callOf[$c['no_rawat']])) $callOf[$c['no_rawat']] = $c;
$cj = function ($c) { return [
    'id' => (int)$c['id'], 'status' => $c['status'], 'keluhan' => $c['keluhan'], 'ikon' => $c['audio'] ? '🎤' : ($c['ikon'] ?? '🔔'),
    'prioritas' => $c['prioritas'], 'suara' => (bool)$c['audio'], 'pelapor' => $c['pelapor'],
    'waktu' => iso($c['waktu']), 'jam' => date('H:i', strtotime($c['waktu'])), 'perawat' => $c['perawat'],
]; };
$outP = [];
foreach ($pasien as $p) {
    $outP[] = ['id' => $p['no_rawat'], 'nama' => $nm($p['nama']), 'no_rm' => $p['no_rm'], 'jk' => $p['jk'], 'umur' => umur($p['tgl_lahir']),
               'ruang' => $p['ruang'], 'kamar' => $p['kamar'], 'lokasi' => lokasi($p), 'dokter' => $p['dokter'],
               'alergi' => isset($alergi[$p['no_rawat']]), 'call' => isset($callOf[$p['no_rawat']]) ? $cj($callOf[$p['no_rawat']]) : null];
}
$aktif = [];
foreach ($open as $c) {
    if (!isset($byRawat[$c['no_rawat']])) continue; // pasien di ruang lain / sudah pulang
    $pp = $byRawat[$c['no_rawat']];
    $aktif[] = $cj($c) + ['pasien' => $nm($pp['nama']), 'lokasi' => lokasi($pp), 'pesan' => $c['pesan'],
                          'ucapan' => teks_ucapan($c, (string)$pp['ruang'], $nm($pp['nama']))];
}
$st = q("SELECT COUNT(*) n, SUM(status='selesai') selesai,
         AVG(CASE WHEN waktu_respon IS NOT NULL THEN TIMESTAMPDIFF(SECOND, waktu, waktu_respon) END) respon
         FROM nc_panggilan_wira WHERE DATE(waktu) = CURDATE()" . ($ruang !== '' ? ' AND kd_bangsal = ?' : ''), $ruang !== '' ? [$ruang] : [])->fetch();
json_out([
    'ok' => true, 'now' => date('c'), 'sig' => $sig,
    'codeblue' => array_map('cb_json', $cbAktif),
    'pasien' => $outP, 'aktif' => $aktif,
    'stat' => ['pasien' => count($outP), 'hari_ini' => (int)$st['n'], 'selesai' => (int)$st['selesai'],
               'baru' => count(array_filter($aktif, function ($c) { return $c['status'] === 'baru'; })),
               'diproses' => count(array_filter($aktif, function ($c) { return $c['status'] === 'diproses'; })),
               'respon' => $st['respon'] !== null ? durasi((int)round($st['respon'])) : '-'],
    'batas' => (int)setting('batas_respon', '5') * 60,
]);