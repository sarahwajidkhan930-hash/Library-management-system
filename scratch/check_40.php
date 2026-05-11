<?php
require_once 'core/db.php';
$stmt = $pdo->query('SELECT * FROM role_access WHERE page_id=40');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
