<?php
require_once 'core/db.php';
$stmt = $pdo->query('SHOW COLUMNS FROM book_requests');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($columns as $c) {
    echo $c['Field'] . " - " . $c['Type'] . "\n";
}
