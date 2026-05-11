<?php
require_once '../../includes/header.php';
require_once '../../core/db.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch student's past requests
$stmt = $pdo->prepare("SELECT * FROM book_requests WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing books and authors for autocomplete
$existingBooks = $pdo->query("SELECT title FROM lib_books GROUP BY title ORDER BY title ASC")->fetchAll(PDO::FETCH_COLUMN);
$existingAuthors = $pdo->query("SELECT author_name FROM lib_authors GROUP BY author_name ORDER BY author_name ASC")->fetchAll(PDO::FETCH_COLUMN);

function getStatusBadge($status) {
    switch ($status) {
        case 'pending': return '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending</span>';
        case 'approved': return '<span class="badge bg-primary"><i class="bi bi-check-circle me-1"></i>Approved</span>';
        case 'purchased': return '<span class="badge bg-success"><i class="bi bi-cart-check me-1"></i>Purchased</span>';
        case 'rejected': return '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejected</span>';
        case 'cancelled': return '<span class="badge bg-secondary"><i class="bi bi-slash-circle me-1"></i>Cancelled</span>';
        default: return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="p-4 rounded-4 shadow-sm" style="background: linear-gradient(135deg, var(--erp-primary) 0%, var(--erp-primary-dark) 100%);">
                <div class="d-flex justify-content-between align-items-center text-white text-center text-md-start">
                    <div>
                        <h2 class="fw-bold mb-1"><i class="bi bi-journal-plus me-2"></i>Student Request Portal</h2>
                        <p class="mb-0 opacity-75 lead">Request new books to be added to the library collection.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Request Form -->
        <div class="col-lg-4 col-md-5">
            <div class="card glass-card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom-0 py-3 pb-0">
                    <h5 class="fw-bold text-primary m-0"><i class="bi bi-bag-plus-fill me-2"></i>New Request</h5>
                </div>
                <div class="card-body">
                    <form id="bookRequestForm">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Book Title *</label>
                            <input type="text" name="book_title" class="form-control" placeholder="Select or type book title" list="bookList" required autocomplete="off">
                            <datalist id="bookList">
                                <?php foreach($existingBooks as $b): ?>
                                    <option value="<?= htmlspecialchars($b) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Author *</label>
                            <input type="text" name="author" class="form-control" placeholder="Select or type author name" list="authorList" required autocomplete="off">
                            <datalist id="authorList">
                                <?php foreach($existingAuthors as $a): ?>
                                    <option value="<?= htmlspecialchars($a) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Reason for Request *</label>
                            <textarea name="reason" class="form-control" rows="4" placeholder="Why do you need this book?" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm rounded-3">
                            <i class="bi bi-send-fill me-2"></i>Submit Request
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- My Requests Table -->
        <div class="col-lg-8 col-md-7">
            <div class="card glass-card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold m-0"><i class="bi bi-clock-history me-2 text-info"></i>My Previous Requests</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Book Details</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($requests)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            You haven't requested any books yet.
                                        </td>
                                    </tr>
                                <?php else: foreach($requests as $req): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-primary"><?= htmlspecialchars($req['book_title']) ?></div>
                                            <div class="small text-muted">by <?= htmlspecialchars($req['author']) ?></div>
                                            <?php if($req['admin_notes']): ?>
                                                <div class="small text-danger mt-1 fst-italic"><i class="bi bi-chat-left-text me-1"></i>Note: <?= htmlspecialchars($req['admin_notes']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= getStatusBadge($req['status']) ?></td>
                                        <td><?= date('d M Y', strtotime($req['created_at'])) ?></td>
                                        <td class="pe-4 text-end">
                                            <?php if($req['status'] === 'pending'): ?>
                                                <button onclick="cancelRequest(<?= $req['id'] ?>)" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                    Cancel
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-light rounded-pill px-3" disabled>Locked</button>
                                            <?php endif; ?>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('bookRequestForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Submitting...';

    const formData = new FormData(this);
    formData.append('action', 'submit_request');

    fetch('ajax_book_requests.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            Swal.fire({
                title: 'Success!',
                text: 'Your request has been submitted for review.',
                icon: 'success',
                confirmButtonColor: 'var(--erp-primary)'
            }).then(() => location.reload());
        } else {
            Swal.fire('Error', data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Server error occurred.', 'error');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});

function cancelRequest(id) {
    Swal.fire({
        title: 'Cancel Request?',
        text: "Are you sure you want to cancel this book request?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, cancel it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'cancel_request');
            fd.append('id', id);
            
            fetch('ajax_book_requests.php', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('Cancelled!', 'Your request has been cancelled.', 'success')
                    .then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}
</script>

<?php require_once '../../includes/footer.php'; ?>
