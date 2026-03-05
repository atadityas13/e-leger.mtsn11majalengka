<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforce_csrf('siswa');

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nisn = trim($_POST['nisn'] ?? '');
        $nis = trim($_POST['nis'] ?? '');

        $cek = db()->prepare('SELECT nisn, nis FROM siswa WHERE nisn=:nisn OR nis=:nis LIMIT 1');
        $cek->execute([
            'nisn' => $nisn,
            'nis' => $nis,
        ]);
        $exists = $cek->fetch();

        if ($exists) {
            if (($exists['nisn'] ?? '') === $nisn) {
                set_flash('error', 'NISN sudah terdaftar. Gunakan NISN lain.');
                redirect('index.php?page=siswa');
            }

            if (($exists['nis'] ?? '') === $nis) {
                set_flash('error', 'NIS sudah terdaftar. Gunakan NIS lain.');
                redirect('index.php?page=siswa');
            }
        }

        $stmt = db()->prepare('INSERT INTO siswa (nisn, nis, nama, tempat_lahir, tgl_lahir, current_semester, status_siswa) VALUES (:nisn,:nis,:nama,:tempat,:tgl,:semester,:status)');
        $stmt->execute([
            'nisn' => $nisn,
            'nis' => $nis,
            'nama' => trim($_POST['nama'] ?? ''),
            'tempat' => trim($_POST['tempat_lahir'] ?? ''),
            'tgl' => $_POST['tgl_lahir'] ?? '',
            'semester' => (int) ($_POST['current_semester'] ?? 1),
            'status' => $_POST['status_siswa'] ?? 'Aktif',
        ]);
        set_flash('success', 'Data siswa ditambahkan.');
        redirect('index.php?page=siswa');
    }

    if ($action === 'update_status') {
        $stmt = db()->prepare('UPDATE siswa SET status_siswa=:status WHERE nisn=:nisn');
        $stmt->execute([
            'status' => $_POST['status_siswa'] ?? 'Aktif',
            'nisn' => $_POST['nisn'] ?? '',
        ]);
        set_flash('success', 'Status siswa diperbarui.');
        redirect('index.php?page=siswa');
    }
}

$siswa = db()->query('SELECT * FROM siswa ORDER BY nama')->fetchAll();

require dirname(__DIR__) . '/partials/header.php';
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3">
        <h3 class="mb-0">Tambah Siswa</h3>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="create">
            <div class="col-md-4"><label class="form-label">NISN</label><input type="text" class="form-control" name="nisn" required></div>
            <div class="col-md-4"><label class="form-label">NIS</label><input type="text" class="form-control" name="nis" required></div>
            <div class="col-md-4"><label class="form-label">Nama</label><input type="text" class="form-control" name="nama" required></div>
            <div class="col-md-4"><label class="form-label">Tempat Lahir</label><input type="text" class="form-control" name="tempat_lahir" required></div>
            <div class="col-md-4"><label class="form-label">Tanggal Lahir</label><input type="date" class="form-control" name="tgl_lahir" required></div>
            <div class="col-md-2">
                <label class="form-label">Current Semester</label>
                <select name="current_semester" class="form-select">
                    <option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status_siswa" class="form-select">
                    <option>Aktif</option>
                    <option>Tidak Melanjutkan</option>
                    <option>Lulus</option>
                </select>
            </div>
            <div class="col-12 text-end"><button type="submit" class="btn btn-success">Simpan</button></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3">
        <h3 class="mb-0">Data Siswa</h3>
    </div>
    <div class="card-body">
        <div class="table-wrap">
            <table>
                <thead><tr><th>NISN</th><th>Nama</th><th>Semester</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($siswa as $s): ?>
                    <tr>
                        <td><?= e($s['nisn']) ?></td>
                        <td><?= e($s['nama']) ?></td>
                        <td><?= e((string)$s['current_semester']) ?></td>
                        <td><span class="badge text-bg-light border"><?= e($s['status_siswa']) ?></span></td>
                        <td class="text-end">
                            <form method="post" class="d-inline-flex gap-2 align-items-center">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="nisn" value="<?= e($s['nisn']) ?>">
                                <select name="status_siswa" class="form-select form-select-sm">
                                    <option <?= $s['status_siswa'] === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                                    <option <?= $s['status_siswa'] === 'Tidak Melanjutkan' ? 'selected' : '' ?>>Tidak Melanjutkan</option>
                                    <option <?= $s['status_siswa'] === 'Lulus' ? 'selected' : '' ?>>Lulus</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php';
