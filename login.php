<?php
require_once 'config.php';
require_once 'auth.php';

// Jika sudah ada session login, langsung arahkan ke Laporan Servis IT
if (isLoggedIn()) {
    header("Location: laporan.php");
    exit();
}

$error_message = '';
$success_message = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'harus_login') {
    $error_message = 'Silakan login terlebih dahulu untuk mengakses Menu Laporan Servis IT!';
} elseif (isset($_GET['msg']) && $_GET['msg'] === 'logout') {
    $success_message = 'Anda telah berhasil logout.';
}

// Proses Form Login saat disubmit
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error_message = 'Harap isi username dan password!';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // Verifikasi password hash bcrypt
            $is_valid = password_verify($password, $user['password']);

            // Kompatibilitas jika password di database masih plain-text, otomatis di-upgrade ke Bcrypt
            if (!$is_valid && $password === $user['password']) {
                $new_hash = password_hash($password, PASSWORD_BCRYPT);
                $up_stmt = $conn->prepare("UPDATE `users` SET `password` = ? WHERE `id` = ?");
                $up_stmt->bind_param("si", $new_hash, $user['id']);
                $up_stmt->execute();
                $up_stmt->close();
                $is_valid = true;
            }

            if ($is_valid) {
                // Set Session Login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();

                // Redirect ke Menu Laporan Servis IT
                header("Location: laporan.php");
                exit();
            } else {
                $error_message = 'Password yang Anda masukkan salah!';
            }
        } else {
            $error_message = 'Username tidak terdaftar dalam sistem!';
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
    <title>Login Petugas IT - Servis Komputer & Jaringan</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0369a1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            width: 100%;
            max-width: 440px;
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 65px;
            height: 65px;
            border-radius: 18px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #fff;
            font-size: 1.8rem;
            box-shadow: 0 10px 20px rgba(2, 132, 199, 0.35);
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            font-size: 0.95rem;
        }
        .form-control:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15);
        }
        .btn-login {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            color: white;
            transition: all 0.2s ease-in-out;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(2, 132, 199, 0.35);
            color: white;
        }
        .demo-box {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 12px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="p-4 p-md-5">
        <div class="text-center mb-4">
            <?php $logo_login = getAppLogo('dark'); ?>
            <?php if ($logo_login['found']): ?>
                <?php if ($logo_login['is_white_on_light']): ?>
                    <div class="mb-3">
                        <div class="d-inline-flex p-3 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                            <img src="<?= $logo_login['path']; ?>" alt="Logo" style="max-height: 60px; width: auto; object-fit: contain;">
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <img src="<?= $logo_login['path']; ?>" alt="Logo" style="max-height: 70px; width: auto; object-fit: contain;">
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="brand-badge mb-3">
                    <i class="bi bi-hdd-network"></i>
                </div>
            <?php endif; ?>
            <h4 class="fw-bold text-dark mb-1">Login Petugas IT</h4>
            <p class="text-muted small">Sistem Servis Komputer & Jaringan</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center py-2 px-3 small" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>
                <div><?= htmlspecialchars($error_message); ?></div>
                <button type="button" class="btn-close ms-auto p-2" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center py-2 px-3 small" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-6"></i>
                <div><?= htmlspecialchars($success_message); ?></div>
                <button type="button" class="btn-close ms-auto p-2" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label for="username" class="form-label text-secondary small fw-bold">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" id="username" name="username" placeholder="Masukkan username" required autocomplete="username">
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label text-secondary small fw-bold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-key"></i></span>
                    <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="btn btn-login w-100 mb-3">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Laporan Servis
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="index.php" class="text-decoration-none small text-muted hover-underline">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Halaman Utama (Website)
            </a>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
