<?php
/**
 * CEK INSTALASI NURSE CALL — buka http://server/nursecall/cek.php jika aplikasi menampilkan layar putih / error.
 * Ditulis dengan sintaks PHP lama agar tetap jalan walau versi PHP server terlalu tua.
 * Hapus file ini setelah aplikasi berjalan normal.
 */
// Hanya boleh dibuka dari komputer server / jaringan lokal RS (berisi info teknis). Hapus file ini setelah instalasi.
$ipCek = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
$lokal = $ipCek === '' || $ipCek === '::1' || strpos($ipCek, '127.') === 0
      || (filter_var($ipCek, FILTER_VALIDATE_IP) && !filter_var($ipCek, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE));
if (!$lokal) { header('HTTP/1.1 403 Forbidden'); header('Content-Type: text/plain; charset=utf-8'); exit('cek.php hanya bisa dibuka dari komputer server / jaringan lokal. Hapus file ini setelah instalasi selesai.'); }
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/html; charset=utf-8');
$hasil = array();
function baris($nama, $ok, $ket) { global $hasil; $hasil[] = array($nama, $ok, $ket); }

// 1. PHP & ekstensi
baris('Versi PHP', version_compare(PHP_VERSION, '7.1.0', '>='), PHP_VERSION . ' (minimal 7.1, disarankan 7.4 / 8.x)');
foreach (array('pdo_mysql' => 'wajib (koneksi database)', 'mbstring' => 'disarankan', 'json' => 'wajib', 'session' => 'wajib', 'fileinfo' => 'opsional') as $ext => $ket)
    baris('Ekstensi ' . $ext, extension_loaded($ext) || $ket === 'opsional' || $ket === 'disarankan', (extension_loaded($ext) ? 'aktif' : 'TIDAK aktif') . ' — ' . $ket);

// 1b. File aplikasi versi terbaru (mendeteksi file lama yang belum ditimpa)
$versi = array(
    'inc/bootstrap.php' => 'nc_user_wira', 'inc/layout.php' => 'admisi.php', 'inc/fungsi_panggilan.php' => 'nc_panggilan_wira', 'inc/filter_pg.php' => 'kd_bangsal',
    'login.php' => 'khanza_login', 'index.php' => 'daftar_bangsal', 'admisi.php' => 'reg_periksa', 'ranap.php' => 'kamar_inap', 'gelang.php' => 'data_gelang',
    'lapor.php' => 'nc_gelang_wira', 'pasien.php' => 'ranap(', 'api/poll.php' => 'nc_panggilan_wira', 'api/data_tv.php' => 'nc_panggilan_wira', 'display.php' => 'data_tv.php', 'assets/js/gelang-modal.js' => 'data-gelang', 'assets/js/station.js' => 'r.redirected', 'inc/foot.php' => 'aset(',
);
foreach ($versi as $file => $tanda) {
    $isi = is_file(__DIR__ . '/' . $file) ? file_get_contents(__DIR__ . '/' . $file) : null;
    baris('File ' . $file, $isi !== null && strpos($isi, $tanda) !== false, $isi === null ? 'TIDAK ADA — salin file ini' : (strpos($isi, $tanda) !== false ? 'versi terbaru' : 'MASIH VERSI LAMA — timpa dengan file terbaru'));
}
foreach (array('api/codeblue.php', 'api/display.php', 'inc/panggilan.php', 'inc/khanza.php', 'update_pesan_suara.sql', 'update_display_tv.sql', 'update_login_khanza.sql') as $lama)
    if (is_file(__DIR__ . '/' . $lama)) baris('File lama ' . $lama, false, 'sudah tidak dipakai — hapus');

// 1c. Pindai SEMUA file PHP: cari nama tabel versi lama (sebelum pakai Khanza / sebelum akhiran _wira)
$pola = '/\\b(FROM|JOIN|INTO|UPDATE)\\s+`?(panggilan|users|settings|keluhan|ruang|gelang|nc_user|nc_gelang|nc_keluhan|nc_panggilan|nc_setting)\\b(?!_wira)/i';
$lamaAda = array();
foreach (array('', 'api/', 'inc/') as $dir) {
    foreach ((array)glob(__DIR__ . '/' . $dir . '*.php') as $f) {
        if (basename($f) === 'cek.php') continue;
        $isi = file_get_contents($f);
        if (preg_match($pola, $isi, $m)) $lamaAda[] = $dir . basename($f) . ' (tabel "' . $m[2] . '")';
    }
}
baris('Pindai file versi lama', !$lamaAda, $lamaAda ? 'MASIH VERSI LAMA, timpa file ini: ' . implode(', ', $lamaAda) : 'tidak ada file lama');
$wajibAda = array('index.php', 'login.php', 'logout.php', 'admisi.php', 'ranap.php', 'gelang.php', 'lapor.php', 'pasien.php', 'panggilan.php', 'display.php',
    'api/poll.php', 'api/aksi.php', 'api/status.php', 'api/audio.php', 'api/data_tv.php', 'api/aksi_codeblue.php', 'codeblue.php', 'assets/js/codeblue.js',
    'inc/bootstrap.php', 'inc/layout.php', 'inc/fungsi_panggilan.php', 'inc/head.php', 'inc/foot.php', 'inc/filter_pg.php',
    'assets/js/app.js', 'assets/js/station.js', 'assets/js/display.js', 'assets/js/gelang-modal.js', 'assets/js/qrcode.js', 'database.sql');
$hilang = array(); foreach ($wajibAda as $f) if (!is_file(__DIR__ . '/' . $f)) $hilang[] = $f;
baris('Kelengkapan file', !$hilang, $hilang ? 'file tidak ada: ' . implode(', ', $hilang) : 'lengkap');

// 2. config.php
$cfgOk = is_file(__DIR__ . '/config.php');
if ($cfgOk) { ob_start(); $cfgOk = (include __DIR__ . '/config.php') !== false; ob_end_clean(); }
baris('config.php', $cfgOk && defined('DB_NAME'), $cfgOk ? 'terbaca, database: ' . (defined('DB_NAME') ? DB_NAME : '?') : 'tidak ditemukan / error');

// 3. Koneksi & tabel
$pdo = null;
if ($cfgOk && defined('DB_NAME') && extension_loaded('pdo_mysql') && version_compare(PHP_VERSION, '5.3.0', '>=')) {
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . (defined('DB_PORT') ? DB_PORT : 3306) . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        $v = $pdo->query('SELECT VERSION()')->fetchColumn();
        baris('Koneksi database', true, 'terhubung ke ' . DB_NAME . ' (MySQL/MariaDB ' . $v . ')');
    } catch (Exception $e) { baris('Koneksi database', false, $e->getMessage()); }
}
if ($pdo) {
    $butuh = array(
        'user' => array('id_user', 'password'), 'petugas' => array('nip', 'nama'), 'dokter' => array('kd_dokter', 'nm_dokter'),
        'setting' => array('nama_instansi'), 'pasien' => array('no_rkm_medis', 'nm_pasien', 'jk', 'tgl_lahir'),
        'reg_periksa' => array('no_rawat', 'tgl_registrasi', 'jam_reg', 'kd_dokter', 'no_rkm_medis', 'kd_poli', 'kd_pj', 'stts', 'stts_daftar', 'status_lanjut', 'status_bayar', 'umurdaftar', 'sttsumur'),
        'kamar_inap' => array('no_rawat', 'kd_kamar', 'diagnosa_awal', 'tgl_masuk', 'jam_masuk', 'tgl_keluar', 'jam_keluar', 'stts_pulang'),
        'kamar' => array('kd_kamar', 'kd_bangsal', 'statusdata'), 'bangsal' => array('kd_bangsal', 'nm_bangsal', 'status'),
        'poliklinik' => array('kd_poli', 'nm_poli'), 'penjab' => array('kd_pj', 'png_jawab'), 'dpjp_ranap' => array('no_rawat', 'kd_dokter'),
        'pemeriksaan_ranap' => array('no_rawat', 'alergi'),
        'nc_user_wira' => array('id_user', 'role'), 'nc_gelang_wira' => array('no_rawat', 'token'), 'nc_panggilan_wira' => array('no_rawat', 'kd_bangsal', 'audio'),
        'nc_keluhan_wira' => array('nama'), 'nc_setting_wira' => array('k', 'v'), 'nc_codeblue_wira' => array('lokasi', 'status'),
    );
    foreach ($butuh as $tabel => $kolom) {
        try {
            $ada = array();
            foreach ($pdo->query('SHOW COLUMNS FROM `' . $tabel . '`') as $c) $ada[] = $c['Field'];
            $kurang = array_diff($kolom, $ada);
            $nc = strpos($tabel, 'nc_') === 0;
            baris(($nc ? 'Tabel Nurse Call ' : 'Tabel Khanza ') . $tabel, !$kurang, $kurang ? 'kolom tidak ada: ' . implode(', ', $kurang) : 'OK');
        } catch (Exception $e) {
            $nc = strpos($tabel, 'nc_') === 0;
            baris(($nc ? 'Tabel Nurse Call ' : 'Tabel Khanza ') . $tabel, false, $nc ? 'belum ada — import database.sql ke database ' . DB_NAME : 'tidak ditemukan');
        }
    }
    try {
        $n = $pdo->query("SELECT COUNT(*) FROM kamar_inap WHERE stts_pulang = '-'")->fetchColumn();
        baris('Pasien rawat inap aktif', true, $n . ' pasien');
        $n = $pdo->query('SELECT COUNT(*) FROM reg_periksa WHERE tgl_registrasi = CURDATE()')->fetchColumn();
        baris('Registrasi hari ini', true, $n . ' pendaftaran');
    } catch (Exception $e) { }
    try {
        $st = $pdo->prepare('SELECT COUNT(*) FROM user WHERE id_user = AES_ENCRYPT(?, ?)');
        $nik = defined('NC_ADMIN_NIK') ? NC_ADMIN_NIK : array();
        $nik = is_array($nik) && $nik ? reset($nik) : '';
        $st->execute(array($nik, defined('KHANZA_KEY_USER') ? KHANZA_KEY_USER : 'nur'));
        $adaNik = (bool)$st->fetchColumn();
        baris('NIK admin (NC_ADMIN_NIK) ada di tabel user Khanza', $adaNik, $nik ? $nik : 'belum diisi di config.php');
    } catch (Exception $e) { baris('NIK admin', false, $e->getMessage()); }
}
// 4. Folder upload
foreach (array('uploads', 'uploads/suara', 'data') as $d) baris('Folder ' . $d . ' bisa ditulis', is_writable(__DIR__ . '/' . $d), is_dir(__DIR__ . '/' . $d) ? (is_writable(__DIR__ . '/' . $d) ? 'OK' : 'ubah izin menjadi 755/775') : 'folder tidak ada');
$gagal = 0; foreach ($hasil as $h) if (!$h[1]) $gagal++;
?><!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Cek Instalasi Nurse Call</title>
<style>body{font-family:system-ui,sans-serif;background:#f4f6fb;margin:0;padding:20px;color:#1d2433}.w{max-width:860px;margin:auto;background:#fff;border-radius:14px;padding:22px;box-shadow:0 4px 16px rgba(0,0,0,.06)}
table{width:100%;border-collapse:collapse;font-size:14px}td{padding:8px 10px;border-bottom:1px solid #e3e7ef;vertical-align:top}.ok{color:#1c8a4e;font-weight:700}.no{color:#c0392b;font-weight:700}
.sum{padding:12px 14px;border-radius:10px;margin-bottom:14px}</style></head><body><div class="w">
<h2 style="margin-top:0">Cek Instalasi Nurse Call</h2>
<div class="sum" style="background:<?php echo $gagal ? '#fdecea' : '#e5f5ec'; ?>"><?php echo $gagal ? '<b>' . $gagal . ' pemeriksaan gagal.</b> Perbaiki baris berwarna merah di bawah.' : '<b>Semua OK.</b> Buka <a href="index.php">aplikasi</a>, lalu hapus file cek.php ini.'; ?></div>
<table><?php foreach ($hasil as $h): ?><tr><td><?php echo htmlspecialchars($h[0]); ?></td><td class="<?php echo $h[1] ? 'ok' : 'no'; ?>"><?php echo $h[1] ? '✔' : '✘'; ?></td><td><?php echo htmlspecialchars($h[2]); ?></td></tr><?php endforeach; ?></table>
<p style="font-size:13px;color:#6b7385">Kolom Khanza yang tidak ada: kirim pesan ini ke pengembang agar query disesuaikan dengan versi Khanza Anda.</p>
</div></body></html>