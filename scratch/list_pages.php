<?php
require_once 'core/db.php';
$stmt = $pdo->query('SELECT id, page_name, page_url, parent_id, icon_class FROM sys_pages');
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($pages);
