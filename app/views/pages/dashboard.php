<?php
/**
 * ========================================================
 * E-LEGER MTSN 11 MAJALENGKA
 * ========================================================
 * 
 * Sistem Manajemen Data Nilai Siswa
 * MTsN 11 Majalengka, Kabupaten Majalengka, Jawa Barat
 * 
 * File: Dashboard Home Page
 * Deskripsi: Halaman utama dashboard dengan overview statistik dan informasi cepat
 * 
 * @package    E-Leger-MTSN11
 * @author     MTsN 11 Majalengka Development Team
 * @copyright  2026 MTsN 11 Majalengka. All rights reserved.
 * @license    Proprietary License
 * @version    1.0.0
 * @since      2026-01-01
 * @created    2026-03-06
 * @modified   2026-03-06
 * 
 * Features:
 * - Statistik jumlah siswa aktif
 * - Informasi semester akademik aktif
 * - Quick access ke menu utama
 * - Status sistem dan last update
 * 
 * DISCLAIMER:
 * Software ini dikembangkan khusus untuk MTsN 11 Majalengka.
 * Dilarang keras menyalin, memodifikasi, atau mendistribusikan
 * tanpa izin tertulis dari MTsN 11 Majalengka.
 * 
 * CONTACT:
 * Website: https://mtsn11majalengka.sch.id
 * Email: mtsn11majalengka@gmail.com
 * Phone: (0233) 8319182
 * 
 * ========================================================
 */
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
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-secondary small">Siswa Aktif</div>
                <div class="display-6 fw-semibold text-success mb-0"><?= e((string) $aktif) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-secondary small">Tidak Melanjutkan</div>
                <div class="display-6 fw-semibold text-warning mb-0"><?= e((string) $nonaktif) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-secondary small">Lulus</div>
                <div class="display-6 fw-semibold text-primary mb-0"><?= e((string) $lulus) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3">
        <h3 class="mb-0">Statistik Siswa Aktif per Current Semester</h3>
    </div>
    <div class="card-body">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Semester</th><th>Jumlah</th></tr></thead>
                <tbody>
                <?php foreach ($statsSemester as $row): ?>
                    <tr><td><?= e(current_semester_label($row['current_semester'])) ?></td><td><?= e((string) $row['total']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3">
        <h3 class="mb-1">Status Upload Tahun Ajaran <?= e($setting['tahun_ajaran']) ?> (<?= e($setting['semester_aktif']) ?>)</h3>
        <p class="text-secondary mb-0">Semester target upload saat ini: <?= e(implode(', ', $targetSemester)) ?></p>
    </div>
    <div class="card-body">
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
</div>
<?php require dirname(__DIR__) . '/partials/footer.php';
