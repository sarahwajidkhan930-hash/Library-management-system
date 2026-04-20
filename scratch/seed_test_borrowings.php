<?php
require_once 'core/session.php'; // For DB connection
require_once 'core/library_functions.php';

$lib = new Library($pdo);
$studentId = 6; // Ahmad
$bookIds = [1, 2]; // Harry Potter & Game of Thrones
$dueDate = date('Y-m-d', strtotime('+14 days'));

echo "Seeding test borrowings for Student ID $studentId...\n";

foreach ($bookIds as $id) {
    $result = $lib->checkOutBook($id, $studentId, $dueDate);
    if ($result['success']) {
        echo "Successfully issued Book ID $id to Student.\n";
    } else {
        echo "Failed to issue Book ID $id: " . $result['message'] . "\n";
    }
}

echo "Done!\n";
