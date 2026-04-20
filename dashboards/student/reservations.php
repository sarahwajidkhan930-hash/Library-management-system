<?php 
require_once '../../includes/header.php'; 
require_once '../../core/library_functions.php';

$lib = new Library($pdo);
$userId = $_SESSION['user_id'];
$reservations = $lib->getStudentReservations($userId);
?>

<div class="container-fluid px-4 mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h1 class="h3 mb-0 text-gray-800">My Book Reservations</h1>
            <p class="text-muted small">Manage your pending holds and reservations.</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="student_dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="m-0 fw-bold text-primary"><i class="bi bi-calendar-check me-2"></i>Active Reservations</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Book Details</th>
                            <th>Reserved Date</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservations)): ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">You have no pending reservations. Browse the catalog to hold books.</td></tr>
                        <?php else: foreach ($reservations as $r): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($r['title']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($r['author_name'] ?? 'Unknown Author') ?></div>
                                    <div class="small text-primary-emphasis"><?= htmlspecialchars($r['category_name']) ?></div>
                                </td>
                                <td><?= date('d M Y, h:i A', strtotime($r['reserved_at'])) ?></td>
                                <td>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2">
                                        <i class="bi bi-hourglass-split me-1"></i>Pending
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-danger px-3 rounded-pill" onclick="cancelReservation(<?= $r['id'] ?>)">
                                        <i class="bi bi-trash me-1"></i>Cancel Hold
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

<script>
async function cancelReservation(resId) {
    if(!confirm('Are you sure you want to cancel this reservation?')) return;
    
    const formData = new FormData();
    formData.append('action', 'cancel_reservation');
    formData.append('reservation_id', resId);

    try {
        const response = await fetch('ajax_student_actions.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if(result.success) location.reload();
        else alert(result.message);
    } catch (e) {
        alert('An error occurred.');
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
