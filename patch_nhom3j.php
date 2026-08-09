<?php
$base = __DIR__ . '/';
$f = $base . 'views/admin/billing/services.php';
$c = file_get_contents($f);
copy($f, $f . '.bak_nhom3j');
$namePos = strpos($c, 'name="billing_mode"');
if ($namePos === false) { echo "FAIL: khong thay name=billing_mode\n"; exit(1); }
$selectPos = strrpos(substr($c, 0, $namePos), '<select');
if ($selectPos === false) { echo "FAIL: khong thay <select truoc billing_mode\n"; exit(1); }
$closePos = strpos($c, '</select>', $namePos);
if ($closePos === false) { echo "FAIL: khong thay </select>\n"; exit(1); }
$endPos = $closePos + strlen('</select>');
$new = <<<'EOT'
<?php $formKind = $formService['kind'] ?? 'other'; ?>
<?php $allowedModeValues = (isset($kindBillingModes) && is_array($kindBillingModes) && array_key_exists($formKind, $kindBillingModes)) ? $kindBillingModes[$formKind] : array_column($serviceBillingModes ?? [], 'value'); ?>
<select
                                name="billing_mode"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none"
                            >
                                <?php foreach (($serviceBillingModes ?? []) as $mode): ?>
                                <?php if (in_array($mode['value'], $allowedModeValues, true)): ?>
                                <option value="<?= e($mode['value']) ?>" <?= ($formService['billing_mode'] ?? 'fixed') === $mode['value'] ? 'selected' : '' ?>>
                                    <?= e($mode['label']) ?>
                                </option>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <?php if (ServiceModel::isLockedKind($formKind)): ?>
                            <p class="text-xs text-amber-700 mt-1 flex items-center gap-1"><span class="material-symbols-outlined text-sm">lock</span>Cách tính đã khóa theo loại dịch vụ bắt buộc.</p>
                            <?php endif; ?>
EOT;
$c2 = substr($c, 0, $selectPos) . $new . substr($c, $endPos);
file_put_contents($f, $c2);
echo "OK: view services.php da loc billing_mode theo kind.\n";