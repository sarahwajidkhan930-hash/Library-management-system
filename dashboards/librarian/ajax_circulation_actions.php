<?php
require_once '../../core/config.php';
require_once '../../core/db.php';
require_once '../../core/library_functions.php';
session_start();

// Security check: Only librarians can perform these actions
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['librarian', 'super_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$lib = new Library($pdo);
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'quick_return':
        $userId = $_POST['user_id'] ?? 0;
        $bookId = $_POST['book_id'] ?? 0;
        
        $borrowingId = $lib->getActiveBorrowingId($userId, $bookId);
        if ($borrowingId) {
            $res = $lib->checkInBook($borrowingId);
            echo json_encode($res);
        } else {
            echo json_encode(['success' => false, 'message' => 'No active borrowing record found for this asset.']);
        }
        break;

    case 'settle_fine':
        $userId = $_POST['user_id'] ?? 0;
        $res = $lib->settleStudentFines($userId);
        echo json_encode($res);
        break;

    case 'manual_fine':
        $userId = $_POST['user_id'] ?? 0;
        $bookId = $_POST['book_id'] ?? 0;
        $amount = $_POST['amount'] ?? 0;
        $reason = $_POST['reason'] ?? 'Manual override';
        
        if ($amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid amount.']);
            exit();
        }
        
        $res = $lib->manualImposeFine($userId, $bookId, $amount, $reason);
        echo json_encode($res);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
?>
