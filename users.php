<?php
require_once 'config.php';
require_once 'auth.php';

// Pastikan user sudah login
checkAuth();

// Pastikan hanya role admin yang boleh mengelola akun
if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: laporan.php?status_msg=unauthorized");
    exit();
}

$alert_type = '';
$alert_msg = '';

// Proses form POST (Tambah / Edit / Hapus Akun)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['admin', 'teknisi']) ? $_POST['role'] : 'teknisi';

        if (empty($username) || empty($password) || empty($nama_lengkap)) {
            $alert_type = 'danger';
            $alert_msg = 'Semua field wajib diisi!';
        } else {
            // Cek apakah username sudah terdaftar
            $check = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $check->bind_param("s", $username);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $alert_type = 'warning';
                $alert_msg = "Username <strong>" . htmlspecialchars($username) . "</strong> sudah digunakan! Silakan gunakan username lain.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $hash, $nama_lengkap, $role);
                if ($stmt->execute()) {
                    $alert_type = 'success';
                    $alert_msg = "Akun baru <strong>" . htmlspecialchars($username) . "</strong> berhasil ditambahkan!";
                } else {
                    $alert_type = 'danger';
                    $alert_msg = "Gagal menambahkan akun: " . htmlspecialchars($stmt->error);
                }
                $stmt->close();
            }
            $check->close();
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['admin', 'teknisi']) ? $_POST['role'] : 'teknisi';

        if ($id > 0 && !empty($username) && !empty($nama_lengkap)) {
            // Cek duplikasi username untuk ID lain
            $check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
            $check->bind_param("si", $username, $id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $alert_type = 'warning';
                $alert_msg = "Username <strong>" . htmlspecialchars($username) . "</strong> sudah digunakan oleh akun lain!";
            } else {
                if (!empty($password)) {
                    // Update dengan password baru
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, nama_lengkap = ?, role = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $username, $hash, $nama_lengkap, $role, $id);
                } else {
                    // Update tanpa mengubah password lama
                    $stmt = $conn->prepare("UPDATE users SET username = ?, nama_lengkap = ?, role = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $username, $nama_lengkap, $role, $id);
                }

                if ($stmt->execute()) {
                    // Jika mengedit akun sendiri, update data session
                    if ($id === intval($_SESSION['user_id'])) {
                        $_SESSION['username'] = $username;
                        $_SESSION['nama'] = $nama_lengkap;
                        $_SESSION['role'] = $role;
                    }
                    $alert_type = 'info';
                    $alert_msg = "Data akun <strong>" . htmlspecialchars($username) . "</strong> berhasil diperbarui!";
                } else {
                    $alert_type = 'danger';
                    $alert_msg = "Gagal memperbarui akun: " . htmlspecialchars($stmt->error);
                }
                $stmt->close();
            }
            $check->close();
        }
    } elseif ($action === 'hapus') {
        $id = intval($_POST['id'] ?? 0);
        if ($id === intval($_SESSION['user_id'])) {
            $alert_type = 'danger';
            $alert_msg = 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif login!';
        } elseif ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $alert_type = 'warning';
                $alert_msg = 'Akun petugas berhasil dihapus dari sistem.';
            } else {
                $alert_type = 'danger';
                $alert_msg = "Gagal menghapus akun: " . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        }
    }
}

// Ambil daftar users
$users_list = $conn->query("SELECT * FROM users ORDER BY role ASC, created_at DESC");
$total_users = $users_list ? $users_list->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Akun Petugas - Tech Solution</title>
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
                <span>Tech <span class="text-primary-accent">Panel Admin</span></span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="laporan.php">
                            <i class="bi bi-clipboard2-data-fill me-1 text-primary"></i> Data Laporan Servis
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php">
                            <i class="bi bi-people-fill me-1 text-info"></i> Kelola Akun Petugas
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
                        <span class="badge bg-danger text-uppercase" style="font-size: 0.7rem;"><?= htmlspecialchars($_SESSION['role'] ?? 'Admin'); ?></span>
                    </div>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1 rounded-pill px-3">
                        <i class="bi bi-box-arrow-right"></i> Logout Session
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Konten Utama Kelola User -->
    <main class="container-fluid px-lg-4 py-4">

        <!-- Alert Notifikasi -->
        <?php if (!empty($alert_msg)): ?>
            <div class="alert alert-<?= $alert_type; ?> alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                <?= $alert_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Header Judul & Tombol Tambah -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">
                    <i class="bi bi-people-fill text-primary me-2"></i>Kelola Akun Petugas IT
                </h3>
                <p class="text-muted small mb-0">Manajemen hak akses Administrator dan Teknisi penanggung jawab servis.</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2 rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                    <i class="bi bi-person-plus-fill"></i> Tambah Akun Baru
                </button>
            </div>
        </div>

        <!-- Tabel Daftar Pengguna -->
        <div class="card border-0 rounded-4 shadow-sm overflow-hidden">
            <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-shield-lock text-primary me-2"></i>Daftar Pengguna Sistem
                </h5>
                <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill">
                    <?= $total_users; ?> Akun Terdaftar
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th class="ps-4" width="80">NO</th>
                            <th>NAMA LENGKAP</th>
                            <th>USERNAME</th>
                            <th>ROLE / HAK AKSES</th>
                            <th>TANGGAL TERDAFTAR</th>
                            <th class="text-center pe-4" width="130">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_users > 0): ?>
                            <?php $no = 1; while ($u = $users_list->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 text-muted small"><?= $no++; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="brand-icon" style="width: 32px; height: 32px; font-size: 0.9rem; <?= $u['role'] === 'admin' ? 'background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);' : ''; ?>">
                                                <i class="bi <?= $u['role'] === 'admin' ? 'bi-shield-check' : 'bi-wrench'; ?>"></i>
                                            </div>
                                            <div>
                                                <strong class="text-dark d-block"><?= htmlspecialchars($u['nama_lengkap']); ?></strong>
                                                <?php if (intval($u['id']) === intval($_SESSION['user_id'])): ?>
                                                    <span class="badge bg-info-subtle text-info border border-info border-opacity-25" style="font-size: 0.65rem;">Akun Anda Saat Ini</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <code class="text-primary fw-bold fs-6">@<?= htmlspecialchars($u['username']); ?></code>
                                    </td>
                                    <td>
                                        <?php if ($u['role'] === 'admin'): ?>
                                            <span class="badge bg-danger text-uppercase px-2 py-1 rounded-pill">
                                                <i class="bi bi-shield-fill me-1"></i> Administrator IT
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-primary text-uppercase px-2 py-1 rounded-pill">
                                                <i class="bi bi-tools me-1"></i> Teknisi IT
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small">
                                        <?= date('d M Y, H:i', strtotime($u['created_at'])); ?> WIB
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <!-- Tombol Edit Akun -->
                                            <button type="button" class="btn btn-outline-primary"
                                                    title="Edit Akun"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEditUser"
                                                    data-id="<?= $u['id']; ?>"
                                                    data-username="<?= htmlspecialchars($u['username']); ?>"
                                                    data-nama="<?= htmlspecialchars($u['nama_lengkap']); ?>"
                                                    data-role="<?= $u['role']; ?>">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <!-- Tombol Hapus Akun -->
                                            <?php if (intval($u['id']) !== intval($_SESSION['user_id'])): ?>
                                                <button type="button" class="btn btn-outline-danger"
                                                        title="Hapus Akun"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalHapusUser"
                                                        data-id="<?= $u['id']; ?>"
                                                        data-username="<?= htmlspecialchars($u['username']); ?>"
                                                        data-nama="<?= htmlspecialchars($u['nama_lengkap']); ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-outline-secondary opacity-50" title="Tidak dapat menghapus akun sendiri" disabled>
                                                    <i class="bi bi-lock-fill"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- ================= MODAL TAMBAH USER ================= -->
    <div class="modal fade" id="modalTambahUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-plus-fill text-primary me-2"></i>Tambah Akun Petugas Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="users.php">
                    <input type="hidden" name="action" value="tambah">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold">Nama Lengkap *</label>
                                <input type="text" name="nama_lengkap" class="form-control" required placeholder="Contoh: Rian Anggara / Teknisi Jaringan">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Username *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">@</span>
                                    <input type="text" name="username" class="form-control" required placeholder="username_login">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Role / Hak Akses *</label>
                                <select name="role" class="form-select" required>
                                    <option value="teknisi" selected>Teknisi IT</option>
                                    <option value="admin">Administrator IT</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Password Login *</label>
                                <input type="password" name="password" class="form-control" required placeholder="Minimal 6 karakter" minlength="6">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-save me-1"></i> Simpan Akun
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= MODAL EDIT USER ================= -->
    <div class="modal fade" id="modalEditUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Edit Data Akun
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="users.php">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_user_id">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold">Nama Lengkap *</label>
                                <input type="text" name="nama_lengkap" id="edit_user_nama" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Username *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">@</span>
                                    <input type="text" name="username" id="edit_user_username" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Role / Hak Akses *</label>
                                <select name="role" id="edit_user_role" class="form-select" required>
                                    <option value="teknisi">Teknisi IT</option>
                                    <option value="admin">Administrator IT</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Ganti Password (Opsional)</label>
                                <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password" minlength="6">
                                <small class="text-muted" style="font-size: 0.75rem;">Biarkan kosong jika tetap menggunakan password lama.</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-check2-circle me-1"></i> Perbarui Akun
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= MODAL HAPUS USER ================= -->
    <div class="modal fade" id="modalHapusUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus Akun
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="users.php">
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="id" id="hapus_user_id">
                    <div class="modal-body p-4">
                        <p class="mb-2">Apakah Anda yakin ingin menghapus akun petugas berikut?</p>
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="small text-muted mb-1">Nama Lengkap: <strong id="hapus_user_nama" class="text-dark"></strong></div>
                            <div class="small text-muted">Username: <strong id="hapus_user_username" class="font-monospace text-primary"></strong></div>
                        </div>
                        <small class="text-danger mt-2 d-block"><i class="bi bi-info-circle me-1"></i>Akun yang dihapus tidak akan dapat login lagi ke sistem.</small>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4">
                            <i class="bi bi-trash-fill me-1"></i> Ya, Hapus Akun
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JavaScript Data Binding ke Modal -->
    <script>
        const modalEdit = document.getElementById('modalEditUser');
        if (modalEdit) {
            modalEdit.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                document.getElementById('edit_user_id').value = btn.getAttribute('data-id');
                document.getElementById('edit_user_nama').value = btn.getAttribute('data-nama');
                document.getElementById('edit_user_username').value = btn.getAttribute('data-username');
                document.getElementById('edit_user_role').value = btn.getAttribute('data-role');
            });
        }

        const modalHapus = document.getElementById('modalHapusUser');
        if (modalHapus) {
            modalHapus.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                document.getElementById('hapus_user_id').value = btn.getAttribute('data-id');
                document.getElementById('hapus_user_nama').textContent = btn.getAttribute('data-nama');
                document.getElementById('hapus_user_username').textContent = '@' + btn.getAttribute('data-username');
            });
        }
    </script>
</body>
</html>
