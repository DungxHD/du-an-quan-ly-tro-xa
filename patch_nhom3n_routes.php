<?php
$base = __DIR__ . '/';
$f = $base.'index.php';
$c = file_get_contents($f);
copy($f, $f.'.bak3n');
if (strpos($c, 'admin-undo-delete-service') !== false) {
echo "SKIP: routes da ton tai.\n"; exit(0);
}
$anchor = "case 'admin-delete-service':";
$pos = strpos($c, $anchor);
if ($pos === false) { echo "FAIL: khong tim thay case admin-delete-service.\n"; exit(1); }
$breakPos = strpos($c, 'break;', $pos);
if ($breakPos === false) { echo "FAIL: khong tim thay break sau admin-delete-service.\n"; exit(1); }
$insertAt = $breakPos + strlen('break;');
$newRoutes = "
    case 'admin-undo-delete-service':
        requireAdmin();
        (new AdminController())->undoDeleteService(\$id);
        break;
    case 'admin-cancel-price-change':
        requireAdmin();
        (new AdminController())->cancelPriceChange(\$id);
        break;";
$c2 = substr_replace($c, $newRoutes, $insertAt, 0);
file_put_contents($f, $c2);
echo "OK: da them 2 routes.\n";