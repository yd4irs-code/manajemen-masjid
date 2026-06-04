<?php
$file = 'marbot/proses.php';
$content = file_get_contents($file);

// Inject formSave logic
$formSaveLogic = <<<EOT
		if (\$id == 'petugas_jumat') {
			\$tanggal = \$dt['tanggal'];
			\$khatib = \$dt['khatib'];
			\$muadzin = \$dt['muadzin'];
			try {
				\$pdo = getDbConnection();
				if (\$index === 'new') {
					\$stmt = \$pdo->prepare("INSERT INTO petugas_jumat (tanggal, khatib, muadzin) VALUES (:t, :k, :m)");
					\$stmt->execute([':t'=>\$tanggal, ':k'=>\$khatib, ':m'=>\$muadzin]);
				} else {
					\$stmt = \$pdo->prepare("UPDATE petugas_jumat SET tanggal=:t, khatib=:k, muadzin=:m WHERE id=:id");
					\$stmt->execute([':t'=>\$tanggal, ':k'=>\$khatib, ':m'=>\$muadzin, ':id'=>\$index]);
				}
			} catch (Exception \$e) { \$this->retError('Gagal simpan petugas: '.\$e->getMessage()); }
			\$this->updateSync();
			\$this->retSuccess();
			return;
		}

EOT;
$content = preg_replace('/(private function formSave\(\)\{.*?\$index\t= \$dt\[\'index\'\];\s*unset\(\$dt\[\'formId\'\]\);\s*unset\(\$dt\[\'index\'\]\);)/is', "$1\n$formSaveLogic", $content);

// Inject formDelete logic
$formDeleteLogic = <<<EOT
		if (\$id == 'petugas_jumat') {
			try {
				\$pdo = getDbConnection();
				\$stmt = \$pdo->prepare("DELETE FROM petugas_jumat WHERE id=:id");
				\$stmt->execute([':id' => \$index]);
			} catch (Exception \$e) { \$this->retError('Gagal hapus petugas: '.\$e->getMessage()); }
			\$this->updateSync();
			\$this->retSuccess();
			return;
		}

EOT;
$content = preg_replace('/(private function formDelete\(\)\{.*?\$index = \$dt\[\'index\'\];)/is', "$1\n$formDeleteLogic", $content);

// Inject petugas_jumat view logic before closing class
$petugasViewLogic = <<<EOT

	private function petugas_jumat() {
		if (\$_SESSION['role'] !== 'admin' && \$_SESSION['role'] !== 'marbot') \$this->retError("Akses ditolak");
		ob_start();
		try {
			\$pdo = getDbConnection();
			\$petugas = \$pdo->query("SELECT * FROM petugas_jumat ORDER BY tanggal DESC, id DESC")->fetchAll();
		} catch (Exception \$e) { 
			\$petugas = []; 
		}
		
		?>
		<section class="content-header content-dynamic">
			<div class="row">
				<div class="col-md-12 col-sm-12 col-xs-12">
					<div class="box box-success">
						<div class="box-header with-border">
							<h3 class="box-title">Jadwal Petugas Sholat Jum'at</h3>
							<div class="box-tools pull-right">
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
										<th>Muadzin / Bilal</th>
										<th style="min-width:80px;">Aksi</th>
									</tr>
								</thead>
								<tbody>
									<?php 
									\$no = 1;
									foreach(\$petugas as \$v): ?>
									<tr>
										<td><?=\$no++?></td>
										<td><span style="display:none;"><?=\$v['tanggal']?></span><?=date('d-m-Y', strtotime(\$v['tanggal']))?></td>
										<td><?=htmlspecialchars(\$v['khatib'])?></td>
										<td><?=htmlspecialchars(\$v['muadzin'])?></td>
										<td>
											<button class="btn btn-sm btn-warning" onclick='openModalPetugas("<?=\$v['id']?>", "<?=\$v['tanggal']?>", <?=htmlspecialchars(json_encode(\$v['khatib']), ENT_QUOTES)?>, <?=htmlspecialchars(json_encode(\$v['muadzin']), ENT_QUOTES)?>)'><i class="fa fa-pencil"></i> Edit</button>
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
		function openModalPetugas(id, tanggal, khatib, muadzin) {
			$('#modalPetugasTitle').text(id === 'new' ? 'Tambah Jadwal Petugas' : 'Edit Jadwal Petugas');
			$('#modalPetugas [name="index"]').val(id);
			$('#modalPetugas [name="tanggal"]').val(tanggal);
			$('#modalPetugas [name="khatib"]').val(khatib);
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
		</script>
		<?php
		\$this->data = ob_get_clean();
		\$this->retSuccess();
	}
EOT;

$content = preg_replace('/\}\s*\$request\s*=\s*isset\(\$_POST/is', "$petugasViewLogic\n}\n\$request=isset(\$_POST", $content);

file_put_contents($file, $content);
echo "Inject petugas logic success.\n";

