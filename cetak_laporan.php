<?php
require_once 'config.php';
require_once 'auth.php';

// Pastikan hanya petugas login yang bisa cetak dokumen resmi
checkAuth();

$single_id = intval($_GET['id'] ?? 0);

if ($single_id > 0) {
    // Mode 1: Cetak Tanda Terima / Nota Servis Tunggal
    $stmt = $conn->prepare("SELECT * FROM `laporan_servis` WHERE `id` = ? LIMIT 1");
    $stmt->bind_param("i", $single_id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$data) {
        die("Data tiket servis tidak ditemukan.");
    }
    $is_single = true;
} else {
    // Mode 2: Cetak Rekap Laporan Keseluruhan (Tabel)
    $filter_layanan = $_GET['layanan'] ?? '';
    $filter_status = $_GET['status'] ?? '';
    $search_query = trim($_GET['q'] ?? '');

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
    $report_list = $stmt->get_result();
    $is_single = false;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_single ? "Nota Servis " . htmlspecialchars($data['kode_tiket']) : "Rekap Laporan Servis IT"; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        .print-container {
            max-width: 820px;
            margin: 20px auto;
            background: #fff;
            padding: 35px 45px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }
        .kop-header {
            border-bottom: 3px double #0f172a;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .brand-logo-print {
            font-size: 2.2rem;
            color: #0284c7;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .print-container {
                max-width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- Tombol Navigasi & Print (Hanya tampil di layar) -->
    <div class="container text-center my-3 no-print">
        <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 me-2">
            <i class="bi bi-printer-fill me-1"></i> Cetak Dokumen (PDF/Print)
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-x-circle me-1"></i> Tutup
        </button>
    </div>

    <div class="print-container">
        <!-- Header Dokumen / Kop -->
        <div class="kop-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <?php $logo_print = getAppLogo('dark'); ?>
                <?php if ($logo_print['found']): ?>
                    <?php if ($logo_print['is_white_on_light']): ?>
                        <div class="p-2 rounded-3 d-inline-flex align-items-center justify-content-center" style="background-color: #0f172a !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                            <img src="<?= $logo_print['path']; ?>" alt="Logo" style="height: 48px; width: auto; object-fit: contain;">
                        </div>
                    <?php else: ?>
                        <img src="<?= $logo_print['path']; ?>" alt="Logo" style="height: 55px; width: auto; object-fit: contain;">
                    <?php endif; ?>
                <?php else: ?>
                    <i class="bi bi-hdd-network-fill brand-logo-print"></i>
                <?php endif; ?>
                <div>
                    <h3 class="fw-bold mb-0 text-dark">TECH SOLUTION</h3>
                    <p class="text-muted small mb-0">Layanan Servis Komputer, Laptop & Instalasi Jaringan Profesional</p>
                    <small class="text-muted">Jl. Teknologi No. 45 | Telp: (021) 8899-7766 | WA: 0812-3456-7890</small>
                </div>
            </div>
            <div class="text-end">
                <span class="badge bg-secondary-subtle text-secondary fs-6 px-3 py-2 font-monospace">
                    <?= $is_single ? "NOTA TANDA TERIMA" : "REKAP LAPORAN IT"; ?>
                </span>
            </div>
        </div>

        <?php if ($is_single): ?>
            <!-- ================= TAMPILAN NOTA TIKET TUNGGAL ================= -->
            <div class="row mb-4">
                <div class="col-6">
                    <table class="table table-sm table-borderless small mb-0">
                        <tr>
                            <td class="text-muted" width="130">Nomor Tiket</td>
                            <td class="fw-bold font-monospace text-primary fs-6">: <?= htmlspecialchars($data['kode_tiket']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Kategori</td>
                            <td class="fw-bold">: <?= htmlspecialchars($data['jenis_layanan']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal Masuk</td>
                            <td>: <?= date('d F Y, H:i', strtotime($data['tanggal_masuk'])); ?> WIB</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal Selesai</td>
                            <td>: <?= !empty($data['tanggal_selesai']) ? date('d F Y, H:i', strtotime($data['tanggal_selesai'])) . " WIB" : "Dalam Pengerjaan"; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-6">
                    <table class="table table-sm table-borderless small mb-0">
                        <tr>
                            <td class="text-muted" width="130">Nama Pelanggan</td>
                            <td class="fw-bold">: <?= htmlspecialchars($data['nama_pelanggan']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">No. Telepon / WA</td>
                            <td>: <?= htmlspecialchars($data['no_hp']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Alamat</td>
                            <td>: <?= htmlspecialchars($data['alamat'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status Servis</td>
                            <td>: <strong class="badge bg-dark"><?= htmlspecialchars($data['status']); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Detail Perangkat & Pengerjaan -->
            <table class="table table-bordered mb-4">
                <thead class="table-light small">
                    <tr>
                        <th width="40%">PERANGKAT & KELUHAN</th>
                        <th width="40%">TINDAKAN PERBAIKAN / TEKNISI</th>
                        <th width="20%" class="text-end">BIAYA (RP)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($data['tipe_perangkat']); ?></strong>
                            <p class="text-muted small mt-1 mb-0">
                                <strong>Keluhan:</strong><br><?= nl2br(htmlspecialchars($data['keluhan'])); ?>
                            </p>
                        </td>
                        <td>
                            <?php if (!empty($data['tindakan'])): ?>
                                <p class="small mb-1"><?= nl2br(htmlspecialchars($data['tindakan'])); ?></p>
                            <?php else: ?>
                                <p class="text-muted small fst-italic mb-1">Dalam proses pemeriksaan teknisi</p>
                            <?php endif; ?>
                            <small class="text-secondary d-block mt-2">
                                <strong>Teknisi:</strong> <?= htmlspecialchars($data['teknisi_nama'] ?? '-'); ?>
                            </small>
                            <?php if (!empty($data['catatan'])): ?>
                                <small class="text-info d-block">
                                    <strong>Catatan:</strong> <?= htmlspecialchars($data['catatan']); ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-bold align-middle fs-6">
                            Rp <?= number_format($data['biaya'], 0, ',', '.'); ?>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <th colspan="2" class="text-end">TOTAL TAGIHAN:</th>
                        <th class="text-end text-primary fs-5">Rp <?= number_format($data['biaya'], 0, ',', '.'); ?></th>
                    </tr>
                </tfoot>
            </table>

            <!-- Ketentuan & Tanda Tangan -->
            <div class="row align-items-end mt-5 pt-3">
                <div class="col-7">
                    <div class="p-3 border rounded small text-muted bg-light">
                        <strong>Syarat & Ketentuan Servis:</strong>
                        <ol class="ps-3 mb-0 mt-1" style="font-size: 0.75rem;">
                            <li>Barang yang telah selesai diservis dan tidak diambil dalam 30 hari di luar tanggung jawab kami.</li>
                            <li>Garansi berlaku untuk kerusakan yang sama sesuai catatan teknisi.</li>
                            <li>Tanda terima ini wajib dibawa saat pengambilan unit servis.</li>
                        </ol>
                    </div>
                </div>
                <div class="col-5">
                    <div class="row text-center small">
                        <div class="col-6">
                            <p class="mb-4">Pelanggan,</p>
                            <br>
                            <strong>( <?= htmlspecialchars($data['nama_pelanggan']); ?> )</strong>
                        </div>
                        <div class="col-6">
                            <p class="mb-4">Teknisi IT,</p>
                            <br>
                            <strong>( <?= htmlspecialchars($data['teknisi_nama'] ?? 'Teknisi IT'); ?> )</strong>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- ================= TAMPILAN REKAP LAPORAN BANYAK DATA ================= -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-0">Laporan Rekapitulasi Servis Komputer & Jaringan</h5>
                    <small class="text-muted">Dicetak pada: <?= date('d F Y, H:i'); ?> WIB oleh <?= htmlspecialchars($_SESSION['nama'] ?? 'Petugas IT'); ?></small>
                </div>
                <div>
                    <span class="badge bg-primary px-3 py-2">Total Data: <?= $report_list->num_rows; ?></span>
                </div>
            </div>

            <table class="table table-bordered table-sm small">
                <thead class="table-light text-center">
                    <tr>
                        <th>No</th>
                        <th>Kode Tiket</th>
                        <th>Tgl Masuk</th>
                        <th>Pelanggan</th>
                        <th>Kategori</th>
                        <th>Perangkat & Keluhan</th>
                        <th>Teknisi / Tindakan</th>
                        <th>Status</th>
                        <th>Biaya (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $total_rekap_biaya = 0;
                    while ($r = $report_list->fetch_assoc()): 
                        $total_rekap_biaya += $r['biaya'];
                    ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td class="font-monospace fw-bold"><?= htmlspecialchars($r['kode_tiket']); ?></td>
                            <td><?= date('d/m/Y', strtotime($r['tanggal_masuk'])); ?></td>
                            <td>
                                <strong><?= htmlspecialchars($r['nama_pelanggan']); ?></strong><br>
                                <span class="text-muted"><?= htmlspecialchars($r['no_hp']); ?></span>
                            </td>
                            <td><?= htmlspecialchars($r['jenis_layanan']); ?></td>
                            <td>
                                <strong><?= htmlspecialchars($r['tipe_perangkat']); ?></strong><br>
                                <span class="text-muted"><?= htmlspecialchars($r['keluhan']); ?></span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($r['teknisi_nama'] ?? '-'); ?></strong><br>
                                <span class="text-muted"><?= htmlspecialchars($r['tindakan'] ?? '-'); ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary"><?= htmlspecialchars($r['status']); ?></span>
                            </td>
                            <td class="text-end fw-bold">
                                Rp <?= number_format($r['biaya'], 0, ',', '.'); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="8" class="text-end">TOTAL KESELURUHAN BIAYA:</td>
                        <td class="text-end text-primary">Rp <?= number_format($total_rekap_biaya, 0, ',', '.'); ?></td>
                    </tr>
                </tfoot>
            </table>

            <div class="row justify-content-end mt-5 pt-3">
                <div class="col-4 text-center small">
                    <p class="mb-4">Penanggung Jawab IT / Kepala Unit,</p>
                    <br><br>
                    <strong>( <?= htmlspecialchars($_SESSION['nama'] ?? 'Administrator IT'); ?> )</strong>
                </div>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
