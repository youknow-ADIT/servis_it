<?php
require_once 'config.php';
require_once 'auth.php';

// Pastikan hanya user login yang bisa update
checkAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'Antrian';
    $teknisi = trim($_POST['teknisi_nama'] ?? 'Teknisi IT');
    $tindakan = trim($_POST['tindakan'] ?? '');
    $biaya = floatval($_POST['biaya'] ?? 0);
    $catatan = trim($_POST['catatan'] ?? '');

    if ($id > 0) {
        // Jika status diubah menjadi Selesai, catat tanggal_selesai NOW()
        if ($status === 'Selesai') {
            $stmt = $conn->prepare("UPDATE `laporan_servis` SET 
                `status` = ?, 
                `teknisi_nama` = ?, 
                `tindakan` = ?, 
                `biaya` = ?, 
                `catatan` = ?,
                `tanggal_selesai` = IFNULL(`tanggal_selesai`, NOW())
                WHERE `id` = ?");
            $stmt->bind_param("sssdsi", $status, $teknisi, $tindakan, $biaya, $catatan, $id);
        } else {
            $stmt = $conn->prepare("UPDATE `laporan_servis` SET 
                `status` = ?, 
                `teknisi_nama` = ?, 
                `tindakan` = ?, 
                `biaya` = ?, 
                `catatan` = ?
                WHERE `id` = ?");
            $stmt->bind_param("sssdsi", $status, $teknisi, $tindakan, $biaya, $catatan, $id);
        }

        $stmt->execute();
        $stmt->close();

        header("Location: laporan.php?status_msg=updated");
        exit();
    }
}

header("Location: laporan.php");
exit();
?>
