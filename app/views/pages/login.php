<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforce_csrf('login');

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'nama_lengkap' => $user['nama_lengkap'],
            'role' => $user['role'],
        ];
        redirect('index.php?page=dashboard');
    }

    set_flash('error', 'Username atau password salah.');
    redirect('index.php?page=login');
}

require dirname(__DIR__) . '/partials/header.php';
?>
<div class="login-shell">
    <div class="card login-box">
        <h2>Login e-Leger</h2>
        <p>Masuk sebagai Super Admin atau Admin Kurikulum.</p>
        <form method="post">
            <?= csrf_input() ?>
            <label>Username</label>
            <input type="text" name="username" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit">Masuk</button>
        </form>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php';
