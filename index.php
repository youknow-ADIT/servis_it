<?php
require_once 'config.php';
require_once 'auth.php';

$booking_success = false;
$tiket_baru = '';

// Proses form booking servis dari pengunjung website
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'booking_servis') {
    $nama = trim($_POST['nama_pelanggan'] ?? '');
    $hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $layanan = $_POST['jenis_layanan'] ?? 'Komputer';
    $perangkat = trim($_POST['tipe_perangkat'] ?? '');
    $keluhan = trim($_POST['keluhan'] ?? '');

    if (!empty($nama) && !empty($hp) && !empty($perangkat) && !empty($keluhan)) {
        // Generate kode tiket unik: SRV-TAHUNBULAN-RANDOM
        $tiket_baru = 'SRV-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -4));
        $status_awal = 'Diserahkan';
        $teknisi_default = 'Belum Ditentukan';
        $biaya_default = 0;

        $stmt = $conn->prepare("INSERT INTO `laporan_servis` 
            (`kode_tiket`, `nama_pelanggan`, `no_hp`, `alamat`, `jenis_layanan`, `tipe_perangkat`, `keluhan`, `biaya`, `status`, `teknisi_nama`, `tanggal_masuk`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $stmt->bind_param("sssssssdss", $tiket_baru, $nama, $hp, $alamat, $layanan, $perangkat, $keluhan, $biaya_default, $status_awal, $teknisi_default);
        if ($stmt->execute()) {
            $booking_success = true;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech Solution - Jasa Servis Komputer & Instalasi Jaringan Profesional</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- ================= NAVBAR ================= -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
                <?php $logo_nav = getAppLogo('light'); ?>
                <?php if ($logo_nav['found']): ?>
                    <img src="<?= $logo_nav['path']; ?>" alt="Logo" style="height: 38px; width: auto; object-fit: contain;">
                <?php else: ?>
                    <span class="brand-icon">
                        <i class="bi bi-hdd-network-fill"></i>
                    </span>
                <?php endif; ?>
                <span>Tech<span class="text-primary-accent">Solution</span></span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="#beranda">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#layanan-komputer">Servis Komputer</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#layanan-jaringan">Instalasi Jaringan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#biaya">Biaya</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#booking">Booking Servis</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-warning fw-semibold" href="tracking.php"><i class="bi bi-search me-1"></i> Lacak Servis</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#kontak">Kontak</a>
                    </li>
                </ul>

                <!-- Tombol Navbar Berdasarkan Login Session -->
                <div class="d-flex align-items-center gap-2">
                    <?php if (isLoggedIn()): ?>
                        <!-- Jika SUDAH login session -->
                        <div class="dropdown">
                            <button class="btn btn-primary d-flex align-items-center gap-2 dropdown-toggle py-2 px-3 rounded-pill" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-file-earmark-bar-graph-fill"></i>
                                <span>Menu Laporan Servis IT</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                                <li class="px-3 py-2 border-bottom">
                                    <small class="text-muted d-block">Login sebagai:</small>
                                    <strong class="text-dark"><?= htmlspecialchars($_SESSION['nama'] ?? 'Petugas IT'); ?></strong>
                                    <span class="badge bg-primary-subtle text-primary ms-1"><?= strtoupper($_SESSION['role'] ?? 'Teknisi'); ?></span>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2" href="laporan.php">
                                        <i class="bi bi-speedometer2 me-2 text-primary"></i> Dashboard & Laporan
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger py-2" href="logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Jika BELUM login session -->
                        <a href="login.php" class="btn btn-outline-light d-flex align-items-center gap-2 py-2 px-3 rounded-pill">
                            <i class="bi bi-person-lock"></i>
                            <span>Login Petugas IT</span>
                        </a>
                        <a href="#booking" class="btn btn-primary d-flex align-items-center gap-2 py-2 px-3 rounded-pill">
                            <i class="bi bi-wrench-adjustable"></i>
                            <span>Order Servis</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- ================= HERO SECTION ================= -->
    <header id="beranda" class="hero-section text-white py-5">
        <div class="container py-lg-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="badge bg-primary-subtle text-info border border-info border-opacity-25 px-3 py-2 rounded-pill mb-3">
                        <i class="bi bi-shield-check me-1"></i> Teknisi Profesional & Bergaransi
                    </span>
                    <h1 class="display-4 fw-bold lh-sm mb-3">
                        Solusi Servis <span class="text-primary-accent">Komputer</span> & Solusi <span class="text-primary-accent">Jaringan IT</span> Terpercaya
                    </h1>
                    <p class="lead text-light text-opacity-75 mb-4">
                        Kami melayani perbaikan PC, Laptop, penarikan kabel LAN kantor, instalasi Router MikroTik, WiFi Access Point, hingga kontrak maintenance IT untuk perusahaan & instansi.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="#booking" class="btn btn-primary btn-lg rounded-pill px-4">
                            <i class="bi bi-calendar-check me-2"></i> Booking Servis Sekarang
                        </a>
                        <a href="https://wa.me/6281360306866?text=Halo%20Tech%20Solution,%20saya%20ingin%20konsultasi%20jasa%20servis" target="_blank" class="btn btn-success btn-lg rounded-pill px-4">
                            <i class="bi bi-whatsapp me-2"></i> Chat WhatsApp
                        </a>
                    </div>
                    <div class="row mt-5 pt-3 border-top border-secondary border-opacity-50">
                        <div class="col-4">
                            <h3 class="fw-bold text-white mb-0">1.500+</h3>
                            <small class="text-light text-opacity-75">Unit PC/Laptop Selesai</small>
                        </div>
                        <div class="col-4">
                            <h3 class="fw-bold text-white mb-0">200+</h3>
                            <small class="text-light text-opacity-75">Proyek Jaringan LAN</small>
                        </div>
                        <div class="col-4">
                            <h3 class="fw-bold text-white mb-0">100%</h3>
                            <small class="text-light text-opacity-75">Garansi Pengerjaan</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="hero-visual-card p-4 rounded-4 shadow-lg border border-secondary border-opacity-25 bg-dark bg-opacity-75">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-secondary border-opacity-50">
                            <span class="text-white fw-bold"><i class="bi bi-activity text-success me-2"></i>Status Layanan Aktif</span>
                            <span class="badge bg-success">Online 24/6</span>
                        </div>
                        <div class="service-pill mb-2 p-3 rounded-3 bg-secondary bg-opacity-25 d-flex align-items-center gap-3">
                            <div class="icon-circle bg-primary text-white"><i class="bi bi-pc-display"></i></div>
                            <div>
                                <h6 class="text-white mb-0">Servis Hardware & Software PC</h6>
                                <small class="text-light text-opacity-75">Mati total, Bluescreen, Upgrade SSD & RAM</small>
                            </div>
                        </div>
                        <div class="service-pill mb-2 p-3 rounded-3 bg-secondary bg-opacity-25 d-flex align-items-center gap-3">
                            <div class="icon-circle bg-info text-white"><i class="bi bi-router-fill"></i></div>
                            <div>
                                <h6 class="text-white mb-0">MikroTik & WiFi Enterprise</h6>
                                <small class="text-light text-opacity-75">Bandwidth Management, Failover & VLAN</small>
                            </div>
                        </div>
                        <div class="service-pill p-3 rounded-3 bg-secondary bg-opacity-25 d-flex align-items-center gap-3">
                            <div class="icon-circle bg-warning text-dark"><i class="bi bi-ethernet"></i></div>
                            <div>
                                <h6 class="text-white mb-0">Instalasi Kabel LAN & Server</h6>
                                <small class="text-light text-opacity-75">Cat6, Server Rack, Switch & File Sharing</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Alert Notifikasi Jika Sukses Booking Tiket -->
    <?php if ($booking_success): ?>
    <div class="container mt-4">
        <div class="alert alert-success alert-dismissible fade show p-4 rounded-4 shadow-sm" role="alert">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-check-circle-fill text-success fs-1"></i>
                <div>
                    <h5 class="alert-heading fw-bold">Pendaftaran Servis Berhasil Dikirim!</h5>
                    <p class="mb-2">Nomor Tiket Servis Anda adalah: <strong class="badge bg-dark fs-6 font-monospace px-3 py-2"><?= htmlspecialchars($tiket_baru); ?></strong></p>
                    <p class="text-muted small mb-3">Data servis Anda telah tercatat otomatis di sistem Laporan Servis IT kami. Teknisi kami akan segera memeriksa perangkat dan menghubungi Anda melalui WhatsApp.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="tracking.php?q=<?= urlencode($tiket_baru); ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                            <i class="bi bi-crosshair me-1"></i> Pantau Progres Pengerjaan Tiket Ini
                        </a>
                        <a href="https://wa.me/6281360306866?text=Halo%20Admin,%20saya%20sudah%20daftar%20servis%20dengan%20Nomor%20Tiket%20<?= urlencode($tiket_baru); ?>" target="_blank" class="btn btn-sm btn-success rounded-pill px-3">
                            <i class="bi bi-whatsapp me-1"></i> Konfirmasi ke WhatsApp Teknisi
                        </a>
                    </div>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Tracking Banner untuk Pelanggan -->
    <div class="container my-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white" style="border-left: 5px solid #0284c7 !important;">
            <div class="row align-items-center g-3">
                <div class="col-lg-5">
                    <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-search text-primary me-2"></i>Lacak Posisi Servis Anda</h5>
                    <p class="text-muted small mb-0">Sudah servis di Tech Solution? Masukkan nomor tiket atau nomor WhatsApp untuk cek posisi pengerjaan dan nama teknisi Anda.</p>
                </div>
                <div class="col-lg-7">
                    <form action="tracking.php" method="GET" class="d-flex gap-2">
                        <input type="text" name="q" class="form-control rounded-pill" placeholder="Masukkan nomor tiket (contoh: SRV-202610-001)" required>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 text-nowrap">
                            <i class="bi bi-arrow-right-circle me-1"></i> Lacak Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= LAYANAN SERVIS KOMPUTER ================= -->
    <section id="layanan-komputer" class="py-5 bg-light">
        <div class="container py-4">
            <div class="text-center mb-5">
                <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill">PC & LAPTOP REPAIR</span>
                <h2 class="fw-bold mt-2">Jasa Servis Komputer & Laptop</h2>
                <p class="text-muted mx-auto" style="max-width: 650px;">
                    Kami menangani segala jenis kerusakan perangkat komputer Desktop, Laptop, All-in-One PC hingga Server dengan peralatan diagnostik lengkap.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm rounded-4 service-card p-3">
                        <div class="card-body">
                            <div class="service-icon-box bg-primary-subtle text-primary mb-3">
                                <i class="bi bi-cpu-fill fs-3"></i>
                            </div>
                            <h5 class="fw-bold">Perbaikan Hardware</h5>
                            <p class="text-muted small">
                                Mengatasi komputer mati total, ganti power supply (PSU), perbaikan motherboard, ganti keyboard & LCD pecah pada laptop.
                            </p>
                            <ul class="list-unstyled text-muted small mt-3">
                                <li><i class="bi bi-check2 text-primary me-2"></i>Mati total & No Display</li>
                                <li><i class="bi bi-check2 text-primary me-2"></i>Ganti Engsel & Casing</li>
                                <li><i class="bi bi-check2 text-primary me-2"></i>Servis IC Power & Chipset</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm rounded-4 service-card p-3">
                        <div class="card-body">
                            <div class="service-icon-box bg-success-subtle text-success mb-3">
                                <i class="bi bi-speedometer fs-3"></i>
                            </div>
                            <h5 class="fw-bold">Upgrade & Tune-Up</h5>
                            <p class="text-muted small">
                                Bikin komputer lama kembali ngebut dengan upgrade SSD NVMe M.2 / SATA, penambahan kapasitas RAM, dan ganti pasta pendingin.
                            </p>
                            <ul class="list-unstyled text-muted small mt-3">
                                <li><i class="bi bi-check2 text-success me-2"></i>SSD Cloning tanpa hilang data</li>
                                <li><i class="bi bi-check2 text-success me-2"></i>Dual Channel RAM Upgrade</li>
                                <li><i class="bi bi-check2 text-success me-2"></i>Repaste Thermal Paste High-End</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm rounded-4 service-card p-3">
                        <div class="card-body">
                            <div class="service-icon-box bg-warning-subtle text-warning mb-3">
                                <i class="bi bi-window-stack fs-3"></i>
                            </div>
                            <h5 class="fw-bold">Instalasi Software & OS</h5>
                            <p class="text-muted small">
                                Install ulang Windows 10/11 Original, Linux Ubuntu/Debian, aktivasi driver resmi, software perkantoran, dan software grafis/CAD.
                            </p>
                            <ul class="list-unstyled text-muted small mt-3">
                                <li><i class="bi bi-check2 text-warning me-2"></i>Bebas Virus & Malware</li>
                                <li><i class="bi bi-check2 text-warning me-2"></i>Driver Ter-update Stabil</li>
                                <li><i class="bi bi-check2 text-warning me-2"></i>Optimasi Booting & Registry</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm rounded-4 service-card p-3">
                        <div class="card-body">
                            <div class="service-icon-box bg-danger-subtle text-danger mb-3">
                                <i class="bi bi-database-check fs-3"></i>
                            </div>
                            <h5 class="fw-bold">Data Recovery</h5>
                            <p class="text-muted small">
                                Penyelamatan file berharga dari harddisk eksternal/internal, flashdisk, atau memory card yang terformat atau corrupt.
                            </p>
                            <ul class="list-unstyled text-muted small mt-3">
                                <li><i class="bi bi-check2 text-danger me-2"></i>Recovery Partisi Hilang</li>
                                <li><i class="bi bi-check2 text-danger me-2"></i>Hardisk Bad Sector</li>
                                <li><i class="bi bi-check2 text-danger me-2"></i>Kerahasiaan Data Terjamin</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= LAYANAN JASA JARINGAN ================= -->
    <section id="layanan-jaringan" class="py-5">
        <div class="container py-4">
            <div class="text-center mb-5">
                <span class="badge bg-info-subtle text-info-emphasis fw-bold px-3 py-2 rounded-pill">NETWORKING & INFRASTRUCTURE</span>
                <h2 class="fw-bold mt-2">Jasa Instalasi & Maintenance Jaringan</h2>
                <p class="text-muted mx-auto" style="max-width: 650px;">
                    Infrastruktur jaringan stabil dan cepat untuk kebutuhan kantor, instansi, cafe, gudang, maupun rumah tinggal.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border rounded-4 p-4 shadow-sm hover-lift">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="feature-icon bg-primary text-white"><i class="bi bi-diagram-3-fill"></i></div>
                            <h5 class="fw-bold mb-0">Instalasi LAN & Kabel UTP</h5>
                        </div>
                        <p class="text-muted small">
                            Penarikan kabel LAN Cat5e/Cat6/Cat6A outdoor dan indoor dengan ducting rapi. Terminasi ke modular jack, patch panel, dan crimping RJ45 standard T568B.
                        </p>
                        <ul class="list-unstyled text-secondary small">
                            <li><i class="bi bi-arrow-right-short text-primary"></i> Pengkabelan terstruktur rapi (Cable Management)</li>
                            <li><i class="bi bi-arrow-right-short text-primary"></i> Setup Switch Hub Gigabit & Switch PoE</li>
                            <li><i class="bi bi-arrow-right-short text-primary"></i> Penataan Wallmount & Server Rack</li>
                        </ul>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border rounded-4 p-4 shadow-sm hover-lift">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="feature-icon bg-info text-white"><i class="bi bi-router"></i></div>
                            <h5 class="fw-bold mb-0">Konfigurasi MikroTik & Router</h5>
                        </div>
                        <p class="text-muted small">
                            Setting router canggih untuk membagi bandwidth secara adil, menggabungkan 2 sumber internet (Load Balancing), dan proteksi keamanan internet kantor.
                        </p>
                        <ul class="list-unstyled text-secondary small">
                            <li><i class="bi bi-arrow-right-short text-info"></i> Bandwidth Management (Simple Queue / PCQ)</li>
                            <li><i class="bi bi-arrow-right-short text-info"></i> Failover Otomatis (Jika 1 ISP mati, pindah otomatis)</li>
                            <li><i class="bi bi-arrow-right-short text-info"></i> VPN Server / WireGuard / PPTP untuk akses jarak jauh</li>
                        </ul>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border rounded-4 p-4 shadow-sm hover-lift">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="feature-icon bg-success text-white"><i class="bi bi-wifi"></i></div>
                            <h5 class="fw-bold mb-0">WiFi Access Point Kantor</h5>
                        </div>
                        <p class="text-muted small">
                            Instalasi pemancar sinyal WiFi enterprise (Ubiquiti UniFi, Ruijie Reyee, TP-Link Omada) dengan seamless roaming tanpa putus saat berpindah ruangan.
                        </p>
                        <ul class="list-unstyled text-secondary small">
                            <li><i class="bi bi-arrow-right-short text-success"></i> Setup Captive Portal & Login Voucher Tamu</li>
                            <li><i class="bi bi-arrow-right-short text-success"></i> Pembagian SSID Karyawan dan Pengunjung</li>
                            <li><i class="bi bi-arrow-right-short text-success"></i> Eliminasi Blank Spot Sinyal WiFi</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= PAKET & ESTIMASI BIAYA ================= -->
    <section id="biaya" class="py-5 bg-light">
        <div class="container py-4">
            <div class="text-center mb-5">
                <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill">TRANSPARAN & BERSAING</span>
                <h2 class="fw-bold mt-2">Estimasi Biaya Jasa Servis</h2>
                <p class="text-muted mx-auto" style="max-width: 650px;">
                    Harga terjangkau dengan kualitas pengerjaan standar industri dan garansi uang kembali.
                </p>
            </div>

            <div class="row g-4 align-items-stretch">
                <!-- Paket 1 -->
                <div class="col-lg-4">
                    <div class="card h-100 border-0 rounded-4 shadow-sm p-4 text-center">
                        <div class="badge bg-secondary-subtle text-secondary mx-auto px-3 py-2 rounded-pill mb-3">PAKET PC / LAPTOP</div>
                        <h4 class="fw-bold">Servis & Tune-Up</h4>
                        <div class="my-3">
                            <span class="text-muted small">Mulai Dari</span>
                            <h2 class="fw-bold text-primary">Rp 75.000</h2>
                        </div>
                        <ul class="list-unstyled text-secondary small text-start my-4 lh-lg">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Pengecekan Kerusakan Hardware & Software</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Pembersihan Debu Kipas & Casing</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Install Ulang Windows + Driver Lengkap</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Garansi Pengerjaan 14 Hari</li>
                        </ul>
                        <a href="#booking" class="btn btn-outline-primary rounded-pill w-100 mt-auto">Pilih Paket</a>
                    </div>
                </div>

                <!-- Paket 2 (Recommended) -->
                <div class="col-lg-4">
                    <div class="card h-100 border-primary border-2 rounded-4 shadow p-4 text-center position-relative bg-white">
                        <div class="position-absolute top-0 start-50 translate-middle">
                            <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm">PALING POPULER</span>
                        </div>
                        <div class="badge bg-primary-subtle text-primary mx-auto px-3 py-2 rounded-pill mb-3 mt-2">PAKET JARINGAN KANTOR</div>
                        <h4 class="fw-bold">Setup MikroTik & WiFi</h4>
                        <div class="my-3">
                            <span class="text-muted small">Mulai Dari</span>
                            <h2 class="fw-bold text-primary">Rp 350.000</h2>
                        </div>
                        <ul class="list-unstyled text-secondary small text-start my-4 lh-lg">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Setting MikroTik Bandwidth Management</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Konfigurasi WiFi Hotspot / Voucher</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Setup Failover / Multi-WAN ISP</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Setting Firewall & Keamanan Jaringan</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Garansi Setting & Remote Support 30 Hari</li>
                        </ul>
                        <a href="#booking" class="btn btn-primary rounded-pill w-100 mt-auto">Pilih Paket</a>
                    </div>
                </div>

                <!-- Paket 3 -->
                <div class="col-lg-4">
                    <div class="card h-100 border-0 rounded-4 shadow-sm p-4 text-center">
                        <div class="badge bg-secondary-subtle text-secondary mx-auto px-3 py-2 rounded-pill mb-3">INSTALASI LAN</div>
                        <h4 class="fw-bold">Penarikan Titik Kabel LAN</h4>
                        <div class="my-3">
                            <span class="text-muted small">Mulai Dari</span>
                            <h2 class="fw-bold text-primary">Rp 90.000 <span class="fs-6 text-muted fw-normal">/titik</span></h2>
                        </div>
                        <ul class="list-unstyled text-secondary small text-start my-4 lh-lg">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Penarikan Kabel UTP Cat6 Berkualitas</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Crimping RJ45 & Faceplate Modular</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Labeling Penomoran Kabel Terstruktur</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Fluke/Cable Tester Quality Check</li>
                        </ul>
                        <a href="#booking" class="btn btn-outline-primary rounded-pill w-100 mt-auto">Pilih Paket</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= FORM BOOKING SERVIS ================= -->
    <section id="booking" class="py-5">
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5">
                        <div class="text-center mb-4">
                            <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill">FORM KONSULTASI / BOOKING</span>
                            <h3 class="fw-bold mt-2">Daftarkan Perangkat & Servis Anda</h3>
                            <p class="text-muted small">Isi formulir di bawah ini. Tiket servis otomatis terbit dan langsung masuk ke Laporan Antrian Teknisi IT.</p>
                        </div>

                        <form method="POST" action="index.php#booking">
                            <input type="hidden" name="action" value="booking_servis">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-secondary">Nama Lengkap / Nama Perusahaan *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                                        <input type="text" name="nama_pelanggan" class="form-control" placeholder="Contoh: Budi Santoso / PT. Surya" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-secondary">Nomor WhatsApp / HP Aktif *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-whatsapp text-success"></i></span>
                                        <input type="tel" name="no_hp" class="form-control" placeholder="Contoh: 081234567890" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-secondary">Pilih Kategori Layanan *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-gear"></i></span>
                                        <select name="jenis_layanan" class="form-select" required>
                                            <option value="Komputer">Servis Komputer / Laptop</option>
                                            <option value="Jaringan">Instalasi / Setting Jaringan</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-secondary">Tipe / Merk Perangkat *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-laptop"></i></span>
                                        <input type="text" name="tipe_perangkat" class="form-control" placeholder="Contoh: Laptop Asus VivoBook / MikroTik RB951" required>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold small text-secondary">Deskripsi Masalah / Keluhan Kerusakan *</label>
                                    <textarea name="keluhan" class="form-control" rows="3" placeholder="Ceritakan detail kerusakan, gejala awal, atau permintaan konfigurasi jaringan..." required></textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold small text-secondary">Alamat / Lokasi Pengerjaan (Opsional)</label>
                                    <input type="text" name="alamat" class="form-control" placeholder="Masukkan alamat jika memerlukan servis panggilan atau pengerjaan di lokasi (on-site)">
                                </div>

                                <div class="col-12 text-center mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm">
                                        <i class="bi bi-send-check me-2"></i> Kirim Tiket Servis Sekarang
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= KONTAK & FOOTER ================= -->
    <footer id="kontak" class="bg-dark text-white pt-5 pb-4 border-top border-secondary border-opacity-25">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-2 fw-bold fs-4 mb-3">
                        <?php $logo_footer = getAppLogo('light'); ?>
                        <?php if ($logo_footer['found']): ?>
                            <img src="<?= $logo_footer['path']; ?>" alt="Logo" style="height: 38px; width: auto; object-fit: contain;">
                        <?php else: ?>
                            <span class="brand-icon"><i class="bi bi-hdd-network-fill"></i></span>
                        <?php endif; ?>
                        <span>Tech<span class="text-primary-accent">Solution</span></span>
                    </div>
                    <p class="text-light text-opacity-75 small">
                        Penyedia layanan servis komputer, laptop, dan instalasi infrastruktur jaringan komputer terdepan untuk instansi, kantor, dan perorangan.
                    </p>
                    <div class="d-flex gap-2">
                        <a href="https://wa.me/6281360306866" target="_blank" class="btn btn-sm btn-outline-light rounded-circle"><i class="bi bi-whatsapp"></i></a>
                        <a href="https://www.facebook.com/username_facebook_anda" target="_blank" class="btn btn-sm btn-outline-light rounded-circle"><i class="bi bi-facebook"></i></a>
                        <a href="https://www.instagram.com/username_ig_anda/" target="_blank" class="btn btn-sm btn-outline-light rounded-circle"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>

                <div class="col-lg-4">
                    <h5 class="fw-bold text-white mb-3">Kontak & Workshop</h5>
                    <ul class="list-unstyled text-light text-opacity-75 small">
                        <li class="mb-2"><i class="bi bi-geo-alt-fill text-danger me-2"></i> Tanjung Morawa - <strong><em>Sumatera Utara</em></strong></li>
                        <li class="mb-2"><i class="bi bi-telephone-fill text-success me-2"></i> 0813 6030 6866</li>
                        <li class="mb-2"><i class="bi bi-envelope-fill text-warning me-2"></i> aditya.pratama@pelitanusantara.ac.id</li>
                        <li class="mb-2"><i class="bi bi-clock-fill text-info me-2"></i> Senin - Sabtu: 08:30 - 20:00 WIB</li>
                    </ul>
                </div>

                <div class="col-lg-4">
                    <h5 class="fw-bold text-white mb-3">Akses Internal Petugas</h5>
                    <p class="text-light text-opacity-75 small">
                        Halaman khusus teknisi dan admin IT untuk mengelola antrian, laporan servis, dan update status pengerjaan.
                    </p>
                    <?php if (isLoggedIn()): ?>
                        <a href="laporan.php" class="btn btn-primary btn-sm rounded-pill px-3">
                            <i class="bi bi-file-earmark-text me-1"></i> Buka Menu Laporan Servis IT
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Login Petugas IT
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="border-top border-secondary border-opacity-25 pt-3 text-center text-light text-opacity-50 small">
                &copy; <?= date('Y'); ?> Tech Solution. Aditya Pratama - 250121156-TIF B202
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tutup menu mobile otomatis setelah link diklik
        document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                const navbarEl = document.getElementById('navbarNav');
                if (navbarEl && navbarEl.classList.contains('show')) {
                    const bsCollapse = bootstrap.Collapse.getInstance(navbarEl) || new bootstrap.Collapse(navbarEl, {toggle: false});
                    bsCollapse.hide();
                }
            });
        });
    </script>
</body>
</html>
