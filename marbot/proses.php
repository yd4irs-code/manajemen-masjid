<?php

include_once "session.php";
require_once '../db/koneksi.php';

class proses extends fb {

	protected function updateSync() {
		@file_put_contents('../db/last_updated.txt', (string)time());
	}

	protected
		$database	= [];
	public function __construct($id){
		// $this->getDatabase();
		if($id=='login'||$id=='logout'){
			$this->registered=false;
			$this->$id();
		}
		else if($id=='changeDbCheck'){
			$this->getDatabase();
			$this->$id();
		}
		else if($this->verification($id)){
			$this->getDatabase();
			$this->$id();
		}
    }

	private function logout(){
        $_SESSION = array();
        session_destroy();
		$this->registered=false;
		$this->retSuccess();
	}
	
	private function login(){
		$user	= isset($_POST['dt']['user'])?$_POST['dt']['user']:false;
		$pass	= isset($_POST['dt']['pass'])?$_POST['dt']['pass']:false;
		
		if(!$user||!$pass){
			$this->retError("Data tidak valid...");
		} else {
			try {
				$pdo = getDbConnection();
				$stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = :u");
				$stmt->execute([':u' => $user]);
				$userData = $stmt->fetch();
				
				if ($userData && (password_verify($pass, $userData['password']) || $pass === $userData['password'])) {
					$_SESSION["user_id"] = $userData['username'];
					$_SESSION["role"]    = $userData['role'];
					$this->registered=true;
					$this->retSuccess();
				} else {
					$this->retError("Username atau password salah...");
				}
			} catch (Exception $e) {
				$this->retError("Database error: " . $e->getMessage());
			}
		}
	}
	private function resetDevice(){
		if($this->dt=='KONFIRMASI'){
			try {
				$pdo = getDbConnection();
				// Hapus semua data di tabel settings, info, running_text
				$pdo->exec("DELETE FROM settings");
				$pdo->exec("DELETE FROM info");
				$pdo->exec("DELETE FROM running_text");
				// Reset ke data default dengan memanggil getDatabase (akan insert default)
				$this->getDatabase();
				$this->logout();
			} catch (Exception $e) {
				$this->retError('Gagal reset database: ' . $e->getMessage());
			}
		}
		else $this->retError('Not confirm...');
	}
	
	private function getDatabase(){
		try {
			$pdo = getDbConnection();
			// Cek apakah tabel settings sudah ada dan ada data
			$count = $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
			if ($count == 0) {
				// Isi data default jika belum ada
				$this->insertDefaultData($pdo);
			}
			$this->database = loadDatabase();
		} catch (Exception $e) {
			$this->retError('Database error: ' . $e->getMessage());
		}
	}

	private function insertDefaultData($pdo) {
		$default = [
			'akses_user' => 'admin', 'akses_pass' => 'admin',
			'nama' => 'Musholla Ad-Din', 'lokasi' => 'Bekasi',
			'latitude' => '-6.14', 'longitude' => '106.59',
			'timeZone' => '7', 'dst' => '0',
			'prayTimesMethod' => '0',
			'prayTimesAdjust_fajr' => '20', 'prayTimesAdjust_dhuhr' => '',
			'prayTimesAdjust_asr' => 'Standard', 'prayTimesAdjust_maghrib' => '', 'prayTimesAdjust_isha' => '18',
			'prayTimesTune_fajr' => '0', 'prayTimesTune_dhuhr' => '0',
			'prayTimesTune_asr' => '0', 'prayTimesTune_maghrib' => '0', 'prayTimesTune_isha' => '0',
			'prayName_fajr' => 'Subuh', 'prayName_dhuhr' => 'Dzuhur', 'prayName_asr' => 'Ashar',
			'prayName_maghrib' => 'Maghrib', 'prayName_isha' => "Isya'",
			'timeName_Hours' => 'Jam', 'timeName_Minutes' => 'Menit', 'timeName_Seconds' => 'Detik',
			'dayName_Sunday' => 'Minggu', 'dayName_Monday' => 'Senin', 'dayName_Tuesday' => 'Selasa',
			'dayName_Wednesday' => 'Rabu', 'dayName_Thursday' => 'Kamis', 'dayName_Friday' => "Jum'at", 'dayName_Saturday' => 'Sabtu',
			'monthName_January' => 'Januari', 'monthName_February' => 'Februari', 'monthName_March' => 'Maret',
			'monthName_April' => 'April', 'monthName_May' => 'Mei', 'monthName_June' => 'Juni',
			'monthName_July' => 'Juli', 'monthName_August' => 'Agustus', 'monthName_September' => 'September',
			'monthName_October' => 'Oktober', 'monthName_November' => 'November', 'monthName_December' => 'Desember',
			'timer_info' => '5', 'timer_wallpaper' => '15', 'timer_wait_adzan' => '1', 'timer_adzan' => '3', 'timer_sholat' => '20',
			'iqomah_fajr' => '10', 'iqomah_dhuhr' => '10', 'iqomah_asr' => '10', 'iqomah_maghrib' => '10', 'iqomah_isha' => '10',
			'jumat_active' => '1', 'jumat_duration' => '60', 'jumat_text' => 'Harap diam saat khotib khutbah',
			'tarawih_active' => '0', 'tarawih_duration' => '180',
		];
		$stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value=:v2");
		foreach ($default as $k => $v) {
			$stmt->execute([':k' => $k, ':v' => $v, ':v2' => $v]);
		}
		// insert default info
		$pdo->exec("DELETE FROM info");
		$stmtInfo = $pdo->prepare("INSERT INTO info (header, body, footer, is_active, sort_order) VALUES (:h,:b,:f,:a,:s)");
		$defaultInfo = [
			['Aplikasi Manajemen-Masjid', 'Selamat datang di aplikasi Manajemen Masjid', 'Manajemen|Masjid V.1.0', 1, 0],
			['سَوُّوا صُفُوفَكُمْ', 'Luruskanlah shaf-shaf kalian, karena lurusnya shaf adalah kesempurnaan shalat', 'HR. Bukhari no.690, Muslim no.433', 1, 1],
		];
		foreach ($defaultInfo as $r) {
			$stmtInfo->execute([':h'=>$r[0],':b'=>$r[1],':f'=>$r[2],':a'=>$r[3],':s'=>$r[4]]);
		}
		// insert default running_text
		$pdo->exec("DELETE FROM running_text");
		$stmtRt = $pdo->prepare("INSERT INTO running_text (teks, sort_order) VALUES (:t,:s)");
		$stmtRt->execute([':t' => 'Selamat datang di aplikasi Manajemen-Masjid', ':s' => 0]);
	}
	
	
	private function readDatabase(){
		// echo '<pre>'.print_r($this->database,1).'</pre>';
		// $this->info();
		$db		= $this->database;
		unset($db['akses']); // Hapus data sensitif sebelum dikirim ke client
		$this->data = $db;
		$this->retSuccess();
	}
	
	private function changeDbCheck(){
		$db		= $this->database;
		$wp		= $this->getWallpaper();
		$logo	= '../logo/'.$this->getLogo();
		
		$combine	= json_encode($db).json_encode($wp).filesize($logo);
		$this->data = sha1($combine).strlen($combine);//hemat ram... hahaha....
		$this->retSuccess();
	}
	
	private function saveDatabase(){
		try {
			$pdo = getDbConnection();
			$db  = $this->database;
			// Simpan info ke tabel terpisah
			if (isset($db['info'])) {
				saveInfoTable($db['info']);
				unset($db['info']);
			}
			// Simpan running_text ke tabel terpisah
			if (isset($db['running_text'])) {
				saveRunningTextTable($db['running_text']);
				unset($db['running_text']);
			}
			// Simpan sisa ke tabel settings
			saveDatabaseToMySQL($db);
		} catch (Exception $e) {
			$this->retError('Gagal menyimpan ke database: ' . $e->getMessage());
		}
	}
	
	
	private function formSave(){
		$dt		= $this->dt;
		$db		= $this->database;
		
		$id		= $dt['formId'] ?? '';
		$index	= $dt['index'] ?? '';
		if(isset($dt['formId'])) unset($dt['formId']);
		if(isset($dt['index'])) unset($dt['index']);
		if ($id == 'petugas_jumat') {
			$tanggal = $dt['tanggal'];
			$khatib = $dt['khatib'];
			$muadzin = $dt['muadzin'];
			$no_hp = isset($dt['no_hp']) ? $dt['no_hp'] : null;
			try {
				$pdo = getDbConnection();
				if ($index === 'new') {
					$stmt = $pdo->prepare("INSERT INTO petugas_jumat (tanggal, khatib, muadzin, no_hp) VALUES (:t, :k, :m, :h)");
					$stmt->execute([':t'=>$tanggal, ':k'=>$khatib, ':m'=>$muadzin, ':h'=>$no_hp]);
				} else {
					$stmt = $pdo->prepare("UPDATE petugas_jumat SET tanggal=:t, khatib=:k, muadzin=:m, no_hp=:h WHERE id=:id");
					$stmt->execute([':t'=>$tanggal, ':k'=>$khatib, ':m'=>$muadzin, ':h'=>$no_hp, ':id'=>$index]);
				}
			} catch (Exception $e) { $this->retError('Gagal simpan petugas: '.$e->getMessage()); }
			$this->updateSync();
			$this->retSuccess();
			return;
		}

		
		if ($id == 'manajemen_user') {
			$username = $dt['username'];
			$nama_lengkap = $dt['nama_lengkap'];
			$role = $dt['role'];
			try {
				$pdo = getDbConnection();
				if ($index === 'new') {
					$password = password_hash($dt['password'], PASSWORD_BCRYPT);
					$stmt = $pdo->prepare("INSERT INTO users (username, nama_lengkap, password, role) VALUES (:u, :nl, :p, :r)");
					$stmt->execute([':u'=>$username, ':nl'=>$nama_lengkap, ':p'=>$password, ':r'=>$role]);
				} else {
					if (!empty($dt['password'])) {
						$password = password_hash($dt['password'], PASSWORD_BCRYPT);
						$stmt = $pdo->prepare("UPDATE users SET username=:u, nama_lengkap=:nl, password=:p, role=:r WHERE id=:id");
						$stmt->execute([':u'=>$username, ':nl'=>$nama_lengkap, ':p'=>$password, ':r'=>$role, ':id'=>$index]);
					} else {
						$stmt = $pdo->prepare("UPDATE users SET username=:u, nama_lengkap=:nl, role=:r WHERE id=:id");
						$stmt->execute([':u'=>$username, ':nl'=>$nama_lengkap, ':r'=>$role, ':id'=>$index]);
					}
				}
			} catch (Exception $e) { $this->retError('Gagal simpan user: '.$e->getMessage()); }
			$this->retSuccess();
			return;
		}
		
		if ($id == 'saldo_awal') {
			$nominal = $dt['nominal'];
			try {
				$pdo = getDbConnection();
				$cek = $pdo->query("SELECT id FROM settings WHERE setting_key='saldo_awal'")->fetchColumn();
				if ($cek) {
					$stmt = $pdo->prepare("UPDATE settings SET setting_value=:v WHERE setting_key='saldo_awal'");
				} else {
					$stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('saldo_awal', :v)");
				}
				$stmt->execute([':v'=>$nominal]);
			} catch (Exception $e) { $this->retError('Gagal set saldo: '.$e->getMessage()); }
			$this->retSuccess();
			return;
		}

		if ($id == 'keuangan') {
			$tanggal = $dt['tanggal'];
			$jenis = $dt['jenis'];
			$metode = $dt['metode'] ?? 'Tunai';
			$nominal = $dt['nominal'];
			$keterangan = $dt['keterangan'];
			try {
				$pdo = getDbConnection();
				if ($index === 'new') {
					$stmt = $pdo->prepare("INSERT INTO keuangan (tanggal, jenis, metode, nominal, keterangan) VALUES (:t, :j, :m, :n, :k)");
					$stmt->execute([':t'=>$tanggal, ':j'=>$jenis, ':m'=>$metode, ':n'=>$nominal, ':k'=>$keterangan]);
				} else {
					$stmt = $pdo->prepare("UPDATE keuangan SET tanggal=:t, jenis=:j, metode=:m, nominal=:n, keterangan=:k WHERE id=:id");
					$stmt->execute([':t'=>$tanggal, ':j'=>$jenis, ':m'=>$metode, ':n'=>$nominal, ':k'=>$keterangan, ':id'=>$index]);
				}
			} catch (Exception $e) { $this->retError('Gagal simpan keuangan: '.$e->getMessage()); }
			$this->retSuccess();
			return;
		}

		if ($id == 'import_csv_keuangan') {
			if ($_SESSION['role'] !== 'admin') $this->retError('Akses ditolak: Hanya Admin yang dapat mengimpor data.');
			if (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] == UPLOAD_ERR_OK) {
				$fileTmpPath = $_FILES['file_csv']['tmp_name'];
				$file = fopen($fileTmpPath, 'r');
				
				// Deteksi delimiter (; atau ,)
				$firstLine = fgets($file);
				$delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
				rewind($file);
				
				// Lewati baris pertama (header)
				fgetcsv($file, 1000, $delimiter);
				
				try {
					$pdo = getDbConnection();
					$stmt = $pdo->prepare("INSERT INTO keuangan (tanggal, jenis, metode, nominal, keterangan) VALUES (:t, :j, :m, :n, :k)");
					
					while (($row = fgetcsv($file, 1000, $delimiter)) !== false) {
						// Format: TANGGAL (DD/MM/YYYY), KETERANGAN, PENERIMAAN, PENGELUARAN
						if (count($row) < 3) continue;
						
						// Tanggal conversion
						$dateObj = DateTime::createFromFormat('d/m/Y', trim($row[0]));
						if (!$dateObj) continue; // Skip jika format tanggal salah
						$tanggal = $dateObj->format('Y-m-d');
						
						$keterangan = trim($row[1]);
						$penerimaan = isset($row[2]) ? (float)preg_replace('/[^0-9]/', '', $row[2]) : 0;
						$pengeluaran = isset($row[3]) ? (float)preg_replace('/[^0-9]/', '', $row[3]) : 0;
						
						if ($penerimaan > 0) {
							$stmt->execute([':t'=>$tanggal, ':j'=>'pemasukan', ':m'=>'Tunai', ':n'=>$penerimaan, ':k'=>$keterangan]);
						} elseif ($pengeluaran > 0) {
							$stmt->execute([':t'=>$tanggal, ':j'=>'pengeluaran', ':m'=>'Tunai', ':n'=>$pengeluaran, ':k'=>$keterangan]);
						}
					}
					fclose($file);
					$this->retSuccess();
					return;
				} catch (Exception $e) {
					fclose($file);
					$this->retError('Gagal import CSV: '.$e->getMessage());
				}
			} else {
				$this->retError('Berkas CSV gagal diunggah.');
			}
		}

		if ($id == 'toggle_info_status') {
			try {
				$pdo = getDbConnection();
				$ids = $pdo->query("SELECT id FROM info ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
				if (isset($ids[$index])) {
					$stmt = $pdo->prepare("UPDATE info SET is_active = NOT is_active WHERE id = :id");
					$stmt->execute([':id'=>$ids[$index]]);
				}
			} catch (Exception $e) { $this->retError('Gagal update status: '.$e->getMessage()); }
			$this->retSuccess();
			return;
		}

		if ($id == 'info') {
			// Simpan langsung ke tabel info (insert/update)
			$header = $dt['r1'];
			$body   = $dt['r2'];
			$footer = $dt['r3'];
			$active = $dt['active'] ? 1 : 0;
			try {
				$pdo = getDbConnection();
				if ($index === 'new') {
					$maxOrder = $pdo->query("SELECT MAX(sort_order) FROM info")->fetchColumn();
					$stmt = $pdo->prepare("INSERT INTO info (header, body, footer, is_active, sort_order) VALUES (:h,:b,:f,:a,:s)");
					$stmt->execute([':h'=>$header,':b'=>$body,':f'=>$footer,':a'=>$active,':s'=>($maxOrder+1)]);
				} else {
					// Update baris ke-$index (0-based -> ambil id dari sort_order)
					$ids = $pdo->query("SELECT id FROM info ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
					if (isset($ids[$index])) {
						$stmt = $pdo->prepare("UPDATE info SET header=:h, body=:b, footer=:f, is_active=:a WHERE id=:id");
						$stmt->execute([':h'=>$header,':b'=>$body,':f'=>$footer,':a'=>$active,':id'=>$ids[$index]]);
					}
				}
			} catch (Exception $e) { $this->retError('Gagal simpan info: '.$e->getMessage()); }
			$this->retSuccess();
			return;
		}
		
		if ($id == 'toggle_rt_status') {
			try {
				$pdo = getDbConnection();
				$ids = $pdo->query("SELECT id FROM running_text ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
				if (isset($ids[$index])) {
					$stmt = $pdo->prepare("UPDATE running_text SET is_active = NOT is_active WHERE id = :id");
					$stmt->execute([':id'=>$ids[$index]]);
				}
			} catch (Exception $e) { $this->retError('Gagal update status rt: '.$e->getMessage()); }
			$this->retSuccess();
			return;
		}

		if ($id == 'running_text') {
			$teks = $dt['text'];
			$active = isset($dt['active']) ? ($dt['active'] ? 1 : 0) : 1;
			try {
				$pdo = getDbConnection();
				if ($index === 'new') {
					$maxOrder = $pdo->query("SELECT MAX(sort_order) FROM running_text")->fetchColumn();
					$stmt = $pdo->prepare("INSERT INTO running_text (teks, is_active, sort_order) VALUES (:t, :a, :s)");
					$stmt->execute([':t'=>$teks, ':a'=>$active, ':s'=>($maxOrder+1)]);
				} else {
					$ids = $pdo->query("SELECT id FROM running_text ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
					if (isset($ids[$index])) {
						$stmt = $pdo->prepare("UPDATE running_text SET teks=:t, is_active=:a WHERE id=:id");
						$stmt->execute([':t'=>$teks, ':a'=>$active, ':id'=>$ids[$index]]);
					}
				}
			} catch (Exception $e) { $this->retError('Gagal simpan running_text: '.$e->getMessage()); }
			$this->retSuccess();
			return;
		}
		
		if ($id == 'prayTimesAdjust') {
			$db['prayTimesMethod'] = $dt['prayTimesMethod'];
			unset($dt['prayTimesMethod']);
		}
		
		if ($id == 'gantiPass') {
			try {
				$pdo = getDbConnection();
				// Ambil username login dari session
				$currentUser = $_SESSION['user_id'] ?? '';
				
				$stmt = $pdo->prepare("SELECT password FROM users WHERE username = :u");
				$stmt->execute([':u' => $currentUser]);
				$curPass = $stmt->fetchColumn();
				
				if (!password_verify($dt['password_lama'], $curPass))
					$this->retError('Password lama salah...');
				else if ($dt['password_baru'] != $dt['ulangi_password_baru'])
					$this->retError('Password baru tidak sama...');
				else if (strlen($dt['password_baru']) < 8)
					$this->retError('Password terlalu pendek, minimal 8 karakter...');
				else {
					$stmtUpdate = $pdo->prepare("UPDATE users SET password = :p WHERE username = :u");
					$stmtUpdate->execute([':p' => $dt['password_baru'], ':u' => $currentUser]);
					$this->retSuccess();
					return;
				}
			} catch (Exception $e) {
				$this->retError("Database error: " . $e->getMessage());
			}
		}
		
		if ($index == 'no-index')
			$db[$id] = array_merge($db[$id], $dt);
		else if ($index == 'new')
			$db[$id][] = $dt;
		else
			$db[$id][$index] = $dt;
		
		$this->database = array_merge($this->database, $db);
		$this->saveDatabase();
		$this->retSuccess();
	}
	
	private function saveWallpaper(){
		// $this->data	= $_FILES;
		
		if(isset($_FILES)){
			$allowed_ext =  array('jpg');
			$i=0;
			foreach($_FILES as $file){
				if($file['size']>0){
					$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
					if(!in_array($ext,$allowed_ext) ) {
						$this->retError($file['name']." tidak didukung\nExt yang diperbolehkan : ".implode(", ",$allowed_ext));
					}
					else {
						move_uploaded_file($file['tmp_name'], "../wallpaper/".time().$i.'.'.$ext);
					}
				}
				$i++;
			}
		}
		
		
		$this->retSuccess();
	}
	
	private function saveLogo(){
		if(isset($_FILES)){
			$allowed_ext =  array('png');
			$i=0;
			foreach($_FILES as $file){
				if($file['size']>0){
					$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
					if(!in_array($ext,$allowed_ext) ) {
						$this->retError($file['name']." tidak didukung\nExt yang diperbolehkan : ".implode(", ",$allowed_ext));
					}
					else {
						$oldLogo	= '../logo/'.$this->getLogo();
						if(file_exists($oldLogo)) unlink($oldLogo);
						move_uploaded_file($file['tmp_name'], "../logo/".time().'.'.$ext);
					}
				}
				$i++;
			}
		}
		$this->retSuccess();
	}
	
	private function getWallpaper(){
		$dir	= '../wallpaper/';
		$files	= array_diff(scandir($dir),array('.','..','Thumbs.db'));
		return $files;
		
	}
	private function getLogo(){
		$dir	= '../logo/';
		$files	= array_diff(scandir($dir),array('.','..','Thumbs.db'));
		$files	= array_values($files);//re index
		return $files[0];
		
	}
	private function wallpaperDelete(){
		if(count($this->getWallpaper())<2){
			$this->retError('minimal harus ada 1 wallpaper');
		}
		else{
			$dir	= '../wallpaper/';
			$file	= $this->dt;
			// $this->retError($file);die;
			if(file_exists($dir.$file)) unlink($dir.$file);
			$this->retSuccess();
		}
	}
	private function formDelete(){
		$dt    = $this->dt;
		$db    = $this->database;
		$id    = $dt['formId'];
		$index = $dt['index'];
		if ($id == 'petugas_jumat') {
			try {
				$pdo = getDbConnection();
				$stmt = $pdo->prepare("DELETE FROM petugas_jumat WHERE id=:id");
				$stmt->execute([':id' => $index]);
			} catch (Exception $e) { $this->retError('Gagal hapus petugas: '.$e->getMessage()); }
			$this->updateSync();
			$this->retSuccess();
			return;
		}

		
		if ($id == 'manajemen_user') {
			try {
				$pdo = getDbConnection();
				$stmt = $pdo->prepare("DELETE FROM users WHERE id=:id");
				$stmt->execute([':id' => $index]);
			} catch (Exception $e) { $this->retError('Gagal hapus user: '.$e->getMessage()); }
			$this->retSuccess();
			return;
		}
		
		if ($id == 'keuangan') {
			try {
				$pdo = getDbConnection();
				$stmt = $pdo->prepare("DELETE FROM keuangan WHERE id=:id");
				$stmt->execute([':id' => $index]);
			} catch (Exception $e) { $this->retError('Gagal hapus keuangan: '.$e->getMessage()); }
			$this->retSuccess();
			return;
		}

		if ($id == 'keuangan_all') {
			if ($_SESSION['role'] !== 'admin') $this->retError('Akses ditolak: Hanya Admin yang dapat mengosongkan data.');
			try {
				$pdo = getDbConnection();
				$pdo->exec("TRUNCATE TABLE keuangan");
				// Reset saldo awal ke 0 juga
				$pdo->exec("UPDATE settings SET setting_value='0' WHERE setting_key='saldo_awal'");
			} catch (Exception $e) { $this->retError('Gagal mengosongkan data: '.$e->getMessage()); }
			$this->retSuccess();
			return;
		}

		if ($id == 'info') {
			if (count($db['info']) < 2) {
				$this->retError("Minimal harus ada 1 data...");
			} else {
				try {
					$pdo = getDbConnection();
					$ids = $pdo->query("SELECT id FROM info ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
					if (isset($ids[$index])) {
						$stmt = $pdo->prepare("DELETE FROM info WHERE id=:id");
						$stmt->execute([':id' => $ids[$index]]);
					}
				} catch (Exception $e) { $this->retError('Gagal hapus info: '.$e->getMessage()); }
				$this->retSuccess();
			}
			return;
		}
		
		if ($id == 'running_text') {
			if (count($db['running_text']) < 2) {
				$this->retError("Minimal harus ada 1 data...");
			} else {
				try {
					$pdo = getDbConnection();
					$ids = $pdo->query("SELECT id FROM running_text ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
					if (isset($ids[$index])) {
						$stmt = $pdo->prepare("DELETE FROM running_text WHERE id=:id");
						$stmt->execute([':id' => $ids[$index]]);
					}
				} catch (Exception $e) { $this->retError('Gagal hapus running_text: '.$e->getMessage()); }
				$this->retSuccess();
			}
			return;
		}
		
		// Untuk data selain info/running_text
		if (count($db[$id]) < 2) {
			$this->retError("Minimal harus ada 1 data...");
		} else {
			unset($db[$id][$index]);
			$db[$id] = array_values($db[$id]);
			$this->database = $db;
			$this->saveDatabase();
			$this->retSuccess();
		}
	}
	
	
		
	/* *****************************************************************************************************************
	 * *** VIEW MANAJEMEN USER & KEUANGAN
	 * *****************************************************************************************************************/
	
	private function manajemen_user() {
		if ($_SESSION['role'] !== 'admin') $this->retError("Akses ditolak");
		ob_start();
		try {
			$pdo = getDbConnection();
			$users = $pdo->query("SELECT * FROM users ORDER BY role ASC, username ASC")->fetchAll();
		} catch (Exception $e) { $users = []; }
		
		$arrRole = ['Admin' => 'admin', 'Marbot' => 'marbot', 'Bendahara' => 'bendahara'];
		
		?>
		<section class="content-header content-dynamic">
			<div class="row">
				<div class="col-md-12 col-sm-12 col-xs-12">
					<div class="box box-info">
						<div class="box-header with-border">
							<h3 class="box-title">Manajemen User</h3>
							<div class="box-tools pull-right">
								<button type="button" class="btn btn-sm btn-primary" onclick="openModalUser('new', '', 'marbot')"><i class="fa fa-plus"></i> Tambah User</button>
							</div>
						</div>
						<div class="box-body table-responsive">
							<table class="table table-bordered table-striped dataTable">
								<thead>
									<tr>
										<th>No</th>
										<th>Username</th>
										<th>Nama Lengkap</th>
										<th>Role</th>
										<th>Aksi</th>
									</tr>
								</thead>
								<tbody>
									<?php 
									$no = 1;
									foreach($users as $v): ?>
									<tr>
										<td><?=$no++?></td>
										<td><?=$v['username']?></td>
										<td><?=$v['nama_lengkap']?></td>
										<td><span class="label label-<?= $v['role']=='admin'?'danger':($v['role']=='marbot'?'success':'warning') ?>"><?=strtoupper($v['role'])?></span></td>
										<td>
											<button class="btn btn-sm btn-warning" onclick="openModalUser('<?=$v['id']?>', '<?=$v['username']?>', '<?=$v['nama_lengkap']?>', '<?=$v['role']?>')"><i class="fa fa-pencil"></i> Edit</button>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Modal -->
		<div class="modal fade" id="modalUser" tabindex="-1" role="dialog">
		  <div class="modal-dialog" role="document">
			<form method="post" class="form">
			<div class="modal-content">
			  <div class="modal-header">
				<h4 class="modal-title" id="modalUserTitle">Tambah/Edit User</h4>
			  </div>
			  <div class="modal-body">
					<div class="input-group" style="margin-bottom:10px;">
					  <span class="input-group-addon">Username</span>
					  <input name="username" type="text" maxlength="50" class="form-control" required>
					</div>
					<div class="input-group" style="margin-bottom:10px;">
					  <span class="input-group-addon">Nama Lengkap</span>
					  <input name="nama_lengkap" type="text" maxlength="100" class="form-control" required>
					</div>
					<div class="input-group" style="margin-bottom:10px;">
					  <span class="input-group-addon">Password</span>
					  <input name="password" type="password" maxlength="50" class="form-control">
					</div>
					<div class="input-group" style="margin-bottom:10px;">
					  <span class="input-group-addon">Role</span>
					  <select name="role" class="form-control input-sm" required>
						<?php foreach($arrRole as $ka => $va): ?>
							<option value="<?=$va?>"><?=$ka?></option>
						<?php endforeach; ?>
					  </select>
					</div>
					<div class="form-group hidden">
						<input type="hidden" name="formId" value="manajemen_user">
						<input type="hidden" name="index" value="">
						<input type="hidden" id="role-hidden" name="role" value="" disabled>
					</div>
			  </div>
			  <div class="modal-footer">
				<button type="button" id="btnDeleteUser" class="btn btn-danger delete pull-left"><i class="fa fa-trash"></i> Hapus</button>
				<button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
				<button type="submit" class="btn btn-primary"><i class="fa fa-floppy-o"></i> Simpan</button>
			  </div>
			</div>
			</form>
		  </div>
		</div>

		<script>
		function openModalUser(id, username, nama_lengkap, role) {
			$('#modalUserTitle').text(id === 'new' ? 'Tambah User Baru' : 'Edit User: ' + username);
			$('#modalUser [name="index"]').val(id);
			$('#modalUser [name="username"]').val(username).prop('readonly', username === 'admin');
			$('#modalUser [name="nama_lengkap"]').val(nama_lengkap);
			$('#modalUser [name="password"]').val('').prop('required', id === 'new');
			$('#modalUser [name="password"]').attr('placeholder', id === 'new' ? 'Wajib diisi' : 'Kosongkan jika tidak diganti');
			
			if (username === 'admin') {
				$('#modalUser select[name="role"]').val(role).prop('disabled', true);
				$('#role-hidden').val('admin').prop('disabled', false); // Tetap kirim admin meski select disabled
			} else {
				$('#modalUser select[name="role"]').val(role).prop('disabled', false);
				$('#role-hidden').prop('disabled', true);
			}
			
			if (id !== 'new' && username !== 'admin') {
				$('#btnDeleteUser').show();
			} else {
				$('#btnDeleteUser').hide();
			}
			
			$('#modalUser').modal('show');
		}
		
		// Initialize datatable if available
		if ($.fn.DataTable) {
			$('.dataTable').DataTable({
				"language": { "search": "Cari:", "lengthMenu": "Tampil _MENU_ data", "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ data" }
			});
		}
		</script>
		<?php
		$this->data = ob_get_clean();
		$this->retSuccess();
	}
	
	private function keuangan() {
		if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'bendahara' && $_SESSION['role'] !== 'marbot') $this->retError("Akses ditolak");
		ob_start();
		try {
			$pdo = getDbConnection();
			$keuangan = $pdo->query("SELECT * FROM keuangan ORDER BY tanggal DESC, id DESC")->fetchAll();
			
			$sAwalRaw = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='saldo_awal'")->fetchColumn();
			$sAwal = $sAwalRaw ? (float)$sAwalRaw : 0;
			
			$qMasuk = $pdo->query("SELECT SUM(nominal) FROM keuangan WHERE jenis='pemasukan'")->fetchColumn();
			$qKeluar = $pdo->query("SELECT SUM(nominal) FROM keuangan WHERE jenis='pengeluaran'")->fetchColumn();
			$saldo = $sAwal + $qMasuk - $qKeluar;
		} catch (Exception $e) { 
			$keuangan = []; 
			$sAwal = 0;
			$saldo = 0;
		}
		
		$arrJenis = ['Pemasukan' => 'pemasukan', 'Pengeluaran' => 'pengeluaran'];
		$arrMetode = ['Tunai', 'Transfer', 'Kotak Amal', 'QRIS'];
		
		?>
		<section class="content-header content-dynamic">
			<div class="row">
				<div class="col-md-12 col-sm-12 col-xs-12">
					<div class="alert alert-info" style="display:flex; justify-content:space-between; align-items:center; padding: 10px 15px;">
						<h4 style="margin:0;"><i class="fa fa-money"></i> Saldo Kas Saat Ini: Rp <?=number_format((float)$saldo, 0, ',', '.')?></h4>
						<button type="button" class="btn btn-sm btn-default text-black" onclick="openModalSaldoAwal(<?=$sAwal?>)"><i class="fa fa-pencil"></i> Atur Saldo Awal</button>
					</div>
					
					<div class="box box-success">
						<div class="box-header with-border">
							<h3 class="box-title">Buku Kas Keuangan</h3>
							<div class="box-tools pull-right">
								<?php if ($_SESSION['role'] === 'admin'): ?>
								<button type="button" class="btn btn-sm btn-danger" onclick="resetKeuangan()"><i class="fa fa-trash"></i> Kosongkan Data</button>
								<button type="button" class="btn btn-sm btn-success" onclick="$('#modalImportCsv').modal('show')"><i class="fa fa-file-excel-o"></i> Import CSV</button>
								<?php endif; ?>
								<button type="button" class="btn btn-sm btn-info" onclick="openModalCetak()"><i class="fa fa-print"></i> Cetak Laporan</button>
								<button type="button" class="btn btn-sm btn-primary" onclick="openModalKeuangan('new', '<?=date('Y-m-d')?>', 'pemasukan', 'Tunai', '', '')"><i class="fa fa-plus"></i> Tambah Transaksi</button>
							</div>
						</div>
						<div class="box-body table-responsive">
							<table class="table table-bordered table-striped dataTable">
								<thead>
									<tr>
										<th style="width: 50px;">No</th>
										<th>Tanggal</th>
										<th>Jenis</th>
										<th>Metode</th>
										<th>Keterangan</th>
										<th class="text-right">Nominal (Rp)</th>
										<th style="min-width:80px;">Aksi</th>
									</tr>
								</thead>
								<tbody>
									<?php 
									$no = 1;
									foreach($keuangan as $v): ?>
									<tr>
										<td><?=$no++?></td>
										<td><span style="display:none;"><?=$v['tanggal']?></span><?=date('d-m-Y', strtotime($v['tanggal']))?></td>
										<td><span class="label label-<?= $v['jenis']=='pemasukan'?'success':'danger' ?>"><?=strtoupper($v['jenis'])?></span></td>
										<td><?=$v['metode']?></td>
										<td style="white-space: pre-wrap; word-wrap: break-word; max-width: 250px;"><?=htmlspecialchars($v['keterangan'])?></td>
										<td class="text-right"><?=number_format($v['nominal'], 0, ',', '.')?></td>
										<td>
											<button class="btn btn-sm btn-warning" onclick='openModalKeuangan("<?=$v['id']?>", "<?=$v['tanggal']?>", "<?=$v['jenis']?>", "<?=$v['metode']?>", "<?=$v['nominal']?>", <?=htmlspecialchars(json_encode($v['keterangan']), ENT_QUOTES)?>)'><i class="fa fa-pencil"></i> Edit</button>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Modal Import CSV -->
		<div class="modal fade" id="modalImportCsv" tabindex="-1" role="dialog">
		  <div class="modal-dialog" role="document">
			<form method="post" id="formImportCsv" enctype="multipart/form-data">
			<div class="modal-content">
			  <div class="modal-header">
				<h4 class="modal-title">Import Data Keuangan CSV</h4>
			  </div>
			  <div class="modal-body">
					<p class="text-muted"><small>Unggah file berformat CSV (pemisah koma atau titik koma) dengan format urutan kolom: <strong>TANGGAL (DD/MM/YYYY), KETERANGAN, PENERIMAAN, PENGELUARAN</strong>.<br>Baris pertama (header) akan diabaikan secara otomatis.</small></p>
					<div class="form-group">
					  <label>File CSV</label>
					  <input name="file_csv" type="file" class="form-control" accept=".csv" required>
					</div>
					<div class="form-group hidden">
						<input type="hidden" name="formId" value="import_csv_keuangan">
						<input type="hidden" name="index" value="new">
					</div>
			  </div>
			  <div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
				<button type="submit" class="btn btn-success"><i class="fa fa-upload"></i> Proses Import</button>
			  </div>
			</div>
			</form>
		  </div>
		</div>

		<!-- Modal -->
		<div class="modal fade" id="modalKeuangan" tabindex="-1" role="dialog">
		  <div class="modal-dialog" role="document">
			<form method="post" class="form">
			<div class="modal-content">
			  <div class="modal-header">
				<h4 class="modal-title" id="modalKeuanganTitle">Tambah/Edit Transaksi</h4>
			  </div>
			  <div class="modal-body">
					<div class="input-group" style="margin-bottom:10px;">
					  <span class="input-group-addon">Tanggal</span>
					  <input name="tanggal" type="date" class="form-control" required>
					</div>
					<div class="input-group" style="margin-bottom:10px;">
					  <span class="input-group-addon">Jenis</span>
					  <select name="jenis" class="form-control input-sm" required>
						<?php foreach($arrJenis as $ka => $va): ?>
							<option value="<?=$va?>"><?=$ka?></option>
						<?php endforeach; ?>
					  </select>
					</div>
					<div class="input-group" style="margin-bottom:10px;">
					  <span class="input-group-addon">Metode</span>
					  <select name="metode" class="form-control input-sm" required>
						<?php foreach($arrMetode as $va): ?>
							<option value="<?=$va?>"><?=$va?></option>
						<?php endforeach; ?>
					  </select>
					</div>
					<div class="input-group" style="margin-bottom:10px;">
					  <span class="input-group-addon">Nominal (Rp)</span>
					  <input name="nominal" type="number" min="0" class="form-control" required>
					</div>
					<div class="input" style="margin-bottom:10px;">
					  <textarea name="keterangan" maxlength="255" rows="2" class="form-control" placeholder="Keterangan transaksi" required></textarea>
					</div>
					<div class="form-group hidden">
						<input type="hidden" name="formId" value="keuangan">
						<input type="hidden" name="index" value="">
					</div>
			  </div>
			  <div class="modal-footer">
				<button type="button" id="btnDeleteKeuangan" class="btn btn-danger delete pull-left"><i class="fa fa-trash"></i> Hapus</button>
				<button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
				<button type="submit" class="btn btn-primary"><i class="fa fa-floppy-o"></i> Simpan</button>
			  </div>
			</div>
			</form>
		  </div>
		  </div>
		</div>

		<!-- Modal Saldo Awal -->
		<div class="modal fade" id="modalSaldoAwal" tabindex="-1" role="dialog">
		  <div class="modal-dialog modal-sm" role="document">
			<form method="post" class="form">
			<div class="modal-content">
			  <div class="modal-header">
				<h4 class="modal-title">Atur Saldo Awal</h4>
			  </div>
			  <div class="modal-body">
					<p class="text-muted" style="margin-top:0;"><small>Saldo awal ini akan ditambahkan ke perhitungan seluruh transaksi kas berjalan.</small></p>
					<div class="input-group" style="margin-bottom:10px;">
					  <span class="input-group-addon">Rp</span>
					  <input name="nominal" type="number" min="0" class="form-control" required>
					</div>
					<div class="form-group hidden">
						<input type="hidden" name="formId" value="saldo_awal">
						<input type="hidden" name="index" value="new">
					</div>
			  </div>
			  <div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
				<button type="submit" class="btn btn-primary"><i class="fa fa-floppy-o"></i> Simpan</button>
			  </div>
			</div>
			</form>
		  </div>
		</div>

		<!-- Modal Cetak Laporan -->
		<div class="modal fade" id="modalCetak" tabindex="-1" role="dialog">
		  <div class="modal-dialog" role="document">
			<form method="get" action="cetak_laporan.php" target="_blank" class="form-cetak">
			<div class="modal-content">
			  <div class="modal-header">
				<h4 class="modal-title">Cetak Laporan Keuangan</h4>
			  </div>
			  <div class="modal-body">
					<div class="input-group" style="margin-bottom:10px;">
					  <span class="input-group-addon">Periode</span>
					  <select name="periode" id="cetakPeriode" class="form-control" onchange="toggleBulanTahun()" required>
						<option value="jumat">Jum'at (Jumat lalu s/d Hari ini)</option>
						<option value="bulanan">Bulanan</option>
						<option value="semua">Seluruh Transaksi</option>
					  </select>
					</div>
					<div id="wrapBulanTahun" style="display:none;">
						<div class="input-group" style="margin-bottom:10px;">
						  <span class="input-group-addon">Bulan</span>
						  <select name="bulan" class="form-control">
							<?php 
							$namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
							for($i=1; $i<=12; $i++) {
								$sel = ($i == date('n')) ? 'selected' : '';
								echo "<option value='$i' $sel>{$namaBulan[$i]}</option>";
							}
							?>
						  </select>
						</div>
						<div class="input-group" style="margin-bottom:10px;">
						  <span class="input-group-addon">Tahun</span>
						  <select name="tahun" class="form-control">
							<?php 
							for($i=date('Y'); $i>=2020; $i--) {
								echo "<option value='$i'>$i</option>";
							}
							?>
						  </select>
						</div>
					</div>
					<!-- Timestamp unik agar browser tidak cache halaman cetak -->
				<input type="hidden" name="_t" id="cetakTimestamp" value="">
		  </div><!-- /.modal-body -->
		  <div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
				<button type="submit" class="btn btn-primary" onclick="$('#modalCetak').modal('hide')"><i class="fa fa-print"></i> Cetak</button>
			  </div>
			</div>
			</form>
		  </div>
		</div>

		<script>
		function openModalSaldoAwal(nominal) {
			$('#modalSaldoAwal [name="nominal"]').val(nominal);
			$('#modalSaldoAwal').modal('show');
		}
		function openModalCetak() {
			$('#modalCetak').modal('show');
		}
		// Isi timestamp saat tombol Cetak diklik agar URL selalu unik (anti cache)
		$('.form-cetak').on('submit', function() {
			$('#cetakTimestamp').val(Date.now());
		});
		function toggleBulanTahun() {
			if ($('#cetakPeriode').val() === 'bulanan') {
				$('#wrapBulanTahun').show();
			} else {
				$('#wrapBulanTahun').hide();
			}
		}
		function openModalKeuangan(id, tanggal, jenis, metode, nominal, keterangan) {
			$('#modalKeuanganTitle').text(id === 'new' ? 'Tambah Transaksi Baru' : 'Edit Transaksi');
			$('#modalKeuangan [name="index"]').val(id);
			$('#modalKeuangan [name="tanggal"]').val(tanggal);
			$('#modalKeuangan [name="jenis"]').val(jenis);
			$('#modalKeuangan [name="metode"]').val(metode);
			$('#modalKeuangan [name="nominal"]').val(nominal);
			$('#modalKeuangan [name="keterangan"]').val(keterangan);
			
			if (id !== 'new') {
				$('#btnDeleteKeuangan').show();
			} else {
				$('#btnDeleteKeuangan').hide();
			}
			
			$('#modalKeuangan').modal('show');
		}

		$('#formImportCsv').submit(function(e){
			e.preventDefault();
			var formData = new FormData(this);
			formData.append('id', 'formSave');
			// formData sudah otomatis memuat input file_csv, formId, index
			
			// Ubah struktur sedikit agar sesuai dengan harapan backend ($dt['formId'])
			// Backend meminta $id = $dt['formId']. Karena $_FILES tidak masuk di $dt,
			// kita perlu menyesuaikan backend atau menyesuaikan cara kirim.
			// Karena $dt di-serialize di handler lama, backend membaca $dt['formId'].
			// Kita kirimkan saja dt[formId]
			formData.append('dt[formId]', 'import_csv_keuangan');
			
			$.ajax({
				url: 'proses.php',
				type: 'post',
				dataType: 'json',
				data: formData,
				processData: false,
				contentType: false
			}).done(function(dt) {
				if(dt.success) {
					$('#modalImportCsv').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();
					setTimeout(function(){
						$('.sidebar-menu .active a').trigger("click");
					}, 200);
				} else {
					alert(dt.data);
				}
			}).fail(function(msg){
				alert("Terjadi kesalahan koneksi saat unggah CSV.");
			});
		});

		function resetKeuangan() {
			if (confirm("PERINGATAN: Apakah Anda yakin ingin MENGHAPUS SELURUH DATA transaksi keuangan?\n\nTindakan ini tidak dapat dibatalkan dan saldo akan diatur ulang!")) {
				$.ajax({
					url: 'proses.php',
					type: 'post',
					dataType: 'json',
					data: { id: 'formDelete', dt: { formId: 'keuangan_all', index: '' } }
				}).done(function(dt) {
					if(dt.success) {
						$('.sidebar-menu .active a').trigger("click");
					} else {
						alert(dt.data);
					}
				}).fail(function(){
					alert("Koneksi gagal.");
				});
			}
		}
		
		if ($.fn.DataTable) {
			$('.dataTable').DataTable({
				"language": { "search": "Cari:", "lengthMenu": "Tampil _MENU_ data", "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ data" },
				"order": [[1, "desc"]]
			});
		}
		</script>
		<?php
		$this->data = ob_get_clean();
		$this->retSuccess();
	}
	
	/* *****************************************************************************************************************
	 * *** VIEW
	 * *****************************************************************************************************************/
	
	private function info(){
		$db	= $this->database;
		$id	= 'info';
		ob_start();
		$arrActive = ['Ya' => 1, 'Tidak' => 0];
		
		?>
		<section class="content-header content-dynamic">
			<div class="row">
				<div class="col-md-12 col-sm-12 col-xs-12">
					<div class="box box-info">
						<div class="box-header with-border">
							<h3 class="box-title">Informasi Layar</h3>
							<div class="box-tools pull-right">
								<button type="button" class="btn btn-sm btn-primary" onclick="openModalInfo('new', '', '', '', 1)"><i class="fa fa-plus"></i> Tambah Informasi</button>
							</div>
						</div>
						<div class="box-body table-responsive">
							<table class="table table-bordered table-striped dataTable">
								<thead>
									<tr>
										<th style="width: 50px;">No</th>
										<th style="width: 20%;">Header</th>
										<th>Isi Informasi</th>
										<th style="width: 15%;">Footer</th>
										<th style="width: 80px;">Status</th>
										<th style="width: 80px;">Aksi</th>
									</tr>
								</thead>
								<tbody>
									<?php 
									$no = 1;
									foreach($db[$id] as $k => $v): ?>
									<tr>
										<td><?=$no++?></td>
										<td><?=htmlspecialchars($v[0])?></td>
										<td style="white-space: pre-wrap; word-wrap: break-word; max-width: 300px;"><?=htmlspecialchars($v[1])?></td>
										<td><?=htmlspecialchars($v[2])?></td>
										<td>
											<a href="javascript:void(0)" onclick="toggleInfoStatus(<?=$k?>)" style="text-decoration:none;">
												<span class="label label-<?= $v[3]?'success':'danger' ?>" style="cursor:pointer;"><?= $v[3]?'AKTIF':'TIDAK' ?></span>
											</a>
										</td>
										<td>
											<button class="btn btn-sm btn-warning" onclick='openModalInfo("<?=$k?>", <?=htmlspecialchars(json_encode($v[0]), ENT_QUOTES)?>, <?=htmlspecialchars(json_encode($v[1]), ENT_QUOTES)?>, <?=htmlspecialchars(json_encode($v[2]), ENT_QUOTES)?>, <?=$v[3]?1:0?>)'><i class="fa fa-pencil"></i> Edit</button>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Modal -->
		<div class="modal fade" id="modalInfo" tabindex="-1" role="dialog">
		  <div class="modal-dialog" role="document">
			<form method="post" class="form">
			<div class="modal-content">
			  <div class="modal-header">
				<h4 class="modal-title" id="modalInfoTitle">Tambah/Edit Info</h4>
			  </div>
			  <div class="modal-body">
					<div class="input-group" style="margin-bottom:10px;">
					  <span class="input-group-addon">Header</span>
					  <input name="r1" type="text" maxlength="100" class="form-control" required>
					</div>
					<div class="input" style="margin-bottom:10px;">
					  <textarea name="r2" maxlength="255" rows="4" class="form-control" placeholder="Teks Informasi (maks. 255 karakter)" required></textarea>
					</div>
					<div class="input-group" style="margin-bottom:10px;">
					  <span class="input-group-addon">Footer</span>
					  <input name="r3" type="text" maxlength="100" class="form-control" placeholder="Boleh dikosongkan">
					</div>
					<div class="input-group" style="margin-bottom:10px;">
					  <span class="input-group-addon">Aktif</span>
					  <select name="active" class="form-control input-sm" required>
						<?php foreach($arrActive as $ka => $va): ?>
							<option value="<?=$va?>"><?=$ka?></option>
						<?php endforeach; ?>
					  </select>
					</div>
					<div class="form-group hidden">
						<input type="hidden" name="formId" value="info">
						<input type="hidden" name="index" value="">
					</div>
			  </div>
			  <div class="modal-footer">
				<button type="button" id="btnDeleteInfo" class="btn btn-danger delete pull-left"><i class="fa fa-trash"></i> Hapus</button>
				<button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
				<button type="submit" class="btn btn-primary"><i class="fa fa-floppy-o"></i> Simpan</button>
			  </div>
			</div>
			</form>
		  </div>
		</div>

		<script>
		function openModalInfo(id, header, body, footer, active) {
			$('#modalInfoTitle').text(id === 'new' ? 'Tambah Info Baru' : 'Edit Info');
			$('#modalInfo [name="index"]').val(id);
			$('#modalInfo [name="r1"]').val(header);
			$('#modalInfo [name="r2"]').val(body);
			$('#modalInfo [name="r3"]').val(footer);
			$('#modalInfo [name="active"]').val(active);
			
			if (id !== 'new') {
				$('#btnDeleteInfo').show();
			} else {
				$('#btnDeleteInfo').hide();
			}
			
			$('#modalInfo').modal('show');
		}

		function toggleInfoStatus(index) {
			$.ajax({
				type: "POST",
				url: "proses.php",
				dataType: "json",
				data: { id: 'formSave', dt: { formId: 'toggle_info_status', index: index } }
			}).done(function(dt) {
				if(dt.success) {
					$('.sidebar-menu .active a').trigger("click");
				} else {
					alert(dt.data);
				}
			}).fail(function(msg){
				alert("Terjadi kesalahan koneksi.");
			});
		}
		
		if ($.fn.DataTable) {
			$('.dataTable').DataTable({
				"language": { "search": "Cari:", "lengthMenu": "Tampil _MENU_ data", "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ data" }
			});
		}
		</script>
		<?php
		$this->data = ob_get_clean();
		$this->retSuccess();
	}
	private function wallpaper(){
		$db	= $this->database;
		$id	= 'wallpaper';
		$wp	= $this->getWallpaper();
		ob_start();
		echo '
			<section class="content-header content-dynamic section-wallpaper">
			<div class="row">
		';
		// echo '<pre>'.print_r($wp,1).'</pre>';
		?>
		<div class="col-md-12 col-sm-12 col-xs-12">
			<form method="post" class="form-file" enctype="multipart/form-data">
			<div class="box box-info">
				<div class="box-header with-border">
					<h3 class="box-title">Tambah wallpaper</h3>
					<div class="box-tools pull-right">
						<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
					</div>
				</div>
				<div class="box-body">
					<div class="input-group">
					  <span class="input-group-addon">File wallpaper</span>
					  <input type="file" multiple="" class="form-control input-sm" placeholder="" data-proses="saveWallpaper">
					</div>
					<div class="input">
						<small>
						- Ext file yang didukung :  <b>.jpg</b><br>
						- Ukuran maksimal <b>2Mb</b><br>
						- Maksimal 5 file dalam sekali upload<br>
						- Tips : Jika ukuran gambar > 2Mb, cara cepat kompres gambar ⇒ kirim ke whatsapp :P
						</small>
					</div>
				</div>
				<div class="box-footer">
					<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-upload" aria-hidden="true"></i> upload</button>
				</div>
			</div>
			</form>
		</div>
		<?php
		foreach($wp as $v):
		?>
		<div class="col-md-4 col-sm-6 col-xs-12">
          <div class="small-box" style="background-image: url(../wallpaper/<?=$v?>);">
            <div class="inner"></div>
            <a href="javascript:void(0)" data-file="<?=$v?>" class="small-box-footer"><i class="fa fa-trash"></i> delete</a>
          </div>
        </div>
		<?php 
		endforeach;
		echo '</div></section>';
		$this->data = ob_get_clean();
		$this->retSuccess();
		// echo $my_var;
	}
	
	private function running_text(){
		$db	= $this->database;
		$id	= 'running_text';
		ob_start();
		$arrActive = ['Ya' => 1, 'Tidak' => 0];
		?>
		<section class="content-header content-dynamic">
			<div class="row">
				<div class="col-md-12 col-sm-12 col-xs-12">
					<div class="box box-info">
						<div class="box-header with-border">
							<h3 class="box-title">Running Text</h3>
							<div class="box-tools pull-right">
								<button type="button" class="btn btn-sm btn-primary" onclick="openModalRt('new', '', 1)"><i class="fa fa-plus"></i> Tambah Teks</button>
							</div>
						</div>
						<div class="box-body table-responsive">
							<table class="table table-bordered table-striped dataTable">
								<thead>
									<tr>
										<th style="width: 50px;">No</th>
										<th>Teks Berjalan</th>
										<th style="width: 80px;">Status</th>
										<th style="width: 80px;">Aksi</th>
									</tr>
								</thead>
								<tbody>
									<?php 
									$no = 1;
									foreach($db[$id] as $k => $v): 
										// Kompatibilitas mundur jika array format lama
										$teks = is_array($v) ? $v[0] : $v;
										$aktif = is_array($v) ? $v[1] : true;
									?>
									<tr>
										<td><?=$no++?></td>
										<td style="white-space: pre-wrap; word-wrap: break-word; max-width: 400px;"><?=htmlspecialchars($teks)?></td>
										<td>
											<a href="javascript:void(0)" onclick="toggleRtStatus('<?=$k?>')" style="text-decoration:none;">
												<span class="label label-<?= $aktif?'success':'danger' ?>" style="cursor:pointer;"><?= $aktif?'AKTIF':'TIDAK' ?></span>
											</a>
										</td>
										<td>
											<button class="btn btn-sm btn-warning" onclick='openModalRt("<?=$k?>", <?=htmlspecialchars(json_encode($teks), ENT_QUOTES)?>, <?=$aktif?1:0?>)'><i class="fa fa-pencil"></i> Edit</button>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Modal -->
		<div class="modal fade" id="modalRt" tabindex="-1" role="dialog">
		  <div class="modal-dialog" role="document">
			<form method="post" class="form">
			<div class="modal-content">
			  <div class="modal-header">
				<h4 class="modal-title" id="modalRtTitle">Tambah/Edit Running Text</h4>
			  </div>
			  <div class="modal-body">
					<div class="input" style="margin-bottom:10px;">
					  <textarea name="text" maxlength="255" rows="4" class="form-control" placeholder="Teks Berjalan (maks. 255 karakter)" required></textarea>
					</div>
					<div class="input-group" style="margin-bottom:10px;">
					  <span class="input-group-addon">Aktif</span>
					  <select name="active" class="form-control input-sm" required>
						<?php foreach($arrActive as $ka => $va): ?>
							<option value="<?=$va?>"><?=$ka?></option>
						<?php endforeach; ?>
					  </select>
					</div>
					<div class="form-group hidden">
						<input type="hidden" name="formId" value="running_text">
						<input type="hidden" name="index" value="">
					</div>
			  </div>
			  <div class="modal-footer">
				<button type="button" id="btnDeleteRt" class="btn btn-danger delete pull-left"><i class="fa fa-trash"></i> Hapus</button>
				<button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
				<button type="submit" class="btn btn-primary"><i class="fa fa-floppy-o"></i> Simpan</button>
			  </div>
			</div>
			</form>
		  </div>
		</div>

		<script>
		function openModalRt(id, teks, active) {
			$('#modalRtTitle').text(id === 'new' ? 'Tambah Teks Baru' : 'Edit Teks');
			$('#modalRt [name="index"]').val(id);
			$('#modalRt [name="text"]').val(teks);
			$('#modalRt [name="active"]').val(active);
			
			if (id !== 'new') {
				$('#btnDeleteRt').show();
			} else {
				$('#btnDeleteRt').hide();
			}
			
			$('#modalRt').modal('show');
		}

		function toggleRtStatus(index) {
			$.ajax({
				type: "POST",
				url: "proses.php",
				dataType: "json",
				data: { id: 'formSave', dt: { formId: 'toggle_rt_status', index: index } }
			}).done(function(dt) {
				if(dt.success) {
					$('.sidebar-menu .active a').trigger("click");
				} else {
					alert(dt.data);
				}
			}).fail(function(msg){
				alert("Terjadi kesalahan koneksi.");
			});
		}
		
		if ($.fn.DataTable) {
			$('.dataTable').DataTable({
				"language": { "search": "Cari:", "lengthMenu": "Tampil _MENU_ data", "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ data" }
			});
		}
		</script>
		<?php
		$this->data = ob_get_clean();
		$this->retSuccess();
	}
	
	private function timer(){
		$db	= $this->database;
		$id	= 'timer';
		
		ob_start();
		echo '
			<section class="content-header content-dynamic">
			<div class="row">
			<div class="col-md-12 col-sm-12 col-xs-12">
		';
		
		//timer
		$timer		= $db['timer'];
		$formTimer	= [];
		foreach($timer as $k => $v){
			$formTimer[$k]	= [
				'type'	=> 'number',
				'min'	=> 1,
				'max'	=> 180,
				'step'	=> 1,
				'value'	=> $v,
				'placeholder'	=> '1-180',
				'required'	=> true,
				'addon'	=> 'menit'
			];
			if($k=='info'||$k=='wallpaper'){
				$formTimer[$k]['max']			= 86400;
				$formTimer[$k]['addon']			= 'detik';
				$formTimer[$k]['placeholder']	= '1-86400';
			}
		}
		$setTimer	= [
			'id'=>'timer',
			'title'=>'Timer'
		];
		echo $this->generateCompleteForm($formTimer,$setTimer);
		
		//form iqomah
		$iqomah		= $db['iqomah'];
		$formIqomah	= [];
		foreach($iqomah as $k => $v){
			$formIqomah[$k]	= [
				'type'	=> 'number',
				'min'	=> 1,
				'max'	=> 180,
				'step'	=> 1,
				'value'	=> $v,
				'required'	=> true,
				'placeholder'	=> '1-180',
				'addon'	=> 'menit'
			];
		}
		$setIqomah	= [
			'id'=>'iqomah',
			'title'=>'Timer Iqomah'
		];
		echo $this->generateCompleteForm($formIqomah,$setIqomah);
		

		echo '</div></div></section>';
		$this->data .= ob_get_clean();
		$this->retSuccess();
		
	}
	
	private function jadwal(){
		$db		= $this->database;
		$method	= $db['prayTimesMethod'];
		$adjust	= $db['prayTimesAdjust'];
		
		$arrMethod	= [
			'0'			=> 'Manual parameter',
			'MWL'		=> 'Muslim World League',
			'ISNA'		=> 'Islamic Society of North America',
			'Egypt'		=> 'Egyptian General Authority of Survey',
			'Makkah'	=> 'Umm al-Qura University, Makkah',
			'Karachi'	=> 'University of Islamic Sciences, Karachi',
			'Tehran'	=> 'Institute of Geophysics, University of Tehran',
			'Jafari'	=> 'Shia Ithna Ashari (Ja`fari)'
		];
		ob_start();
		echo '
			<section class="content-header content-dynamic">
			<div class="row">
			<div class="col-md-12 col-sm-12 col-xs-12">
		';
		?>
			<div class="nav-tabs-custom">
				<ul class="nav nav-tabs pull-right">
				  <li><a href="#info" data-toggle="tab"><i class="fa fa-info-circle"></i></a></li>
				  <li><a href="#parameter" data-toggle="tab">Parameter</a></li>
				  <li class="active"><a href="#metode" data-toggle="tab">Metode</a></li>
				  <li class="pull-left header"><i class="fa fa-inbox"></i>Library</li>
				</ul>
				<div class="tab-content">
				  <!-- Morris chart - Sales -->
				  <div class="tab-pane" id="info" >
					Perhitungan waktu sholat menggunakan library dari <a href="http://praytimes.org/" target="_blank">praytimes.org</a>, Untuk manual lebih detail bisa di cek pada halaman situs tersebut.<br>
					Library yang dipakai <b>PrayTimes Version 2.3</b> (versi terbaru pada saat aplikasi ini dibuat)<br><br>
					Untuk mempermudah, setting parameter yang bisa di ganti hanya <b>fajr, dhuhr, asr, maghrib, isha</b> menyesuaikan tampilan pada display. Jika parameter tidak perlu diganti kosongkan saja (diisi default)
					
					<br><br>
					Contoh penggunaan untuk kota bekasi mengkuti metode kemenag bekasi :
					<pre>
latitude	= -6.14
longitude	= 106.59
timeZone	= 7 (GMT +7)
fajr		= 20°
asr		= Standard (Shafii, Maliki, Jafari and Hanbali / shadow factor = 1)
isha		= 18°
					</pre>
					<small>Default aplikasi ini menggunakan setting <b>bekasi - jawa barat - indonesia</b> dengan metode seperti diatas</small>
				  </div>
				  <div class="tab-pane" id="parameter">
					<h4>Parameters</h4>
					<table class="table table-condensed">
						<thead>
							<tr>
								<th>Parameter
								</th>
								<th>Values
								</th>
								<th>Description
								</th>
								<th>Sample Value
								</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td> fajr </td>
								<td> degrees </td>
								<td> twilight angle </td>
								<td> 15
								</td>
							</tr>
							<tr>
								<td> dhuhr </td>
								<td> minutes </td>
								<td> minutes after mid-day </td>
								<td> 1 min
								</td>
							</tr>
							<tr>
								<td rowspan="2"> asr
								</td>
								<td> method </td>
								<td> asr juristic method; see the table below </td>
								<td> Standard
								</td>
							</tr>
							<tr>
								<td> factor </td>
								<td> shadow length factor for realizing asr </td>
								<td> 1.7
								</td>
							</tr>
							<tr>
								<td rowspan="2"> maghrib
								</td>
								<td> degrees </td>
								<td> twilight angle </td>
								<td> 4
								</td>
							</tr>
							<tr>
								<td> minutes </td>
								<td> minutes after sunset </td>
								<td> 15 min
								</td>
							</tr>
							<tr>
								<td rowspan="2"> isha
								</td>
								<td> degrees </td>
								<td> twilight angle </td>
								<td> 18
								</td>
							</tr>
							<tr>
								<td> minutes </td>
								<td> minutes after maghrib </td>
								<td> 90 min
								</td>
							</tr>
						</tbody>
					</table>
					
					<h4>Asr methods</h4>
					<table class="table table-condensed table-striped">
						<thead>
							<tr>
								<th>Method
								</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td> Standard </td>
								<td> Shafii, Maliki, Jafari and Hanbali (shadow factor = 1)
								</td>
							</tr>
							<tr>
								<td> Hanafi </td>
								<td> Hanafi school of tought (shadow factor = 2)
								</td>
							</tr>
						</tbody>
					</table>
					
					<b>Contoh penggunaan:</b><br>
					- Asr menggunakan metode <i>Shafii, Maliki, Jafari and Hanbali</i>, maka diisi : <b>Standard</b><br>
					- Asr menggunakan metode <i>Hanafi school of tought</i>, maka diisi : <b>Hanafi</b><br>
					- Asr menggunakan <i>shadow factor 1.5</i>, maka diisi : <b>1.5</b><br>
					- Isha menggunakan <i>twilight angle (18.5 deg)</i>, maka diisi : <b>18.5</b><br>
					- Isha menggunakan <i>85 minutes after maghrib</i>, maka diisi : <b>85 min</b> <a style="color:#00F">(85 spasi min)</a><br>
					- dst...
					
					
				  </div>
				  <div class="tab-pane active" id="metode" >
					<h4>Calculation Methods</h4>
					<table class="table table-condensed table-striped">
						<thead>
							<tr>
								<th>Method
								</th>
								<th>Abbr.
								</th>
								<th>Region Used
								</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td> Muslim World League </td>
								<td> MWL </td>
								<td> Europe, Far East, parts of US
								</td>
							</tr>
							<tr>
								<td> Islamic Society of North America </td>
								<td> ISNA </td>
								<td> North America (US and Canada)
								</td>
							</tr>
							<tr>
								<td> Egyptian General Authority of Survey </td>
								<td> Egypt </td>
								<td> Africa, Syria, Lebanon, Malaysia
								</td>
							</tr>
							<tr>
								<td> Umm al-Qura University, Makkah </td>
								<td> Makkah </td>
								<td> Arabian Peninsula
								</td>
							</tr>
							<tr>
								<td> University of Islamic Sciences, Karachi </td>
								<td> Karachi &nbsp; </td>
								<td> Pakistan, Afganistan, Bangladesh, India
								</td>
							</tr>
							<tr>
								<td> Institute of Geophysics, University of Tehran </td>
								<td> Tehran </td>
								<td> Iran, Some Shia communities
								</td>
							</tr>
							<tr>
								<td> Shia Ithna Ashari, Leva Research Institute, Qum &nbsp; </td>
								<td> Jafari </td>
								<td> Some Shia communities worldwide
								</td>
							</tr>
						</tbody>
					</table>
					
					<h4>Calculating Parameters</h4>
					<table class="table table-condensed table-striped">
						<thead>
							<tr>
								<th>Method &nbsp;
								</th>
								<th>Fajr Angle
								</th>
								<th>Isha
								</th>
								<th>Maghrib
								</th>
								<th>Midnight
								</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td> MWL </td>
								<td> 18° </td>
								<td> 17° </td>
								<td> = Senset </td>
								<td> mid Sunset to Sunrise
								</td>
							</tr>
							<tr>
								<td> ISNA </td>
								<td> 15° </td>
								<td> 15° </td>
								<td> = Senset </td>
								<td> mid Sunset to Sunrise
								</td>
							</tr>
							<tr>
								<td> Egypt </td>
								<td> 19.5° </td>
								<td> 17.5° </td>
								<td> = Senset </td>
								<td> mid Sunset to Sunrise
								</td>
							</tr>
							<tr>
								<td> Makkah </td>
								<td> 18.5° </td>
								<td> 90 min after Maghrib
									<br>120 min during Ramadan </td>
								<td> = Senset </td>
								<td> mid Sunset to Sunrise
								</td>
							</tr>
							<tr>
								<td> Karachi </td>
								<td> 18° </td>
								<td> 18° </td>
								<td> = Senset </td>
								<td> mid Sunset to Sunrise
								</td>
							</tr>
							<tr>
								<td> Tehran </td>
								<td> 17.7° </td>
								<td> 14° </td>
								<td> 4.5° </td>
								<td> mid Sunset to Fajr
								</td>
							</tr>
							<tr>
								<td> Jafari </td>
								<td> 16° </td>
								<td> 14° </td>
								<td> 4° </td>
								<td> mid Sunset to Fajr
								</td>
							</tr>
						</tbody>
					</table>
					
				  </div>
				</div>
			</div>
				
			<form method="post" class="form">
			<div class="box box-warning">
				<div class="box-header with-border">
					<h3 class="box-title">Metode</h3>
					<div class="box-tools pull-right">
						<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
					</div>
				</div>
				<div class="box-body">
					<div class="input">
						<select class="form-control" name="prayTimesMethod" id="prayTimesMethod">
							<?=$this->generateOptionSelect($method,$arrMethod,false)?>
						</select>
					</div>
					<div id="prayTimesAdjust" style="display:none">
						<?=$this->formPrayTimesAdjust($adjust) ?>
						<div class="form-group">
							<small>
								- Lihat manual parameter (diatas) untuk cara pengisian.<br>
								- Parameter <b>case sensitive</b> (contoh : <b>Standard</b> tidak sama dengan <b>standard</b>)<br>
								- Jika dikosongkan maka akan diisi default.
							</small>
							<input type="hidden" name="formId" value="prayTimesAdjust">
							<input type="hidden" name="index" value="no-index">
						</div>
					</div>
				</div>
				<div class="box-footer">
					<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-floppy-o" aria-hidden="true"></i> simpan</button>
				</div>
			</div>
			</form>
		<?php
		
		$seting	= $db['setting'];
		$location 	= [
			'latitude'	=> [
				'type'	=> 'number',
				'min'	=> -999.0000001,
				'max'	=> 999.9999999,
				'step'	=> 0.0000001,
				'value'	=> $seting['latitude'],
				'required'	=> true,
				'addon'	=> '°'
			],
			'longitude'	=> [
				'type'	=> 'number',
				'min'	=> -999.0000001,
				'max'	=> 999.9999999,
				'step'	=> 0.0000001,
				'value'	=> $seting['longitude'],
				'required'	=> true,
				'addon'	=> '°'
			],
			'timeZone'	=> [
				'type'	=> 'number',
				'min'	=> -11,
				'max'	=> 12,
				'step'	=> 1,
				'value'	=> $seting['timeZone'],
				'required'	=> true,
				'placeholder'	=> 'GMT-11 to GMT+12',	
				'addon'	=> 'GMT'
			],
			'dst'		=> [
				'type'	=> 'select',
				'arr'	=> ['0'=>'0','1'=>'1','Auto'=>'auto'],
				'value'	=> $seting['dst'],
				'required'	=> true
			],
		];
		$set = [
			'id'	=> 'setting',
			'title'	=> 'Lokasi',
			'info'	=> '<b>DST</b> = Daylight Saving Time (Waktu Musim Panas)
						Waktu resmi dimajukan (biasanya) satu jam lebih awal dari zona waktu standar dan diberlakukan selama musim semi dan musim panas (berlaku untuk wilayah eropa)
						Untuk wilayah indonesia isi 0.
			'
		];
		echo $this->generateCompleteForm($location,$set);
		
		$tune	= $db['prayTimesTune'];
		$tune_	= [];
		foreach($tune as $k=>$v){
			$tune_[$k]	= [
				'type'	=> 'number',
				'min'	=> -60,
				'max'	=> 60,
				'step'	=> 1,
				'value'	=> $v,
				'required'	=> true,
				'placeholder'	=> '-60 to 60',	
				'addon'	=> 'menit'
			];
		}
		$set = [
			'id'	=> 'prayTimesTune',
			'title'	=> 'Penyesuaian waktu sholat',
			'info'	=> '- Untuk menyesuaikan waktu sholat -60 sampai +60 menit.
						- Contoh penggunaan : jadwal ditambahkan +2 menit untuk ihtiyati (pengaman)
			'
		];
		echo $this->generateCompleteForm($tune_,$set);
		
		
		echo '</div></div></section>';
		$this->data = ob_get_clean();
		$this->retSuccess();
		
	}
	
	
	private function pengaturan(){
		$db	= $this->database;
		ob_start();
		echo '
			<section class="content-header content-dynamic">
			<div class="row">
			<div class="col-md-12 col-sm-12 col-xs-12">
		';
		?>
		<form method="post" class="form-file" enctype="multipart/form-data">
		<div class="box box-success ">
			<div class="box-header with-border">
				<h3 class="box-title">Logo</h3>
				<div class="box-tools pull-right">
					<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
				</div>
			</div>
			<div class="box-body" style="background-image: url(../dist/img/bgTransparent.jpg);">
				<img class="img-responsive pad" src="../logo/<?=$this->getLogo();?>" style="border:2px dashed #F00;padding:0">
			</div>
			<div class="box-body">
				<div class="input-group">
				  <span class="input-group-addon">File logo</span>
				  <input type="file" class="form-control input-sm" placeholder="" data-proses="saveLogo">
				</div>
				<div class="input">
					<small>
					- Ext file yang didukung :  <b>.png</b><br>
					- Ukuran maksimal <b>2Mb</b><br>
					- Tips : jika logo tampil terlalu besar pada display, edit gambar pada image editor (contoh : photoshop) dan beri jarak kosong pada atas-bawah atau kiri-kanan gambar
					</small>
				</div>
			</div>
			<div class="box-footer">
				<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-upload" aria-hidden="true"></i> upload</button>
			</div>
		</div>
		</form>
		<?php
		
		$setting 	= $db['setting'];
		unset($setting['latitude']);
		unset($setting['longitude']);
		unset($setting['timeZone']);
		unset($setting['dst']);
		$setSetting = [
			'id'	=> 'setting',
			'title'	=> 'Detail masjid/musholla',
			'color'	=> 'box-success',
			'info'	=> '- Data ini opsional (bisa dikosongkan)
			',
			'open'	=> false
		];
		echo $this->generateTextForm($setting,$setSetting,false);
		
		$dataPass 	= [
			'password lama'	=> [
				'name'		=> 'password_lama',
				'type'		=> 'password',
				'maxlength'	=> 20,
				'required'	=> true
			],
			'password baru'	=> [
				'name'		=> 'password_baru',
				'type'		=> 'password',
				'maxlength'	=> 20,
				'required'	=> true
			],
			'ulangi password'	=> [
				'name'		=> 'ulangi_password_baru',
				'type'		=> 'password',
				'maxlength'	=> 20,
				'required'	=> true
			],
		];
		
		$setPass = [
			'id'	=> 'gantiPass',
			'title'	=> 'Ganti password admin',
			'color'	=> 'box-danger',
			'info'	=> '- Password default : <b>admin</b>
						- Jangan mengganti password dengan \'admin\'
						- Tips : gunakan campuran angka dan huruf untuk memperkuat password.
			',
			'open'	=> false
		];
		echo $this->generateCompleteForm($dataPass,$setPass);
		
		$prayName 	= $db['prayName'];
		$set = [
			'id'	=> 'prayName',
			'title'	=> 'Nama sholat',
			'open'	=> false
		];
		echo $this->generateTextForm($prayName,$set);
		
		$timeName 	= $db['timeName'];
		$set = [
			'id'	=> 'timeName',
			'title'	=> 'Nama waktu',
			'open'	=> false
		];
		echo $this->generateTextForm($timeName,$set);
		
		$dayName 	= $db['dayName'];
		$set = [
			'id'	=> 'dayName',
			'title'	=> 'Nama hari',
			'open'	=> false
		];
		echo $this->generateTextForm($dayName,$set);
		
		$monthName 	= $db['monthName'];
		$set = [
			'id'	=> 'monthName',
			'title'	=> 'Nama bulan',
			'open'	=> false
		];
		echo $this->generateTextForm($monthName,$set);
		
		echo '</div></div></section>';
		$this->data = ob_get_clean();
		$this->retSuccess();
		// echo $my_var;
	}
	
	private function simulasi(){
		ob_start();
		echo '
			<section class="content-header content-dynamic">
			<div class="row">
			<div class="col-md-12 col-sm-12 col-xs-12">
		';
		?>
		<div class="box box-info">
			<div class="box-header with-border">
				<h3 class="box-title">Simulasi jadwal sholat</h3>
				<div class="box-tools pull-right">
					<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
				</div>
			</div>
			<div class="box-body">
				<div class="row date-navigation month-picker" style="text-align:center">
					<button class="btn btn-info  prev"><i class="fa fa-long-arrow-left" aria-hidden="true"></i> Prev</button>
					<input style="width:120px" type="text" class="btn picker btn-info" value="Hari ini" readonly>
					<button class="btn btn-info next">Next <i class="fa fa-long-arrow-right" aria-hidden="true"></i></button>
				</div>
				<div class="table-responsive">
				</div>
			</div>
		</div>
		<?php
		echo '</div></div></section>';
		$this->data = ob_get_clean();
		$this->retSuccess();
	}
	private function getPraySetting(){
		$db	= $this->database;
		$this->data['setting']			= $db['setting'];
		$this->data['prayTimesMethod']	= $db['prayTimesMethod'];
		
		$prayTimesAdjust	= [];
		foreach($db['prayTimesAdjust'] as $k => $v){
			if(strlen(trim($v))>0) $prayTimesAdjust[$k]=$v;
		}
		$this->data['prayTimesAdjust']	= $prayTimesAdjust;
		
		$prayTimesTune		= [];
		foreach($db['prayTimesTune'] as $k => $v){
			if($v<0 || $v>0) $prayTimesTune[$k]=$v;
		}
		
		$this->data['prayTimesTune']	= $prayTimesTune;
		$this->data['items']			= array_keys($db['prayName']);
		$this->data['thead']			= array_values($db['prayName']);
		array_unshift($this->data['thead'], 'Tgl');
		
		
		$this->retSuccess();
	}
	private function about(){
		ob_start();
		?>
		<section class="content-header content-dynamic">
			<div class="row">
				<div class="col-md-6">
				  <!-- Widget: user widget style 1 -->
				  <div class="box box-widget widget-user-2">
					<!-- Add the bg color to the header using any of the bg-* classes -->
					<div class="widget-user-header bg-aqua-active">
					  <div class="widget-user-image">
						<div style="width:65px;height:65px;background:#563eae;position:absolute;border-radius:60px 35px 0 35px;font-size:38px;padding:15px 10px;box-shadow:3px 3px 10px 0 rgba(0,0,0,0.4);overflow:hidden;transform: rotate(-135deg); color:#00a7d0">
						dm
						</div>
					  </div>
					  <!-- /.widget-user-image -->
					  <h3 class="widget-user-username">Manajemen|Masjid</h3>
					  <h5 class="widget-user-desc">Media informasi untuk masjid/musholla</h5>
					</div>
					<div class="box-footer no-padding" style="overflow:hidden">
					  <ul class="nav nav-stacked">
						<li>
							<a class="row">
								<div class="col-xs-5" style="text-align:right">Version</div>
								<div class="col-xs-7"><span class="badge bg-blue">1.0.0</span></div>
							</a>
						</li>
						<li>
							<a class="row">
								<div class="col-xs-5" style="text-align:right">Date</div>
								<div class="col-xs-7"><span class="badge bg-aqua">Feb 2020</span></div>
							</a>
						</li>
						<li>
							<a class="row">
								<div class="col-xs-5" style="text-align:right">Program</div>
								<div class="col-xs-7">Tim Masjid</div>
							</a>
						</li>
						<li>
							<a class="row">
								<div class="col-xs-5" style="text-align:right">Display design</div>
								<div class="col-xs-7">Tim Desain</div>
							</a>
						</li>
						<li>
							<a class="row">
								<div class="col-xs-5" style="text-align:right">License</div>
								<div class="col-xs-7">Berbayar, sangat mahal sekali.... :P</div>
							</a>
						</li>
					  </ul>
					</div>
				  </div>
				  <!-- /.widget-user -->
			</div>
		</div></section>
		<?php
		$this->data = ob_get_clean();
		$this->retSuccess();
	}
	private function generateCompleteForm($arr,$setting=[]){
		$default = [
			'id'	=> '',
			'title'	=> '',
			'color'	=> 'box-info',
			'index'	=> 'no-index',
			'info'	=> false,
			'open'	=> true
		];
		$set	= array_merge($default,$setting);
		$icon	= $set['open']?'fa-minus':'fa-plus';
		$class	= $set['color'].($set['open']?'':' collapsed-box');
		$form	= '
			<form method="post" class="form">
			<div class="box '.$class.'">
				<div class="box-header with-border">
					<h3 class="box-title">'.$set['title'].'</h3>
					<div class="box-tools pull-right">
						<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa '.$icon.'"></i></button>
					</div>
				</div>
				<div class="box-body">';
					foreach($arr as $k => $v){
						$required	= '';
						if(array_key_exists('required', $v)){
							$required	= $v['required']?' required':'';
							unset($v['required']);
						}
						if($v['type']=='select'){
							$reverse	= true;
							if(array_key_exists('rev', $v)){
								$reverse	= $v['rev'];
							}
							$form	.= '
							<div class="input-group">
								<span class="input-group-addon">'.$k.'</span>
								<select class="form-control"';
									$form	.= array_key_exists('name', $v)?' name="'.$v['name'].'"':' name="'.$k.'"';
									$form	.= $required.'>'.$this->generateOptionSelect($v['value'],$v['arr'],$reverse).
								'</select>'.
							'</div>';
						}
						else{
							$form	.= '
							<div class="input-group">
								<span class="input-group-addon">'.$k.'</span>
								<input class="form-control"';
								$addon	= '';
								if(!array_key_exists('name', $v))$form.=' name="'.$k.'"';
								foreach($v as $kr => $vr){
									if($kr=='required')$form	.= " required";
									else if($kr=='addon') $addon = '<span class="input-group-addon">'.$vr.'</span>';
									else $form	.= " $kr=\"$vr\"";
								}
								$form	.= $required.'>'.$addon.
							'</div>';
						}
					}
					$form .='
					<div class="input">
						'.($set['info']?'<small>'.nl2br($set['info']).'</small>':'').'
						<input type="hidden" name="formId" value="'.$set['id'].'">
						<input type="hidden" name="index" value="'.$set['index'].'">
					</div>
				</div>
				<div class="box-footer">
					<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-floppy-o" aria-hidden="true"></i> simpan</button>
				</div>
			</div>
			</form>
		';
		return $form;
	}
	
	private function generateTextForm($arr,$setting=[],$required=true){
		$form	= [];
		foreach($arr as $k => $v){
			$form[$k]	= [
				'type'		=> 'text',
				'maxlength'	=> 100,
				'value'		=> $v,
				'required'	=> $required
			];
		};
		return $this->generateCompleteForm($form,$setting);
	}
	
	private function formPrayTimesAdjust($arr,$required=false){
		$form	= '';
		$req	= $required?'required':'';
		foreach($arr as $k => $v){
			$form	.= '
			<div class="input-group">
				<span class="input-group-addon">'.$k.'</span>
				<input 
					name	="'.$k.'" 
					type	="text" 
					class	="form-control" 
					maxlength	="100" 
					value	="'.$v.'"
					'.$req.'>
			</div>
			';
		}
		return $form;
	}
	
	private function generateOptionSelect($selected,$arr,$reverse=true){
		$opt	= '';
		foreach($arr as $k => $v){
			if($reverse){
				$sel	= $v==$selected?'selected':'';
				$opt	.= "<option value=\"$v\" $sel>$k</option>";
			}
			else{
				$sel	= $k==$selected?'selected':'';
				$opt	.= "<option value=\"$k\" $sel>$v</option>";
			}
		}
		return $opt;
	}
	
	
	
	
	/*
	private function generateForm($arr){
		$form	= '';
		foreach($arr as $k => $v){
			$form	.= '<div class="input-group"><span class="input-group-addon">'.$k.'</span><input class	="form-control" name="'.$k.'"';
			$addon	= '';
			foreach($v as $kr => $vr){
				if($kr=='required')$form	.= " required";
				else if($kr=='addon') $addon = '<span class="input-group-addon">'.$vr.'</span>';
				else $form	.= " $kr=\"$vr\"";
			}
			$form	.= '>'.$addon.'</div>';
		}
		return $form;
	}
	*/
	
	
	

	private function petugas_jumat() {
		if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'marbot') $this->retError("Akses ditolak");
		ob_start();
		try {
			$pdo = getDbConnection();
			$petugas = $pdo->query("SELECT * FROM petugas_jumat ORDER BY tanggal DESC, id DESC")->fetchAll();
		} catch (Exception $e) { 
			$petugas = []; 
		}
		
		?>
		<section class="content-header content-dynamic">
			<div class="row">
				<div class="col-md-12 col-sm-12 col-xs-12">
					<div class="box box-success">
						<div class="box-header with-border">
							<h3 class="box-title">Jadwal Petugas Sholat Jum'at</h3>
							<div class="box-tools pull-right">
								<a href="cetak_jumat.php" target="_blank" class="btn btn-sm btn-default"><i class="fa fa-print"></i> Cetak Jadwal</a>
								<button type="button" class="btn btn-sm btn-primary" onclick="openModalPetugas('new', '', '', '')"><i class="fa fa-plus"></i> Tambah Jadwal</button>
							</div>
						</div>
						<div class="box-body table-responsive">
							<table class="table table-bordered table-striped dataTable">
								<thead>
									<tr>
										<th style="width: 50px;">No</th>
										<th>Tanggal Jum'at</th>
										<th>Khatib / Imam</th>
										<th>No. HP Khatib</th>
										<th>Muadzin / Bilal</th>
										<th style="min-width:80px;">Aksi</th>
									</tr>
								</thead>
								<tbody>
									<?php 
									$no = 1;
									foreach($petugas as $v): ?>
									<tr>
										<td><?=$no++?></td>
										<td><span style="display:none;"><?=$v['tanggal']?></span><?=date('d-m-Y', strtotime($v['tanggal']))?></td>
										<td><?=htmlspecialchars($v['khatib'])?></td>
										<td><?=htmlspecialchars($v['no_hp'])?></td>
										<td><?=htmlspecialchars($v['muadzin'])?></td>
										<td>
											<button class="btn btn-sm btn-warning" onclick='openModalPetugas("<?=$v['id']?>", "<?=$v['tanggal']?>", <?=htmlspecialchars(json_encode($v['khatib']), ENT_QUOTES)?>, <?=htmlspecialchars(json_encode($v['muadzin']), ENT_QUOTES)?>, <?=htmlspecialchars(json_encode($v['no_hp'] ?? ""), ENT_QUOTES)?>)'><i class="fa fa-pencil"></i> Edit</button>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Modal -->
		<div class="modal fade" id="modalPetugas" tabindex="-1" role="dialog">
		  <div class="modal-dialog" role="document">
			<form method="post" class="form">
			<div class="modal-content">
			  <div class="modal-header">
				<h4 class="modal-title" id="modalPetugasTitle">Tambah/Edit Petugas</h4>
			  </div>
			  <div class="modal-body">
					<p class="text-muted"><small>Pastikan tanggal yang dipilih adalah hari Jum'at. Data akan otomatis ditampilkan di layar pada minggu tersebut.</small></p>
					<div class="input-group" style="margin-bottom:10px;">
					  <span class="input-group-addon">Tanggal</span>
					  <input name="tanggal" type="date" class="form-control" required>
					</div>
					<div class="input-group" style="margin-bottom:10px;">
					  <span class="input-group-addon">Khatib/Imam</span>
					  <input name="khatib" type="text" class="form-control" placeholder="Contoh: Ust. H. Fulan, Lc." required>
					</div>
					<div class="input-group" style="margin-bottom:10px;">
					  <span class="input-group-addon">No. HP Khatib</span>
					  <input name="no_hp" type="text" class="form-control" placeholder="Opsional (Misal: 08123...)">
					</div>
					<div class="input-group" style="margin-bottom:10px;">
					  <span class="input-group-addon">Muadzin/Bilal</span>
					  <input name="muadzin" type="text" class="form-control" placeholder="Contoh: Fulan bin Fulan" required>
					</div>
					
					<div class="form-group hidden">
						<input type="hidden" name="formId" value="petugas_jumat">
						<input type="hidden" name="index" value="">
					</div>
			  </div>
			  <div class="modal-footer">
				<button type="button" id="btnDeletePetugas" class="btn btn-danger delete pull-left"><i class="fa fa-trash"></i> Hapus</button>
				<button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
				<button type="submit" class="btn btn-primary"><i class="fa fa-floppy-o"></i> Simpan</button>
			  </div>
			</div>
			</form>
		  </div>
		</div>

		<script>
		function openModalPetugas(id, tanggal, khatib, muadzin, no_hp) {
			$('#modalPetugasTitle').text(id === 'new' ? 'Tambah Jadwal Petugas' : 'Edit Jadwal Petugas');
			$('#modalPetugas [name="index"]').val(id);
			$('#modalPetugas [name="tanggal"]').val(tanggal);
			$('#modalPetugas [name="khatib"]').val(khatib);
			$('#modalPetugas [name="no_hp"]').val(no_hp || '');
			$('#modalPetugas [name="muadzin"]').val(muadzin);
			
			if (id !== 'new') {
				$('#btnDeletePetugas').show();
			} else {
				$('#btnDeletePetugas').hide();
			}
			
			$('#modalPetugas').modal('show');
		}
		
		if ($.fn.DataTable) {
			$('.dataTable').DataTable({
				"language": { "search": "Cari:", "lengthMenu": "Tampil _MENU_ data", "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ data" },
				"order": [[1, "desc"]]
			});
		}
		
		$('#modalPetugas [name="tanggal"]').on('change', function() {
			var date = new Date($(this).val());
			if (!isNaN(date.getTime()) && date.getDay() !== 5) {
				alert("Hanya bisa memilih hari Jum'at!");
				$(this).val('');
			}
		});
		</script>
		<?php
		$this->data = ob_get_clean();
		$this->retSuccess();
	}
}
$request=isset($_POST['id'])?$_POST['id']:"UNKNOWN_REQUEST_________________________________________";
new proses($request);
?>