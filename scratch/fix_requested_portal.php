<?php
require_once 'core/db.php';
try {
    $pdo->beginTransaction();
    // Delete duplicate/empty portal
    $pdo->exec("DELETE FROM sys_pages WHERE id = 38");
    // Update main Request Portal
    $pdo->exec("UPDATE sys_pages SET page_name = 'Requested Portal', icon_class = 'bi bi-bag-plus-fill' WHERE id = 39");
    // Update student request page
    $pdo->exec("UPDATE sys_pages SET page_name = 'Student Request', icon_class = 'bi bi-journal-plus' WHERE id = 40");
    // Update librarian manage requests page
    $pdo->exec("UPDATE sys_pages SET page_name = 'Manage Requests', page_url = 'dashboards/librarian/manage_requests.php', icon_class = 'bi bi-clipboard2-check-fill' WHERE id = 41");
    $pdo->commit();
    echo "Success";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Failed: " . $e->getMessage();
}
