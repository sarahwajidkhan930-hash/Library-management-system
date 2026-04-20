<?php
require 'd:/xampp/htdocs/universal/core/db.php';
try {
    // 1. Add missing columns to users table (Implicit commits happen with ALTER TABLE)
    $sqlData = [
        "ALTER TABLE users ADD COLUMN department VARCHAR(100) DEFAULT 'General' AFTER role",
        "ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER email",
        "ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER is_active"
    ];

    foreach ($sqlData as $sql) {
        try {
            $pdo->exec($sql);
            echo "Success: Query executed." . PHP_EOL;
        } catch (PDOException $e) {
            echo "Notice: " . $e->getMessage() . PHP_EOL;
        }
    }

    // 2. Update existing test student
    $pdo->exec("UPDATE users SET department = 'Computer Science', phone = '+92 300 1234567' WHERE email = 'student@test.com'");

    echo "Migration completed!" . PHP_EOL;
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . PHP_EOL;
}
?>
