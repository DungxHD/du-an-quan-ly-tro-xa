<?php
$base = __DIR__ . '/';
function show($file, $needles) {
    echo "=== $file ===\n";
    if (!is_file($file)) { echo "FILE KHONG TON TAI\n\n"; return; }
    $lines = file($file);
    foreach ($lines as $i => $line) {
        foreach ($needles as $n) {
            if (strpos($line, $n) !== false) {
                echo ($i+1) . ": " . rtrim($line) . "\n";
                break;
            }
        }
    }
    echo "\n";
}
show($base . 'models/billing/ServiceModel.php', ["KINDS", "'kind'"]);
show($base . 'controllers/AdminController.php', ["core[", "ServiceModel::save(", "function saveService"]);