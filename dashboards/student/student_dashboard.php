<?php 
require_once '../../includes/header.php'; 
require_once '../../core/library_functions.php';

$lib = new Library($pdo);
$userId = $_SESSION['user_id'];

// Run automated overdue sync and due-date alert checks
$lib->updateOverdueStatus();
$lib->checkAndGenerateDueAlerts($userId);

// Initial data fetch
$stats = $lib->getStudentStats($userId);
$borrowings = $lib->getStudentBorrowings($userId);
$transactions = $lib->getStudentTransactions($userId);

// Separate Current Borrowings from History
$currentBorrowings = array_filter($borrowings, function($b) {
    return $b['status'] != 'returned';
});
$returnedHistory = array_filter($borrowings, function($b) {
    return $b['status'] == 'returned';
});
$search = $_GET['search'] ?? '';
$books = $lib->getBooks($search);
?>

<style>
    :root {
        --std-primary: var(--erp-primary);
        --std-accent: #f87171;
        --std-bg: #f8fafc;
        --std-card-bg: #ffffff;
        --std-text-dark: #0f172a;
        --std-text-muted: #64748b;
        --std-border: #e2e8f0;
    }
    
    body {
        background-color: var(--std-bg);
        color: var(--std-text-dark);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }
    
    .dashboard-wrapper { padding: 1.5rem; }
    .page-header { margin-bottom: 2rem; }
    .page-title { font-weight: 800; letter-spacing: -0.025em; color: var(--std-primary); font-size: 1.75rem; }
    
    /* Stat Cards */
    .stat-card-custom {
        background: var(--std-card-bg);
        border: 1px solid var(--std-border);
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        border-bottom: 4px solid var(--std-border);
    }
    .stat-card-custom:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
    .stat-card-primary { border-bottom-color: var(--std-primary); }
    .stat-card-info { border-bottom-color: #3b82f6; }
    .stat-card-warning { border-bottom-color: #f59e0b; }
    .stat-card-danger { border-bottom-color: #ef4444; }
    .stat-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--std-text-muted); margin-bottom: 0.5rem; }
    .stat-value { font-size: 2rem; font-weight: 800; color: var(--std-text-dark); line-height: 1; }
    
    /* Section Cards */
    .glass-card {
        background: var(--std-card-bg);
        border: 1px solid var(--std-border);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 2rem;
    }
    .card-header-custom { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--std-border); background: #fff; display: flex; justify-content: space-between; align-items: center; }
    
    /* Table Styling */
    .table-custom thead th { background-color: #f8fafc; padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--std-text-muted); border-top: none; }
    .table-custom tbody td { padding: 1.25rem 1.5rem; vertical-align: middle; }
    .badge-status { padding: 0.4rem 0.8rem; font-weight: 600; border-radius: 8px; font-size: 0.75rem; }
    
    /* Book Items */
    .book-item { transition: all 0.2s; border-bottom: 1px solid var(--std-border); padding: 1rem 0; }
    .book-item:last-child { border-bottom: none; }
    .book-item:hover { background-color: #f8fafc; }
    .btn-search { background-color: var(--std-primary); color: white; border: none; }
    .btn-search:hover { background-color: #7f1d1d; color: white; }

    .btn-reserve { font-size: 0.7rem; padding: 0.2rem 0.6rem; border-radius: 6px; }

    /* Global Redundancy Fix */
    /* .app-content-header { display: none !important; } - Removed for better spacing */

    /* Awareness Pulse */
    @keyframes alertPulse {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
        70% { transform: scale(1.02); box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
    .animate-urgent { animation: alertPulse 2s infinite; }
</style>

<div class="content-body py-2">
    <!-- Critical Actions Awareness -->
    <?php if ($stats['overdue_count'] > 0): ?>
        <div class="alert bg-danger border-0 text-white d-flex align-items-center mb-4 p-4 shadow-lg animate-urgent" style="border-radius: 20px;">
            <i class="bi bi-exclamation-octagon-fill fs-1 me-4"></i>
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-1">CRITICAL ACTION REQUIRED</h4>
                <p class="mb-0 opacity-90">You have <strong><?= $stats['overdue_count'] ?> overdue items</strong> that require immediate return to avoid further penalties.</p>
            </div>
            <a href="history.php" class="btn btn-light rounded-pill px-4 fw-bold">Return Now</a>
        </div>
    <?php elseif ($stats['total_fines'] > 0): ?>
        <div class="alert bg-primary border-0 text-white d-flex align-items-center mb-4 p-4 shadow-lg shadow-primary-subtle" style="border-radius: 20px;">
            <i class="bi bi-cash-stack fs-1 me-4"></i>
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-1">OUTSTANDING BALANCE</h4>
                <p class="mb-0 opacity-90">Your account has an outstanding balance of <strong>Rs. <?= number_format($stats['total_fines'], 2) ?></strong>. Please settle this to maintain borrowing privileges.</p>
            </div>
            <a href="fines.php" class="btn btn-light text-primary rounded-pill px-4 fw-bold">Pay Now</a>
        </div>
    <?php endif; ?>

    <!-- Welcome Section (Minimalist) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title mb-1">Welcome back, <?= explode(' ', $_SESSION['name'])[0] ?>!</h1>
                    <p class="text-muted small mb-0">Here is a quick look at your library status.</p>
                </div>
                <div class="d-none d-md-block">
                    <a href="ajax_student_actions.php?action=export_history" class="btn btn-outline-primary rounded-pill px-4 btn-sm shadow-sm">
                        <i class="bi bi-file-earmark-arrow-down me-2"></i>Export History
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-card-custom stat-card-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Total Borrowed</div>
                        <div class="stat-value"><?= $stats['total_borrowed'] ?></div>
                    </div>
                    <i class="bi bi-book fs-1 opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3" onclick="location.href='history.php'" style="cursor: pointer;">
            <div class="stat-card-custom stat-card-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Currently Held</div>
                        <div class="stat-value"><?= $stats['currently_held'] ?></div>
                    </div>
                    <i class="bi bi-bookmark-check fs-1 opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-custom stat-card-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Overdue Books</div>
                        <div class="stat-value text-warning"><?= $stats['overdue_count'] ?></div>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-1 opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3" onclick="location.href='fines.php'" style="cursor: pointer;">
            <div class="stat-card-custom stat-card-danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Total Fines</div>
                        <div class="stat-value text-danger">Rs. <?= number_format($stats['total_fines'], 2) ?></div>
                    </div>
                    <?php if ($stats['total_fines'] > 0): ?>
                        <button class="btn btn-sm btn-danger rounded-circle p-2" title="Clear Dues" onclick="event.stopPropagation(); payFines();">
                            <i class="bi bi-credit-card-2-back fs-4"></i>
                        </button>
                    <?php else: ?>
                        <i class="bi bi-cash-stack fs-1 opacity-25"></i>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Active Borrowings Only -->
            <div class="card glass-card mb-4">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>My Current Borrowings</h5>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3"><?= count($currentBorrowings) ?> Active</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom align-middle">
                            <thead>
                                <tr>
                                    <th>Book Details</th>
                                    <th>Borrowed Date</th>
                                    <th>Due Date</th>
                                    <th class="text-center">Overdue Details</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($currentBorrowings) > 0): foreach($currentBorrowings as $b): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($b['cover_image'])): ?>
                                                    <img src="../../assets/img/covers/<?= htmlspecialchars($b['cover_image']) ?>" class="rounded shadow-sm me-3" style="width: 40px; height: 55px; object-fit: cover;" alt="Cover">
                                                <?php else: ?>
                                                    <div class="rounded shadow-sm me-3 bg-light d-flex align-items-center justify-content-center text-primary" style="width: 40px; height: 55px;">
                                                        <i class="bi bi-book"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-bold text-dark lh-sm mb-1"><?= htmlspecialchars($b['title']) ?></div>
                                                    <div class="small text-muted"><?= htmlspecialchars($b['author_name'] ?? 'Unknown Author') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= date('d M Y', strtotime($b['borrow_date'])) ?></td>
                                        <td>
                                            <span class="<?= (strtotime($b['due_date']) < time()) ? 'text-danger fw-bold' : '' ?>">
                                                <?= date('d M Y', strtotime($b['due_date'])) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                            $daysOverdue = floor((time() - strtotime($b['due_date'])) / 86400);
                                            $accruedFine = $daysOverdue > 0 ? $daysOverdue * 10 : 0;
                                            
                                            if ($daysOverdue > 0): 
                                            ?>
                                                <div class="small fw-bold text-danger mb-1 d-block"><?= $daysOverdue ?> Days Late</div>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.70rem;">Fine: Rs. <?= number_format($accruedFine, 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small italic">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($b['status'] == 'overdue'): ?>
                                                <span class="badge text-bg-danger badge-status mb-1">OVERDUE</span>
                                            <?php elseif (isset($b['is_issueable']) && $b['is_issueable'] == 0): ?>
                                                <span class="badge text-bg-warning badge-status">RECALLED</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-success badge-status">ACTIVE</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">You have no active borrowings at the moment.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Historical Archive (Separated Log) -->
            <div class="card glass-card mb-4" style="opacity: 0.85;">
                <div class="card-header-custom bg-light border-bottom">
                    <h5 class="fw-bold mb-0 text-muted small"><i class="bi bi-archive me-2"></i>Recently Returned History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <tbody class="text-muted">
                                <?php if (count($returnedHistory) > 0): foreach(array_slice($returnedHistory, 0, 3) as $history): ?>
                                    <tr>
                                        <td class="ps-4 py-3" style="width: 50%">
                                            <div class="small fw-600"><?= htmlspecialchars($history['title']) ?></div>
                                        </td>
                                        <td class="small">Returned: <?= date('d M Y', strtotime($history['return_date'])) ?></td>
                                        <td class="text-end pe-4">
                                            <span class="badge bg-secondary-subtle text-secondary extra-small">ARCHIVED</span>
                                        </td>
                                    </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="3" class="text-center py-4 extra-small text-muted italic">No historical records available in this view.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Quick Reservation View link (or just keep it in sidebar) -->
            <div class="alert bg-info-subtle border-info-subtle d-flex align-items-center p-4 rounded-4 shadow-sm mb-4">
                <i class="bi bi-calendar-check fs-1 text-info me-4"></i>
                <div>
                   <h5 class="text-info-emphasis fw-bold mb-1">Looking for your reservations?</h5>
                   <p class="text-info-emphasis opacity-75 mb-0">Check the dedicated <a href="reservations.php" class="fw-bold text-info">Reservations Page</a> to manage your pending holds.</p>
                </div>
            </div>
        </div>

        <!-- Sidebar Discovery -->
        <div class="col-lg-4">
            <div class="card glass-card">
                <div class="card-header-custom">
                    <h5 class="m-0 fw-bold"><i class="bi bi-search me-2 text-info"></i>Discover Books</h5>
                </div>
                <div class="card-body">
                    <form action="" method="GET" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search by title, author..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-search"><i class="bi bi-search"></i></button>
                        </div>
                    </form>

                    <div class="book-list" style="max-height: 500px; overflow-y: auto;">
                        <?php if (empty($books)): ?>
                            <div class="text-center py-4 text-muted">No books found matching your search.</div>
                        <?php else: foreach ($books as $book): ?>
                            <div class="book-item d-flex align-items-start px-2">
                                <div class="flex-shrink-0 mt-1" style="width: 50px;">
                                    <?php if (!empty($book['cover_image'])): ?>
                                        <img src="../../assets/img/covers/<?= htmlspecialchars($book['cover_image']) ?>" alt="Cover" class="img-fluid rounded shadow-sm" style="width: 100%; height: auto; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded p-2 text-primary text-center">
                                            <i class="bi bi-book-half fs-4"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="fw-bold text-dark lh-sm mb-1"><?= htmlspecialchars($book['title']) ?></div>
                                    <div class="small text-muted mb-1"><?= htmlspecialchars($book['author_name']) ?></div>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <?php 
                                            // Check if user currently holds this book
                                            $userHoldingStatus = false;
                                            foreach ($currentBorrowings as $cb) {
                                                if ($cb['book_id'] == $book['id']) {
                                                    $userHoldingStatus = $cb['status']; // 'borrowed' or 'overdue'
                                                    break;
                                                }
                                            }
                                            ?>
                                            <?php if ($userHoldingStatus === 'overdue'): ?>
                                                <span class="badge rounded-pill bg-danger text-white px-2 py-1" style="font-size: 0.65rem;">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Your Copy: Overdue
                                                </span>
                                            <?php elseif ($userHoldingStatus === 'borrowed'): ?>
                                                <span class="badge rounded-pill bg-info text-dark px-2 py-1" style="font-size: 0.65rem;">
                                                    <i class="bi bi-bookmark-check-fill me-1"></i>Currently Borrowed By You
                                                </span>
                                            <?php elseif (isset($book['is_issueable']) && $book['is_issueable'] == 0): ?>
                                                <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 0.65rem;">
                                                    <i class="bi bi-slash-circle me-1"></i>Reference Only
                                                </span>
                                            <?php elseif ($book['available_copies'] > 0): ?>
                                                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 0.65rem;">
                                                    <i class="bi bi-check-circle me-1"></i>Available
                                                </span>
                                            <?php else: ?>
                                                <span class="badge rounded-pill bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1" style="font-size: 0.65rem;">
                                                    <i class="bi bi-x-circle me-1"></i>Out of Stock
                                                </span>
                                                <button class="btn btn-outline-info btn-reserve ms-2" onclick="reserveBook(<?= $book['id'] ?>)">
                                                    Reserve
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        <span class="small text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($book['category_name']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function reserveBook(bookId) {
    if(!confirm('Do you want to reserve this book? You will be notified when it is available.')) return;
    
    const formData = new FormData();
    formData.append('action', 'reserve');
    formData.append('book_id', bookId);

    try {
        const response = await fetch('ajax_student_actions.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        alert(result.message);
        if(result.success) location.reload();
    } catch (e) {
        alert('An error occurred. Please try again.');
    }
}
async function payFines() {
    if(!confirm('This will simulate a payment transaction. Proceed to clear Rs. <?= number_format($stats['total_fines'], 2) ?>?')) return;
    
    const formData = new FormData();
    formData.append('action', 'settle_fines');

    try {
        const response = await fetch('ajax_student_actions.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        alert(result.message);
        if(result.success) location.reload();
    } catch (e) {
        alert('Payment processing failed.');
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
