<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';
$success = '';

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

        // Ambil judul buku untuk pesan pop-up
        $bookStmt = $pdo->prepare('SELECT judul FROM books WHERE id = :id LIMIT 1');
        $bookStmt->execute(['id' => $bookId]);
        $bookRow = $bookStmt->fetch();

        $_SESSION['review_popup'] = [
            'judul' => $bookRow['judul'] ?? 'buku ini',
            'rating' => $rating,
        ];

        header('Location: /reviewbuku/books.php');
        exit;
    }
}

// Ambil semua buku beserta rata-rata rating dan jumlah ulasan
$books = $pdo->query(
    'SELECT b.*,
            ROUND(AVG(r.rating), 1) AS avg_rating,
            COUNT(r.id) AS total_reviews
     FROM books b
     LEFT JOIN reviews r ON r.book_id = b.id
     GROUP BY b.id
     ORDER BY b.id DESC'
)->fetchAll();

// Ambil semua ulasan bergabung dengan nama user
$reviewRows = $pdo->query(
    'SELECT r.*, u.nama AS nama_user
     FROM reviews r
     JOIN users u ON u.id = r.user_id
     ORDER BY r.tanggal DESC, r.id DESC'
)->fetchAll();

$reviewsByBook = [];
foreach ($reviewRows as $row) {
    $reviewsByBook[(int) $row['book_id']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Katalog & Review Buku - Review Buku</title>
</head>
<body>
    <h2>Katalog & Review Buku</h2>
    <p>
        <a href="/reviewbuku/index.php">Beranda</a>
        <?php if (is_logged_in()): ?>
            | Login sebagai: <strong><?= htmlspecialchars($_SESSION['nama'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></strong>
            <?php if (current_user_role() === 'admin'): ?>
                | <a href="/reviewbuku/admin/books.php">Kelola Buku (Admin)</a>
            <?php endif; ?>
            | <a href="/reviewbuku/logout.php">Logout</a>
        <?php else: ?>
            | <a href="/reviewbuku/login.php">Login</a>
            | <a href="/reviewbuku/register.php">Daftar</a>
        <?php endif; ?>
    </p>

    <?php if ($error !== ''): ?>
        <p style="color: red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <p style="color: green;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <hr>

    <?php if (empty($books)): ?>
        <p>Belum ada buku di katalog.</p>
    <?php else: ?>
        <?php foreach ($books as $book): ?>
            <?php $bookReviews = $reviewsByBook[(int) $book['id']] ?? []; ?>
            <div style="border: 1px solid #ccc; padding: 12px; margin-bottom: 18px;">
                <h3><?= htmlspecialchars($book['judul'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p>
                    <strong>Penulis:</strong> <?= htmlspecialchars($book['penulis'], ENT_QUOTES, 'UTF-8') ?> |
                    <strong>Genre:</strong> <?= htmlspecialchars($book['genre'] ?? '-', ENT_QUOTES, 'UTF-8') ?> |
                    <strong>Rating:</strong> <?= $book['avg_rating'] !== null ? htmlspecialchars((string) $book['avg_rating'], ENT_QUOTES, 'UTF-8') . '/5' : 'Belum ada rating' ?>
                    (<?= (int) $book['total_reviews'] ?> ulasan)
                </p>

                <?php if (!empty($book['cover'])): ?>
                    <p><img src="<?= htmlspecialchars($book['cover'], ENT_QUOTES, 'UTF-8') ?>" alt="Cover <?= htmlspecialchars($book['judul'], ENT_QUOTES, 'UTF-8') ?>" style="max-height: 150px;"></p>
                <?php endif; ?>

                <p><strong>Sinopsis:</strong><br><?= nl2br(htmlspecialchars($book['sinopsis'] ?? '-', ENT_QUOTES, 'UTF-8')) ?></p>

                <h4>Ulasan Pembaca</h4>
                <?php if (empty($bookReviews)): ?>
                    <p>Belum ada ulasan untuk buku ini.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($bookReviews as $rev): ?>
                            <li>
                                <strong><?= htmlspecialchars($rev['nama_user'], ENT_QUOTES, 'UTF-8') ?></strong>
                                — Rating: <strong><?= (int) $rev['rating'] ?>/5</strong>
                                (<?= htmlspecialchars($rev['tanggal'], ENT_QUOTES, 'UTF-8') ?>)<br>
                                <?= nl2br(htmlspecialchars($rev['ulasan'], ENT_QUOTES, 'UTF-8')) ?>

                                <?php if (is_logged_in() && (int) $rev['user_id'] === (int) $_SESSION['user_id']): ?>
                                    <form method="POST" action="/reviewbuku/books.php" style="display:inline;" onsubmit="return confirm('Hapus ulasan ini?');">
                                        <input type="hidden" name="action" value="delete_review">
                                        <input type="hidden" name="review_id" value="<?= (int) $rev['id'] ?>">
                                        <button type="submit">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (is_logged_in()): ?>
                    <form method="POST" action="/reviewbuku/books.php">
                        <input type="hidden" name="action" value="add_review">
                        <input type="hidden" name="book_id" value="<?= (int) $book['id'] ?>">

                        <label>Rating (1-5):</label>
                        <select name="rating" required>
                            <option value="5">5</option>
                            <option value="4">4</option>
                            <option value="3">3</option>
                            <option value="2">2</option>
                            <option value="1">1</option>
                        </select><br><br>

                        <label>Ulasan:</label><br>
                        <textarea name="ulasan" rows="3" cols="40" required></textarea><br><br>

                        <button type="submit">Kirim Ulasan</button>
                    </form>
                <?php else: ?>
                    <p><a href="/reviewbuku/login.php">Login</a> untuk menulis ulasan.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['review_popup'])): ?>
        <?php
            $popupData = $_SESSION['review_popup'];
            unset($_SESSION['review_popup']);
            $popupJudul = htmlspecialchars((string) ($popupData['judul'] ?? ''), ENT_QUOTES, 'UTF-8');
            $popupRating = (int) ($popupData['rating'] ?? 0);
        ?>
        <div id="reviewPopup" style="position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; z-index: 999;">
            <div style="background: #fff; padding: 20px 24px; border-radius: 8px; max-width: 380px; width: 90%; text-align: center; box-shadow: 0 8px 24px rgba(0,0,0,0.2);">
                <h3 style="margin-top: 0;">Terima Kasih!</h3>
                <p>Ulasan kamu dengan rating <strong><?= $popupRating ?>/5</strong> untuk buku <strong><?= $popupJudul ?></strong> sudah tersimpan.</p>
                <button type="button" onclick="document.getElementById('reviewPopup').style.display='none';">Tutup</button>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>