<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$error = '';
$success = '';

// Hapus buku
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare('DELETE FROM books WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $success = 'Buku berhasil dihapus.';
    }
}

// Tambah atau edit buku
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(($_POST['action'] ?? ''), ['create', 'update'], true)) {
    $action = $_POST['action'];
    $id = (int) ($_POST['id'] ?? 0);
    $judul = trim($_POST['judul'] ?? '');
    $penulis = trim($_POST['penulis'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    $sinopsis = trim($_POST['sinopsis'] ?? '');
    $cover = trim($_POST['cover'] ?? '');

    if ($judul === '' || $penulis === '') {
        $error = 'Judul dan penulis wajib diisi.';
    } else {
        if ($action === 'create') {
            $stmt = $pdo->prepare(
                'INSERT INTO books (judul, penulis, genre, sinopsis, cover) VALUES (:judul, :penulis, :genre, :sinopsis, :cover)'
            );
            $stmt->execute([
                'judul' => $judul,
                'penulis' => $penulis,
                'genre' => $genre !== '' ? $genre : null,
                'sinopsis' => $sinopsis !== '' ? $sinopsis : null,
                'cover' => $cover !== '' ? $cover : null,
            ]);
            $success = 'Buku baru berhasil ditambahkan.';
        } elseif ($action === 'update' && $id > 0) {
            $stmt = $pdo->prepare(
                'UPDATE books SET judul = :judul, penulis = :penulis, genre = :genre, sinopsis = :sinopsis, cover = :cover WHERE id = :id'
            );
            $stmt->execute([
                'id' => $id,
                'judul' => $judul,
                'penulis' => $penulis,
                'genre' => $genre !== '' ? $genre : null,
                'sinopsis' => $sinopsis !== '' ? $sinopsis : null,
                'cover' => $cover !== '' ? $cover : null,
            ]);
            $success = 'Data buku berhasil diperbarui.';
        }
    }
}

// Ambil data buku untuk mode edit jika ada ?edit=ID
$editBook = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    if ($editId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $editId]);
        $editBook = $stmt->fetch();
    }
}

// Ambil seluruh daftar buku
$books = $pdo->query('SELECT * FROM books ORDER BY id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Buku (Admin) - Review Buku</title>
</head>
<body>
    <h2>Kelola Buku (Admin)</h2>
    <p>
        Login sebagai: <strong><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></strong> |
        <a href="/reviewbuku/index.php">Beranda</a> |
        <a href="/reviewbuku/logout.php">Logout</a>
    </p>

    <?php if ($error !== ''): ?>
        <p style="color: red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <p style="color: green;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <h3><?= $editBook ? 'Edit Buku #' . (int) $editBook['id'] : 'Tambah Buku Baru' ?></h3>
    <form method="POST" action="/reviewbuku/admin/books.php">
        <input type="hidden" name="action" value="<?= $editBook ? 'update' : 'create' ?>">
        <?php if ($editBook): ?>
            <input type="hidden" name="id" value="<?= (int) $editBook['id'] ?>">
        <?php endif; ?>

        <div>
            <label>Judul</label><br>
            <input type="text" name="judul" value="<?= htmlspecialchars($editBook['judul'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div><br>

        <div>
            <label>Penulis</label><br>
            <input type="text" name="penulis" value="<?= htmlspecialchars($editBook['penulis'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div><br>

        <div>
            <label>Genre</label><br>
            <input type="text" name="genre" value="<?= htmlspecialchars($editBook['genre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div><br>

        <div>
            <label>Sinopsis</label><br>
            <textarea name="sinopsis" rows="4" cols="40"><?= htmlspecialchars($editBook['sinopsis'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div><br>

        <div>
            <label>Cover (nama file / URL)</label><br>
            <input type="text" name="cover" value="<?= htmlspecialchars($editBook['cover'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div><br>

        <button type="submit"><?= $editBook ? 'Simpan Perubahan' : 'Tambah Buku' ?></button>
        <?php if ($editBook): ?>
            <a href="/reviewbuku/admin/books.php">Batal Edit</a>
        <?php endif; ?>
    </form>

    <hr>

    <h3>Daftar Buku</h3>
    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Judul</th>
                <th>Penulis</th>
                <th>Genre</th>
                <th>Sinopsis</th>
                <th>Cover</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($books)): ?>
                <tr>
                    <td colspan="7">Belum ada data buku.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td><?= (int) $book['id'] ?></td>
                        <td><?= htmlspecialchars($book['judul'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($book['penulis'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($book['genre'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($book['sinopsis'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($book['cover'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="/reviewbuku/admin/books.php?edit=<?= (int) $book['id'] ?>">Edit</a>
                            <form method="POST" action="/reviewbuku/admin/books.php" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus buku ini?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $book['id'] ?>">
                                <button type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>