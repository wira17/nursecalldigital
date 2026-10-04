<?php if (empty($noInstall)): ?>
<div class="install-bar" id="installBar">
  <span>Pasang <b><?= e(APP_NAME) ?></b> di perangkat ini agar lebih cepat dibuka.</span>
  <button class="btn sm" id="installBtn">Pasang</button>
  <button class="btn sm light" id="installClose" aria-label="Tutup">✕</button>
</div>
<?php endif; ?>
<script>window.APP_BASE = <?= json_encode(base_url()) ?>;</script>
<script src="<?= aset('assets/js/app.js') ?>"></script>
<?php if (is_login() && bisa('station')): ?>
<script>window.NC = <?= json_encode(['csrf' => csrf_token(), 'ruang' => ruang_pantau(), 'batas' => (int)setting('batas_respon', '5') * 60]) ?>;</script>
<script src="<?= aset('assets/js/codeblue.js') ?>"></script>
<script src="<?= aset('assets/js/station.js') ?>"></script>
<?php endif; ?>
<?php foreach ((array)($GLOBALS['extraJs'] ?? []) as $js): ?><script src="<?= aset($js) ?>"></script><?php endforeach; ?>
</body>
</html>