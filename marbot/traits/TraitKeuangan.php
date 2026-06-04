<?php
trait TraitKeuangan {
	private function keuangan() {
		if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'bendahara') $this->retError("Akses ditolak");
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
			  </div>
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

}
