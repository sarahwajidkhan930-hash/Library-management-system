<?php
require_once 'core/db.php';
try {
    // Add librarian access to the student request form (page 40)
    $stmt = $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('librarian', 40)");
    $stmt->execute();
    echo "Access granted.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
