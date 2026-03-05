<?php
$user = current_user();
$flash = get_flash();
$page = $_GET['page'] ?? 'dashboard';
$isAdmin = ($user['role'] ?? '') === 'admin';
$flashClass = (($flash['type'] ?? '') === 'success') ? 'alert-success' : 'alert-danger';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(app_config('name')) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-body-tertiary">
<div class="app-shell">
    <?php if ($user): ?>
        <aside class="sidebar">
            <div class="brand-wrap mb-3">
                <div class="small text-white-50">e-Leger</div>
                <div class="h5 mb-0 text-white fw-semibold">MTsN 11 Majalengka</div>
            </div>

            <div class="menu-label">Menu Utama</div>
            <nav class="nav flex-column gap-1 mb-3">
                <a class="sidebar-link <?= $page === 'dashboard' ? 'active' : '' ?>" href="index.php?page=dashboard">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <?php if ($isAdmin): ?>
                    <a class="sidebar-link <?= $page === 'users' ? 'active' : '' ?>" href="index.php?page=users">
                        <i class="bi bi-people"></i> Data User
                    </a>
                    <a class="sidebar-link <?= $page === 'mapel' ? 'active' : '' ?>" href="index.php?page=mapel">
                        <i class="bi bi-journal-bookmark"></i> Data Mapel
                    </a>
                    <a class="sidebar-link <?= $page === 'siswa' ? 'active' : '' ?>" href="index.php?page=siswa">
                        <i class="bi bi-person-vcard"></i> Data Siswa
                    </a>
                <?php endif; ?>

                <a class="sidebar-link <?= $page === 'nilai-import' ? 'active' : '' ?>" href="index.php?page=nilai-import">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Olah Nilai
                </a>
                <?php if ($isAdmin): ?>
                    <a class="sidebar-link <?= $page === 'semester-control' ? 'active' : '' ?>" href="index.php?page=semester-control">
                        <i class="bi bi-calendar2-check"></i> Kontrol Semester
                    </a>
                    <a class="sidebar-link <?= $page === 'kelulusan' ? 'active' : '' ?>" href="index.php?page=kelulusan">
                        <i class="bi bi-mortarboard"></i> Kelulusan
                    </a>
                <?php endif; ?>
                <a class="sidebar-link <?= $page === 'laporan' ? 'active' : '' ?>" href="index.php?page=laporan">
                    <i class="bi bi-printer"></i> Laporan & Cetak
                </a>
            </nav>

            <div class="mt-auto pt-2 border-top border-success-subtle">
                <a class="btn btn-outline-light w-100" href="index.php?page=logout">
                    <i class="bi bi-box-arrow-right"></i> Keluar
                </a>
            </div>
        </aside>
    <?php endif; ?>

    <main class="main-content">
        <?php if ($user): ?>
            <header class="topbar card shadow-sm border-0 mb-3">
                <?php $set = setting_akademik(); ?>
                <div>
                    <div class="fw-semibold"><?= e($user['nama_lengkap']) ?></div>
                    <small class="text-secondary">Role: <?= e(strtoupper($user['role'])) ?></small>
                </div>
                <div class="text-lg-end">
                    <span class="badge text-bg-success-subtle text-success-emphasis border border-success-subtle">
                        Tahun Ajaran <?= e($set['tahun_ajaran']) ?>
                    </span>
                    <span class="badge text-bg-primary-subtle text-primary-emphasis border border-primary-subtle ms-1">
                        <?= e($set['semester_aktif']) ?>
                    </span>
                </div>
            </header>
        <?php endif; ?>

        <?php if ($flash): ?>
            <div class="alert <?= e($flashClass) ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
