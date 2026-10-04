<?php

$f = [
    'dari'    => valid_date($_GET['dari'] ?? '') ? $_GET['dari'] : date('Y-m-d', strtotime('-6 days')),
    'sampai'  => valid_date($_GET['sampai'] ?? '') ? $_GET['sampai'] : date('Y-m-d'),
    'ruang'   => substr(preg_replace('/[^\w.-]/', '', (string)($_GET['ruang'] ?? '')), 0, 5),
    'status'  => array_key_exists($_GET['status'] ?? '', STATUS_PG) ? $_GET['status'] : '',
    'keluhan' => (int)($_GET['keluhan'] ?? 0),
    'cari'    => trim($_GET['cari'] ?? ''),
];
$w = ['DATE(p.waktu) BETWEEN ? AND ?']; $params = [$f['dari'], $f['sampai']];
if ($f['ruang'] !== '') { $w[] = 'p.kd_bangsal = ?'; $params[] = $f['ruang']; }
if ($f['status'])  { $w[] = 'p.status = ?'; $params[] = $f['status']; }
if ($f['keluhan']) { $w[] = 'p.keluhan_id = ?'; $params[] = $f['keluhan']; }
if ($f['cari'] !== '') { $w[] = '(ps.nm_pasien LIKE ? OR p.no_rkm_medis LIKE ? OR p.no_rawat LIKE ? OR p.pesan LIKE ? OR p.tindakan LIKE ?)'; $l = '%' . $f['cari'] . '%'; array_push($params, $l, $l, $l, $l, $l); }
$where = 'WHERE ' . implode(' AND ', $w);