<?php
require_once '../../core/config.php';
require_once '../../core/db.php';
require_once '../../core/audit_helper.php';
session_start();

// Security check: Librarians and Assistant Managers can access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['librarian', 'assistant_manager', 'super_admin'])) {
    header("Location: ../../login.php");
    exit();
}

$currentRole = $_SESSION['role'] ?? '';
$isLibrarian = in_array($currentRole, ['librarian', 'super_admin']);

// MySQLi Connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) { die("Connection failed: " . $mysqli->connect_error); }

// ── PAGINATION LOGIC ─────────────────────────────────────────
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Handle Search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchQuery = "";
$searchParams = [];
$searchTypes = "";

if (!empty($search)) {
    $searchQuery = " AND (u.name LIKE ? OR u.identity_no LIKE ? OR u.email LIKE ? OR u.department LIKE ?)";
    $searchTerm = "%$search%";
    $searchParams = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    $searchTypes = "ssss";
}

// Get Total Records for Pagination
$countSql = "SELECT COUNT(*) FROM users u WHERE u.role = 'student' $searchQuery";
$stmt = $mysqli->prepare($countSql);
if (!empty($search)) {
    $stmt->bind_param($searchTypes, ...$searchParams);
}
$stmt->execute();
$total_records = $stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_records / $limit);

// Fetch Paginated Results
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM lib_borrowings WHERE user_id = u.id AND status = 'borrowed') as currently_held,
          (SELECT IFNULL(SUM(fine_amount), 0) FROM lib_borrowings WHERE user_id = u.id) as total_fines
          FROM users u 
          WHERE u.role = 'student' $searchQuery
          ORDER BY u.id DESC
          LIMIT ?, ?";

$stmt = $mysqli->prepare($query);
if (!empty($search)) {
    $allParams = array_merge($searchParams, [$offset, $limit]);
    $stmt->bind_param($searchTypes . "ii", ...$allParams);
} else {
    $stmt->bind_param("ii", $offset, $limit);
}
$stmt->execute();
$result = $stmt->get_result();

require_once '../../includes/header.php';
?>

<style>
    :root { --premium-crimson: var(--erp-primary); --surface-grey: var(--erp-bg-main); }
    body { background-color: var(--surface-grey) !important; }
    .premium-card { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07); overflow: hidden; border: none; }
    .premium-header { padding: 1.5rem; border-bottom: 2px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
    .premium-title { color: var(--premium-crimson); font-weight: 800; display: flex; align-items: center; margin: 0; }
    .table-premium thead th { background: #f8fafc; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700; padding: 1.25rem 1rem; border-bottom: 2px solid #f1f5f9; }
    .table-premium tbody td { padding: 1.25rem 1rem; vertical-align: middle; font-size: 0.9rem; color: #334155; border-bottom: 1px solid #f1f5f9; }
    .search-input { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.5rem 1rem; }
    .search-btn { background: var(--premium-crimson); color: white; border-radius: 10px; border: none; padding: 0 1rem; }
    .status-badge { padding: 0.4rem 1rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
    
    /* Sophisticated Modal Styles */
    .modal-premium-header {
        background: linear-gradient(135deg, var(--erp-primary) 0%, var(--erp-primary-dark) 100%);
        padding: 2.5rem 2rem;
        border-radius: 25px 25px 0 0;
        position: relative;
        overflow: hidden;
    }
    .modal-premium-header::after {
        content: ""; position: absolute; top: -50%; right: -20%; width: 200px; height: 200px;
        background: rgba(255,255,255,0.03); border-radius: 50%;
    }
    .avatar-sophisticated {
        width: 80px; height: 80px; background: rgba(255,255,255,0.15);
        backdrop-filter: blur(10px); border: 2px solid rgba(255,255,255,0.2);
        border-radius: 20px; display: flex; align-items: center; justify-content: center;
        font-size: 2rem; color: white; box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }
    .info-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.15em; font-weight: 800; color: #94a3b8; margin-bottom: 2px; }
    .info-value { font-weight: 700; color: #1e293b; font-size: 0.95rem; }
    .balance-card { background: var(--erp-bg-soft); border-left: 4px solid var(--erp-primary); padding: 1rem; border-radius: 12px; }
    .holdings-card { background: #eff6ff; border-left: 4px solid #2563eb; padding: 1rem; border-radius: 12px; }

    /* Global Redundancy Fix */
    .app-content-header { display: none !important; }
</style>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="premium-title"><i class="bi bi-people me-3 p-2 bg-light rounded-3"></i>Catalogue Directory</h2>
    </div>
    <div class="col-md-6">
        <form action="student_directory.php" method="GET" class="d-flex gap-2 justify-content-md-end">
            <input type="text" name="search" class="search-input form-control-sm" style="width: 250px;" placeholder="Search ID, Name or Dept..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="search-btn"><i class="bi bi-search"></i></button>
        </form>
    </div>
</div>

<div class="premium-card shadow-sm">
    <div class="premium-header pb-2 border-0">
        <p class="mb-0 text-muted small fw-600">Active Membership Registry</p>
    </div>
    
    <div class="table-responsive">
        <table class="table table-premium mb-0">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Department</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="fw-bold text-premium-crimson"><?= htmlspecialchars($row['identity_no'] ?: 'N/A') ?></td>
                            <td class="fw-600"><?= htmlspecialchars($row['name']) ?></td>
                            <td><span class="badge bg-light text-dark border px-3"><?= htmlspecialchars($row['department']) ?></span></td>
                            <td class="text-center">
                                <?php if($row['is_active']): ?>
                                    <span class="status-badge bg-success bg-opacity-10 text-success">Active</span>
                                <?php else: ?>
                                    <span class="status-badge bg-danger bg-opacity-10 text-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3 view-profile" 
                                        data-row='<?= json_encode($row, JSON_HEX_APOS) ?>'>
                                    <i class="bi bi-eye me-1"></i> View Profile
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">No student records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION UI -->
    <?php if ($total_pages > 1): ?>
    <div class="p-4 border-top">
        <nav>
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link border-0 rounded-start-pill px-3" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a>
                </li>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link border-0 mx-1 rounded-circle" style="width: 32px; height:32px; display:flex; align-items:center; justify-content:center;" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link border-0 rounded-end-pill px-3" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-2xl" style="border-radius:30px; overflow:hidden;">
            <div class="modal-premium-header text-white border-0">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-4 shadow-none" data-bs-dismiss="modal"></button>
                <div class="d-flex align-items-center">
                    <div class="avatar-sophisticated me-4">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <div>
                        <div class="badge bg-white bg-opacity-20 text-white mb-2 px-3 py-1 rounded-pill extra-small fw-bold">VERIFIED MEMBER</div>
                        <h4 class="mb-0 fw-900 tracking-tight" id="prof-name">Student Name</h4>
                        <p class="mb-0 opacity-70 small" id="prof-dept"><i class="bi bi-building me-1"></i>Department Name</p>
                    </div>
                </div>
            </div>
            <div class="modal-body p-4 p-lg-5">
                <div class="row g-4 mb-5">
                    <div class="col-6">
                        <div class="info-label">IDENTIFIER</div>
                        <div class="info-value" id="prof-id">S-12345</div>
                    </div>
                    <div class="col-6">
                        <div class="info-label">REGISTRATION</div>
                        <div class="info-value" id="prof-reg">REG-888444</div>
                    </div>
                    <div class="col-12">
                        <div class="info-label">SECURE CORRESPONDENCE</div>
                        <div class="info-value text-primary" id="prof-email">email@university.edu</div>
                    </div>
                    <div class="col-6">
                        <div class="info-label">CONTACT CHANNEL</div>
                        <div class="info-value" id="prof-phone">+92 300 1234567</div>
                    </div>
                    <div class="col-6">
                        <div class="info-label">MEMBER SINCE</div>
                        <div class="info-value text-muted" id="prof-joined">Jan 24, 2026</div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="balance-card">
                            <div class="info-label text-danger">OUTSTANDING DUES</div>
                            <div class="h5 mb-0 fw-900 text-danger" id="prof-fines">Rs. 0.00</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="holdings-card">
                            <div class="info-label text-primary">COLLECTION ASSETS</div>
                            <div class="h5 mb-0 fw-900 text-primary" id="prof-holdings">0 Volumes</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-dark w-100 rounded-pill py-3 fw-900 tracking-wider shadow-lg" data-bs-dismiss="modal" style="background: #1e293b;">
                    DISMISS RECORDS
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const profileModal = new bootstrap.Modal(document.getElementById('profileModal'));
    
    document.querySelectorAll('.view-profile').forEach(btn => {
        btn.addEventListener('click', () => {
            const data = JSON.parse(btn.dataset.row);
            
            document.getElementById('prof-name').innerText = data.name;
            document.getElementById('prof-dept').innerText = data.department;
            document.getElementById('prof-id').innerText = data.identity_no || 'N/A';
            document.getElementById('prof-reg').innerText = data.registration_no || 'N/A';
            document.getElementById('prof-email').innerText = data.email;
            document.getElementById('prof-phone').innerText = data.phone || 'Not Provided';
            document.getElementById('prof-joined').innerText = new Date(data.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            document.getElementById('prof-fines').innerText = 'Rs. ' + parseFloat(data.fines).toFixed(2);
            document.getElementById('prof-holdings').innerText = data.currently_held + ' Volumes';
            
            profileModal.show();
        });
    });
});
</script>

<?php $mysqli->close(); require_once '../../includes/footer.php'; ?>
