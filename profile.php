<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

function get_reviewer_title(int $points): string
{
    if ($points >= 150) {
        return 'Master Review';
    }
    if ($points >= 70) {
        return 'Kutu Buku';
    }
    if ($points >= 30) {
        return 'Reviewer Aktif';
    }
    return 'Pemula';
}

$targetUserId = (int) ($_GET['user_id'] ?? ($_SESSION['user_id'] ?? 0));
if ($targetUserId <= 0) {
    header('Location: /reviewbuku/login.php');
    exit;
}

$error = '';
$success = '';

// Update bio jika pemilik profil yang sedang login mengirim form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_bio') {
    require_login();
    if ((int) $_SESSION['user_id'] !== $targetUserId) {
        $error = 'Kamu hanya dapat mengubah bio pada profilmu sendiri.';
    } else {
        $bioInput = trim($_POST['bio'] ?? '');
        $bioValue = $bioInput === '' ? null : $bioInput;

        $updateStmt = $pdo->prepare('UPDATE users SET bio = :bio WHERE id = :id');
        $updateStmt->execute([
            'bio' => $bioValue,
            'id' => $targetUserId,
        ]);
        $success = 'Bio profil berhasil diperbarui.';
    }
}

$userStmt = $pdo->prepare('SELECT id, nama, email, role, bio FROM users WHERE id = :id LIMIT 1');
$userStmt->execute(['id' => $targetUserId]);
$profileUser = $userStmt->fetch();

if (!$profileUser) {
    header('Location: /reviewbuku/books.php');
    exit;
}

$revStmt = $pdo->prepare(
    'SELECT r.id, r.book_id, r.rating, r.ulasan, r.tanggal,
            b.judul AS judul_buku, b.penulis AS penulis_buku, b.genre,
            COUNT(rl.id) AS like_count
     FROM reviews r
     JOIN books b ON b.id = r.book_id
     LEFT JOIN review_likes rl ON rl.review_id = r.id
     WHERE r.user_id = :user_id
     GROUP BY r.id
     ORDER BY r.tanggal DESC, r.id DESC'
);
$revStmt->execute(['user_id' => $targetUserId]);
$userReviews = $revStmt->fetchAll();

$totalReviews = count($userReviews);
$totalPoints = $totalReviews * 10;
$reviewerTitle = get_reviewer_title($totalPoints);
$isOwnProfile = is_logged_in() && (int) $_SESSION['user_id'] === $targetUserId;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Reviewer - <?= htmlspecialchars($profileUser['nama'], ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/reviewbuku/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a href="/reviewbuku/index.php" class="brand-logo">Review Buku</a>
            <nav class="nav-links">
                <a href="/reviewbuku/index.php" class="nav-link">Beranda</a>
                <a href="/reviewbuku/books.php" class="nav-link">Katalog & Review Buku</a>
                <?php if (is_logged_in()): ?>
                    <a href="/reviewbuku/profile.php?user_id=<?= (int) $_SESSION['user_id'] ?>" class="nav-link">
                        Profil (<strong><?= htmlspecialchars($_SESSION['nama'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></strong>)
                    </a>
                    <?php if (current_user_role() === 'admin'): ?>
                        <a href="/reviewbuku/admin/books.php" class="btn-secondary">Kelola Buku (Admin)</a>
                    <?php endif; ?>
                    <a href="/reviewbuku/logout.php" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="/reviewbuku/login.php" class="nav-link">Login</a>
                    <a href="/reviewbuku/register.php" class="btn-primary">Daftar</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if ($error !== ''): ?>
            <div class="alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <section class="hero-box">
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 8px;">
                <h1 class="hero-title" style="margin: 0;"><?= htmlspecialchars($profileUser['nama'], ENT_QUOTES, 'UTF-8') ?></h1>
                <span class="title-badge"><?= htmlspecialchars($reviewerTitle, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="book-badge" style="margin: 0;"><?= htmlspecialchars($profileUser['role'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <p class="hero-subtitle" style="margin-bottom: 14px;">
                <?= !empty($profileUser['bio'])
                    ? nl2br(htmlspecialchars($profileUser['bio'], ENT_QUOTES, 'UTF-8'))
                    : 'Belum menambahkan bio pembaca.' ?>
            </p>

            <div class="book-meta" style="display: inline-block; margin: 0;">
                <strong><?= $totalReviews ?></strong> Ulasan •
                <strong><?= $totalPoints ?></strong> Poin &amp; Hati (+10 per ulasan) •
                Title: <strong><?= htmlspecialchars($reviewerTitle, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>

            <?php if ($isOwnProfile): ?>
                <div class="review-form-box" style="margin-top: 16px;">
                    <form method="POST" action="/reviewbuku/profile.php?user_id=<?= $targetUserId ?>">
                        <input type="hidden" name="action" value="update_bio">
                        <label style="display: block; margin-bottom: 6px; font-weight: bold;">Edit Bio Pembaca:</label>
                        <textarea name="bio" rows="2" class="form-control" placeholder="Ceritakan genre favorit atau gaya baca kamu..." style="margin-bottom: 10px;"><?= htmlspecialchars($profileUser['bio'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <button type="submit" class="btn-secondary">Simpan Bio</button>
                    </form>
                </div>
            <?php endif; ?>
        </section>

        <section class="detail-card">
            <h2 class="book-title" style="font-size: 1.25rem; margin-bottom: 12px;">Riwayat Ulasan (<?= $totalReviews ?>)</h2>

            <?php if (empty($userReviews)): ?>
                <p class="book-sinopsis">Belum ada ulasan yang ditulis oleh reviewer ini.</p>
            <?php else: ?>
                <ul class="review-list">
                    <?php foreach ($userReviews as $rev): ?>
                        <li class="review-item">
                            <div class="review-header">
                                <div>
                                    <a href="/reviewbuku/books.php?book_id=<?= (int) $rev['book_id'] ?>" class="reviewer-link">
                                        <?= htmlspecialchars($rev['judul_buku'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <span>(<?= htmlspecialchars($rev['penulis_buku'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                    <span class="title-badge">Rating <?= (int) $rev['rating'] ?>/5</span>
                                </div>
                                <span class="like-btn liked">♥ <?= (int) $rev['like_count'] ?> Suka • <?= htmlspecialchars($rev['tanggal'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div><?= nl2br(htmlspecialchars($rev['ulasan'], ENT_QUOTES, 'UTF-8')) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>