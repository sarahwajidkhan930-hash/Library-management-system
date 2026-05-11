<?php
require_once '../../core/config.php';
require_once '../../core/db.php';
require_once '../../core/audit_helper.php';
session_start();

// Security check: Super Admins Only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../../login.php");
    exit();
}

$message = "";
$messageType = "";

// ── HANDLE BACKUP (EXPORT) ───────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $tables = ['lib_books', 'lib_authors', 'lib_categories', 'lib_borrowings', 'lib_notifications', 'lib_reservations', 'lib_reviews', 'lib_transactions', 'system_settings', 'users', 'sys_pages', 'role_access', 'sys_roles'];
    $sql_dump = "-- Universal ERP Library Module Backup\n";
    $sql_dump .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    $sql_dump .= "SET FOREIGN_KEY_CHECKS=0;\n";

    foreach ($tables as $table) {
        $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
        
        $stmt = $pdo->query("SHOW CREATE TABLE `$table` text");
        $createTable = $stmt->fetch();
        $sql_dump .= $createTable['Create Table'] . ";\n\n";

        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $keys = array_keys($row);
            $values = array_map(function($v) use ($pdo) {
                return $v === null ? "NULL" : $pdo->quote($v);
            }, array_values($row));
            
            $sql_dump .= "INSERT INTO `$table` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $values) . ");\n";
        }
        $sql_dump .= "\n";
    }
    
    $sql_dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename=library_backup_' . date('Y-m-d') . '.sql');
    echo $sql_dump;
    logAction(0, 'SYSTEM_BACKUP', "Super Admin triggered a manual database backup.");
    exit;
}

// ── HANDLE RESTORE (IMPORT) ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])) {
    if ($_FILES['sql_file']['error'] == UPLOAD_ERR_OK) {
        $sql = file_get_contents($_FILES['sql_file']['tmp_name']);
        try {
            $pdo->exec($sql);
            $message = "Database restoration successful. All systems synchronized.";
            $messageType = "success";
            logAction(0, 'SYSTEM_RESTORE', "Super Admin restored database from file: " . $_FILES['sql_file']['name']);
        } catch (Exception $e) {
            $message = "Restoration failed: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

require_once '../../includes/header.php';
?>

<div class="container-fluid px-4 mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6 text-start">
            <h1 class="h3 mb-0 text-dark fw-bold"><i class="bi bi-shield-shaded me-2 text-primary"></i>System Continuity Utility</h1>
            <p class="text-muted small">Hot-backup and data restoration engine for the Library ERP.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi <?= $messageType == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Backup Card -->
        <div class="col-md-6">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden h-100">
                <div class="card-header bg-primary text-white p-4 border-0">
                    <h5 class="fw-bold mb-0">Database Export</h5>
                    <p class="small opacity-75 mb-0">Generate a comprehensive snapshot of all library records.</p>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div class="mb-4">
                        <ul class="list-unstyled small text-muted">
                            <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Includes all Books, Authors, and Categories</li>
                            <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Includes Circulation and Fine History</li>
                            <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Full Audit Trails & System Logs</li>
                        </ul>
                    </div>
                    <a href="?action=export" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm">
                        <i class="bi bi-cloud-download me-2"></i>Initialize SQL Backup
                    </a>
                </div>
            </div>
        </div>

        <!-- Restore Card -->
        <div class="col-md-6">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden h-100">
                <div class="card-header bg-dark text-white p-4 border-0">
                    <h5 class="fw-bold mb-0">System Restoration</h5>
                    <p class="small opacity-75 mb-0">Overwrite the current state from a previous backup file.</p>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-warning border-warning border-opacity-25 small mb-4">
                        <i class="bi bi-exclamation-triangle me-2"></i><strong>Caution:</strong> This will replace all current data. Ensure you have a current backup before proceeding.
                    </div>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Select SQL Backup File</label>
                            <input type="file" name="sql_file" class="form-control rounded-3" accept=".sql" required>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 py-3 rounded-pill fw-bold shadow-sm" onclick="return confirm('Initiate full database restoration? This cannot be undone!')">
                            <i class="bi bi-cloud-upload me-2"></i>Recover from Archive
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
