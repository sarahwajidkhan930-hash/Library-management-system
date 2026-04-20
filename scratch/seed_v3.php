<?php
require_once 'core/db.php';
$dueDate = date('Y-m-d', strtotime('+14 days'));
$stmt = $pdo->prepare("INSERT INTO lib_borrowings (user_id, book_id, borrow_date, due_date, status) VALUES (1, 1, CURDATE(), ?, 'borrowed'), (1, 2, CURDATE(), ?, 'borrowed')");
$stmt->execute([$dueDate, $dueDate]);
echo "Seeded for User 1 (Root Admin)\n";
