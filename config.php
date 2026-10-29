<?php
/**
 * Konfigurasi Koneksi Database XAMPP (MySQL)
 * Project: Website Jasa Servis Komputer & Jaringan
 */

// Konfigurasi error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Matikan display warning mentah agar UI tetap bersih

// 0. Ambil konfigurasi (Prioritaskan Environment Variables Vercel/Cloud, lalu .env lokal, lalu default XAMPP)
$env_file = __DIR__ . '/.env';
$env_data = [];
if (file_exists($env_file)) {
    $env_lines = @file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($env_lines) {
        foreach ($env_lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($env_key, $env_val) = explode('=', $line, 2);
                $env_data[trim($env_key)] = trim($env_val);
            }
        }
    }
}

function getEnvVal($key, $default = '', $env_data = []) {
    $val = getenv($key);
    if ($val !== false && $val !== '') return $val;
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    if (isset($env_data[$key]) && $env_data[$key] !== '') return $env_data[$key];
    return $default;
}

$db_host = getEnvVal('DB_HOST', '127.0.0.1', $env_data);
$db_port = intval(getEnvVal('DB_PORT', '3306', $env_data));
$db_user = getEnvVal('DB_USER', 'root', $env_data);
$db_pass = getEnvVal('DB_PASS', '', $env_data);
$db_name = getEnvVal('DB_NAME', 'servis_it_db', $env_data);
$db_ssl  = getEnvVal('DB_SSL', '', $env_data);

// Deteksi apakah koneksi memerlukan enkripsi TLS/SSL (TiDB Cloud atau flag DB_SSL=true)
$use_ssl = ($db_ssl === 'true') || ($db_port === 4000) || (strpos($db_host, 'tidbcloud.com') !== false);

function getDbConnection($host, $user, $pass, $db, $port, $use_ssl) {
    if ($use_ssl) {
        $c = mysqli_init();
        if ($c) {
            $c->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
            $c->ssl_set(NULL, NULL, NULL, NULL, NULL);
            @$c->real_connect($host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);
            return $c;
        }
    }
    return @new mysqli($host, $user, $pass, $db, $port);
}

try {
    // 1. Coba hubungkan ke database
    mysqli_report(MYSQLI_REPORT_OFF); // Nonaktifkan exception crash agar error terhandle dengan ramah
    $conn = getDbConnection($db_host, $db_user, $db_pass, $db_name, $db_port, $use_ssl);

    // 2. Jika database belum ada (error 1049), buat database otomatis
    if ($conn->connect_errno == 1049) {
        $init_conn = getDbConnection($db_host, $db_user, $db_pass, '', $db_port, $use_ssl);
        if ($init_conn->connect_error) {
            tampilkanErrorKoneksi($init_conn->connect_error);
        }

        // Buat database
        $init_conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $init_conn->close();

        // Hubungkan kembali
        $conn = getDbConnection($db_host, $db_user, $db_pass, $db_name, $db_port, $use_ssl);
    } elseif ($conn->connect_error) {
        tampilkanErrorKoneksi($conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

    // 3. Inisialisasi tabel jika belum ada
    initDatabaseTables($conn);

} catch (Exception $e) {
    tampilkanErrorKoneksi($e->getMessage());
}

/**
 * Tampilan pesan error yang informatif dan ramah jika MySQL belum aktif
 */
function tampilkanErrorKoneksi($pesan_error) {
    $is_vercel = !empty(getenv('VERCEL')) || !empty($_ENV['VERCEL']);
    
    $solusi = $is_vercel 
        ? "<li>Buka dashboard proyek Anda di <strong>Vercel &rarr; Settings &rarr; Environment Variables</strong>.</li>
           <li>Pastikan Anda telah mengisi variabel database cloud (<strong>DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT</strong>).</li>
           <li>Ingat bahwa Vercel tidak dapat mengakses <code>127.0.0.1</code> laptop Anda, gunakan Cloud MySQL seperti <strong>TiDB Cloud</strong> atau <strong>Aiven</strong>.</li>
           <li>Redeploy proyek setelah menambahkan Environment Variables.</li>"
        : "<li>Buka aplikasi <strong>XAMPP Control Panel</strong>.</li>
           <li>Pastikan modul <strong>MySQL</strong> sudah diklik <strong>Start</strong> (berwarna hijau).</li>
           <li>Jika MySQL sempat crash, pastikan tidak ada port conflict dengan aplikasi lain di port 3306.</li>
           <li>Refresh / muat ulang halaman ini (F5).</li>";

    die("
    <div style='font-family: Arial, sans-serif; max-width: 650px; margin: 60px auto; padding: 25px; border-radius: 12px; background: #fff1f2; border: 1px solid #fecdd3; color: #9f1239; box-shadow: 0 4px 15px rgba(0,0,0,0.05);'>
        <h3 style='margin-top:0;'>⚠️ Koneksi Database Belum Terhubung</h3>
        <p><strong>Pesan Sistem:</strong> " . htmlspecialchars($pesan_error) . "</p>
        <hr style='border:0; border-top:1px solid #fecdd3; margin: 15px 0;'>
        <p><strong>Panduan Solusi:</strong></p>
        <ol style='padding-left: 20px; line-height: 1.6;'>
            $solusi
        </ol>
    </div>
    ");
}

/**
 * Fungsi untuk inisialisasi tabel otomatis jika belum ada di database
 */
function initDatabaseTables($conn) {
    // Tabel Users
    $table_users = "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `nama_lengkap` VARCHAR(100) NOT NULL,
        `role` ENUM('admin', 'teknisi') NOT NULL DEFAULT 'teknisi',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->query($table_users);

    // Tabel Laporan Servis IT
    $table_servis = "CREATE TABLE IF NOT EXISTS `laporan_servis` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->query($table_servis);

    // Migrasi otomatis: Pastikan kolom status bertipe VARCHAR jika tabel lama masih menggunakan ENUM
    $col_check = $conn->query("SHOW COLUMNS FROM `laporan_servis` LIKE 'status'");
    if ($col_check && $col_info = $col_check->fetch_assoc()) {
        if (stripos($col_info['Type'], 'enum') !== false) {
            $conn->query("ALTER TABLE `laporan_servis` MODIFY `status` VARCHAR(50) NOT NULL DEFAULT 'Diserahkan'");
        }
    }

    // Cek apakah user admin default sudah ada
    $check_user = $conn->query("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
    if ($check_user && $check_user->num_rows == 0) {
        $hash_admin = password_hash('admin123', PASSWORD_BCRYPT);
        $hash_teknisi = password_hash('teknisi123', PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
        
        $u1 = 'admin'; $n1 = 'Administrator IT'; $r1 = 'admin';
        $stmt->bind_param("ssss", $u1, $hash_admin, $n1, $r1);
        $stmt->execute();

        $u2 = 'teknisi'; $n2 = 'Teknisi Jaringan & Komputer'; $r2 = 'teknisi';
        $stmt->bind_param("ssss", $u2, $hash_teknisi, $n2, $r2);
        $stmt->execute();
        $stmt->close();

        // Masukkan data contoh servis awal
        $sample_data = "INSERT INTO `laporan_servis` 
            (`kode_tiket`, `nama_pelanggan`, `no_hp`, `alamat`, `jenis_layanan`, `tipe_perangkat`, `keluhan`, `tindakan`, `biaya`, `status`, `teknisi_nama`, `tanggal_masuk`, `tanggal_selesai`) 
            VALUES
            ('SRV-202610-001', 'Budi Santoso', '081234567890', 'Jl. Merdeka No. 12', 'Komputer', 'PC Desktop Core i7', 'Komputer sering mati mendadak saat render video dan bluescreen error', 'Ganti pasta thermal processor, pembersihan debu heatsink & ganti RAM 16GB', 350000, 'Selesai', 'Teknisi Jaringan & Komputer', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 1 DAY),
            ('SRV-202610-002', 'Klinik Sehat Medika', '085712345678', 'Jl. Sudirman Kav. 5', 'Jaringan', 'Router MikroTik RB750Gr3 + 3 Switch', 'Koneksi internet kantor sering RTO, antar ruangan tidak bisa cetak ke printer sharing', 'Konfigurasi ulang MikroTik, routing subnet LAN, crimping 4 kabel UTP Cat6 baru', 750000, 'Selesai', 'Teknisi Jaringan & Komputer', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 1 DAY),
            ('SRV-202610-003', 'PT. Maju Logistik', '082198765432', 'Kawasan Industri Blok B', 'Jaringan', 'Access Point Ruijie & Switch PoE', 'Perluasan jaringan WiFi untuk gudang logistik baru area 400m2', 'Penarikan kabel STP outdoor 80 meter, setup 2 unit AP Reyee PoE & manajemen SSID tamu', 1200000, 'Sedang Dikerjakan', 'Teknisi Jaringan & Komputer', NOW() - INTERVAL 1 DAY, NULL),
            ('SRV-202610-004', 'Rina Anggraini', '081377889900', 'Perumahan Griya Indah D-4', 'Komputer', 'Laptop Asus ROG Strix', 'Layar blank hitam setelah booting, kipas berputar kencang', 'Pengecekan chipset VGA dan fleksibel LCD, menunggu persetujuan pergantian sparepart', 150000, 'Menunggu Sparepart', 'Belum Ditentukan', NOW(), NULL),
            ('SRV-202610-005', 'Ahmad Fauzi', '089611223344', 'Jl. Mawar No. 8', 'Komputer', 'Laptop Lenovo ThinkPad', 'Upgrade SSD NVMe 512GB & migrasi OS Windows 11 Original', 'Antrian pengerjaan hari ini', 550000, 'Antrian', 'Belum Ditentukan', NOW(), NULL);";
        $conn->query($sample_data);
    }
}

/**
 * Fungsi Deteksi & Pemilihan Logo Otomatis Sesuai Warna Latar Belakang
 * 
 * $variant:
 *  - 'light' : Untuk latar belakang gelap (Navbar Web hitam, Navbar Admin).
 *              Mencari: logo-white.png, logo-light.png, logo.png, logo-color.png, logo-warna.png
 *  - 'dark'  : Untuk latar belakang terang / kertas putih (Cetak Nota/Laporan, Form Login).
 *              Mencari: logo-dark.png, logo-black.png, logo-color.png, logo-warna.png, atau fallback ke logo.png
 */
function getAppLogo($variant = 'light') {
    $fs_dir = __DIR__ . '/assets/img/';
    $web_dir = 'assets/img/';
    
    if ($variant === 'dark') {
        // Cari logo versi gelap atau berwarna untuk latar putih
        $dark_files = ['logo-dark.png', 'logo-black.png', 'logo-color.png', 'logo-warna.png'];
        foreach ($dark_files as $f) {
            if (file_exists($fs_dir . $f)) {
                return [
                    'found' => true,
                    'path' => $web_dir . $f,
                    'is_white_on_light' => false
                ];
            }
        }
        
        // Jika belum ada logo gelap/warna, gunakan logo.png / logo-white.png (ditandai untuk diberi background kontras)
        $white_files = ['logo.png', 'logo-white.png', 'logo-light.png'];
        foreach ($white_files as $f) {
            if (file_exists($fs_dir . $f)) {
                return [
                    'found' => true,
                    'path' => $web_dir . $f,
                    'is_white_on_light' => true
                ];
            }
        }
    } else {
        // Untuk latar gelap (Navbar): prioritaskan logo putih/terang atau logo.png
        $light_files = ['logo-white.png', 'logo-light.png', 'logo.png', 'logo-color.png', 'logo-warna.png', 'logo-dark.png'];
        foreach ($light_files as $f) {
            if (file_exists($fs_dir . $f)) {
                return [
                    'found' => true,
                    'path' => $web_dir . $f,
                    'is_white_on_light' => false
                ];
            }
        }
    }
    
    return [
        'found' => false,
        'path' => '',
        'is_white_on_light' => false
    ];
}
?>
