<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';

if (is_logged_in()) {
    $role = $_SESSION['role'] ?? 'user';
    if ($role === 'admin') {
        header('Location: /reviewbuku/admin/books.php');
        exit;
    }
    if (empty($_SESSION['onboarding_done'])) {
        header('Location: /reviewbuku/onboarding.php');
        exit;
    }
    header('Location: /reviewbuku/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, nama, email, password, role, onboarding_done
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'] ?? 'user';
            $_SESSION['onboarding_done'] = (int) ($user['onboarding_done'] ?? 0);

            if ($_SESSION['role'] === 'admin') {
                header('Location: /reviewbuku/admin/books.php');
                exit;
            }

            if ($_SESSION['onboarding_done'] === 0) {
                header('Location: /reviewbuku/onboarding.php');
                exit;
            }

            header('Location: /reviewbuku/index.php');
            exit;
        } else {
            $error = 'Email atau password tidak sesuai.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Review Buku</title>
    <link rel="stylesheet" href="/reviewbuku/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a href="/reviewbuku/index.php" class="brand-logo">Review Buku</a>
            <nav class="nav-links">
                <a href="/reviewbuku/index.php" class="nav-link">Beranda</a>
                <a href="/reviewbuku/books.php" class="nav-link">Semua Buku</a>
                <a href="/reviewbuku/register.php" class="btn-primary">Daftar</a>
            </nav>
        </div>
    </header>

    <main class="container" style="max-width: 460px;">
        <article class="detail-card">
            <span class="book-badge">Masuk Akun</span>
            <h1 class="hero-title" style="font-size: 1.5rem; margin-top: 8px;">Selamat Datang Kembali</h1>
            <p class="book-sinopsis" style="-webkit-line-clamp: unset; overflow: visible; margin-bottom: 16px;">
                Masuk untuk menulis ulasan, memberi suka, mengumpulkan poin, dan mengelola profil bacamu.
            </p>

            <?php if ($error !== ''): ?>
                <div class="alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="POST" action="/reviewbuku/login.php" id="loginForm">
                <label for="email" style="display: block; margin-bottom: 6px; font-weight: bold;">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="nama@email.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required
                    autofocus
                    style="margin-bottom: 12px;"
                >

                <label for="password" style="display: block; margin-bottom: 6px; font-weight: bold;">Password</label>
                <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 16px;">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        required
                    >
                    <button type="button" id="togglePassword" class="btn-secondary" style="white-space: nowrap;">
                        Lihat
                    </button>
                </div>

                <button type="submit" id="submitLoginBtn" class="btn-primary" style="width: 100%;">
                    Login
                </button>
            </form>

            <p class="book-sinopsis" style="-webkit-line-clamp: unset; overflow: visible; margin-top: 16px; text-align: center;">
                Belum punya akun? <a href="/reviewbuku/register.php" class="reviewer-link">Daftar di sini</a>
            </p>
        </article>
    </main>

    <script>
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitLoginBtn');

        toggleBtn.addEventListener('click', () => {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            toggleBtn.textContent = isHidden ? 'Sembunyikan' : 'Lihat';
        });

        loginForm.addEventListener('submit', () => {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Memproses...';
        });
    </script>
</body>
</html>