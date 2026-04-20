<?php
/**
 * audit_helper.php
 * Modular audit trail function.
 */

require_once __DIR__ . '/db.php';

/**
 * Records an action into the audit trail.
 * 
 * @param int $userId ID of the user performing the action
 * @param int|null $bookId ID of the book involved (optional)
 * @param string $action Action description (e.g., 'REGISTER_BOOK', 'SETTLE_FINE')
 * @param string $notes Additional context
 * @return bool Success status
 */
/**
 * Records an action into the audit trail (ERP Standard).
 * 
 * @param string $activity Action description (e.g., 'LOGIN', 'ISSUE', 'RETURN')
 * @param string $notes Additional context (e.g., 'Book ID: 5')
 * @return bool Success status
 */
function logAction($activity, $notes = '') {
    global $pdo;
    
    // Ensure session is started to get user_id
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $userId = $_SESSION['user_id'] ?? 0;
    if (!$userId) return false;

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    try {
        $sql = "INSERT INTO audit_logs (user_id, activity, notes, ip_address) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$userId, $activity, $notes, $ipAddress]);
    } catch (PDOException $e) {
        error_log("Audit Log Error: " . $e->getMessage());
        return false;
    }
}
?>
