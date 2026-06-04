<?php
session_start();
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

function get_php_version_status() {
    return version_compare(PHP_VERSION, '7.4.0', '>=');
}

function get_pdo_status() {
    return extension_loaded('pdo_mysql');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'db_config') {
        $host = $_POST['db_host'];
        $user = $_POST['db_user'];
        $pass = $_POST['db_pass'];
        $name = $_POST['db_name'];

        try {
            // Test connection
            $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            
            // Rewrite koneksi.php
            $koneksiPath = 'db/koneksi.php';
            if (file_exists($koneksiPath)) {
                $content = file_get_contents($koneksiPath);
                
                // Replace defines using regex to ensure it overrides existing configs
                $content = preg_replace("/define\('DB_HOST',\s*'.*?'\);/", "define('DB_HOST', '$host');", $content);
                $content = preg_replace("/define\('DB_USER',\s*'.*?'\);/", "define('DB_USER', '$user');", $content);
                $content = preg_replace("/define\('DB_PASS',\s*'.*?'\);/", "define('DB_PASS', '$pass');", $content);
                $content = preg_replace("/define\('DB_NAME',\s*'.*?'\);/", "define('DB_NAME', '$name');", $content);
                
                file_put_contents($koneksiPath, $content);
            }

            // Run Migrations (Create Tables)
            $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
                `id`            INT AUTO_INCREMENT PRIMARY KEY,
                `setting_key`   VARCHAR(100) UNIQUE NOT NULL,
                `setting_value` TEXT,
                `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `info` (
                `id`         INT AUTO_INCREMENT PRIMARY KEY,
                `header`     VARCHAR(100)  NOT NULL,
                `body`       TEXT,
                `footer`     VARCHAR(100)  DEFAULT '',
                `is_active`  TINYINT(1)   DEFAULT 1,
                `sort_order` INT           DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `running_text` (
                `id`         INT AUTO_INCREMENT PRIMARY KEY,
                `teks`       VARCHAR(255)  NOT NULL,
                `is_active`  TINYINT(1)   DEFAULT 1,
                `sort_order` INT           DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
                `id`            INT AUTO_INCREMENT PRIMARY KEY,
                `username`      VARCHAR(50)  UNIQUE NOT NULL,
                `password`      VARCHAR(255) NOT NULL,
                `nama_lengkap`  VARCHAR(100) NOT NULL,
                `role`          ENUM('admin', 'marbot', 'bendahara') NOT NULL DEFAULT 'admin',
                `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `keuangan` (
                `id`          INT AUTO_INCREMENT PRIMARY KEY,
                `tanggal`     DATE NOT NULL,
                `jenis`       ENUM('pemasukan', 'pengeluaran') NOT NULL,
                `metode`      VARCHAR(50) DEFAULT 'Tunai',
                `nominal`     DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `keterangan`  TEXT,
                `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `petugas_jumat` (
                `id`          INT AUTO_INCREMENT PRIMARY KEY,
                `tanggal`     DATE NOT NULL UNIQUE,
                `khatib`      VARCHAR(100) NOT NULL,
                `muadzin`     VARCHAR(100) NOT NULL,
                `no_hp`       VARCHAR(50) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $_SESSION['install_db'] = $name;
            header("Location: installer.php?step=3");
            exit;
        } catch (PDOException $e) {
            $error = 'Koneksi ke database gagal: ' . $e->getMessage();
        }
    } elseif ($action === 'admin_setup') {
        $nama_masjid = $_POST['nama_masjid'];
        $lokasi = $_POST['lokasi'];
        $admin_user = $_POST['admin_user'];
        $admin_pass = password_hash($_POST['admin_pass'], PASSWORD_BCRYPT);
        
        require_once 'db/koneksi.php';
        try {
            $pdo = getDbConnection();
            
            // Insert initial settings
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v1) ON DUPLICATE KEY UPDATE setting_value=:v2");
            $stmt->execute([':k' => 'nama', ':v1' => $nama_masjid, ':v2' => $nama_masjid]);
            $stmt->execute([':k' => 'lokasi', ':v1' => $lokasi, ':v2' => $lokasi]);
            
            // Insert Default Admin
            $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (:u, :p1, 'Administrator', 'admin') ON DUPLICATE KEY UPDATE password=:p2");
            $stmt->execute([':u' => $admin_user, ':p1' => $admin_pass, ':p2' => $admin_pass]);
            
            header("Location: installer.php?step=4");
            exit;
        } catch (Exception $e) {
            $error = 'Gagal menyimpan pengaturan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalasi Sistem Masjid</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7f6;
            margin: 0;
            padding: 40px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        h2 {
            margin-top: 0;
            color: #333;
            text-align: center;
        }
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .step {
            color: #ccc;
            font-weight: bold;
        }
        .step.active {
            color: #27ae60;
            border-bottom: 3px solid #27ae60;
            padding-bottom: 8px;
            margin-bottom: -12px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            background: #27ae60;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
        .btn:hover {
            background: #219653;
        }
        .alert {
            padding: 10px;
            background: #e74c3c;
            color: #fff;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .check-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border: 1px solid #eee;
            margin-bottom: 5px;
            border-radius: 4px;
        }
        .status-ok { color: #27ae60; font-weight: bold; }
        .status-err { color: #e74c3c; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h2>Instalasi Sistem Masjid</h2>
    
    <div class="steps">
        <div class="step <?= $step == 1 ? 'active' : '' ?>">1. Syarat Sistem</div>
        <div class="step <?= $step == 2 ? 'active' : '' ?>">2. Database</div>
        <div class="step <?= $step == 3 ? 'active' : '' ?>">3. Admin Setup</div>
        <div class="step <?= $step == 4 ? 'active' : '' ?>">4. Selesai</div>
    </div>

    <?php if ($error): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($step == 1): 
        $php_ok = get_php_version_status();
        $pdo_ok = get_pdo_status();
        $all_ok = $php_ok && $pdo_ok;
    ?>
        <div class="check-item">
            <span>Versi PHP (Minimal 7.4.0)</span>
            <span class="<?= $php_ok ? 'status-ok' : 'status-err' ?>"><?= PHP_VERSION ?> <?= $php_ok ? 'OK' : 'Gagal' ?></span>
        </div>
        <div class="check-item">
            <span>Ekstensi PDO MySQL</span>
            <span class="<?= $pdo_ok ? 'status-ok' : 'status-err' ?>"><?= $pdo_ok ? 'Aktif' : 'Tidak Aktif' ?></span>
        </div>
        <div style="margin-top: 20px;">
            <?php if ($all_ok): ?>
                <a href="?step=2"><button class="btn">Lanjutkan</button></a>
            <?php else: ?>
                <button class="btn" style="background:#ccc;cursor:not-allowed;" disabled>Sistem Tidak Memenuhi Syarat</button>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($step == 2): ?>
        <p>Silakan buat database kosong terlebih dahulu di cPanel/Hosting Anda. Kemudian masukkan detailnya di bawah ini.</p>
        <form method="POST">
            <input type="hidden" name="action" value="db_config">
            <div class="form-group">
                <label>Host Database</label>
                <input type="text" name="db_host" value="localhost" required>
            </div>
            <div class="form-group">
                <label>Nama Database</label>
                <input type="text" name="db_name" placeholder="contoh: user_dbmasjid" required>
            </div>
            <div class="form-group">
                <label>Username Database</label>
                <input type="text" name="db_user" placeholder="contoh: root atau user_db" required>
            </div>
            <div class="form-group">
                <label>Password Database</label>
                <input type="password" name="db_pass" placeholder="(Kosongkan jika di localhost XAMPP)">
            </div>
            <button type="submit" class="btn">Tes Koneksi & Buat Tabel</button>
        </form>
    <?php endif; ?>

    <?php if ($step == 3): ?>
        <p>Tabel berhasil dibuat di database <strong><?= htmlspecialchars($_SESSION['install_db'] ?? '') ?></strong>. Sekarang mari atur profil masjid dan akun administrator pertama.</p>
        <form method="POST">
            <input type="hidden" name="action" value="admin_setup">
            <div class="form-group">
                <label>Nama Masjid</label>
                <input type="text" name="nama_masjid" placeholder="Contoh: Masjid Agung At-Taqwa" required>
            </div>
            <div class="form-group">
                <label>Lokasi / Alamat</label>
                <input type="text" name="lokasi" placeholder="Contoh: Jl. Sudirman No.1, Jakarta" required>
            </div>
            <hr style="margin:20px 0; border:1px solid #eee;">
            <div class="form-group">
                <label>Username Login Admin</label>
                <input type="text" name="admin_user" value="admin" required>
            </div>
            <div class="form-group">
                <label>Password Login Admin</label>
                <input type="password" name="admin_pass" required minlength="4">
            </div>
            <button type="submit" class="btn">Simpan Pengaturan</button>
        </form>
    <?php endif; ?>

    <?php if ($step == 4): ?>
        <div style="text-align:center;">
            <h1 style="color:#27ae60; font-size:48px; margin:10px 0;">✔</h1>
            <h3 style="color:#27ae60;">Instalasi Berhasil!</h3>
            <p>Sistem Manajemen & Display TV Masjid siap digunakan.</p>
            <div class="alert" style="background:#f39c12; text-align:left;">
                <strong>Perhatian Penting:</strong><br>
                Demi keamanan sistem, Anda <u>sangat disarankan</u> untuk menghapus file <strong>installer.php</strong> dari server sekarang juga agar tidak ada orang lain yang mereset sistem Anda.
            </div>
            <a href="index.php"><button class="btn" style="background:#2980b9;">Buka Layar Display Utama</button></a>
            <br>
            <a href="marbot/index.php"><button class="btn" style="background:#8e44ad;">Buka Panel Administrator</button></a>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
