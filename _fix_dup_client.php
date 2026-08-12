<?php
$f = 'views/admin/billing/services.php';
$bak = $f . '.bak_' . date('Ymd_His');
copy($f, $bak);
echo "Backup: $bak\n";
$v = file_get_contents($f);

if (strpos($v, '[svc-dup-check]') !== false) {
    echo "[SKIP] Đã có check trùng tên client-side\n";
    exit(0);
}

$anchor = <<<'EOT'
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>
EOT;

$script = <<<'EOT'
<script>
// [svc-dup-check] Chan trung ten dich vu ngay khi submit (phia client)
(function(){
  var existingServices = <?= json_encode(array_values(array_map(function($s){ return ['id' => (int)($s['id'] ?? 0), 'name' => trim((string)($s['name'] ?? ''))]; }, $services ?? [])), JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;
  var form = document.querySelector('#service-drawer form');
  if (!form) return;
  var nameInput = form.querySelector('input[name="name"]');
  if (!nameInput) return;
  var errEl = document.createElement('p');
  errEl.className = 'text-sm text-red-600 mt-1 hidden';
  nameInput.closest('div').appendChild(errEl);
  nameInput.addEventListener('input', function(){
    errEl.classList.add('hidden');
    nameInput.classList.remove('border-red-300','bg-red-50');
  });
  form.addEventListener('submit', function(e){
    var val = (nameInput.value || '').trim();
    if (val === '') return;
    var idInput = form.querySelector('input[name="id"]');
    var cur = idInput ? (parseInt(idInput.value, 10) || 0) : 0;
    var dup = existingServices.some(function(s){ return s.id !== cur && s.name === val; });
    if (dup) {
      e.preventDefault();
      e.stopImmediatePropagation();
      errEl.textContent = 'Tên dịch vụ "' + val + '" đã tồn tại. Vui lòng chọn tên khác.';
      errEl.classList.remove('hidden');
      nameInput.classList.add('border-red-300','bg-red-50');
      nameInput.focus();
    }
  });
})();
</script>
EOT;

if (strpos($v, $anchor) !== false) {
    $v = str_replace($anchor, $script . "\n" . $anchor, $v);
    file_put_contents($f, $v);
    echo "[OK] Đã thêm check trùng tên client-side (chặn khi Submit)\n";
} else {
    echo "[FAIL] Không tìm thấy anchor panel_footer\n";
}
