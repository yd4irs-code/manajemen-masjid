<?php
	// Data koneksi database dan default konfigurasi
	require_once 'db/koneksi.php';
	try {
		$db = loadDatabase();
	} catch (Exception $e) {
		echo "<h1>Jalankan migrasi database terlebih dahulu: <a href='db/migrate.php'>migrate.php</a></h1>";
		die;
	}
	$showDb = $db;
	unset($showDb['akses']);
	
	$info_timer			= $db['timer']['info'] 		* 1000;	
	$wallpaper_timer	= $db['timer']['wallpaper'] * 1000;	
	$adzan_timer		= $db['timer']['adzan'] 	* 1000 * 60; 
	$sholat_timer		= $db['timer']['sholat'] 	* 1000 * 60;
	
	$khutbah_jumat		= $db['jumat']['duration'] 	* 1000 * 60;
	$sholat_tarawih		= $db['tarawih']['duration'] 	* 1000 * 60;

	// Saldo Kas Keuangan
	$saldoKas = 0;
	try {
		$pdo = getDbConnection();
		$sAwalRaw = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='saldo_awal'")->fetchColumn();
		$sAwal = $sAwalRaw ? (float)$sAwalRaw : 0;
		$qMasuk = $pdo->query("SELECT SUM(nominal) FROM keuangan WHERE jenis='pemasukan'")->fetchColumn();
		$qKeluar = $pdo->query("SELECT SUM(nominal) FROM keuangan WHERE jenis='pengeluaran'")->fetchColumn();
		$saldoKas = $sAwal + $qMasuk - $qKeluar;
	} catch (Exception $e) {}
	
	// Inject slide informasi saldo kas otomatis
	$db['info'][] = [
		"Informasi Keuangan Masjid",
		"Total Saldo Kas Saat Ini\nRp " . number_format($saldoKas, 0, ',', '.'),
		"Semoga Allah membalas amal jariyah para jamaah",
		true
	];

	// --- FUNGSI HIJRIYAH SERVER-SIDE ---
	function get_hijriyah_server($tanggal) {
		$timestamp = strtotime($tanggal);
		$d = (int)date('d', $timestamp);
		$m = (int)date('m', $timestamp);
		$y = (int)date('Y', $timestamp);
		
		if (($y > 1582) || (($y == 1582) && ($m > 10)) || (($y == 1582) && ($m == 10) && ($d > 14))) {
			$jd = (int)((1461 * ($y + 4800 + (int)(($m - 14) / 12))) / 4) + (int)((367 * ($m - 2 - 12 * ((int)(($m - 14) / 12)))) / 12) - (int)((3 * ((int)(($y + 4900 + (int)(($m - 14) / 12)) / 100))) / 4) + $d - 32075;
		} else {
			$jd = 367 * $y - (int)((7 * ($y + 5001 + (int)(($m - 9) / 7))) / 4) + (int)((275 * $m) / 9) + $d + 1729777;
		}
		$l = $jd - 1948440 + 10632;
		$n = (int)(($l - 1) / 10631);
		$l = $l - 10631 * $n + 354;
		$j = ((int)((10985 - $l) / 5316)) * ((int)((50 * $l) / 17719)) + ((int)($l / 5670)) * ((int)((43 * $l) / 15238));
		$l = $l - ((int)((30 - $j) / 15)) * ((int)((17719 * $j) / 50)) - ((int)($j / 16)) * ((int)((15238 * $j) / 43)) + 29;
		$month = (int)((24 * $l) / 709);
		$day   = $l - (int)((709 * $month) / 24);
		$year  = 30 * $n + $j - 30;
		
		$bulan = [1 => "Muharram", "Safar", "Rabiul Awwal", "Rabiul Akhir", "Jumadil Awwal", "Jumadil Akhir", "Rajab", "Syaban", "Ramadhan", "Syawal", "Dzulqadah", "Dzulhijjah"];
		return $day . " " . $bulan[$month] . " " . $year . " H";
	}

	// Persiapan variabel Hijriah untuk sinkronisasi JS
	$hijri_hari_ini = get_hijriyah_server(date('Y-m-d'));
	$hijri_besok    = get_hijriyah_server(date('Y-m-d', strtotime('+1 day')));
	$tgl_hijri_awal = (date('H:i') >= "18:20") ? $hijri_besok : $hijri_hari_ini;
	
	//Logo — sekarang di root/logo/
	$dirLogo	= 'logo/';
	$filesLogo	= array_diff(scandir($dirLogo),array('.','..','Thumbs.db'));
	$filesLogo	= array_values($filesLogo);
	$logo		= $filesLogo[0];
	
	// Wallpaper — sekarang di root/wallpaper/
	$dir	= 'wallpaper/';
	$files	= array_diff(scandir($dir),array('.','..','Thumbs.db'));
	$wallpaper	= '';
	$i	= 0;
	foreach($files as $v){
		$active	= $i==0?'active':'';
		$wallpaper	.= '<div class="item slides '.$active.'"><div style="background-image: url(wallpaper/'.$v.');"></div></div>';
		$i++;
	}
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($db['setting']['nama'] ?? 'Manajemen Masjid') ?></title>
    <link rel="icon" type="image/png" href="icon.png"/>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <div id="preloader">
      <div id="status">&nbsp;</div>
    </div> 
	
	<div id="full-screen-clock" style="display:none"></div>
	<div id="count-down" class="full-screen" style="display:none">
		<div class="counter">
			<h1>COUNTER</h1>
			<div class="hh">00<span>JAM</span></div>
			<div class="ii">00<span>MENIT</span></div>
			<div class="ss">00<span>DETIK</span></div>
		</div>
	</div>
	<div id="display-adzan" class="full-screen" style="display:none"><div></div></div>
	<div id="display-sholat" class="full-screen" style="display:none"></div>
	<div id="display-khutbah" class="full-screen" style="display:none"><div></div></div>
	
	<div class="carousel fade-carousel slide" data-ride="carousel" data-interval="<?=$wallpaper_timer?>">
	  <div class="overlay"></div>
	  <div class="carousel-inner"><?=$wallpaper?></div> 
	</div>
	
	<div id="left-container">
		<div id="jam"></div>
		<div id="tgl"></div>
		<div id="jadwal"></div>
	</div>
	
	<div id="right-counter" style="display:none">
		<div class="counter">
			<h1>COUNTER</h1>
			<div class="hh">00<span>JAM</span></div>
			<div class="ii">00<span>MENIT</span></div>
			<div class="ss">00<span>DETIK</span></div>
		</div>
	</div>
	<div id="right-container">
		<div id="quote">
			<div class="carousel quote-carousel slide" data-ride="carousel" data-interval="<?=$info_timer?>" data-pause="null">
			  <div class="carousel-inner">
				<?php 
				$i=0;
				foreach($db['info'] as $k => $v){
					if($v[3]){
						echo '
						<div class="item slides '.($i==0?'active':'').'">
						  <div class="hero">        
							<hgroup>
								<div class="text1">'.htmlentities($v[0]).'</div>        
								<div class="text2">'.nl2br(htmlentities($v[1])).'</div>        
								<div class="text3">'.htmlentities($v[2]).'</div>
							</hgroup>
						  </div>
						</div>
						';
						$i++;
					}
				}
				?>
			  </div> 
			</div>
		</div>
		<div id="logo" style="background-image: url(logo/<?=$logo?>);"></div>
		
		<div id="keuangan-widget" style="background: rgba(0,0,0,0.6); color: #fff; padding: 10px 20px; font-size: 26px; border-radius: 10px; margin-bottom: 20px; border-left: 5px solid #00a65a; display: inline-block; box-shadow: 0 4px 10px rgba(0,0,0,0.5);">
			<i class="fa fa-money"></i> Saldo Kas Masjid: <b>Rp <?= number_format((float)$saldoKas, 0, ',', '.') ?></b>
		</div>

		<div id="running-text">
			<div class="item">
				<marquee>
				<?php 
					foreach($db['running_text'] as $k => $v){
						if ($v[1]) { // cek is_active
							echo '<i class="fa fa-square-o" aria-hidden="true"></i> '.htmlentities($v[0]);
						}
					}
				?>
				</marquee>
			</div>
		</div>
	</div>

    <script src="js/jquery-3.4.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/moment-with-locales.js"></script>
    <script src="js/PrayTimes.js"></script>
    <script src="js/jquery.marquee.js"></script>
    <script>
		var format = '24h';
		<?php
			echo "var lat = ".$db['setting']['latitude'].";\n";
			echo "var lng = ".$db['setting']['longitude'].";\n";
			echo "var timeZone = ".$db['setting']['timeZone'].";\n";
			echo "var dst = ".$db['setting']['dst'].";\n";
			
			if($db['prayTimesMethod']=='0'){
				foreach($db['prayTimesAdjust'] as $k => $v){
					if($v!='') $prayTimesAdjust[$k]=$v;
				}
				echo "var prayTimesAdjust =\t$.parseJSON('".stripslashes(str_replace("`","\\`",json_encode($prayTimesAdjust)))."');\n";
				echo "prayTimes.adjust(prayTimesAdjust);\n"; 
			} else {
				echo "prayTimes.setMethod('".$db['prayTimesMethod']."');\n";
			}
			
			$prayTimesTune	= [];
			foreach($db['prayTimesTune'] as $k => $v){
				if($v!='0') $prayTimesTune[$k]=$v;
			}
			if(count($prayTimesTune)>0){
				echo "var prayTimesTune =\t$.parseJSON('".stripslashes(str_replace("`","\\`",json_encode($prayTimesTune)))."');\n";
				echo "prayTimes.tune(prayTimesTune);\n"; 
			}
		?>
		
		var app	={
			db	: $.parseJSON(`<?=stripslashes(str_replace("`","\\`",json_encode($showDb)))?>`),
			cekDb	: false,
			tglHariIni		: '',
			tglBesok		: '',
			jadwalHariIni	: {},
			jadwalBesok		: {},
			timer			: false,
			adzanTimer		: false,
			countDownTimer	: false,
			sholatTimer		: false,
			khutbahTimer	: false,
			nextPrayCount	: 0,
			fajr	: '',
			dhuhr	: '',
			asr		: '',
			maghrib	: '',
			isha	: '',
			audio	: new Audio('img/beep.mp3'),
			
			// Properti Hijriah
			hijriHariIni : "<?=$hijri_hari_ini?>",
			hijriBesok    : "<?=$hijri_besok?>",
			hijriAktif    : "<?=$tgl_hijri_awal?>",
			
			initialize	: function(){
				app.timer	= setInterval(function(){app.cekPerDetik()},1000);
				$('#preloader').delay(350).fadeOut('slow');
			},
			cekPerDetik	: function(){
				let sekarang = moment();
				let jamMenit = sekarang.format('HH:mm');

				// Logika perubahan Hijriah otomatis tepat pukul 18:20
				if (jamMenit >= "18:20") {
					app.hijriAktif = app.hijriBesok;
				} else {
					app.hijriAktif = app.hijriHariIni;
				}

				if(!app.tglHariIni || sekarang.format('YYYY-MM-DD') != moment(app.tglHariIni).format('YYYY-MM-DD')){
					// Refresh otomatis saat ganti hari masehi untuk sinkronisasi ulang data
					if(app.tglHariIni != '') location.reload();

					app.tglHariIni	= sekarang;
					app.tglBesok 	= moment().add(1,'days');
					app.jadwalHariIni	= app.getJadwal(moment(app.tglHariIni).toDate());
					app.jadwalBesok		= app.getJadwal(moment(app.tglBesok).toDate());
					app.fajr	= moment(app.jadwalHariIni.fajr,'HH:mm');
					app.dhuhr	= moment(app.jadwalHariIni.dhuhr,'HH:mm');
					app.asr		= moment(app.jadwalHariIni.asr,'HH:mm');
					app.maghrib	= moment(app.jadwalHariIni.maghrib,'HH:mm');
					app.isha	= moment(app.jadwalHariIni.isha,'HH:mm');
				}
				app.showJadwal();
				app.displaySchedule();
				
				$.ajax({  
					type    : "GET",  
					url     : "check_update.php",
					cache   : false
				}).done(function(dt){
					if(app.cekDb==false) app.cekDb = dt.trim();
					else if(app.cekDb !== dt.trim()) location.reload();
				});
			},
			getJadwal	: function(jadwalDate){
				return prayTimes.getTimes(jadwalDate, [lat, lng], timeZone, dst, format);
			},
			showJadwal	: function(){
				let jamSekarang	= moment();
				let jamDelay	= moment().subtract(5,'minutes');
				let jadwal	= '';
				let hari	= app.db.dayName[jamSekarang.format("dddd")];
				let bulan	= app.db.monthName[jamSekarang.format("MMMM")];
				
				$('#jam').html(jamSekarang.format("HH.mm[<div>]ss[</div>]"));
				
				// TANGGAL GABUNGAN (Real-time Hijriah dari app.hijriAktif)
				let strMasehi = jamSekarang.format("["+hari+"], DD ["+bulan+"] YYYY");
				$('#tgl').html( strMasehi + '<br><span style="display:inline-block; margin-top:2px;">' + app.hijriAktif + '</span>' );
				
				if($('.full-screen').is(":visible")){
					$('#full-screen-clock').html(jamSekarang.format("[<i class='fa fa-clock-o''></i>&nbsp;&nbsp;]HH:mm"));
					$('#full-screen-clock').slideDown();
				}
				else $('#full-screen-clock').slideUp();
				
				let jadwalDipake = app.jadwalHariIni;
				let jadwalPlusIcon	= '';
				
				if(jamDelay > app.isha){
					jadwalDipake = app.jadwalBesok;
					jadwalPlusIcon	= '<span><i class="fa fa-plus" aria-hidden="true"></i></span>';
				}
				$.each(app.db.prayName, function(k,v) {
					let css= '';
					if		(k == 'isha' 	&& jamDelay < app.isha		&& jamDelay > app.maghrib) 	css= 'active';
					else if	(k == 'maghrib' && jamDelay < app.maghrib	&& jamDelay > app.asr) 		css= 'active';
					else if	(k == 'asr' 	&& jamDelay < app.asr		&& jamDelay > app.dhuhr) 	css= 'active';
					else if	(k == 'dhuhr' 	&& jamDelay < app.dhuhr		&& jamDelay > app.fajr) 	css= 'active';
					else if	(k == 'fajr'	&& (jamDelay < app.fajr		|| jamDelay > app.isha))	css= 'active';
					jadwal += '<div class="row '+css+'"><div class="col-xs-5">'+v+'</div><div class="col-xs-7">'+jadwalDipake[k] + jadwalPlusIcon + '</div></div>';
				});
				$('#jadwal').html(jadwal);
			},
			displaySchedule: function(){
				let waitAdzan		= moment().add(app.db.timer.wait_adzan,'minutes').format('YYYY-MM-DD HH:mm:ss');
				let jamSekarang		= moment().format('YYYY-MM-DD HH:mm:ss');
				
				$.each(app.db.prayName, function(k,v) {
					let t			= moment(app[k]);
					let jadwal		= t.format('YYYY-MM-DD HH:mm:ss');
					let stIqomah	= t.add(app.db.timer.adzan,'minutes').format('YYYY-MM-DD HH:mm:ss');
					let enIqomah	= moment(stIqomah,'YYYY-MM-DD HH:mm:ss').add(app.db.iqomah[k],'minutes')
					
					if(waitAdzan == jadwal)			app.runRightCountDown(app[k],'Menuju '+v);
					else if(jadwal == jamSekarang)		app.showDisplayAdzan(v);
					else if(stIqomah == jamSekarang){
						if(moment().format('dddd')=='Friday' && k=='dhuhr'){
							// Khusus hari Jumat Dzuhur, lewati layar Iqamah dan langsung kembali ke normal
						}
						else {
							app.runFullCountDown(enIqomah,'IQOMAH',true);
						}
					}
				});
			},
			getNextPray	: function(){
				let jamSekarang		= moment();
				let nextPray		= 'fajr';
				let jadwalDipake 	= false;
				if(jamSekarang > app.isha){
					jadwalDipake	= moment(app.jadwalBesok[nextPray],'HH:mm').add(1,'Day');
				}
				else{
					$.each(app.db.prayName, function(k,v){
						if(jamSekarang < app[k]){
							nextPray	= k;
							return false;
						}
					});
					jadwalDipake	= moment(app.jadwalHariIni[nextPray],'HH:mm');
				}
				return { 'pray': nextPray, 'date': jadwalDipake };
			},
			showCountDownNextPray	: function(){
				let nextPray		= app.getNextPray();
				if (app.countDownTimer) return;
				app.nextPrayCount	= 0;
				app.countDownTimer	= setInterval(function(){
					let t	= app.countDownCalculate(nextPray.date);
					
					$('#right-counter .counter>h1').html('Menuju '+app.db.prayName[nextPray.pray]);
					$('#right-counter .counter>.hh').html(t.hours+'<span>'+app.db.timeName.Hours+'</span>');
					$('#right-counter .counter>.ii').html(t.minutes+'<span>'+app.db.timeName.Minutes+'</span>');
					$('#right-counter .counter>.ss').html(t.seconds+'<span>'+app.db.timeName.Seconds+'</span>');
					
					$('#right-counter').slideDown();
					$('#quote').hide();
					
					app.nextPrayCount++;
					if (app.nextPrayCount >= 30) {
						clearInterval(app.countDownTimer);
						app.countDownTimer	= false;
						$('#right-counter').fadeOut();
						$('#quote').fadeIn();
					}
				},1000);
			},
			showDisplayAdzan	: function(prayName){
				if (!app.adzanTimer){
					$('#display-adzan>div').text('Adzan ' + prayName);
					$('#display-adzan').show();
					app.adzanTimer	= setTimeout(function(){
						$('#display-adzan').fadeOut();
						app.adzanTimer	= false;
					},(app.db.timer.adzan * 60 * 1000)+1500);
				}
			},
			showDisplayKhutbah	: function(){
				if (!app.khutbahTimer){
					$('#display-khutbah>div').text(app.db.jumat.text);
					$('#display-khutbah').show();
					app.khutbahTimer	= setTimeout(function(){
						app.khutbahTimer	= false;
						app.showDisplaySholat();
						$('#display-khutbah').fadeOut();
					},app.db.jumat.duration * 60 * 1000);
				}
			},
			showDisplaySholat	: function(){
				if (!app.khutbahTimer){
					let jamSekarang		= moment();
					let duration		= (jamSekarang > app.isha && app.db.tarawih.active)?app.db.tarawih.duration:app.db.timer.sholat;
					$('#display-sholat').show();
					app.khutbahTimer	= setTimeout(function(){
						$('#display-sholat').fadeOut();
						app.khutbahTimer	= false;
						app.showCountDownNextPray();
					},duration * 60 * 1000);
				}
			},
			runFullCountDown: function(jam,title,runDisplaySholat){
				if (app.countDownTimer) return;
				app.countDownTimer	= setInterval(function(){
					let t	= app.countDownCalculate(jam);
					
					$('#count-down .counter>h1').html(title);
					$('#count-down .counter>.hh').html(t.hours+'<span>'+app.db.timeName.Hours+'</span>');
					$('#count-down .counter>.ii').html(t.minutes+'<span>'+app.db.timeName.Minutes+'</span>');
					$('#count-down .counter>.ss').html(t.seconds+'<span>'+app.db.timeName.Seconds+'</span>');
					
					$('#count-down').fadeIn();
					if(t.distance==5){
						app.audio.play().catch( () => { console.log('Sound blocked'); });
					}
					if (t.distance < 1) {
						clearInterval(app.countDownTimer);
						app.countDownTimer	= false;
						$('#count-down').fadeOut();
						if(runDisplaySholat){ app.showDisplaySholat(); }
					}
				},1000);
			},
			runRightCountDown	: function(jam,title){
				if (app.countDownTimer) return;
				app.countDownTimer	= setInterval(function(){
					let t	= app.countDownCalculate(jam);
					
					$('#right-counter .counter>h1').html(title);
					$('#right-counter .counter>.hh').html(t.hours+'<span>'+app.db.timeName.Hours+'</span>');
					$('#right-counter .counter>.ii').html(t.minutes+'<span>'+app.db.timeName.Minutes+'</span>');
					$('#right-counter .counter>.ss').html(t.seconds+'<span>'+app.db.timeName.Seconds+'</span>');
					
					$('#right-counter').slideDown();
					$('#quote').hide();
					
					if (t.distance < 1) {
						clearInterval(app.countDownTimer);
						app.countDownTimer	= false;
						$('#right-counter').fadeOut();
						$('#quote').fadeIn();
					}
				},1000);
			},
			countDownCalculate(jam){
				let jamSekarang	= moment();
				let distance	= Math.round(jam.diff(jamSekarang, 'seconds', true)) ;
				let hours = Math.floor((distance % (60 * 60 * 24)) / (60 * 60));
				let minutes = Math.floor((distance % (60 * 60)) / 60);
				let seconds = Math.floor((distance % 60));
				hours	= (hours>=0		&& hours<10)	?'0'+hours:hours;
				minutes	= (minutes>=0	&& minutes<10)	?'0'+minutes:minutes;
				seconds	= (seconds>=0	&& seconds<10)	?'0'+seconds:seconds;
				return	{ 'distance': distance, 'hours': hours, 'minutes': minutes, 'seconds': seconds };
			}
		}
		app.initialize();
	</script>
</body>
</html>
