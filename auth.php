<?php
/**
 * Auth Middleware & Session Helpers
 */
if (session_status() === PHP_SESSION_NONE) {
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
