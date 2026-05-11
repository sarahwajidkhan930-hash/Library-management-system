<?php
require_once 'core/db.php';
$stmt = $pdo->query('SELECT page_id, role_key FROM role_access WHERE page_id IN (38, 39, 40, 41)');
$access = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($access);
