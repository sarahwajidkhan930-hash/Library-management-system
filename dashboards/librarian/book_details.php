<?php
require_once '../../core/config.php';
require_once '../../core/db.php';
require_once '../../core/library_functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Role guard
$allowedRoles = ['librarian', 'assistant_manager', 'super_admin'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $allowedRoles)) {
    header('Location: ../../login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: manage_inventory.php');
    exit;
}

$lib = new Library($pdo);
$book = $lib->getBookById($id);

if (!$book) {
    header('Location: manage_inventory.php');
    exit;
}

// Fetch active borrowings for this book
$stmt = $pdo->prepare("
    SELECT br.*, u.name as student_name, u.email as student_email 
    FROM lib_borrowings br
    JOIN users u ON br.user_id = u.id
    WHERE br.book_id = ? AND br.status != 'returned'
    ORDER BY br.borrow_date DESC
");
$stmt->execute([$id]);
$activeLoans = $stmt->fetchAll();

// Fetch Reviews & Stats
$reviews = $lib->getBookReviews($id);
$ratingStats = $lib->getBookRatingStats($id);

require_once '../../includes/header.php';
?>

<style>
    .book-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    .book-header {
        background: linear-gradient(135deg, var(--erp-primary) 0%, var(--erp-primary-dark) 100%);
        padding: 3rem 2rem;
        color: white;
    }
    .status-badge {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0.5rem 1rem;
        border-radius: 50px;
    }
    .info-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #94a3b8;
        letter-spacing: 0.05em;
        margin-bottom: 0.25rem;
    }
    .info-value {
        font-weight: 600;
        color: #1e293b;
        font-size: 1.1rem;
    }
</style>

<div class="content-header px-4 pt-4">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-6 text-start">
                <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>
    </div>
</div>

<div class="content px-4 pb-5">
    <div class="container-fluid">
        <div class="card book-card mb-4 mt-3">
            <div class="book-header">
                <div class="row align-items-center">
                    <?php if (!empty($book['cover_image'])): ?>
                    <div class="col-md-3 text-center mb-4 mb-md-0">
                        <img src="../../assets/img/covers/<?= htmlspecialchars($book['cover_image']) ?>" alt="Cover Art" class="img-fluid rounded-4 shadow-lg" style="max-height: 250px; border: 4px solid rgba(255,255,255,0.15);">
                    </div>
                    <div class="col-md-9 text-center text-md-start">
                    <?php else: ?>
                    <div class="col-12 text-center">
                    <?php endif; ?>
                        
                        <div class="mb-3">
                            <?php if ($book['is_issueable'] == 1): ?>
                        <span class="status-badge bg-white text-success shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i>Available for Issue
                        </span>
                    <?php else: ?>
                        <span class="status-badge bg-white text-danger shadow-sm">
                            <i class="bi bi-shield-lock me-1"></i>Library Use Only
                        </span>
                    <?php endif; ?>
                </div>
                <h1 class="fw-bold mb-1" style="font-size: 2.5rem;"><?= htmlspecialchars($book['title']) ?></h1>
                <p class="lead opacity-75 fw-medium mb-2">by <?= htmlspecialchars($book['author_name'] ?? 'Unknown Author') ?></p>
                
                <?php if ($ratingStats['count'] > 0): ?>
                    <div class="d-flex justify-content-center align-items-center gap-2">
                        <div class="text-warning fs-4">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <i class="bi bi-star<?= ($i <= round($ratingStats['average'])) ? '-fill' : '' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="fw-bold"><?= $ratingStats['average'] ?></span>
                        <span class="opacity-50 small">(<?= $ratingStats['count'] ?> reviews)</span>
                    </div>
                <?php endif; ?>
                
                    </div> <!-- End of Col -->
                </div> <!-- End of Row -->
            </div>
            
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="info-label">Category</div>
                        <div class="info-value">
                            <i class="bi bi-tag-fill text-warning me-2"></i><?= htmlspecialchars($book['category_name'] ?? 'Uncategorised') ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">ISBN Number</div>
                        <div class="info-value"><?= htmlspecialchars($book['isbn'] ?: 'N/A') ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Fine Per Day</div>
                        <div class="info-value text-danger">Rs. <?= number_format($book['fine_per_day'] ?? 10, 2) ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Book Type</div>
                        <div class="info-value"><?= htmlspecialchars($book['book_type'] ?: 'Standard') ?></div>
                    </div>
                </div>

                <hr class="my-4 opacity-5">

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-4 p-4 h-100">
                            <h5 class="fw-bold mb-4"><i class="bi bi-info-circle me-2 text-primary"></i>Collection Summary</h5>
                            <div class="d-flex justify-content-between mb-3 align-items-center">
                                <span class="text-muted">Total Stock</span>
                                <span class="fw-bold fs-4"><?= $book['total_copies'] ?> <small class="text-muted fw-normal" style="font-size: 0.8rem;">copies</small></span>
                            </div>
                            <div class="d-flex justify-content-between mb-0 align-items-center">
                                <span class="text-muted">On Shelf (Avail)</span>
                                <span class="fw-bold fs-4 text-success"><?= $book['available_copies'] ?> <small class="text-muted fw-normal" style="font-size: 0.8rem;">copies</small></span>
                            </div>
                            <div class="progress mt-3" style="height: 10px; border-radius: 50px;">
                                <?php 
                                $perc = ($book['total_copies'] > 0) ? ($book['available_copies'] / $book['total_copies'] * 100) : 0;
                                ?>
                                <div class="progress-bar bg-success" style="width: <?= $perc ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-4 p-4 h-100">
                            <h5 class="fw-bold mb-3"><i class="bi bi-text-left me-2 text-primary"></i>Description</h5>
                            <p class="text-muted mb-0">
                                <?= !empty($book['description']) ? nl2br(htmlspecialchars($book['description'])) : 'No detailed description available for this book in the library database.' ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Borrowing History / Active Loans -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 py-3 px-4">
                <h5 class="fw-bold m-0 text-dark">
                    <i class="bi bi-arrow-left-right me-2 text-primary"></i>Active Borrowers
                    <span class="badge bg-primary ms-2"><?= count($activeLoans) ?></span>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($activeLoans)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                        This book is currently not borrowed by any student.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Student Name</th>
                                    <th class="text-center">Issued Date</th>
                                    <th class="text-center">Due Date</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeLoans as $loan): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold"><?= htmlspecialchars($loan['student_name']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($loan['student_email']) ?></div>
                                        </td>
                                        <td class="text-center"><?= date('d M Y', strtotime($loan['borrow_date'])) ?></td>
                                        <td class="text-center"><?= date('d M Y', strtotime($loan['due_date'])) ?></td>
                                        <td class="text-center">
                                            <?php if ($loan['status'] == 'overdue'): ?>
                                                <span class="badge bg-danger rounded-pill px-3">Overdue</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary rounded-pill px-3">Active</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Student Reviews -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mt-4">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold m-0 text-dark">
                    <i class="bi bi-chat-left-quote me-2 text-primary"></i>Student Feedback
                </h5>
                <?php if ($ratingStats['count'] > 0): ?>
                    <span class="badge bg-warning text-dark fw-bold rounded-pill px-3">
                        <?= $ratingStats['average'] ?> / 5 Stars
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body p-4">
                <?php if (empty($reviews)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-chat-dots fs-2 d-block mb-2 opacity-25"></i>
                        No reviews yet. Be the first to share your thoughts!
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($reviews as $rev): ?>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-4 h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <div class="fw-bold small"><?= htmlspecialchars($rev['student_name']) ?></div>
                                            <div class="text-warning extra-small">
                                                <?php for($i=1; $i<=5; $i++): ?>
                                                    <i class="bi bi-star<?= ($i <= $rev['rating']) ? '-fill' : '' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <div class="extra-small text-muted opacity-75">
                                            <?= date('d M Y', strtotime($rev['created_at'])) ?>
                                        </div>
                                    </div>
                                    <p class="small text-muted mb-0 italic">"<?= nl2br(htmlspecialchars($rev['comment'])) ?>"</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .extra-small { font-size: 0.7rem; }
    .italic { font-style: italic; }
</style>

<?php require_once '../../includes/footer.php'; ?>
