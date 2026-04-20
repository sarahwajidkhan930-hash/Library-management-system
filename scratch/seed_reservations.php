<?php
require_once 'core/db.php';
// Add 2 pending reservations for User ID 1 (Root Admin)
// Book 6 (The Psychology of Money) and Book 7 (The 7 Habits)
$stmt = $pdo->prepare("INSERT INTO lib_reservations (user_id, book_id, status) VALUES (1, 6, 'pending'), (1, 7, 'pending')");
$stmt->execute();
echo "Seeded 2 reservations for User 1\n";
