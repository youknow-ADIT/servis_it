<?php
require_once 'config.php';
require_once 'auth.php';

// Pastikan user sudah login melalui session
checkAuth();

// Penanganan Filter & Pencarian
$filter_layanan = $_GET['layanan'] ?? '';
$filter_status = $_GET['status'] ?? '';
$search_query = trim($_GET['q'] ?? '');

// Membangun query SQL dinamis
$sql = "SELECT * FROM `laporan_servis` WHERE 1=1";
$params = [];
$types = "";

if (!empty($filter_layanan)) {
    $sql .= " AND `jenis_layanan` = ?";
    $params[] = $filter_layanan;
    $types .= "s";
}

if (!empty($filter_status)) {
    $sql .= " AND `status` = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if (!empty($search_query)) {
    $sql .= " AND (`kode_tiket` LIKE ? OR `nama_pelanggan` LIKE ? OR `no_hp` LIKE ? OR `tipe_perangkat` LIKE ? OR `keluhan` LIKE ?)";
    $like_term = "%$search_query%";
    $params[] = $like_term;
    $params[] = $like_term;
    $params[] = $like_term;
    $params[] = $like_term;
    $params[] = $like_term;
    $types .= "sssss";
}

$sql .= " ORDER BY `tanggal_masuk` DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$servis_list = $stmt->get_result();

// Menghitung statistik untuk Dashboard KPI
$stat_total = $conn->query("SELECT COUNT(*) as total FROM `laporan_servis`")->fetch_assoc()['total'] ?? 0;
$stat_komputer = $conn->query("SELECT COUNT(*) as total FROM `laporan_servis` WHERE `jenis_layanan` = 'Komputer'")->fetch_assoc()['total'] ?? 0;
$stat_jaringan = $conn->query("SELECT COUNT(*) as total FROM `laporan_servis` WHERE `jenis_layanan` = 'Jaringan'")->fetch_assoc()['total'] ?? 0;
$stat_selesai = $conn->query("SELECT COUNT(*) as total FROM `laporan_servis` WHERE `status` = 'Selesai'")->fetch_assoc()['total'] ?? 0;
$stat_pendapatan = $conn->query("SELECT SUM(biaya) as total FROM `laporan_servis` WHERE `status` = 'Selesai'")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Laporan Servis IT - Dashboard Petugas</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

    <!-- Top Navbar Khusus Area Petugas IT -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-2">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="laporan.php">
                <?php $logo_admin = getAppLogo('light'); ?>
                <?php if ($logo_admin['found']): ?>
                    <img src="<?= $logo_admin['path']; ?>" alt="Logo" style="height: 35px; width: auto; object-fit: contain;">
                <?php else: ?>
                    <span class="brand-icon"><i class="bi bi-hdd-network-fill"></i></span>
                <?php endif; ?>
                <span>Tech <span class="text-primary-accent">Laporan Servis</span></span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="laporan.php">
                            <i class="bi bi-clipboard2-data-fill me-1 text-primary"></i> Data Laporan Servis
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php" target="_blank">
                            <i class="bi bi-globe me-1"></i> Lihat Website Utama
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <div class="text-white text-end d-none d-md-block">
                        <div class="fw-bold small"><?= htmlspecialchars($_SESSION['nama'] ?? 'User'); ?></div>
                        <span class="badge bg-primary text-uppercase" style="font-size: 0.7rem;"><?= htmlspecialchars($_SESSION['role'] ?? 'Teknisi'); ?></span>
                    </div>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1 rounded-pill px-3">
                        <i class="bi bi-box-arrow-right"></i> Logout Session
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Konten Utama Dashboard Laporan Servis -->
    <main class="container-fluid px-lg-4 py-4">

        <!-- Notifikasi Pesan Sukses / Error -->
        <?php if (isset($_GET['status_msg'])): ?>
            <?php if ($_GET['status_msg'] === 'saved'): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> Data servis baru berhasil ditambahkan!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['status_msg'] === 'updated'): ?>
                <div class="alert alert-info alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i> Data & status servis berhasil diperbarui!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['status_msg'] === 'deleted'): ?>
                <div class="alert alert-warning alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                    <i class="bi bi-trash-fill me-2"></i> Data servis telah dihapus dari sistem!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Header Judul & Tombol Aksi -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">
                    <i class="bi bi-clipboard2-pulse text-primary me-2"></i>Menu Laporan Servis IT
                </h3>
                <p class="text-muted small mb-0">Manajemen perbaikan komputer, instalasi jaringan, dan monitoring status teknisi.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2 rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahServis">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Servis Masuk
                </button>
                <a href="cetak_laporan.php?layanan=<?= urlencode($filter_layanan); ?>&status=<?= urlencode($filter_status); ?>&q=<?= urlencode($search_query); ?>" target="_blank" class="btn btn-outline-secondary d-flex align-items-center gap-2 rounded-pill px-3">
                    <i class="bi bi-printer-fill"></i> Cetak Rekap Laporan (A4)
                </a>
            </div>
        </div>

        <!-- KPI Statistic Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold">Total Servis Masuk</span>
                            <h3 class="fw-bold mb-0 text-dark"><?= $stat_total; ?></h3>
                        </div>
                        <div class="stat-icon bg-primary-subtle text-primary">
                            <i class="bi bi-journal-check fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold">Servis Komputer</span>
                            <h3 class="fw-bold mb-0 text-primary"><?= $stat_komputer; ?></h3>
                        </div>
                        <div class="stat-icon bg-info-subtle text-info">
                            <i class="bi bi-pc-display fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold">Proyek Jaringan</span>
                            <h3 class="fw-bold mb-0 text-success"><?= $stat_jaringan; ?></h3>
                        </div>
                        <div class="stat-icon bg-success-subtle text-success">
                            <i class="bi bi-diagram-3-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold">Servis Selesai</span>
                            <h3 class="fw-bold mb-0 text-success"><?= $stat_selesai; ?></h3>
                            <small class="text-muted" style="font-size: 0.75rem;">Total Rp <?= number_format($stat_pendapatan, 0, ',', '.'); ?></small>
                        </div>
                        <div class="stat-icon bg-warning-subtle text-warning">
                            <i class="bi bi-check2-all fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="card border-0 rounded-4 shadow-sm mb-4">
            <div class="card-body p-3">
                <form method="GET" action="laporan.php" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" value="<?= htmlspecialchars($search_query); ?>" class="form-control border-start-0 ps-0" placeholder="Cari tiket, pelanggan, keluhan...">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <select name="layanan" class="form-select">
                            <option value="">Semua Kategori (Komputer & Jaringan)</option>
                            <option value="Komputer" <?= $filter_layanan === 'Komputer' ? 'selected' : ''; ?>>Hanya Servis Komputer</option>
                            <option value="Jaringan" <?= $filter_layanan === 'Jaringan' ? 'selected' : ''; ?>>Hanya Instalasi Jaringan</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">Semua Status Servis</option>
                            <option value="Diserahkan" <?= ($filter_status === 'Diserahkan' || $filter_status === 'Antrian') ? 'selected' : ''; ?>>1. Diserahkan / Antrian</option>
                            <option value="Pengecekan & Diagnosis" <?= $filter_status === 'Pengecekan & Diagnosis' ? 'selected' : ''; ?>>2. Pengecekan & Diagnosis</option>
                            <option value="Proses Pengerjaan" <?= ($filter_status === 'Proses Pengerjaan' || $filter_status === 'Sedang Dikerjakan') ? 'selected' : ''; ?>>3. Proses Pengerjaan</option>
                            <option value="Menunggu Sparepart" <?= $filter_status === 'Menunggu Sparepart' ? 'selected' : ''; ?>>3.1 Menunggu Sparepart</option>
                            <option value="Pengujian (QC)" <?= $filter_status === 'Pengujian (QC)' ? 'selected' : ''; ?>>4. Pengujian (QC)</option>
                            <option value="Selesai" <?= $filter_status === 'Selesai' ? 'selected' : ''; ?>>5. Selesai (Siap Diambil)</option>
                            <option value="Dibatalkan" <?= $filter_status === 'Dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-3 w-100">
                            <i class="bi bi-funnel-fill me-1"></i> Filter Data
                        </button>
                        <a href="laporan.php" class="btn btn-outline-secondary rounded-3" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Laporan Servis IT -->
        <div class="card border-0 rounded-4 shadow-sm overflow-hidden">
            <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-table text-primary me-2"></i>Daftar Laporan Servis IT
                </h5>
                <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill">
                    <?= $servis_list->num_rows; ?> data ditemukan
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th class="ps-4">KODE TIKET</th>
                            <th>TGL MASUK</th>
                            <th>PELANGGAN & HP</th>
                            <th>KATEGORI</th>
                            <th>PERANGKAT & KELUHAN</th>
                            <th>TINDAKAN / TEKNISI</th>
                            <th>BIAYA (RP)</th>
                            <th>STATUS</th>
                            <th class="text-center pe-4">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($servis_list->num_rows > 0): ?>
                            <?php while ($row = $servis_list->fetch_assoc()): ?>
                                <tr>
                                    <!-- Kode Tiket -->
                                    <td class="ps-4 fw-bold">
                                        <span class="badge bg-dark-subtle text-dark font-monospace px-2 py-1">
                                            <?= htmlspecialchars($row['kode_tiket']); ?>
                                        </span>
                                    </td>

                                    <!-- Tanggal Masuk -->
                                    <td class="small text-muted">
                                        <?= date('d/m/Y H:i', strtotime($row['tanggal_masuk'])); ?>
                                    </td>

                                    <!-- Pelanggan & Kontak -->
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_pelanggan']); ?></div>
                                        <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $row['no_hp'])); ?>" target="_blank" class="badge bg-success-subtle text-success text-decoration-none font-monospace">
                                            <i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($row['no_hp']); ?>
                                        </a>
                                    </td>

                                    <!-- Kategori Layanan -->
                                    <td>
                                        <?php if ($row['jenis_layanan'] === 'Komputer'): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 px-2 py-1">
                                                <i class="bi bi-pc-display me-1"></i> Komputer
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info border-opacity-25 px-2 py-1">
                                                <i class="bi bi-router me-1"></i> Jaringan
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Tipe Perangkat & Keluhan -->
                                    <td style="max-width: 260px;">
                                        <div class="fw-semibold text-dark text-truncate" title="<?= htmlspecialchars($row['tipe_perangkat']); ?>">
                                            <?= htmlspecialchars($row['tipe_perangkat']); ?>
                                        </div>
                                        <small class="text-muted d-block text-truncate" title="<?= htmlspecialchars($row['keluhan']); ?>">
                                            <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($row['keluhan']); ?>
                                        </small>
                                    </td>

                                    <!-- Tindakan & Teknisi -->
                                    <td style="max-width: 240px;">
                                        <?php if (!empty($row['tindakan'])): ?>
                                            <div class="small text-dark text-truncate" title="<?= htmlspecialchars($row['tindakan']); ?>">
                                                <i class="bi bi-tools text-primary me-1"></i><?= htmlspecialchars($row['tindakan']); ?>
                                            </div>
                                        <?php else: ?>
                                            <small class="text-muted fst-italic">Belum ada tindakan</small>
                                        <?php endif; ?>
                                        <small class="text-secondary d-block">
                                            <i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($row['teknisi_nama'] ?? 'Belum Ditentukan'); ?>
                                        </small>
                                    </td>

                                    <!-- Biaya -->
                                    <td class="fw-bold text-dark">
                                        Rp <?= number_format($row['biaya'], 0, ',', '.'); ?>
                                    </td>

                                    <!-- Status Badges -->
                                    <td>
                                        <?php
                                            $st = $row['status'];
                                            $badge_class = 'bg-secondary';
                                            if ($st === 'Diserahkan' || $st === 'Antrian') $badge_class = 'bg-warning text-dark';
                                            elseif ($st === 'Pengecekan & Diagnosis') $badge_class = 'bg-info text-dark';
                                            elseif ($st === 'Proses Pengerjaan' || $st === 'Sedang Dikerjakan') $badge_class = 'bg-primary text-white';
                                            elseif ($st === 'Menunggu Sparepart') $badge_class = 'bg-dark text-warning border border-warning';
                                            elseif ($st === 'Pengujian (QC)') $badge_class = 'bg-secondary text-white';
                                            elseif ($st === 'Selesai') $badge_class = 'bg-success text-white';
                                            elseif ($st === 'Dibatalkan') $badge_class = 'bg-danger text-white';
                                        ?>
                                        <span class="badge <?= $badge_class; ?> px-2 py-1 rounded-pill">
                                            <?= htmlspecialchars($st); ?>
                                        </span>
                                    </td>

                                    <!-- Tombol Aksi -->
                                    <td class="text-center pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <!-- Tombol Lihat Tampilan Tracking Pelanggan -->
                                            <a href="tracking.php?q=<?= urlencode($row['kode_tiket']); ?>" target="_blank" class="btn btn-outline-info" title="Lihat Tampilan Tracking Pelanggan">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <!-- Tombol Edit / Update Status -->
                                            <button type="button" class="btn btn-outline-primary" 
                                                    title="Update Status Servis"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalUpdateServis"
                                                    data-id="<?= $row['id']; ?>"
                                                    data-tiket="<?= htmlspecialchars($row['kode_tiket']); ?>"
                                                    data-pelanggan="<?= htmlspecialchars($row['nama_pelanggan']); ?>"
                                                    data-hp="<?= htmlspecialchars($row['no_hp']); ?>"
                                                    data-layanan="<?= htmlspecialchars($row['jenis_layanan']); ?>"
                                                    data-perangkat="<?= htmlspecialchars($row['tipe_perangkat']); ?>"
                                                    data-keluhan="<?= htmlspecialchars($row['keluhan']); ?>"
                                                    data-tindakan="<?= htmlspecialchars($row['tindakan'] ?? ''); ?>"
                                                    data-biaya="<?= $row['biaya']; ?>"
                                                    data-status="<?= $row['status']; ?>"
                                                    data-teknisi="<?= htmlspecialchars($row['teknisi_nama'] ?? ''); ?>"
                                                    data-catatan="<?= htmlspecialchars($row['catatan'] ?? ''); ?>">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <!-- Tombol Cetak Tanda Terima / Nota -->
                                            <a href="cetak_laporan.php?id=<?= $row['id']; ?>" target="_blank" class="btn btn-outline-dark" title="Cetak Nota / Tanda Terima">
                                                <i class="bi bi-printer"></i>
                                            </a>

                                            <!-- Tombol Hapus (Modal Aman) -->
                                            <button type="button" class="btn btn-outline-danger" 
                                                    title="Hapus Data Servis"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalHapusServis"
                                                    data-id="<?= $row['id']; ?>"
                                                    data-tiket="<?= htmlspecialchars($row['kode_tiket']); ?>"
                                                    data-pelanggan="<?= htmlspecialchars($row['nama_pelanggan']); ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    Tidak ada data laporan servis yang sesuai dengan filter pencarian.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- ================= MODAL TAMBAH SERVIS ================= -->
    <div class="modal fade" id="modalTambahServis" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle text-primary me-2"></i>Tambah Servis Masuk Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="simpan_servis.php">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nama Pelanggan / Perusahaan *</label>
                                <input type="text" name="nama_pelanggan" class="form-control" required placeholder="Contoh: PT. Sumber Daya / Bpk. Hadi">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nomor HP / WhatsApp *</label>
                                <input type="tel" name="no_hp" class="form-control" required placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Kategori Layanan *</label>
                                <select name="jenis_layanan" class="form-select" required>
                                    <option value="Komputer">Servis Komputer / Laptop</option>
                                    <option value="Jaringan">Instalasi / Setting Jaringan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Tipe & Merk Perangkat *</label>
                                <input type="text" name="tipe_perangkat" class="form-control" required placeholder="Contoh: PC Server / MikroTik CCR / Laptop Dell">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Alamat / Lokasi Pengerjaan</label>
                                <input type="text" name="alamat" class="form-control" placeholder="Jl. Raya No. 123 (Kosongkan bila pengerjaan di workshop)">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Keluhan / Gejala Kerusakan *</label>
                                <textarea name="keluhan" class="form-control" rows="3" required placeholder="Jelaskan kendala perangkat secara rinci..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nama Teknisi Penanggung Jawab</label>
                                <input type="text" name="teknisi_nama" class="form-control" value="<?= htmlspecialchars($_SESSION['nama'] ?? 'Teknisi IT'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Estimasi Biaya Awal (Rp)</label>
                                <input type="number" name="biaya" class="form-control" value="0" min="0" step="1000">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-save me-1"></i> Simpan Data Servis
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= MODAL UPDATE SERVIS & STATUS ================= -->
    <div class="modal fade" id="modalUpdateServis" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Update Progres & Status Servis IT
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="update_servis.php">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body p-4">
                        <div class="alert alert-secondary d-flex align-items-center justify-content-between p-3 rounded-3 mb-3">
                            <div>
                                <small class="text-muted d-block">Nomor Tiket Servis:</small>
                                <strong id="edit_tiket_label" class="fs-6 font-monospace text-dark"></strong>
                            </div>
                            <div>
                                <small class="text-muted d-block">Pelanggan:</small>
                                <strong id="edit_pelanggan_label" class="text-dark"></strong>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Status Pengerjaan (Tahap Servis) *</label>
                                <select name="status" id="edit_status" class="form-select" required>
                                    <option value="Diserahkan">1. Diserahkan / Antrian Masuk</option>
                                    <option value="Pengecekan & Diagnosis">2. Pengecekan & Diagnosis</option>
                                    <option value="Proses Pengerjaan">3. Proses Pengerjaan</option>
                                    <option value="Menunggu Sparepart">3.1 Menunggu Sparepart</option>
                                    <option value="Pengujian (QC)">4. Pengujian & Quality Control (QC)</option>
                                    <option value="Selesai">5. Selesai (Siap Diambil)</option>
                                    <option value="Dibatalkan">Dibatalkan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Teknisi Penanggung Jawab *</label>
                                <input type="text" name="teknisi_nama" id="edit_teknisi" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Tindakan Perbaikan / Konfigurasi</label>
                                <textarea name="tindakan" id="edit_tindakan" class="form-control" rows="3" placeholder="Contoh: Ganti IC Power, setting VLAN ID 10 & 20, crimping ulang kabel Cat6..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Total Biaya Jasa & Sparepart (Rp) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="biaya" id="edit_biaya" class="form-control" required min="0" step="1000">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Catatan Tambahan / Garansi</label>
                                <input type="text" name="catatan" id="edit_catatan" class="form-control" placeholder="Contoh: Garansi servis 30 hari">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-check2-circle me-1"></i> Update Data Servis
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= MODAL KONFIRMASI HAPUS SERVIS ================= -->
    <div class="modal fade" id="modalHapusServis" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus Servis
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="hapus_servis.php">
                    <input type="hidden" name="id" id="hapus_id">
                    <div class="modal-body p-4">
                        <p class="mb-2">Apakah Anda yakin ingin menghapus data servis ini secara permanen?</p>
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="small text-muted mb-1">Nomor Tiket: <strong id="hapus_tiket_label" class="font-monospace text-dark fs-6"></strong></div>
                            <div class="small text-muted">Pelanggan: <strong id="hapus_pelanggan_label" class="text-dark"></strong></div>
                        </div>
                        <small class="text-danger mt-2 d-block"><i class="bi bi-info-circle me-1"></i>Data yang dihapus tidak dapat dipulihkan kembali.</small>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4">
                            <i class="bi bi-trash-fill me-1"></i> Ya, Hapus Permanen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script Pengisian Data Otomatis ke Modal Update & Hapus -->
    <script>
        const modalUpdate = document.getElementById('modalUpdateServis');
        if (modalUpdate) {
            modalUpdate.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                document.getElementById('edit_id').value = btn.getAttribute('data-id');
                document.getElementById('edit_tiket_label').textContent = btn.getAttribute('data-tiket');
                document.getElementById('edit_pelanggan_label').textContent = btn.getAttribute('data-pelanggan');
                document.getElementById('edit_status').value = btn.getAttribute('data-status');
                document.getElementById('edit_teknisi').value = btn.getAttribute('data-teknisi');
                document.getElementById('edit_tindakan').value = btn.getAttribute('data-tindakan');
                document.getElementById('edit_biaya').value = btn.getAttribute('data-biaya');
                document.getElementById('edit_catatan').value = btn.getAttribute('data-catatan');
            });
        }

        const modalHapus = document.getElementById('modalHapusServis');
        if (modalHapus) {
            modalHapus.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                document.getElementById('hapus_id').value = btn.getAttribute('data-id');
                document.getElementById('hapus_tiket_label').textContent = btn.getAttribute('data-tiket');
                document.getElementById('hapus_pelanggan_label').textContent = btn.getAttribute('data-pelanggan');
            });
        }
    </script>
</body>
</html>
