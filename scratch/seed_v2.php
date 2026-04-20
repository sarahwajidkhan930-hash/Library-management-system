<?php
require_once 'core/db.php'; // Direct DB connection
require_once 'core/library_functions.php';

$lib = new Library($pdo);
$dueDate = date('Y-m-d', strtotime('+14 days'));

// Seed both Ahmad (6) and Sara (7) to be sure
$testUsers = [6, 7];
$booksToIssue = [1, 2, 3];

foreach ($testUsers as $studentId) {
    echo "Processing Student ID $studentId...\n";
    foreach ($booksToIssue as $bookId) {
        $stmt = $pdo->prepare("INSERT INTO lib_borrowings (user_id, book_id, borrow_date, due_date, status) VALUES (?, ?, CURDATE(), ?, 'borrowed')");
        if ($stmt->execute([$studentId, $bookId, $dueDate])) {
            // Also decrement available copies to keep it realistic
            $pdo->prepare("UPDATE lib_books SET available_copies = available_copies - 1 WHERE id = ?")->execute([$bookId]);
            echo "Successfully issued Book $bookId to Student $studentId\n";
        }
    }
}
echo "Seeding complete.\n";
