<?php

/**
 * public/index.php - Front controller / satu-satunya entry point aplikasi.
 *
 * Semua halaman diakses lewat sini menggunakan parameter ?page=,
 * misalnya:
 *   public/index.php?page=login
 *   public/index.php?page=dashboard_admin
 *
 * File di app/views/ TIDAK diakses langsung oleh browser lagi,
 * melainkan di-require dari sini. Whitelist di bawah mencegah
 * Local File Inclusion (LFI) - hanya halaman yang terdaftar
 * yang bisa di-load, apapun input ?page= dari user.
 */

$page = $_GET['page'] ?? 'login';

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$requestPath = rtrim(str_replace('\\', '/', $requestPath), '/');

if (basename($requestPath) === 'index.php') {
    $publicBaseUrl = dirname($requestPath);
    $appBaseUrl = dirname($publicBaseUrl);
} else {
    $appBaseUrl = $requestPath;
    $publicBaseUrl = $appBaseUrl . '/public';
}

$appBaseUrl = $appBaseUrl === '/' ? '' : rtrim($appBaseUrl, '/');
$publicBaseUrl = $publicBaseUrl === '/' ? '' : rtrim($publicBaseUrl, '/');

$allowedPages = [
    'login'              => __DIR__ . '/../app/views/login.php',
    'dashboard_admin'    => __DIR__ . '/../app/views/dashboard_admin.php',
    'data_buku'          => __DIR__ . '/../app/views/data_buku.php',
    'kelola_peminjaman'  => __DIR__ . '/../app/views/kelola_peminjaman.php',
    'peminjaman_siswa'   => __DIR__ . '/../app/views/peminjaman_siswa.php',
    'riwayat_siswa'      => __DIR__ . '/../app/views/riwayat_siswa.php',
    'data_anggota'       => __DIR__ . '/../app/views/data_anggota.php',
    'laporan_denda'      => __DIR__ . '/../app/views/laporan_denda.php',
];

if (!array_key_exists($page, $allowedPages)) {
    http_response_code(404);
    echo '404 - Halaman tidak ditemukan';
    exit;
}

require $allowedPages[$page];
