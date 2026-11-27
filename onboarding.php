<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (!is_logged_in()) {
    header('Location: /reviewbuku/login.php');
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? 'user';

// Admin tidak perlu menjalani alur onboarding pembaca
if ($userRole === 'admin') {
    header('Location: /reviewbuku/index.php');
    exit;
}

$error = '';
$currentYear = (int) date('Y');

// Ambil data pengguna saat ini
$userStmt = $pdo->prepare('SELECT id, nama, reading_goal, onboarding_done FROM users WHERE id = :id LIMIT 1');
$userStmt->execute(['id' => $userId]);
$currentUser = $userStmt->fetch();

if (!$currentUser) {
    header('Location: /reviewbuku/logout.php');
    exit;
}

// Ambil daftar genre unik yang tersedia di katalog buku
$genreStmt = $pdo->query(
    "SELECT DISTINCT genre
     FROM books
     WHERE genre IS NOT NULL AND TRIM(genre) <> ''
     ORDER BY genre ASC"
);
$availableGenres = $genreStmt->fetchAll(PDO::FETCH_COLUMN);

// Ambil genre favorit yang sudah tersimpan sebelumnya (jika ada)
$savedGenreStmt = $pdo->prepare('SELECT genre FROM user_genres WHERE user_id = :user_id');
$savedGenreStmt->execute(['user_id' => $userId]);
$selectedGenres = $savedGenreStmt->fetchAll(PDO::FETCH_COLUMN);

$readingGoal = $currentUser['reading_goal'] !== null ? (int) $currentUser['reading_goal'] : 12;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'skip') {
        $skipStmt = $pdo->prepare('UPDATE users SET onboarding_done = 1 WHERE id = :id');
        $skipStmt->execute(['id' => $userId]);
        $_SESSION['onboarding_done'] = 1;

        header('Location: /reviewbuku/index.php');
        exit;
    }

    $goalRaw = trim($_POST['reading_goal'] ?? '');
    $postedGenres = $_POST['genres'] ?? [];
    if (!is_array($postedGenres)) {
        $postedGenres = [];
    }

    // Validasi target membaca (opsional dikosongkan, tapi jika diisi harus angka >= 1)
    $parsedGoal = null;
    if ($goalRaw !== '') {
        if (!ctype_digit($goalRaw) || (int) $goalRaw < 1 || (int) $goalRaw > 500) {
            $error = 'Target membaca harus berupa angka antara 1 sampai 500 buku.';
        } else {
            $parsedGoal = (int) $goalRaw;
        }
    }

    // Filter hanya genre yang valid dari katalog
    $validGenres = [];
    foreach ($postedGenres as $g) {
        $gStr = trim((string) $g);
        if (in_array($gStr, $availableGenres, true) && !in_array($gStr, $validGenres, true)) {
            $validGenres[] = $gStr;
        }
    }

    if ($error === '') {
        $pdo->beginTransaction();
        try {
            $updateUserStmt = $pdo->prepare(
                'UPDATE users
                 SET reading_goal = :reading_goal, onboarding_done = 1
                 WHERE id = :id'
            );
            $updateUserStmt->execute([
                'reading_goal' => $parsedGoal,
                'id' => $userId,
            ]);

            $deleteGenresStmt = $pdo->prepare('DELETE FROM user_genres WHERE user_id = :user_id');
            $deleteGenresStmt->execute(['user_id' => $userId]);

            if (!empty($validGenres)) {
                $insertGenreStmt = $pdo->prepare(
                    'INSERT INTO user_genres (user_id, genre) VALUES (:user_id, :genre)'
                );
                foreach ($validGenres as $genreItem) {
                    $insertGenreStmt->execute([
                        'user_id' => $userId,
                        'genre' => $genreItem,
                    ]);
                }
            }

            $pdo->commit();
            $_SESSION['onboarding_done'] = 1;

            header('Location: /reviewbuku/index.php');
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $error = 'Terjadi kesalahan saat menyimpan preferensi onboarding.';
        }
    }

    $readingGoal = $parsedGoal ?? 12;
    $selectedGenres = $validGenres;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding Pembaca - Review Buku</title>
    <link rel="stylesheet" href="/reviewbuku/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a href="/reviewbuku/index.php" class="brand-logo">Review Buku</a>
            <nav class="nav-links">
                <a href="/reviewbuku/books.php" class="nav-link">Semua Buku</a>
                <a href="/reviewbuku/logout.php" class="btn-secondary">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container" style="max-width: 680px;">
        <article class="detail-card">
            <span class="book-badge">Langkah Awal</span>
            <h1 class="hero-title" style="font-size: 1.5rem; margin-top: 8px;">
                Selamat Datang, <?= htmlspecialchars($currentUser['nama'], ENT_QUOTES, 'UTF-8') ?>!
            </h1>
            <p class="book-sinopsis" style="-webkit-line-clamp: unset; overflow: visible; margin-bottom: 16px;">
                Atur target bacaan tahun <?= $currentYear ?> dan pilih genre favoritmu agar rekomendasi buku terasa lebih pas. Kamu juga bisa melewatinya kapan saja.
            </p>

            <?php if ($error !== ''): ?>
                <div class="alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="POST" action="/reviewbuku/onboarding.php" id="onboardingForm">
                <!-- Step 1: Target Membaca Tahunan -->
                <section style="margin-bottom: 24px;">
                    <h2 style="font-size: 1.1rem; margin-bottom: 6px;">Step 1 — Target Membaca Tahun <?= $currentYear ?></h2>
                    <p class="book-meta" style="margin: 0 0 10px;">
                        Berapa target buku yang ingin kamu baca tahun ini? (Boleh dikosongkan)
                    </p>
                    <input
                        type="number"
                        id="reading_goal"
                        name="reading_goal"
                        class="form-control"
                        min="1"
                        max="500"
                        value="<?= htmlspecialchars((string) $readingGoal, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Contoh: 12"
                        style="max-width: 200px;"
                    >
                </section>

                <!-- Step 2: Genre Favorit -->
                <section style="margin-bottom: 24px;">
                    <h2 style="font-size: 1.1rem; margin-bottom: 6px;">Step 2 — Pilih Genre Favorit</h2>
                    <p class="book-meta" style="margin: 0 0 12px;">
                        Pilih satu atau beberapa genre yang paling menarik buatmu:
                    </p>

                    <?php if (empty($availableGenres)): ?>
                        <p class="book-meta">Belum ada data genre di katalog buku.</p>
                    <?php else: ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px;">
                            <?php foreach ($availableGenres as $genre): ?>
                                <?php $checked = in_array($genre, $selectedGenres, true); ?>
                                <label style="display: flex; align-items: center; gap: 8px; padding: 8px 10px; border: 1px solid #d5d5d5; border-radius: 6px; cursor: pointer;">
                                    <input
                                        type="checkbox"
                                        name="genres[]"
                                        value="<?= htmlspecialchars($genre, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $checked ? 'checked' : '' ?>
                                    >
                                    <span><?= htmlspecialchars($genre, ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <div style="display: flex; gap: 12px; flex-wrap: wrap; justify-content: space-between; align-items: center;">
                    <button type="submit" name="action" value="skip" class="btn-secondary" formnovalidate>
                        Lewati Dulu
                    </button>
                    <button type="submit" name="action" value="save" class="btn-primary">
                        Simpan &amp; Mulai Menjelajah
                    </button>
                </div>
            </form>
        </article>
    </main>
</body>
</html>