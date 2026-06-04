<?php
require_once 'session.php';
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'bendahara'])) {
    die("Akses ditolak");
}

require_once '../db/koneksi.php';
$pdo = getDbConnection();

$periode = $_GET['periode'] ?? 'semua';
$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

$startDate = '';
$endDate = '';
$judulPeriode = '';

if ($periode === 'jumat') {
    // Dari Jumat minggu lalu sampai hari ini
    $startDate = date('Y-m-d', strtotime('last friday'));
    $endDate = date('Y-m-d');
    $judulPeriode = "Periode: " . date('d/m/Y', strtotime($startDate)) . " s/d " . date('d/m/Y', strtotime($endDate));
} elseif ($periode === 'bulanan') {
    $startDate = sprintf('%04d-%02d-01', $tahun, $bulan);
    $endDate = date('Y-m-t', strtotime($startDate));
    $namaBulan = ['1'=>'Januari', '2'=>'Februari', '3'=>'Maret', '4'=>'April', '5'=>'Mei', '6'=>'Juni', '7'=>'Juli', '8'=>'Agustus', '9'=>'September', '10'=>'Oktober', '11'=>'November', '12'=>'Desember'];
    $judulPeriode = "Periode Bulan: " . $namaBulan[(int)$bulan] . " " . $tahun;
} else {
    $startDate = '1970-01-01';
    $endDate = '9999-12-31';
    $judulPeriode = "Seluruh Transaksi (Hingga " . date('d/m/Y') . ")";
}

// Ambil baseline saldo awal dari pengaturan
$baselineAwalRaw = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='saldo_awal'")->fetchColumn();
$baselineAwal = $baselineAwalRaw ? (float)$baselineAwalRaw : 0;

// Hitung saldo awal (baseline + akumulasi transaksi sebelum start_date)
$stmtSaldo = $pdo->prepare("SELECT SUM(CASE WHEN jenis='pemasukan' THEN nominal ELSE -nominal END) as saldo_awal FROM keuangan WHERE tanggal < :start_date");
$stmtSaldo->execute([':start_date' => $startDate]);
$saldoAwalTx = $stmtSaldo->fetchColumn();
$saldoAwalTx = $saldoAwalTx ? (float)$saldoAwalTx : 0;

$saldoAwal = $baselineAwal + $saldoAwalTx;

// Ambil transaksi dalam periode
$stmtTx = $pdo->prepare("SELECT * FROM keuangan WHERE tanggal >= :start_date AND tanggal <= :end_date ORDER BY tanggal ASC, id ASC");
$stmtTx->execute([':start_date' => $startDate, ':end_date' => $endDate]);
$transaksi = $stmtTx->fetchAll();

// Ambil data masjid dari settings
$stmtSet = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('nama', 'lokasi')");
$settings = [];
foreach($stmtSet->fetchAll() as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}
$namaMasjid = $settings['nama'] ?? 'Masjid';
$lokasiMasjid = $settings['lokasi'] ?? '';

// Ambil nama bendahara
$stmtBen = $pdo->query("SELECT nama_lengkap FROM users WHERE role='bendahara' ORDER BY id ASC LIMIT 1");
$namaBendahara = $stmtBen->fetchColumn() ?: '(............................)';

// Render Laporan HTML
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background: #fff;
            color: #333;
            margin: 0;
            padding: 20px;
            font-size: 13px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 3px double #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 5px 0 0 0;
            font-size: 18px;
            color: #555;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        .report-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        table th {
            background-color: #f8f9fa;
            font-weight: 600;
            text-align: center;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .saldo-awal { background-color: #f1f8ff; font-weight: bold; }
        .saldo-akhir { background-color: #e6f9e6; font-weight: bold; font-size: 14px; }
        
        .signature {
            float: right;
            text-align: center;
            width: 250px;
            margin-top: 20px;
        }
        .signature p { margin: 5px 0; }
        .signature .name {
            margin-top: 70px;
            font-weight: bold;
            text-decoration: underline;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <div class="header">
            <h1>LAPORAN KEUANGAN BUKU KAS</h1>
            <h2><?= htmlspecialchars($namaMasjid) ?></h2>
            <p><?= htmlspecialchars($lokasiMasjid) ?></p>
        </div>
        
        <div class="report-info">
            <div>
                <strong><?= $judulPeriode ?></strong>
            </div>
            <div>
                <strong>Dicetak pada:</strong> <?= date('d/m/Y H:i') ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:5%;">No</th>
                    <th style="width:12%;">Tanggal</th>
                    <th style="width:30%;">Keterangan</th>
                    <th style="width:12%;">Metode</th>
                    <th style="width:13%;">Pemasukan (Rp)</th>
                    <th style="width:13%;">Pengeluaran (Rp)</th>
                    <th style="width:15%;">Saldo (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="saldo-awal">
                    <td colspan="4" class="text-right"><strong>SALDO AWAL</strong></td>
                    <td class="text-right"></td>
                    <td class="text-right"></td>
                    <td class="text-right"><?= number_format($saldoAwal, 0, ',', '.') ?></td>
                </tr>
                <?php
                $no = 1;
                $saldoBerjalan = $saldoAwal;
                $totalMasuk = 0;
                $totalKeluar = 0;
                foreach ($transaksi as $tx) {
                    $masuk = $tx['jenis'] === 'pemasukan' ? $tx['nominal'] : 0;
                    $keluar = $tx['jenis'] === 'pengeluaran' ? $tx['nominal'] : 0;
                    $saldoBerjalan += ($masuk - $keluar);
                    $totalMasuk += $masuk;
                    $totalKeluar += $keluar;
                    ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center"><?= date('d-m-Y', strtotime($tx['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($tx['keterangan']) ?></td>
                        <td class="text-center"><?= htmlspecialchars($tx['metode']) ?></td>
                        <td class="text-right"><?= $masuk > 0 ? number_format($masuk, 0, ',', '.') : '-' ?></td>
                        <td class="text-right"><?= $keluar > 0 ? number_format($keluar, 0, ',', '.') : '-' ?></td>
                        <td class="text-right"><?= number_format($saldoBerjalan, 0, ',', '.') ?></td>
                    </tr>
                    <?php
                }
                ?>
                <tr class="saldo-akhir">
                    <td colspan="4" class="text-right"><strong>TOTAL & SALDO AKHIR</strong></td>
                    <td class="text-right"><?= number_format($totalMasuk, 0, ',', '.') ?></td>
                    <td class="text-right"><?= number_format($totalKeluar, 0, ',', '.') ?></td>
                    <td class="text-right"><?= number_format($saldoBerjalan, 0, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>

        <div class="signature">
            <p>Palembang, <?= date('d F Y') ?></p>
            <p>Bendahara,</p>
            <div class="name"><?= htmlspecialchars($namaBendahara) ?></div>
        </div>
    </div>
</body>
</html>
