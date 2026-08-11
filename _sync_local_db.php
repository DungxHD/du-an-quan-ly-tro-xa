<?php
$pdo = new PDO('mysql:host=localhost;dbname=manage;charset=utf8mb4','root','', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$cols = $pdo->query("SHOW COLUMNS FROM rooms")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('notice_given', $cols)) { $pdo->exec("ALTER TABLE rooms ADD COLUMN notice_given tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = tenant da bao chuyen di' AFTER status"); echo "+ rooms.notice_given\n"; }
if (!in_array('expected_vacant_date', $cols)) { $pdo->exec("ALTER TABLE rooms ADD COLUMN expected_vacant_date date DEFAULT NULL AFTER notice_given"); echo "+ rooms.expected_vacant_date\n"; }
$tabs = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('maintenance_requests', $tabs)) {
    $theirs = shell_exec('git show :3:database.sql 2>nul') ?: shell_exec('git show 7cacdff1d742b89cce133e72e8da8aa4c617c18a:database.sql 2>nul');
    if ($theirs && preg_match('/CREATE TABLE IF NOT EXISTS `maintenance_requests` \(.*?\)\s*ENGINE=InnoDB[^;]*;/s', $theirs, $m)) { $pdo->exec($m[0]); echo "+ bang maintenance_requests\n"; }
    else echo "[CANH BAO] Khong lay duoc cau truc maintenance_requests, lay thu cong tu ban cua ban ay.\n";
}
$fks = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA='manage' AND TABLE_NAME='roommate_requests' AND CONSTRAINT_TYPE='FOREIGN KEY'")->fetchAll(PDO::FETCH_COLUMN);
if (in_array('fk_rm_target', $fks)) { $pdo->exec("ALTER TABLE roommate_requests DROP FOREIGN KEY fk_rm_target"); echo "- FK gay fk_rm_target\n"; }
if (!in_array('fk_rm_host', $fks)) { $pdo->exec("ALTER TABLE roommate_requests ADD CONSTRAINT fk_rm_host FOREIGN KEY (host_user_id) REFERENCES users(id) ON DELETE CASCADE"); echo "+ FK fk_rm_host\n"; }
echo "HOAN TAT dong bo DB local.\n";
