<?php
include_once "session.php";
if(!isset($_SESSION["user_id"])){
	header("Location: login.php");
	exit;
}
require_once '../db/koneksi.php';
$name = '';
try {
	$_pdo  = getDbConnection();
	$_stmt = $_pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'nama'");
	$_stmt->execute();
	$_row  = $_stmt->fetch();
	if ($_row) $name = $_row['setting_value'];
} catch (Exception $e) { $name = ''; }

$role = $_SESSION['role'] ?? 'marbot'; // Default marbot jika tidak set
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Manajemen|Masjid|Admin</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <link rel="icon" type="image/png" href="../icon.png"/>
  <link rel="stylesheet" href="../dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../dist/css/font-awesome.min.css">
  <link rel="stylesheet" href="../dist/css/AdminLTE.min.css">
  <link rel="stylesheet" href="../dist/css/_all-skins.min.css">
  <link rel="stylesheet" href="../dist/css/bootstrap-datetimepicker.css">
  <link rel="stylesheet" href="../dist/css/datatables.min.css">
	<style>
		button.info-box{
			padding:0;
			border:none;
			border-radius:10px;
			overflow:hidden;
		}
		
		button.info-box:active{
			box-shadow: inset 0 0 100px #000000
		}
		.nav-tabs-custom .tab-content h4{
			background-color:#00a65a;
			color:#FFF; 
			font-size: 18px; 
			padding: 7px 10px; 
			margin: 0;
			font-size:14px;
			text-transform: uppercase;
		}
		.section-wallpaper .small-box{
			background-position: center center;
			background-size: cover;
			background-repeat: no-repeat;
			margin-bottom:10px;
		}
		.section-wallpaper .small-box .inner{
			min-height:100px;
		}
		.section-wallpaper .small-box>.small-box-footer{
			background: rgba(0, 0, 0, 0.3);
		}
		#container{
			padding-bottom:20px;
		}
		form .box .input,
		form .box .input-group{
			margin-bottom:1px;
		}
		form .box .input-group>.input-group-addon:first-child{
			background-color:#00a65a;
			border-color:#00a65a;
			color:#FFF;
			min-width:100px;
			text-align: right;
		}
		.dataTable thead{
			background:#00a65a;
			color: #fff;
		}
		.table-responsive{
			border:none !important;
		}
		.sidebar-menu>li>a{
			cursor:pointer;
		}
		.date-navigation .btn{
			border-radius: 20px;
			height: 30px;
			font-size:12px;
			padding: 2px 15px;
			margin: 15px;
			
		}
		div.dataTables_wrapper div.dataTables_filter label{
			margin-bottom:0;
		}
		table.dataTable{
			margin-top: 0 !important;
		}
		.no-margin>tbody>tr>td,
		.no-margin>thead>tr>th{
			text-align:center;
			padding:0 !important;
		}
		.no-margin>thead>tr>th{
			padding:3px 0 !important;
		}
		.no-margin>tbody>tr>td{
			margin:0;
			padding: 2px;
		}
		.today{
			background:#87e7ff  !important;
			font-weight:bold;
		}
	</style>
</head>
<body class="hold-transition skin-green sidebar-mini">
<div class="wrapper">
  <header class="main-header">
    <a href="" class="logo" onclick="location.reload()">
      <span class="logo-mini">MM</span>
      <span class="logo-lg"><b>Manajemen</b>Masjid</span>
    </a>
    <nav class="navbar navbar-static-top">
      <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
        <span class="sr-only">Toggle navigation</span>
      </a>
      <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">
          <li>
            <a><?=$name?> - Admin Manajemen</a>
          </li>
        </ul>
      </div>
    </nav>
  </header>
  <!-- Left side column. contains the logo and sidebar -->
  <aside class="main-sidebar">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">
      <!-- sidebar menu: : style can be found in sidebar.less -->
      <ul class="sidebar-menu" data-widget="tree">
        <li class="header">NAVIGATION</li>
		
		<?php if ($role === 'admin' || $role === 'marbot'): ?>
		<li class="active"><a data-target="info"><i class="fa fa-comment"></i> <span>Informasi</span></a></li>
		<li><a data-target="running_text"><i class="fa fa-text-width"></i> <span>Running text</span></a></li>
		<li><a data-target="wallpaper"><i class="fa fa-television"></i> <span>Wallpaper</span></a></li>
		<li><a data-target="petugas_jumat"><i class="fa fa-user-plus"></i> <span>Petugas Jum'at</span></a></li>
		<?php endif; ?>
		
		<?php if ($role === 'admin'): ?>
		<li><a data-target="timer"><i class="fa fa-clock-o"></i> <span>Timer</span></a></li>
		<li><a data-target="jadwal"><i class="fa fa-calendar"></i> <span>Setting Jadwal</span></a></li>
		<li><a data-target="simulasi"><i class="fa fa-history"></i> <span>Simulasi Jadwal</span></a></li>
		<?php endif; ?>
		
		<?php if ($role === 'admin' || $role === 'bendahara' || $role === 'marbot'): ?>
		<li <?=$role==='bendahara'?'class="active"':''?>><a data-target="keuangan"><i class="fa fa-money"></i> <span>Keuangan</span></a></li>
		<?php endif; ?>
		
		<?php if ($role === 'admin'): ?>
		<li><a data-target="pengaturan"><i class="fa fa-cogs"></i> <span>Pengaturan</span></a></li>
		<li><a data-target="manajemen_user"><i class="fa fa-users"></i> <span>Manajemen User</span></a></li>
		<?php endif; ?>
		
		<li><a data-target="logout"><i class="fa fa-sign-out"></i> <span>Logout</span></a></li>
      </ul>
    </section>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
	<div id='container' class="content-wrapper">
	</div>
	<footer class="main-footer">
		<div class="pull-right">
		  <b>Version</b> 1.0 (2026)
		</div>
		<strong>Manajemen|Masjid</strong> Aplication
	</footer>
	<!-- <script src="cordova.js"></script> -->
	<script src="../dist/js/jquery.min.js"></script>
	<script src="../dist/js/bootstrap.min.js"></script>
	<script src="../dist/js/swipe.js"></script>
	<script src="../dist/js/adminlte.min.js"></script>
	<script src="../dist/js/moment-with-locales.js"></script>
	<script src="../dist/js/bootstrap-datetimepicker.min.js"></script>
	<script src="../dist/js/datatables.min.js"></script>
	<script src="../js/PrayTimes.js"></script>
	<script src="../dist/js/fn.js?v=<?=time()?>"></script>
</body>
</html>
