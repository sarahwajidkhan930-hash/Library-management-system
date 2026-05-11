<?php
require_once '../../core/session.php';
require_once '../../core/library_functions.php';

$lib = new Library($pdo);
$userId = $_SESSION['user_id'];

// Handle GET for Export
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'export_history') {
    $data = $lib->getExportData($userId);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=borrowing_history_' . $userId . '_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Book Title', 'Author', 'Borrow Date', 'Due Date', 'Return Date', 'Fine', 'Status']);
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Handle POST for actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Invalid action.'];

    try {
        if ($action === 'reserve') {
            $bookId = $_POST['book_id'];
            $response = $lib->reserveBook($userId, $bookId);
        } elseif ($action === 'cancel_reservation') {
            $resId = $_POST['reservation_id'];
            if ($lib->cancelReservation($resId, $userId)) {
                $response = ['success' => true, 'message' => 'Reservation cancelled.'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to cancel reservation.'];
            }
        } elseif ($action === 'settle_fines') {
            $response = $lib->settleStudentFines($userId);
        } elseif ($action === 'submit_review') {
            $bookId = $_POST['book_id'];
            $rating = (int)$_POST['rating'];
            $comment = $_POST['comment'] ?? '';
            $response = $lib->addReview($userId, $bookId, $rating, $comment);
        } elseif ($action === 'mark_notifications_read') {
            if ($lib->markAllAsRead($userId)) {
                $response = ['success' => true, 'message' => 'Notifications cleared.'];
            }
        } elseif ($action === 'sync_registry') {
            $lib->updateOverdueStatus();
            $lib->refreshOverdueStates();
            $response = ['success' => true, 'message' => 'Registry synchronized.'];
        } elseif ($action === 'fulfill_hold') {
            // Note: This is called by Librarian, but we reuse the handler for utility
            $resId = $_POST['reservation_id'];
            $response = $lib->fulfillReservation($resId);
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
