<?php
$v = file_get_contents('views/admin/rooms/rooms.php');
$modal = file_get_contents('_modal.html');
$eol = (strpos($v, "\r\n") !== false) ? "\r\n" : "\n";
$footer = "<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>";
$v = str_replace($footer, $modal . $eol . $footer, $v);
file_put_contents('views/admin/rooms/rooms.php', $v);
echo "[OK] Buoc 3: modal\n";