<?php
require_once 'core/db.php';

$pageName = 'Student';
$stmt = $pdo->prepare("SELECT id FROM sys_pages WHERE page_name = ?");
$stmt->execute([$pageName]);
$page = $stmt->fetch();

if ($page) {
    $pageId = $page['id'];
    echo "Found page '$pageName' with ID $pageId\n";
    
    $rolesToGrant = ['super_admin', 'student', 'librarian'];
    foreach ($rolesToGrant as $role) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES (?, ?)");
        $stmt->execute([$role, $pageId]);
        echo "Granted access to role '$role' for page ID $pageId\n";
    }
} else {
    echo "Page '$pageName' not found.\n";
}
