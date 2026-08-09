<?php
$base = __DIR__ . '/';
$checks = [
    'controllers/AdminController.php' => ['scheduleServiceChange', 'applyDueChanges'],
    'models/billing/ServiceModel.php' => ['getKindBillingModesMap', 'isLockedKind'],
    'models/billing/PriceChangeModel.php' => ['scheduleServiceChange', 'applyDueChanges', 'getEffectiveConfigForPeriod'],
    'models/billing/PaymentModel.php' => ['getEffectiveConfigForPeriod'],
    'models/billing/MeterReadingModel.php' => ["case 'electricity':"],
];
$allOk = true;
foreach ($checks as $rel => $markers) {
    $c = @file_get_contents($base . $rel);
    if ($c === false) { echo "MISSING FILE: $rel\n"; $allOk = false; continue; }
    foreach ($markers as $mk) {
        $has = strpos($c, $mk) !== false;
        echo ($has ? 'OK   ' : 'FAIL ') . $rel . ' :: ' . $mk . "\n";
        if (!$has) { $allOk = false; }
    }
}
foreach (['.bak_nhom3b', '.bak_nhom3d'] as $bak) {
    echo (is_file($base . 'controllers/AdminController.php' . $bak) ? 'CO ' : 'KHONG ') . 'backup AdminController' . $bak . "\n";
}
echo $allOk ? "=> PATCH DA AP DUNG DAY DU.\n" : "=> PATCH CHUA AP DUNG (HOAC THIEU BUOC).\n";