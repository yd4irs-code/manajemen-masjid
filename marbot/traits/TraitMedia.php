<?php
trait TraitMedia {
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
		
		$this->updateSync();
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
		$this->updateSync();
		$this->retSuccess();
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
			$this->updateSync();
			$this->retSuccess();
		}
	}

}
