<?php
trait TraitJadwal {
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

}
