<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';
$success = '';

$selectedBookId = (int) ($_GET['book_id'] ?? $_POST['book_id'] ?? 0);
$sort = ($_GET['sort'] ?? 'top') === 'latest' ? 'latest' : 'top';

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

function get_reward_message(int $points): string
{
    if ($points >= 150) {
        return 'Luar biasa! Gelar Master Review makin bersinar berkat ulasanmu yang konsisten.';
    }
    if ($points >= 70) {
        return 'Keren banget! Jejak bacamu makin kaya dan membantu pembaca lain.';
    }
    if ($points >= 30) {
        return 'Manis! Semangat mengulasmu bikin katalog makin hidup.';
    }
    return 'Langkah awal yang manis! Satu ulasan baru sudah menambah koleksi poin & hatimu.';
}

$redirectUrl = '/reviewbuku/books.php';
$queryParams = [];
if ($selectedBookId > 0) {
    $queryParams['book_id'] = $selectedBookId;
}
if ($sort === 'latest') {
    $queryParams['sort'] = 'latest';
}
if (!empty($queryParams)) {
    $redirectUrl .= '?' . http_build_query($queryParams);
}

// Toggle like / unlike ulasan milik user login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_like') {
    require_login();
    $reviewId = (int) ($_POST['review_id'] ?? 0);
    $userId = (int) $_SESSION['user_id'];

    if ($reviewId > 0) {
        $checkStmt = $pdo->prepare(
            'SELECT id FROM review_likes WHERE review_id = :review_id AND user_id = :user_id LIMIT 1'
        );
        $checkStmt->execute([
            'review_id' => $reviewId,
            'user_id' => $userId,
        ]);
        $existingLike = $checkStmt->fetch();

        if ($existingLike) {
            $delLike = $pdo->prepare('DELETE FROM review_likes WHERE id = :id');
            $delLike->execute(['id' => (int) $existingLike['id']]);
        } else {
            $insLike = $pdo->prepare(
                'INSERT INTO review_likes (review_id, user_id) VALUES (:review_id, :user_id)'
            );
            $insLike->execute([
                'review_id' => $reviewId,
                'user_id' => $userId,
            ]);
        }
    }

    header('Location: ' . $redirectUrl);
    exit;
}

// Hapus ulasan milik user yang sedang login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_review') {
    require_login();
    $reviewId = (int) ($_POST['review_id'] ?? 0);
    $userId = (int) $_SESSION['user_id'];

    if ($reviewId > 0) {
        $stmt = $pdo->prepare('DELETE FROM reviews WHERE id = :id AND user_id = :user_id');
        $stmt->execute([
            'id' => $reviewId,
            'user_id' => $userId,
        ]);
        $success = 'Ulasan berhasil dihapus.';
    }
}

// Tambah ulasan baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_review') {
    require_login();
    $bookId = (int) ($_POST['book_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $ulasan = trim($_POST['ulasan'] ?? '');
    $userId = (int) $_SESSION['user_id'];

    if ($bookId <= 0) {
        $error = 'Buku tidak valid.';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Rating harus bernilai 1 sampai 5.';
    } elseif ($ulasan === '') {
        $error = 'Ulasan wajib diisi.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO reviews (user_id, book_id, rating, ulasan) VALUES (:user_id, :book_id, :rating, :ulasan)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'book_id' => $bookId,
            'rating' => $rating,
            'ulasan' => $ulasan,
        ]);

        // Hitung total ulasan user untuk poin/hati (+10 per ulasan) & title
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM reviews WHERE user_id = :user_id');
        $countStmt->execute(['user_id' => $userId]);
        $reviewCount = (int) $countStmt->fetchColumn();
        $totalPoints = $reviewCount * 10;
        $title = get_reviewer_title($totalPoints);
        $rewardText = get_reward_message($totalPoints);

        // Ambil judul buku untuk pesan pop-up
        $bookStmt = $pdo->prepare('SELECT judul FROM books WHERE id = :id LIMIT 1');
        $bookStmt->execute(['id' => $bookId]);
        $bookRow = $bookStmt->fetch();

        $_SESSION['review_popup'] = [
            'judul' => $bookRow['judul'] ?? 'buku ini',
            'rating' => $rating,
            'points_earned' => 10,
            'total_points' => $totalPoints,
            'title' => $title,
            'reward_text' => $rewardText,
        ];

        $afterAddUrl = '/reviewbuku/books.php?book_id=' . $bookId . ($sort === 'latest' ? '&sort=latest' : '');
        header('Location: ' . $afterAddUrl);
        exit;
    }
}

// Ambil buku (difilter jika ada book_id, atau seluruh katalog)
if ($selectedBookId > 0) {
    $bookQueryStmt = $pdo->prepare(
        'SELECT b.*,
                ROUND(AVG(r.rating), 1) AS avg_rating,
                COUNT(r.id) AS total_reviews
         FROM books b
         LEFT JOIN reviews r ON r.book_id = b.id
         WHERE b.id = :id
         GROUP BY b.id'
    );
    $bookQueryStmt->execute(['id' => $selectedBookId]);
    $books = $bookQueryStmt->fetchAll();
} else {
    $books = $pdo->query(
        'SELECT b.*,
                ROUND(AVG(r.rating), 1) AS avg_rating,
                COUNT(r.id) AS total_reviews
         FROM books b
         LEFT JOIN reviews r ON r.book_id = b.id
         GROUP BY b.id
         ORDER BY b.id DESC'
    )->fetchAll();
}

// Hitung jumlah ulasan tiap reviewer untuk title badge
$reviewerCounts = [];
foreach ($pdo->query('SELECT user_id, COUNT(*) AS cnt FROM reviews GROUP BY user_id')->fetchAll() as $rc) {
    $reviewerCounts[(int) $rc['user_id']] = (int) $rc['cnt'];
}

// Ambil ulasan + jumlah like + status like user aktif
$currentUserId = is_logged_in() ? (int) $_SESSION['user_id'] : 0;
$orderBySql = $sort === 'latest'
    ? 'ORDER BY r.tanggal DESC, r.id DESC'
    : 'ORDER BY like_count DESC, r.tanggal DESC, r.id DESC';

$reviewSql = "
    SELECT r.*,
           u.nama AS nama_user,
           COUNT(rl.id) AS like_count,
           MAX(CASE WHEN rl.user_id = :current_user_id THEN 1 ELSE 0 END) AS is_liked
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    LEFT JOIN review_likes rl ON rl.review_id = r.id
    GROUP BY r.id
    {$orderBySql}
";
$reviewStmt = $pdo->prepare($reviewSql);
$reviewStmt->execute(['current_user_id' => $currentUserId]);
$reviewRows = $reviewStmt->fetchAll();

$reviewsByBook = [];
foreach ($reviewRows as $row) {
    $reviewsByBook[(int) $row['book_id']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog & Review Buku - Review Buku</title>
    <link rel="stylesheet" href="/reviewbuku/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a href="/reviewbuku/index.php" class="brand-logo">Review Buku</a>
            <nav class="nav-links">
                <a href="/reviewbuku/index.php" class="nav-link">Beranda</a>
                <a href="/reviewbuku/books.php" class="nav-link">Semua Buku</a>
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
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px;">
            <h1 class="hero-title" style="margin: 0;">Katalog & Review Buku</h1>
            <div style="display: flex; gap: 8px; align-items: center;">
                <span class="book-meta" style="margin: 0;">Urutan ulasan:</span>
                <?php
                $topUrl = '/reviewbuku/books.php' . ($selectedBookId > 0 ? '?book_id=' . $selectedBookId : '');
                $latestUrl = '/reviewbuku/books.php?' . http_build_query(array_filter([
                    'book_id' => $selectedBookId > 0 ? $selectedBookId : null,
                    'sort' => 'latest',
                ]));
                ?>
                <a href="<?= htmlspecialchars($topUrl, ENT_QUOTES, 'UTF-8') ?>" class="genre-pill <?= $sort === 'top' ? 'active' : '' ?>">Top Review</a>
                <a href="<?= htmlspecialchars($latestUrl, ENT_QUOTES, 'UTF-8') ?>" class="genre-pill <?= $sort === 'latest' ? 'active' : '' ?>">Terbaru</a>
            </div>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (empty($books)): ?>
            <div class="empty-state">
                <p>Belum ada buku di katalog.</p>
            </div>
        <?php else: ?>
            <?php foreach ($books as $book): ?>
                <?php $bookReviews = $reviewsByBook[(int) $book['id']] ?? []; ?>
                <article class="detail-card">
                    <span class="book-badge"><?= htmlspecialchars($book['genre'] ?? 'Umum', ENT_QUOTES, 'UTF-8') ?></span>
                    <h2 class="book-title" style="font-size: 1.35rem;"><?= htmlspecialchars($book['judul'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="book-author">
                        oleh <strong><?= htmlspecialchars($book['penulis'], ENT_QUOTES, 'UTF-8') ?></strong>
                        • Rating: <strong><?= $book['avg_rating'] !== null ? htmlspecialchars((string) $book['avg_rating'], ENT_QUOTES, 'UTF-8') . '/5' : 'Belum ada rating' ?></strong>
                        (<?= (int) $book['total_reviews'] ?> ulasan)
                    </p>

                    <div class="book-meta">
                        <?php if (!empty($book['tahun_terbit'])): ?>
                            <span>Tahun: <?= htmlspecialchars((string) $book['tahun_terbit'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?php if (!empty($book['jumlah_halaman'])): ?>
                            <span> • <?= htmlspecialchars((string) $book['jumlah_halaman'], ENT_QUOTES, 'UTF-8') ?> halaman</span>
                        <?php endif; ?>
                        <?php if (!empty($book['penerbit'])): ?>
                            <span> • Penerbit: <?= htmlspecialchars($book['penerbit'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($book['cover'])): ?>
                        <p><img src="<?= htmlspecialchars($book['cover'], ENT_QUOTES, 'UTF-8') ?>" alt="Cover <?= htmlspecialchars($book['judul'], ENT_QUOTES, 'UTF-8') ?>" style="max-height: 160px; border-radius: 10px;"></p>
                    <?php endif; ?>

                    <p class="book-sinopsis" style="-webkit-line-clamp: unset; overflow: visible;">
                        <strong>Sinopsis:</strong><br><?= nl2br(htmlspecialchars($book['sinopsis'] ?? '-', ENT_QUOTES, 'UTF-8')) ?>
                    </p>

                    <h3 style="margin: 18px 0 6px;">Ulasan Pembaca</h3>
                    <?php if (empty($bookReviews)): ?>
                        <p class="book-sinopsis">Belum ada ulasan untuk buku ini. Jadilah yang pertama mengulas!</p>
                    <?php else: ?>
                        <ul class="review-list">
                            <?php foreach ($bookReviews as $rev): ?>
                                <?php
                                $revUserId = (int) $rev['user_id'];
                                $revPoints = ($reviewerCounts[$revUserId] ?? 0) * 10;
                                $revTitle = get_reviewer_title($revPoints);
                                $isLiked = !empty($rev['is_liked']);
                                ?>
                                <li class="review-item">
                                    <div class="review-header">
                                        <div>
                                            <a href="/reviewbuku/profile.php?user_id=<?= $revUserId ?>" class="reviewer-link">
                                                <?= htmlspecialchars($rev['nama_user'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                            <span class="title-badge"><?= htmlspecialchars($revTitle, ENT_QUOTES, 'UTF-8') ?></span>
                                            <span>• Rating: <strong><?= (int) $rev['rating'] ?>/5</strong></span>
                                            <span class="book-sinopsis" style="display: inline;">(<?= htmlspecialchars($rev['tanggal'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                        </div>

                                        <div style="display: flex; gap: 6px; align-items: center;">
                                            <?php if (is_logged_in()): ?>
                                                <form method="POST" action="<?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') ?>" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_like">
                                                    <input type="hidden" name="review_id" value="<?= (int) $rev['id'] ?>">
                                                    <button type="submit" class="like-btn <?= $isLiked ? 'liked' : '' ?>">
                                                        ♥ <?= (int) $rev['like_count'] ?> Suka
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="like-btn">♥ <?= (int) $rev['like_count'] ?> Suka</span>
                                            <?php endif; ?>

                                            <?php if (is_logged_in() && $revUserId === (int) $_SESSION['user_id']): ?>
                                                <form method="POST" action="<?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') ?>" style="display: inline;" onsubmit="return confirm('Hapus ulasan ini?');">
                                                    <input type="hidden" name="action" value="delete_review">
                                                    <input type="hidden" name="review_id" value="<?= (int) $rev['id'] ?>">
                                                    <button type="submit" class="btn-secondary" style="padding: 5px 10px;">Hapus</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div><?= nl2br(htmlspecialchars($rev['ulasan'], ENT_QUOTES, 'UTF-8')) ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (is_logged_in()): ?>
                        <div class="review-form-box">
                            <form method="POST" action="<?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="add_review">
                                <input type="hidden" name="book_id" value="<?= (int) $book['id'] ?>">

                                <label style="display: block; margin-bottom: 6px; font-weight: bold;">Rating (1–5):</label>
                                <select name="rating" class="form-control" style="max-width: 140px; margin-bottom: 10px;" required>
                                    <option value="5">5 - Sangat Bagus</option>
                                    <option value="4">4 - Bagus</option>
                                    <option value="3">3 - Cukup</option>
                                    <option value="2">2 - Kurang</option>
                                    <option value="1">1 - Buruk</option>
                                </select>

                                <label style="display: block; margin-bottom: 6px; font-weight: bold;">Tulis Ulasanmu:</label>
                                <textarea name="ulasan" rows="3" class="form-control" placeholder="Bagikan kesan, alur cerita, atau bagian favoritmu..." required style="margin-bottom: 10px;"></textarea>

                                <button type="submit" class="btn-primary">Kirim Ulasan (+10 Poin & Hati)</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <p><a href="/reviewbuku/login.php" class="reviewer-link">Login</a> untuk menulis ulasan atau menyukai ulasan pembaca lain.</p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <?php if (!empty($_SESSION['review_popup'])): ?>
        <?php
            $popupData = $_SESSION['review_popup'];
            unset($_SESSION['review_popup']);
            $popupJudul = htmlspecialchars((string) ($popupData['judul'] ?? ''), ENT_QUOTES, 'UTF-8');
            $popupRating = (int) ($popupData['rating'] ?? 0);
            $pointsEarned = (int) ($popupData['points_earned'] ?? 10);
            $totalPoints = (int) ($popupData['total_points'] ?? 0);
            $popupTitle = htmlspecialchars((string) ($popupData['title'] ?? 'Pemula'), ENT_QUOTES, 'UTF-8');
            $rewardText = htmlspecialchars((string) ($popupData['reward_text'] ?? 'Terima kasih atas ulasanmu!'), ENT_QUOTES, 'UTF-8');
        ?>
        <div id="reviewPopup" class="popup-backdrop">
            <div class="popup-card">
                <h3 style="margin: 0 0 6px; color: var(--rose-dark);">Manis! Ulasan Tersimpan</h3>
                <div class="reward-pill">+<?= $pointsEarned ?> Poin & Hati • Total: <?= $totalPoints ?></div>
                <p style="margin: 0 0 8px;"><strong><?= $rewardText ?></strong></p>
                <p class="book-sinopsis" style="-webkit-line-clamp: unset; overflow: visible; margin-bottom: 14px;">
                    Rating <strong><?= $popupRating ?>/5</strong> untuk <strong><?= $popupJudul ?></strong> • Title saat ini: <strong><?= $popupTitle ?></strong>.
                </p>
                <button type="button" class="btn-primary" onclick="document.getElementById('reviewPopup').style.display='none';">
                    Lanjutkan Membaca
                </button>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>