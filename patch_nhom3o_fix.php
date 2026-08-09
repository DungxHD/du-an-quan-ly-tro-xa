<?php
$base = __DIR__ . '/';
$f = $base.'controllers/AdminController.php';
$c = file_get_contents($f);
copy($f, $f.'.bak3o_fix');
$log = []; $allOk = true;
function putLog(&$ok,$l,$s){ global $log; $log[] = ($s?'OK: ':'FAIL: ').$l; $ok=$ok&&$s; }

// ===== FIX 1: them applies_to + unit vao saveService =====
if (strpos($c, "\$core['applies_to']") !== false) {
    putLog($allOk, 'applies_to/unit (da co)', true);
} else {
    $n = "\$core['billing_mode'] = (string)\$existing['billing_mode'];";
    $p = strpos($c, $n);
    if ($p !== false) {
        $ip = $p + strlen($n);
        $le = (substr($c, $ip, 2) === "\r\n") ? "\r\n" : "\n";
        $inj = $le . "\$core['applies_to'] = (string)(\$existing['applies_to'] ?? 'room');"
             . $le . "\$core['unit'] = ServiceModel::deriveUnit((string)(\$existing['kind'] ?? 'other'), (string)\$existing['billing_mode']);";
        $c = substr($c, 0, $ip) . $inj . substr($c, $ip);
        putLog($allOk, 'applies_to/unit', true);
    } else putLog($allOk, 'applies_to/unit', false);
}

// ===== FIX 2: them chon thang ap dung vao saveService =====
$ssS = strpos($c, 'function saveService');
$ssE = ($ssS !== false) ? strpos($c, 'function undoDeleteService', $ssS) : false;
if ($ssS !== false && $ssE !== false && $ssE > $ssS) {
    $blk = substr($c, $ssS, $ssE - $ssS);
    if (strpos($blk, "effective_month") !== false) {
        putLog($allOk, 'chon thang saveService (da co)', true);
    } else {
        $n2 = "\$nextMonth = (int)date('n') + 1;";
        $p2 = strpos($blk, $n2);
        $n3 = "if (\$nextMonth > 12) { \$nextMonth = 1; \$nextYear++; }";
        $p3 = ($p2 !== false) ? strpos($blk, $n3, $p2) : false;
        if ($p2 !== false && $p3 !== false) {
            $p3e = $p3 + strlen($n3);
            $le = (substr($blk, $p3e, 2) === "\r\n") ? "\r\n" : "\n";
            $new = "\$em = (int)(\$_POST['effective_month'] ?? 0); \$ey = (int)(\$_POST['effective_year'] ?? 0);"
                 . $le . "\$curOrder = ((int)date('Y')*100)+(int)date('n');"
                 . $le . "if (\$em >= 1 && \$em <= 12 && \$ey >= (int)date('Y') && (\$ey*100+\$em) > \$curOrder) { \$nextMonth=\$em; \$nextYear=\$ey; }"
                 . $le . "else { \$nextMonth = (int)date('n') + 1; \$nextYear = (int)date('Y'); if (\$nextMonth > 12) { \$nextMonth = 1; \$nextYear++; } }";
            $blk = substr($blk, 0, $p2) . $new . substr($blk, $p3e);
            $c = substr($c, 0, $ssS) . $blk . substr($c, $ssE);
            putLog($allOk, 'chon thang saveService', true);
        } else putLog($allOk, 'chon thang saveService', false);
    }
} else putLog($allOk, 'chon thang saveService', false);

file_put_contents($f, $c);
echo implode("\n", $log) . "\n";
echo $allOk ? "PATCH 3O FIX HOAN TAT.\n" : "CO BUOC FAIL - KIEM TRA.\n";