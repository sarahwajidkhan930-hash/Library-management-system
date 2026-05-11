<?php
require_once 'core/db.php';
$stmt = $pdo->query('SHOW COLUMNS FROM lib_authors');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
