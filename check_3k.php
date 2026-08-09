<?php
$base = __DIR__ . '/';
$checks = [
'models/billing/ServiceModel.php' => ['deriveUnit','countRoomsUsing','isPendingDelete','scheduleDelete','applyDueDeletes','delete_month'],
'models/billing/PriceChangeModel.php' => ['cancelPendingChange'],
'controllers/AdminController.php' => ['applyDueDeletes','priceHistories','undoDeleteService','cancelPriceChange','countRoomsUsing','scheduleDelete'],
];
$allOk = true;
foreach ($checks as $rel => $markers) {
$c = @file_get_contents($base.$rel);
if ($c === false) { echo "MISSING FILE: $rel\n"; $allOk = false; continue; }
foreach ($markers as $m) {
$has = strpos($c,$m) !== false;
echo ($has?'OK   ':'FAIL ').$rel.' :: '.$m."\n";
if (!$has) $allOk = false;
}
}
foreach (['models/billing/ServiceModel.php','models/billing/PriceChangeModel.php','controllers/AdminController.php'] as $rel) {
echo trim((string)shell_exec('php -l "'.$base.$rel.'" 2>&1'))."\n";
}
echo $allOk ? "=> BACKEND 3K DA DU.\n" : "=> 3K CHUA DU (CHO PATCH 3M).\n";