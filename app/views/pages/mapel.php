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
<div class="card">
    <h3>Tambah Mapel</h3>
    <form method="post" class="grid-3">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="create">
        <div>
            <label>Nama Mapel</label><input type="text" name="nama_mapel" required>
        </div>
        <div>
            <label>Kelompok</label>
            <select name="kelompok"><option value="A">A</option><option value="B">B</option></select>
        </div>
        <div>
            <label>Sub PAI</label>
            <div class="inline"><input type="checkbox" name="is_sub_pai" value="1"><span>Ya</span></div>
        </div>
        <div><label>&nbsp;</label><button type="submit">Simpan</button></div>
    </form>
</div>

<div class="card">
    <h3>Data Mapel</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Mapel</th><th>Kelompok</th><th>Sub PAI</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($mapel as $m): ?>
                <tr>
                    <td><?= e($m['nama_mapel']) ?></td>
                    <td><?= e($m['kelompok']) ?></td>
                    <td><?= (int) $m['is_sub_pai'] === 1 ? 'Ya' : 'Tidak' ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Hapus mapel ini?')">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= e((string)$m['id']) ?>">
                            <button type="submit" class="danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php';
