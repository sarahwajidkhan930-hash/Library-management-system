<?php
require_once '../../core/config.php';
require_once '../../core/db.php';
session_start();

// Security check: Only librarians and super admins can access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['librarian', 'super_admin'])) {
    header("Location: ../../login.php");
    exit();
}

// MySQLi Connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Handle Search and Filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$action_filter = isset($_GET['action']) ? $_GET['action'] : 'ALL';

$query = "SELECT t.*, u.name as user_name, u.identity_no as user_identity, b.title as book_title 
          FROM lib_transactions t
          JOIN users u ON t.user_id = u.id
          JOIN lib_books b ON t.book_id = b.id";

$conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $conditions[] = "(u.name LIKE ? OR u.identity_no LIKE ? OR b.title LIKE ? OR t.notes LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ssss";
}

if ($action_filter !== 'ALL') {
    $conditions[] = "t.action = ?";
    $params[] = $action_filter;
    $types .= "s";
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY t.transaction_date DESC LIMIT 10";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

require_once '../../includes/header.php';
?>

<style>
    :root {
        --premium-crimson: var(--erp-primary);
        --soft-crimson: var(--erp-primary-light);
        --surface-grey: var(--erp-bg-main);
    }

    body { background-color: var(--surface-grey) !important; }

    /* Global Redundancy Fix */
    .app-content-header { display: none !important; }

    .premium-card {
        background: white;
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
        overflow: hidden;
    }

    .premium-header {
        background: white;
        padding: 1.5rem;
        border-bottom: 2px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .premium-title {
        color: var(--premium-crimson);
        font-weight: 800;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .table-premium thead th {
        background-color: #f8fafc;
        color: #64748b;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        font-weight: 700;
        padding: 1.25rem 1rem;
        border-bottom: 2px solid #f1f5f9;
    }

    .table-premium tbody td {
        padding: 1.25rem 1rem;
        vertical-align: middle;
        font-size: 0.9rem;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }

    .transaction-ts {
        color: #64748b;
        font-size: 0.8rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .action-pill {
        font-weight: 700;
        padding: 0.5rem 1rem;
        border-radius: 10px;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .search-input, .filter-select {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.5rem 1rem;
        transition: all 0.3s;
    }

    .search-input:focus, .filter-select:focus {
        border-color: var(--premium-crimson);
        box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        background: white;
    }

    .search-btn {
        background: var(--premium-crimson);
        color: white;
        border-radius: 10px;
        border: none;
        padding: 0 1.2rem;
        font-weight: 600;
    }
</style>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="premium-title">
            <i class="bi bi-shield-check me-3 p-2 bg-light rounded-3"></i>Digital Audit Trail
        </h2>
    </div>
</div>

<div class="premium-card shadow-sm border-0">
    <div class="premium-header">
        <p class="mb-0 text-muted small fw-600">Secure System Transaction Logs</p>
        <form action="digital_audit_trail.php" method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <select name="action" class="filter-select form-control-sm">
                <option value="ALL" <?= $action_filter == 'ALL' ? 'selected' : '' ?>>All Operations</option>
                <option value="ISSUE" <?= $action_filter == 'ISSUE' ? 'selected' : '' ?>>Book Issue</option>
                <option value="RETURN" <?= $action_filter == 'RETURN' ? 'selected' : '' ?>>Book Return</option>
                <option value="REGISTER_BOOK" <?= $action_filter == 'REGISTER_BOOK' ? 'selected' : '' ?>>Registry</option>
                <option value="SETTLE_FINE" <?= $action_filter == 'SETTLE_FINE' ? 'selected' : '' ?>>Financials</option>
            </select>
            <input type="text" name="search" class="search-input form-control-sm" placeholder="User or Book title..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="search-btn"><i class="bi bi-funnel me-1"></i> Filter</button>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table table-premium mb-0">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Executing Agent</th>
                    <th>Affected Asset</th>
                    <th class="text-center">Operation</th>
                    <th>Audit Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="transaction-ts">
                                <i class="bi bi-clock-history me-1" style="color: var(--premium-crimson);"></i>
                                <?= date('M d, Y | h:i A', strtotime($row['transaction_date'])) ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['user_name']) ?></div>
                                <div class="text-muted extra-small" style="font-size: 0.75rem;">Verified Member</div>
                            </td>
                            <td>
                                <div class="text-dark small fw-600"><?= htmlspecialchars($row['book_title']) ?></div>
                                <div class="text-muted italic extra-small" style="font-size: 0.7rem;">Asset ID: #<?= $row['book_id'] ?></div>
                            </td>
                            <td class="text-center">
                                <?php 
                                    $action = $row['action'];
                                    $color = '#64748b';
                                    $bg = '#f1f5f9';
                                    if ($action == 'ISSUE') { $bg = '#fff7ed'; $color = '#9a3412'; }
                                    elseif ($action == 'RETURN') { $bg = '#f0fdf4'; $color = '#15803d'; }
                                    elseif ($action == 'REGISTER_BOOK') { $bg = '#eff6ff'; $color = '#1e40af'; }
                                    elseif ($action == 'SETTLE_FINE') { $bg = '#fff1f2'; $color = '#be123c'; }
                                ?>
                                <span class="action-pill" style="background: <?= $bg ?>; color: <?= $color ?>;">
                                    <?= str_replace('_', ' ', $action) ?>
                                </span>
                            </td>
                            <td><span class="text-muted small"><?= htmlspecialchars($row['notes'] ?: 'No additional metadata captured.') ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">No audit trail entries matched your current filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
$mysqli->close();
require_once '../../includes/footer.php'; 
?>
