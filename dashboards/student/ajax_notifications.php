<?php
require_once '../../core/session.php';
require_once '../../core/library_functions.php';

$lib = new Library($pdo);
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Invalid action.'];

    try {
        if ($action === 'mark_read') {
            if ($lib->markAllAsRead($userId)) {
                $response = ['success' => true, 'message' => 'All notifications marked as read.'];
            }
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
