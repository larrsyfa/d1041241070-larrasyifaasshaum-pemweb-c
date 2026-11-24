<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nama === '' || $email === '' || $password === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $cek = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $cek->execute(['email' => $email]);

        if ($cek->fetch()) {
            $error = 'Email sudah terdaftar.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare(
                'INSERT INTO users (nama, email, password, role) VALUES (:nama, :email, :password, :role)'
            );
            $insert->execute([
                'nama' => $nama,
                'email' => $email,
                'password' => $hash,
                'role' => 'user',
            ]);
            $success = 'Registrasi berhasil! Silakan login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register - Review Buku</title>
</head>
<body>
    <h2>Daftar Akun</h2>

    <?php if ($error !== ''): ?>
        <p style="color: red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <p style="color: green;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <div>
            <label>Nama</label><br>
            <input type="text" name="nama" required>
        </div><br>
        <div>
            <label>Email</label><br>
            <input type="email" name="email" required>
        </div><br>
        <div>
            <label>Password</label><br>
            <input type="password" name="password" required>
        </div><br>
        <button type="submit">Daftar</button>
    </form>

    <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
</body>
</html>