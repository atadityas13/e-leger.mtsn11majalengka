<?php
$eligibleSql = "SELECT s.nisn, s.nama
FROM siswa s
WHERE s.status_siswa='Aktif' AND s.current_semester=5
AND (
    SELECT COUNT(DISTINCT nr.semester) FROM nilai_rapor nr WHERE nr.nisn=s.nisn AND nr.semester BETWEEN 1 AND 5
) = 5
AND EXISTS (SELECT 1 FROM nilai_uam nu WHERE nu.nisn=s.nisn)";
$eligible = db()->query($eligibleSql)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'migrate') {
    enforce_csrf('kelulusan');

    $angkatan = (int) ($_POST['angkatan_lulus'] ?? date('Y'));

    db()->beginTransaction();
    try {
        foreach ($eligible as $s) {
            $stmtMapel = db()->prepare('SELECT id, nama_mapel FROM mapel ORDER BY id');
            $stmtMapel->execute();
            $mapel = $stmtMapel->fetchAll();

            $detail = [];
            foreach ($mapel as $m) {
                $stR = db()->prepare('SELECT AVG(nilai_angka) rata FROM nilai_rapor WHERE nisn=:nisn AND mapel_id=:mapel AND semester BETWEEN 1 AND 5');
                $stR->execute(['nisn' => $s['nisn'], 'mapel' => $m['id']]);
                $rata = (float) ($stR->fetch()['rata'] ?? 0);

                $stU = db()->prepare('SELECT nilai_angka FROM nilai_uam WHERE nisn=:nisn AND mapel_id=:mapel LIMIT 1');
                $stU->execute(['nisn' => $s['nisn'], 'mapel' => $m['id']]);
                $uam = (float) ($stU->fetch()['nilai_angka'] ?? 0);

                $ijazah = hitung_nilai_ijazah($rata, $uam);
                $detail[] = [
                    'mapel_id' => (int)$m['id'],
                    'mapel' => $m['nama_mapel'],
                    'rata_rapor' => $rata,
                    'nilai_uam' => $uam,
                    'nilai_ijazah' => $ijazah,
                    'terbilang' => terbilang_nilai($ijazah),
                ];
            }

            $stmtInsert = db()->prepare('INSERT INTO alumni (nisn, angkatan_lulus, data_ijazah_json) VALUES (:nisn,:angkatan,:json)
                ON DUPLICATE KEY UPDATE angkatan_lulus=VALUES(angkatan_lulus), data_ijazah_json=VALUES(data_ijazah_json)');
            $stmtInsert->execute([
                'nisn' => $s['nisn'],
                'angkatan' => $angkatan,
                'json' => json_encode($detail, JSON_UNESCAPED_UNICODE),
            ]);

            $stmtDelete = db()->prepare('DELETE FROM siswa WHERE nisn=:nisn');
            $stmtDelete->execute(['nisn' => $s['nisn']]);
        }

        db()->commit();
        set_flash('success', 'Migrasi kelulusan selesai. Data siswa lulus dipindah ke alumni.');
    } catch (Throwable $e) {
        db()->rollBack();
        set_flash('error', 'Migrasi gagal: ' . $e->getMessage());
    }

    redirect('index.php?page=kelulusan');
}

require dirname(__DIR__) . '/partials/header.php';
?>
<div class="card">
    <h3>Migrasi Kelulusan ke Alumni</h3>
    <p>Syarat: siswa aktif semester 5, nilai rapor lengkap semester 1-5, dan memiliki nilai UAM.</p>
    <form method="post" class="inline" onsubmit="return confirm('Proses migrasi kelulusan?')">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="migrate">
        <label>Tahun Angkatan Lulus</label>
        <input type="number" name="angkatan_lulus" value="<?= e(date('Y')) ?>" required>
        <button type="submit">Migrasi Sekarang</button>
    </form>
</div>

<div class="card">
    <h3>Daftar Siswa Eligible Kelulusan</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>NISN</th><th>Nama</th></tr></thead>
            <tbody>
            <?php foreach ($eligible as $s): ?>
                <tr><td><?= e($s['nisn']) ?></td><td><?= e($s['nama']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php';
