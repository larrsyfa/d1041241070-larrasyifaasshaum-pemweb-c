<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Beranda - Review Buku</title>
</head>
<body>
    <h2>Review Buku</h2>
    <p>
        <a href="/reviewbuku/books.php">Katalog & Review Buku</a>
        <?php if (is_logged_in()): ?>
            | Login sebagai: <strong><?= htmlspecialchars($_SESSION['nama'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></strong>
            (<?= htmlspecialchars(current_user_role(), ENT_QUOTES, 'UTF-8') ?>)
            <?php if (current_user_role() === 'admin'): ?>
                | <a href="/reviewbuku/admin/books.php">Kelola Buku (Admin)</a>
            <?php endif; ?>
            | <a href="/reviewbuku/logout.php">Logout</a>
        <?php else: ?>
            | <a href="/reviewbuku/login.php">Login</a>
            | <a href="/reviewbuku/register.php">Daftar</a>
        <?php endif; ?>
    </p>
    <hr>
    <p>Selamat datang di platform katalog dan ulasan buku. Silakan buka <a href="/reviewbuku/books.php">Katalog & Review Buku</a> untuk melihat daftar buku dan menulis ulasan.</p>
</body>
</html>