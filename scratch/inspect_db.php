<?php
require_once 'core/config.php';
require_once 'core/db.php';

try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Users Columns: " . implode(", ", $columns) . "\n";
    
    $stmt = $pdo->query("DESCRIBE lib_borrowings");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Borrowings Columns: " . implode(", ", $columns) . "\n";

    echo "\nData for User 1:\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = 1");
    $stmt->execute();
    print_r($stmt->fetch(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
