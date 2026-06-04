<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$file = 'db/last_updated.txt';
if (file_exists($file)) {
    echo trim(file_get_contents($file));
} else {
    $time = (string)time();
    @file_put_contents($file, $time);
    echo $time;
}
