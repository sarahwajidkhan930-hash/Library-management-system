<?php
require_once '../../core/session.php';
require_once '../../core/db.php';

header('Content-Type: application/json');

if (!in_array($_SESSION['role'], ['librarian', 'super_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'update_request') {
        $id = (int)$_POST['id'];
        $title = trim($_POST['book_title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        $isbn = trim($_POST['isbn'] ?? '');
        $status = $_POST['status'] ?? 'pending';
        $admin_notes = trim($_POST['admin_notes'] ?? '');
        $reviewerId = $_SESSION['user_id'];

        if(empty($title) || empty($author)) {
            echo json_encode(['success' => false, 'message' => 'Title and Author are required.']);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE book_requests 
            SET book_title = ?, author = ?, genre = ?, isbn = ?, status = ?, admin_notes = ?, reviewed_by = ?, reviewed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$title, $author, $genre, $isbn, $status, $admin_notes, $reviewerId, $id]);

        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
