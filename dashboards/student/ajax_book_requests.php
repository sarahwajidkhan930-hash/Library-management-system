<?php
require_once '../../core/session.php';
require_once '../../core/db.php';

header('Content-Type: application/json');

if (!in_array($_SESSION['role'], ['student', 'librarian', 'super_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$action = $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    if ($action === 'submit_request') {
        $title = trim($_POST['book_title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        $isbn = trim($_POST['isbn'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $priority = $_POST['priority'] ?? 'normal';

        if(empty($title) || empty($author) || empty($reason)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO book_requests (user_id, book_title, author, genre, isbn, reason, priority, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$userId, $title, $author, $genre, $isbn, $reason, $priority]);

        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'cancel_request') {
        $id = (int)($_POST['id'] ?? 0);
        
        // Verify ownership and status
        $stmt = $pdo->prepare("SELECT status FROM book_requests WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $req = $stmt->fetch();
        
        if(!$req || $req['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'Cannot cancel this request.']);
            exit;
        }
        
        $pdo->prepare("UPDATE book_requests SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
