<?php
require_once '../../core/session_guard.php';
require_once '../../core/rbac_helper.php';
require_once '../../core/audit_helper.php';
require_once '../../includes/header.php'; 
require_once '../../core/library_functions.php';

// RBAC Guard: Block Assistant Manager from Audit Trail
restrictTo('view_audit');

$lib = new Library($pdo);
logAction('VIEW_AUDIT', 'User accessed the Digital Audit Trail');

$transactions = $lib->getTransactions();
if (!is_array($transactions)) $transactions = [];
?>

<style>
    :root {
        --trail-primary: var(--erp-navy);
        --trail-bg: var(--erp-bg-soft);
        --trail-card: var(--erp-white);
        --trail-border: var(--erp-border);
        --trail-text-muted: #64748b;
    }
    
    body { background-color: var(--trail-bg) !important; font-family: 'Inter', sans-serif; }

    .trail-wrapper { padding: 2rem; }
    
    .page-title {
        font-weight: 800;
        color: var(--trail-primary);
        letter-spacing: -0.025em;
    }

    .glass-card {
        background: var(--trail-card);
        border: 1px solid var(--trail-border);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        border-radius: 16px;
        overflow: hidden;
    }

    .table-trail thead th {
        background-color: #f8fafc;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700;
        color: var(--trail-text-muted);
        padding: 1rem 1.5rem;
    }

    .table-trail tbody td {
        padding: 1.25rem 1.5rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--trail-border);
    }

    .action-badge {
        font-size: 0.7rem;
        font-weight: 800;
        padding: 0.35rem 0.65rem;
        border-radius: 6px;
        text-transform: uppercase;
    }
    .badge-issue { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .badge-return { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .badge-fine { background-color: #fff7ed; color: #9a3412; border: 1px solid #ffedd5; }
    .badge-system { background-color: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
    .badge-register { background-color: #f5f3ff; color: #5b21b6; border: 1px solid #ddd6fe; }
</style>

<div class="trail-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="librarian_dashboard.php" class="text-decoration-none text-muted">Librarian Console</a></li>
                    <li class="breadcrumb-item active">Audit Trail</li>
                </ol>
            </nav>
            <h1 class="page-title m-0"><i class="bi bi-journal-text me-2"></i>Digital Transaction Logging</h1>
        </div>
        <div class="text-muted small">
            <i class="bi bi-info-circle me-1"></i> Total Transactions: <strong><?= count($transactions) ?></strong>
        </div>
    </div>

    <div class="row align-items-center mb-4 g-3 d-print-none">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="trailSearch" class="form-control border-start-0 ps-0" placeholder="Search by student or book title...">
            </div>
        </div>
        <div class="col-md-3">
            <select id="actionFilter" class="form-select">
                <option value="ALL">All Actions</option>
                <option value="ISSUE">Issue Only</option>
                <option value="RETURN">Return Only</option>
                <option value="SETTLE_FINE">Fine Settlements</option>
                <option value="REGISTER_BOOK">New Book Entries</option>
            </select>
        </div>
        <div class="col-md-4 text-md-end d-flex gap-2 justify-content-md-end">
            <button id="exportCsv" class="btn btn-outline-primary fw-bold shadow-sm">
                <i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV
            </button>
            <button onclick="window.print()" class="btn btn-primary fw-bold shadow-sm">
                <i class="bi bi-printer me-2"></i>Print PDF
            </button>
        </div>
    </div>

    <div class="card glass-card">
        <div class="table-responsive">
            <table class="table table-trail mb-0" id="trailTable">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Student</th>
                        <th>Book Title</th>
                        <th class="text-center">Action</th>
                        <th>Reference Notes</th>
                    </tr>
                </thead>
                <tbody id="trailBody">
                    <?php if (empty($transactions)): ?>
                        <tr class="no-results"><td colspan="5" class="text-center py-5 text-muted">No transaction logs available yet.</td></tr>
                    <?php else: foreach ($transactions as $t): ?>
                        <tr class="trail-row" data-action="<?= $t['action'] ?>">
                            <td class="text-muted small"><?= date('d M Y, h:i A', strtotime($t['transaction_date'])) ?></td>
                            <td class="fw-bold text-dark search-cell"><?= htmlspecialchars($t['user_name']) ?></td>
                            <td class="search-cell"><?= htmlspecialchars($t['book_title']) ?></td>
                            <td class="text-center">
                                <?php 
                                    $badgeClass = 'badge-system';
                                    if ($t['action'] == 'ISSUE') $badgeClass = 'badge-issue';
                                    elseif ($t['action'] == 'RETURN') $badgeClass = 'badge-return';
                                    elseif ($t['action'] == 'SETTLE_FINE') $badgeClass = 'badge-fine';
                                    elseif ($t['action'] == 'REGISTER_BOOK') $badgeClass = 'badge-register';
                                ?>
                                <span class="action-badge <?= $badgeClass ?>">
                                    <?= str_replace('_', ' ', $t['action']) ?>
                                </span>
                            </td>
                            <td class="small text-muted italic"><?= htmlspecialchars($t['notes'] ?: 'N/A') ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('trailSearch');
    const actionFilter = document.getElementById('actionFilter');
    const tableRows = document.querySelectorAll('.trail-row');
    const exportBtn = document.getElementById('exportCsv');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const actionValue = actionFilter.value;
        let visibleCount = 0;

        tableRows.forEach(row => {
            const student = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const book = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const action = row.getAttribute('data-action');

            const matchesSearch = student.includes(searchTerm) || book.includes(searchTerm);
            const matchesAction = actionValue === 'ALL' || action === actionValue;

            if (matchesSearch && matchesAction) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Handle empty state
        let emptyMsg = document.querySelector('.no-results-dynamic');
        if (visibleCount === 0 && tableRows.length > 0) {
            if (!emptyMsg) {
                const tr = document.createElement('tr');
                tr.className = 'no-results-dynamic';
                tr.innerHTML = `<td colspan="5" class="text-center py-5 text-muted">No transactions match your filters.</td>`;
                document.getElementById('trailBody').appendChild(tr);
            }
        } else if (emptyMsg) {
            emptyMsg.remove();
        }
    }

    searchInput.addEventListener('input', filterTable);
    actionFilter.addEventListener('change', filterTable);

    // Export to CSV
    exportBtn.addEventListener('click', function() {
        let csv = [];
        const rows = document.querySelectorAll("#trailTable tr");
        
        for (let i = 0; i < rows.length; i++) {
            if (rows[i].style.display !== 'none') {
                let row = [], cols = rows[i].querySelectorAll("td, th");
                
                for (let j = 0; j < cols.length; j++) {
                    let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, "").trim().replace(/(\s\s+)/gm, ' ');
                    data = data.replace(/"/g, '""');
                    row.push('"' + data + '"');
                }
                csv.push(row.join(","));
            }
        }

        const csvString = csv.join("\n");
        const filename = 'library_audit_trail_' + new Date().toISOString().slice(0, 10) + '.csv';
        const link = document.createElement("a");
        const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        
        link.setAttribute("href", url);
        link.setAttribute("download", filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>

<style>
@media print {
    .app-header, .app-sidebar, .d-print-none, .breadcrumb { display: none !important; }
    .trail-wrapper { padding: 0; width: 100%; }
    .glass-card { border: none !important; box-shadow: none !important; }
    .table-trail { border: 1px solid #dee2e6; width: 100%; border-collapse: collapse; }
    .table-trail thead th { background: #f8f9fa !important; border-bottom: 2px solid #dee2e6; color: black !important; }
    .table-trail td { border-bottom: 1px solid #dee2e6 !important; }
    .action-badge { border: 1px solid #ddd !important; background: transparent !important; color: black !important; padding: 2px 5px !important; }
}
</style>

<?php require_once '../../includes/footer.php'; ?>
