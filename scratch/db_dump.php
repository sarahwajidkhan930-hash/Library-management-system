<?php
require_once 'core/db.php';

echo "--- SYS_PAGES (Search: student_dashboard) ---\n";
$stmt = $pdo->query("SELECT * FROM sys_pages WHERE page_url LIKE '%student_dashboard.php%'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}


