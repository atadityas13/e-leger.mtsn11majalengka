<?php
$filterYear = trim($_GET['tahun_lulus'] ?? '');

$sql = 'SELECT a.nisn, a.angkatan_lulus, s.nama
        FROM alumni a
        LEFT JOIN siswa s ON s.nisn = a.nisn';
$params = [];

if ($filterYear !== '') {
    $sql .= ' WHERE a.angkatan_lulus = :tahun_lulus';
    $params['tahun_lulus'] = $filterYear;
}

$sql .= ' ORDER BY a.angkatan_lulus DESC, a.nisn';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$years = db()->query('SELECT DISTINCT angkatan_lulus FROM alumni ORDER BY angkatan_lulus DESC')->fetchAll();

require dirname(__DIR__) . '/partials/header.php';
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3">
        <h3 class="mb-1">Data Alumni</h3>
        <p class="text-secondary mb-0">Daftar siswa alumni dengan filter tahun lulus.</p>
    </div>
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end mb-2">
            <input type="hidden" name="page" value="alumni">
            <div class="col-md-4">
                <label class="form-label">Tahun Lulus</label>
                <select name="tahun_lulus" class="form-select">
                    <option value="">Semua Tahun</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?= e((string) $year['angkatan_lulus']) ?>" <?= $filterYear === (string) $year['angkatan_lulus'] ? 'selected' : '' ?>>
                            <?= e((string) $year['angkatan_lulus']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success w-100">Terapkan Filter</button>
            </div>
            <div class="col-md-3">
                <a href="index.php?page=alumni" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>NISN</th>
                    <th>Nama</th>
                    <th>Tahun Lulus</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($rows) === 0): ?>
                    <tr>
                        <td colspan="3" class="text-center text-secondary">Belum ada data alumni.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= e($row['nisn']) ?></td>
                            <td><?= e($row['nama'] ?: '-') ?></td>
                            <td><?= e((string) $row['angkatan_lulus']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php';
