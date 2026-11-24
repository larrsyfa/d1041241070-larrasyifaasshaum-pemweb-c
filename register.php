<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';
$success = '';

if (is_logged_in()) {
    header('Location: /reviewbuku/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nama === '' || $email === '' || $password === '') {
        $error = 'Nama, email, dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $checkStmt->execute(['email' => $email]);

        if ($checkStmt->fetch()) {
            $error = 'Email sudah terdaftar. Silakan gunakan email lain atau login.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare(
                'INSERT INTO users (nama, email, password, role) VALUES (:nama, :email, :password, :role)'
            );
            $insertStmt->execute([
                'nama' => $nama,
                'email' => $email,
                'password' => $hashedPassword,
                'role' => 'user',
            ]);

            $success = 'Akun berhasil dibuat! Silakan login untuk mulai mengulas buku.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Review Buku</title>
    <link rel="stylesheet" href="/reviewbuku/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a href="/reviewbuku/index.php" class="brand-logo">Review Buku</a>
            <nav class="nav-links">
                <a href="/reviewbuku/index.php" class="nav-link">Beranda</a>
                <a href="/reviewbuku/books.php" class="nav-link">Semua Buku</a>
                <a href="/reviewbuku/login.php" class="btn-secondary">Login</a>
            </nav>
        </div>
    </header>

    <main class="container" style="max-width: 480px;">
        <article class="detail-card">
            <span class="book-badge">Daftar Akun</span>
            <h1 class="hero-title" style="font-size: 1.5rem; margin-top: 8px;">Buat Akun Pembaca</h1>
            <p class="book-sinopsis" style="-webkit-line-clamp: unset; overflow: visible; margin-bottom: 16px;">
                Bergabung untuk memberi ulasan buku, kumpulkan poin &amp; hati (+10 tiap ulasan), dan raih *title* pembaca.
            </p>

            <?php if ($error !== ''): ?>
                <div class="alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="alert-success" role="status">
                    <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                    <a href="/reviewbuku/login.php" class="reviewer-link" style="margin-left: 6px;">Masuk sekarang →</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="/reviewbuku/register.php" id="registerForm">
                <label for="nama" style="display: block; margin-bottom: 6px; font-weight: bold;">Nama Lengkap</label>
                <input
                    type="text"
                    id="nama"
                    name="nama"
                    class="form-control"
                    placeholder="Contoh: Larra Syifa"
                    value="<?= htmlspecialchars($_POST['nama'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required
                    autofocus
                    style="margin-bottom: 12px;"
                >

                <label for="email" style="display: block; margin-bottom: 6px; font-weight: bold;">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="nama@email.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required
                    style="margin-bottom: 12px;"
                >

                <label for="password" style="display: block; margin-bottom: 6px; font-weight: bold;">Password</label>
                <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px;">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Minimal 6 karakter"
                        minlength="6"
                        required
                    >
                    <button type="button" id="toggleRegPassword" class="btn-secondary" style="white-space: nowrap;">
                        Lihat
                    </button>
                </div>
                <p id="passwordHint" class="book-meta" style="margin: 0 0 16px;">
                    Kekuatan password: <strong>Belum diisi</strong> (minimal 6 karakter).
                </p>

                <button type="submit" id="submitRegisterBtn" class="btn-primary" style="width: 100%;">
                    Daftar
                </button>
            </form>

            <p class="book-sinopsis" style="-webkit-line-clamp: unset; overflow: visible; margin-top: 16px; text-align: center;">
                Sudah punya akun? <a href="/reviewbuku/login.php" class="reviewer-link">Login di sini</a>
            </p>
        </article>
    </main>

    <script>
        const toggleRegBtn = document.getElementById('toggleRegPassword');
        const regPassInput = document.getElementById('password');
        const passHint = document.getElementById('passwordHint');
        const registerForm = document.getElementById('registerForm');
        const submitRegBtn = document.getElementById('submitRegisterBtn');

        toggleRegBtn.addEventListener('click', () => {
            const isHidden = regPassInput.type === 'password';
            regPassInput.type = isHidden ? 'text' : 'password';
            toggleRegBtn.textContent = isHidden ? 'Sembunyikan' : 'Lihat';
        });

        regPassInput.addEventListener('input', () => {
            const len = regPassInput.value.length;
            let label = 'Belum diisi';
            if (len > 0 && len < 6) {
                label = 'Pendek (' + len + '/6 karakter)';
            } else if (len >= 6 && len < 10) {
                label = 'Cukup aman';
            } else if (len >= 10) {
                label = 'Kuat';
            }
            passHint.innerHTML = 'Kekuatan password: <strong>' + label + '</strong> (minimal 6 karakter).';
        });

        registerForm.addEventListener('submit', () => {
            submitRegBtn.disabled = true;
            submitRegBtn.textContent = 'Mendaftarkan...';
        });
    </script>
</body>
</html>