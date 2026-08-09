<?php
$base = __DIR__ . '/';
echo "=== 1. LEFTOVER goi saveChange() (ngoai tru .bak) ===\n";
$found = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    if ($file->getExtension() !== 'php') continue;
    if (strpos($file->getFilename(), '.bak') !== false) continue;
    $content = file_get_contents($file->getPathname());
    if (preg_match('/\bsaveChange\s*\(/', $content)) {
        echo "FOUND: " . str_replace($base, '', $file->getPathname()) . "\n";
        $found++;
    }
}
echo ($found === 0 ? "OK: khong con noi nao goi saveChange()\n" : "CANH BAO NGUY HIEM: con $found file goi saveChange() -> se fatal error!\n");

echo "\n=== 2. services.kind cho cac dich vu bat buoc ===\n";
try {
    $pdo = new PDO('mysql:host=localhost;dbname=manage;charset=utf8mb4','root','', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    foreach ($pdo->query("SELECT id,name,kind,billing_mode,is_required,is_active FROM services ORDER BY id") as $r) {
        $flag = ((int)$r['is_required']===1 && $r['kind']==='other') ? '  <-- LOAI: required ma kind=other!' : '';
        echo "svc#{$r['id']} {$r['name']} kind={$r['kind']} mode={$r['billing_mode']} req={$r['is_required']} act={$r['is_active']}{$flag}\n";
    }
    echo "\n=== 3. price_changes se TU DONG AP DUNG khi mo trang (applied=0, effective <= thang nay) ===\n";
    $cur = ((int)date('Y')*100)+(int)date('n');
    $auto = 0;
    foreach ($pdo->query("SELECT id,service_id,old_price,new_price,new_billing_mode,effective_month,effective_year FROM price_changes WHERE applied=0 ORDER BY effective_year,effective_month") as $r) {
        $ord = ((int)$r['effective_year']*100)+(int)$r['effective_month'];
        $will = $ord <= $cur;
        if ($will) $auto++;
        echo 'pc#'.$r['id'].' svc='.$r['service_id'].' '.$r['old_price'].'->'.$r['new_price'].' mode='.($r['new_billing_mode'] ?? '-').' eff='.$r['effective_month'].'/'.$r['effective_year'].' ['.($will?'SE-AUTO-APPLY':'pending')."]\n";
    }
    echo ($auto===0 ? "OK: khong co lich nao se tu ap dung ngay\n" : "CANH BAO: $auto lich se tu dong doi gia khi mo trang ke tiep!\n");
} catch (Throwable $e) { echo "DB ERROR: ".$e->getMessage()."\n"; }