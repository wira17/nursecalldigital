<?php
require __DIR__ . '/inc/bootstrap.php';
unset($_SESSION['uid'], $_SESSION['ruang_pantau']);
session_regenerate_id(true);
redirect(base_url('login.php'));
