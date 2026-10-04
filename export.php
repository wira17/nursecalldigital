<?php

require __DIR__ . '/inc/bootstrap.php';
require_login();
if (!bisa('station') && !bisa('laporan')) { http_response_code(403); exit('Akses ditolak'); }
require __DIR__ . '/inc/filter_pg.php';
$st = q(SQL_PG . " $where ORDER BY p.waktu", $params);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="nurse-call-' . $f['dari'] . '_' . $f['sampai'] . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
fwrite($out, "sep=;\n");
$safe = function ($v) { return (is_string($v) && $v !== '' && !is_numeric($v) && strpbrk($v[0], '=+-@') !== false) ? "'" . $v : $v; };
fputcsv($out, ['No', 'Waktu Lapor', 'No. Rawat', 'No. RM', 'Nama Pasien', 'Ruang', 'Keluhan', 'Prioritas', 'Pesan', 'Pesan Suara', 'Pelapor', 'Status', 'Waktu Respon', 'Respon (detik)', 'Perawat', 'Waktu Selesai', 'Tindakan'], ';', '"', '');
$i = 0;
while ($r = $st->fetch()) {
    $r = rapikan_pasien($r);
    $row = array_map($safe, [++$i, $r['waktu'], $r['no_rawat'], '', $r['pasien'], lokasi($r), $r['keluhan'], PRIORITAS[$r['prioritas']], $r['pesan'], $r['audio'] ? 'Ya' . ($r['durasi_audio'] ? ' (' . (int)$r['durasi_audio'] . ' dtk)' : '') : '', $r['pelapor'],
        STATUS_PG[$r['status']], $r['waktu_respon'], $r['waktu_respon'] ? (int)$r['detik_respon'] : '', $r['perawat'], $r['waktu_selesai'], $r['tindakan']]);
    $row[3] = '="' . $r['no_rm'] . '"'; // No. RM tetap teks (angka 0 di depan tidak hilang)
    fputcsv($out, $row, ';', '"', '');
}
fclose($out);