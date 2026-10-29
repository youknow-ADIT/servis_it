-- Database: `servis_it_db`
-- Sistem Informasi Jasa Servis Komputer & Jaringan
-- Ekspor untuk XAMPP MySQL / phpMyAdmin

CREATE DATABASE IF NOT EXISTS `servis_it_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `servis_it_db`;

-- --------------------------------------------------------
-- Struktur Tabel `users` (Akun Login Session)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'teknisi') NOT NULL DEFAULT 'teknisi',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password Default:
-- username: admin   | password: admin123
-- username: teknisi | password: teknisi123
INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`) VALUES
(1, 'admin', '$2y$10$BinTEvId22CRQjLQvBoI8uvxdyv8rbGtL.TVF/ROA.6hIybbwP3hG', 'Administrator IT', 'admin'),
(2, 'teknisi', '$2y$10$l.62rkab6hLMMdLNXRScPuNEBnhGp.2SN9emDmh7k0LFwvYnajuJ.', 'Teknisi Jaringan & Komputer', 'teknisi');

-- --------------------------------------------------------
-- Struktur Tabel `laporan_servis` (Data Laporan Servis IT)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `laporan_servis`;
CREATE TABLE `laporan_servis` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_tiket` VARCHAR(30) NOT NULL UNIQUE,
  `nama_pelanggan` VARCHAR(100) NOT NULL,
  `no_hp` VARCHAR(20) NOT NULL,
  `alamat` TEXT NULL,
  `jenis_layanan` ENUM('Komputer', 'Jaringan') NOT NULL,
  `tipe_perangkat` VARCHAR(100) NOT NULL,
  `keluhan` TEXT NOT NULL,
  `tindakan` TEXT NULL,
  `biaya` DECIMAL(12,2) DEFAULT 0,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Diserahkan',
  `teknisi_nama` VARCHAR(100) DEFAULT 'Belum Ditentukan',
  `tanggal_masuk` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `tanggal_selesai` DATETIME NULL,
  `catatan` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contoh Data Awal
INSERT INTO `laporan_servis` 
(`kode_tiket`, `nama_pelanggan`, `no_hp`, `alamat`, `jenis_layanan`, `tipe_perangkat`, `keluhan`, `tindakan`, `biaya`, `status`, `teknisi_nama`, `tanggal_masuk`, `tanggal_selesai`) 
VALUES
('SRV-202610-001', 'Budi Santoso', '081234567890', 'Jl. Merdeka No. 12', 'Komputer', 'PC Desktop Core i7', 'Komputer sering mati mendadak saat render video dan bluescreen error', 'Ganti pasta thermal processor, pembersihan debu heatsink & ganti RAM 16GB', 350000, 'Selesai', 'Teknisi Jaringan & Komputer', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 1 DAY),
('SRV-202610-002', 'Klinik Sehat Medika', '085712345678', 'Jl. Sudirman Kav. 5', 'Jaringan', 'Router MikroTik RB750Gr3 + 3 Switch', 'Koneksi internet kantor sering RTO, antar ruangan tidak bisa cetak ke printer sharing', 'Konfigurasi ulang MikroTik, routing subnet LAN, crimping 4 kabel UTP Cat6 baru', 750000, 'Selesai', 'Teknisi Jaringan & Komputer', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 1 DAY),
('SRV-202610-003', 'PT. Maju Logistik', '082198765432', 'Kawasan Industri Blok B', 'Jaringan', 'Access Point Ruijie & Switch PoE', 'Perluasan jaringan WiFi untuk gudang logistik baru area 400m2', 'Penarikan kabel STP outdoor 80 meter, setup 2 unit AP Reyee PoE & manajemen SSID tamu', 1200000, 'Sedang Dikerjakan', 'Teknisi Jaringan & Komputer', NOW() - INTERVAL 1 DAY, NULL),
('SRV-202610-004', 'Rina Anggraini', '081377889900', 'Perumahan Griya Indah D-4', 'Komputer', 'Laptop Asus ROG Strix', 'Layar blank hitam setelah booting, kipas berputar kencang', 'Pengecekan chipset VGA dan fleksibel LCD, menunggu persetujuan pergantian sparepart', 150000, 'Menunggu Sparepart', 'Belum Ditentukan', NOW(), NULL),
('SRV-202610-005', 'Ahmad Fauzi', '089611223344', 'Jl. Mawar No. 8', 'Komputer', 'Laptop Lenovo ThinkPad', 'Upgrade SSD NVMe 512GB & migrasi OS Windows 11 Original', 'Antrian pengerjaan hari ini', 550000, 'Antrian', 'Belum Ditentukan', NOW(), NULL);
