<?php
const NC_V_LAYOUT = 'wira-2';
/* =========================================================
 *  Layout bersama (desain sama dengan E-Alkes / E-Cuti)
 *  - Monitor/laptop (>820px): topbar + menu dropdown + tabel
 *  - HP (<=820px): header gradasi + tab bar bawah + kartu (PWA)
 *  Variabel sebelum layout_top(): $pageTitle, $active, $back (opsional, URL tombol kembali di HP)
 * ========================================================= */

function icon(string $n, int $s = 22): string {
    $p = [
        'chev'  => '<path d="M9 6l6 6-6 6"/>',
        'alert' => '<path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
        'info'  => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>',
        'home'  => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'plus'  => '<path d="M12 5v14M5 12h14"/>',
        'list'  => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'bell'  => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'monitor'=> '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>',
        'bed'   => '<path d="M2 4v16M2 8h18a2 2 0 0 1 2 2v10M2 17h20"/><circle cx="7" cy="12.5" r="2"/>',
        'qr'    => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3zM20 14v.01M14 20h.01M17 20h4v-3"/>',
        'chart' => '<path d="M18 20V10M12 20V4M6 20v-6"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'door'  => '<path d="M3 21h18M5 21V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v17"/><path d="M15 12h.01"/>',
        'tag'   => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><circle cx="7" cy="7" r="1"/>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'user'  => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'key'   => '<path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.78 7.78 5.5 5.5 0 0 1 7.78-7.78zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>',
        'grid'  => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'gear'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68 1.65 1.65 0 0 0 10 3.17V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'out'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'back'  => '<path d="M15 18l-6-6 6-6"/>',
        'print' => '<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
        'dl'    => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
        'phone' => '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/>',
        'check' => '<path d="M20 6L9 17l-5-5"/>',
        'run'   => '<circle cx="13" cy="4" r="2"/><path d="M4 22l4-8 3 2 1 6M9 11l3-4 3 3 4 1M8 14l-3-1"/>',
        'vol'   => '<path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/>',
        'mute'  => '<path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M23 9l-6 6M17 9l6 6"/>',
        'menu'  => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'down'  => '<path d="M6 9l6 6 6-6"/>',
        'palette'=> '<circle cx="13.5" cy="6.5" r="1"/><circle cx="17.5" cy="10.5" r="1"/><circle cx="8.5" cy="7.5" r="1"/><circle cx="6.5" cy="12.5" r="1"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.93 0 1.5-.75 1.5-1.5 0-.4-.15-.74-.4-1-.25-.26-.4-.6-.4-1 0-.83.67-1.5 1.5-1.5H16c3.31 0 6-2.69 6-6 0-4.96-4.49-9-10-9z"/>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>',
        'mic'   => '<rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 17v5M8 22h8"/>',
        'play'  => '<path d="M6 4l14 8-14 8z"/>',
        'tv'    => '<rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/>',
        'copy'  => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'search'=> '<circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>',
        'send'  => '<path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>',
    ];
    return '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$n] ?? '') . '</svg>';
}

/** Logo lonceng mengikuti warna tema (dipakai di menu, header HP, login, halaman pasien) */
function logo_svg(int $s = 30): string {
    return '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 48 48" aria-hidden="true"><path fill="var(--pri)" d="M24 4a2.6 2.6 0 0 1 2.6 2.6v1.1C33 9 37.4 14.6 37.4 21.2v8.3l3.4 5.2c.8 1.3-.1 3-1.7 3H8.9c-1.6 0-2.5-1.7-1.7-3l3.4-5.2v-8.3C10.6 14.6 15 9 21.4 7.7V6.6A2.6 2.6 0 0 1 24 4z"/><circle cx="24" cy="42" r="4" fill="var(--pri)"/><path fill="#fff" d="M21.6 15h4.8v5.6H32v4.8h-5.6V31h-4.8v-5.6H16v-4.8h5.6z"/><path fill="#e53935" d="M22.4 16h3.2v5.4H31v3.2h-5.4V30h-3.2v-5.4H17v-3.2h5.4z"/></svg>';
}

/**
 * Menu dikelompokkan per grup, disaring sesuai hak akses.
 * Untuk menambah modul baru cukup tambahkan grup di sini.
 */
function menu_groups(): array {
    $g = [];
    $g['utama'] = ['Utama', 'home', ['index' => ['Dashboard', 'index.php', 'home']]];
    $g['pasien'] = ['Pasien', 'bed', [
        'admisi' => ['Admisi', 'admisi.php', 'list'],
        'ranap'  => ['Rawat Inap', 'ranap.php', 'bed'],
    ]];
    if (bisa('codeblue')) $g['darurat'] = ['Darurat', 'alert', ['codeblue' => ['Code Blue', 'codeblue.php', 'heart']]];
    if (bisa('master')) $g['master'] = ['Pengaturan', 'gear', [
        'pengguna'   => ['Pengguna', 'pengguna.php', 'key'],
        'keluhan'    => ['Jenis Keluhan', 'keluhan.php', 'list'],
        'tampilan'   => ['Tampilan & Tema', 'tampilan.php', 'palette'],
        'pengaturan' => ['Pengaturan', 'pengaturan.php', 'gear'],
    ]];
    $g['info'] = ['Lainnya', 'info', ['tentang' => ['Tentang Aplikasi', '#tentang', 'info']]];
    return $g;
}
/** Atribut href menu: '#tentang' membuka modal Tentang Aplikasi, selain itu link biasa */
function menu_href(string $url): string {
    return $url[0] === '#' ? 'href="#" data-tentang' : 'href="' . e(base_url($url)) . '"';
}
const APP_FOOTER = 'Powered by www.fixdigitech.com · 0821-7784-6209'; // teks footer kanan bawah (tetap)

/** Jumlah panggilan baru (belum ditangani) di ruang yang dipantau */
function jumlah_baru(): int {
    static $n = null;
    if ($n === null) {
        $r = ruang_pantau();
        $n = (int)q("SELECT COUNT(*) FROM nc_panggilan_wira WHERE status = 'baru'" . ($r !== '' ? ' AND kd_bangsal = ?' : ''), $r !== '' ? [$r] : [])->fetchColumn();
    }
    return $n;
}

function layout_top(): void {
    global $pageTitle, $active, $back;
    require_login();
    $u = user();
    $title = ($pageTitle ?? 'Dashboard') . ' - ' . APP_NAME;
    $bodyClass = 'app' . (bisa('station') ? ' station-role' : '');
    require __DIR__ . '/head.php';
    $groups = menu_groups();
    $nBaru = bisa('station') ? jumlah_baru() : 0;
    $crumbGroup = '';
    foreach ($groups as [$gl, $gi, $it]) if (isset($it[$active ?? ''])) $crumbGroup = $gl;
    ?>
<div class="shell">
<aside class="side desk" id="side"><div class="side-in">
  <a class="side-brand" href="<?= base_url() ?>">
    <span class="side-logo"><?= logo_svg() ?></span>
    <span class="side-name"><b><?= e(APP_NAME) ?></b><small><?= e(mb_strtoupper(setting('nama_rs'))) ?></small></span>
  </a>
  <nav class="side-nav">
    <?php foreach ($groups as $gk => [$glabel, $gic, $items]): ?>
      <div class="side-sec"><?= e($glabel) ?></div>
      <?php foreach ($items as $k => [$label, $url, $ic]): ?>
        <a <?= menu_href($url) ?> class="<?= ($active ?? '') === $k ? 'on' : '' ?>" title="<?= e($label) ?>">
          <?= icon($ic, 20) ?><span class="lbl"><?= e($label) ?></span>
          <?php if ($k === 'index' && bisa('station')): ?><span class="pill" data-live-count <?= $nBaru ? '' : 'hidden' ?>><?= $nBaru ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
  <div class="side-bottom">
    <a href="<?= base_url('logout.php') ?>" data-confirm="Keluar dari aplikasi?" title="Keluar"><?= icon('out', 20) ?><span class="lbl">Keluar</span></a>
  </div>
</div></aside>
<div class="main-col">
<header class="topbar desk">
  <button type="button" class="tb-btn" id="sideToggle" aria-label="Tampilkan / sembunyikan menu"><?= icon('menu', 20) ?></button>
  <nav class="crumb">
    <a href="<?= base_url() ?>" aria-label="Beranda"><?= icon('home', 16) ?></a>
    <?php if ($crumbGroup && !in_array($crumbGroup, ['Utama'], true)): ?><span>/</span><span class="muted"><?= e($crumbGroup) ?></span><?php endif; ?>
    <span>/</span><b><?= e($pageTitle ?? '') ?></b>
  </nav>
  <span class="sp"></span>
  <span class="clock" title="Waktu server"><?= icon('clock', 18) ?><b id="clock" data-tz="<?= e(date_default_timezone_get()) ?>"><?= date('H:i') ?></b><small><?= e(zona()) ?></small></span>
  <button type="button" class="tb-btn" data-install-any title="Pasang aplikasi di perangkat ini"><?= icon('phone', 20) ?></button>
  <?php if (bisa('station')): ?>
  <a class="tb-btn" href="<?= base_url() ?>" title="Panggilan belum ditangani"><?= icon('bell', 20) ?><span class="count" data-live-count <?= $nBaru ? '' : 'hidden' ?>><?= $nBaru ?></span></a>
  <?php endif; ?>
  <details class="tb-user">
    <summary><span class="avatar"><?= e(inisial($u['nama'])) ?></span><span class="who"><b><?= e($u['nama']) ?></b><small><?= e((ROLE_LABEL[role()] ?? 'User')) ?><?= $u['ruang'] ? ' · ' . e($u['ruang']) : '' ?></small></span><?= icon('down', 16) ?></summary>
    <div class="drop">
      <a href="<?= base_url('profil.php') ?>"><?= icon('user', 18) ?> Profil &amp; Password</a>
      <?php if (bisa('master')): ?><a href="<?= base_url('tampilan.php') ?>"><?= icon('palette', 18) ?> Tampilan &amp; Tema</a><?php endif; ?>
      <a href="#" data-tentang><?= icon('info', 18) ?> Tentang Aplikasi</a>
      <a href="<?= base_url('logout.php') ?>" data-confirm="Keluar dari aplikasi?"><?= icon('out', 18) ?> Keluar</a>
    </div>
  </details>
</header>

<header class="m-hero mob">
  <div class="top">
    <?php if (!empty($back)): ?>
      <a class="back" href="<?= e($back) ?>" aria-label="Kembali"><?= icon('back') ?></a>
    <?php else: ?>
      <div class="logo"><?= logo_svg() ?></div>
    <?php endif; ?>
    <div class="t"><b><?= e(APP_NAME) ?></b><span><?= e(setting('nama_rs')) ?></span></div>
    <?php if (bisa('station')): ?>
    <a class="bell" href="<?= base_url() ?>" aria-label="Panggilan baru"><?= icon('bell') ?><span class="count" data-live-count <?= $nBaru ? '' : 'hidden' ?>><?= $nBaru ?></span></a>
    <?php endif; ?>
  </div>
  <?php if (($active ?? '') === 'index'): ?>
    <div class="greet">Halo,<b><?= e($u['nama']) ?></b><?= e((ROLE_LABEL[role()] ?? 'User')) ?><?= $u['ruang'] ? ' · Ruang ' . e($u['ruang']) : '' ?></div>
  <?php else: ?>
    <h1><?= e($pageTitle ?? '') ?></h1>
  <?php endif; ?>
</header>

<main class="wrap-wide">
<?php if ($f = flash()): ?><div class="alert <?= e($f[1]) ?>"><?= e($f[0]) ?></div><?php endif;
}

function layout_bottom(): void {
    global $active;
    $tabs = [['index', 'Dashboard', 'index.php', 'home'], ['admisi', 'Admisi', 'admisi.php', 'list'], ['ranap', 'Rawat Inap', 'ranap.php', 'bed'], ['menu', 'Menu', 'menu.php', 'grid']];
    $inMenu = !in_array($active ?? '', array_column($tabs, 0), true);
    ?>
</main>
<footer class="app-foot desk">
  <span>© <?= date('Y') ?> <?= e(setting('nama_rs')) ?> · <?= e(APP_NAME) ?> Digital. Hak cipta dilindungi.</span>
  <span><?= e(APP_FOOTER) ?></span>
</footer>
</div><!-- /.main-col -->
</div><!-- /.shell -->
<nav class="tabbar mob">
  <?php foreach ($tabs as [$k, $label, $url, $ic]): $on = ($active ?? '') === $k || ($k === 'menu' && $inMenu); ?>
    <?php if ($ic === 'plus'): ?>
      <a href="<?= base_url($url) ?>" class="fab <?= $on ? 'on' : '' ?>"><i><?= icon('plus', 26) ?></i><?= e($label) ?></a>
    <?php else: ?>
      <a href="<?= base_url($url) ?>" class="<?= $on ? 'on' : '' ?>"><?= icon($ic) ?><?= e($label) ?>
        <?php if ($k === 'index' && bisa('station')): ?><span class="count" data-live-count hidden></span><?php endif; ?></a>
    <?php endif; ?>
  <?php endforeach; ?>
</nav>
<!-- Modal Tentang Aplikasi (landscape, satu halaman tanpa scroll) -->
<div class="ab-modal" id="tentang" hidden role="dialog" aria-modal="true" aria-labelledby="abJudul">
  <div class="ab-box">
    <button type="button" class="ab-x" data-ab-tutup aria-label="Tutup">&times;</button>
    <aside class="ab-side">
      <span class="ab-logo"><?= logo_svg(50) ?></span>
      <h2 id="abJudul">Nurse Call Digital</h2>
      <div class="ab-ver">Sistem Panggilan Perawat<br>Terintegrasi SIMRS Khanza</div>
      <span class="ab-tag">Versi 1.0</span>
      <ul class="ab-fitur">
        <li>Scan QR Code gelang pasien</li>
        <li>Notifikasi &amp; alarm real-time</li>
        <li>Pesan suara dari pasien</li>
        <li>Display TV ruang perawatan</li>
      </ul>
      <div class="ab-side-foot">© <?= date('Y') ?> Nurse Call Digital<br>www.fixdigitech.com</div>
    </aside>
    <div class="ab-main">
      <section>
        <h3>Tentang Aplikasi</h3>
        <p>Nurse Call Digital merupakan sistem panggilan perawat berbasis web yang terintegrasi langsung dengan database SIMRS Khanza. Pasien maupun keluarga cukup memindai QR Code pada gelang untuk menyampaikan kebutuhan, kemudian permintaan tersebut diterima perawat secara real-time melalui notifikasi, alarm, serta layar display di ruang perawatan.</p>
      </section>
      <section class="ab-rule">
        <h3>Ketentuan Penggunaan</h3>
        <p>Aplikasi ini disediakan secara <b>gratis</b> bagi rumah sakit dan fasilitas kesehatan pengguna SIMRS Khanza sebagai bentuk kontribusi dalam peningkatan mutu pelayanan keperawatan. Aplikasi ini <b>dilarang diperjualbelikan, disewakan, atau dimanfaatkan untuk mencari keuntungan pribadi</b> dalam bentuk apa pun.</p>
      </section>
      <section>
        <h3>Dukungan Pengembangan</h3>
        <p>Bagi Bapak/Ibu yang ingin berdonasi atau berpartisipasi dalam pengembangan aplikasi ini, dukungan dapat disalurkan melalui:</p>
        <div class="ab-don">
          <div class="ab-item"><span class="ab-bank bsi">BSI</span><div><b class="ab-no">7134197557</b><small>a.n. M. Wira Satria Buana</small></div>
            <button type="button" class="ab-copy" data-salin="7134197557" title="Salin nomor rekening"><?= icon('copy', 15) ?><span>Salin</span></button></div>
          <div class="ab-item"><span class="ab-bank gopay">GoPay</span><div><b class="ab-no">082177846209</b><small>a.n. M. Wira Satria Buana</small></div>
            <button type="button" class="ab-copy" data-salin="082177846209" title="Salin nomor GoPay"><?= icon('copy', 15) ?><span>Salin</span></button></div>
        </div>
      </section>
      <div class="ab-thanks">Terima kasih atas dukungan dan kepercayaan Bapak/Ibu. <span><?= e(APP_FOOTER) ?></span></div>
    </div>
  </div>
</div>
<style>/* Modal Tentang Aplikasi — CSS & JS disertakan di sini agar selalu jalan (tidak tergantung cache app.css / app.js) */
.ab-modal{position:fixed;inset:0;background:rgba(15,20,30,.5);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);display:grid;place-items:center;z-index:130;padding:20px}
.ab-modal[hidden]{display:none}
.ab-box{position:relative;display:grid;grid-template-columns:290px minmax(0,1fr);width:100%;max-width:940px;max-height:calc(100vh - 40px);background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.28);animation:pop .25s ease-out}
.ab-x{position:absolute;top:12px;right:14px;z-index:2;border:0;background:var(--bg,#f1f4f3);color:var(--muted,#6b7385);width:34px;height:34px;border-radius:50%;font-size:22px;line-height:1;cursor:pointer}
.ab-x:hover{background:var(--pri-soft);color:var(--pri-2)}
.ab-side{display:flex;flex-direction:column;align-items:center;text-align:center;padding:30px 24px 22px;color:#fff;background:linear-gradient(160deg,var(--pri),var(--pri-2))}
.ab-logo{display:grid;place-items:center;width:74px;height:74px;border-radius:20px;background:#fff;box-shadow:0 8px 20px rgba(0,0,0,.15)}
.ab-side h2{margin:14px 0 4px;font-size:21px;color:#fff;letter-spacing:.01em}
.ab-ver{font-size:13px;opacity:.9;line-height:1.45}
.ab-tag{margin-top:10px;font-size:11.5px;font-weight:700;letter-spacing:.06em;background:rgba(255,255,255,.18);border-radius:99px;padding:3px 12px}
.ab-fitur{list-style:none;margin:22px 0 0;padding:0;width:100%;text-align:left;display:grid;gap:9px;font-size:13px}
.ab-fitur li{display:flex;align-items:center;gap:9px;background:rgba(255,255,255,.1);border-radius:10px;padding:8px 11px}
.ab-fitur li::before{content:"";flex:none;width:7px;height:7px;border-radius:50%;background:#fff;opacity:.9}
.ab-side-foot{margin-top:auto;padding-top:18px;font-size:11.5px;opacity:.75;line-height:1.5}
.ab-main{padding:26px 30px 20px;display:flex;flex-direction:column;gap:14px;min-width:0}
.ab-main h3{margin:0 0 5px;font-size:14.5px;color:var(--pri-2);letter-spacing:.02em;display:flex;align-items:center;gap:8px}
.ab-main h3::before{content:"";width:4px;height:15px;border-radius:2px;background:var(--pri)}
.ab-main p{margin:0;font-size:13.5px;line-height:1.6;color:var(--ink,#1d2433);text-align:justify;text-justify:inter-word;hyphens:auto;-webkit-hyphens:auto}
.ab-rule{background:var(--pri-soft);border-radius:12px;padding:12px 16px}
.ab-don{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px}
.ab-item{display:flex;align-items:center;gap:10px;border:1px solid var(--line,#e3e7ef);border-radius:12px;padding:9px 10px;min-width:0}
.ab-item>div{flex:1;min-width:0}
.ab-item small{display:block;color:var(--muted,#6b7385);font-size:11.5px;line-height:1.35}
.ab-no{display:block;font-size:15.5px;letter-spacing:.03em;font-variant-numeric:tabular-nums}
.ab-bank{flex:none;width:50px;height:36px;border-radius:9px;display:grid;place-items:center;font-weight:800;font-size:12px;color:#fff}
.ab-bank.bsi{background:linear-gradient(135deg,#00a39d,#0d7c74)}
.ab-bank.gopay{background:linear-gradient(135deg,#00aed6,#0085b3)}
.ab-copy{flex:none;display:inline-flex;align-items:center;gap:5px;border:1px solid var(--line,#e3e7ef);background:#fff;border-radius:8px;padding:6px 9px;font:inherit;font-size:12px;font-weight:600;color:var(--pri-2);cursor:pointer}
.ab-copy:hover{background:var(--pri-soft)}
.ab-thanks{margin-top:auto;padding-top:12px;border-top:1px solid var(--line,#e3e7ef);font-size:12.5px;font-weight:600;color:var(--ink,#1d2433);display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}
.ab-thanks span{font-weight:400;color:var(--muted,#6b7385)}
@media(max-width:760px){
  .ab-modal{padding:12px}
  .ab-box{grid-template-columns:1fr;max-height:calc(100vh - 24px);overflow:auto}
  .ab-side{flex-direction:row;flex-wrap:wrap;text-align:left;align-items:center;gap:0 12px;padding:18px 54px 16px 18px}
  .ab-logo{width:52px;height:52px;border-radius:14px}.ab-logo svg{width:36px;height:36px}
  .ab-side h2{margin:0;font-size:18px}.ab-side>div.ab-ver{flex-basis:100%;margin-top:6px}
  .ab-tag,.ab-fitur,.ab-side-foot{display:none}
  .ab-x{background:rgba(255,255,255,.2);color:#fff}
  .ab-main{padding:18px 18px 16px}
  .ab-don{grid-template-columns:1fr}
}
</style>
<script>(function () {
  var m = document.getElementById('tentang'); if (!m) return;
  function buka(ev) { if (ev) ev.preventDefault(); m.hidden = false; }
  function tutup() { m.hidden = true; }
  document.addEventListener('click', function (ev) {
    if (ev.target.closest('[data-tentang]')) { var d = ev.target.closest('details'); if (d) d.open = false; buka(ev); return; }
    if (ev.target === m || ev.target.closest('[data-ab-tutup]')) tutup();
    var b = ev.target.closest('[data-salin]');
    if (b) {
      var t = b.getAttribute('data-salin'), asli = b.innerHTML;
      var ok = function () { b.textContent = '✓ Disalin'; setTimeout(function () { b.innerHTML = asli; }, 1600); };
      if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(t).then(ok, function () {});
      else { var i = document.createElement('textarea'); i.value = t; i.style.position = 'fixed'; i.style.opacity = '0'; document.body.appendChild(i); i.select(); try { document.execCommand('copy'); ok(); } catch (e) {} document.body.removeChild(i); }
    }
  });
  document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && !m.hidden) tutup(); });
  if (location.hash === '#tentang') buka();
})();</script>
<?php if (bisa('station')): ?>
<!-- Pop-up alarm panggilan baru (dipakai station.js di semua halaman) -->
<div class="alarm" id="alarm" hidden>
  <div class="box" role="alertdialog" aria-labelledby="alTitle">
    <div class="hd"><span class="bellic"><?= icon('bell', 34) ?></span><h2 id="alTitle">NURSE CALL</h2><p>Pasien membutuhkan bantuan!</p></div>
    <div class="bd">
      <dl class="info">
        <dt>Nama</dt><dd id="alNama"></dd>
        <dt>No. RM</dt><dd id="alRm"></dd>
        <dt>Ruang</dt><dd id="alRuang"></dd>
        <dt>Keluhan</dt><dd id="alKel"></dd>
        <dt>Pesan</dt><dd id="alPesan"></dd>
        <dt>Waktu</dt><dd id="alWaktu"></dd>
      </dl>
      <div id="alVoice" hidden></div>
    </div>
    <div class="more" id="alMore" hidden></div>
    <div class="ft">
      <button class="btn ok" id="alTangani"><?= icon('run', 18) ?> Tandai Ditangani</button>
      <button class="btn light" id="alTutup">Tutup</button>
    </div>
  </div>
</div>
<?php endif;
    require __DIR__ . '/foot.php';
}

function empty_box(string $msg, string $ic = 'bell'): string {
    return '<div class="empty">' . icon($ic, 44) . '<div>' . e($msg) . '</div></div>';
}

/** Navigasi halaman */
function pager(int $page, int $pages, callable $qs): void {
    if ($pages <= 1) return;
    echo '<div class="pager">';
    if ($page > 1) echo '<a href="?' . $qs(['page' => $page - 1]) . '">‹</a>';
    for ($i = max(1, $page - 3); $i <= min($pages, $page + 3); $i++)
        echo $i === $page ? '<span class="on">' . $i . '</span>' : '<a href="?' . $qs(['page' => $i]) . '">' . $i . '</a>';
    if ($page < $pages) echo '<a href="?' . $qs(['page' => $page + 1]) . '">›</a>';
    echo '</div>';
}

/** Buat fungsi query-string yang mempertahankan filter */
function qs_builder(array $f): callable {
    return function (array $x = []) use ($f) {
        return http_build_query(array_filter(array_merge($f, $x), function ($v) { return $v !== '' && $v !== 0 && $v !== null; }));
    };
}