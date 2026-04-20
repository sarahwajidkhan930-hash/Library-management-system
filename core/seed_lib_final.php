<?php
require 'd:/xampp/htdocs/universal/core/db.php';

try {
    $email = 'librarian@university.edu';
    $name = 'Professional Librarian';
    $password = password_hash('lib123', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, registration_no, password, role, is_active) VALUES (?, ?, 'LIB-001', ?, 'librarian', 1)");
        $stmt->execute([$name, $email, $password]);
        echo "Created Professional Librarian account.\n";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET password = ?, role = 'librarian', is_active = 1 WHERE email = ?");
        $stmt->execute([$password, $email]);
        echo "Updated existing Librarian account with new password.\n";
    }
    
    echo "Login Credentials:\n";
    echo "Email: $email\n";
    echo "Password: lib123\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
