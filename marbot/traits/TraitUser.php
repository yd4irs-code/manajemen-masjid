<?php
trait TraitUser {
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
										<td><?=htmlspecialchars($v['username'])?></td>
										<td><?=htmlspecialchars($v['nama_lengkap'])?></td>
										<td><span class="label label-<?= $v['role']=='admin'?'danger':($v['role']=='marbot'?'success':'warning') ?>"><?=strtoupper($v['role'])?></span></td>
										<td>
											<button class="btn btn-sm btn-warning" onclick='openModalUser("<?=$v['id']?>", <?=htmlspecialchars(json_encode($v['username']), ENT_QUOTES)?>, <?=htmlspecialchars(json_encode($v['nama_lengkap']), ENT_QUOTES)?>, "<?=$v['role']?>")'><i class="fa fa-pencil"></i> Edit</button>
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

}
