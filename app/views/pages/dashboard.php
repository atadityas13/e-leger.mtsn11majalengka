<?php
require dirname(__DIR__) . '/partials/header.php';

$setting = setting_akademik();
$targetSemester = semester_upload_target($setting['semester_aktif']);

$stmt = db()->query("SELECT current_semester, COUNT(*) total FROM siswa WHERE status_siswa='Aktif' GROUP BY current_semester");
$statsSemester = $stmt->fetchAll();

$stmt = db()->prepare("SELECT semester, COUNT(*) jumlah FROM nilai_rapor WHERE tahun_ajaran=:ta GROUP BY semester");
$stmt->execute(['ta' => $setting['tahun_ajaran']]);
$uploadStats = $stmt->fetchAll();

$aktif = db()->query("SELECT COUNT(*) c FROM siswa WHERE status_siswa='Aktif'")->fetch()['c'] ?? 0;
$nonaktif = db()->query("SELECT COUNT(*) c FROM siswa WHERE status_siswa='Tidak Melanjutkan'")->fetch()['c'] ?? 0;
$lulus = db()->query("SELECT COUNT(*) c FROM siswa WHERE status_siswa='Lulus'")->fetch()['c'] ?? 0;
?>
<div class="grid-3">
    <div class="card"><h3>Siswa Aktif</h3><strong><?= e((string) $aktif) ?></strong></div>
    <div class="card"><h3>Tidak Melanjutkan</h3><strong><?= e((string) $nonaktif) ?></strong></div>
    <div class="card"><h3>Lulus</h3><strong><?= e((string) $lulus) ?></strong></div>
</div>

<div class="card">
    <h3>Statistik Siswa Aktif per Current Semester</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Semester</th><th>Jumlah</th></tr></thead>
            <tbody>
            <?php foreach ($statsSemester as $row): ?>
                <tr><td><?= e((string) $row['current_semester']) ?></td><td><?= e((string) $row['total']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h3>Status Upload Tahun Ajaran <?= e($setting['tahun_ajaran']) ?> (Target <?= e($setting['semester_aktif']) ?>)</h3>
    <p>Semester target upload saat ini: <?= e(implode(', ', $targetSemester)) ?></p>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Semester</th><th>Jumlah Entri Nilai</th></tr></thead>
            <tbody>
            <?php foreach ($uploadStats as $row): ?>
                <tr><td><?= e((string) $row['semester']) ?></td><td><?= e((string) $row['jumlah']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php';
