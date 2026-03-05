<?php
$setting = setting_akademik();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforce_csrf('semester-control');

    $action = $_POST['action'] ?? '';

    if ($action === 'set_aktif') {
        $stmt = db()->prepare('INSERT INTO pengaturan_akademik (tahun_ajaran, semester_aktif) VALUES (:ta,:sem)');
        $stmt->execute([
            'ta' => trim($_POST['tahun_ajaran'] ?? ''),
            'sem' => strtoupper($_POST['semester_aktif'] ?? 'GANJIL'),
        ]);
        set_flash('success', 'Tahun ajaran aktif berhasil diperbarui.');
        redirect('index.php?page=semester-control');
    }

    if ($action === 'finalisasi') {
        $active = setting_akademik();
        $target = semester_upload_target($active['semester_aktif']);

        db()->beginTransaction();
        try {
            $in = implode(',', array_fill(0, count($target), '?'));

            $params = $target;
            array_unshift($params, $active['tahun_ajaran']);

            $sqlFinal = "UPDATE nilai_rapor SET is_finalized=1 WHERE tahun_ajaran=? AND semester IN ($in)";
            $stmtFinal = db()->prepare($sqlFinal);
            $stmtFinal->execute($params);

            $sqlNaik = "UPDATE siswa SET current_semester = CASE
                        WHEN current_semester < 5 THEN current_semester + 1
                        ELSE current_semester END
                        WHERE status_siswa='Aktif' AND current_semester IN ($in)";
            $stmtNaik = db()->prepare($sqlNaik);
            $stmtNaik->execute($target);

            db()->commit();
            set_flash('success', 'Finalisasi berhasil: nilai dikunci dan current_semester siswa aktif dinaikkan.');
        } catch (Throwable $e) {
            db()->rollBack();
            set_flash('error', 'Finalisasi gagal: ' . $e->getMessage());
        }

        redirect('index.php?page=semester-control');
    }
}

require dirname(__DIR__) . '/partials/header.php';
?>
<div class="card">
    <h3>Set Tahun Ajaran Aktif</h3>
    <form method="post" class="grid-3">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="set_aktif">
        <div><label>Tahun Ajaran</label><input type="text" name="tahun_ajaran" value="<?= e($setting['tahun_ajaran']) ?>" required></div>
        <div>
            <label>Semester Aktif</label>
            <select name="semester_aktif" required>
                <option value="GANJIL" <?= $setting['semester_aktif'] === 'GANJIL' ? 'selected' : '' ?>>GANJIL</option>
                <option value="GENAP" <?= $setting['semester_aktif'] === 'GENAP' ? 'selected' : '' ?>>GENAP</option>
            </select>
        </div>
        <div><label>&nbsp;</label><button type="submit">Simpan Pengaturan</button></div>
    </form>
</div>

<div class="card">
    <h3>Finalisasi Semester</h3>
    <p>Proses ini akan mengunci nilai rapor semester target dan menaikkan current_semester siswa aktif. Siswa status Tidak Melanjutkan tidak diproses.</p>
    <form method="post" onsubmit="return confirm('Yakin finalisasi semester aktif?')">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="finalisasi">
        <button type="submit" class="danger">Finalisasi Sekarang</button>
    </form>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php';
