<?php
/**
 * Vercel Serverless Function Router
 * Project: Tech Solution Servis IT
 * 
 * Router ini menerima semua request dari Vercel dan meneruskannya
 * ke file PHP terkait di root project tanpa mengubah struktur aplikasi.
 */

// Pindahkan working directory ke root project agar require lokal tetap bekerja
chdir(__DIR__ . '/..');

// Ambil path request URI
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

// 1. Jika akses root URL atau /index.php -> jalankan index.php
if (empty($path) || $path === 'index.php') {
    require __DIR__ . '/../index.php';
    exit();
}

// 2. Ambil nama file dan validasi keamanan (cegah path traversal)
$filename = basename($path);
$target_file = __DIR__ . '/../' . $filename;

// 3. Jika file PHP yang diminta ada di root, panggil file tersebut
if (file_exists($target_file) && pathinfo($target_file, PATHINFO_EXTENSION) === 'php') {
    require $target_file;
    exit();
}

// 4. Fallback 404 jika file tidak ditemukan
http_response_code(404);
echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body style='font-family:sans-serif;text-align:center;padding:50px;'><h2>404 - Halaman Tidak Ditemukan</h2><p><a href='/'>Kembali ke Halaman Utama</a></p></body></html>";
exit();
