<?php

require __DIR__ . '/inc/bootstrap.php';
require_akses('master');
$me = user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = trim((string)($_POST['id_user'] ?? '')); $aksi = $_POST['aksi'] ?? '';
    $lama = $id !== '' ? q('SELECT * FROM nc_user_wira WHERE id_user = ?', [$id])->fetch() : null;
    if ($aksi === 'simpan') {
        $baru = !$lama;
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'user';
        $bangsal = substr(preg_replace('/[^\w.-]/', '', (string)($_POST['kd_bangsal'] ?? '')), 0, 5) ?: null;
        $hp = preg_replace('/[^0-9+]/', '', $_POST['no_hp'] ?? '') ?: null;
        $err = [];
        if (!preg_match('/^[A-Za-z0-9._-]{1,30}$/', $id)) $err[] = 'NIK tidak valid.';
        elseif ($baru && !khanza_user_ada($id)) $err[] = 'NIK ' . $id . ' tidak ditemukan di tabel user Khanza.';
        if ($id === $me['id_user'] && $role !== 'admin') $err[] = 'Anda tidak bisa menurunkan role akun sendiri.';
        if (is_admin_nik($id) && $role !== 'admin') $err[] = 'NIK ini tercantum di NC_ADMIN_NIK (config.php) sehingga selalu Admin.';
        if ($err) { flash(implode(' ', $err), 'err'); redirect(base_url('pengguna.php' . ($lama ? '?edit=' . rawurlencode($id) : ''))); }
        if ($baru) {
            $peg = khanza_pegawai($id);
            q('INSERT INTO nc_user_wira (id_user, nama, role, kd_bangsal, no_hp) VALUES (?,?,?,?,?)', [$id, mb_substr($peg['nama'] ?? $id, 0, 100), $role, $bangsal, $hp]);
            flash('NIK ' . $id . ' (' . ($peg['nama'] ?? '-') . ') didaftarkan sebagai ' . ROLE_LABEL[$role] . '. Ia login memakai password Khanza-nya.');
        } else {
            q('UPDATE nc_user_wira SET role = ?, kd_bangsal = ?, no_hp = ? WHERE id_user = ?', [$role, $bangsal, $hp, $id]);
            flash('Pengguna diperbarui.');
        }
    } elseif ($lama && $id !== $me['id_user']) {
        if ($aksi === 'toggle') {
            if (is_admin_nik($id)) flash('NIK di NC_ADMIN_NIK tidak bisa dinonaktifkan dari sini.', 'err');
            else { q('UPDATE nc_user_wira SET aktif = 1 - aktif WHERE id_user = ?', [$id]); flash($lama['aktif'] ? 'Akses Nurse Call untuk ' . $lama['nama'] . ' dinonaktifkan.' : 'Akses diaktifkan kembali.'); }
        }
        if ($aksi === 'hapus') {
            q('DELETE FROM nc_user_wira WHERE id_user = ?', [$id]);
            flash('Pengaturan role ' . $lama['nama'] . ' dihapus. Jika ia login lagi, akan memakai role otomatis.');
        }
    }
    redirect(base_url('pengguna.php'));
}
$edit = isset($_GET['edit']) ? q('SELECT * FROM nc_user_wira WHERE id_user = ?', [(string)$_GET['edit']])->fetch() : null;
$cari = trim($_GET['cari'] ?? '');
$par = []; $w = '';
if ($cari !== '') { $w = 'WHERE u.nama LIKE ? OR u.id_user LIKE ?'; $par = ["%$cari%", "%$cari%"]; }
$list = q("SELECT u.*, b.nm_bangsal ruang FROM nc_user_wira u LEFT JOIN bangsal b ON b.kd_bangsal = u.kd_bangsal $w ORDER BY u.aktif DESC, u.role = 'admin' DESC, u.nama", $par)->fetchAll();
$ruangList = daftar_bangsal();
$pageTitle = 'Pengguna'; $active = 'pengguna'; $back = base_url('menu.php');
layout_top();
?>
<div class="page-head"><div class="desk"><h1>Pengguna &amp; Hak Akses</h1><div class="muted small">Pegawai SIMRS Khanza yang pernah login / didaftarkan ke Nurse Call</div></div>
  <form method="get" class="flex"><input name="cari" value="<?= e($cari) ?>" placeholder="Cari nama / NIK" style="width:220px"><button class="btn light">Cari</button></form></div>
<div class="grid3">
  <div>
    <?php if (!$list): ?><div class="card"><?= empty_box('Belum ada pengguna.', 'users') ?></div><?php endif; ?>
    <div class="list">
    <?php foreach ($list as $x): ?>
      <div class="item" style="<?= $x['aktif'] ? '' : 'opacity:.55' ?>">
        <div class="h"><span class="flex" style="flex-wrap:nowrap"><span class="avatar"><?= e(inisial($x['nama'])) ?></span><span><b><?= e($x['nama']) ?></b>
          <div class="small muted">NIK <?= e($x['id_user']) ?><?= $x['ruang'] ? ' · Ruang ' . e(nama_rapi($x['ruang'], true)) : '' ?><?= $x['last_login'] ? ' · login ' . tgl_indo($x['last_login'], true) : ' · belum pernah login' ?></div></span></span>
          <span><span class="badge role"><?= e(ROLE_LABEL[$x['role']] ?? 'User') ?></span><?= $x['aktif'] ? '' : ' <span class="badge gray">Nonaktif</span>' ?></span></div>
        <div class="f" style="justify-content:flex-end">
          <a class="btn sm light" href="?edit=<?= rawurlencode($x['id_user']) ?>">Ubah</a>
          <?php if ($x['id_user'] !== $me['id_user'] && !is_admin_nik($x['id_user'])): ?>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="toggle"><input type="hidden" name="id_user" value="<?= e($x['id_user']) ?>"><button class="btn sm light"><?= $x['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id_user" value="<?= e($x['id_user']) ?>"><button class="btn sm danger" data-confirm="Hapus pengaturan role <?= e($x['nama']) ?>? (Tidak menghapus user di Khanza)">Hapus</button></form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
    <div class="card mt"><h2>Hak akses per role</h2>
      <dl class="info">
        <dt>Login</dt><dd>Semua pegawai yang punya akun SIMRS Khanza bisa login dengan <b>NIK &amp; password Khanza</b>. Saat pertama login otomatis menjadi <b>User</b>. <i>Nonaktifkan</i> untuk menolak akses seseorang.</dd>
        <dt>Admin</dt><dd>Semua menu: Dashboard, Admisi, Rawat Inap, Pengguna, Tampilan &amp; Tema, Pengaturan, Laporan. NIK di <code>NC_ADMIN_NIK</code> (config.php) selalu Admin.</dd>
        <dt>User</dt><dd>Dashboard (nurse station: notifikasi, alarm, menangani panggilan), Admisi dan Rawat Inap (lihat pasien &amp; cetak gelang).</dd>
      </dl>
      <p class="small muted">Ruang tugas = bangsal Khanza yang otomatis dipantau di nurse station saat login.</p>
    </div>
  </div>
  <form method="post" class="card dash-side" data-once><?= csrf_field() ?><input type="hidden" name="aksi" value="simpan">
    <h2><?= $edit ? 'Ubah' : 'Daftarkan' ?> Pengguna</h2>
    <?php if (!$edit): ?><p class="small muted" style="margin-top:0">Tidak wajib — pegawai Khanza bisa langsung login. Daftarkan di sini jika ingin menentukan role / ruang sebelum ia login.</p><?php endif; ?>
    <label>NIK / ID User Khanza <span class="req">*</span></label>
    <input name="id_user" required maxlength="30" autocapitalize="none" value="<?= e($edit['id_user'] ?? '') ?>" <?= $edit ? 'readonly' : '' ?>>
    <?php if ($edit): ?><label>Nama <span class="muted small">(mengikuti Khanza)</span></label><input value="<?= e($edit['nama']) ?>" readonly><?php endif; ?>
    <label>Role <span class="req">*</span></label><select name="role"><?php foreach (ROLE_LABEL as $k => $l): ?><option value="<?= $k ?>" <?= ($edit['role'] ?? 'user') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
    <label>Ruang tugas</label><select name="kd_bangsal"><option value="">Semua ruang</option><?php foreach ($ruangList as $r): ?><option value="<?= e($r['kd_bangsal']) ?>" <?= ($edit['kd_bangsal'] ?? '') === $r['kd_bangsal'] ? 'selected' : '' ?>><?= e($r['nama']) ?></option><?php endforeach; ?></select>
    <label>No. HP</label><input name="no_hp" inputmode="tel" value="<?= e($edit['no_hp'] ?? '') ?>">
    <p class="hint">Password tidak diatur di sini — mengikuti password SIMRS Khanza.</p>
    <div class="flex mt"><button class="btn">Simpan</button><?php if ($edit): ?><a class="btn light" href="?">Batal</a><?php endif; ?></div>
  </form>
</div>
<?php layout_bottom(); ?>