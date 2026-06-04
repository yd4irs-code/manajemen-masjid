<?php
/* *****************************************************************************************************************
 * *** KONFIGURASI DATABASE MYSQL
 * *****************************************************************************************************************/

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_masjid');
define('DB_PORT', 3306);

/**
 * Membuat koneksi PDO ke MySQL
 */
function getDbConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode([
                'success'    => false,
                'registered' => false,
                'data'       => 'Koneksi database gagal: ' . $e->getMessage()
            ]));
        }
    }
    return $pdo;
}

/**
 * Ambil semua settings dari tabel settings dan susun menjadi array $database
 * seperti format database.json sebelumnya
 */
function loadDatabase() {
    $pdo  = getDbConnection();
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $rows = $stmt->fetchAll();

    $s = [];
    foreach ($rows as $row) {
        $s[$row['setting_key']] = $row['setting_value'];
    }

    // Ambil info
    $stmtInfo = $pdo->query("SELECT * FROM info ORDER BY sort_order ASC, id ASC");
    $infoRows  = $stmtInfo->fetchAll();
    $infoArr   = [];
    foreach ($infoRows as $r) {
        $infoArr[] = [
            $r['header'],
            $r['body'],
            $r['footer'],
            (bool)$r['is_active']
        ];
    }

    // Ambil jadwal Petugas Jumat terdekat
    try {
        $stmtJumat = $pdo->query("SELECT * FROM petugas_jumat WHERE tanggal >= CURDATE() ORDER BY tanggal ASC LIMIT 1");
        $jumatRow = $stmtJumat->fetch();
        if ($jumatRow) {
            $bulan = [1=>"Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
            $tgl = strtotime($jumatRow['tanggal']);
            $formatTanggal = date('d ', $tgl) . $bulan[(int)date('m', $tgl)] . date(' Y', $tgl);
            
            $infoArr[] = [
                "Petugas Shalat Jum'at",
                $jumatRow['khatib'] . " (Imam/Khatib)\n" . $jumatRow['muadzin'] . " (Muadzin/Bilal)",
                "Jum'at, " . $formatTanggal,
                true
            ];
        }
    } catch (Exception $e) {}

    // Ambil running_text
    $stmtRt = $pdo->query("SELECT teks, is_active FROM running_text ORDER BY sort_order ASC, id ASC");
    $rtRows  = $stmtRt->fetchAll();
    $rtArr   = [];
    foreach ($rtRows as $r) {
        $rtArr[] = [
            $r['teks'],
            (bool)$r['is_active']
        ];
    }

    // Ambil rekap pemasukan 7 hari terakhir untuk running text
    try {
        $stmtKeuangan = $pdo->query("SELECT tanggal, keterangan, nominal, metode FROM keuangan WHERE jenis='pemasukan' AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ORDER BY tanggal DESC");
        $kRows = $stmtKeuangan->fetchAll();
        if (count($kRows) > 0) {
            $items = [];
            foreach ($kRows as $k) {
                $metodeStr = empty($k['metode']) ? 'Tunai' : htmlspecialchars($k['metode']);
                $keteranganStr = empty($k['keterangan']) ? 'Hamba Allah' : htmlspecialchars($k['keterangan']);
                $items[] = "Dari " . $keteranganStr . " sejumlah Rp " . number_format($k['nominal'], 0, ',', '.') . " melalui " . $metodeStr;
            }
            $rtText = implode(" *** ", $items);
            $rtArr[] = [
                $rtText,
                true
            ];
        }
    } catch (Exception $e) {}

    // Susun array database sesuai format lama
    $db = [
        'akses' => [
            'user' => $s['akses_user'] ?? 'admin',
            'pass' => $s['akses_pass'] ?? 'admin',
        ],
        'setting' => [
            'nama'      => $s['nama']      ?? 'Masjid',
            'lokasi'    => $s['lokasi']    ?? '',
            'latitude'  => $s['latitude']  ?? '-6.14',
            'longitude' => $s['longitude'] ?? '106.59',
            'timeZone'  => $s['timeZone']  ?? '7',
            'dst'       => $s['dst']       ?? '0',
        ],
        'prayTimesMethod' => $s['prayTimesMethod'] ?? '0',
        'prayTimesAdjust' => [
            'fajr'    => $s['prayTimesAdjust_fajr']    ?? '20',
            'dhuhr'   => $s['prayTimesAdjust_dhuhr']   ?? '',
            'asr'     => $s['prayTimesAdjust_asr']     ?? 'Standard',
            'maghrib' => $s['prayTimesAdjust_maghrib'] ?? '',
            'isha'    => $s['prayTimesAdjust_isha']    ?? '18',
        ],
        'prayTimesTune' => [
            'fajr'    => $s['prayTimesTune_fajr']    ?? '0',
            'dhuhr'   => $s['prayTimesTune_dhuhr']   ?? '0',
            'asr'     => $s['prayTimesTune_asr']     ?? '0',
            'maghrib' => $s['prayTimesTune_maghrib'] ?? '0',
            'isha'    => $s['prayTimesTune_isha']    ?? '0',
        ],
        'prayName' => [
            'fajr'    => $s['prayName_fajr']    ?? 'Subuh',
            'dhuhr'   => $s['prayName_dhuhr']   ?? 'Dzuhur',
            'asr'     => $s['prayName_asr']     ?? 'Ashar',
            'maghrib' => $s['prayName_maghrib'] ?? 'Maghrib',
            'isha'    => $s['prayName_isha']    ?? "Isya'",
        ],
        'timeName' => [
            'Hours'   => $s['timeName_Hours']   ?? 'Jam',
            'Minutes' => $s['timeName_Minutes'] ?? 'Menit',
            'Seconds' => $s['timeName_Seconds'] ?? 'Detik',
        ],
        'dayName' => [
            'Sunday'    => $s['dayName_Sunday']    ?? 'Minggu',
            'Monday'    => $s['dayName_Monday']    ?? 'Senin',
            'Tuesday'   => $s['dayName_Tuesday']   ?? 'Selasa',
            'Wednesday' => $s['dayName_Wednesday'] ?? 'Rabu',
            'Thursday'  => $s['dayName_Thursday']  ?? 'Kamis',
            'Friday'    => $s['dayName_Friday']    ?? "Jum'at",
            'Saturday'  => $s['dayName_Saturday']  ?? 'Sabtu',
        ],
        'monthName' => [
            'January'   => $s['monthName_January']   ?? 'Januari',
            'February'  => $s['monthName_February']  ?? 'Februari',
            'March'     => $s['monthName_March']     ?? 'Maret',
            'April'     => $s['monthName_April']     ?? 'April',
            'May'       => $s['monthName_May']       ?? 'Mei',
            'June'      => $s['monthName_June']      ?? 'Juni',
            'July'      => $s['monthName_July']      ?? 'Juli',
            'August'    => $s['monthName_August']    ?? 'Agustus',
            'September' => $s['monthName_September'] ?? 'September',
            'October'   => $s['monthName_October']   ?? 'Oktober',
            'November'  => $s['monthName_November']  ?? 'November',
            'December'  => $s['monthName_December']  ?? 'Desember',
        ],
        'timer' => [
            'info'       => $s['timer_info']       ?? '5',
            'wallpaper'  => $s['timer_wallpaper']  ?? '15',
            'wait_adzan' => $s['timer_wait_adzan'] ?? '1',
            'adzan'      => $s['timer_adzan']      ?? '3',
            'sholat'     => $s['timer_sholat']     ?? '20',
        ],
        'iqomah' => [
            'fajr'    => $s['iqomah_fajr']    ?? '10',
            'dhuhr'   => $s['iqomah_dhuhr']   ?? '10',
            'asr'     => $s['iqomah_asr']     ?? '10',
            'maghrib' => $s['iqomah_maghrib'] ?? '10',
            'isha'    => $s['iqomah_isha']    ?? '10',
        ],
        'jumat' => [
            'active'   => $s['jumat_active']   ?? '1',
            'duration' => $s['jumat_duration'] ?? '60',
            'text'     => $s['jumat_text']     ?? 'Harap diam saat khotib khutbah',
        ],
        'tarawih' => [
            'active'   => $s['tarawih_active']   ?? '0',
            'duration' => $s['tarawih_duration'] ?? '180',
        ],
        'info'         => $infoArr,
        'running_text' => $rtArr,
    ];

    return $db;
}

/**
 * Simpan satu setting key-value ke tabel settings (INSERT or UPDATE)
 */
function saveSetting($key, $value) {
    $pdo  = getDbConnection();
    $stmt = $pdo->prepare(
        "INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
         ON DUPLICATE KEY UPDATE setting_value = :v2"
    );
    $stmt->execute([':k' => $key, ':v' => $value, ':v2' => $value]);
}

/**
 * Simpan banyak settings sekaligus (array key => value)
 */
function saveSettings($arr) {
    foreach ($arr as $k => $v) {
        saveSetting($k, $v);
    }
}

/**
 * Rebuild seluruh tabel settings dari array $database (format lama)
 */
function saveDatabaseToMySQL($db) {
    $flat = flattenDatabase($db);
    saveSettings($flat);
}

/**
 * Flatten array database ke key-value flat untuk tabel settings
 */
function flattenDatabase($db) {
    $flat = [];

    // akses
    if (isset($db['akses'])) {
        $flat['akses_user'] = $db['akses']['user'];
        $flat['akses_pass'] = $db['akses']['pass'];
    }
    // setting
    if (isset($db['setting'])) {
        foreach ($db['setting'] as $k => $v) {
            $flat[$k] = $v;
        }
    }
    // prayTimesMethod
    if (isset($db['prayTimesMethod'])) {
        $flat['prayTimesMethod'] = $db['prayTimesMethod'];
    }
    // prayTimesAdjust
    if (isset($db['prayTimesAdjust'])) {
        foreach ($db['prayTimesAdjust'] as $k => $v) {
            $flat['prayTimesAdjust_' . $k] = $v;
        }
    }
    // prayTimesTune
    if (isset($db['prayTimesTune'])) {
        foreach ($db['prayTimesTune'] as $k => $v) {
            $flat['prayTimesTune_' . $k] = $v;
        }
    }
    // prayName
    if (isset($db['prayName'])) {
        foreach ($db['prayName'] as $k => $v) {
            $flat['prayName_' . $k] = $v;
        }
    }
    // timeName
    if (isset($db['timeName'])) {
        foreach ($db['timeName'] as $k => $v) {
            $flat['timeName_' . $k] = $v;
        }
    }
    // dayName
    if (isset($db['dayName'])) {
        foreach ($db['dayName'] as $k => $v) {
            $flat['dayName_' . $k] = $v;
        }
    }
    // monthName
    if (isset($db['monthName'])) {
        foreach ($db['monthName'] as $k => $v) {
            $flat['monthName_' . $k] = $v;
        }
    }
    // timer
    if (isset($db['timer'])) {
        foreach ($db['timer'] as $k => $v) {
            $flat['timer_' . $k] = $v;
        }
    }
    // iqomah
    if (isset($db['iqomah'])) {
        foreach ($db['iqomah'] as $k => $v) {
            $flat['iqomah_' . $k] = $v;
        }
    }
    // jumat
    if (isset($db['jumat'])) {
        foreach ($db['jumat'] as $k => $v) {
            $flat['jumat_' . $k] = $v;
        }
    }
    // tarawih
    if (isset($db['tarawih'])) {
        foreach ($db['tarawih'] as $k => $v) {
            $flat['tarawih_' . $k] = $v;
        }
    }

    return $flat;
}

/**
 * Simpan array info ke tabel info (hapus semua lalu insert ulang)
 */
function saveInfoTable($infoArr) {
    $pdo = getDbConnection();
    $pdo->exec("DELETE FROM info");
    $stmt = $pdo->prepare(
        "INSERT INTO info (header, body, footer, is_active, sort_order)
         VALUES (:header, :body, :footer, :is_active, :sort_order)"
    );
    foreach ($infoArr as $i => $v) {
        $stmt->execute([
            ':header'    => $v[0],
            ':body'      => $v[1],
            ':footer'    => $v[2],
            ':is_active' => $v[3] ? 1 : 0,
            ':sort_order'=> $i,
        ]);
    }
}

/**
 * Simpan array running_text ke tabel running_text (hapus semua lalu insert ulang)
 */
function saveRunningTextTable($rtArr) {
    $pdo = getDbConnection();
    $pdo->exec("DELETE FROM running_text");
    $stmt = $pdo->prepare(
        "INSERT INTO running_text (teks, is_active, sort_order) VALUES (:teks, :is_active, :sort_order)"
    );
    foreach ($rtArr as $i => $v) {
        // $v adalah array [teks, is_active]
        $teks     = is_array($v) ? $v[0] : $v;
        $isActive = is_array($v) ? ($v[1] ? 1 : 0) : 1;
        $stmt->execute([':teks' => $teks, ':is_active' => $isActive, ':sort_order' => $i]);
    }
}
?>
