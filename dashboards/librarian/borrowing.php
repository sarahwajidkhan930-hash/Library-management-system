<?php 
require_once '../../includes/header.php'; 
require_once '../../core/library_functions.php';

$lib = new Library($pdo);
$message = '';

// Handle Check Out
if (isset($_POST['checkout_book'])) {
    $res = $lib->checkOutBook($_POST['book_id'], $_POST['student_id'], $_POST['due_date']);
    $message = $res['success'] 
        ? '<div class="alert alert-success alert-dismissible fade show shadow-sm border-0"><i class="bi bi-check-circle-fill me-2"></i> '.$res['message'].' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'
        : '<div class="alert alert-danger alert-dismissible fade show shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> '.$res['message'].' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

// Handle Check In
if (isset($_GET['checkin'])) {
    $res = $lib->checkInBook($_GET['checkin']);
    $message = $res['success'] 
        ? '<div class="alert alert-success alert-dismissible fade show shadow-sm border-0"><i class="bi bi-check-circle-fill me-2"></i> '.$res['message'].' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'
        : '<div class="alert alert-danger alert-dismissible fade show shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> '.$res['message'].' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

$activeBorrowings = $lib->getActiveBorrowings();
$books = $lib->getBooks();
$students = $lib->getStudents();
$reservations = $lib->getAllPendingReservations();
?>

<style>
    :root {
        --borrow-primary: var(--erp-primary);
        --borrow-bg: #f8fafc;
        --borrow-border: #e2e8f0;
    }
    
    body { background-color: var(--borrow-bg); }

    .glass-card {
        background: #ffffff;
        border: 1px solid var(--borrow-border);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 2rem;
    }
    
    .btn-primary {
        background-color: var(--borrow-primary);
        border-color: var(--borrow-primary);
        border-radius: 8px;
        font-weight: 600;
    }
    
    .btn-primary:hover {
        background-color: #7f1d1d;
        border-color: #7f1d1d;
    }
    
    .table-custom thead th {
        background-color: #f1f5f9;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700;
        color: #64748b;
        padding: 1rem 1.5rem;
    }
    
    .table-custom tbody td {
        padding: 1rem 1.5rem;
        vertical-align: middle;
    }

    .badge-soft-danger { background-color: var(--erp-bg-soft); color: var(--erp-primary); border: 1px solid var(--erp-border); }
    .badge-soft-success { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
</style>

<div class="content-header px-4 pt-4">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-muted">Librarian</a></li>
                        <li class="breadcrumb-item active">Borrowing & Returns</li>
                    </ol>
                </nav>
                <h1 class="m-0 fw-bold text-dark"><i class="bi bi-arrow-left-right me-2 text-danger"></i>Borrowing Management</h1>
            </div>
            <div class="col-sm-6 text-end">
                <button class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#checkoutModal">
                    <i class="bi bi-plus-lg me-2"></i>Issue New Book
                </button>
            </div>
        </div>
    </div>
</div>

<div class="content px-4">
    <div class="container-fluid">
        <?= $message ?>

        <div class="card glass-card">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="card-title fw-bold m-0 text-dark">Currently Active Borrowings</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Book Information</th>
                                <th>Issued Date</th>
                                <th class="text-center">Due Date</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activeBorrowings)): ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">No books are currently on loan.</td></tr>
                            <?php else: foreach ($activeBorrowings as $loan): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($loan['student_name']) ?></div>
                                        <span class="text-muted small">Student ID: #<?= $loan['user_id'] ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($loan['title']) ?></div>
                                        <span class="text-muted small">Book ID: #<?= $loan['book_id'] ?></span>
                                    </td>
                                    <td><?= date('d M Y', strtotime($loan['borrow_date'])) ?></td>
                                    <td class="text-center">
                                        <span class="fw-medium <?= (strtotime($loan['due_date']) < time()) ? 'text-danger' : '' ?>">
                                            <?= date('d M Y', strtotime($loan['due_date'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($loan['status'] == 'overdue'): ?>
                                            <span class="badge badge-soft-danger rounded-pill px-3">Overdue</span>
                                        <?php else: ?>
                                            <span class="badge badge-soft-success rounded-pill px-3">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="?checkin=<?= $loan['id'] ?>" class="btn btn-sm btn-outline-success px-3 rounded-pill" onclick="return confirm('Confirm book return and return to inventory?')">
                                            Mark as Returned
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card glass-card border-info mt-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="card-title fw-bold m-0 text-info"><i class="bi bi-calendar-check me-2"></i>Active Hold Requests</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Student Waiting</th>
                                <th>Book Title</th>
                                <th>Requested</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reservations)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No pending holds found.</td></tr>
                            <?php else: foreach ($reservations as $r): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($r['student_name']) ?></div>
                                        <div class="small text-muted">ID: #<?= $r['user_id'] ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary"><?= htmlspecialchars($r['title']) ?></div>
                                        <div class="small text-muted">Book ID: #<?= $r['book_id'] ?></div>
                                    </td>
                                    <td><?= date('d M Y', strtotime($r['reserved_at'])) ?></td>
                                    <td class="text-center">
                                        <button onclick="fulfillHold(<?= $r['id'] ?>)" class="btn btn-sm btn-info text-white rounded-pill px-3 shadow-sm">
                                            <i class="bi bi-check-lg me-1"></i>Fulfill Hold
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Issue Book Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h4 class="modal-title fw-bold text-dark"><i class="bi bi-journal-check me-2 text-success"></i>Issue a Book</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Book</label>
                        <select name="book_id" class="form-select border-0 bg-light py-2" required>
                            <option value="">-- Search and Select Book --</option>
                            <?php foreach ($books as $b): if ($b['available_copies'] > 0): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['title']) ?> (<?= $b['available_copies'] ?> available)</option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Student</label>
                        <select name="student_id" id="checkoutStudent" class="form-select border-0 bg-light py-2" required>
                            <option value="">-- Search and Select Student --</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= (isset($_GET['student_id']) && $_GET['student_id'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?> (<?= $s['email'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Return Due Date</label>
                        <input type="date" name="due_date" class="form-control border-0 bg-light py-2" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4 me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="checkout_book" class="btn btn-primary px-4">Complete Issue</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', (event) => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('student_id')) {
        const checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
        checkoutModal.show();
    }
});

function fulfillHold(resId) {
    if(!confirm('This will immediately issue the book to the student and fulfill their hold. Proceed?')) return;
    
    const formData = new FormData();
    formData.append('action', 'fulfill_hold');
    formData.append('reservation_id', resId);

    fetch('../student/ajax_student_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if(data.success) location.reload();
    })
    .catch(err => alert('Fulfillment failed.'));
}
</script>

<?php require_once '../../includes/footer.php'; ?>
