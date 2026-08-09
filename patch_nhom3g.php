<?php
$base = __DIR__ . '/';
$log = [];
function putLog(&$ok, $label, $success) { global $log; $log[] = ($success ? 'OK: ' : 'FAIL: ') . $label; $ok = $ok && $success; }
$allOk = true;

// ---- Fix 1: ServiceModel ternary kind (tránh null) ----
$f = $base . 'models/billing/ServiceModel.php';
$c = str_replace("\r\n", "\n", file_get_contents($f));
copy($f, $f . '.bak_nhom3g');
$old = "/'kind' => in_array\(\(\$service\['kind'\] \?\? 'other'\), self::KINDS, true\) \? \$service\['kind'\] : 'other',/";
$new = "'kind' => in_array((\$service['kind'] ?? 'other'), self::KINDS, true) ? (\$service['kind'] ?? 'other') : 'other',";
$c2 = preg_replace($old, $new, $c, 1, $cnt);
putLog($allOk, 'SM fix ternary kind', $cnt === 1);
if ($cnt === 1) { $c = $c2; }
file_put_contents($f, $c);

// ---- Fix 2: AdminController saveService giu kind khi sua ----
$f = $base . 'controllers/AdminController.php';
$c = str_replace("\r\n", "\n", file_get_contents($f));
copy($f, $f . '.bak_nhom3g');
$old = "/\$core = \$data;\n\$core\['price'\] = \(float\)\$existing\['price'\];\n\$core\['billing_mode'\] = \(string\)\$existing\['billing_mode'\];\nServiceModel::save\(\$core, \$id\);/";
$new = "\$core = \$data;\n\$core['price'] = (float)\$existing['price'];\n\$core['billing_mode'] = (string)\$existing['billing_mode'];\n\$core['kind'] = (string)(\$existing['kind'] ?? 'other');\nServiceModel::save(\$core, \$id);";
$c2 = preg_replace($old, $new, $c, 1, $cnt);
putLog($allOk, 'AC giu kind khi sua', $cnt === 1);
if ($cnt === 1) { $c = $c2; }
file_put_contents($f, $c);

echo implode("\n", $log) . "\n";
echo $allOk ? "FIX NHOM 3G HOAN TAT.\n" : "CO BUOC FAIL - KIEM TRA BANG TAY.\n";