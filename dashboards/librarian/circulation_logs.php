<?php
require_once '../../core/config.php';
require_once '../../core/db.php';
session_start();

// Security check: Only librarians can access
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

// Rebuilt high-integrity Audit Trail query with LEFT JOINs to capture all system operations
$query = "SELECT t.*, u.name as user_name, u.fines as student_fines, u.id as student_id, b.title as book_title, b.id as catalog_item_id,
          (SELECT id FROM lib_borrowings WHERE user_id = t.user_id AND (book_id = t.book_id OR t.book_id IS NULL) AND status != 'returned' LIMIT 1) as active_borrowing_id
          FROM lib_transactions t
          JOIN users u ON t.user_id = u.id
          LEFT JOIN lib_books b ON t.book_id = b.id";

$conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $conditions[] = "(u.name LIKE ? OR b.title LIKE ? OR t.notes LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

if ($action_filter !== 'ALL') {
    $conditions[] = "t.action = ?";
    $params[] = $action_filter;
    $types .= "s";
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY t.transaction_date DESC LIMIT 50";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

require_once '../../includes/header.php';
?>

<style>
    .btn-action { padding: 4px 8px; border-radius: 6px; border: 1px solid #f1f5f9; background: white; font-size: 0.85rem; line-height: 1; transition: all 0.2s; }
    .btn-action:hover { background: #f8fafc; border-color: #e2e8f0; transform: translateY(-1px); }

    :root {
        --premium-crimson: var(--erp-primary);
        --soft-crimson: var(--erp-primary-light);
        --surface-grey: var(--erp-bg-main);
    }

    body { background-color: var(--surface-grey) !important; }

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

    .transaction-date {
        color: #64748b;
        font-size: 0.8rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .action-badge {
        font-weight: 700;
        padding: 0.5rem 1.2rem;
        border-radius: 10px;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .search-input {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.5rem 1rem;
        transition: all 0.3s;
    }

    .search-input:focus {
        border-color: var(--premium-crimson);
        box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        background: white;
    }

    .search-btn {
        background: var(--premium-crimson);
        color: white;
        border-radius: 10px;
        border: none;
        padding: 0 1rem;
    }

    /* Remove Redundant Global Header */
    .app-content-header { display: none !important; }
</style>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="premium-title">
            <a href="librarian_dashboard.php" class="text-decoration-none text-premium-crimson">
                <i class="bi bi-arrow-left me-3 p-2 bg-light rounded-3"></i>
            </a>
            Circulation Audit
        </h2>
    </div>
    <div class="col-md-6">
        <form action="circulation_logs.php" method="GET" class="d-flex gap-2 justify-content-md-end align-items-center">
            <?php if (!empty($search) || $action_filter !== 'ALL'): ?>
                <a href="circulation_logs.php" class="text-muted small me-2 text-decoration-none">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            <?php endif; ?>
            <input type="text" name="search" class="search-input form-control-sm" style="width: 250px;" placeholder="Search entries..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="search-btn"><i class="bi bi-search"></i></button>
        </form>
    </div>
</div>

<div class="premium-card shadow-sm border-0">
    <div class="premium-header pb-0 border-0">
        <!-- Digital Transaction Audit Trail removed to free space as requested -->
    </div>
    
    <div class="table-responsive">
        <table class="table table-premium mb-0">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Member</th>
                    <th>Asset</th>
                    <th class="text-center">Operation</th>
                    <th>Log Notes</th>
                    <th class="text-end" style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="transaction-date">
                                <i class="bi bi-clock-history me-1 text-premium-crimson" style="color: var(--premium-crimson);"></i>
                                <?= date('M d, Y | h:i A', strtotime($row['transaction_date'])) ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark lh-sm"><?= htmlspecialchars($row['user_name']) ?></div>
                                <?php if($row['student_fines'] > 0): ?>
                                    <span class="text-danger fw-bold extra-small" style="font-size: 0.65rem;">Rs. <?= number_format($row['student_fines'], 2) ?> Dues</span>
                                <?php else: ?>
                                    <span class="text-muted extra-small" style="font-size: 0.75rem;">Verified Member</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['book_title']): ?>
                                    <div class="text-dark small fw-600"><?= htmlspecialchars($row['book_title']) ?></div>
                                    <div class="text-muted italic extra-small" style="font-size: 0.7rem;">Catalog ID: #<?= $row['book_id'] ?></div>
                                <?php else: ?>
                                    <div class="text-muted italic small"><i class="bi bi-gear-fill me-1"></i>System Operation</div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php 
                                    $action = $row['action'];
                                    $color = '#64748b';
                                    $bg = '#f1f5f9';
                                    if ($action == 'ISSUE') { $bg = '#e0f2fe'; $color = '#0369a1'; }
                                    elseif ($action == 'RETURN') { $bg = '#dcfce7'; $color = '#15803d'; }
                                    elseif ($action == 'REGISTER_BOOK') { $bg = '#fff1f2'; $color = '#be123c'; }
                                    elseif ($action == 'PASSWORD_RESET') { $bg = '#fef3c7'; $color = '#92400e'; }
                                    elseif ($action == 'SETTLE_FINE') { $bg = '#f5f3ff'; $color = '#5b21b6'; }
                                ?>
                                <a href="circulation_logs.php?action=<?= urlencode($action) ?>" class="text-decoration-none">
                                    <span class="action-badge" style="background: <?= $bg ?>; color: <?= $color ?>;">
                                        <?= htmlspecialchars(str_replace('_', ' ', $action)) ?>
                                    </span>
                                </a>
                            </td>
                            <td><span class="text-muted small"><?= htmlspecialchars($row['notes'] ?: 'Metadata preserved.') ?></span></td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <?php if ($row['active_borrowing_id']): ?>
                                        <button class="btn btn-action text-success" title="Quick Return" onclick="quickReturn(<?= $row['user_id'] ?>, <?= $row['book_id'] ?>)">
                                            <i class="bi bi-arrow-return-left"></i>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($row['student_fines'] > 0): ?>
                                        <button class="btn btn-action text-primary" title="Settle Fines" onclick="settleFine(<?= $row['user_id'] ?>, '<?= addslashes($row['user_name']) ?>')">
                                            <i class="bi bi-cash-stack"></i>
                                        </button>
                                    <?php endif; ?>

                                    <button class="btn btn-action text-danger" title="Impose Fine" onclick="imposeFine(<?= $row['user_id'] ?>, <?= $row['book_id'] ?>, '<?= addslashes($row['user_name']) ?>')">
                                        <i class="bi bi-exclamation-octagon"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">No circulation history mappings recorded.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
$mysqli->close();
require_once '../../includes/footer.php'; 
?>

<script src="../../assets/js/sweetalert2.all.min.js"></script>
<script>
async function quickReturn(userId, bookId) {
    const res = await Swal.fire({
        title: 'Quick Return',
        text: "Mark this book as returned?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#15803d',
        confirmButtonText: 'Yes, Return'
    });
    if (res.isConfirmed) performAction('quick_return', { user_id: userId, book_id: bookId });
}

async function settleFine(userId, name) {
    const res = await Swal.fire({
        title: 'Settle Dues',
        text: `Clear all outstanding fines for ${name}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0369a1',
        confirmButtonText: 'Clear Dues'
    });
    if (res.isConfirmed) performAction('settle_fine', { user_id: userId });
}

async function imposeFine(userId, bookId, name) {
    const { value: formValues } = await Swal.fire({
        title: `Manual Fine: ${name}`,
        html:
            '<input id="swal-amount" class="swal2-input" placeholder="Amount (Rs.)" type="number">' +
            '<input id="swal-reason" class="swal2-input" placeholder="Reason (e.g. Damage)">',
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Impose Fine',
        preConfirm: () => {
            return {
                amount: document.getElementById('swal-amount').value,
                reason: document.getElementById('swal-reason').value
            }
        }
    });
    if (formValues) {
        if (!formValues.amount || formValues.amount <= 0) return Swal.fire('Error', 'Invalid amount', 'error');
        performAction('manual_fine', { 
            user_id: userId, 
            book_id: bookId, 
            amount: formValues.amount, 
            reason: formValues.reason 
        });
    }
}

function performAction(action, data) {
    const formData = new FormData();
    formData.append('action', action);
    for (let key in data) formData.append(key, data[key]);

    fetch('ajax_circulation_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Success!',
                text: data.message,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => location.reload());
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(() => Swal.fire('Error', 'Operation failed.', 'error'));
}
</script>
