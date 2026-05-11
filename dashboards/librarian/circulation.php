<?php 
require_once '../../core/session_guard.php';
require_once '../../includes/header.php'; 
require_once '../../core/library_functions.php';

$lib = new Library($pdo);
$message = '';

// Handle Check Out
if (isset($_POST['checkout_book'])) {
    $bookIds = $_POST['book_id'];
    $studentId = $_POST['student_id'];
    $dueDate = $_POST['due_date'];
    
    if (!is_array($bookIds)) {
        $bookIds = [$bookIds];
    }
    
    $successCount = 0;
    $errors = [];
    foreach ($bookIds as $bId) {
        if (empty($bId)) continue;
        $res = $lib->checkOutBook($bId, $studentId, $dueDate);
        if ($res['success']) {
            $successCount++;
        } else {
            $stmt = $pdo->prepare("SELECT title FROM lib_books WHERE id = ?");
            $stmt->execute([$bId]);
            $title = $stmt->fetchColumn() ?: "ID #$bId";
            $errors[] = "<strong>$title</strong>: " . $res['message'];
        }
    }
    
    $message = '';
    if ($successCount > 0) {
        $message .= '<div class="alert alert-success alert-dismissible fade show shadow-sm border-0"><i class="bi bi-check-circle-fill me-2"></i> Successfully issued ' . $successCount . ' book(s). <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } 
    if (!empty($errors)) {
        $message .= '<div class="alert alert-danger alert-dismissible fade show shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> ' . implode("<br>", $errors) . ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Handle Check In
if (isset($_GET['checkin'])) {
    $borrowingId = $_GET['checkin'];
    // Get borrowing info for logging before it's marked as returned
    $stmt = $pdo->prepare("SELECT book_id, user_id FROM lib_borrowings WHERE id = ?");
    $stmt->execute([$borrowingId]);
    $loan = $stmt->fetch();

    $res = $lib->checkInBook($borrowingId);
    if ($res['success']) {
        if ($loan) {
            $lib->logAction($loan['book_id'], 'RETURN', "Book returned by student ID: {$loan['user_id']}");
        }
        $message = '<div class="alert alert-success alert-dismissible fade show shadow-sm border-0"><i class="bi bi-check-circle-fill me-2"></i> '.$res['message'].' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> '.$res['message'].' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

$activeBorrowings = $lib->getActiveBorrowings();
$books = $lib->getBooks();
$students = $lib->getStudents();
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
                        <li class="breadcrumb-item active">Circulation Management</li>
                    </ol>
                </nav>
                <h1 class="m-0 fw-bold text-dark"><i class="bi bi-arrow-left-right me-2 text-danger"></i>Circulation Management</h1>
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
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title fw-bold m-0 text-dark">Currently Active Borrowings</h5>
                <button type="button" id="bulkReturnBtn" class="btn btn-sm btn-success px-3 shadow-sm d-none" onclick="processBulkReturn()"><i class="bi bi-arrow-return-left me-1"></i>Return Selected (<span id="bulkReturnCount">0</span>)</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0" id="activeBorrowingsTable">
                        <thead>
                            <tr>
                                <th style="width: 40px;" class="text-center"><input type="checkbox" id="selectAllLoans" class="form-check-input border-secondary"></th>
                                <th>Student</th>
                                <th>Book Information</th>
                                <th>Issued Date</th>
                                <th class="text-center">Due Date</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Command Center</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activeBorrowings)): ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted">No books are currently on loan.</td></tr>
                            <?php else: foreach ($activeBorrowings as $loan): ?>
                                <tr>
                                    <td class="text-center"><input type="checkbox" class="form-check-input loan-checkbox border-secondary" value="<?= $loan['id'] ?>"></td>
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
                                    <td class="text-end">
                                        <div class="d-flex gap-2 justify-content-end">
                                            <a href="?checkin=<?= $loan['id'] ?>" class="btn btn-sm btn-outline-success px-3 rounded-pill" title="Return Book" onclick="return confirm('Confirm book return?')">
                                                <i class="bi bi-arrow-return-left"></i>
                                            </a>
                                            <?php if ($loan['status'] !== 'overdue'): ?>
                                            <button class="btn btn-sm btn-outline-info px-3 rounded-pill" title="Renew (+7 Days)" onclick="renewBook(<?= $loan['id'] ?>, '<?= addslashes($loan['title']) ?>')">
                                                <i class="bi bi-calendar-plus"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-primary px-3 rounded-pill" title="Settle Fines" onclick="settleFine(<?= $loan['user_id'] ?>, '<?= addslashes($loan['student_name']) ?>')">
                                                <i class="bi bi-cash-stack"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger px-3 rounded-pill" title="Impose Fine" onclick="imposeFine(<?= $loan['user_id'] ?>, <?= $loan['book_id'] ?>, '<?= addslashes($loan['student_name']) ?>')">
                                                <i class="bi bi-exclamation-octagon"></i>
                                            </button>
                                        </div>
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
                    <div class="mb-3 bg-danger bg-opacity-10 p-3 rounded-4 border border-danger border-opacity-25 shadow-sm">
                        <label class="form-label small text-danger fw-bold m-0 d-flex align-items-center">
                            <i class="bi bi-upc-scan me-2 fs-5"></i>Barcode Scanner Focus
                        </label>
                        <input type="text" id="barcodeScanner" class="form-control border-0 mt-2 bg-white" placeholder="Scan Book ISBN/ID or Student ID...">
                        <div id="scannerFeedback" class="small mt-1 fw-semibold text-danger"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Book(s) <span class="text-primary small">(Hold Ctrl/Cmd to select multiple)</span></label>
                        <select name="book_id[]" id="checkoutBook" class="form-select border-0 bg-light py-2" required multiple size="5">
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

<script src="../../assets/js/sweetalert2.all.min.js"></script>
<script>
window.addEventListener('DOMContentLoaded', (event) => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('student_id')) {
        const checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
        if (checkoutModal) checkoutModal.show();
    }
    
    const scannerInput = document.getElementById('barcodeScanner');
    const scannerFeedback = document.getElementById('scannerFeedback');
    const bookSelect = document.getElementById('checkoutBook');
    const studentSelect = document.getElementById('checkoutStudent');
    
    if (scannerInput) {
        scannerInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                let code = this.value.trim();
                if (code === '') return;
                
                fetch('ajax_barcode_lookup.php?code=' + encodeURIComponent(code))
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (data.type === 'student') {
                            studentSelect.value = data.id;
                            scannerFeedback.innerHTML = `<span class="text-success"><i class="bi bi-person-check-fill me-1"></i>Student Selected: ${data.name}</span>`;
                        } else if (data.type === 'book') {
                            let option = Array.from(bookSelect.options).find(opt => opt.value == data.id);
                            if (option) {
                                option.selected = true;
                                scannerFeedback.innerHTML = `<span class="text-success"><i class="bi bi-journal-check me-1"></i>Book Selected: ${data.title}</span>`;
                            } else {
                                scannerFeedback.innerHTML = `<span class="text-warning">Book matched but is out of stock.</span>`;
                            }
                        }
                        scannerInput.value = '';
                    } else {
                        scannerFeedback.innerHTML = `<span class="text-danger"><i class="bi bi-x-circle me-1"></i>${data.message || 'Barcode not found'}</span>`;
                        scannerInput.value = '';
                    }
                })
                .catch(() => {
                    scannerFeedback.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Lookup failed.</span>`;
                });
            }
        });
    }

    const selectAll = document.getElementById('selectAllLoans');
    const loanCheckboxes = document.querySelectorAll('.loan-checkbox');
    const bulkReturnBtn = document.getElementById('bulkReturnBtn');
    const bulkReturnCount = document.getElementById('bulkReturnCount');

    function updateBulkReturnBtn() {
        if (!bulkReturnBtn) return;
        const checkedCount = document.querySelectorAll('.loan-checkbox:checked').length;
        bulkReturnCount.innerText = checkedCount;
        if (checkedCount > 0) {
            bulkReturnBtn.classList.remove('d-none');
        } else {
            bulkReturnBtn.classList.add('d-none');
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            loanCheckboxes.forEach(cb => cb.checked = this.checked);
            updateBulkReturnBtn();
        });
    }

    loanCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkReturnBtn);
    });
});

async function processBulkReturn() {
    const checked = document.querySelectorAll('.loan-checkbox:checked');
    if (checked.length === 0) return;
    
    const ids = Array.from(checked).map(cb => cb.value);
    
    const res = await Swal.fire({
        title: 'Bulk Return',
        text: `Are you sure you want to return ${ids.length} selected books?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#15803d',
        confirmButtonText: 'Yes, Return All'
    });

    if (res.isConfirmed) {
        const formData = new FormData();
        formData.append('action', 'bulk_return');
        ids.forEach(id => formData.append('return_ids[]', id));
        
        fetch('ajax_circulation_actions.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ title: 'Success!', text: data.message, icon: 'success', timer: 1500, showConfirmButton: false }).then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    }
}

async function renewBook(borrowingId, title) {
    const res = await Swal.fire({
        title: 'Renew Book',
        text: `Extend due date for "${title}" by 7 days?`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#0ea5e9',
        confirmButtonText: 'Renew (+7 Days)'
    });

    if (res.isConfirmed) {
        performAction('renew', { borrowing_id: borrowingId });
    }
}

async function settleFine(userId, name) {
    const res = await Swal.fire({
        title: 'Settle Fines',
        text: `Mark all outstanding dues as PAID for ${name}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0369a1',
        confirmButtonText: 'Clear Dues'
    });

    if (res.isConfirmed) {
        performAction('settle_fine', { user_id: userId });
    }
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
        if (!formValues.amount || formValues.amount <= 0) return Swal.fire('Error', 'Please enter a valid amount', 'error');
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
    .catch(() => Swal.fire('Error', 'System transaction failed.', 'error'));
}
</script>

<?php require_once '../../includes/footer.php'; ?>
