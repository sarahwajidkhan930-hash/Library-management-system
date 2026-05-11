<?php
require_once '../../core/config.php';
require_once '../../core/db.php';
require_once '../../core/library_functions.php';
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['librarian', 'assistant_manager', 'super_admin'])) {
    echo json_encode(['success' => false]);
    exit();
}

$barcode = $_GET['code'] ?? '';
if (empty($barcode)) {
    echo json_encode(['success' => false]);
    exit;
}

// 1. Try to find a student (User ID, identity_no, email)
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'student' AND (id = ? OR identity_no = ? OR email = ?)");
$stmt->execute([$barcode, $barcode, $barcode]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student) {
    echo json_encode(['success' => true, 'type' => 'student', 'id' => $student['id'], 'name' => $student['name']]);
    exit;
}

// 2. Try to find a book (Book ID or ISBN)
$stmt = $pdo->prepare("SELECT id, title, available_copies FROM lib_books WHERE id = ? OR isbn = ?");
$stmt->execute([$barcode, $barcode]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if ($book) {
    if ($book['available_copies'] > 0) {
        echo json_encode(['success' => true, 'type' => 'book', 'id' => $book['id'], 'title' => $book['title']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Book found but is out of stock.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Barcode not found.']);
?>
