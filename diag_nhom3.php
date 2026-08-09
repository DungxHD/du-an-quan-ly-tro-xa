<?php
$base = __DIR__ . '/';
echo "=== MARKERS ===\n";
$checks = [
 'models/billing/ServiceModel.php' => ['KINDS','isLockedKind',"'kind' =>"],
 'models/billing/PriceChangeModel.php' => ['scheduleServiceChange','applyDueChanges','getEffectiveConfigForPeriod',"'applied'"],
 'controllers/AdminController.php' => ['applyDueChanges','scheduleServiceChange','kindBillingModes'],
 'models/billing/PaymentModel.php' => ['getEffectiveConfigForPeriod'],
 'models/billing/MeterReadingModel.php' => ["case 'electricity'"],
 'index.php' => ["'id' => 'services', 'label' => 'Dịch vụ'","group-services"],
];
foreach ($checks as $rel => $ms) {
  $c = is_file($base.$rel) ? file_get_contents($base.$rel) : '';
  foreach ($ms as $m) echo (strpos($c,$m)!==false?'FOUND   ':'MISSING ').$rel.' :: '.$m."\n";
}
echo "\n=== BRACE STYLE (AdminController) ===\n";
$ac = file_get_contents($base.'controllers/AdminController.php');
echo (preg_match('/function services\(\)\s*\n\s*\{/',$ac)?"ALLMAN (ngoặc xuống dòng)\n":(preg_match('/function services\(\)\s*\{/',$ac)?"K&R (ngoặc cùng dòng)\n":"KHONG XAC DINH\n"));
echo "\n=== SYNTAX ===\n";
foreach (array_keys($checks) as $rel) echo trim((string)shell_exec('php -l "'.$base.$rel.'" 2>&1'))."\n";
echo "\n=== DB ===\n";
try {
  $pdo = new PDO('mysql:host=localhost;dbname=manage;charset=utf8mb4','root','', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  $cs = $pdo->query("SHOW COLUMNS FROM services")->fetchAll(PDO::FETCH_COLUMN);
  echo 'services cols: '.implode(', ',$cs)."\n";
  $pc = $pdo->query("SHOW COLUMNS FROM price_changes")->fetchAll(PDO::FETCH_COLUMN);
  echo 'price_changes cols: '.implode(', ',$pc)."\n";
  $hasKind = in_array('kind',$cs);
  $sql = 'SELECT id,name,'.($hasKind?'kind,':''). 'billing_mode,is_required,is_active,price FROM services';
  foreach ($pdo->query($sql) as $r) echo 'svc#'.$r['id'].' '.$r['name'].' kind='.($r['kind']??'-').' mode='.$r['billing_mode'].' req='.$r['is_required'].' act='.$r['is_active'].' price='.$r['price']."\n";
  echo 'price_changes rows: '.$pdo->query('SELECT COUNT(*) FROM price_changes')->fetchColumn()."\n";
  echo 'meter_readings rows: '.$pdo->query('SELECT COUNT(*) FROM meter_readings')->fetchColumn()."\n";
} catch (Throwable $e) { echo 'DB ERROR: '.$e->getMessage()."\n"; }