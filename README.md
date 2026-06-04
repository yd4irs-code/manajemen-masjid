# Aplikasi Manajemen & Display TV Masjid

Aplikasi Manajemen & Display TV Masjid adalah sistem informasi berbasis web yang dirancang secara khusus untuk memenuhi kebutuhan tata kelola masjid modern. Aplikasi ini berfokus pada dua fungsi utama: **Layar Informasi TV (Digital Signage)** untuk jamaah dan **Panel Administrasi Terpusat** untuk pengurus masjid.

Sistem ini sangat cocok dipasang pada Smart TV atau Monitor besar yang terhubung dengan mini-PC (seperti Raspberry Pi atau STB) di dalam ruang utama masjid.

---

## 🌟 Selayang Pandang

Pada era modern, transparansi dan penyebaran informasi di masjid harus dilakukan secara cepat dan akurat. Aplikasi ini menggantikan papan tulis manual atau kalender cetak dengan layar digital interaktif yang secara otomatis menampilkan waktu sholat, pengumuman, laporan keuangan, serta jadwal petugas sholat Jum'at.

Layar display didesain sedemikian rupa agar jamaah dapat membaca informasi dengan jelas. Di sisi lain, para pengurus masjid (Admin, Marbot, dan Bendahara) disediakan portal *dashboard* khusus untuk memperbarui konten tanpa harus mengutak-atik kode atau layar TV secara langsung.

---

## ✨ Fitur-Fitur Utama

### 1. Layar Display Interaktif (Frontend)
- **Jam Digital & Tanggal Presisi**: Menampilkan jam, tanggal Masehi, serta tanggal Hijriyah yang berubah secara otomatis sesuai pergerakan waktu maghrib (waktu pergantian hari Hijriyah disesuaikan pukul 18:20).
- **Jadwal Waktu Sholat Otomatis**: Waktu sholat (Subuh, Dzuhur, Ashar, Maghrib, Isya) terhitung otomatis berdasarkan garis lintang (latitude) dan garis bujur (longitude) lokasi masjid tanpa perlu *input* manual.
- **Hitung Mundur Adzan**: Saat waktu sholat mendekat, layar memunculkan penghitung waktu mundur (*countdown*).
- **Layar Siaga Adzan**: Layar akan mati sementara atau menampilkan layar penuh bertuliskan "Adzan" selama adzan berkumandang untuk mencegah gangguan visual.
- **Carousel Informasi & Wallpaper**: Menampilkan *slide* bergambar atau pengumuman teks yang berjalan secara bergantian (termasuk laporan saldo kas masjid secara otomatis).
- **Running Text Pintar**: Teks berjalan di bagian bawah layar yang fleksibel. Secara cerdas, sistem otomatis menarik dan merangkai laporan data **Pemasukan Kas 7 Hari Terakhir** untuk ditayangkan berselingan dengan pengumuman biasa.

### 2. Panel Pengurus (Backend Multi-User)
Sistem ini memisahkan hak akses menjadi 3 tingkatan (Role) untuk menjaga keamanan data:

- **Administrator** (Hak Akses Penuh)
  - Dapat mengakses seluruh menu aplikasi.
  - Memiliki akses eksklusif ke **Manajemen User**, **Setting Jadwal & Koordinat**, **Simulasi Waktu**, dan **Pengaturan Dasar** (Nama Masjid, Lokasi).
  
- **Bendahara** (Khusus Keuangan)
  - Hanya dapat melihat dan mengelola menu **Keuangan**.
  - Dapat menginput transaksi Pemasukan dan Pengeluaran.
  - Dapat mengatur "Saldo Awal".
  - Mencetak laporan buku kas per bulan, periode jumat, atau keseluruhan secara rapi.
  
- **Marbot** (Pengelola Konten Harian)
  - Diberikan wewenang mengatur konten layar: **Informasi**, **Running Text**, **Wallpaper**, dan **Petugas Jum'at**.
  - **Jadwal Petugas Jum'at**: Marbot dapat menyusun jadwal Khatib/Imam dan Muadzin setahun penuh. Nomor HP Khatib dapat disimpan untuk keperluan arsip tanpa ditampilkan di TV.
  - Dapat mencetak laporan penugasan Sholat Jum'at yang telah diformat rapi dengan kolom tanda tangan khusus Marbot.

---

## 🛠️ Persyaratan Sistem (System Requirements)

Untuk menjalankan aplikasi ini dengan lancar, Anda memerlukan spesifikasi server (bisa berupa *localhost* di PC pengurus) sebagai berikut:

- **Sistem Operasi**: Windows, Linux, atau macOS (bisa dijalankan via XAMPP/WAMP/MAMP/LAMP).
- **Web Server**: Apache atau Nginx.
- **PHP**: Versi 7.4 atau 8.0 ke atas. (Ekstensi PDO dan MySQLi wajib diaktifkan).
- **Database**: MySQL versi 5.7+ atau MariaDB versi 10.3+.
- **Browser Client**: Google Chrome, Mozilla Firefox, atau browser berbasis Chromium terbaru (Dianjurkan untuk Layar TV).

---

## 🚀 Cara Instalasi

Proses instalasi dirancang sesederhana mungkin agar mudah dipraktikkan oleh pengurus masjid:

1. **Unduh & Pindahkan File**
   Salin atau ekstrak seluruh folder aplikasi ini ke dalam direktori *server* lokal Anda (Misalnya `C:\xampp\htdocs\masjid` pada Windows XAMPP).
2. **Nyalakan Server**
   Buka panel kontrol web server Anda (contoh: XAMPP Control Panel), lalu tekan tombol **Start** pada modul **Apache** dan **MySQL**.
3. **Konfigurasi Database**
   Secara *default*, sistem akan mencari basis data bernama `db_masjid` di `localhost` dengan *username* `root` tanpa kata sandi. 
   *(Jika Anda menggunakan password khusus di MySQL, ubah pengaturannya di dalam berkas `db/koneksi.php` pada baris definisi konstan `DB_PASS` & `DB_USER`).*
4. **Migrasi Database Otomatis**
   - Buka browser dan ketikkan alamat: `http://localhost/masjid/db/migrate.php`.
   - Tunggu beberapa detik, sistem akan secara otomatis membuat struktur pangkalan data, tabel keuangan, tabel *users*, tabel petugas, dsb, serta menyuntikkan data-data awalan yang diperlukan.
   - Jika layar menyatakan migrasi sukses (berwarna hijau), sistem sudah siap dipakai.
5. **Login Pertama Kali**
   - Akses panel administrasi melalui: `http://localhost/masjid/marbot/`
   - Gunakan akun bawaan yang telah disediakan setelah proses migrasi. Jika tidak ada petunjuk khusus saat migrasi, disarankan untuk masuk menggunakan akun *Admin* pertama yang ada di pangkalan data tabel `users` atau membuatnya langsung secara manual via *phpMyAdmin*.

---

## 💡 Kustomisasi Tambahan

- **Tema & Tampilan Utama**: Berkas utama untuk perwajahan ada pada `index.php`. Segala bentuk estetika warna bisa diedit dengan mengganti atribut kelas berbasis Bootstrap atau modifikasi CSS.
- **Titik Koordinat Sholat**: Anda WAJIB mengganti garis Lintang (*Latitude*) dan Bujur (*Longitude*) pada menu **Pengaturan** di panel Admin sesuai dengan letak geografis kota/desa masjid Anda agar hitungan waktu adzan akurat.

## 🤝 Lisensi & Kontribusi
Sistem ini bersifat terbuka (*Open Source*) dan didedikasikan sepenuhnya untuk operasional masjid. Pengembang/programmer Muslim dipersilakan untuk menggandakan, memodifikasi, dan membagikan ulang sistem ini untuk membantu masjid-masjid lainnya di seluruh Indonesia agar menjadi lebih modern dan profesional.

---
*Semoga Allah memudahkan setiap niat baik kita dalam memakmurkan rumah-Nya.*
