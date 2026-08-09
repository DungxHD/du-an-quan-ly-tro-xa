<?php
$base = __DIR__ . '/';
$f = $base . 'views/admin/billing/services.php';
$c = file_get_contents($f);
copy($f, $f . '.bak_nhom3i');
$anchorReq = strpos($c, 'name="is_required"');
if ($anchorReq === false) { echo "FAIL: khong thay name=is_required\n"; exit(1); }
$gridOpen = '<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">';
$startPos = strrpos(substr($c, 0, $anchorReq), $gridOpen);
if ($startPos === false) { echo "FAIL: khong thay grid open\n"; exit(1); }
$buttonPos = strpos($c, '<button type="submit"', $anchorReq);
if ($buttonPos === false) { echo "FAIL: khong thay button submit\n"; exit(1); }
$orig = substr($c, $startPos, $buttonPos - $startPos);
$ifOpen = <<<'EOT'
<?php if (ServiceModel::isLockedKind($formService['kind'] ?? 'other')): ?>
<div class="px-4 py-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 text-sm flex items-center gap-2">
    <span class="material-symbols-outlined text-base">lock</span>
    Dịch vụ bắt buộc (điện / nước / rác): luôn bật và luôn bắt buộc — không thể tắt hay xóa.
</div>
<input type="hidden" name="is_required" value="1">
<input type="hidden" name="is_active" value="1">
<?php else: ?>
EOT;
$new = $ifOpen . "\n" . $orig . "<?php endif; ?>\n";
$c2 = substr($c, 0, $startPos) . $new . substr($c, $buttonPos);
file_put_contents($f, $c2);
echo "OK: view services.php da khoa checkbox cho dich vu bat buoc.\n";