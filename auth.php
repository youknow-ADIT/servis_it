<?php
/**
 * Auth Middleware & Session Helpers
 */
if (session_status() === PHP_SESSION_NONE) {
    // Pada Vercel serverless functions, arahkan session storage ke folder /tmp yang writable
    if (!empty(getenv('VERCEL')) || !empty($_ENV['VERCEL'])) {
        @ini_set('session.save_path', '/tmp');
    }
    session_start();
}

/**
 * Cek apakah user sudah login session.
 * Jika belum, arahkan kembali ke halaman login.php
 */
function checkAuth() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header("Location: login.php?msg=harus_login");
        exit();
    }
}

/**
 * Cek status login (true/false) tanpa redirect
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}
?>
