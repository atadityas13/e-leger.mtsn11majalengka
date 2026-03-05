<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforce_csrf('users');

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $stmt = db()->prepare('INSERT INTO users (username, password, nama_lengkap, role) VALUES (:username,:password,:nama,:role)');
        $stmt->execute([
            'username' => trim($_POST['username'] ?? ''),
            'password' => password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT),
            'nama' => trim($_POST['nama_lengkap'] ?? ''),
            'role' => $_POST['role'] ?? 'kurikulum',
        ]);
        set_flash('success', 'User berhasil ditambahkan.');
        redirect('index.php?page=users');
    }

    if ($action === 'delete') {
        $stmt = db()->prepare('DELETE FROM users WHERE id = :id AND id != :self');
        $stmt->execute([
            'id' => (int) ($_POST['id'] ?? 0),
            'self' => (int) ($_SESSION['user']['id'] ?? 0),
        ]);
        set_flash('success', 'User berhasil dihapus.');
        redirect('index.php?page=users');
    }
}

$users = db()->query('SELECT id, username, nama_lengkap, role FROM users ORDER BY id DESC')->fetchAll();

require dirname(__DIR__) . '/partials/header.php';
?>
<div class="card">
    <h3>Tambah User</h3>
    <form method="post" class="grid-3">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="create">
        <div>
            <label>Username</label><input type="text" name="username" required>
        </div>
        <div>
            <label>Nama Lengkap</label><input type="text" name="nama_lengkap" required>
        </div>
        <div>
            <label>Role</label>
            <select name="role" required>
                <option value="admin">Super Admin</option>
                <option value="kurikulum">Admin Kurikulum</option>
            </select>
        </div>
        <div>
            <label>Password</label><input type="password" name="password" required>
        </div>
        <div><label>&nbsp;</label><button type="submit">Simpan</button></div>
    </form>
</div>

<div class="card">
    <h3>Data User</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Username</th><th>Nama</th><th>Role</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['username']) ?></td>
                    <td><?= e($u['nama_lengkap']) ?></td>
                    <td><?= e($u['role']) ?></td>
                    <td>
                        <?php if ((int)$u['id'] !== (int)$_SESSION['user']['id']): ?>
                            <form method="post" onsubmit="return confirm('Hapus user ini?')">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string)$u['id']) ?>">
                                <button type="submit" class="danger">Hapus</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php';
