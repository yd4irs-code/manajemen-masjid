<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

date_default_timezone_set('Asia/Jakarta');
$file = 'db/last_updated.txt';
$current_date = date('Y-m-d');

if (file_exists($file)) {
    echo trim(file_get_contents($file)) . "_" . $current_date;
} else {
    $time = (string)time();
    @file_put_contents($file, $time);
    echo $time . "_" . $current_date;
}
