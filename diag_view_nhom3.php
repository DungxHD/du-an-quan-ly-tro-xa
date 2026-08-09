<?php
$base = __DIR__ . '/';
try {
    $pdo = new PDO('mysql:host=localhost;dbname=manage;charset=utf8mb4','root','');
    $cs = $pdo->query('SHOW COLUMNS FROM services')->fetchAll(PDO::FETCH_COLUMN);
    foreach (['delete_month','delete_year','kind'] as $col) {
        echo (in_array($col,$cs,true)?'OK   ':'FAIL ') . 'services.' . $col . "\n";
    }
    $pc = $pdo->query('SHOW COLUMNS FROM price_changes')->fetchAll(PDO::FETCH_COLUMN);
    foreach (['applied','old_billing_mode','new_billing_mode'] as $col) {
        echo (in_array($col,$pc,true)?'OK   ':'FAIL ') . 'price_changes.' . $col . "\n";
    }
} catch (Throwable $e) { echo 'DB ERROR: '.$e->getMessage()."\n"; }
$view = $base.'views/admin/billing/services.php';
$out = $base.'view_dump_nhom3.txt';
if (is_file($view)) {
    $lines = file($view);
    $s = 'TONG SO DONG: '.count($lines)."\n";
    foreach ($lines as $i => $ln) { $s .= str_pad((string)($i+1),4,' ',STR_PAD_LEFT).'| '.$ln; }
    file_put_contents($out, $s);
    echo "DA GHI: view_dump_nhom3.txt (".count($lines)." dong)\n";
} else { echo 'MISSING: '.$view."\n"; }