<?php
$base = __DIR__ . '/';
$skip = ['check_risk_nhom3.php','recheck_nhom3.php','patch_nhom3b.php','patch_nhom3d.php','patch_nhom3e.php','patch_nhom3f.php','verify_nhom3.php','diag_nhom3.php'];
$found = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    if ($file->getExtension() !== 'php') continue;
    $n = $file->getFilename();
    if (strpos($n, '.bak') !== false || in_array($n, $skip, true)) continue;
    if (preg_match('/\bsaveChange\s*\(/', file_get_contents($file->getPathname()))) {
        echo "FOUND: " . str_replace($base, '', $file->getPathname()) . "\n"; $found++;
    }
}
echo ($found === 0 ? "OK: code that khong con goi saveChange()\n" : "CANH BAO: $found file\n");
try {
    $pdo = new PDO('mysql:host=localhost;dbname=manage;charset=utf8mb4','root','', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    echo "\nGia hien tai cua dich vu:\n";
    foreach ($pdo->query("SELECT id,name,kind,price FROM services ORDER BY id") as $r)
        echo "svc#{$r['id']} {$r['name']} kind={$r['kind']} price={$r['price']}\n";
} catch (Throwable $e) { echo "DB ERROR: ".$e->getMessage()."\n"; }