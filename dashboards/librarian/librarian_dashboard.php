<?php 
require_once '../../includes/header.php'; 
require_once '../../core/library_functions.php';

$lib = new Library($pdo);
$lib->refreshOverdueStates(); // Automated real-time refresh
$libStats = $lib->getLibrarianStats();
$analytics = $lib->getAdvancedAnalytics();

// ── RBAC Role Helpers ────────────────────────────────────────
$currentRole = $_SESSION['role']  ?? '';
$currentUser = $_SESSION['user_id'] ?? 0;
$isLibrarian = in_array($currentRole, ['librarian', 'super_admin']);
$isAssistant = ($currentRole === 'assistant_manager');

// ── Operations Feed Data ──────────────────────────────────────
$operationsFeed = $pdo->query("
    SELECT al.activity, al.notes, al.created_at,
           u.name AS staff_name, u.role AS staff_role, u.avatar AS staff_avatar
    FROM audit_logs al
    JOIN users u ON al.user_id = u.id
    WHERE u.role IN ('assistant_manager', 'librarian')
    ORDER BY al.created_at DESC
    LIMIT 10
")->fetchAll();

function timeAgo($timestamp) {
    if (empty($timestamp)) return "N/A";
    $time = strtotime($timestamp);
    $diff = time() - $time;
    if ($diff < 1) return 'Just now';
    $intervals = [
        31536000 => 'year', 2592000 => 'month', 604800 => 'week',
        86400    => 'day',  3600     => 'hour',  60     => 'minute', 1 => 'second'
    ];
    foreach ($intervals as $secs => $str) {
        $d = $diff / $secs;
        if ($d >= 1) {
            $r = round($d);
            return $r . ' ' . $str . ($r > 1 ? 's' : '') . ' ago';
        }
    }
}
?>

<style>
    :root {
        --lib-primary: var(--erp-primary);
        --lib-bg-soft: var(--erp-bg-soft);
        --lib-border: var(--erp-border);
    }
    
    body { background-color: var(--lib-bg-soft); color: #0f172a; }

    .glass-card {
        background: #ffffff;
        border: 1px solid var(--lib-border);
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        border-radius: 12px;
    }
    
    .stat-card {
        border-left: 5px solid;
        padding: 1.5rem;
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-5px); }

    .stat-label {
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #475569;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    .stat-value {
        font-size: 2.5rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
    }

    /* Live Pulsing Dot */
    .pulse-live {
        width: 8px;
        height: 8px;
        background: #10b981;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulse-green 2s infinite;
    }
    @keyframes pulse-green {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    .staff-avatar-sm {
        width: 32px;
        height: 32px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid var(--lib-border);
    }

    .quick-action-card {
        transition: all 0.2s;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
    }
    .quick-action-card:hover {
        background-color: var(--lib-primary);
        color: white !important;
    }
    .quick-action-card:hover .icon-box {
        background-color: rgba(255,255,255,0.2) !important;
        color: white !important;
    }

    /* Global Redundancy Fix */
    .app-content-header { display: none !important; }

    .card-header-premium {
        background: linear-gradient(to right, #ffffff, #f8fafc);
        border-bottom: 2px solid var(--lib-border);
    }

    /* Hover Effects Restored */
    .stat-card, .glass-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .stat-card:hover, .glass-card:hover {
        transform: translateY(-8px) !important;
        box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.1) !important;
    }
</style>

<div class="content-header p-4">
    <div class="container-fluid text-center mb-4">
        <h1 class="fw-extrabold text-primary mb-1" style="font-size: 2.5rem;"><i class="bi bi-shield-lock me-3"></i>Library Dashboard</h1>
        <p class="text-secondary opacity-75 lead">Welcome back. Here is a summary of your library's current status.</p>
    </div>
    
    <div class="container-fluid">
        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card glass-card stat-card border-0" style="background: linear-gradient(135deg, white 0%, #fffafa 100%); border-left: 5px solid var(--lib-primary) !important;">
                    <div class="card-body">
                        <div class="stat-label">Stock Inventory</div>
                        <div class="stat-value text-dark"><?= number_format($libStats['total_books']) ?></div>
                        <div class="small text-muted mt-2"><i class="bi bi-stack me-1"></i>Total Volumes</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card glass-card stat-card border-0" style="background: linear-gradient(135deg, white 0%, #f0f7ff 100%); border-left: 5px solid #3b82f6 !important;">
                    <div class="card-body">
                        <div class="stat-label">Active Outings</div>
                        <div class="stat-value text-primary"><?= number_format($libStats['active_loans']) ?></div>
                        <div class="small text-muted mt-2"><i class="bi bi-arrow-left-right me-1"></i>Current Loans</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card glass-card stat-card border-0" style="background: linear-gradient(135deg, white 0%, #ecfeff 100%); border-left: 5px solid #06b6d4 !important;">
                    <div class="card-body">
                        <div class="stat-label">Pending Holds</div>
                        <div class="stat-value" style="color: #06b6d4;"><?= number_format($libStats['pending_reservations']) ?></div>
                        <div class="small text-muted mt-2"><i class="bi bi-calendar2-check me-1"></i>Waiting for Books</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card glass-card stat-card border-0 h-100" style="background: linear-gradient(135deg, white 0%, #fff1f2 100%); border-left: 5px solid #ef4444 !important;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-label">Late Returns</div>
                                <div class="stat-value text-danger"><?= number_format($libStats['overdue_books']) ?></div>
                            </div>
                            <?php if ($libStats['overdue_books'] > 0): ?>
                                <button id="sendRemindersBtn" class="btn btn-sm btn-danger fw-bold px-3 shadow-sm border-0" style="border-radius: 8px;">
                                    <i class="bi bi-bell-fill me-1"></i>Remind
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="small text-muted mt-2"><i class="bi bi-exclamation-triangle me-1"></i>Items Past Due</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             ANALYTICS & TRENDS
             High-impact visual overview of library performance
        ══════════════════════════════════════════════════ -->
        <h3 class="fw-bold mb-4 text-dark"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Visual Analytics</h3>
        <div class="row g-4 mb-5">
            <!-- Monthly Circulation Chart -->
            <div class="col-md-7">
                <div class="card glass-card h-100 shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold m-0">Circulation Trends (Last 6 Months)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="circulationChart" height="220"></canvas>
                    </div>
                </div>
            </div>
            <!-- Category Distribution Chart -->
            <div class="col-md-5">
                <div class="card glass-card h-100 shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold m-0">Collection Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="fw-bold mb-4 text-dark"><i class="bi bi-lightning-fill me-2 text-warning"></i>Quick Management Actions</h3>
        
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <a href="manage_inventory.php" class="card glass-card quick-action-card h-100 border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="icon-box bg-light rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 50px; height: 50px; color: var(--lib-primary);">
                            <i class="bi bi-stack fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Stock Controls</h5>
                            <p class="small mb-0 opacity-75">Inventory management.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="borrowing.php" class="card glass-card quick-action-card h-100 border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="icon-box bg-light rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 50px; height: 50px; color: #3b82f6;">
                            <i class="bi bi-arrow-left-right fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Issue/Return</h5>
                            <p class="small mb-0 opacity-75">Circulation handling.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="student_directory.php" class="card glass-card quick-action-card h-100 border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="icon-box bg-light rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 50px; height: 50px; color: #10b981;">
                            <i class="bi bi-people fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Member Directory</h5>
                            <p class="small mb-0 opacity-75">Profile management.</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php if ($isLibrarian): ?>
            <div class="col-md-3">
                <a href="audit_trail.php" class="card glass-card quick-action-card h-100 border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="icon-box bg-light rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 50px; height: 50px; color: #1e293b;">
                            <i class="bi bi-journal-text fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Audit Trail</h5>
                            <p class="small mb-0 opacity-75">Librarian only.</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <div class="row g-4 mb-5">
            <!-- Most Borrowed Book -->
            <div class="col-md-6">
                <div class="card glass-card h-100 shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold m-0"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Most Borrowed Collection</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($analytics['most_borrowed']): ?>
                            <div class="d-flex align-items-center p-3 bg-light rounded-3 border">
                                <i class="bi bi-book-half fs-1 me-4 text-primary opacity-50"></i>
                                <div>
                                    <a href="book_details.php?id=<?= $analytics['most_borrowed']['id'] ?>" class="text-decoration-none">
                                        <h4 class="fw-bold mb-1 text-primary"><?= htmlspecialchars($analytics['most_borrowed']['title']) ?></h4>
                                    </a>
                                    <p class="text-muted mb-0">Issued <span class="fw-bold text-dark"><?= $analytics['most_borrowed']['borrow_count'] ?></span> times.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">No data available for collection analytics.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top Library Users -->
            <div class="col-md-6">
                <div class="card glass-card h-100 shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold m-0"><i class="bi bi-trophy-fill me-2 text-warning"></i>Top Library Advocates</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($analytics['top_users'] as $idx => $user): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-<?= $idx == 0 ? 'warning' : 'light text-dark' ?> me-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;"><?= $idx + 1 ?></span>
                                        <span class="fw-semibold"><?= htmlspecialchars($user['name']) ?></span>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3"><?= $user['borrow_count'] ?> Books</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             RESERVATION MANAGEMENT
        ══════════════════════════════════════════════════ -->
        <?php $reservations = $lib->getAllPendingReservations(); ?>
        <div class="row mb-5">
            <div class="col-12">
                <div class="card glass-card shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center card-header-premium">
                        <h5 class="fw-bold m-0"><i class="bi bi-calendar-event me-2 text-info"></i>Active Hold Requests</h5>
                        <div class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3">
                            <i class="bi bi-shield-check me-1"></i>Fulfillment Center Active
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Student</th>
                                        <th>Book Waiting For</th>
                                        <th>Requested On</th>
                                        <th>Contact</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($reservations)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">No pending hold requests at the moment.</td></tr>
                                <?php else: foreach ($reservations as $r): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold"><?= htmlspecialchars($r['student_name']) ?></div>
                                            <div class="small text-muted">#<?= $r['user_id'] ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-primary"><?= htmlspecialchars($r['title']) ?></div>
                                            <div class="small text-muted">Book ID: <?= $r['book_id'] ?></div>
                                        </td>
                                        <td><?= date('d M Y, h:i A', strtotime($r['reserved_at'])) ?></td>
                                        <td>
                                            <div class="small"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($r['student_email']) ?></div>
                                            <div class="small"><i class="bi bi-phone me-1"></i><?= htmlspecialchars($r['student_phone'] ?: 'No Phone') ?></div>
                                        </td>
                                        <td class="text-end pe-4">
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

        <!-- ══════════════════════════════════════════════════
             OPERATIONS FEED
        <div class="row mb-4">
            <div class="col-12">
                <div class="card glass-card shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-3 card-header-premium">
                        <h5 class="fw-bold m-0 d-flex align-items-center">
                            <span class="pulse-live"></span>
                            <i class="bi bi-activity me-2 text-primary"></i>Live Operations Feed
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm shadow-sm" style="border-radius:20px; overflow:hidden;">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar-event"></i></span>
                                <input type="date" id="feedStart" class="form-control border-start-0 ps-0" placeholder="Start">
                                <span class="input-group-text bg-white px-1">to</span>
                                <input type="date" id="feedEnd" class="form-control" placeholder="End">
                                <button class="btn btn-primary px-3" id="applyFilterBtn"><i class="bi bi-search"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 py-3" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Staff Member</th>
                                        <th style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Action</th>
                                        <th style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Details</th>
                                        <th class="text-end pe-4" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody id="liveFeedBody">
                                <?php if (empty($operationsFeed)): ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted">No recent operations logged.</td></tr>
                                <?php else: foreach ($operationsFeed as $op):
                                    $badgeColor = match(true) {
                                        str_contains($op['activity'], 'RETURN')  => 'success',
                                        str_contains($op['activity'], 'ISSUE')   => 'primary',
                                        str_contains($op['activity'], 'BLOCKED') => 'danger',
                                        str_contains($op['activity'], 'FINE')    => 'warning',
                                        str_contains($op['activity'], 'CATEGORY')=> 'info',
                                        default => 'secondary'
                                    };
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <img src="<?= $op['staff_avatar'] ?: '../../assets/img/avatar.png' ?>" class="staff-avatar-sm me-3 border shadow-sm" alt="AV" onerror="this.src='../../assets/img/avatar.png'">
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($op['staff_name']) ?></div>
                                                    <span class="badge bg-<?= $op['staff_role'] === 'assistant_manager' ? 'warning text-dark' : 'primary' ?> rounded-pill" style="font-size:.7rem;">
                                                        <?= ucwords(str_replace('_', ' ', $op['staff_role'])) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $badgeColor ?> bg-opacity-10 text-<?= $badgeColor ?> border border-<?= $badgeColor ?> border-opacity-25 rounded-pill px-3">
                                                <i class="bi bi-arrow-right me-1"></i><?= htmlspecialchars($op['activity']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small" style="max-width:300px;">
                                            <?= htmlspecialchars(substr($op['notes'] ?? '', 0, 80)) ?>
                                            <?= strlen($op['notes'] ?? '') > 80 ? '...' : '' ?>
                                        </td>
                                        <td class="text-end pe-4 text-muted small fw-bold">
                                            <i class="bi bi-clock-history me-1"></i><?= timeAgo($op['created_at']) ?>
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
</div>

<?php require_once '../../includes/footer.php'; ?>

<!-- Chart.js Integration -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDarkMode = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    const textColor = isDarkMode ? '#94a3b8' : '#64748b';
    const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';
    const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--erp-primary').trim() || '#4f46e5';

    // 1. Circulation Trends Chart
    const circCtx = document.getElementById('circulationChart').getContext('2d');
    const circData = <?= json_encode($analytics['monthly_circulation']) ?>;
    
    new Chart(circCtx, {
        type: 'bar',
        data: {
            labels: circData.map(d => d.month),
            datasets: [{
                label: 'Book Issues',
                data: circData.map(d => d.count),
                backgroundColor: primaryColor,
                borderRadius: 8,
                barThickness: 30
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { color: textColor }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: textColor }
                }
            }
        }
    });

    // 2. Category Distribution Chart
    const catCtx = document.getElementById('categoryChart').getContext('2d');
    const catData = <?= json_encode($analytics['category_distribution']) ?>;
    
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: catData.map(d => d.category_name),
            datasets: [{
                data: catData.map(d => d.count),
                backgroundColor: [
                    primaryColor,
                    '#6366f1',
                    '#818cf8',
                    '#a5b4fc',
                    '#c7d2fe',
                    '#e0e7ff'
                ],
                borderWidth: 0,
                spacing: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: textColor,
                        padding: 20,
                        usePointStyle: true,
                        font: { size: 11, weight: '600' }
                    }
                }
            },
            cutout: '70%'
        }
    });

    // 3. Real-Time Live Feed Polling & Filtering
    function refreshLiveFeed() {
        const start = document.getElementById('feedStart')?.value || '';
        const end = document.getElementById('feedEnd')?.value || '';
        const url = `ajax_operations_feed.php?start=${start}&end=${end}`;

        fetch(url)
            .then(response => response.text())
            .then(html => {
                const feedBody = document.getElementById('liveFeedBody');
                if (feedBody) {
                    feedBody.innerHTML = html;
                }
            })
            .catch(error => console.warn('Live feed update failed:', error));
    }

    if (document.getElementById('applyFilterBtn')) {
        document.getElementById('applyFilterBtn').addEventListener('click', refreshLiveFeed);
    }

    // 4. Overdue Reminders Implementation
    const sendRemindersBtn = document.getElementById('sendRemindersBtn');
    if (sendRemindersBtn) {
        sendRemindersBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Sending...';
            
            fetch('ajax_bulk_reminders.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        refreshLiveFeed(); 
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .finally(() => {
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-bell-fill me-1"></i>Remind';
                });
        });
    }

    // 5. Reservation Fulfillment
    window.fulfillHold = function(resId) {
        if(!confirm('This will immediately issue the book to the student and fulfill their hold. Proceed?')) return;
        
        const formData = new FormData();
        formData.append('action', 'fulfill_hold');
        formData.append('reservation_id', resId);

        fetch('../student/ajax_student_actions.php', { // Reusing for consistency
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if(data.success) location.reload();
        })
        .catch(err => alert('Fulfillment failed. Please ensure the book is in stock.'));
    };

    // Refresh every 20 seconds
    setInterval(refreshLiveFeed, 20000);
});
</script>
