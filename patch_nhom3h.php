<?php
$base = __DIR__ . '/';
$log = [];
$allOk = true;
function putLog(&$ok, $label, $success) { global $log; $log[] = ($success ? 'OK: ' : 'FAIL: ') . $label; $ok = $ok && $success; }

// ---- Fix 1: ServiceModel sua ternary kind (tranh null) ----
$f = $base . 'models/billing/ServiceModel.php';
$c = file_get_contents($f);
copy($f, $f . '.bak_nhom3h');
$buggy = "? \$service['kind'] : 'other',";
$fixed = "? (\$service['kind'] ?? 'other') : 'other',";
if (strpos($c, $fixed) !== false) {
    putLog($allOk, 'SM ternary kind (da dung)', true);
} else {
    $count = 0;
    $c2 = str_replace($buggy, $fixed, $c, $count);
    if ($count === 1) { file_put_contents($f, $c2); putLog($allOk, 'SM ternary kind', true); }
    else { putLog($allOk, 'SM ternary kind', false); }
}

// ---- Fix 2: AdminController giu kind khi sua dich vu ----
$f = $base . 'controllers/AdminController.php';
$c = file_get_contents($f);
copy($f, $f . '.bak_nhom3h');
if (strpos($c, "\$core['kind']") !== false) {
    putLog($allOk, 'AC giu kind (da co)', true);
} else {
    $needle = "\$core['billing_mode'] = (string)\$existing['billing_mode'];";
    $pos = strpos($c, $needle);
    if ($pos === false) {
        putLog($allOk, 'AC giu kind', false);
    } else {
        $lineStart = strrpos(substr($c, 0, $pos), "\n");
        $lineStart = ($lineStart === false) ? 0 : $lineStart + 1;
        $indent = substr($c, $lineStart, $pos - $lineStart);
        if (trim($indent) !== '') { $indent = ''; }
        $needleEnd = $pos + strlen($needle);
        $newLine = "\n" . $indent . "\$core['kind'] = (string)(\$existing['kind'] ?? 'other');";
        $c2 = substr($c, 0, $needleEnd) . $newLine . substr($c, $needleEnd);
        file_put_contents($f, $c2);
        putLog($allOk, 'AC giu kind', true);
    }
}

echo implode("\n", $log) . "\n";
echo $allOk ? "FIX NHOM 3H HOAN TAT.\n" : "CO BUOC FAIL - KIEM TRA.\n";