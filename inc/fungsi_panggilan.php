<?php
/** Fungsi bersama untuk data panggilan (dipakai API & halaman). Tabel: nc_panggilan_wira */
const NC_V_PANGGILAN = 'wira-2';

function iso(?string $dt): ?string { return $dt ? date('c', strtotime($dt)) : null; }

/** Ubah 1 baris panggilan menjadi data JSON untuk nurse station */
/** Nama ruang untuk diucapkan: "Aster 3 A (LT3)" -> "Aster 3A" (tanpa keterangan dalam kurung) */
function ruang_ucap(?string $ruang): string {
    $r = trim(preg_replace('/\s*[\(\[][^\)\]]*[\)\]]/', '', (string)$ruang));
    $r = preg_replace('/(\d)\s+([A-Za-z])\b/', '$1$2', $r);
    return trim(preg_replace('/\s+/', ' ', $r));
}
/**
 * Kalimat pengumuman suara (dipakai nurse station & display TV):
 * "Panggilan perawat. Aster 3A. Atas nama Yuli Lestari. Dengan laporan Pusing."
 * Pilihan "Lainnya" -> yang dibaca isi teks yang ditulis pasien. Pesan suara -> "pesan suara".
 */
function teks_ucapan(array $c, string $ruang, string $nama): string {
    $kel = trim((string)($c['keluhan'] ?? '')); $pesan = trim((string)($c['pesan'] ?? ''));
    if (!empty($c['audio'])) $lap = 'pesan suara';
    elseif ($pesan !== '' && ($kel === '' || stripos($kel, 'lain') !== false)) $lap = $pesan;
    else $lap = str_replace('/', ' atau ', $kel) . ($pesan !== '' ? ', ' . $pesan : '');
    $lap = trim(preg_replace('/\s+/', ' ', $lap), " .,");
    $t = 'Panggilan perawat. ' . ruang_ucap($ruang) . '. ' . ($nama !== '' ? 'Atas nama ' . $nama . '. ' : '') . 'Dengan laporan ' . $lap . '.';
    if (($c['pelapor'] ?? '') === 'keluarga') $t .= ' Dilaporkan oleh keluarga.';
    return $t;
}

/* ================= CODE BLUE ================= */
/** Code Blue yang masih aktif (otomatis ditutup setelah 60 menit agar alarm tidak berbunyi selamanya) */
function codeblue_aktif(): array {
    try {
        q("UPDATE nc_codeblue_wira SET status = 'selesai', waktu_selesai = NOW(), catatan = COALESCE(catatan, 'Ditutup otomatis setelah 60 menit')
           WHERE status = 'aktif' AND waktu < NOW() - INTERVAL 60 MINUTE");
        return q("SELECT c.*, COALESCE(u.nama, c.oleh) nama_oleh FROM nc_codeblue_wira c LEFT JOIN nc_user_wira u ON u.id_user = c.oleh
                  WHERE c.status = 'aktif' ORDER BY c.waktu DESC")->fetchAll();
    } catch (PDOException $e) { error_log('[NurseCall] codeblue_aktif: ' . $e->getMessage()); return []; }
}
/** Kalimat pengumuman: "Code blue. Code blue. Code blue di Aster 3A, kamar A 3A." */
function codeblue_ucapan(array $c): string {
    if (!empty($c['kd_kamar'])) {
        $ruang = ruang_ucap(nama_rapi((string)($c['nm_bangsal'] ?? ''), true));
        $kamar = trim(preg_replace('/[._\-]+/', ' ', (string)$c['kd_kamar']));
        $lok = ($ruang !== '' ? $ruang . ', ' : '') . 'kamar ' . $kamar;
    } else $lok = (string)$c['lokasi'];
    return 'Code blue. Code blue. Code blue di ' . $lok . '.';
}
function cb_json(array $c): array {
    if (!isset($c['nm_bangsal']) && !empty($c['kd_bangsal'])) $c['nm_bangsal'] = (string)q('SELECT nm_bangsal FROM bangsal WHERE kd_bangsal = ?', [$c['kd_bangsal']])->fetchColumn();
    return ['id' => (int)$c['id'], 'lokasi' => $c['lokasi'], 'pasien' => $c['pasien'], 'oleh' => $c['nama_oleh'] ?? $c['oleh'],
            'waktu' => iso($c['waktu']), 'jam' => date('H:i', strtotime($c['waktu'])), 'ucapan' => codeblue_ucapan($c)];
}

function pg_json(array $r): array {
    $r = rapikan_pasien($r);
    return [
        'id' => (int)$r['id'], 'no_rawat' => $r['no_rawat'], 'pasien' => $r['pasien'], 'no_rm' => $r['no_rm'], 'jk' => $r['jk'],
        'umur' => umur($r['tgl_lahir']), 'ruang' => $r['ruang'], 'lokasi' => lokasi($r),
        'keluhan' => $r['keluhan'], 'ikon' => $r['audio'] ? '🎤' : ($r['ikon'] ?? '🔔'), 'prioritas' => $r['prioritas'],
        'pesan' => $r['pesan'], 'audio' => $r['audio'] ? base_url('api/audio.php?id=' . $r['id']) : null,
        'durasi_audio' => $r['durasi_audio'] !== null ? (int)$r['durasi_audio'] : null, 'pelapor' => $r['pelapor'], 'status' => $r['status'],
        'waktu' => iso($r['waktu']), 'jam' => date('H:i', strtotime($r['waktu'])), 'tgl' => tgl_indo($r['waktu'], true),
        'waktu_respon' => iso($r['waktu_respon']), 'perawat' => $r['perawat'],
        'detik_respon' => $r['waktu_respon'] ? (int)$r['detik_respon'] : null,
        'tindakan' => $r['tindakan'],
        'ucapan' => teks_ucapan($r, (string)$r['ruang'], (string)$r['pasien']),
    ];
}

/**
 * Ubah status panggilan. $aksi: tangani (baru -> diproses) | selesai (baru/diproses -> selesai)
 * Mengembalikan pesan sukses, melempar RuntimeException jika gagal.
 */
function ubah_status(int $id, string $aksi, string $tindakan = ''): string {
    $u = user(); $now = date('Y-m-d H:i:s');
    $p = q('SELECT * FROM nc_panggilan_wira WHERE id = ?', [$id])->fetch();
    if (!$p) throw new RuntimeException('Panggilan tidak ditemukan.');
    if ($aksi === 'tangani') {
        if ($p['status'] !== 'baru') {
            $siapa = $p['perawat'] ? q('SELECT nama FROM nc_user_wira WHERE id_user = ?', [$p['perawat']])->fetchColumn() : '';
            throw new RuntimeException('Panggilan ini sudah ditangani' . ($siapa ? ' oleh ' . $siapa : '') . '.');
        }
        // Kondisi status='baru' di WHERE mencegah 2 perawat menangani bersamaan
        if (!q("UPDATE nc_panggilan_wira SET status = 'diproses', waktu_respon = ?, perawat = ? WHERE id = ? AND status = 'baru'", [$now, $u['id_user'], $id])->rowCount())
            throw new RuntimeException('Panggilan ini sudah ditangani perawat lain.');
        return 'Panggilan ditandai sedang ditangani. Segera menuju pasien.';
    }
    if ($aksi === 'selesai') {
        if ($p['status'] === 'selesai') throw new RuntimeException('Panggilan ini sudah selesai.');
        q("UPDATE nc_panggilan_wira SET status = 'selesai', waktu_selesai = ?, selesai_oleh = ?, tindakan = ?,
             waktu_respon = COALESCE(waktu_respon, ?), perawat = COALESCE(perawat, ?) WHERE id = ?",
          [$now, $u['id_user'], mb_substr(trim($tindakan), 0, 255) ?: null, $now, $u['id_user'], $id]);
        return 'Panggilan selesai ditangani.';
    }
    throw new RuntimeException('Aksi tidak dikenal.');
}

/** Kenali format file suara dari isi file (bukan dari nama). Mengembalikan ekstensi atau null. */
function jenis_audio(string $file): ?string {
    $h = (string)@file_get_contents($file, false, null, 0, 16);
    if (strncmp($h, "\x1A\x45\xDF\xA3", 4) === 0) return 'webm';                 // Chrome / Android (Opus)
    if (strncmp($h, 'OggS', 4) === 0) return 'ogg';                                  // Firefox
    if (substr($h, 4, 4) === 'ftyp') return strpos(substr($h, 8, 4), '3g') === 0 ? '3gp' : 'm4a'; // iPhone / Safari, perekam HP
    if (strncmp($h, 'RIFF', 4) === 0 && substr($h, 8, 4) === 'WAVE') return 'wav';
    if (strncmp($h, 'ID3', 3) === 0 || (ord($h[0] ?? "\0") === 0xFF && (ord($h[1] ?? "\0") & 0xE0) === 0xE0)) return 'mp3';
    if (strncmp($h, '#!AMR', 5) === 0) return 'amr';
    return null;
}
const MIME_AUDIO = ['webm' => 'audio/webm', 'ogg' => 'audio/ogg', 'm4a' => 'audio/mp4', '3gp' => 'audio/3gpp', 'wav' => 'audio/wav', 'mp3' => 'audio/mpeg', 'amr' => 'audio/amr'];