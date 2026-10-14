<?php
require_once __DIR__ . '/cek_login.php';
$pesanTampil = pesanFlash();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judulHalaman ?? 'Merdeka Kickoff') ?> | Merdeka Kickoff</title>
    <link rel="stylesheet" href="<?= e($akarUrl ?? '') ?>css/style.css">
    <script src="<?= e($akarUrl ?? '') ?>js/script.js" defer></script>
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= e($akarUrl ?? '') ?>index.php" aria-label="Merdeka Kickoff beranda">
            <span class="brand-mark">MK</span>
            <span>Merdeka <strong>Kickoff</strong></span>
        </a>
        <button class="nav-toggle" type="button" aria-label="Buka menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <nav class="main-nav" aria-label="Navigasi utama">
            <a href="<?= e($akarUrl ?? '') ?>index.php">Beranda</a>
            <?php if (sudahLogin()): ?>
                <a href="<?= e($akarUrl ?? '') ?>dashboard.php">Dashboard</a>
                <a href="<?= e($akarUrl ?? '') ?>booking/index.php">Booking</a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="<?= e($akarUrl ?? '') ?>lapangan/index.php">Lapangan</a>
                    <a href="<?= e($akarUrl ?? '') ?>laporan.php">Laporan</a>
                <?php endif; ?>
                <span class="nav-user"><?= e($_SESSION['nama']) ?></span>
                <form class="nav-logout" action="<?= e($akarUrl ?? '') ?>logout.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <button class="button button-small button-outline" type="submit">Logout</button>
                </form>
            <?php else: ?>
                <a href="<?= e($akarUrl ?? '') ?>login.php">Login</a>
                <a class="button button-small" href="<?= e($akarUrl ?? '') ?>register.php">Daftar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="page-main">
    <div class="container">
        <?php if ($pesanTampil): ?>
            <div class="alert alert-<?= e($pesanTampil['jenis'] ?? 'success') ?>" role="status">
                <?= e($pesanTampil['teks'] ?? '') ?>
            </div>
        <?php endif; ?>
