<?php
/**
 * ========================================================
 * TRACER MTSN 11 MAJALENGKA
 * ========================================================
 * File: Upload Token Management Page
 * ========================================================
 */

// Hanya admin/kurikulum yang bisa akses
require_login();
if (!in_array(current_user()['role'] ?? '', ['admin', 'kurikulum'])) {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect('index.php?page=dashboard');
}

$setting = setting_akademik();

// Bersihkan token yang sudah kedaluwarsa
try {
    db()->exec("DELETE FROM upload_token WHERE expires_at IS NOT NULL AND expires_at < NOW()");
} catch (Exception $e) { /* ignore */ }

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforce_csrf('upload-token-management');

    $action = strtolower(trim((string) ($_POST['action'] ?? '')));

    if ($action === 'toggle_require') {
        $row = db()->query("SELECT require_upload_token FROM pengaturan_akademik WHERE is_aktif=1 LIMIT 1")->fetch();
        $newState = (int) !(($row['require_upload_token'] ?? 0) == 1);
        $stmt = db()->prepare("UPDATE pengaturan_akademik SET require_upload_token = ? WHERE is_aktif=1");
        $stmt->execute([$newState]);
        set_flash('success', $newState ? 'Verifikasi token <strong>diaktifkan</strong>.' : 'Verifikasi token <strong>dinonaktifkan</strong>.');
        redirect('index.php?page=upload-token-management');
    }

    if ($action === 'change_mode') {
        $newMode = strtolower(trim((string) ($_POST['mode'] ?? '')));
        if (in_array($newMode, ['daily', 'manual'])) {
            $stmt = db()->prepare("UPDATE pengaturan_akademik SET token_mode = ? WHERE is_aktif=1");
            $stmt->execute([$newMode]);
            set_flash('success', 'Mode token diubah ke <strong>' . ($newMode === 'daily' ? 'Otomatis' : 'Manual') . '</strong>.');
            redirect('index.php?page=upload-token-management');
        }
    }

    if ($action === 'generate_manual') {
        $rawToken = preg_replace('/\D/', '', trim((string) ($_POST['token_value'] ?? '')));
        $hours = max(1, min(720, (int) ($_POST['expiry_hours'] ?? 24)));
        $tokenValue = $rawToken !== '' ? $rawToken : str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        if (!preg_match('/^\d{6}$/', $tokenValue)) {
            set_flash('error', 'Token harus tepat 6 digit angka.');
            redirect('index.php?page=upload-token-management');
        }

        $check = db()->prepare("
            SELECT id FROM upload_token
            WHERE token = :tok
            AND created_tahun_ajaran = :ta
            AND created_semester_aktif = :sem
            AND (expires_at IS NULL OR expires_at > NOW())
            AND is_used = 0
            LIMIT 1
        ");
        $check->execute([
            'tok' => $tokenValue,
            'ta' => $setting['tahun_ajaran'],
            'sem' => $setting['semester_aktif']
        ]);
        if ($check->fetch()) {
            set_flash('error', 'Token tersebut sudah aktif. Gunakan 6 digit angka yang berbeda.');
            redirect('index.php?page=upload-token-management');
        }

        $expiresAt = date('Y-m-d H:i:s', time() + $hours * 3600);
        $stmt = db()->prepare("
            INSERT INTO upload_token (token, token_type, created_by, expires_at, created_tahun_ajaran, created_semester_aktif, ip_address, user_agent)
            VALUES (:token, 'manual', :creator, :expires, :ta, :sem, :ip, :ua)
        ");
        $stmt->execute([
            'token' => $tokenValue,
            'creator' => current_user()['username'] ?? 'system',
            'expires' => $expiresAt,
            'ta' => $setting['tahun_ajaran'],
            'sem' => $setting['semester_aktif'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255),
        ]);
        set_flash('success', "Token <strong>" . e($tokenValue) . "</strong> berhasil dibuat (berlaku $hours jam).");
        redirect('index.php?page=upload-token-management');
    }

    redirect('index.php?page=upload-token-management');
}

// Load settings
$row = db()->query("SELECT require_upload_token, token_mode FROM pengaturan_akademik WHERE is_aktif=1 LIMIT 1")->fetch();
$requireToken = ($row['require_upload_token'] ?? 0) == 1;
$tokenMode    = in_array($row['token_mode'] ?? '', ['daily', 'manual']) ? $row['token_mode'] : 'daily';

// Ambil token aktif beserta waktu kedaluwarsa
$stmtToken = db()->prepare("
    SELECT token, expires_at FROM upload_token
    WHERE created_tahun_ajaran = :ta
    AND created_semester_aktif = :sem
    AND (expires_at IS NULL OR expires_at > NOW())
    AND is_used = 0
    ORDER BY created_at DESC
    LIMIT 1
");
$stmtToken->execute(['ta' => $setting['tahun_ajaran'], 'sem' => $setting['semester_aktif']]);
$activeTokenRow = $stmtToken->fetch() ?: null;

// Mode otomatis: buat token harian jika belum ada
if ($tokenMode === 'daily' && !$activeTokenRow) {
    generate_upload_token('daily', current_user()['username'] ?? 'system', 24);
    $stmtToken2 = db()->prepare("
        SELECT token, expires_at FROM upload_token
        WHERE created_tahun_ajaran = :ta
        AND created_semester_aktif = :sem
        AND (expires_at IS NULL OR expires_at > NOW())
        AND is_used = 0
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmtToken2->execute(['ta' => $setting['tahun_ajaran'], 'sem' => $setting['semester_aktif']]);
    $activeTokenRow = $stmtToken2->fetch() ?: null;
}

$currentToken   = $activeTokenRow ? $activeTokenRow['token'] : null;
$tokenExpiresAt = ($activeTokenRow && $activeTokenRow['expires_at']) ? $activeTokenRow['expires_at'] : null;

require dirname(__DIR__) . '/partials/header.php';
?>

<div class="card border-0 shadow-sm" style="max-width:580px;margin:0 auto;">
    <div class="card-header bg-white border-bottom py-3">
        <h5 class="mb-0 fw-semibold">Manajemen Token Upload</h5>
    </div>
    <div class="card-body p-4">

        <!-- Toggle Aktif / Nonaktif -->
        <div class="d-flex align-items-center justify-content-between p-3 rounded bg-light mb-4">
            <div>
                <div class="fw-semibold">Verifikasi Token</div>
                <div class="small mt-1">
                    <?php if ($requireToken): ?>
                        <span class="badge bg-success">AKTIF</span>
                        <span class="text-secondary">– Token diperlukan untuk upload</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">NONAKTIF</span>
                        <span class="text-secondary">– Upload tanpa token</span>
                    <?php endif; ?>
                </div>
            </div>
            <form method="post">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="toggle_require">
                <button type="submit" class="btn btn-sm <?= $requireToken ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                    <?= $requireToken ? 'Nonaktifkan' : 'Aktifkan' ?>
                </button>
            </form>
        </div>

        <!-- Pilih Mode -->
        <div class="mb-4">
            <div class="fw-semibold mb-2">Mode Token</div>
            <div class="d-flex gap-2">
                <form method="post">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="change_mode">
                    <input type="hidden" name="mode" value="daily">
                    <button type="submit" class="btn btn-sm <?= $tokenMode === 'daily' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        Otomatis
                    </button>
                </form>
                <form method="post">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="change_mode">
                    <input type="hidden" name="mode" value="manual">
                    <button type="submit" class="btn btn-sm <?= $tokenMode === 'manual' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        Manual
                    </button>
                </form>
            </div>
            <div class="small text-secondary mt-2">
                <?php if ($tokenMode === 'daily'): ?>
                    Mode Otomatis: token harian dibuat otomatis, berlaku 24 jam.
                <?php else: ?>
                    Mode Manual: buat token sendiri dengan nilai dan masa berlaku yang ditentukan.
                <?php endif; ?>
            </div>
        </div>

        <hr>

        <!-- Tampilan Token -->
        <?php if ($currentToken): ?>
            <div class="text-center py-3">
                <div class="small text-secondary mb-1">Token Aktif</div>
                <div class="fs-2 font-monospace fw-bold mb-2" style="letter-spacing:.15em"><?= e($currentToken) ?></div>
                <?php if ($tokenExpiresAt): ?>
                    <div class="small text-secondary mb-3">
                        Berakhir <?= e(date('d M Y, H:i', strtotime($tokenExpiresAt))) ?>
                        &nbsp;·&nbsp;<span id="tokenCountdown" class="fw-semibold"></span>
                    </div>
                <?php endif; ?>
                <button class="btn btn-sm btn-outline-primary" onclick="copyToken('<?= e($currentToken) ?>')">
                    <i class="bi bi-clipboard"></i> Salin Token
                </button>
            </div>
        <?php else: ?>
            <div class="text-center text-secondary py-4">
                <i class="bi bi-shield-x fs-1 d-block mb-2 opacity-50"></i>
                Tidak ada token aktif
            </div>
        <?php endif; ?>

        <!-- Tombol Buat Token (mode manual saja) -->
        <?php if ($tokenMode === 'manual'): ?>
            <div class="text-center mt-3">
                <button type="button" class="btn btn-primary btn-sm"
                        data-bs-toggle="modal" data-bs-target="#modalBuatToken">
                    <i class="bi bi-plus-lg"></i> Buat Token
                </button>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- Modal Buat Token Manual -->
<div class="modal fade" id="modalBuatToken" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Buat Token Manual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="generate_manual">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Nilai Token
                            <span class="text-secondary fw-normal small">(kosongkan untuk generate otomatis)</span>
                        </label>
                           <input type="text" name="token_value" class="form-control font-monospace"
                               inputmode="numeric" maxlength="6" placeholder="Contoh: 123456"
                               oninput="this.value=this.value.replace(/\D/g,'').slice(0,6)">
                           <div class="form-text">Harus 6 digit angka.</div>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Masa Berlaku</label>
                        <select name="expiry_hours" class="form-select">
                            <option value="1">1 Jam</option>
                            <option value="6">6 Jam</option>
                            <option value="12">12 Jam</option>
                            <option value="24" selected>24 Jam (1 Hari)</option>
                            <option value="48">2 Hari</option>
                            <option value="72">3 Hari</option>
                            <option value="168">1 Minggu</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Buat Token</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($tokenExpiresAt): ?>
<script>
(function () {
    const expiresAt = <?= json_encode(strtotime($tokenExpiresAt) * 1000) ?>;
    const el = document.getElementById('tokenCountdown');
    if (!el) return;

    function tick() {
        const diff = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
        if (diff === 0) {
            el.textContent = 'Token habis';
            el.style.color = '#dc3545';
            return;
        }
        const h = Math.floor(diff / 3600);
        const m = Math.floor((diff % 3600) / 60);
        const s = diff % 60;
        el.textContent = (h ? h + 'j ' : '') + String(m).padStart(2, '0') + 'm ' + String(s).padStart(2, '0') + 'd';
        el.style.color = diff < 3600 ? '#fd7e14' : '#198754';
    }
    tick();
    setInterval(tick, 1000);
})();
</script>
<?php endif; ?>

<script>
function copyToken(text) {
    const ok = () => {
        if (window.Swal) Swal.fire({ icon: 'success', title: 'Token disalin!', timer: 1200, showConfirmButton: false });
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(ok).catch(() => fallbackCopy(text, ok));
    } else {
        fallbackCopy(text, ok);
    }
}
function fallbackCopy(text, cb) {
    const ta = document.createElement('textarea');
    ta.value = text;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    if (cb) cb();
}
</script>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
