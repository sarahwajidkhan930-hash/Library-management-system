<?php
/**
 * ajax_book_details.php
 * Fetch full book details for modal view (JSON)
 */
require_once '../../core/db.php';
require_once '../../core/library_functions.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

$allowedRoles = ['librarian', 'assistant_manager', 'super_admin'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $allowedRoles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$bookId = (int) ($_GET['id'] ?? 0);

if ($bookId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Book ID.']);
    exit;
}

try {
    $lib = new Library($pdo);
    $book = $lib->getBookById($bookId);
    
    if (!$book) {
        echo json_encode(['success' => false, 'message' => 'Book not found.']);
        exit;
    }

    // Fetch active borrowings for history
    $stmt = $pdo->prepare("
        SELECT br.id, br.borrow_date, br.due_date, br.status, u.name as student_name 
        FROM lib_borrowings br
        JOIN users u ON br.user_id = u.id
        WHERE br.book_id = ? AND br.status != 'returned'
        ORDER BY br.borrow_date DESC
        LIMIT 5
    ");
    $stmt->execute([$bookId]);
    $activeLoans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true, 
        'book' => $book,
        'active_loans' => $activeLoans
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
