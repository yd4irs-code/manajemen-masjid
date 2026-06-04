<?php
require_once 'session.php';
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'marbot'])) {
    die("Akses ditolak");
}

require_once '../db/koneksi.php';
$pdo = getDbConnection();

// Ambil data masjid dari settings
$stmtSet = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('nama', 'lokasi')");
$settings = [];
foreach($stmtSet->fetchAll() as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}
$namaMasjid = $settings['nama'] ?? 'Masjid';
$lokasiMasjid = $settings['lokasi'] ?? '';

// Ambil jadwal Petugas Jumat (dari sekarang hingga 1 tahun ke depan atau seluruhnya yang ada di database, kita ambil semua yang akan datang dan yang lama sedikit)
// Kita ambil berdasarkan tahun ini saja atau semua data yang ada diurutkan dari yang terbaru ke terlama?
// User: mencetak daftar petugas sholat jumat selama 1 tahun. Kita urutkan ascending (dari Januari - Desember)
$tahunIni = date('Y');
$stmtJumat = $pdo->query("SELECT * FROM petugas_jumat WHERE YEAR(tanggal) = '$tahunIni' ORDER BY tanggal ASC");
$jadwal = $stmtJumat->fetchAll();

// Jika ingin mengambil semua data (jika mau mencetak keseluruhan)
// $stmtJumat = $pdo->query("SELECT * FROM petugas_jumat ORDER BY tanggal ASC");
// $jadwal = $stmtJumat->fetchAll();

// Ambil nama marbot
$stmtMarbot = $pdo->query("SELECT nama_lengkap FROM users WHERE role='marbot' ORDER BY id ASC LIMIT 1");
$namaMarbot = $stmtMarbot->fetchColumn() ?: '(............................)';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Jadwal Petugas Jum'at</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background: #fff;
            color: #000;
            margin: 0;
            padding: 30px;
            font-size: 14px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 5px 0 0 0;
            font-size: 18px;
            font-weight: normal;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        th, td {
            border: 1px solid #000;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2 !important; /* Gunakan important agar tercetak di mode print background */
            -webkit-print-color-adjust: exact;
        }
        .text-center { text-align: center; }
        .signature-area {
            width: 100%;
            margin-top: 50px;
        }
        .signature-box {
            float: right;
            width: 300px;
            text-align: center;
        }
        .signature-date {
            margin-bottom: 70px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
        
        @media print {
            body { padding: 0; }
            button { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        
        <!-- Header / Kop Surat -->
        <div class="header">
            <h1>DAFTAR PETUGAS SHOLAT JUM'AT</h1>
            <h2><?= htmlspecialchars($namaMasjid) ?></h2>
            <p style="margin: 5px 0 0 0;"><?= htmlspecialchars($lokasiMasjid) ?></p>
            <p style="margin: 5px 0 0 0; font-weight: bold;">Tahun <?= htmlspecialchars($tahunIni) ?></p>
        </div>

        <!-- Tabel Jadwal -->
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width: 50px;">No</th>
                    <th class="text-center" style="width: 180px;">Tanggal</th>
                    <th>Khatib / Imam</th>
                    <th>No. HP</th>
                    <th>Muadzin / Bilal</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($jadwal) > 0): ?>
                    <?php $no = 1; foreach($jadwal as $j): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center">
                            <?php 
                                $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                                $tgl = strtotime($j['tanggal']);
                                echo date('d', $tgl) . ' ' . $bulan[(int)date('m', $tgl)] . ' ' . date('Y', $tgl);
                            ?>
                        </td>
                        <td><?= htmlspecialchars($j['khatib']) ?></td>
                        <td><?= htmlspecialchars($j['no_hp'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($j['muadzin']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center" style="padding: 30px;">Belum ada jadwal yang diinput untuk tahun ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Kolom Tanda Tangan -->
        <div class="signature-area">
            <div class="signature-box">
                <div class="signature-date">
                    Palembang, <?= date('d') ?> <?php $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; echo $bulan[(int)date('m')]; ?> <?= date('Y') ?><br>
                    <strong>Pengurus Masjid,</strong>
                </div>
                <div class="signature-name">
                    <?= htmlspecialchars($namaMarbot) ?>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>
        
    </div>
</body>
</html>
