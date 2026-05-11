<?php
require_once 'core/db.php';

echo "=== ALL SYS_PAGES ===\n";
$stmt = $pdo->query("SELECT * FROM sys_pages ORDER BY sort_order ASC, id ASC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    printf("[ID:%2d] parent=%2d | sort=%3d | %-40s | %-45s | %s\n",
        $row['id'], $row['parent_id'], $row['sort_order'],
        $row['page_name'], $row['page_url'], $row['icon_class']
    );
}

echo "\n=== ALL ROLE_ACCESS ===\n";
$stmt = $pdo->query("SELECT ra.role_key, p.page_name, ra.page_id FROM role_access ra JOIN sys_pages p ON ra.page_id = p.id ORDER BY ra.role_key, p.sort_order");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    printf("role=%-20s | page_id=%2d | %s\n", $row['role_key'], $row['page_id'], $row['page_name']);
}
