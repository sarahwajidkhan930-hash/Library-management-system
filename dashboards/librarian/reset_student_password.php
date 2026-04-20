<?php
require_once '../../core/config.php';
require_once '../../core/db.php';
require_once '../../core/library_functions.php';
session_start();

// Security check: Only librarians and super admins can access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['librarian', 'super_admin'])) {
    header("Location: ../../login.php");
    exit();
}

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$message = "";
$messageType = "";
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search_identifier = isset($_POST['search_identifier']) ? trim($_POST['search_identifier']) : "";
$student = null;

// Verify student existence and role
if ($student_id > 0) {
    $stmt = $mysqli->prepare("SELECT id, name, email, identity_no FROM users WHERE id = ? AND role = 'student' LIMIT 1");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} elseif (!empty($search_identifier)) {
    // Search by Email or ID Number if ID not provided in GET
    $stmt = $mysqli->prepare("SELECT id, name, email, identity_no FROM users WHERE (email = ? OR identity_no = ?) AND role = 'student' LIMIT 1");
    $stmt->bind_param("ss", $search_identifier, $search_identifier);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    if ($student) {
        $student_id = $student['id'];
    } else {
        $message = "No student found with that Email or ID.";
        $messageType = "warning";
    }
    $stmt->close();
}

// Handle Password Reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || strlen($new_password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $messageType = "danger";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
        $messageType = "danger";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update_stmt->bind_param("si", $hashed_password, $student_id);

        if ($update_stmt->execute()) {
            // Log the action (Pass null for book_id as this is a system action)
            $lib = new Library($pdo);
            $lib->logAction(null, 'PASSWORD_RESET', "Password reset for student (ID: $student_id): " . $student['name']);

            $_SESSION['success_msg'] = "Password for '" . $student['name'] . "' has been reset successfully.";
            header("Location: student_directory.php");
            exit();
        } else {
            $message = "Error updating password: " . $update_stmt->error;
            $messageType = "danger";
        }
        $update_stmt->close();
    }
}

require_once '../../includes/header.php';
?>

<style>
    :root {
        --premium-crimson: var(--erp-primary);
        --soft-crimson: var(--erp-primary-dark);
        --glass-white: rgba(255, 255, 255, 0.95);
        --surface-grey: var(--erp-bg-main);
    }

    body { background-color: var(--surface-grey) !important; }

    .premium-card {
        background: var(--glass-white);
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border-top: 5px solid var(--premium-crimson);
    }

    .premium-header {
        background: white;
        padding: 2rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        text-align: center;
    }

    .premium-title {
        color: var(--premium-crimson);
        font-weight: 800;
        margin-bottom: 0.5rem;
    }

    .student-info-box {
        background: var(--erp-bg-soft);
        border: 1px solid var(--erp-border);
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 2rem;
    }

    .form-label {
        color: #334155;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
    }

    .form-control {
        border-radius: 10px;
        padding: 0.8rem 1rem;
        border: 1px solid #e2e8f0;
    }

    .btn-reset {
        background: var(--premium-crimson);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.8rem 2rem;
        width: 100%;
        font-weight: 700;
        transition: 0.3s;
    }

    .btn-reset:hover {
        background: var(--soft-crimson);
        transform: translateY(-2px);
        color: white;
    }
</style>

<div class="row justify-content-center py-5">
    <?php if (!$student): ?>
        <div class="col-md-6 col-lg-5">
            <div class="premium-card">
                <div class="premium-header">
                    <div class="mb-3">
                        <i class="bi bi-search fs-1" style="color: var(--premium-crimson);"></i>
                    </div>
                    <h3 class="premium-title">Find Student</h3>
                    <p class="text-muted small">Enter student email or identity no</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> small mb-4 py-2 text-center">
                            <?= $message ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label">Search Identifier</label>
                            <input type="text" name="search_identifier" class="form-control" placeholder="example@email.com or CNIC/ID" required>
                        </div>
                        <button type="submit" class="btn btn-reset">
                            <i class="bi bi-person-check me-2"></i>Access Student Record
                        </button>
                        <div class="text-center mt-3">
                            <a href="student_directory.php" class="text-muted small text-decoration-none">Return to Directory</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="col-md-6 col-lg-4">
            <div class="premium-card">
                <div class="premium-header">
                    <div class="mb-3 text-premium-crimson">
                        <i class="bi bi-shield-lock-fill fs-1" style="color: var(--premium-crimson);"></i>
                    </div>
                    <h3 class="premium-title">Credential Reset</h3>
                    <p class="text-muted small">Update student authentication data</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> small mb-4 py-2">
                            <?= $message ?>
                        </div>
                    <?php endif; ?>

                    <div class="student-info-box d-flex align-items-center mb-4">
                        <div class="bg-white rounded-circle p-2 me-3 shadow-sm">
                            <i class="bi bi-person-fill text-danger fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($student['name']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($student['identity_no']) ?></div>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">New Secure Password</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Re-type password" required>
                        </div>
                        <button type="submit" name="reset_password" class="btn btn-reset">
                            <i class="bi bi-arrow-repeat me-2"></i>Update Credentials
                        </button>
                        <div class="text-center mt-3">
                            <a href="reset_student_password.php" class="text-muted small text-decoration-none">Clear and Search Again</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
$mysqli->close();
require_once '../../includes/footer.php'; 
?>
