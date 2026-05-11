<?php
require_once 'core/db.php';

echo "--- Student Related Pages ---\n";
$stmt = $pdo->query("SELECT * FROM sys_pages WHERE page_url LIKE '%student%' OR page_name LIKE '%Student%'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
