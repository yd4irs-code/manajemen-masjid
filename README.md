# 🕌 Aplikasi Manajemen & Display TV Masjid

Aplikasi berbasis web yang dirancang khusus untuk kebutuhan tata kelola masjid modern. Sistem ini menggabungkan dua fungsi utama: **Layar Informasi Digital (Display TV)** untuk jamaah dan **Panel Administrasi** untuk pengurus masjid dengan sistem multi-pengguna (*multi-user*) berperan (*role-based access*).

> Sangat cocok dipasang pada Smart TV atau Monitor besar yang terhubung dengan mini-PC (Raspberry Pi, PC mini, STB Android, dll.) di dalam ruang utama masjid.

---

## 🌟 Gambaran Umum

Pada era modern, transparansi dan penyebaran informasi di masjid harus dilakukan secara cepat dan akurat. Aplikasi ini menggantikan papan tulis manual atau kalender cetak dengan layar digital yang secara otomatis menampilkan waktu sholat, pengumuman, saldo kas masjid, serta jadwal petugas sholat Jum'at — **semuanya diperbarui secara real-time tanpa perlu menyentuh layar TV**.

---

## ✨ Fitur Lengkap

### 1. 📺 Layar Display TV (Frontend — `index.php`)

Halaman utama yang ditampilkan ke jamaah di layar TV. Semua konten bersifat *live* dan sinkron otomatis.

| Fitur | Keterangan |
|---|---|
| **Jam & Tanggal Digital** | Jam berjalan real-time, tanggal Masehi, dan tanggal Hijriyah. Pergantian hari Hijriyah otomatis pada pukul 18:20. |
| **Jadwal Waktu Sholat** | Dihitung otomatis (Subuh, Dzuhur, Ashar, Maghrib, Isya) berdasarkan koordinat GPS masjid. Tidak perlu input manual. |
| **Hitung Mundur Adzan** | Countdown muncul otomatis saat mendekati waktu sholat. |
| **Layar Adzan & Iqomah** | Layar beralih penuh saat adzan dan menampilkan hitung mundur iqomah. |
| **Layar Sholat Jum'at** | Countdown otomatis setiap hari Jum'at, menampilkan nama Khatib & Muadzin pekan tersebut. |
| **Carousel Informasi** | Slide pengumuman teks + gambar wallpaper berputar otomatis. Termasuk **slide saldo kas masjid** yang disisipkan otomatis. |
| **Running Text** | Teks berjalan di bagian bawah layar. Otomatis menampilkan **rekap pemasukan kas 7 hari terakhir** berselang dengan teks pengumuman biasa. |
| **Widget Saldo Kas** | Menampilkan total saldo kas masjid terkini di sudut layar. |
| **Logo Masjid** | Logo ditampilkan di sudut layar, dapat diubah melalui panel admin. |
| **Wallpaper Dinamis** | Latar belakang berupa foto masjid yang dapat diganti dan berganti-ganti secara otomatis. |
| **Sinkronisasi Otomatis** | Layar TV **otomatis refresh** dalam hitungan detik setelah pengurus menyimpan perubahan di panel admin. Tidak perlu menyentuh TV. |
| **Refresh Ganti Hari** | Layar otomatis me-reload saat tengah malam untuk menyegarkan data jadwal hari baru. |

---

### 2. 🔐 Panel Pengurus (Backend — `marbot/`)

Panel administrasi berbasis web yang dapat diakses dari komputer/HP pengurus. Dilindungi sistem **login** dan **role-based access control**.

#### Sistem Peran (Role) Pengguna

| Role | Akses Menu |
|---|---|
| **Admin** | Akses penuh ke seluruh menu |
| **Bendahara** | Hanya menu Keuangan |
| **Marbot** | Informasi, Running Text, Wallpaper, Petugas Jum'at, Keuangan (view) |

---

#### Menu Panel Admin

| Menu | Fungsi | Role |
|---|---|---|
| **Informasi** | Kelola slide pengumuman (teks + header + footer), aktif/nonaktif tiap slide | Admin, Marbot |
| **Running Text** | Kelola teks berjalan, aktif/nonaktif tiap baris | Admin, Marbot |
| **Wallpaper** | Upload/hapus foto latar belakang layar (format JPG) | Admin, Marbot |
| **Petugas Jum'at** | Input jadwal Khatib & Muadzin mingguan; cetak PDF jadwal setahun | Admin, Marbot |
| **Timer** | Atur durasi adzan, iqomah, sholat, dan pergantian slide | Admin |
| **Setting Jadwal** | Atur metode hitung waktu sholat, koreksi menit per waktu sholat | Admin |
| **Simulasi Jadwal** | Simulasi tampilan layar untuk waktu tertentu | Admin |
| **Keuangan** | Input/edit/hapus transaksi, import CSV, atur saldo awal, cetak laporan | Admin, Bendahara |
| **Pengaturan** | Nama masjid, lokasi, koordinat GPS, zona waktu | Admin |
| **Manajemen User** | Tambah/edit/hapus akun pengurus (Admin, Marbot, Bendahara) | Admin |
| **Ganti Password** | Setiap pengguna dapat mengganti password sendiri | Semua |

---

#### Fitur Keuangan (Detail)

- **Buku Kas Digital**: Catat setiap transaksi pemasukan dan pengeluaran dengan keterangan, metode pembayaran (Tunai, Transfer, QRIS, Kotak Amal), dan tanggal.
- **Import Data CSV**: Upload file buku kas dari Excel/spreadsheet. Format kolom: `TANGGAL (DD/MM/YYYY) ; KETERANGAN ; PENERIMAAN ; PENGELUARAN`.
- **Saldo Otomatis**: Saldo dihitung otomatis dari saldo awal + total pemasukan – total pengeluaran.
- **Kosongkan Data**: Hapus seluruh data transaksi sekaligus (beserta reset saldo awal ke Rp 0).
- **Cetak Laporan PDF**: Laporan keuangan dengan format buku kas resmi, bisa dicetak per:
  - Jum'at (Jum'at lalu s/d hari ini)
  - Bulanan (pilih bulan & tahun)
  - Seluruh transaksi
- **Tanda Tangan Bendahara**: Nama bendahara otomatis tertera di laporan cetak.

---

## 🛠️ Persyaratan Sistem

| Komponen | Spesifikasi |
|---|---|
| **Sistem Operasi** | Windows, Linux, atau macOS |
| **Web Server** | Apache atau Nginx (XAMPP / LAMP / WAMP / MAMP) |
| **PHP** | Versi **7.4** atau lebih baru (ekstensi `pdo_mysql` wajib aktif) |
| **Database** | MySQL 5.7+ atau MariaDB 10.3+ |
| **Browser (Display TV)** | Google Chrome / Chromium terbaru (disarankan mode kiosk) |
| **Browser (Panel Admin)** | Chrome, Firefox, Edge terbaru |

---

## 🚀 Cara Instalasi

Proses instalasi dipandu oleh **wizard instalasi grafis** (`installer.php`). Ikuti langkah-langkah berikut:

### Langkah 1 — Salin File ke Server
Salin seluruh folder aplikasi ke direktori web server Anda:
- **XAMPP Windows**: `C:\xampp\htdocs\masjid\`
- **Linux / Hosting**: `/var/www/html/masjid/` atau direktori `public_html`

### Langkah 2 — Nyalakan Server
Buka panel kontrol XAMPP (atau sejenisnya), tekan **Start** pada modul **Apache** dan **MySQL**.

### Langkah 3 — Jalankan Installer
Buka browser dan akses:
```
http://localhost/masjid/installer.php
```
Wizard instalasi akan membimbing Anda melalui 4 tahap:

| Tahap | Keterangan |
|---|---|
| **Step 1** — Cek Sistem | Memeriksa versi PHP dan ekstensi yang diperlukan |
| **Step 2** — Konfigurasi Database | Input Host, Port, Username, Password, dan Nama Database MySQL |
| **Step 3** — Buat Tabel | Installer otomatis membuat seluruh tabel yang diperlukan |
| **Step 4** — Buat Admin | Input nama masjid, lokasi, username, dan password Admin pertama |

> Setelah instalasi selesai, **hapus atau rename file `installer.php`** untuk keamanan.

### Langkah 4 — Login ke Panel Admin
```
http://localhost/masjid/marbot/
```
Gunakan username dan password yang Anda buat pada Step 4 wizard instalasi.

### Langkah 5 — Tampilkan Display di TV
Buka browser di perangkat yang terhubung ke TV, akses:
```
http://localhost/masjid/
```
atau jika TV menggunakan IP jaringan yang sama:
```
http://[IP-PC-SERVER]/masjid/
```

---

## ⚙️ Konfigurasi Awal yang Wajib Dilakukan

Setelah instalasi, segera lakukan konfigurasi berikut melalui Panel Admin → **Pengaturan**:

1. **Koordinat GPS Masjid** ⚠️ *Sangat Penting*
   - Ubah nilai **Latitude** dan **Longitude** sesuai lokasi masjid Anda.
   - Jika koordinat salah, jadwal waktu sholat akan tidak akurat.
   - Koordinat bisa diperoleh dari Google Maps (klik kanan lokasi → "What's here?").

2. **Nama & Lokasi Masjid**
   - Akan tampil di laporan cetak keuangan dan jadwal Jum'at.

3. **Zona Waktu**
   - Sesuaikan zona waktu (WIB = 7, WITA = 8, WIT = 9).

4. **Saldo Awal Kas**
   - Masuk ke menu **Keuangan** → Atur Saldo Awal sebelum mulai mencatat transaksi.

---

## 📁 Struktur Folder Penting

```
masjid/
├── index.php           ← Layar Display TV (halaman utama jamaah)
├── installer.php       ← Wizard instalasi (hapus setelah install)
├── check_update.php    ← Endpoint cek sinkronisasi otomatis layar TV
├── db/
│   ├── koneksi.php     ← Konfigurasi koneksi database MySQL
│   └── migrate.php     ← (Legacy) opsional, tidak diperlukan lagi
├── marbot/
│   ├── index.php       ← Halaman utama Panel Admin
│   ├── login.php       ← Halaman login pengurus
│   ├── proses.php      ← Backend utama (AJAX handler semua fitur)
│   ├── session.php     ← Manajemen sesi & autentikasi
│   ├── cetak_laporan.php  ← Cetak laporan keuangan PDF
│   └── cetak_jumat.php    ← Cetak jadwal petugas Jum'at PDF
├── css/                ← Stylesheet khusus layar Display
├── js/                 ← JavaScript utama & pustaka pendukung
├── dist/               ← Aset AdminLTE (Bootstrap, FontAwesome, dll.)
├── wallpaper/          ← Foto latar belakang layar TV
└── logo/               ← Logo masjid
```

---

## 🔄 Cara Kerja Sinkronisasi Otomatis

Setiap kali pengurus menyimpan perubahan melalui panel admin, sistem menulis *timestamp* baru ke file `db/last_updated.txt`. Layar TV melakukan pengecekan ke `check_update.php` **setiap 1 detik**. Jika timestamp berubah, browser Display akan **otomatis refresh** tanpa perlu intervensi manual.

```
Panel Admin simpan data
       ↓
System tulis timestamp baru ke last_updated.txt
       ↓
Browser Display cek setiap 1 detik via AJAX
       ↓
Timestamp berubah → browser otomatis reload
```

---

## 🔐 Keamanan

- Semua password disimpan dengan enkripsi **BCRYPT** (standar industri).
- Setiap endpoint dilindungi validasi sesi dan pemeriksaan role.
- Semua input data menggunakan **Prepared Statement** untuk mencegah SQL Injection.
- Role `bendahara` dan `marbot` tidak dapat mengakses menu di luar kewenangannya.

---

## 💡 Tips Penggunaan

- **Mode Kiosk TV**: Untuk tampilan optimal di TV, buka Chrome dalam mode kiosk:
  ```
  chrome.exe --kiosk http://localhost/masjid/
  ```
- **Lupa Password Admin**: Gunakan phpMyAdmin untuk mengganti kolom `password` di tabel `users` dengan hash BCRYPT baru, lalu login dengan password baru tersebut.
- **Import Keuangan dari Excel**: Simpan file Excel sebagai `.csv` dengan pemisah titik koma (`;`), pastikan format tanggal adalah `DD/MM/YYYY`.

---

## 🤝 Lisensi & Kontribusi

Sistem ini bersifat terbuka (*Open Source*) dan didedikasikan sepenuhnya untuk operasional masjid. Pengembang/programmer Muslim dipersilakan untuk menggandakan, memodifikasi, dan membagikan ulang sistem ini untuk membantu masjid-masjid lainnya di seluruh Indonesia agar menjadi lebih modern dan profesional.

---

*بَارَكَ اللهُ فِيكُمْ — Semoga Allah memudahkan setiap niat baik kita dalam memakmurkan rumah-Nya.*
