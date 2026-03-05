<?php
use PhpOffice\PhpSpreadsheet\IOFactory;

$setting = setting_akademik();
$semesterAktif = strtoupper($setting['semester_aktif']);
$targetRapor = semester_upload_target($semesterAktif);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforce_csrf('nilai-import');

    if (!class_exists(IOFactory::class)) {
        set_flash('error', 'PhpSpreadsheet belum terpasang. Jalankan composer install.');
        redirect('index.php?page=nilai-import');
    }

    $action = $_POST['action'] ?? '';
    $mapelId = (int) ($_POST['mapel_id'] ?? 0);
    $tmp = $_FILES['file_excel']['tmp_name'] ?? '';

    if (!$mapelId || !$tmp) {
        set_flash('error', 'Mapel dan file wajib diisi.');
        redirect('index.php?page=nilai-import');
    }

    $spreadsheet = IOFactory::load($tmp);
    $rows = $spreadsheet->getActiveSheet()->toArray();

    db()->beginTransaction();
    try {
        $count = 0;
        $skipRange = 0;
        foreach ($rows as $i => $row) {
            if ($i === 0) {
                continue;
            }

            $nisn = trim((string) ($row[0] ?? ''));
            $nilai = (float) ($row[1] ?? 0);

            if ($nisn === '' || $nilai <= 0) {
                continue;
            }

            if ($nilai < 70 || $nilai > 100) {
                $skipRange++;
                continue;
            }

            $stSiswa = db()->prepare('SELECT nisn, current_semester, status_siswa FROM siswa WHERE nisn=:nisn LIMIT 1');
            $stSiswa->execute(['nisn' => $nisn]);
            $siswa = $stSiswa->fetch();
            if (!$siswa || $siswa['status_siswa'] !== 'Aktif') {
                continue;
            }

            if ($action === 'import_rapor') {
                $semesterSiswa = (int) $siswa['current_semester'];
                if (!in_array($semesterSiswa, $targetRapor, true)) {
                    continue;
                }

                $stmt = db()->prepare('INSERT INTO nilai_rapor (nisn, mapel_id, semester, tahun_ajaran, nilai_angka, is_finalized) VALUES (:nisn,:mapel,:semester,:ta,:nilai,0)
                    ON DUPLICATE KEY UPDATE nilai_angka=VALUES(nilai_angka), is_finalized=0');
                $stmt->execute([
                    'nisn' => $nisn,
                    'mapel' => $mapelId,
                    'semester' => $semesterSiswa,
                    'ta' => $setting['tahun_ajaran'],
                    'nilai' => $nilai,
                ]);
                $count++;
            }

            if ($action === 'import_uam' && $semesterAktif === 'GENAP') {
                if ((int)$siswa['current_semester'] !== 5) {
                    continue;
                }

                $stmt = db()->prepare('INSERT INTO nilai_uam (nisn, mapel_id, nilai_angka) VALUES (:nisn,:mapel,:nilai)
                    ON DUPLICATE KEY UPDATE nilai_angka=VALUES(nilai_angka)');
                $stmt->execute([
                    'nisn' => $nisn,
                    'mapel' => $mapelId,
                    'nilai' => $nilai,
                ]);
                $count++;
            }
        }

        db()->commit();
        set_flash('success', "Import selesai. Diproses: {$count}, dilewati (nilai di luar 70-100): {$skipRange}.");
    } catch (Throwable $e) {
        db()->rollBack();
        set_flash('error', 'Import gagal: ' . $e->getMessage());
    }

    redirect('index.php?page=nilai-import');
}

$mapel = db()->query('SELECT id, nama_mapel FROM mapel ORDER BY nama_mapel')->fetchAll();

require dirname(__DIR__) . '/partials/header.php';
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3">
        <h3 class="mb-1">Import Excel Nilai Rapor</h3>
        <p class="text-secondary mb-0">Format file: kolom A=NISN, kolom B=Nilai Angka (rentang valid 70-100). Sistem otomatis mengikuti current semester siswa.</p>
    </div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" class="row g-3">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="import_rapor">
            <div class="col-md-5">
                <label class="form-label">Mapel</label>
                <select name="mapel_id" class="form-select" required>
                    <option value="">-- pilih --</option>
                    <?php foreach ($mapel as $m): ?>
                        <option value="<?= e((string)$m['id']) ?>"><?= e($m['nama_mapel']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">File Excel (.xlsx/.xls)</label>
                <input type="file" class="form-control" name="file_excel" accept=".xlsx,.xls" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-success w-100">Import Rapor</button>
            </div>
        </form>
    </div>
</div>

<?php if ($semesterAktif === 'GENAP'): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3">
        <h3 class="mb-0">Import Excel Nilai UAM (Semester 5)</h3>
    </div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" class="row g-3">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="import_uam">
            <div class="col-md-5">
                <label class="form-label">Mapel</label>
                <select name="mapel_id" class="form-select" required>
                    <option value="">-- pilih --</option>
                    <?php foreach ($mapel as $m): ?>
                        <option value="<?= e((string)$m['id']) ?>"><?= e($m['nama_mapel']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">File Excel (.xlsx/.xls)</label>
                <input type="file" class="form-control" name="file_excel" accept=".xlsx,.xls" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Import UAM</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<?php require dirname(__DIR__) . '/partials/footer.php';
