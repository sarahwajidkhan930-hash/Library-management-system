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
    
    /* Removed hardcoded body background to allow theme.css to handle it */

    .glass-card {
        background: var(--erp-card-bg);
        border: 1px solid var(--lib-border);
        box-shadow: var(--erp-shadow);
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
        color: var(--erp-text-secondary);
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    .stat-value {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--erp-text-main);
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
        background: var(--erp-card-bg);
    }
    .quick-action-card:hover {
        background-color: var(--lib-primary) !important;
        color: white !important;
    }
    .quick-action-card:hover .icon-box {
        background-color: rgba(255,255,255,0.2) !important;
        color: white !important;
    }
    .quick-action-card:hover h5, 
    .quick-action-card:hover p {
        color: white !important;
    }
</style>

<!-- Welcome Section -->

<div class="row mb-4">
    <div class="col-12">
        <div class="p-4 rounded-4 shadow-sm" style="background: linear-gradient(135deg, var(--erp-primary) 0%, var(--erp-primary-dark) 100%);">
            <div class="d-flex justify-content-between align-items-center text-white text-center text-md-start">
                <div>
                    <h2 class="fw-bold mb-1"><i class="bi bi-shield-lock me-2"></i>Librarian Command Center</h2>
                    <p class="mb-0 opacity-75 lead">Operational overview and system analytics for today.</p>
                </div>
                <div class="d-none d-lg-block">
                     <button onclick="syncSystemRegistry()" class="btn btn-light btn-lg fw-bold rounded-pill px-4 shadow-sm">
                        <i class="bi bi-arrow-repeat me-1"></i>Sync System Registry
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card glass-card stat-card border-0" style="background: linear-gradient(135deg, var(--erp-card-bg) 0%, var(--erp-bg-soft) 100%); border-left: 5px solid var(--lib-primary) !important;">
                    <div class="card-body">
                        <div class="stat-label">Stock Inventory</div>
                        <div class="stat-value text-dark"><?= number_format($libStats['total_books']) ?></div>
                        <div class="small text-muted mt-2"><i class="bi bi-stack me-1"></i>Total Volumes</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card glass-card stat-card border-0" style="background: linear-gradient(135deg, var(--erp-card-bg) 0%, var(--erp-bg-soft) 100%); border-left: 5px solid var(--erp-info) !important;">
                    <div class="card-body">
                        <div class="stat-label">Active Outings</div>
                        <div class="stat-value text-info"><?= number_format($libStats['active_loans']) ?></div>
                        <div class="small text-muted mt-2"><i class="bi bi-arrow-left-right me-1"></i>Current Loans</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card glass-card stat-card border-0" style="background: linear-gradient(135deg, var(--erp-card-bg) 0%, var(--erp-bg-soft) 100%); border-left: 5px solid var(--erp-warning) !important;">
                    <div class="card-body">
                        <div class="stat-label">Pending Holds</div>
                        <div class="stat-value text-warning"><?= number_format($libStats['pending_reservations']) ?></div>
                        <div class="small text-muted mt-2"><i class="bi bi-calendar2-check me-1"></i>Waiting for Books</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card glass-card stat-card border-0 h-100" style="background: linear-gradient(135deg, var(--erp-card-bg) 0%, var(--erp-bg-soft) 100%); border-left: 5px solid #ef4444 !important;">
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

        <h3 class="fw-bold mb-4 text-dark"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Visual Analytics</h3>
        <div class="row g-4 mb-5">
            <div class="col-md-7">
                <div class="card glass-card h-100 shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold m-0">Circulation Trends</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="circulationChart" height="220"></canvas>
                    </div>
                </div>
            </div>
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
                        <div class="icon-box bg-light rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 50px; height: 50px; color: var(--erp-info);">
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

    </div>
</div>




<!-- External Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        const isDarkMode = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const textColor = isDarkMode ? '#94a3b8' : '#64748b';
        const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';
        const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--erp-primary').trim() || '#8c031c';

        // --- GLOBAL FUNCTIONS (Available immediately) ---
        
        // 4. Reservation Fulfillment
        window.fulfillHold = function(resId) {
            Swal.fire({
                title: 'Fulfill Hold?',
                text: "This will issue the book to the student immediately.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#8c031c',
                confirmButtonText: 'Yes, Fulfill'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'fulfill_hold');
                    formData.append('reservation_id', resId);

                    fetch('../student/ajax_student_actions.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            Swal.fire('Done!', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Failed', data.message, 'error');
                        }
                    })
                    .catch(err => Swal.fire('Error', 'Fulfillment failed.', 'error'));
                }
            });
        };

        // 5. System Registry Sync
        window.syncSystemRegistry = function() {
            const btn = document.querySelector('button[onclick="syncSystemRegistry()"]');
            if (btn) btn.disabled = true;
            
            const fd = new FormData();
            fd.append('action', 'sync_registry');

            fetch('../student/ajax_student_actions.php', {
                method: 'POST',
                body: fd
            })
            .then(() => {
                Swal.fire({
                    title: 'Registry Synced',
                    text: 'Data refreshed successfully.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            })
            .catch(() => {
                if(btn) btn.disabled = false;
                Swal.fire('Error', 'Sync failed.', 'error');
            });
        };

        // --- DASHBOARD INITIALIZATION ---

        // 1. Circulation Trends Chart
        const circCanvas = document.getElementById('circulationChart');
        if (circCanvas) {
            const circCtx = circCanvas.getContext('2d');
            const circData = <?= json_encode($analytics['monthly_circulation'] ?: []) ?>;
            
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
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } },
                        x: { grid: { display: false }, ticks: { color: textColor } }
                    }
                }
            });
        }

        // 2. Category Distribution Chart
        const catCanvas = document.getElementById('categoryChart');
        if (catCanvas) {
            const catCtx = catCanvas.getContext('2d');
            const catData = <?= json_encode($analytics['category_distribution'] ?: []) ?>;
            
            new Chart(catCtx, {
                type: 'doughnut',
                data: {
                    labels: catData.map(d => d.category_name),
                    datasets: [{
                        data: catData.map(d => d.count),
                        backgroundColor: [primaryColor, '#6366f1', '#818cf8', '#a5b4fc', '#c7d2fe', '#e0e7ff'],
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
                            labels: { color: textColor, padding: 20, usePointStyle: true, font: { size: 11, weight: '600' } }
                        }
                    },
                    cutout: '70%'
                }
            });
        }

        // 3. Overdue Reminders
        const sendRemindersBtn = document.getElementById('sendRemindersBtn');
        if (sendRemindersBtn) {
            sendRemindersBtn.addEventListener('click', function() {
                this.disabled = true;
                this.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Sending...';
                
                fetch('ajax_bulk_reminders.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success', data.message, 'success');
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(err => Swal.fire('Error', 'Failed to send reminders.', 'error'))
                    .finally(() => {
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-bell-fill me-1"></i>Remind';
                    });
            });
        }


    } catch (e) {
        console.error('Dashboard JS Error:', e);
    }
});
</script>
<?php require_once '../../includes/footer.php'; ?>




