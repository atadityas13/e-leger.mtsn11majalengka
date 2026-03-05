<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforce_csrf('mapel');

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $stmt = db()->prepare('INSERT INTO mapel (nama_mapel, kelompok, is_sub_pai) VALUES (:nama,:kelompok,:is_sub_pai)');
        $stmt->execute([
            'nama' => trim($_POST['nama_mapel'] ?? ''),
            'kelompok' => $_POST['kelompok'] ?? 'A',
            'is_sub_pai' => isset($_POST['is_sub_pai']) ? 1 : 0,
        ]);
        set_flash('success', 'Mapel berhasil ditambahkan.');
        redirect('index.php?page=mapel');
    }

    if ($action === 'delete') {
        $stmt = db()->prepare('DELETE FROM mapel WHERE id=:id');
        $stmt->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        set_flash('success', 'Mapel berhasil dihapus.');
        redirect('index.php?page=mapel');
    }
}

$mapel = db()->query('SELECT * FROM mapel ORDER BY kelompok, nama_mapel')->fetchAll();

require dirname(__DIR__) . '/partials/header.php';
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3">
        <h3 class="mb-0">Tambah Mapel</h3>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="create">
            <div class="col-md-6">
                <label class="form-label">Nama Mapel</label>
                <input type="text" name="nama_mapel" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Kelompok</label>
                <select name="kelompok" class="form-select">
                    <option value="A">A</option>
                    <option value="B">B</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label d-block">Sub PAI</label>
                <div class="form-check mt-2">
                    <input type="checkbox" class="form-check-input" name="is_sub_pai" value="1" id="is_sub_pai">
                    <label class="form-check-label" for="is_sub_pai">Ya</label>
                </div>
            </div>
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-success">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3">
        <h3 class="mb-0">Data Mapel</h3>
    </div>
    <div class="card-body">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Mapel</th><th>Kelompok</th><th>Sub PAI</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($mapel as $m): ?>
                    <tr>
                        <td><?= e($m['nama_mapel']) ?></td>
                        <td><span class="badge text-bg-light border"><?= e($m['kelompok']) ?></span></td>
                        <td><?= (int) $m['is_sub_pai'] === 1 ? 'Ya' : 'Tidak' ?></td>
                        <td class="text-end">
                            <form method="post" class="d-inline" onsubmit="return confirm('Hapus mapel ini?')">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string)$m['id']) ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
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
