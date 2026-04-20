<?php
require_once 'core/config.php';
require_once 'core/db.php';

try {
    // Set one book to be overdue by 8 days for demonstration
    $pastDate = date('Y-m-d', strtotime('-8 days'));
    $stmt = $pdo->prepare("UPDATE lib_borrowings SET due_date = ?, status = 'borrowed' WHERE user_id = 1 LIMIT 1");
    $stmt->execute([$pastDate]);
    
    echo "SUCCESS: One loan has been moved to 8 days ago. Please refresh the dashboard.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
