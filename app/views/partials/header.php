<?php
$user = current_user();
$flash = get_flash();
$page = $_GET['page'] ?? 'dashboard';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(app_config('name')) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php if ($user): ?>
        <aside class="sidebar">
            <div class="brand">e-Leger MTsN11</div>
            <nav>
                <a class="<?= $page === 'dashboard' ? 'active' : '' ?>" href="index.php?page=dashboard">Dashboard</a>
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                    <a class="<?= $page === 'users' ? 'active' : '' ?>" href="index.php?page=users">Master Data User</a>
                    <a class="<?= $page === 'mapel' ? 'active' : '' ?>" href="index.php?page=mapel">Master Data Mapel</a>
                    <a class="<?= $page === 'siswa' ? 'active' : '' ?>" href="index.php?page=siswa">Master Data Siswa</a>
                <?php endif; ?>
                <a class="<?= $page === 'nilai-import' ? 'active' : '' ?>" href="index.php?page=nilai-import">Olah Nilai</a>
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                    <a class="<?= $page === 'semester-control' ? 'active' : '' ?>" href="index.php?page=semester-control">Kontrol Semester</a>
                    <a class="<?= $page === 'kelulusan' ? 'active' : '' ?>" href="index.php?page=kelulusan">Kelulusan</a>
                <?php endif; ?>
                <a class="<?= $page === 'laporan' ? 'active' : '' ?>" href="index.php?page=laporan">Laporan & Cetak</a>
            </nav>
            <a class="logout" href="index.php?page=logout">Keluar</a>
        </aside>
    <?php endif; ?>

    <main class="main-content">
        <?php if ($user): ?>
            <header class="topbar">
                <div><?= e($user['nama_lengkap']) ?> (<?= e(strtoupper($user['role'])) ?>)</div>
                <?php $set = setting_akademik(); ?>
                <div><?= e($set['tahun_ajaran']) ?> - <?= e($set['semester_aktif']) ?></div>
            </header>
        <?php endif; ?>

        <?php if ($flash): ?>
            <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
