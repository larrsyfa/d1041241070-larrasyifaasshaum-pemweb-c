CREATE DATABASE IF NOT EXISTS reviewbuku;
USE reviewbuku;

-- Tabel pengguna (2 role: user & admin, plus target bacaan & status onboarding)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  bio TEXT NULL,
  reading_goal INT UNSIGNED NULL,
  onboarding_done TINYINT(1) NOT NULL DEFAULT 0
);

-- Tabel katalog buku
CREATE TABLE IF NOT EXISTS books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(150) NOT NULL,
  penulis VARCHAR(100) NOT NULL,
  genre VARCHAR(50),
  sinopsis TEXT,
  cover VARCHAR(255),
  jumlah_halaman INT UNSIGNED NULL,
  tahun_terbit SMALLINT UNSIGNED NULL,
  penerbit VARCHAR(100) NULL
);

-- Tabel ulasan & rating buku
CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  book_id INT NOT NULL,
  rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  ulasan TEXT,
  tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- Tabel like ulasan per pengguna
CREATE TABLE IF NOT EXISTS review_likes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  review_id INT NOT NULL,
  user_id INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_review_user_like (review_id, user_id),
  CONSTRAINT fk_like_review FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
  CONSTRAINT fk_like_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel genre favorit pengguna saat onboarding
CREATE TABLE IF NOT EXISTS user_genres (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  genre VARCHAR(50) NOT NULL,
  UNIQUE KEY uq_user_genre (user_id, genre),
  CONSTRAINT fk_user_genres_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel status daftar bacaan / rak buku pengguna
CREATE TABLE IF NOT EXISTS user_books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  book_id INT NOT NULL,
  status ENUM('want_to_read', 'currently_reading', 'read') NOT NULL DEFAULT 'want_to_read',
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_book (user_id, book_id),
  CONSTRAINT fk_user_books_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_books_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);