<?php
/**
 * ========================================================
 * TRACER MTSN 11 MAJALENGKA
 * ========================================================
 * 
 * Sistem Manajemen Data Nilai Siswa
 * MTsN 11 Majalengka, Kabupaten Majalengka, Jawa Barat
 * 
 * File: Dashboard Home Page
 * Deskripsi: Halaman utama dashboard dengan overview statistik dan informasi cepat
 * 
 * @package    TRACER-MTSN11
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
$uploadTokenSetting = get_upload_token_setting();

try {
    db()->exec("DELETE FROM upload_token WHERE expires_at IS NOT NULL AND expires_at < NOW()");
} catch (Exception $e) {
}

$activeTokenRow = null;
if ($uploadTokenSetting['require_token']) {
    $stmtToken = db()->prepare("\n        SELECT token, expires_at, token_type\n        FROM upload_token\n        WHERE created_tahun_ajaran = :ta\n        AND created_semester_aktif = :sem\n        AND (expires_at IS NULL OR expires_at > NOW())\n        AND is_used = 0\n        ORDER BY created_at DESC\n        LIMIT 1\n    ");
    $stmtToken->execute([
        'ta' => $setting['tahun_ajaran'],
        'sem' => $setting['semester_aktif'],
    ]);
    $activeTokenRow = $stmtToken->fetch() ?: null;

    if (($uploadTokenSetting['token_mode'] ?? 'daily') === 'daily' && !$activeTokenRow) {
        generate_upload_token('daily', current_user()['username'] ?? 'system', 24);
        $stmtToken->execute([
            'ta' => $setting['tahun_ajaran'],
            'sem' => $setting['semester_aktif'],
        ]);
        $activeTokenRow = $stmtToken->fetch() ?: null;
    }
}

$dashboardToken = $activeTokenRow['token'] ?? null;
$dashboardTokenExpiry = $activeTokenRow['expires_at'] ?? null;

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
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-secondary small">Siswa Aktif</div>
                <div class="display-6 fw-semibold text-success mb-0"><?= e((string) $aktif) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-secondary small">Tidak Melanjutkan</div>
                <div class="display-6 fw-semibold text-warning mb-0"><?= e((string) $nonaktif) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-secondary small">Lulus</div>
                <div class="display-6 fw-semibold text-primary mb-0"><?= e((string) $lulus) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-secondary small mb-2">Token Upload</div>
                <?php if (!$uploadTokenSetting['require_token']): ?>
                    <div class="fw-semibold text-secondary mb-1">Verifikasi nonaktif</div>
                    <div class="small text-secondary">Upload tidak memerlukan token</div>
                <?php elseif ($dashboardToken): ?>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div class="fs-5 fw-semibold font-monospace mb-0"><?= e($dashboardToken) ?></div>
                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" onclick="copyDashboardToken('<?= e($dashboardToken) ?>')" aria-label="Salin token">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                    <div class="small text-secondary">
                        Masa berlaku
                        <?php if ($dashboardTokenExpiry): ?>
                            <br><?= e(date('d M Y H:i', strtotime($dashboardTokenExpiry))) ?>
                            <br><span id="dashboardTokenCountdown" class="fw-semibold"></span>
                        <?php else: ?>
                            <br>Tidak dibatasi
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="fw-semibold text-secondary mb-1">Token belum tersedia</div>
                    <div class="small text-secondary">Buat token manual di menu manajemen token</div>
                <?php endif; ?>
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
<?php if ($dashboardToken && $dashboardTokenExpiry): ?>
<script>
(function () {
    const expiresAt = <?= json_encode(strtotime($dashboardTokenExpiry) * 1000) ?>;
    const countdownNode = document.getElementById('dashboardTokenCountdown');
    if (!countdownNode) {
        return;
    }

    function renderCountdown() {
        const diff = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
        if (diff === 0) {
            countdownNode.textContent = 'Token belum tersedia';
            countdownNode.classList.add('text-danger');
            return;
        }

        const hours = Math.floor(diff / 3600);
        const minutes = Math.floor((diff % 3600) / 60);
        const seconds = diff % 60;
        countdownNode.textContent = (hours ? hours + 'j ' : '') + String(minutes).padStart(2, '0') + 'm ' + String(seconds).padStart(2, '0') + 'd';
    }

    renderCountdown();
    setInterval(renderCountdown, 1000);
})();

function copyDashboardToken(text) {
    const afterCopy = () => {
        if (window.Swal) {
            Swal.fire({
                icon: 'success',
                title: 'Token disalin',
                timer: 1200,
                showConfirmButton: false
            });
        }
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(afterCopy).catch(() => fallbackCopyDashboardToken(text, afterCopy));
        return;
    }

    fallbackCopyDashboardToken(text, afterCopy);
}

function fallbackCopyDashboardToken(text, callback) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    if (callback) {
        callback();
    }
}
</script>
<?php elseif ($dashboardToken): ?>
<script>
function copyDashboardToken(text) {
    const afterCopy = () => {
        if (window.Swal) {
            Swal.fire({
                icon: 'success',
                title: 'Token disalin',
                timer: 1200,
                showConfirmButton: false
            });
        }
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(afterCopy).catch(() => fallbackCopyDashboardToken(text, afterCopy));
        return;
    }

    fallbackCopyDashboardToken(text, afterCopy);
}

function fallbackCopyDashboardToken(text, callback) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    if (callback) {
        callback();
    }
}
</script>
<?php endif; ?>
<?php require dirname(__DIR__) . '/partials/footer.php';
