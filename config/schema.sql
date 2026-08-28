-- Skema database Sistem Peminjaman Buku Perpustakaan Sekolah
-- Ganti nama database sesuai keinginanmu di config/Database.php

CREATE DATABASE IF NOT EXISTS perpus_kelompok4;
USE perpus_kelompok4;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'siswa') NOT NULL DEFAULT 'siswa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(150) NOT NULL,
    isbn VARCHAR(30) NOT NULL UNIQUE,
    category_id INT NOT NULL,
    stok INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    batas_kembali DATE NOT NULL,
    tanggal_kembali DATE NULL,
    status ENUM('dipinjam', 'dikembalikan', 'terlambat') NOT NULL DEFAULT 'dipinjam',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- Data contoh kategori
INSERT INTO categories (nama_kategori) VALUES ('Fiksi'), ('Sains'), ('Sejarah'), ('Biografi');

-- Akun admin contoh (password: admin123, sudah di-hash dengan password_hash PHP)
-- Silakan generate ulang hash-nya lewat: php -r "echo password_hash('admin123', PASSWORD_BCRYPT);"
