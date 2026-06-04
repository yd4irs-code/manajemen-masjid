<?php
trait TraitInfo {
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

}
