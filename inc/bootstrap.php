<?php
require_once __DIR__ . '/../config.php';

// Permintaan API (api/*.php) selalu dijawab JSON murni: peringatan PHP tidak boleh ikut tercetak karena merusak JSON
define('IS_API', strpos(str_replace('\\', '/', (string)($_SERVER['SCRIPT_FILENAME'] ?? '')), '/api/') !== false);

/** Pengakses dari komputer server sendiri / jaringan lokal RS (localhost, 10.x, 172.16–31.x, 192.168.x) */
function klien_lokal(): bool {
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if ($ip === '' || $ip === '::1' || strpos($ip, '127.') === 0) return true;
    return (bool)filter_var($ip, FILTER_VALIDATE_IP) && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
}
// Detail error teknis HANYA ditampilkan ke jaringan lokal walau APP_DEBUG = true (saat online, pengunjung luar tidak melihat struktur database)
define('TAMPIL_DETAIL', APP_DEBUG && klien_lokal());
error_reporting(E_ALL);
ini_set('display_errors', TAMPIL_DETAIL && !IS_API ? '1' : '0');
ini_set('expose_php', '0');

/* ---------- Header keamanan (berlaku di Apache maupun Nginx) ---------- */
function pakai_https(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
}
if (!headers_sent() && PHP_SAPI !== 'cli') {
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: microphone=(self), camera=(), geolocation=()');
    if (pakai_https()) header('Strict-Transport-Security: max-age=15552000');
}

/** Folder data internal (penanda instalasi, cache TV, catatan login gagal) — TIDAK bisa dibuka dari browser */
function data_dir(): string {
    $d = __DIR__ . '/../data';
    if (!is_dir($d)) @mkdir($d, 0755, true);
    if (is_dir($d) && !is_file("$d/.htaccess")) {
        @file_put_contents("$d/.htaccess", "# Data internal Nurse Call - tidak boleh diakses dari browser\n<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n");
        @file_put_contents("$d/index.html", '');
    }
    return $d;
}
ini_set('log_errors', '1');

/* ---------- Jangan pernah tampil "layar putih": tampilkan pesan error yang jelas ---------- */
function tampil_error(string $pesan, string $detail = ''): void {
    error_log('[NurseCall] ' . $pesan . ($detail ? ' | ' . $detail : ''));
    if (headers_sent() === false) { http_response_code(500); header('Content-Type: text/html; charset=utf-8'); }
    if (IS_API || (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        while (ob_get_level()) ob_end_clean();
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'msg' => $pesan . ($detail && TAMPIL_DETAIL ? ' | ' . strtok($detail, "\n") : '')], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        return;
    }
    echo '<div style="font-family:system-ui,sans-serif;max-width:760px;margin:40px auto;padding:20px 24px;border:1px solid #f5c2c0;background:#fdecea;border-radius:12px;color:#7a1f16">'
       . '<h3 style="margin:0 0 8px">Nurse Call: terjadi kesalahan</h3><div>' . htmlspecialchars($pesan) . '</div>'
       . (TAMPIL_DETAIL && $detail ? '<pre style="white-space:pre-wrap;font-size:12px;background:#fff;padding:10px;border-radius:8px;margin-top:12px">' . htmlspecialchars($detail) . '</pre>' : '')
       . '<p style="font-size:13px;margin:12px 0 0">Buka <b>cek.php</b> untuk memeriksa PHP, koneksi & tabel database. '
       . (TAMPIL_DETAIL ? '' : 'Detail teknis dicatat di log error PHP (terlihat di layar hanya jika <code>APP_DEBUG</code> aktif dan dibuka dari jaringan lokal).') . '</p></div>';
}
set_exception_handler(function ($ex) {
    $pesan = $ex instanceof PDOException ? 'Query ke database Khanza gagal. Kemungkinan ada tabel/kolom yang berbeda di Khanza Anda.' : 'Kesalahan program.';
    tampil_error($pesan . (TAMPIL_DETAIL ? ' ' . $ex->getMessage() : ''), get_class($ex) . ': ' . $ex->getMessage() . "\n" . $ex->getFile() . ':' . $ex->getLine() . "\n" . $ex->getTraceAsString());
});
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true))
        tampil_error('Kesalahan fatal PHP' . (TAMPIL_DETAIL ? ': ' . $e['message'] : '.'), $e['message'] . "\n" . $e['file'] . ':' . $e['line']);
});

/* ---------- Cadangan jika ekstensi mbstring tidak aktif ---------- */
if (!function_exists('mb_substr')) {
    function mb_substr($s, $start, $len = null) { return $len === null ? substr($s, $start) : substr($s, $start, $len); }
    function mb_strlen($s) { return strlen($s); }
    function mb_strtoupper($s) { return strtoupper($s); }
    function mb_strtolower($s) { return strtolower($s); }
    function mb_strimwidth($s, $start, $w, $trim = '') { return strlen($s) > $w ? substr($s, $start, $w - strlen($trim)) . $trim : $s; }
}
if (!function_exists('mb_convert_case')) {
    if (!defined('MB_CASE_TITLE')) define('MB_CASE_TITLE', 2);
    function mb_convert_case($s, $mode) { return ucwords(strtolower($s)); }
}

if (session_status() === PHP_SESSION_NONE && empty($NO_SESSION)) {
    ini_set('session.use_strict_mode', '1'); ini_set('session.use_only_cookies', '1');
    session_name('NCSESSID');
    if (PHP_VERSION_ID >= 70300) session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => pakai_https()]);
    else session_set_cookie_params(0, '/; samesite=Lax', '', pakai_https(), true);
    session_start();
}

/* ---------- Database ---------- */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . (defined('DB_PORT') ? DB_PORT : 3306) . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec("SET time_zone = '" . date('P') . "'");
            pasang_tabel_nc($pdo);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('[NurseCall] Koneksi database Khanza gagal: ' . $e->getMessage());
            die('<h3 style="font-family:sans-serif">Koneksi ke database SIMRS Khanza gagal. Periksa pengaturan di config.php</h3>'
                . (TAMPIL_DETAIL ? '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>' : ''));
        }
    }
    return $pdo;
}
/**
 * Pasang otomatis tabel tambahan Nurse Call di database Khanza jika belum ada.
 * Semua tabel tambahan berawalan nc_ dan berakhiran _wira (nc_user_wira, nc_setting_wira, dst.)
 * agar mudah dikenali & TIDAK PERNAH menyentuh tabel Khanza: hanya CREATE TABLE IF NOT EXISTS / INSERT IGNORE
 * ke tabel *_wira, tanpa foreign key / trigger / ALTER pada tabel Khanza.
 * Setelah berhasil dibuat file penanda data/.nc_terpasang agar pengecekan tidak diulang setiap halaman.
 */
const NC_TABEL = ['nc_user_wira', 'nc_gelang_wira', 'nc_keluhan_wira', 'nc_panggilan_wira', 'nc_setting_wira', 'nc_codeblue_wira'];
function pasang_tabel_nc(PDO $pdo): void {
    $tanda = data_dir() . '/.nc_terpasang';
    foreach (glob(__DIR__ . '/../uploads/.{nc_terpasang,nc_sapu,tv_*.json}', GLOB_BRACE) ?: [] as $lama) @unlink($lama); // file internal versi lama di folder publik
    $versi = DB_NAME . '|wira6';
    if (is_file($tanda) && trim((string)@file_get_contents($tanda)) === $versi) return;
    $ada = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'nc\\_%'")->fetchAll(PDO::FETCH_COLUMN);
    // Versi sebelumnya memakai nama tanpa _wira: ganti nama saja (data tetap) — hanya jika kolomnya persis milik Nurse Call.
    $ciri = ['nc_user' => 'last_login', 'nc_gelang' => 'dicetak', 'nc_keluhan' => 'urut', 'nc_panggilan' => 'waktu_respon', 'nc_setting' => 'v'];
    foreach ($ciri as $lama => $kolom) {
        $baru = $lama . '_wira';
        if (in_array($lama, $ada, true) && !in_array($baru, $ada, true)) {
            $cek = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
            $cek->execute([$lama, $kolom]);
            if ($cek->fetchColumn()) { $pdo->exec("RENAME TABLE `$lama` TO `$baru`"); $ada[] = $baru; }
        }
    }
    if (array_diff(NC_TABEL, $ada)) {
        $sql = @file_get_contents(__DIR__ . '/../database.sql');
        if (!$sql) throw new RuntimeException('Tabel Nurse Call (nc_..._wira) belum ada dan file database.sql tidak ditemukan. Import database.sql ke database ' . DB_NAME . ' lewat phpMyAdmin.');
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);          // baris komentar
        $sql = preg_replace('/\s--\s[^\n]*$/m', '', $sql);     // komentar di akhir baris
        foreach (preg_split('/;\s*(\n|$)/', $sql) as $st) {
            $st = trim($st);
            if ($st === '') continue;
            // Pengaman: hanya perintah ke tabel *_wira yang boleh dijalankan
            if (stripos($st, 'SET NAMES') !== 0 && !preg_match('/^(CREATE TABLE IF NOT EXISTS|INSERT IGNORE INTO|INSERT INTO)\s+nc_[a-z]+_wira\b/i', $st))
                throw new RuntimeException('database.sql berisi perintah yang tidak diizinkan: ' . mb_substr($st, 0, 80));
            try { $pdo->exec($st); }
            catch (PDOException $e) { throw new RuntimeException('Gagal membuat tabel Nurse Call otomatis (' . $e->getMessage() . '). Import database.sql ke database ' . DB_NAME . ' lewat phpMyAdmin, atau beri user database hak CREATE.'); }
        }
        error_log('[NurseCall] Tabel nc_*_wira dibuat otomatis di database ' . DB_NAME);
    }
    samakan_kolasi($pdo);
    // Role disederhanakan menjadi admin / user (versi lama: pendaftaran, perawat, karu -> user). Hanya mengubah nc_user_wira.
    $tipe = (string)$pdo->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nc_user_wira' AND COLUMN_NAME = 'role'")->fetchColumn();
    if ($tipe !== '' && $tipe !== "enum('admin','user')") {
        $pdo->exec("ALTER TABLE nc_user_wira MODIFY role ENUM('admin','user','pendaftaran','perawat','karu') NOT NULL DEFAULT 'user'");
        $pdo->exec("UPDATE nc_user_wira SET role = 'user' WHERE role <> 'admin'");
        $pdo->exec("ALTER TABLE nc_user_wira MODIFY role ENUM('admin','user') NOT NULL DEFAULT 'user'");
    }
    // Kolom nonaktif di nc_gelang_wira (gelang otomatis mati permanen saat pasien pulang)
    $ada = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nc_gelang_wira' AND COLUMN_NAME = 'nonaktif'")->fetchColumn();
    if (!$ada) $pdo->exec("ALTER TABLE nc_gelang_wira ADD COLUMN nonaktif DATETIME DEFAULT NULL");
    @file_put_contents($tanda, $versi);
}
/**
 * Samakan charset/collation kolom kunci tabel *_wira dengan kolom Khanza yang di-JOIN.
 * Mencegah error "Illegal mix of collations" bila Khanza memakai collation berbeda (mis. latin1_general_ci / utf8mb4).
 * Hanya mengubah tabel *_wira, tabel Khanza tidak disentuh.
 */
function samakan_kolasi(PDO $pdo): void {
    $peta = [
        ['nc_gelang_wira', 'no_rawat', 'reg_periksa', 'no_rawat'], ['nc_panggilan_wira', 'no_rawat', 'reg_periksa', 'no_rawat'],
        ['nc_panggilan_wira', 'no_rkm_medis', 'pasien', 'no_rkm_medis'], ['nc_panggilan_wira', 'kd_bangsal', 'bangsal', 'kd_bangsal'],
        ['nc_panggilan_wira', 'kd_kamar', 'kamar', 'kd_kamar'], ['nc_user_wira', 'kd_bangsal', 'bangsal', 'kd_bangsal'],
        ['nc_user_wira', 'id_user', 'petugas', 'nip'], ['nc_panggilan_wira', 'perawat', 'petugas', 'nip'],
        ['nc_panggilan_wira', 'selesai_oleh', 'petugas', 'nip'], ['nc_gelang_wira', 'dibuat_oleh', 'petugas', 'nip'],
        ['nc_codeblue_wira', 'kd_kamar', 'kamar', 'kd_kamar'], ['nc_codeblue_wira', 'kd_bangsal', 'bangsal', 'kd_bangsal'], ['nc_codeblue_wira', 'no_rawat', 'reg_periksa', 'no_rawat'],
    ];
    $info = $pdo->prepare('SELECT COLUMN_TYPE, IS_NULLABLE, CHARACTER_SET_NAME cs, COLLATION_NAME co FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    foreach ($peta as $m) {
        list($tNc, $cNc, $tKz, $cKz) = $m;
        $info->execute([$tKz, $cKz]); $kz = $info->fetch();
        $info->execute([$tNc, $cNc]); $nc = $info->fetch();
        if (!$kz || !$nc || !$kz['co'] || $kz['co'] === $nc['co']) continue;
        if (!preg_match('/^[a-z0-9_]+$/', $kz['cs'] . $kz['co'])) continue;
        try {
            $pdo->exec("ALTER TABLE `$tNc` MODIFY `$cNc` {$nc['COLUMN_TYPE']} CHARACTER SET {$kz['cs']} COLLATE {$kz['co']} " . ($nc['IS_NULLABLE'] === 'YES' ? 'NULL DEFAULT NULL' : 'NOT NULL'));
            error_log("[NurseCall] Collation $tNc.$cNc disamakan dengan $tKz.$cKz ({$kz['co']})");
        } catch (PDOException $e) { error_log('[NurseCall] Gagal menyamakan collation ' . $tNc . '.' . $cNc . ': ' . $e->getMessage()); }
    }
}
function q(string $sql, array $params = []): PDOStatement {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

/* ---------- Helper ---------- */
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function angka($n): string { return number_format((float)$n, 0, ',', '.'); }

function setting(string $k, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try { foreach (db()->query('SELECT k, v FROM nc_setting_wira') as $r) $cache[$r['k']] = $r['v']; }
        catch (PDOException $e) { @unlink(data_dir() . '/.nc_terpasang'); throw $e; }
        $rs = khanza_rs() ?: []; // identitas RS dari tabel setting Khanza jika tidak diisi di Pengaturan
        if (($cache['nama_rs'] ?? '') === '') $cache['nama_rs'] = $rs['nama_instansi'] ?? 'Rumah Sakit';
        if (($cache['alamat'] ?? '') === '') $cache['alamat'] = trim(($rs['alamat_instansi'] ?? '') . ', ' . ($rs['kabupaten'] ?? ''), ', ');
        if (($cache['telp'] ?? '') === '') $cache['telp'] = $rs['kontak'] ?? '';
    }
    return ($cache[$k] ?? '') !== '' ? $cache[$k] : $default;
}
function save_setting(string $k, string $v): void {
    q('INSERT INTO nc_setting_wira (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, $v]);
}

/** URL dasar aplikasi, mis. https://domain.com/nursecall/ */
function base_url(string $path = ''): string {
    $https = pakai_https();
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    static $dir = null;
    if ($dir === null) {
        // Folder aplikasi di URL, dihitung dari alamat skrip yang sedang dibuka (tetap benar walau folder berupa symlink / alias hosting)
        $root = str_replace('\\', '/', (string)realpath(__DIR__ . '/..'));
        $file = str_replace('\\', '/', (string)realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')));
        $sn   = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $rel  = ($root !== '' && $file !== '' && strpos($file, $root . '/') === 0) ? substr($file, strlen($root)) : '';
        if ($rel !== '' && $sn !== '' && substr($sn, -strlen($rel)) === $rel) $dir = substr($sn, 0, -strlen($rel));
        else { // cadangan: cara lama berdasarkan DOCUMENT_ROOT
            $doc = str_replace('\\', '/', (string)(realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: ''));
            $dir = ($doc !== '' && strpos($root, $doc) === 0) ? substr($root, strlen($doc)) : '';
        }
    }
    return ($https ? 'https' : 'http') . '://' . $host . rtrim($dir, '/') . '/' . ltrim($path, '/');
}
/** URL yang ditanam di QR Code gelang (bisa diatur di Pengaturan agar bisa dibuka dari HP pasien) */
function url_lapor(string $token): string {
    $pub = trim(setting('url_publik'));
    $base = $pub !== '' ? rtrim($pub, '/') . '/' : base_url();
    return $base . 'lapor.php?t=' . $token;
}

/** URL aset (css/js) + versi otomatis dari waktu file diubah: file yang ditimpa langsung terpakai, tanpa cache lama */
function aset(string $path): string {
    $f = __DIR__ . '/../' . ltrim($path, '/');
    return base_url($path) . '?v=' . (is_file($f) ? filemtime($f) : '0');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) { http_response_code(419); die('Sesi kedaluwarsa. Silakan muat ulang halaman.'); }
}
function flash(?string $msg = null, string $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function redirect(string $url): void { header('Location: ' . $url); exit; }
function json_out(array $d, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    $f = JSON_UNESCAPED_UNICODE | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0);
    $j = json_encode($d, $f);
    if ($j === false) { // data Khanza berisi karakter tidak valid (PHP lama): bersihkan lalu coba lagi
        array_walk_recursive($d, function (&$v) { if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8'); });
        $j = json_encode($d, $f | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
    while (ob_get_level()) ob_end_clean(); // buang output liar (spasi / peringatan) sebelum JSON
    echo $j;
    exit;
}

const BULAN = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const BLN   = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const HARI  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
function tgl_indo(?string $dt, bool $jam = false, bool $panjang = false): string {
    if (!$dt) return '-';
    $t = strtotime($dt); $b = $panjang ? BULAN : BLN;
    return date('j', $t) . ' ' . $b[(int)date('n', $t)] . ' ' . date('Y', $t) . ($jam ? ' ' . date('H:i', $t) : '');
}
function inisial(string $nama): string {
    $p = preg_split('/\s+/', trim(preg_replace(['/^(ns|dr|drg|apt)\.?\s+/i', '/[^\p{L}\s]/u'], ['', ' '], $nama)));
    return strtoupper(mb_substr($p[0] ?? '', 0, 1) . mb_substr($p[1] ?? '', 0, 1));
}
function valid_date(?string $d): bool { return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$d) && strtotime($d); }
function umur(?string $tgl): string {
    if (!$tgl || strpos($tgl, '0000-00-00') === 0) return '-';
    try { $d = (new DateTime($tgl))->diff(new DateTime()); } catch (Exception $e) { return '-'; }
    return $d->y > 0 ? $d->y . ' th' : $d->m . ' bln';
}
/** Durasi dalam detik -> "3 mnt 20 dtk" */
function durasi(?int $s): string {
    if ($s === null) return '-';
    if ($s < 60) return $s . ' dtk';
    if ($s < 3600) return floor($s / 60) . ' mnt' . ($s % 60 ? ' ' . ($s % 60) . ' dtk' : '');
    return floor($s / 3600) . ' jam ' . floor(($s % 3600) / 60) . ' mnt';
}
/** "MELATI · MLT.01A" */
function lokasi(array $r): string {
    return trim(($r['ruang'] ?? '') . (!empty($r['kamar']) ? ' · ' . $r['kamar'] : ''), ' ·');
}
/** Nama dari Khanza biasanya HURUF BESAR -> "Andi Pratama" (gelar dokter tetap) */
function nama_rapi(?string $n, bool $akronim = false): string {
    $n = trim((string)$n);
    if ($n === '' || $n !== mb_strtoupper($n)) return $n;
    $t = mb_convert_case(mb_strtolower($n), MB_CASE_TITLE);
    // Kata yang mengandung angka (kode kamar / lantai: LT3, 3A, MLT.02B) tetap seperti aslinya
    $asli = preg_split('/(\s+)/u', $n, -1, PREG_SPLIT_DELIM_CAPTURE); $baru = preg_split('/(\s+)/u', $t, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (count($asli) === count($baru)) { foreach ($asli as $i => $w) if (preg_match('/\d/', $w)) $baru[$i] = $w; $t = implode('', $baru); }
    // Nama ruang / poli: singkatan pendek tetap huruf besar (IGD, VIP, ICU, NICU, HCU, OK)
    if ($akronim) $t = preg_replace_callback('/\b(\p{L}{1,4})\b/u', function ($m) { return in_array(mb_strtoupper($m[1]), ['IGD', 'VIP', 'VVIP', 'ICU', 'ICCU', 'NICU', 'PICU', 'HCU', 'OK', 'IBS', 'PONEK', 'KIA', 'KB', 'THT', 'RS', 'UGD', 'IRNA', 'IRJ'], true) ? mb_strtoupper($m[1]) : $m[1]; }, $t);
    return $t;
}

/* ---------- Tema warna & tampilan ---------- */
function zona(): string {
    return ['+07:00' => 'WIB', '+08:00' => 'WITA', '+09:00' => 'WIT'][date('P')] ?? date('T');
}
const TEMA_PRESET = [
    '#3b7a67' => 'Hijau Alkes', '#0f6e8c' => 'Teal', '#1d5fbf' => 'Biru', '#2f3e9e' => 'Indigo',
    '#6d3fb5' => 'Ungu', '#b23a48' => 'Merah Marun', '#c2571a' => 'Oranye', '#8a6d1f' => 'Coklat Emas',
    '#0e7c66' => 'Toska', '#3d4757' => 'Abu Gelap',
];
function hex_rgb(string $h): array { $h = ltrim($h, '#'); return [hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2))]; }
function rgb_hex(array $c): string { return vsprintf('#%02x%02x%02x', array_map(function ($v) { return max(0, min(255, (int)round($v))); }, $c)); }
/** Campur warna $a dengan $b sebanyak $t (0..1) */
function campur(string $a, string $b, float $t): string {
    $x = hex_rgb($a); $y = hex_rgb($b);
    return rgb_hex([$x[0] + ($y[0] - $x[0]) * $t, $x[1] + ($y[1] - $x[1]) * $t, $x[2] + ($y[2] - $x[2]) * $t]);
}
function luminance(string $h): float {
    $f = function ($v) { $v /= 255; return $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4); };
    [$r, $g, $b] = hex_rgb($h);
    return 0.2126 * $f($r) + 0.7152 * $f($g) + 0.0722 * $f($b);
}
/** Warna utama yang dipakai: jika terlalu terang digelapkan otomatis agar teks putih tetap terbaca */
function warna_tema(): string {
    $c = strtolower(setting('tema_warna', '#3b7a67'));
    if (!preg_match('/^#[0-9a-f]{6}$/', $c)) $c = '#3b7a67';
    for ($i = 0; $i < 12 && luminance($c) > 0.2; $i++) $c = campur($c, '#000000', 0.12);
    return $c;
}
/** CSS variabel tema, dipasang di <head> setiap halaman */
function tema_css(): string {
    $p = warna_tema(); [$r, $g, $b] = hex_rgb($p);
    $v = [
        '--pri' => $p, '--pri-2' => campur($p, '#000000', 0.18), '--pri-3' => campur($p, '#ffffff', 0.22),
        '--pri-soft' => campur($p, '#ffffff', 0.88), '--pri-line' => campur($p, '#ffffff', 0.6), '--pri-rgb' => "$r $g $b",
        '--side' => campur($p, '#000000', 0.66), '--side-2' => campur($p, '#000000', 0.56),
    ];
    return ':root{' . implode(';', array_map(function ($k, $x) { return "$k:$x"; }, array_keys($v), $v)) . '}';
}
/** URL gambar latar halaman login ('' = gradasi warna tema) */
function login_bg(): string {
    $f = setting('login_bg');
    return ($f !== '' && preg_match('/^[\w.-]+$/', $f) && is_file(__DIR__ . '/../uploads/' . $f)) ? base_url('uploads/' . $f) : '';
}

/* ---------- Display TV ---------- */
/** Kunci akses layar TV (dibuat otomatis jika belum ada) */
function display_key(bool $baru = false): string {
    $k = setting('display_key');
    if ($baru || !preg_match('/^[a-f0-9]{16,64}$/', $k)) { $k = bin2hex(random_bytes(12)); save_setting('display_key', $k); }
    return $k;
}
function url_display(string $ruang = ''): string {
    return base_url('display.php?k=' . display_key() . ($ruang !== '' ? '&ruang=' . rawurlencode($ruang) : ''));
}
/** "Andi Pratama Putra" -> "Andi P. P." (untuk layar TV jika disamarkan) */
function samarkan(string $nama): string {
    $p = preg_split('/\s+/', trim($nama));
    return $p[0] . (count($p) > 1 ? ' ' . implode(' ', array_map(function ($x) { return mb_strtoupper(mb_substr($x, 0, 1)) . '.'; }, array_slice($p, 1))) : '');
}

/* ---------- Auth & role ---------- */
// Hanya 2 role: Admin (semua menu) & User (Dashboard / nurse station, Admisi, Rawat Inap, cetak gelang)
const ROLE_LABEL = ['admin' => 'Admin', 'user' => 'User'];

/** Hak akses per modul */
const AKSES = [
    'station'  => ['admin', 'user'],   // nurse station: terima notifikasi & alarm
    'tangani'  => ['admin', 'user'],   // ubah status panggilan
    'pasien'   => ['admin', 'user'],   // buat QR baru (gelang hilang)
    'lihat_pasien' => ['admin', 'user'], // admisi & rawat inap (dari Khanza)
    'gelang'   => ['admin', 'user'],   // cetak gelang QR
    'laporan'  => ['admin'],
    'master'   => ['admin'],           // pengguna, tampilan, pengaturan
    'codeblue' => ['admin', 'user'],   // aktifkan / selesaikan alarm Code Blue
];
function bisa(string $modul): bool { return in_array(role(), AKSES[$modul] ?? [], true); }
function require_akses(string $modul): void {
    require_login();
    if (!bisa($modul)) { http_response_code(403); die('<h3 style="font-family:sans-serif">Akses ditolak</h3><a href="' . base_url() . '">Kembali</a>'); }
}

function is_login(): bool { return !empty($_SESSION['uid']); }
function require_login(): void { if (!is_login()) redirect(base_url('login.php')); }
function user(): array {
    static $u = null;
    if ($u === null && is_login()) {
        $u = q('SELECT u.*, b.nm_bangsal ruang FROM nc_user_wira u LEFT JOIN bangsal b ON b.kd_bangsal = u.kd_bangsal WHERE u.id_user = ? AND u.aktif = 1', [$_SESSION['uid']])->fetch() ?: [];
        if ($u) $u['ruang'] = nama_rapi($u['ruang'], true);
        if (!$u) { unset($_SESSION['uid']); redirect(base_url('login.php')); }
    }
    return $u ?? [];
}
function role(): string { $r = user()['role'] ?? ''; return $r === '' ? '' : ($r === 'admin' ? 'admin' : 'user'); }
function has_role(string ...$roles): bool { return in_array(role(), $roles, true); }

/* ---------- Panggilan ---------- */
const STATUS_PG = ['baru' => 'Baru', 'diproses' => 'Diproses', 'selesai' => 'Selesai'];
const PRIORITAS = ['tinggi' => 'Tinggi', 'sedang' => 'Sedang', 'rendah' => 'Rendah'];
function badge_pg(string $s): string { return '<span class="badge st-' . e($s) . '">' . e(STATUS_PG[$s] ?? $s) . '</span>'; }
function badge_prio(string $p): string { return '<span class="badge pr-' . e($p) . '">' . e(PRIORITAS[$p] ?? $p) . '</span>'; }

/* ---------- Query data Khanza ---------- */
// Panggilan + nama pasien & ruang (bangsal) dari Khanza. Kolom "perawat" = nama perawat, "id_perawat" = NIK-nya.
const SQL_PG = "SELECT p.*, p.perawat id_perawat, ps.nm_pasien pasien, p.no_rkm_medis no_rm, p.kd_kamar kamar, ps.jk, ps.tgl_lahir,
    b.nm_bangsal ruang, COALESCE(u.nama, p.perawat) perawat, COALESCE(us.nama, p.selesai_oleh) penyelesai,
    TIMESTAMPDIFF(SECOND, p.waktu, COALESCE(p.waktu_respon, NOW())) detik_respon,
    TIMESTAMPDIFF(SECOND, p.waktu, p.waktu_selesai) detik_selesai
    FROM nc_panggilan_wira p LEFT JOIN pasien ps ON ps.no_rkm_medis = p.no_rkm_medis LEFT JOIN bangsal b ON b.kd_bangsal = p.kd_bangsal
    LEFT JOIN nc_user_wira u ON u.id_user = p.perawat LEFT JOIN nc_user_wira us ON us.id_user = p.selesai_oleh";

// Pasien rawat inap AKTIF (kamar_inap.stts_pulang = '-') lengkap dengan kamar, bangsal, DPJP & token gelang
const SQL_RANAP = "SELECT ki.no_rawat, rp.no_rkm_medis no_rm, ps.nm_pasien nama, ps.jk, ps.tgl_lahir,
    ki.kd_kamar kamar, k.kelas, k.kd_bangsal, b.nm_bangsal ruang, CONCAT(ki.tgl_masuk, ' ', ki.jam_masuk) tgl_masuk,
    ki.diagnosa_awal diagnosa, d.nm_dokter dokter, g.token, g.dicetak
    FROM kamar_inap ki
    JOIN reg_periksa rp ON rp.no_rawat = ki.no_rawat
    JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
    JOIN kamar k ON k.kd_kamar = ki.kd_kamar
    JOIN bangsal b ON b.kd_bangsal = k.kd_bangsal
    LEFT JOIN dokter d ON d.kd_dokter = COALESCE((SELECT dp.kd_dokter FROM dpjp_ranap dp WHERE dp.no_rawat = ki.no_rawat LIMIT 1), rp.kd_dokter)
    LEFT JOIN nc_gelang_wira g ON g.no_rawat = ki.no_rawat
    WHERE ki.stts_pulang = '-'";

/** Rapikan baris pasien dari Khanza (nama HURUF BESAR -> Huruf Awal Besar) */
function rapikan_pasien(array $r): array {
    foreach (['nama', 'pasien'] as $k) if (isset($r[$k])) $r[$k] = nama_rapi($r[$k]);
    foreach (['ruang', 'poli'] as $k) if (isset($r[$k])) $r[$k] = nama_rapi($r[$k], true);
    return $r;
}
/**
 * Matikan gelang pasien yang sudah PULANG (semua baris kamar_inap-nya bukan '-') atau registrasinya BATAL di Khanza.
 * Permanen: QR lama tetap ditolak walau status di Khanza diubah kembali (buat QR baru lewat Cetak Gelang / Reset QR).
 * Panggilan yang masih terbuka dari pasien tersebut ikut ditutup otomatis.
 * $nr = satu no_rawat (langsung), atau null = semua gelang (dijalankan paling sering 1x per menit).
 */
function nonaktifkan_gelang_pulang(?string $nr = null): void {
    if ($nr === null) {
        $tanda = data_dir() . '/.nc_sapu';
        if (is_file($tanda) && filemtime($tanda) > time() - 60) return;
        @touch($tanda);
    }
    $w = $nr === null ? '' : ' AND g.no_rawat = ?'; $par = $nr === null ? [] : [$nr];
    try {
        q("UPDATE nc_gelang_wira g SET g.nonaktif = NOW()
           WHERE g.nonaktif IS NULL$w AND (
                 (EXISTS (SELECT 1 FROM kamar_inap k WHERE k.no_rawat = g.no_rawat)
                  AND NOT EXISTS (SELECT 1 FROM kamar_inap k WHERE k.no_rawat = g.no_rawat AND k.stts_pulang = '-'))
              OR EXISTS (SELECT 1 FROM reg_periksa r WHERE r.no_rawat = g.no_rawat AND r.stts = 'Batal'))", $par);
        q("UPDATE nc_panggilan_wira p JOIN nc_gelang_wira g ON g.no_rawat = p.no_rawat
           SET p.status = 'selesai', p.waktu_selesai = NOW(), p.tindakan = COALESCE(p.tindakan, 'Ditutup otomatis: pasien sudah pulang')
           WHERE p.status <> 'selesai' AND g.nonaktif IS NOT NULL$w", $par);
    } catch (PDOException $e) { error_log('[NurseCall] nonaktifkan_gelang_pulang: ' . $e->getMessage()); }
}
/** 1 pasien rawat inap aktif berdasarkan no_rawat, null jika sudah pulang / tidak ada */
function ranap(string $noRawat): ?array {
    $r = q(SQL_RANAP . ' AND ki.no_rawat = ? LIMIT 1', [$noRawat])->fetch();
    return $r ? rapikan_pasien($r) : null;
}
/** Alergi terakhir yang dicatat di pemeriksaan_ranap Khanza: [no_rawat => 'Amoxicillin'] */
function alergi_map(array $noRawat): array {
    $noRawat = array_values(array_unique(array_filter($noRawat)));
    if (!$noRawat) return [];
    $out = [];
    try {
        $st = q('SELECT no_rawat, alergi FROM pemeriksaan_ranap WHERE no_rawat IN (' . implode(',', array_fill(0, count($noRawat), '?')) . ")
                 AND alergi IS NOT NULL AND TRIM(alergi) NOT IN ('', '-', 'Tidak Ada', 'TIDAK ADA', 'tidak ada') ORDER BY tgl_perawatan, jam_rawat", $noRawat);
        foreach ($st as $r) $out[$r['no_rawat']] = $r['alergi']; // yang terakhir menimpa
    } catch (PDOException $e) { /* tabel tidak ada di versi Khanza ini */ }
    return $out;
}
/**
 * Data untuk gelang: semua registrasi (reg_periksa) — admisi, IGD, maupun rawat inap.
 * Jika pasien sedang rawat inap, ruang/kamar/DPJP diambil dari kamar_inap aktif.
 */
function data_gelang(string $nr): ?array {
    if ($nr === '') return null;
    $r = q("SELECT rp.no_rawat, rp.no_rkm_medis no_rm, ps.nm_pasien nama, ps.jk, ps.tgl_lahir, CONCAT(rp.tgl_registrasi, ' ', rp.jam_reg) tgl_masuk,
              rp.stts, rp.status_lanjut, pol.nm_poli poli, d.nm_dokter dokter, g.token, g.nonaktif
            FROM reg_periksa rp JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
            LEFT JOIN poliklinik pol ON pol.kd_poli = rp.kd_poli LEFT JOIN dokter d ON d.kd_dokter = rp.kd_dokter
            LEFT JOIN nc_gelang_wira g ON g.no_rawat = rp.no_rawat
            WHERE rp.no_rawat = ? LIMIT 1", [$nr])->fetch();
    if (!$r) return null;
    $r = rapikan_pasien($r);
    $r['poli'] = nama_rapi($r['poli'], true);
    $rn = ranap($nr);
    $r['ranap'] = (bool)$rn;
    $r['kamar'] = ''; $r['ruang'] = '';
    if ($rn) foreach (['ruang', 'kamar', 'kd_bangsal', 'tgl_masuk'] as $k) $r[$k] = $rn[$k];
    if ($rn && $rn['dokter']) $r['dokter'] = $rn['dokter'];
    $r['pulang'] = !$rn && (bool)q('SELECT 1 FROM kamar_inap WHERE no_rawat = ? LIMIT 1', [$nr])->fetchColumn();
    $r['batal'] = $r['stts'] === 'Batal';
    $r['alergi'] = alergi_map([$nr])[$nr] ?? '';
    return $r;
}

/** Bangsal Khanza yang punya kamar aktif (dipakai sebagai "ruang" di Nurse Call) */
function daftar_bangsal(): array {
    static $l = null;
    if ($l === null) {
        $l = q("SELECT b.kd_bangsal, b.nm_bangsal nama,
                  (SELECT COUNT(*) FROM kamar_inap ki JOIN kamar k2 ON k2.kd_kamar = ki.kd_kamar WHERE ki.stts_pulang = '-' AND k2.kd_bangsal = b.kd_bangsal) n
                FROM bangsal b WHERE b.status = '1' AND EXISTS (SELECT 1 FROM kamar k WHERE k.kd_bangsal = b.kd_bangsal AND k.statusdata = '1')
                ORDER BY b.nm_bangsal")->fetchAll();
        foreach ($l as &$b) $b['nama'] = nama_rapi($b['nama'], true);
    }
    return $l;
}
function nama_bangsal(string $kd): string {
    foreach (daftar_bangsal() as $b) if ($b['kd_bangsal'] === $kd) return $b['nama'];
    return $kd;
}

/** Ruang (kd_bangsal) yang sedang dipantau di nurse station, disimpan per sesi. '' = semua ruang */
function ruang_pantau(): string {
    if (isset($_GET['ruang']) && is_login()) $_SESSION['ruang_pantau'] = substr(preg_replace('/[^\w.-]/', '', (string)$_GET['ruang']), 0, 5);
    if (!isset($_SESSION['ruang_pantau'])) $_SESSION['ruang_pantau'] = (string)(user()['kd_bangsal'] ?? '');
    return (string)$_SESSION['ruang_pantau'];
}

/* ---------- Batas percobaan login per alamat IP (tidak bisa diakali dengan menghapus cookie) ---------- */
const LOGIN_MAKS_IP = 10;   // gagal maksimal per IP
const LOGIN_JEDA = 900;     // dalam 15 menit
function login_gagal_ip(bool $tambah = false): int {
    $f = data_dir() . '/login_' . md5((string)($_SERVER['REMOTE_ADDR'] ?? '')) . '.json';
    $t = is_file($f) ? (json_decode((string)@file_get_contents($f), true) ?: []) : [];
    $t = array_values(array_filter($t, function ($x) { return $x > time() - LOGIN_JEDA; }));
    if ($tambah) { $t[] = time(); @file_put_contents($f, json_encode($t), LOCK_EX); }
    return count($t);
}
function login_reset_ip(): void { @unlink(data_dir() . '/login_' . md5((string)($_SERVER['REMOTE_ADDR'] ?? '')) . '.json'); }

/* ---------- Login Khanza ---------- */
/** Cek NIK & password ke tabel `user` Khanza (AES), sama seperti Khanza. Semua user Khanza boleh masuk. */
function khanza_login(string $idUser, string $password): bool {
    if ($idUser === '' || $password === '') return false;
    return (bool)q('SELECT 1 FROM user WHERE id_user = AES_ENCRYPT(?, ?) AND password = AES_ENCRYPT(?, ?) LIMIT 1',
                   [$idUser, KHANZA_KEY_USER, $password, KHANZA_KEY_PASS])->fetchColumn();
}
function khanza_user_ada(string $idUser): bool {
    return (bool)q('SELECT 1 FROM user WHERE id_user = AES_ENCRYPT(?, ?) LIMIT 1', [$idUser, KHANZA_KEY_USER])->fetchColumn();
}
/** Nama pegawai dari tabel petugas, lalu dokter (dokter login memakai kd_dokter) */
function khanza_pegawai(string $idUser): ?array {
    if ($n = q('SELECT nama FROM petugas WHERE nip = ? LIMIT 1', [$idUser])->fetchColumn()) return ['nama' => $n, 'jenis' => 'petugas'];
    if ($n = q('SELECT nm_dokter FROM dokter WHERE kd_dokter = ? LIMIT 1', [$idUser])->fetchColumn()) return ['nama' => $n, 'jenis' => 'dokter'];
    return null;
}
/** Identitas RS dari tabel setting Khanza */
function khanza_rs(): ?array {
    static $rs = false;
    if ($rs === false) {
        try { $rs = db()->query('SELECT nama_instansi, alamat_instansi, kabupaten, propinsi, kontak, email FROM setting LIMIT 1')->fetch() ?: null; }
        catch (PDOException $e) { $rs = null; }
    }
    return $rs;
}
function is_admin_nik(string $id): bool { return in_array($id, (array)(defined('NC_ADMIN_NIK') ? NC_ADMIN_NIK : []), true); }

require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/fungsi_panggilan.php';
// Pastikan file inti satu versi (mencegah campuran file lama & baru setelah update)
foreach (['NC_V_LAYOUT' => 'inc/layout.php', 'NC_V_PANGGILAN' => 'inc/fungsi_panggilan.php'] as $c => $f)
    if (!defined($c) || constant($c) !== 'wira-2')
        throw new RuntimeException('File ' . $f . ' masih versi lama. Timpa dengan file terbaru, lalu buka cek.php.');