<?php 
require_once '../../includes/header.php'; 
require_once '../../core/library_functions.php';

$lib = new Library($pdo);
$userId = $_SESSION['user_id'];
$borrowings = $lib->getStudentBorrowings($userId);
?>

<div class="container-fluid px-4 mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h1 class="h3 mb-0 text-gray-800">Reading History</h1>
            <p class="text-muted small">Your full history of library transactions and borrowings.</p>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary btn-sm me-2" onclick="exportHistory()">
                <i class="bi bi-download me-2"></i>Export as CSV
            </button>
            <a href="student_dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-2"></i>Dashboard
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="m-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Transaction Log</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Book Details</th>
                            <th>Borrowed</th>
                            <th>Due</th>
                            <th>Returned</th>
                            <th>Status/Fine</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($borrowings)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">No reading history found. Start exploring books!</td></tr>
                        <?php else: foreach ($borrowings as $b): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($b['title']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($b['author_name'] ?? '---') ?></div>
                                </td>
                                <td><?= date('d M Y', strtotime($b['borrow_date'])) ?></td>
                                <td><?= date('d M Y', strtotime($b['due_date'])) ?></td>
                                <td><?= $b['return_date'] ? date('d M Y', strtotime($b['return_date'])) : '---' ?></td>
                                <td>
                                    <?php if ($b['status'] == 'returned'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 mb-1">
                                            Returned
                                        </span>
                                        <?php if ($lib->canUserReviewBook($userId, $b['book_id'])): ?>
                                            <br>
                                            <button class="btn btn-sm btn-outline-primary mt-1" style="font-size: 0.65rem;" onclick="openReviewModal(<?= $b['book_id'] ?>, '<?= htmlspecialchars($b['title']) ?>')">
                                                <i class="bi bi-star-fill me-1"></i>Rate & Review
                                            </button>
                                        <?php else: ?>
                                            <br>
                                            <span class="text-muted extra-small"><i class="bi bi-check2-all me-1"></i>Reviewed</span>
                                        <?php endif; ?>
                                    <?php elseif ($b['status'] == 'overdue'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 mb-1">
                                            Overdue
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 mb-1">
                                            Active
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($b['fine_amount'] > 0): ?>
                                        <div class="small fw-bold text-danger">Rs. <?= number_format($b['fine_amount'], 2) ?></div>
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

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalBookTitle">Rate Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="reviewForm">
                    <input type="hidden" name="book_id" id="reviewBookId">
                    <div class="mb-4 text-center">
                        <label class="form-label d-block text-muted small fw-bold text-uppercase mb-3">Your Rating</label>
                        <div class="star-rating fs-2">
                            <i class="bi bi-star star-btn" data-value="1"></i>
                            <i class="bi bi-star star-btn" data-value="2"></i>
                            <i class="bi bi-star star-btn" data-value="3"></i>
                            <i class="bi bi-star star-btn" data-value="4"></i>
                            <i class="bi bi-star star-btn" data-value="5"></i>
                        </div>
                        <input type="hidden" name="rating" id="reviewRating" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold text-uppercase">Your Feedback</label>
                        <textarea name="comment" class="form-control border-0 bg-light rounded-4 p-3" rows="4" placeholder="What did you think of this book?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold rounded-pill">Submit Review</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .star-rating .bi-star, .star-rating .bi-star-fill { cursor: pointer; color: #fbbf24; transition: transform 0.2s; margin: 0 5px; }
    .star-rating .bi-star:hover { transform: scale(1.2); }
    .extra-small { font-size: 0.7rem; }
</style>

<script>
let currentModal;

function openReviewModal(bookId, title) {
    document.getElementById('reviewBookId').value = bookId;
    document.getElementById('modalBookTitle').innerText = 'Review: ' + title;
    
    // Reset stars
    document.getElementById('reviewRating').value = 0;
    document.querySelectorAll('.star-btn').forEach(s => {
        s.classList.remove('bi-star-fill');
        s.classList.add('bi-star');
    });

    if(!currentModal) currentModal = new bootstrap.Modal(document.getElementById('reviewModal'));
    currentModal.show();
}

document.querySelectorAll('.star-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const val = parseInt(this.getAttribute('data-value'));
        document.getElementById('reviewRating').value = val;
        
        document.querySelectorAll('.star-btn').forEach(s => {
            const sVal = parseInt(s.getAttribute('data-value'));
            if(sVal <= val) {
                s.classList.remove('bi-star');
                s.classList.add('bi-star-fill');
            } else {
                s.classList.remove('bi-star-fill');
                s.classList.add('bi-star');
            }
        });
    });
});

document.getElementById('reviewForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const rating = document.getElementById('reviewRating').value;
    if(rating == 0) return alert('Please select a star rating.');

    const formData = new FormData(this);
    formData.append('action', 'submit_review');

    try {
        const res = await fetch('ajax_student_actions.php', { method: 'POST', body: formData });
        const data = await res.json();
        alert(data.message);
        if(data.success) location.reload();
    } catch(err) {
        alert('An error occurred.');
    }
});

function exportHistory() {
    window.location.href = 'ajax_student_actions.php?action=export_history';
}
</script>

<?php require_once '../../includes/footer.php'; ?>
