<?php
/**
 * CODE BLUE — aktifkan / selesaikan alarm darurat (dipanggil dari halaman codeblue.php & pop-up alarm).
 * POST aksi=aktifkan  kd_kamar=... (kamar Khanza)  ATAU  lokasi=... (lokasi lain, mis. "Lobi IGD")
 * POST aksi=selesai   id=...  catatan=...
 */
if (!is_file(__DIR__ . '/../inc/bootstrap.php')) { header('Content-Type: text/plain; charset=utf-8'); http_response_code(500); exit('Salah folder: file ' . basename(__FILE__) . ' harus diletakkan di folder api/ (nursecalldigital/api/' . basename(__FILE__) . ').'); }
require __DIR__ . '/../inc/bootstrap.php';
if (!is_login() || !bisa('codeblue')) json_out(['ok' => false, 'msg' => 'Sesi habis, silakan login ulang.'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) json_out(['ok' => false, 'msg' => 'Halaman kedaluwarsa, muat ulang lalu coba lagi.'], 400);
$me = user();
$aksi = (string)($_POST['aksi'] ?? '');

if ($aksi === 'aktifkan') {
    $kamar = substr(trim((string)($_POST['kd_kamar'] ?? '')), 0, 15);
    $lain  = mb_substr(trim(preg_replace('/\s+/', ' ', (string)($_POST['lokasi'] ?? ''))), 0, 120);
    $row = ['kd_kamar' => null, 'kd_bangsal' => null, 'lokasi' => '', 'no_rawat' => null, 'pasien' => null];
    if ($kamar !== '') {
        $k = q("SELECT k.kd_kamar, k.kd_bangsal, b.nm_bangsal FROM kamar k JOIN bangsal b ON b.kd_bangsal = k.kd_bangsal WHERE k.kd_kamar = ?", [$kamar])->fetch();
        if (!$k) json_out(['ok' => false, 'msg' => 'Kamar tidak ditemukan di Khanza.'], 404);
        $ps = q("SELECT ki.no_rawat, ps.nm_pasien FROM kamar_inap ki JOIN reg_periksa rp ON rp.no_rawat = ki.no_rawat JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
                 WHERE ki.kd_kamar = ? AND ki.stts_pulang = '-' ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC", [$kamar])->fetchAll();
        $row = ['kd_kamar' => $k['kd_kamar'], 'kd_bangsal' => $k['kd_bangsal'], 'lokasi' => nama_rapi($k['nm_bangsal'], true) . ' · ' . $k['kd_kamar'],
                'no_rawat' => count($ps) === 1 ? $ps[0]['no_rawat'] : null,
                'pasien' => $ps ? mb_substr(implode(', ', array_map(function ($x) { return nama_rapi($x['nm_pasien']); }, $ps)), 0, 255) : null];
    } elseif ($lain !== '') {
        $row['lokasi'] = $lain;
    } else json_out(['ok' => false, 'msg' => 'Pilih kamar atau isi lokasi.'], 422);
    // Lokasi yang sama masih aktif -> jangan dobel
    $ada = q("SELECT id FROM nc_codeblue_wira WHERE status = 'aktif' AND lokasi = ?", [$row['lokasi']])->fetchColumn();
    if ($ada) json_out(['ok' => true, 'id' => (int)$ada, 'msg' => 'Code Blue di lokasi ini sudah aktif.']);
    q('INSERT INTO nc_codeblue_wira (kd_kamar, kd_bangsal, lokasi, no_rawat, pasien, status, waktu, oleh) VALUES (?,?,?,?,?,?,?,?)',
      [$row['kd_kamar'], $row['kd_bangsal'], $row['lokasi'], $row['no_rawat'], $row['pasien'], 'aktif', date('Y-m-d H:i:s'), $me['id_user']]);
    json_out(['ok' => true, 'id' => (int)db()->lastInsertId(), 'msg' => 'CODE BLUE diaktifkan di ' . $row['lokasi'] . '.']);
}

if ($aksi === 'selesai') {
    $id = (int)($_POST['id'] ?? 0);
    $cat = mb_substr(trim((string)($_POST['catatan'] ?? '')), 0, 255);
    $n = q("UPDATE nc_codeblue_wira SET status = 'selesai', waktu_selesai = NOW(), selesai_oleh = ?, catatan = ? WHERE id = ? AND status = 'aktif'",
           [$me['id_user'], $cat !== '' ? $cat : null, $id])->rowCount();
    json_out(['ok' => true, 'msg' => $n ? 'Code Blue ditandai selesai.' : 'Code Blue ini sudah selesai.']);
}

json_out(['ok' => false, 'msg' => 'Aksi tidak dikenal.'], 400);