<?php
$base = __DIR__ . '/';
$log = []; $allOk = true;
function putLog(&$ok,$l,$s){ global $log; $log[] = ($s?'OK: ':'FAIL: ').$l; $ok=$ok&&$s; }
function rep(&$c,$search,$replace,$label,&$ok){ $n=0; $c2=str_replace($search,$replace,$c,$n); if($n>=1){$c=$c2;putLog($ok,$label,true);} else putLog($ok,$label,false); }

// ===== BACKEND: saveService chon thang ap dung + giu applies_to/unit =====
$f=$base.'controllers/AdminController.php'; $c=file_get_contents($f); copy($f,$f.'.bak3o');
rep($c,
"\$core['price'] = (float)\$existing['price'];
\$core['billing_mode'] = (string)\$existing['billing_mode'];",
"\$core['price'] = (float)\$existing['price'];
\$core['billing_mode'] = (string)\$existing['billing_mode'];
\$core['applies_to'] = (string)(\$existing['applies_to'] ?? 'room');
\$core['unit'] = ServiceModel::deriveUnit((string)(\$existing['kind'] ?? 'other'), (string)\$existing['billing_mode']);", 'BE giu applies_to/unit', $allOk);
rep($c,
"\$nextMonth = (int)date('n') + 1;
\$nextYear = (int)date('Y');
if (\$nextMonth > 12) { \$nextMonth = 1; \$nextYear++; }",
"\$em = (int)(\$_POST['effective_month'] ?? 0); \$ey = (int)(\$_POST['effective_year'] ?? 0);
\$curOrder = ((int)date('Y')*100)+(int)date('n');
if (\$em >= 1 && \$em <= 12 && \$ey >= (int)date('Y') && (\$ey*100+\$em) > \$curOrder) { \$nextMonth=\$em; \$nextYear=\$ey; }
else { \$nextMonth = (int)date('n') + 1; \$nextYear = (int)date('Y'); if (\$nextMonth > 12) { \$nextMonth = 1; \$nextYear++; } }", 'BE chon thang ap dung', $allOk);
file_put_contents($f,$c);

// ===== VIEW =====
$f=$base.'views/admin/billing/services.php'; $c=file_get_contents($f); copy($f,$f.'.bak3o');
$grid = '<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">';
$tipOpen = '<div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">';
$iconLabel = '>Icon Material</label>';
// C: xoa tooltip block
$tip = strpos($c, $tipOpen); $icon = strpos($c, $iconLabel);
if ($tip!==false && $icon!==false) {
  $iconDiv = strrpos(substr($c,0,$icon), '<div>');
  if ($iconDiv!==false && $iconDiv > $tip) { $c = substr($c,0,$tip).substr($c,$iconDiv); putLog($allOk,'V xoa tooltip',true); }
  else putLog($allOk,'V xoa tooltip',false);
} else putLog($allOk,'V xoa tooltip',false);
// B: thay grid2 (cach tinh + doi tuong) bang cach-tinh-only
$g1 = strpos($c,$grid); $tip = strpos($c,$tipOpen);
if ($g1!==false && $tip!==false && $tip>$g1) {
  $g2 = strpos($c,$grid,$g1+1);
  if ($g2!==false && $g2<$tip) {
    $blockB = <<<'EOT'
<!-- BLOCK-B -->
<div class="grid grid-cols-1 gap-4">
<div>
<label class="block text-sm font-semibold mb-2">Cách tính giá</label>
<?php $formKind = $formService['kind'] ?? 'other'; ?>
<?php $allowedModeValues = (isset($kindBillingModes) && is_array($kindBillingModes) && array_key_exists($formKind, $kindBillingModes)) ? $kindBillingModes[$formKind] : array_column($serviceBillingModes ?? [], 'value'); ?>
<select name="billing_mode" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
<?php foreach (($serviceBillingModes ?? []) as $mode): ?>
<?php if (in_array($mode['value'], $allowedModeValues, true)): ?>
<option value="<?= e($mode['value']) ?>" <?= ($formService['billing_mode'] ?? 'fixed') === $mode['value'] ? 'selected' : '' ?>><?= e($mode['label']) ?></option>
<?php endif; ?>
<?php endforeach; ?>
</select>
<?php if (ServiceModel::isLockedKind($formKind)): ?>
<p class="text-xs text-amber-700 mt-1 flex items-center gap-1"><span class="material-symbols-outlined text-sm">lock</span>Cách tính đã khóa theo loại dịch vụ bắt buộc.</p>
<?php endif; ?>
</div>
</div>
EOT;
    $c = substr($c,0,$g2).$blockB.substr($c,$tip); putLog($allOk,'V bo doi tuong ap dung',true);
  } else putLog($allOk,'V bo doi tuong ap dung',false);
} else putLog($allOk,'V bo doi tuong ap dung',false);
// A: thay grid1 (gia+donvi) bang gia + thang ap dung
$g1 = strpos($c,$grid); $g2 = strpos($c,'<!-- BLOCK-B -->');
if ($g1!==false && $g2!==false) {
  $blockA = <<<'EOT'
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
<div>
<label class="block text-sm font-semibold mb-2">Giá</label>
<input type="number" min="0" step="0.01" name="price" value="<?= e($formService['price'] ?? 0) ?>" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
</div>
<div>
<label class="block text-sm font-semibold mb-2">Tháng áp dụng (giá / cách tính mới)</label>
<div class="grid grid-cols-2 gap-2">
<select name="effective_month" class="w-full px-3 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
<option value="0">Tháng sau (mặc định)</option>
<?php for ($m = 1; $m <= 12; $m++): ?><option value="<?= $m ?>">Tháng <?= $m ?></option><?php endfor; ?>
</select>
<input type="number" name="effective_year" min="<?= (int)date('Y') ?>" max="<?= (int)date('Y') + 5 ?>" value="<?= (int)date('Y') ?>" class="w-full px-3 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none">
</div>
<p class="text-xs text-gray-500 mt-1">Giá/cách tính mới không áp dụng cho tháng hiện tại.</p>
</div>
</div>
EOT;
  $c = substr($c,0,$g1).$blockA.substr($c,$g2); putLog($allOk,'V gia + thang ap dung',true);
} else putLog($allOk,'V gia + thang ap dung',false);
// D: locked/checkbox -> chi giu is_active cho dich vu thuong
$lockOpen = "<?php if (ServiceModel::isLockedKind(\$formService['kind'] ?? 'other')): ?>";
$lo = strpos($c,$lockOpen);
if ($lo!==false) {
  $le = strpos($c,'<?php endif; ?>',$lo);
  if ($le!==false) {
    $blockD = <<<'EOT'
<?php if (ServiceModel::isLockedKind($formService['kind'] ?? 'other')): ?>
<div class="px-4 py-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 text-sm flex items-center gap-2">
<span class="material-symbols-outlined text-base">lock</span>
Dịch vụ bắt buộc (điện / nước / rác): luôn bật và luôn bắt buộc — không thể tắt hay xóa.
</div>
<input type="hidden" name="is_required" value="1">
<input type="hidden" name="is_active" value="1">
<?php else: ?>
<label class="inline-flex w-full items-center justify-between gap-3 px-4 py-3 rounded-xl border border-gray-200 bg-gray-50">
<div>
<p class="text-sm font-semibold text-gray-800">Đang kinh doanh</p>
<p class="text-xs text-gray-500">Tắt đi để ẩn khỏi danh sách đăng ký.</p>
</div>
<input type="checkbox" name="is_active" value="1" <?= !empty($formService['is_active']) ? 'checked' : '' ?> class="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary">
</label>
<?php endif; ?>
EOT;
    $c = substr($c,0,$lo).$blockD.substr($c,$le+strlen('<?php endif; ?>')); putLog($allOk,'V khoa checkbox',true);
  } else putLog($allOk,'V khoa checkbox',false);
} else putLog($allOk,'V khoa checkbox',false);
// Row: pending delete badge + hoan tac
rep($c,
"\$applies = \$appliesToMeta[\$item['applies_to'] ?? 'room'] ?? \$appliesToMeta['room'];",
"\$applies = \$appliesToMeta[\$item['applies_to'] ?? 'room'] ?? \$appliesToMeta['room'];
\$pendDel = !empty(\$pendingDeleteByService[(int)(\$item['id'] ?? 0)]);", 'V row pendDel', $allOk);
rep($c,
'<a href="<?= BASE_URL ?>?page=admin-services&edit=<?= (int)($item['id'] ?? 0) ?>',
'<?php if (!empty($pendDel)): ?>
<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">Sẽ xóa tháng sau</span>
<form method="POST" action="<?= BASE_URL ?>?page=admin-undo-delete-service&id=<?= (int)($item['id'] ?? 0) ?>" class="inline"><?= csrf_field() ?><button type="submit" class="text-green-700 hover:text-green-800 font-semibold text-sm">Hoàn tác</button></form>
<?php else: ?>
<a href="<?= BASE_URL ?>?page=admin-services&edit=<?= (int)($item['id'] ?? 0) ?>', 'V row sua/undo', $allOk);
file_put_contents($f,$c);
echo implode("\n",$log)."\n";
echo $allOk ? "PATCH 3O HOAN TAT.\n" : "CO BUOC FAIL - KIEM TRA.\n";