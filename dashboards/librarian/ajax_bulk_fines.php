<?php
require_once '../../core/config.php';
require_once '../../core/db.php';
require_once '../../core/audit_helper.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['librarian', 'super_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$studentIds = $data['student_ids'] ?? [];

if (empty($studentIds)) {
    echo json_encode(['success' => false, 'message' => 'No students selected.']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    foreach ($studentIds as $id) {
        $id = (int)$id;
        
        // 1. Get student info for logging
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $name = $stmt->fetchColumn();
        
        // 2. Clear user fines
        $stmt = $pdo->prepare("UPDATE users SET fines = 0 WHERE id = ?");
        $stmt->execute([$id]);
        
        // 3. Update all borrowings to settled
        $stmt = $pdo->prepare("UPDATE lib_borrowings SET fine_amount = 0 WHERE user_id = ?");
        $stmt->execute([$id]);
        
        logAction('BULK_FINE_SETTLEMENT', "Librarian settled all fines for student: $name (ID: $id)");
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Fines settled for ' . count($studentIds) . ' students.']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
