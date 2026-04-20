<?php
require_once '../../core/config.php';
require_once '../../core/db.php';
require_once '../../core/audit_helper.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

// Security check: Only librarians and super admins can adjust limits
$allowedRoles = ['librarian', 'super_admin'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $allowedRoles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$userId = (int) ($_POST['user_id'] ?? 0);
$newLimit = (int) ($_POST['new_limit'] ?? 5);

if ($userId <= 0 || $newLimit < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET borrow_limit = ? WHERE id = ?");
    if ($stmt->execute([$newLimit, $userId])) {
        logAction('LIMIT_ADJUSTED', "Borrowing limit for User ID $userId updated to $newLimit by " . $_SESSION['role']);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update database.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
