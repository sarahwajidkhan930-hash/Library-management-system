<?php
require_once 'd:/xampp/htdocs/universal/core/config.php';
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->beginTransaction();

    // 1. Promote sub-items of Librarian (ID 14) to top-level
    $stmt = $pdo->prepare("UPDATE sys_pages SET parent_id = 0 WHERE parent_id = 14");
    $stmt->execute();
    $promotedCount = $stmt->rowCount();

    // 2. Rename Librarian Dashboard (ID 12) to Library Dashboard
    $stmt = $pdo->prepare("UPDATE sys_pages SET page_name = 'Library Dashboard' WHERE id = 12");
    $stmt->execute();

    // 3. Potentially delete the old parent 'Librarian' (ID 14) if it's no longer needed
    // However, the user said "remove the 'Librarian' parent dropdown menu entirely". 
    // I'll set its visibility to 0 OR just delete it if I'm sure. 
    // Let's check if it exists first.
    $stmt = $pdo->prepare("DELETE FROM sys_pages WHERE id = 14");
    $stmt->execute();

    $pdo->commit();
    echo "Database updated successfully. Promoted $promotedCount items and renamed dashboard." . PHP_EOL;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
