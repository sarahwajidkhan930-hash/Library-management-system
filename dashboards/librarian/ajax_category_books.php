<?php
/**
 * ajax_category_books.php
 * Fetch books for a specific category (JSON)
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

$catId = (int) ($_GET['cat_id'] ?? 0);

if ($catId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Category ID.']);
    exit;
}

try {
    $lib = new Library($pdo);
    $books = $lib->getBooksByCategory($catId);
    
    echo json_encode(['success' => true, 'books' => $books]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
