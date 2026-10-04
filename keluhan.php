<?php

require __DIR__ . '/inc/bootstrap.php';
require_akses('master');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0); $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'simpan') {
        $d = ['nama' => trim($_POST['nama'] ?? ''), 'ikon' => mb_substr(trim($_POST['ikon'] ?? ''), 0, 8) ?: '🔔',
              'prioritas' => array_key_exists($_POST['prioritas'] ?? '', PRIORITAS) ? $_POST['prioritas'] : 'sedang', 'urut' => (int)($_POST['urut'] ?? 0)];
        if ($d['nama'] === '') flash('Nama keluhan wajib diisi.', 'err');
        else { $id ? q('UPDATE nc_keluhan_wira SET nama = ?, ikon = ?, prioritas = ?, urut = ? WHERE id = ?', array_merge(array_values($d), [$id]))
                   : q('INSERT INTO nc_keluhan_wira (nama, ikon, prioritas, urut) VALUES (?,?,?,?)', array_values($d)); flash('Jenis keluhan disimpan.'); }
    } elseif ($aksi === 'toggle') { q('UPDATE nc_keluhan_wira SET aktif = 1 - aktif WHERE id = ?', [$id]); flash('Status diubah.'); }
    redirect(base_url('keluhan.php'));
}
$edit = isset($_GET['edit']) ? q('SELECT * FROM nc_keluhan_wira WHERE id = ?', [(int)$_GET['edit']])->fetch() : null;
$list = q('SELECT k.*, (SELECT COUNT(*) FROM nc_panggilan_wira p WHERE p.keluhan_id = k.id) n FROM nc_keluhan_wira k ORDER BY k.aktif DESC, k.urut, k.nama')->fetchAll();
$pageTitle = 'Jenis Keluhan'; $active = 'keluhan'; $back = base_url('menu.php');
layout_top();
?>
<div class="page-head desk"><h1>Jenis Keluhan / Permintaan</h1></div>
<div class="grid3">
  <div class="card flush"><div class="table-wrap"><table>
    <tr><th>Urut</th><th>Keluhan</th><th>Prioritas</th><th class="right">Dipakai</th><th></th></tr>
    <?php foreach ($list as $k): ?>
    <tr style="<?= $k['aktif'] ? '' : 'opacity:.5' ?>"><td class="muted"><?= (int)$k['urut'] ?></td><td><span style="font-size:20px"><?= e($k['ikon']) ?></span> <b><?= e($k['nama']) ?></b><?= $k['aktif'] ? '' : ' <span class="badge gray">Nonaktif</span>' ?></td>
      <td><?= badge_prio($k['prioritas']) ?></td><td class="right"><?= (int)$k['n'] ?>×</td>
      <td class="aksi"><a class="btn sm light" href="?edit=<?= $k['id'] ?>">Ubah</a>
        <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="aksi" value="toggle"><input type="hidden" name="id" value="<?= $k['id'] ?>"><button class="btn sm light"><?= $k['aktif'] ? 'Sembunyikan' : 'Tampilkan' ?></button></form></td></tr>
    <?php endforeach; ?>
  </table></div></div>
  <form method="post" class="card dash-side" data-once><?= csrf_field() ?><input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <h2><?= $edit ? 'Ubah' : 'Tambah' ?> Keluhan</h2>
    <label>Nama keluhan / permintaan <span class="req">*</span></label><input name="nama" required maxlength="60" value="<?= e($edit['nama'] ?? '') ?>" placeholder="mis. Ganti popok">
    <div class="row"><div><label>Ikon (emoji)</label><input name="ikon" maxlength="8" value="<?= e($edit['ikon'] ?? '🔔') ?>" style="font-size:20px"></div>
      <div><label>Urutan</label><input name="urut" type="number" value="<?= (int)($edit['urut'] ?? 50) ?>"></div></div>
    <label>Prioritas</label><select name="prioritas"><?php foreach (PRIORITAS as $k => $v): ?><option value="<?= $k ?>" <?= ($edit['prioritas'] ?? 'sedang') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
    <div class="hint">Prioritas tinggi tampil paling atas di nurse station.</div>
    <div class="flex mt"><button class="btn">Simpan</button><?php if ($edit): ?><a class="btn light" href="?">Batal</a><?php endif; ?></div>
  </form>
</div>
<?php layout_bottom(); ?>