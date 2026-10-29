# Website Jasa Servis Komputer & Jaringan (IT-Net Solution)

Sistem Web Jasa Servis IT & Aplikasi Pelaporan Servis berbasis PHP, HTML5, dan MySQL untuk **XAMPP**.

---

## 🚀 Fitur Utama

1. **Website Showcase & Jasa Servis (HTML5 & Bootstrap 5)**:
   - **Navbar Responsif**: Menyesuaikan status login session secara dinamis.
   - **Layanan Servis Komputer & Laptop**: Hardware mati total, perbaikan motherboard/LCD, tune-up & ganti pasta pendingin, upgrade SSD NVMe/RAM, install ulang OS, dan data recovery.
   - **Layanan Jaringan Komputer**: Instalasi kabel LAN Cat6, setup Router MikroTik (Bandwidth management, failover, VPN, hotspot), konfigurasi WiFi Access Point enterprise, dan maintenance kontrak IT.
   - **Estimasi Biaya Transparan**: Rincian paket servis komputer, jaringan kantor, dan penarikan kabel LAN per titik.
   - **Formulir Booking / Konsultasi Online**: Pengunjung dapat mendaftarkan perbaikan komputer/jaringan langsung dari web. Tiket otomatis terbit dan masuk ke sistem laporan antrian teknisi.

2. **Login Session & Hak Akses Petugas**:
   - Pengelolaan sesi login aman menggunakan PHP `$_SESSION`.
   - Otomatis dialihkan (**redirect**) ke **Menu Laporan Servis IT** (`laporan.php`) setelah login berhasil.
   - Dilengkapi proteksi session (`checkAuth()`) agar halaman laporan tidak dapat diakses tanpa login.

3. **Menu Laporan Servis IT (`laporan.php`)**:
   - **Statistik KPI Dashboard**: Total servis masuk, per kategori (Komputer vs Jaringan), status selesai, dan total pendapatan biaya servis.
   - **Filter & Pencarian**: Filter berdasarkan jenis layanan, status pengerjaan, dan pencarian cepat nomor tiket / nama pelanggan.
   - **Manajemen Data (CRUD)**:
     - Tambah servis masuk secara manual.
     - Update progres status (Antrian, Sedang Dikerjakan, Menunggu Sparepart, Selesai, Dibatalkan).
     - Input tindakan perbaikan, biaya, dan nama teknisi penanggung jawab.
     - Hapus data servis.
   - **Cetak Laporan Resmi (`cetak_laporan.php`)**:
     - Cetak Nota / Tanda Terima Servis per pelanggan (format rapi siap print/PDF dilengkapi syarat & tanda tangan).
     - Cetak Rekapitulasi Laporan Keseluruhan untuk pimpinan unit IT.

---

## 🔑 Akun Login Default

| Role | Username | Password |
|---|---|---|
| **Administrator IT** | `admin` | `admin123` |
| **Teknisi IT** | `teknisi` | `teknisi123` |

---

## 🛠️ Cara Menjalankan di XAMPP

1. Buka **XAMPP Control Panel**.
2. Klik tombol **Start** pada modul **Apache** dan **MySQL**.
3. Buka browser (Google Chrome / Edge) dan akses alamat:
   - Website Utama: `http://localhost/servis_it/`
   - Halaman Login: `http://localhost/servis_it/login.php`
   - Menu Laporan Servis: `http://localhost/servis_it/laporan.php`
4. *Catatan Database*: Sistem ini memiliki fitur **Auto-Installer**. Saat pertama kali Anda mengakses web dengan MySQL XAMPP aktif, database `servis_it_db` dan tabel-tabelnya akan dibuat secara otomatis beserta data sampel.
   *(Jika ingin import manual melalui phpMyAdmin, file `database.sql` sudah tersedia di dalam folder ini)*.
