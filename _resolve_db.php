<?php
$ours   = shell_exec('git show :2:database.sql 2>nul');
if (!$ours)   $ours   = shell_exec('git show HEAD:database.sql 2>nul');
$theirs = shell_exec('git show :3:database.sql 2>nul');
if (!$theirs) $theirs = shell_exec('git show 7cacdff1d742b89cce133e72e8da8aa4c617c18a:database.sql 2>nul');
if (!$ours || !$theirs) { echo "[FAIL] Khong doc duoc 2 ban tu git\n"; exit(1); }

// --- Lay nguyen van maintenance_requests tu ban cua ban ay ---
if (!preg_match('/CREATE TABLE IF NOT EXISTS `maintenance_requests` \(.*?\)\s*ENGINE=InnoDB[^;]*;/s', $theirs, $m)) {
    echo "[FAIL] Khong tim thay maintenance_requests trong ban incoming. Paste thu cong section 25 cua ban ay.\n"; exit(1);
}
$maintenance = $m[0];
$out = $ours;

// --- Ghep 1: rooms + 2 cot cua nhom 2 ---
$roomsStatus = "`status` enum('draft','available','rented','maintenance') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',";
if (strpos($out, $roomsStatus) === false) { echo "[FAIL] Khong tim thay dong status cua rooms\n"; exit(1); }
if (strpos($out, 'notice_given') === false) {
    $out = str_replace($roomsStatus, $roomsStatus
        . "\n  `notice_given` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = tenant da bao chuyen di',"
        . "\n  `expected_vacant_date` date DEFAULT NULL COMMENT 'Ngay du kien trong (khi notice_given=1)',", $out);
}

// --- Ghep 2: sua FK gay cua roommate_requests ---
$badFk  = "CONSTRAINT `fk_rm_target` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE";
$goodFk = "CONSTRAINT `fk_rm_host` FOREIGN KEY (`host_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE";
if (strpos($out, $badFk) !== false) $out = str_replace($badFk, $goodFk, $out);

// --- Ghep 3: them bang maintenance_requests truoc footer ---
if (strpos($out, 'maintenance_requests') === false) {
    $footer = "/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;";
    if (strpos($out, $footer) === false) { echo "[FAIL] Khong tim thay footer\n"; exit(1); }
    $out = str_replace($footer, "-- Dumping structure for table manage.maintenance_requests\n" . $maintenance . "\n\n" . $footer, $out);
}

// --- Kiem tra an toan ---
$err = 0;
foreach (['<<<<<<<', '=======', '>>>>>>>'] as $mk) if (strpos($out, $mk) !== false) { echo "[FAIL] Con marker: $mk\n"; $err = 1; }
preg_match_all('/CREATE TABLE IF NOT EXISTS `(\w+)`/', $out, $mm);
$dup = array_unique(array_diff_assoc($mm[1], array_unique($mm[1])));
foreach (array_count_values($mm[1]) as $tbl => $n) if ($n > 1) { echo "[FAIL] Bang bi trung: $tbl x$n\n"; $err = 1; }
if ($err) { echo "KHONG ghi file. Sua tay truoc.\n"; exit(1); }

file_put_contents('database.sql', $out);
echo "[OK] database.sql da sach: " . count(array_unique($mm[1])) . " bang, khong marker, khong trung lap.\n";
