<?php
require_once 'config.php';
require_once 'auth.php';

// Pastikan user sudah login
checkAuth();

// Menerima ID via POST (rekomendasi keamanan) atau fallback GET
$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM `laporan_servis` WHERE `id` = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: laporan.php?status_msg=deleted");
    exit();
}

header("Location: laporan.php");
exit();
?>
