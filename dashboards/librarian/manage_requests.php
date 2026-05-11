<?php
require_once '../../includes/header.php';
require_once '../../core/db.php';

$role = $_SESSION['role'];
if (!in_array($role, ['librarian', 'super_admin'])) {
    die("<div class='alert alert-danger m-4'>Access Denied.</div>");
}

// Fetch all requests
$stmt = $pdo->query("
    SELECT r.*, u.name as student_name, u.email as student_email
    FROM book_requests r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                        <h2 class="fw-bold mb-1"><i class="bi bi-clipboard2-check-fill me-2"></i>Manage Book Requests</h2>
                        <p class="mb-0 opacity-75 lead">Review, approve, and edit book requests from students.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card glass-card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold m-0"><i class="bi bi-collection-fill me-2 text-primary"></i>All Requests</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Student Info</th>
                                    <th>Book Details</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($requests)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            No requests found.
                                        </td>
                                    </tr>
                                <?php else: foreach($requests as $req): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold"><?= htmlspecialchars($req['student_name']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($req['student_email']) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-primary"><?= htmlspecialchars($req['book_title']) ?></div>
                                            <div class="small text-muted">by <?= htmlspecialchars($req['author']) ?></div>
                                        </td>
                                        <td style="max-width: 250px;">
                                            <div class="small text-truncate" title="<?= htmlspecialchars($req['reason']) ?>">
                                                <?= htmlspecialchars($req['reason']) ?>
                                            </div>
                                        </td>
                                        <td><?= getStatusBadge($req['status']) ?></td>
                                        <td><?= date('d M Y', strtotime($req['created_at'])) ?></td>
                                        <td class="pe-4 text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <button onclick='updateRequestStatus(<?= htmlspecialchars(json_encode($req), ENT_QUOTES, "UTF-8") ?>, "approved")' class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" title="Approve">
                                                    <i class="bi bi-check-circle me-1"></i>Approve
                                                </button>
                                                <button onclick='updateRequestStatus(<?= htmlspecialchars(json_encode($req), ENT_QUOTES, "UTF-8") ?>, "rejected")' class="btn btn-sm btn-danger rounded-pill px-3 shadow-sm" title="Reject">
                                                    <i class="bi bi-x-circle me-1"></i>Reject
                                                </button>
                                                <button onclick='editRequest(<?= htmlspecialchars(json_encode($req), ENT_QUOTES, "UTF-8") ?>)' class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                                                    <i class="bi bi-pencil-square me-1"></i>Edit
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
</div>

<!-- Edit Request Modal -->
<div class="modal fade" id="editRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-0 pb-0" style="background: linear-gradient(135deg, var(--erp-primary) 0%, var(--erp-primary-dark) 100%); color: white; border-radius: 12px 12px 0 0;">
                <h5 class="modal-title fw-bold py-2"><i class="bi bi-pencil-square me-2"></i>Edit Book Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="editRequestForm">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="action" value="update_request">
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Book Title</label>
                            <input type="text" name="book_title" id="edit_title" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Author</label>
                            <input type="text" name="author" id="edit_author" class="form-control" required>
                        </div>
                    </div>

                    <hr class="text-muted opacity-25">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="purchased">Purchased</option>
                                <option value="rejected">Rejected</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Admin Notes (visible to student)</label>
                            <textarea name="admin_notes" id="edit_notes" class="form-control" rows="2" placeholder="Explain rejection or provide updates..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 pe-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" onclick="submitEditForm()" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-save me-1"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let editModal;

document.addEventListener('DOMContentLoaded', function() {
    editModal = new bootstrap.Modal(document.getElementById('editRequestModal'));
});

function editRequest(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_title').value = data.book_title;
    document.getElementById('edit_author').value = data.author;
    document.getElementById('edit_status').value = data.status;
    document.getElementById('edit_notes').value = data.admin_notes || '';
    
    editModal.show();
}

function submitEditForm() {
    const form = document.getElementById('editRequestForm');
    if(!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);

    fetch('ajax_manage_requests.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            editModal.hide();
            Swal.fire({
                title: 'Saved!',
                text: 'Request has been updated successfully.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => location.reload());
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Server error while saving.', 'error');
    });
}

function updateRequestStatus(req, status) {
    let actionText = status === 'approved' ? 'approve' : 'reject';
    let confirmColor = status === 'approved' ? '#198754' : '#dc3545';
    
    Swal.fire({
        title: 'Are you sure?',
        text: `Do you want to ${actionText} this request?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, ' + actionText + ' it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'update_request');
            formData.append('id', req.id);
            formData.append('book_title', req.book_title);
            formData.append('author', req.author);
            formData.append('genre', req.genre || '');
            formData.append('isbn', req.isbn || '');
            formData.append('status', status);
            formData.append('admin_notes', req.admin_notes || '');

            fetch('ajax_manage_requests.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Request has been ' + status + '.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Server error while saving.', 'error');
            });
        }
    });
}
</script>

<?php require_once '../../includes/footer.php'; ?>
