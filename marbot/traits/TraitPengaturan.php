<?php
trait TraitPengaturan {
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

	}
}
