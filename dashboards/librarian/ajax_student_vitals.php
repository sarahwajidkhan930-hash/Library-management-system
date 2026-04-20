<?php
require_once '../../core/config.php';
require_once '../../core/db.php';
require_once '../../core/library_functions.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

$allowedRoles = ['librarian', 'assistant_manager', 'super_admin'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $allowedRoles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$studentId = (int) ($_GET['student_id'] ?? 0);

if ($studentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Student ID.']);
    exit;
}

try {
    $lib = new Library($pdo);
    $vitals = $lib->getBorrowingVitals($studentId);
    
    echo json_encode([
        'success' => true,
        'vitals' => $vitals
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
