<?php
require_once 'config.php';
require_once 'auth.php';

$search = trim($_GET['q'] ?? $_GET['tiket'] ?? '');
$tiket_data = null;
$list_tiket = [];
$error_search = '';

if (!empty($search)) {
    // Cari berdasarkan kode tiket persis atau nomor HP
    $stmt = $conn->prepare("SELECT * FROM `laporan_servis` WHERE `kode_tiket` = ? OR `no_hp` = ? OR `no_hp` LIKE ? ORDER BY `tanggal_masuk` DESC");
    $hp_like = "%$search%";
    $stmt->bind_param("sss", $search, $search, $hp_like);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $tiket_data = $result->fetch_assoc();
    } elseif ($result->num_rows > 1) {
        // Jika pencarian dengan nomor HP menghasilkan lebih dari 1 tiket
        while ($r = $result->fetch_assoc()) {
            $list_tiket[] = $r;
        }
    } else {
        $error_search = "Data tiket servis dengan kata kunci '<strong>" . htmlspecialchars($search) . "</strong>' tidak ditemukan. Pastikan nomor tiket atau nomor WhatsApp Anda benar.";
    }
    $stmt->close();
}

// Jika memilih salah satu dari daftar tiket (jika ada lebih dari 1 tiket untuk 1 nomor HP)
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $single_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM `laporan_servis` WHERE `id` = ? LIMIT 1");
    $stmt->bind_param("i", $single_id);
    $stmt->execute();
    $tiket_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

/**
 * Menentukan Step aktif (1 s/d 5) berdasarkan status saat ini
 */
function getTrackingStep($status) {
    switch ($status) {
        case 'Diserahkan':
        case 'Antrian':
            return 1;
        case 'Pengecekan & Diagnosis':
            return 2;
        case 'Proses Pengerjaan':
        case 'Sedang Dikerjakan':
            return 3;
        case 'Menunggu Sparepart':
            return 3; // Tetap di step pengerjaan dengan catatan khusus
        case 'Pengujian (QC)':
            return 4;
        case 'Selesai':
            return 5;
        case 'Dibatalkan':
            return -1;
        default:
            return 1;
    }
}

$current_step = $tiket_data ? getTrackingStep($tiket_data['status']) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Status Servis - Tech Solution</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Progress Stepper Styling */
        .stepper-wrapper {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 25px;
        }
        .stepper-wrapper::before {
            content: '';
            position: absolute;
            top: 24px;
            left: 30px;
            right: 30px;
            height: 4px;
            background-color: #e2e8f0;
            z-index: 1;
        }
        .stepper-progress {
            position: absolute;
            top: 24px;
            left: 30px;
            height: 4px;
            background: linear-gradient(90deg, #0284c7 0%, #10b981 100%);
            z-index: 2;
            transition: width 0.4s ease;
        }
        .step-item {
            position: relative;
            z-index: 3;
            text-align: center;
            flex: 1;
        }
        .step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #fff;
            border: 3px solid #cbd5e1;
            color: #94a3b8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }
        .step-item.completed .step-circle {
            background-color: #10b981;
            border-color: #10b981;
            color: #fff;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
        }
        .step-item.active .step-circle {
            background-color: #0284c7;
            border-color: #38bdf8;
            color: #fff;
            box-shadow: 0 0 0 5px rgba(2, 132, 199, 0.2);
            animation: pulse-ring 2s infinite;
        }
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(2, 132, 199, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(2, 132, 199, 0); }
            100% { box-shadow: 0 0 0 0 rgba(2, 132, 199, 0); }
        }
        .step-title {
            font-weight: 700;
            font-size: 0.85rem;
            color: #64748b;
        }
        .step-item.completed .step-title {
            color: #10b981;
        }
        .step-item.active .step-title {
            color: #0284c7;
        }
        .step-desc {
            font-size: 0.72rem;
            color: #94a3b8;
            max-width: 130px;
            margin: 0 auto;
            line-height: 1.2;
        }

        /* Technician Card */
        .tech-card {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #bae6fd;
            border-radius: 16px;
        }
        .tech-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            box-shadow: 0 4px 10px rgba(2, 132, 199, 0.3);
        }

        @media (max-width: 768px) {
            .stepper-wrapper {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            .stepper-wrapper::before, .stepper-progress {
                left: 24px;
                top: 25px;
                bottom: 25px;
                width: 4px;
                height: auto;
            }
            .step-item {
                display: flex;
                align-items: center;
                gap: 15px;
                text-align: left;
            }
            .step-circle {
                margin-bottom: 0;
                flex-shrink: 0;
            }
            .step-desc {
                margin: 0;
                max-width: none;
            }
        }
    </style>
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
                <?php $logo_nav = getAppLogo('light'); ?>
                <?php if ($logo_nav['found']): ?>
                    <img src="<?= $logo_nav['path']; ?>" alt="Logo" style="height: 38px; width: auto; object-fit: contain;">
                <?php else: ?>
                    <span class="brand-icon"><i class="bi bi-hdd-network-fill"></i></span>
                <?php endif; ?>
                <span>Tech<span class="text-primary-accent">Solution</span></span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTracking" aria-controls="navbarTracking" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarTracking">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-house me-1"></i> Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="tracking.php"><i class="bi bi-search me-1"></i> Lacak Status Servis</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#booking"><i class="bi bi-wrench me-1"></i> Booking Servis Baru</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#kontak"><i class="bi bi-telephone me-1"></i> Kontak</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <?php if (isLoggedIn()): ?>
                        <a href="laporan.php" class="btn btn-primary rounded-pill px-3">
                            <i class="bi bi-clipboard2-data me-1"></i> Menu Laporan Servis
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light rounded-pill px-3">
                            <i class="bi bi-person-lock me-1"></i> Login Petugas IT
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header Section & Search Form -->
    <div class="bg-dark text-white py-5 position-relative overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #0369a1 100%);">
        <div class="container py-3">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <span class="badge bg-primary-subtle text-info border border-info border-opacity-25 px-3 py-2 rounded-pill mb-3">
                        <i class="bi bi-crosshair me-1"></i> Pelacakan Progres Servis Real-Time
                    </span>
                    <h2 class="display-6 fw-bold mb-3">Pantau Posisi Pengerjaan Perangkat Anda</h2>
                    <p class="text-light text-opacity-75 mb-4">
                        Ketahui tahap perbaikan komputer atau instalasi jaringan Anda secara transparan lengkap dengan nama teknisi penanggung jawab.
                    </p>

                    <!-- Form Pencarian Tiket -->
                    <form method="GET" action="tracking.php" class="card p-2 p-md-3 border-0 shadow-lg rounded-4 bg-white">
                        <div class="row g-2">
                            <div class="col-md-9">
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white border-0 text-muted ps-3"><i class="bi bi-search"></i></span>
                                    <input type="text" name="q" value="<?= htmlspecialchars($search); ?>" class="form-control border-0 ps-1" placeholder="Masukkan Nomor Tiket (contoh: SRV-202610-001)" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill w-100 fw-bold">
                                    <i class="bi bi-arrow-right-circle me-1"></i> Cek Status
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <main class="container py-5">

        <!-- Pesan Error Jika Tidak Ditemukan -->
        <?php if (!empty($error_search)): ?>
            <div class="alert alert-danger p-4 rounded-4 shadow-sm mb-5 text-center">
                <i class="bi bi-exclamation-triangle-fill fs-1 d-block mb-2 text-danger"></i>
                <h5>Nomor Tiket Tidak Ditemukan</h5>
                <p class="mb-0 text-muted"><?= $error_search; ?></p>
            </div>
        <?php endif; ?>

        <!-- Jika Ada Lebih Dari 1 Tiket untuk Nomor HP Tersebut -->
        <?php if (!empty($list_tiket) && !$tiket_data): ?>
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-5">
                <h5 class="fw-bold mb-3"><i class="bi bi-list-check text-primary me-2"></i>Daftar Perangkat Servis Anda (<?= count($list_tiket); ?> Tiket Terdaftar)</h5>
                <p class="text-muted small">Ditemukan beberapa unit servis untuk nomor HP yang Anda masukkan. Silakan pilih perangkat yang ingin dipantau:</p>
                <div class="list-group list-group-flush">
                    <?php foreach ($list_tiket as $item): ?>
                        <a href="tracking.php?id=<?= $item['id']; ?>" class="list-group-item list-group-item-action p-3 rounded-3 mb-2 border d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-dark font-monospace mb-1"><?= htmlspecialchars($item['kode_tiket']); ?></span>
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($item['tipe_perangkat']); ?></h6>
                                <small class="text-muted"><?= htmlspecialchars($item['jenis_layanan']); ?> | Masuk: <?= date('d/m/Y', strtotime($item['tanggal_masuk'])); ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary px-3 py-2 rounded-pill"><?= htmlspecialchars($item['status']); ?></span>
                                <div class="small text-muted mt-1">Klik untuk detail &rarr;</div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- HASIL TRACKING STATUS SERVIS -->
        <?php if ($tiket_data): ?>
            
            <div class="row g-4">
                <!-- Kolom Kiri: Stepper Progres & Detail Pengerjaan -->
                <div class="col-lg-8">
                    
                    <!-- Kartu Status Utama -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                        <div class="d-flex flex-wrap justify-content-between align-items-center pb-3 border-bottom mb-4">
                            <div>
                                <small class="text-muted d-block">Nomor Tiket Servis:</small>
                                <h4 class="fw-bold font-monospace text-primary mb-0"><?= htmlspecialchars($tiket_data['kode_tiket']); ?></h4>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block">Tanggal Masuk:</small>
                                <span class="fw-semibold text-dark"><?= date('d F Y, H:i', strtotime($tiket_data['tanggal_masuk'])); ?> WIB</span>
                            </div>
                        </div>

                        <!-- KONDISI JIKA DIBATALKAN -->
                        <?php if ($tiket_data['status'] === 'Dibatalkan'): ?>
                            <div class="alert alert-danger p-4 rounded-4 mb-4">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-x-circle-fill fs-1 text-danger"></i>
                                    <div>
                                        <h5 class="fw-bold mb-1">Pengerjaan Servis Dibatalkan</h5>
                                        <p class="mb-0 small">Tiket servis ini telah dibatalkan atau unit dikembalikan atas permintaan pelanggan / kesepakatan teknisi.</p>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- PROGRESS STEPPER (5 TAHAP SESUAI KEBUTUHAN PELANGGAN) -->
                            <div class="mb-4">
                                <h6 class="fw-bold text-dark mb-4"><i class="bi bi-diagram-3-fill text-primary me-2"></i>Tahap Pengerjaan Saat Ini:</h6>
                                
                                <div class="stepper-wrapper">
                                    <!-- Dynamic Progress Bar Width -->
                                    <?php
                                        $progress_pct = 0;
                                        if ($current_step == 1) $progress_pct = 0;
                                        elseif ($current_step == 2) $progress_pct = 25;
                                        elseif ($current_step == 3) $progress_pct = 50;
                                        elseif ($current_step == 4) $progress_pct = 75;
                                        elseif ($current_step == 5) $progress_pct = 100;
                                    ?>
                                    <div class="stepper-progress" style="width: calc(<?= $progress_pct; ?>% - 10px);"></div>

                                    <!-- Step 1: Diserahkan -->
                                    <div class="step-item <?= $current_step > 1 ? 'completed' : ($current_step == 1 ? 'active' : ''); ?>">
                                        <div class="step-circle">
                                            <?= $current_step > 1 ? '<i class="bi bi-check-lg"></i>' : '<i class="bi bi-box-seam"></i>'; ?>
                                        </div>
                                        <div class="step-title">1. Diserahkan</div>
                                        <div class="step-desc">Unit masuk & keluhan dicatat</div>
                                    </div>

                                    <!-- Step 2: Diagnosis -->
                                    <div class="step-item <?= $current_step > 2 ? 'completed' : ($current_step == 2 ? 'active' : ''); ?>">
                                        <div class="step-circle">
                                            <?= $current_step > 2 ? '<i class="bi bi-check-lg"></i>' : '<i class="bi bi-search"></i>'; ?>
                                        </div>
                                        <div class="step-title">2. Diagnosis</div>
                                        <div class="step-desc">Pemeriksaan kendala oleh teknisi</div>
                                    </div>

                                    <!-- Step 3: Proses Pengerjaan -->
                                    <div class="step-item <?= $current_step > 3 ? 'completed' : ($current_step == 3 ? 'active' : ''); ?>">
                                        <div class="step-circle">
                                            <?= $current_step > 3 ? '<i class="bi bi-check-lg"></i>' : '<i class="bi bi-tools"></i>'; ?>
                                        </div>
                                        <div class="step-title">3. Pengerjaan</div>
                                        <div class="step-desc">
                                            <?= ($tiket_data['status'] === 'Menunggu Sparepart') ? '<span class="text-danger fw-bold">Menunggu Part</span>' : 'Perbaikan / Konfigurasi'; ?>
                                        </div>
                                    </div>

                                    <!-- Step 4: Pengujian & QC -->
                                    <div class="step-item <?= $current_step > 4 ? 'completed' : ($current_step == 4 ? 'active' : ''); ?>">
                                        <div class="step-circle">
                                            <?= $current_step > 4 ? '<i class="bi bi-check-lg"></i>' : '<i class="bi bi-shield-check"></i>'; ?>
                                        </div>
                                        <div class="step-title">4. Pengujian (QC)</div>
                                        <div class="step-desc">Uji kestabilan & performa</div>
                                    </div>

                                    <!-- Step 5: Selesai -->
                                    <div class="step-item <?= $current_step == 5 ? 'completed' : ''; ?>">
                                        <div class="step-circle">
                                            <i class="bi bi-check2-all"></i>
                                        </div>
                                        <div class="step-title">5. Selesai</div>
                                        <div class="step-desc">Siap diambil / serah terima</div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Box Informasi Status Saat Ini -->
                        <div class="p-3 rounded-4 mb-4 <?php
                            if ($tiket_data['status'] === 'Selesai') echo 'bg-success-subtle border border-success border-opacity-25';
                            elseif ($tiket_data['status'] === 'Menunggu Sparepart') echo 'bg-warning-subtle border border-warning border-opacity-25';
                            else echo 'bg-primary-subtle border border-primary border-opacity-25';
                        ?>">
                            <div class="d-flex align-items-center gap-3">
                                <div class="fs-2">
                                    <?php if ($tiket_data['status'] === 'Selesai'): ?>
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    <?php elseif ($tiket_data['status'] === 'Menunggu Sparepart'): ?>
                                        <i class="bi bi-clock-history text-warning"></i>
                                    <?php else: ?>
                                        <i class="bi bi-gear-wide-connected text-primary"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <span class="small text-muted d-block text-uppercase fw-bold">Status Real-Time Saat Ini:</span>
                                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($tiket_data['status']); ?></h5>
                                    <?php if ($tiket_data['status'] === 'Selesai'): ?>
                                        <small class="text-success fw-semibold">Perangkat telah selesai dikerjakan dan siap diambil di workshop atau serah terima on-site.</small>
                                    <?php elseif ($tiket_data['status'] === 'Menunggu Sparepart'): ?>
                                        <small class="text-secondary">Teknisi sedang menunggu kedatangan komponen sparepart baru untuk dipasang pada perangkat Anda.</small>
                                    <?php else: ?>
                                        <small class="text-secondary">Teknisi kami sedang aktif memproses tahapan perbaikan perangkat Anda.</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Rincian Keluhan & Tindakan Teknisi -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100 bg-light">
                                    <label class="small text-muted fw-bold d-block mb-1"><i class="bi bi-exclamation-circle text-danger me-1"></i> Keluhan Awal Pelanggan:</label>
                                    <p class="mb-0 text-dark small"><?= nl2br(htmlspecialchars($tiket_data['keluhan'])); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100 bg-light">
                                    <label class="small text-muted fw-bold d-block mb-1"><i class="bi bi-tools text-primary me-1"></i> Tindakan / Solusi Teknisi:</label>
                                    <?php if (!empty($tiket_data['tindakan'])): ?>
                                        <p class="mb-0 text-dark small"><?= nl2br(htmlspecialchars($tiket_data['tindakan'])); ?></p>
                                    <?php else: ?>
                                        <p class="mb-0 text-muted small fst-italic">Masih dalam tahapan analisa awal oleh teknisi.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Kolom Kanan: Kartu Teknisi & Biaya -->
                <div class="col-lg-4">

                    <!-- KARTU TEKNISI PENANGGUNG JAWAB (SESUAI PERMINTAAN USER) -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 tech-card">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge bg-primary px-3 py-2 rounded-pill">TEKNISI PENANGGUNG JAWAB</span>
                            <i class="bi bi-shield-check text-primary fs-5"></i>
                        </div>

                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="tech-avatar">
                                <i class="bi bi-person-gear"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">
                                    <?= htmlspecialchars($tiket_data['teknisi_nama'] ?? 'Belum Ditentukan'); ?>
                                </h5>
                                <small class="text-primary fw-semibold">
                                    <?php if (!empty($tiket_data['teknisi_nama']) && $tiket_data['teknisi_nama'] !== 'Belum Ditentukan'): ?>
                                        <i class="bi bi-patch-check-fill me-1"></i> Teknisi Aktif Menangani
                                    <?php else: ?>
                                        <i class="bi bi-hourglass-split me-1"></i> Segera Ditugaskan
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>

                        <p class="small text-secondary mb-3">
                            Untuk pertanyaan atau konsultasi langsung mengenai pengerjaan unit ini, Anda dapat menghubungi teknisi kami via WhatsApp:
                        </p>

                        <!-- Tombol Hubungi Teknisi via WhatsApp -->
                        <a href="https://wa.me/6281360306866?text=Halo%20Teknisi%20<?= urlencode($tiket_data['teknisi_nama']); ?>,%20saya%20pelanggan%20atas%20nama%20<?= urlencode($tiket_data['nama_pelanggan']); ?>%20ingin%20menanyakan%20progres%20servis%20tiket%20<?= urlencode($tiket_data['kode_tiket']); ?>" target="_blank" class="btn btn-success rounded-pill w-100 fw-bold">
                            <i class="bi bi-whatsapp me-2"></i> Chat Teknisi Penanggung Jawab
                        </a>
                    </div>

                    <!-- Kartu Informasi Perangkat & Biaya -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h6 class="fw-bold text-dark pb-2 border-bottom mb-3">
                            <i class="bi bi-laptop text-primary me-2"></i>Informasi Perangkat & Biaya
                        </h6>

                        <table class="table table-sm table-borderless small mb-4">
                            <tr>
                                <td class="text-muted" width="40%">Pelanggan</td>
                                <td class="fw-bold text-dark">: <?= htmlspecialchars($tiket_data['nama_pelanggan']); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Kategori</td>
                                <td class="fw-bold text-dark">: <?= htmlspecialchars($tiket_data['jenis_layanan']); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Perangkat</td>
                                <td class="fw-bold text-dark">: <?= htmlspecialchars($tiket_data['tipe_perangkat']); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">No. Kontak</td>
                                <td class="fw-bold text-dark">: <?= htmlspecialchars($tiket_data['no_hp']); ?></td>
                            </tr>
                        </table>

                        <div class="p-3 rounded-3 bg-light border text-center mb-4">
                            <span class="text-muted small d-block">Estimasi / Total Biaya:</span>
                            <h3 class="fw-bold text-primary mb-0">Rp <?= number_format($tiket_data['biaya'], 0, ',', '.'); ?></h3>
                            <?php if (!empty($tiket_data['catatan'])): ?>
                                <small class="text-success d-block mt-1"><i class="bi bi-check2 me-1"></i><?= htmlspecialchars($tiket_data['catatan']); ?></small>
                            <?php endif; ?>
                        </div>

                        <!-- Tombol Cetak Tanda Terima Pelanggan -->
                        <a href="cetak_laporan.php?id=<?= $tiket_data['id']; ?>" target="_blank" class="btn btn-outline-dark rounded-pill w-100">
                            <i class="bi bi-printer me-2"></i> Cetak Bukti Nota / Tanda Terima
                        </a>
                    </div>

                </div>
            </div>

        <?php elseif (empty($search)): ?>
            <!-- Ilustrasi / Panduan Jika Belum Mencari -->
            <div class="row justify-content-center py-4">
                <div class="col-lg-8 text-center">
                    <div class="mb-4 text-primary opacity-75">
                        <i class="bi bi-clipboard2-check" style="font-size: 5rem;"></i>
                    </div>
                    <h4 class="fw-bold">Lacak Perangkat Anda Kapan Saja</h4>
                    <p class="text-muted">
                        Cukup masukkan Nomor Tiket yang tercantum pada nota tanda terima Anda (misal: <code>SRV-202610-001</code>) atau nomor WhatsApp yang didaftarkan saat menyerahkan unit.
                    </p>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5 border-top border-secondary border-opacity-25">
        <div class="container text-center small text-light text-opacity-50">
            &copy; <?= date('Y'); ?> Tech Solution - Sistem Pelacakan Servis Komputer & Jaringan.
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
