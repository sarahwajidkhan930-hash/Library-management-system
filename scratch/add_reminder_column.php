<?php
require_once 'core/db.php';
try {
    $pdo->exec("ALTER TABLE lib_borrowings ADD COLUMN last_reminded_at TIMESTAMP NULL DEFAULT NULL AFTER status");
    echo "Column last_reminded_at added successfully";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
