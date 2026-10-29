<?php
require_once 'config.php';
require_once 'auth.php';

// Pastikan hanya user login yang bisa menambah data servis dari admin
checkAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_pelanggan'] ?? '');
    $hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $layanan = $_POST['jenis_layanan'] ?? 'Komputer';
    $perangkat = trim($_POST['tipe_perangkat'] ?? '');
    $keluhan = trim($_POST['keluhan'] ?? '');
    $teknisi = trim($_POST['teknisi_nama'] ?? 'Teknisi IT');
    $biaya = floatval($_POST['biaya'] ?? 0);

    if (!empty($nama) && !empty($hp) && !empty($perangkat) && !empty($keluhan)) {
        // Generate kode tiket unik: SRV-TAHUNBULAN-RANDOM
        $kode_tiket = 'SRV-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -4));
        $status_awal = 'Diserahkan';

        $stmt = $conn->prepare("INSERT INTO `laporan_servis` 
            (`kode_tiket`, `nama_pelanggan`, `no_hp`, `alamat`, `jenis_layanan`, `tipe_perangkat`, `keluhan`, `biaya`, `status`, `teknisi_nama`, `tanggal_masuk`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $stmt->bind_param("sssssssdss", $kode_tiket, $nama, $hp, $alamat, $layanan, $perangkat, $keluhan, $biaya, $status_awal, $teknisi);
        $stmt->execute();
        $stmt->close();

        header("Location: laporan.php?status_msg=saved");
        exit();
    }
}

header("Location: laporan.php");
exit();
?>
