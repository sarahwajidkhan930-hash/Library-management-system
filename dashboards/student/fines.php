<?php 
require_once '../../includes/header.php'; 
require_once '../../core/library_functions.php';

$lib = new Library($pdo);
$userId = $_SESSION['user_id'];
$stats = $lib->getStudentStats($userId);
?>

<div class="container-fluid px-4 mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h1 class="h3 mb-0 text-gray-800">My Fines & Payments</h1>
            <p class="text-muted small">Manage outstanding library fines and settlement history.</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="student_dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-danger text-white mb-4 overflow-hidden position-relative">
                <div class="card-body p-4">
                    <div class="stat-label opacity-75 small fw-bold text-uppercase mb-2">Total Outstanding Balance</div>
                    <div class="h1 fw-800 mb-0">Rs. <?= number_format($stats['total_fines'], 2) ?></div>
                    <i class="bi bi-cash-stack fs-1 position-absolute top-50 end-0 translate-middle-y me-4 opacity-25"></i>
                </div>
            </div>

            <?php if ($stats['total_fines'] > 0): ?>
                <div class="card shadow-sm border-0 mb-4 p-4 text-center bg-light">
                    <h5 class="fw-bold mb-3">Settle Outstanding Fines</h5>
                    <p class="text-muted small mb-4">You have unpaid fines. You can settle them now via our simulated payment gateway.</p>
                    <button class="btn btn-danger w-100 py-2 fw-bold" onclick="settleFines()">
                        <i class="bi bi-credit-card me-2"></i>Settle via Simulated Payment
                    </button>
                </div>
            <?php else: ?>
                <div class="card shadow-sm border-0 p-4 text-center bg-success-subtle border border-success-subtle text-success">
                    <i class="bi bi-check-circle-fill fs-1 mb-2"></i>
                    <h5 class="fw-bold mb-1">Clear!</h5>
                    <p class="small mb-0">You have no outstanding library fines.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="m-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Fine Accumulation</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4">Item Details</th>
                                    <th>Return Date</th>
                                    <th class="text-end pe-4">Fine Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $borrowings = $lib->getStudentBorrowings($userId);
                                $foundAny = false;
                                foreach($borrowings as $b): if($b['fine_amount'] > 0): 
                                    $foundAny = true;
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold"><?= htmlspecialchars($b['title']) ?></div>
                                            <div class="small text-muted"><?= $b['status'] == 'returned' ? 'Settled on Return' : 'Pending' ?></div>
                                        </td>
                                        <td><?= $b['return_date'] ? date('d M Y', strtotime($b['return_date'])) : '---' ?></td>
                                        <td class="text-end pe-4 fw-bold text-danger">Rs. <?= number_format($b['fine_amount'], 2) ?></td>
                                    </tr>
                                <?php endif; endforeach; 
                                if (!$foundAny): ?>
                                    <tr><td colspan="3" class="text-center py-5 text-muted">No fine records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function settleFines() {
    if(!confirm('This is a simulated payment. Do you want to settle all outstanding fines?')) return;
    
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
        alert('An error occurred.');
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
