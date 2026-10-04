<?php
/**
 * Halaman PASIEN (publik, tanpa login) — dibuka dengan scan QR Code di gelang.
 * ?t=TOKEN          : form keluhan / permintaan
 * ?t=TOKEN&id=ID    : status laporan (diperbarui otomatis)
 */
require __DIR__ . '/inc/bootstrap.php';
header('X-Robots-Tag: noindex');
$t = preg_replace('/[^a-f0-9]/', '', $_GET['t'] ?? '');
// Token gelang -> no_rawat -> data rawat inap aktif di Khanza (kamar terbaru, termasuk jika pindah kamar)
$g = strlen($t) === 24 ? q('SELECT no_rawat FROM nc_gelang_wira WHERE token = ?', [$t])->fetch() : null;
if ($g) { // cek status terbaru di Khanza: pulang / batal -> gelang dimatikan permanen
    nonaktifkan_gelang_pulang($g['no_rawat']);
    $g = q('SELECT no_rawat, nonaktif FROM nc_gelang_wira WHERE token = ?', [$t])->fetch();
}
$p = $g && !$g['nonaktif'] ? ranap($g['no_rawat']) : null;
$aktif = (bool)$p;
if ($g && !$p) { // belum masuk kamar, atau sudah pulang (gelang nonaktif)
    $p = q("SELECT ps.nm_pasien nama, MAX(CONCAT(ki.tgl_keluar, ' ', ki.jam_keluar)) tgl_pulang FROM reg_periksa rp JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
            LEFT JOIN kamar_inap ki ON ki.no_rawat = rp.no_rawat WHERE rp.no_rawat = ? GROUP BY ps.nm_pasien", [$g['no_rawat']])->fetch() ?: ['nama' => 'Pasien', 'tgl_pulang' => null];
    $p = rapikan_pasien($p) + ['no_rawat' => $g['no_rawat'], 'nonaktif' => $g['nonaktif']];
    $p['nama'] = samarkan($p['nama']); // halaman publik: nama disamarkan setelah gelang tidak aktif
}
/** Ikon garis satu warna (bukan emoji berwarna) untuk tiap jenis keluhan, dipilih dari kata kuncinya */
function ikon_keluhan(string $nama, int $s = 26): string {
    $n = mb_strtolower($nama);
    $peta = [
        'nyeri|sakit'        => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
        'sesak|napas|nafas'  => '<path d="M9.6 4.6A2 2 0 1 1 11 8H2"/><path d="M12.6 19.4A2 2 0 1 0 14 16H2"/><path d="M17.7 7.7A2.5 2.5 0 1 1 19.5 12H2"/>',
        'infus|cairan'       => '<path d="M12 2.7l5.7 5.7a8 8 0 1 1-11.3 0z"/>',
        'mual|muntah'        => '<circle cx="12" cy="12" r="9.5"/><path d="M8.5 16c1-1 2.2-1.5 3.5-1.5s2.5.5 3.5 1.5"/><path d="M9 9.5h.01M15 9.5h.01"/>',
        'pusing'             => '<path d="M21 12a9 9 0 1 1-2.6-6.4"/><path d="M21 4v5h-5"/><circle cx="12" cy="12" r="3"/>',
        'toilet|bab|bak'     => '<circle cx="12" cy="4.5" r="2"/><path d="M12 7v7"/><path d="M8 10h8"/><path d="M9 21l3-7 3 7"/>',
        'minum|makan'        => '<path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v8a5 5 0 0 1-5 5H8a5 5 0 0 1-5-5z"/><path d="M7 2v3M11 2v3"/>',
        'obat'               => '<rect x="3" y="8" width="18" height="8" rx="4"/><path d="M12 8v8"/>',
        'demam|panas'        => '<path d="M14 14.8V4a2 2 0 1 0-4 0v10.8a4 4 0 1 0 4 0z"/>',
        'selimut|sprei|bersih' => '<path d="M3 7h18v10H3z"/><path d="M3 11h18"/>',
        'lain|pesan'         => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    ];
    $path = '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>'; // bawaan: lonceng
    foreach ($peta as $kunci => $d) if (preg_match('/' . $kunci . '/u', $n)) { $path = $d; break; }
    return '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}
/** Simpan panggilan baru dengan ruang & kamar pasien saat ini */
function simpan_panggilan(array $p, array $d): int {
    q('INSERT INTO nc_panggilan_wira (no_rawat, no_rkm_medis, kd_bangsal, kd_kamar, keluhan_id, keluhan, prioritas, pesan, audio, durasi_audio, pelapor, status, waktu, ip)
       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
      [$p['no_rawat'], $p['no_rm'], $p['kd_bangsal'], $p['kamar'], $d['keluhan_id'] ?? null, $d['keluhan'], $d['prioritas'] ?? 'sedang', $d['pesan'] ?? null,
       $d['audio'] ?? null, $d['durasi'] ?? null, ($_POST['pelapor'] ?? '') === 'keluarga' ? 'keluarga' : 'pasien',
       'baru', date('Y-m-d H:i:s'), substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45)]);
    return (int)db()->lastInsertId();
}
$err = null;

/* ---------- Pesan suara (dikirim lewat fetch dari modal rekam) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'suara') {
    $jawab = function (bool $ok, string $msg, array $x = []) { json_out(['ok' => $ok, 'msg' => $msg] + $x, $ok ? 200 : 422); };
    if (!$aktif) $jawab(false, 'Gelang sudah tidak aktif.');
    if (setting('suara_aktif', '1') !== '1') $jawab(false, 'Fitur pesan suara sedang dinonaktifkan.');
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) $jawab(false, 'Halaman kedaluwarsa. Muat ulang lalu coba lagi.');
    $jeda = (int)setting('jeda_lapor', '60');
    $last = q('SELECT TIMESTAMPDIFF(SECOND, MAX(waktu), NOW()) FROM nc_panggilan_wira WHERE no_rawat = ?', [$p['no_rawat']])->fetchColumn();
    if ($last !== null && (int)$last < $jeda) $jawab(false, 'Tunggu ' . ($jeda - (int)$last) . ' detik lagi. Perawat sudah menerima laporan Anda sebelumnya.');
    $f = $_FILES['audio'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK) $jawab(false, $f && in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 'Rekaman terlalu besar.' : 'Rekaman tidak terkirim. Coba lagi.');
    if ($f['size'] < 800) $jawab(false, 'Rekaman terlalu pendek / kosong. Silakan rekam ulang.');
    if ($f['size'] > 4 * 1024 * 1024) $jawab(false, 'Rekaman terlalu besar (maks. 4 MB).');
    $ext = jenis_audio($f['tmp_name']);
    if (!$ext) $jawab(false, 'Format rekaman tidak dikenali.');
    $dir = __DIR__ . '/uploads/suara';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $nama = date('Ymd-His') . '-' . preg_replace('/\W/', '', $p['no_rm']) . '-' . bin2hex(random_bytes(5)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], "$dir/$nama")) $jawab(false, 'Gagal menyimpan rekaman di server.');
    $durasi = max(0, min((int)setting('suara_maks', '60') + 5, (int)($_POST['durasi'] ?? 0))) ?: null;
    $id = simpan_panggilan($p, ['keluhan' => 'Pesan Suara', 'prioritas' => 'sedang', 'audio' => $nama, 'durasi' => $durasi]);
    $jawab(true, 'Pesan suara terkirim.', ['url' => base_url('lapor.php?t=' . $t . '&id=' . $id)]);
}

if ($aktif && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $kid = (int)($_POST['keluhan'] ?? 0);
    $k = q('SELECT * FROM nc_keluhan_wira WHERE id = ? AND aktif = 1', [$kid])->fetch();
    $pesan = mb_substr(trim(preg_replace('/\s+/', ' ', $_POST['pesan'] ?? '')), 0, 255);
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) $err = 'Halaman kedaluwarsa. Silakan kirim ulang.';
    elseif (!$k) $err = 'Pilih jenis keluhan / permintaan terlebih dahulu.';
    elseif (stripos($k['nama'], 'lain') !== false && $pesan === '') $err = 'Untuk "' . $k['nama'] . '", mohon tuliskan keterangannya.';
    else {
        // Laporan sama yang masih menunggu -> tidak perlu kirim ulang
        $dup = q("SELECT id FROM nc_panggilan_wira WHERE no_rawat = ? AND keluhan_id = ? AND status <> 'selesai' ORDER BY id DESC LIMIT 1", [$p['no_rawat'], $kid])->fetchColumn();
        if ($dup) redirect(base_url('lapor.php?t=' . $t . '&id=' . $dup . '&dup=1'));
        $jeda = (int)setting('jeda_lapor', '60');
        $last = q('SELECT TIMESTAMPDIFF(SECOND, MAX(waktu), NOW()) FROM nc_panggilan_wira WHERE no_rawat = ?', [$p['no_rawat']])->fetchColumn();
        if ($last !== null && (int)$last < $jeda) $err = 'Laporan terlalu cepat. Tunggu ' . ($jeda - (int)$last) . ' detik lagi, perawat sudah menerima laporan Anda sebelumnya.';
        else {
            $id = simpan_panggilan($p, ['keluhan_id' => $k['id'], 'keluhan' => $k['nama'], 'prioritas' => $k['prioritas'], 'pesan' => $pesan ?: null]);
            redirect(base_url('lapor.php?t=' . $t . '&id=' . $id));
        }
    }
}

$call = null;
if ($aktif && isset($_GET['id'])) {
    $call = q("SELECT g.*, COALESCE(u.nama, g.perawat) perawat FROM nc_panggilan_wira g LEFT JOIN nc_user_wira u ON u.id_user = g.perawat WHERE g.id = ? AND g.no_rawat = ?", [(int)$_GET['id'], $p['no_rawat']])->fetch();
}
$terbuka = $aktif && !$call ? q("SELECT id, keluhan, status FROM nc_panggilan_wira WHERE no_rawat = ? AND status <> 'selesai' ORDER BY id DESC LIMIT 1", [$p['no_rawat']])->fetch() : null;
$suaraOn = setting('suara_aktif', '1') === '1';
$maksSuara = max(10, min(180, (int)setting('suara_maks', '60')));
$keluhan = q('SELECT * FROM nc_keluhan_wira WHERE aktif = 1 ORDER BY urut, nama')->fetchAll();

$title = 'Panggil Perawat - ' . setting('nama_rs');
$bodyClass = 'lapor'; $noInstall = true;
require __DIR__ . '/inc/head.php';
?>
<style>
/* ===== Halaman pasien (scan QR) — gaya aplikasi Android, ikon satu warna ===== */
body.lapor{background:#f4f6f8;-webkit-font-smoothing:antialiased}
.lp{max-width:520px;margin:0 auto;padding:0 0 calc(18px + env(safe-area-inset-bottom));min-height:100vh;min-height:100dvh;display:flex;flex-direction:column}
.lp>*{margin-left:16px;margin-right:16px}
/* App bar */
.lp>.ax-bar{margin:0;position:sticky;top:0;z-index:20;display:flex;align-items:center;gap:12px;padding:calc(12px + env(safe-area-inset-top)) 16px 12px;background:var(--pri);color:#fff;box-shadow:0 2px 6px rgba(0,0,0,.12)}
.ax-logo{width:38px;height:38px;border-radius:12px;background:#fff;display:grid;place-items:center;flex:none}
.ax-title{min-width:0}.ax-title b{display:block;font-size:17px;font-weight:600;letter-spacing:.01em}
.ax-title span{display:block;font-size:12.5px;opacity:.85;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
/* Kartu pasien */
.lx-pas{margin-top:16px;display:flex;align-items:center;gap:14px;background:#fff;border-radius:16px;padding:14px 16px;box-shadow:0 1px 3px rgba(16,24,40,.08)}
.lx-av{flex:none;width:48px;height:48px;border-radius:50%;background:var(--pri-soft);color:var(--pri-2);display:grid;place-items:center;font-weight:700;font-size:16px}
.lx-pas-id{flex:1;min-width:0}.lx-pas-id b{display:block;font-size:16px;font-weight:600;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.lx-pas-id>span:not(.lx-ruang){display:block;font-size:13px;color:var(--muted)}
.lx-pas-id .lx-ruang{display:flex;width:max-content;align-items:center;gap:5px;margin-top:4px;font-size:12.5px;font-weight:500;color:var(--pri-2);background:var(--pri-soft);border-radius:8px;padding:3px 9px;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
/* Judul bagian */
.lx-head{margin:22px 4px 10px}
.lx-head h2{margin:0;font-size:16px;font-weight:600;color:#1f2937}
.lx-head p{margin:2px 0 0;font-size:13px;color:var(--muted)}
/* Grid keluhan: 3 kolom, ikon garis satu warna */
.lx-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.lx-kel{display:flex;flex-direction:column;align-items:center;gap:8px;min-height:102px;padding:14px 6px 12px;border:0;border-radius:16px;background:#fff;font:inherit;color:#374151;cursor:pointer;box-shadow:0 1px 3px rgba(16,24,40,.08);transition:transform .12s,box-shadow .15s,background .15s;-webkit-tap-highlight-color:transparent;position:relative;overflow:hidden}
.lx-kel:active{transform:scale(.97);background:#f9fafb}
.lx-kel.on{box-shadow:0 0 0 2px var(--pri) inset,0 1px 3px rgba(16,24,40,.08)}
.lx-ic{display:grid;place-items:center;width:48px;height:48px;border-radius:50%;background:var(--pri-soft);color:var(--pri-2)}
.lx-nm{font-size:12.5px;font-weight:500;line-height:1.25;text-align:center;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;word-break:break-word}
/* Baris pesan suara (list item) */
.lx-voice{margin-top:12px;width:100%;display:flex;align-items:center;gap:14px;background:#fff;border:0;border-radius:16px;padding:14px 16px;font:inherit;color:#1f2937;text-align:left;cursor:pointer;box-shadow:0 1px 3px rgba(16,24,40,.08)}
.lx-voice:active{background:#f9fafb}
.lx-voice-ic{flex:none;width:44px;height:44px;border-radius:50%;display:grid;place-items:center;background:var(--pri);color:#fff}
.lx-voice-t{flex:1;min-width:0}.lx-voice-t b{display:block;font-size:15px;font-weight:600}.lx-voice-t small{font-size:12.5px;color:var(--muted)}
.lx-voice-go{color:#9ca3af}
/* Catatan darurat */
.lx-sos{display:flex;gap:10px;align-items:flex-start;margin-top:14px;padding:12px 14px;border-radius:14px;background:#fff;box-shadow:0 1px 3px rgba(16,24,40,.06);font-size:12.5px;color:#4b5563;line-height:1.5}
.lx-sos svg{flex:none;color:#9ca3af;margin-top:1px}
/* Keadaan: QR tidak dikenal / belum aktif / tidak aktif */
.ax-state{margin-top:24px;background:#fff;border-radius:18px;padding:28px 22px;text-align:center;box-shadow:0 1px 3px rgba(16,24,40,.08)}
.ax-state h2{margin:14px 0 6px;font-size:18px;font-weight:600}
.ax-state p{margin:0 0 6px;font-size:14px;color:var(--muted);line-height:1.55}
.ax-state-ic{display:inline-grid;place-items:center;width:64px;height:64px;border-radius:50%;background:var(--pri-soft);color:var(--pri-2)}
.ax-state-ic.warn{background:#fdf3e1;color:#b7791f}.ax-state-ic.mute{background:#eef0f3;color:#6b7280}
/* Alert di halaman */
.lp>.alert{margin-top:14px;border-radius:14px}
/* Halaman status laporan */
.lp>#statusCard{margin-top:16px;border-radius:18px;box-shadow:0 1px 3px rgba(16,24,40,.08)}
.lp .big-ok .ic{background:var(--pri-soft);color:var(--pri-2)}
.lp>.btn.light.block{border-radius:14px;border:0;box-shadow:0 1px 3px rgba(16,24,40,.08);font-weight:600}
.lp>.btn.block{width:auto;display:flex;margin-top:12px}
.lp #statusCard .track{margin-top:18px}
.lp>p.center{margin-left:16px;margin-right:16px}
/* Footer */
.lp>.ax-foot{margin-top:auto;padding-top:26px;text-align:center;font-size:11.5px;line-height:1.6;color:#9ca3af}
.ax-foot b{color:#6b7280;font-weight:600}
/* Dialog konfirmasi (gaya dialog Android) */
.lx-modal{position:fixed;inset:0;z-index:120;background:rgba(17,24,39,.45);display:grid;place-items:center;padding:20px}
.lx-modal[hidden]{display:none}
.lx-box{position:relative;width:100%;max-width:380px;background:#fff;border-radius:24px;padding:22px 20px 18px;box-shadow:0 20px 50px rgba(0,0,0,.25);animation:pop .2s ease-out}
.lx-x{position:absolute;top:12px;right:12px;width:36px;height:36px;border:0;border-radius:50%;background:transparent;color:#6b7280;font-size:24px;line-height:1;cursor:pointer}
.lx-x:active{background:#f3f4f6}
.lx-sel{display:flex;align-items:center;gap:14px;padding-right:36px;margin-bottom:12px}
.lx-sel-ic{flex:none;width:52px;height:52px;border-radius:50%;background:var(--pri-soft);color:var(--pri-2);display:grid;place-items:center}
.lx-sel small{display:block;font-size:12px;color:var(--muted)}.lx-sel b{display:block;font-size:17px;font-weight:600;line-height:1.3}
.lx-err{display:flex;gap:8px;align-items:flex-start;background:#fdecea;color:#b42318;border-radius:12px;padding:10px 12px;font-size:13px;line-height:1.45;margin-bottom:4px}.lx-err svg{flex:none;margin-top:1px}
.lx-lbl{display:block;margin:14px 0 6px;font-size:13px;font-weight:600;color:#374151}
.lx-lbl span{font-weight:400;color:var(--muted)}
.lx-box textarea{min-height:76px;resize:none;font-size:15px;border-radius:12px;background:#f9fafb;border:1.5px solid #e5e7eb}
.lx-box textarea:focus{background:#fff;border-color:var(--pri)}
.lx-box textarea.err{border-color:#d92d20}
.lx-seg{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.lx-seg label{margin:0;cursor:pointer}
.lx-seg input{position:absolute;opacity:0;pointer-events:none}
.lx-seg span{display:flex;align-items:center;justify-content:center;gap:7px;padding:11px 8px;border:1.5px solid #e5e7eb;border-radius:12px;font-weight:500;font-size:14.5px;background:#fff;color:#4b5563}
.lx-seg input:checked+span{border-color:var(--pri);background:var(--pri-soft);color:var(--pri-2);font-weight:600}
.lx-act{display:grid;grid-template-columns:auto 1fr;gap:10px;margin-top:20px}
.lx-btn2{border:0;background:transparent;border-radius:12px;padding:0 16px;font:inherit;font-weight:600;cursor:pointer;color:var(--pri-2)}
.lx-btn2:active{background:var(--pri-soft)}
.lx-send{display:flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:99px;padding:14px;font:inherit;font-size:15.5px;font-weight:600;color:#fff;cursor:pointer;background:var(--pri);box-shadow:0 2px 6px rgb(var(--pri-rgb) / .35)}
.lx-send:active{background:var(--pri-2)}
.lx-send:disabled{opacity:.7}
@media(max-width:340px){.lx-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>

<div class="lp">
  <header class="ax-bar">
    <span class="ax-logo"><?= logo_svg(28) ?></span>
    <div class="ax-title"><b>Panggil Perawat</b><span><?= e(setting('nama_rs')) ?></span></div>
  </header>

<?php if (!$p): ?>
  <div class="ax-state"><span class="ax-state-ic warn"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M9 9l6 6M15 9l-6 6"/></svg></span><h2>QR Code tidak dikenali</h2>
    <p class="muted">Pastikan Anda memindai QR Code pada gelang pasien. Jika masih gagal, silakan panggil perawat langsung atau tekan bel di samping tempat tidur.</p></div>

<?php elseif (!$aktif && empty($p['nonaktif'])): ?>
  <div class="ax-state"><span class="ax-state-ic"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9.5"/><path d="M12 7v5l3 2"/></svg></span><h2>Gelang belum aktif</h2>
    <p class="muted">Gelang <?= e($p['nama']) ?> otomatis aktif setelah pasien masuk kamar rawat inap. Sementara itu, silakan hubungi petugas / perawat secara langsung.</p></div>
<?php elseif (!$aktif): ?>
  <div class="ax-state"><span class="ax-state-ic mute"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 1 1 8 0v4"/></svg></span><h2>Gelang sudah tidak aktif</h2>
    <p class="muted">Pasien <?= e($p['nama']) ?> sudah tidak dirawat, sehingga QR Code pada gelang ini tidak dapat digunakan lagi untuk memanggil perawat.</p>
    <p class="small muted" style="margin-bottom:0">Jika Anda sedang dirawat, minta petugas mencetakkan gelang baru. Semoga lekas sehat!</p></div>

<?php else: ?>
  <div class="lx-pas">
    <span class="lx-av"><?= e(inisial($p['nama'])) ?></span>
    <div class="lx-pas-id"><b><?= e($p['nama']) ?></b><span>No. RM <?= e($p['no_rm']) ?></span>
      <span class="lx-ruang"><?= icon('bed', 14) ?> <?= e(lokasi($p)) ?></span></div>
  </div>

  <?php if ($call): ?>
    <?php $st = $call['status']; ?>
    <div class="card" id="statusCard" data-t="<?= e($t) ?>" data-id="<?= (int)$call['id'] ?>" data-status="<?= e($st) ?>">
      <div class="big-ok"><span class="ic"><?= icon('check', 40) ?></span>
        <h2 style="margin:0"><?= isset($_GET['dup']) ? 'Laporan Anda sudah diterima' : 'Laporan terkirim!' ?></h2>
        <p class="muted" style="margin:4px 0 0"><?= isset($_GET['dup']) ? 'Laporan yang sama masih diproses, tidak perlu mengirim ulang.' : 'Perawat sudah menerima notifikasi di ruang jaga.' ?></p></div>
      <div class="total-box mt" style="background:var(--pri-soft);border-radius:12px;padding:12px 14px">
        <b><?= e($call['keluhan']) ?></b><?= $call['pesan'] ? '<div class="small">“' . e($call['pesan']) . '”</div>' : '' ?>
        <div class="small muted">Dikirim <?= date('H:i', strtotime($call['waktu'])) ?> WIB</div>
      </div>
      <ul class="track mt">
        <li class="done"><span class="dot">✓</span><div><b>Laporan terkirim</b><small><?= date('H:i', strtotime($call['waktu'])) ?></small></div></li>
        <li id="trk2" class="<?= $st === 'baru' ? 'now' : 'done' ?>"><span class="dot"><?= $st === 'baru' ? '2' : '✓' ?></span><div><b id="trk2t"><?= $st === 'baru' ? 'Menunggu perawat…' : 'Perawat menuju ke kamar Anda' ?></b>
          <small id="trk2s"><?= $call['perawat'] ? e($call['perawat']) . ' · ' . date('H:i', strtotime($call['waktu_respon'])) : 'Mohon tunggu sebentar' ?></small></div></li>
        <li id="trk3" class="<?= $st === 'selesai' ? 'done' : ($st === 'diproses' ? 'now' : '') ?>"><span class="dot"><?= $st === 'selesai' ? '✓' : '3' ?></span><div><b>Selesai ditangani</b>
          <small id="trk3s"><?= $call['waktu_selesai'] ? date('H:i', strtotime($call['waktu_selesai'])) : '-' ?></small></div></li>
      </ul>
    </div>
    <a class="btn light block" href="<?= base_url('lapor.php?t=' . $t) ?>">Kirim laporan lain</a>
    <p class="center small muted mt">Halaman ini diperbarui otomatis.</p>

  <?php else: ?>
    <?php if ($terbuka): ?>
      <a class="alert info" style="display:block" href="<?= base_url('lapor.php?t=' . $t . '&id=' . $terbuka['id']) ?>">Laporan <b><?= e($terbuka['keluhan']) ?></b> sedang <?= $terbuka['status'] === 'baru' ? 'menunggu perawat' : 'ditangani' ?>. <u>Lihat status →</u></a>
    <?php endif; ?>
    <?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>
    <form method="post" id="formLapor" autocomplete="off">
      <?= csrf_field() ?>
      <div class="lx-head"><h2>Apa yang Anda butuhkan?</h2><p>Pilih salah satu keluhan atau permintaan</p></div>
      <div class="lx-grid">
        <?php foreach ($keluhan as $k): $wajib = stripos($k['nama'], 'lain') !== false; ?>
          <button type="button" class="lx-kel" data-kel="<?= (int)$k['id'] ?>" data-nama="<?= e($k['nama']) ?>" data-ikon="<?= e($k['ikon']) ?>" data-wajib="<?= $wajib ? '1' : '0' ?>">
            <span class="lx-ic"><?= ikon_keluhan($k['nama']) ?></span><span class="lx-nm"><?= e($k['nama']) ?></span>
          </button>
        <?php endforeach; ?>
      </div>
      <?php if ($suaraOn): ?>
      <button type="button" class="lx-voice" data-mic-open>
        <span class="lx-voice-ic"><?= icon('mic', 22) ?></span>
        <span class="lx-voice-t"><b>Kirim Pesan Suara</b><small>Sampaikan langsung dengan suara ke perawat</small></span>
        <span class="lx-voice-go"><?= icon('chev', 18) ?></span>
      </button>
      <?php endif; ?>

      <!-- Modal konfirmasi laporan (tengah layar) -->
      <div class="lx-modal" id="lxModal" hidden>
        <div class="lx-box" role="dialog" aria-modal="true" aria-labelledby="lxJudul">
          <button type="button" class="lx-x" data-lx-tutup aria-label="Tutup">&times;</button>
          <div class="lx-sel"><span class="lx-sel-ic" id="lxIkon"></span><div><small>Keluhan / permintaan</small><b id="lxJudul"></b></div></div>
          <?php if ($err): ?><div class="lx-err"><?= icon('alert', 16) ?><span><?= e($err) ?></span></div><?php endif; ?>
          <input type="hidden" name="keluhan" id="lxKel" value="<?= (int)($_POST['keluhan'] ?? 0) ?: '' ?>">
          <label for="pesan" class="lx-lbl">Keterangan tambahan <span id="lxOpsi">(opsional)</span></label>
          <textarea id="pesan" name="pesan" maxlength="255" rows="3" placeholder="mis. nyeri di perut kanan sejak 1 jam, boleh di kosongkan"><?= e($_POST['pesan'] ?? '') ?></textarea>
          <div class="lx-lbl">Yang melapor</div>
          <div class="lx-seg">
            <label><input type="radio" name="pelapor" value="pasien" <?= ($_POST['pelapor'] ?? 'pasien') !== 'keluarga' ? 'checked' : '' ?>><span><svg width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.9' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='8' r='4'/><path d='M4 21a8 8 0 0 1 16 0'/></svg> Pasien</span></label>
            <label><input type="radio" name="pelapor" value="keluarga" <?= ($_POST['pelapor'] ?? '') === 'keluarga' ? 'checked' : '' ?>><span><svg width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.9' stroke-linecap='round' stroke-linejoin='round'><circle cx='9' cy='8' r='3.5'/><path d='M2.5 20a6.5 6.5 0 0 1 13 0'/><circle cx='17' cy='9' r='2.5'/><path d='M16 14.2a5 5 0 0 1 5.5 5.3'/></svg> Keluarga</span></label>
          </div>
          <div class="lx-act">
            <button type="button" class="lx-btn2" data-lx-tutup>Batal</button>
            <button type="submit" class="lx-send"><?= icon('send', 18) ?> Kirim Laporan</button>
          </div>
        </div>
      </div>
    </form>
    <div class="lx-sos"><?= icon('alert', 16) ?><span>Kondisi <b>gawat darurat</b>? Tekan bel darurat di samping tempat tidur atau panggil petugas terdekat.</span></div>
  <?php endif; ?>
<?php endif; ?>
<?php if ($aktif && !$call && $suaraOn): ?>
<!-- Modal rekam pesan suara -->
<div class="rec-modal" id="recModal" hidden>
  <div class="rec-box" role="dialog" aria-labelledby="recTitle">
    <button type="button" class="rec-x" data-rec-close aria-label="Tutup">✕</button>
    <h2 id="recTitle">Kirim Pesan Suara</h2>
    <p class="muted small" id="recHint">Tekan tombol mikrofon, lalu silakan bicara.</p>
    <div class="rec-viz"><canvas id="recWave" width="600" height="90"></canvas></div>
    <div class="rec-time"><b id="recTime">0:00</b> / <?= gmdate('i:s', $maksSuara) ?></div>
    <button type="button" class="rec-btn" id="recBtn" aria-label="Mulai rekam"><?= icon('mic', 40) ?></button>
    <div id="recPreview" hidden><audio id="recAudio" controls style="width:100%"></audio></div>
    <div id="recFallback" hidden>
      <div class="alert warn small" style="text-align:left">Perekam langsung tidak tersedia di browser ini. Tekan tombol di bawah untuk merekam memakai aplikasi perekam HP.</div>
      <label class="btn light block" style="margin:0"><?= icon('mic', 18) ?> Rekam dengan perekam HP<input type="file" id="recFile" accept="audio/*" capture hidden></label>
    </div>
    <div class="rec-err alert err small" id="recErr" hidden></div>
    <div class="rec-act" id="recAct" hidden>
      <button type="button" class="btn light" id="recUlang">Rekam ulang</button>
      <button type="button" class="btn send" id="recKirim" style="padding:14px"><?= icon('send', 18) ?> Kirim</button>
    </div>
  </div>
</div>
<?php endif; ?>
  <footer class="ax-foot">© <?= date('Y') ?> <b>Nurse Call Digital</b><br>Powered by www.fixdigitech.com · 0821-7784-6209</footer>
</div>
<script>
(function () {
  var f = document.getElementById('formLapor');
  if (f) {
    var m = document.getElementById('lxModal'), kel = document.getElementById('lxKel'), ps = document.getElementById('pesan'), wajib = false;
    var buka = function (b) {
      kel.value = b.getAttribute('data-kel'); wajib = b.getAttribute('data-wajib') === '1';
      document.getElementById('lxJudul').textContent = b.getAttribute('data-nama');
      document.getElementById('lxIkon').innerHTML = b.querySelector('.lx-ic').innerHTML;
      document.getElementById('lxOpsi').textContent = wajib ? '(wajib diisi)' : '(opsional)';
      ps.placeholder = wajib ? 'Tuliskan kebutuhan Anda…' : 'mis. nyeri di perut kanan sejak 1 jam, boleh dikosongkan';
      f.querySelectorAll('.lx-kel').forEach(function (x) { x.classList.toggle('on', x === b); });
      m.hidden = false; document.body.style.overflow = 'hidden';
      if (wajib) setTimeout(function () { ps.focus(); }, 150);
    };
    var tutup = function () { m.hidden = true; document.body.style.overflow = ''; };
    f.querySelectorAll('.lx-kel').forEach(function (b) { b.addEventListener('click', function () { buka(b); }); });
    m.addEventListener('click', function (e) { if (e.target === m || e.target.closest('[data-lx-tutup]')) tutup(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !m.hidden) tutup(); });
    f.addEventListener('submit', function (ev) {
      if (!kel.value) { ev.preventDefault(); return; }
      if (wajib && !ps.value.trim()) { ev.preventDefault(); ps.focus(); ps.classList.add('err'); return; }
      var b = f.querySelector('[type=submit]'); setTimeout(function () { b.disabled = true; b.textContent = 'Mengirim…'; }, 0);
    });
    // Setelah kirim gagal (mis. terlalu cepat), buka lagi modal dengan pilihan sebelumnya
    if (kel.value) { var s0 = f.querySelector('.lx-kel[data-kel="' + kel.value + '"]'); if (s0) buka(s0); }
  }
  var c = document.getElementById('statusCard');
  if (!c || c.getAttribute('data-status') === 'selesai') return;
  var base = <?= json_encode(base_url()) ?>;
  var tm = setInterval(function () {
    fetch(base + 'api/status.php?t=' + c.getAttribute('data-t') + '&id=' + c.getAttribute('data-id'), { cache: 'no-store' })
      .then(function (r) { return r.json(); }).then(function (d) {
        if (!d.ok) return;
        var t2 = document.getElementById('trk2'), t3 = document.getElementById('trk3');
        if (d.status !== 'baru') {
          t2.className = 'done'; t2.querySelector('.dot').textContent = '✓';
          document.getElementById('trk2t').textContent = 'Perawat menuju ke kamar Anda';
          document.getElementById('trk2s').textContent = (d.perawat || 'Perawat') + (d.respon ? ' · ' + d.respon : '');
          if (d.status === 'diproses' && t3.className !== 'now') { t3.className = 'now'; if (navigator.vibrate) navigator.vibrate(200); }
        }
        if (d.status === 'selesai') { t3.className = 'done'; t3.querySelector('.dot').textContent = '✓'; document.getElementById('trk3s').textContent = d.selesai || ''; clearInterval(tm); }
      }).catch(function () {});
  }, 5000);
})();
</script>
<?php if ($aktif && !$call && $suaraOn): ?>
<script>window.REC = <?= json_encode(['url' => base_url('lapor.php?t=' . $t), 'csrf' => csrf_token(), 'maks' => $maksSuara]) ?>;</script>
<script src="<?= aset('assets/js/rekam.js') ?>"></script>
<?php endif; ?>
</body>
</html>