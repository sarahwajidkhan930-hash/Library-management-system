<?php
require_once '../../core/db.php';
require_once '../../core/audit_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

try {
    // 1. Identify overdue records that haven't been reminded in the last 24 hours
    $stmt = $pdo->query("
        SELECT b.id, b.user_id, u.name as student_name, bk.title as book_title
        FROM lib_borrowings b
        JOIN users u ON b.user_id = u.id
        JOIN lib_books bk ON b.book_id = bk.id
        WHERE b.status = 'overdue' 
        AND (b.last_reminded_at IS NULL OR b.last_reminded_at < DATE_SUB(NOW(), INTERVAL 1 DAY))
    ");
    $toRemind = $stmt->fetchAll();

    if (empty($toRemind)) {
        echo json_encode(['success' => true, 'message' => 'All students are already up to date with reminders.']);
        exit;
    }

    $count = 0;
    foreach ($toRemind as $rem) {
        // 2. Log to Audit Trail
        logAction('REMINDER_SENT', "Sent overdue reminder to {$rem['student_name']} for '{$rem['book_title']}'");
        
        // 3. Update last_reminded_at
        $update = $pdo->prepare("UPDATE lib_borrowings SET last_reminded_at = NOW() WHERE id = ?");
        $update->execute([$rem['id']]);
        $count++;
    }

    echo json_encode(['success' => true, 'message' => "Successfully processed $count reminders."]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}
?>
