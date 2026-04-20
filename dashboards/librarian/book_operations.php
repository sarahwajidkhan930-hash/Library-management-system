<?php
/**
 * book_operations.php
 * ─────────────────────────────────────────────────────────────
 * Modular Book Operations page for the Library ERP.
 *
 * Features
 *  1. Book Categorisation & Issueable Toggle
 *  2. Return & Time Tracker (fine auto-calculated per book rate)
 *  3. Overdue Alert Panel (Librarian / Asst-Manager only)
 *
 * All mutations call logAction() from audit_helper.php.
 * ─────────────────────────────────────────────────────────────
 */

require_once '../../includes/header.php';
require_once '../../core/library_functions.php';
require_once '../../core/audit_helper.php';

// ── Role guard ──────────────────────────────────────────────
$allowedRoles = ['librarian', 'assistant_manager', 'super_admin'];
$userRole     = $_SESSION['role'] ?? '';
if (!in_array($userRole, $allowedRoles)) {
    header('Location: ../../index.php?error=403');
    exit;
}

$lib     = new Library($pdo);
$message = '';

// ═══════════════════════════════════════════════════════════
// SNIPPET 1 — Book Categorisation & Issueable Toggle
// ═══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_book'])) {

    $bookId     = (int)   ($_POST['book_id']       ?? 0);
    $bookType   =          $_POST['book_type']      ?? 'Textbook';
    $isIssueable = (int)  ($_POST['is_issueable']  ?? 1);
    $finePerDay = (float) ($_POST['fine_per_day']  ?? 10.00);

    $allowedTypes = ['Textbook', 'Reference', 'Periodical', 'Fiction', 'Non-Fiction', 'Other'];
    if (!in_array($bookType, $allowedTypes)) {
        $bookType = 'Textbook';
    }

    $stmt = $pdo->prepare("
        UPDATE lib_books
           SET book_type    = ?,
               is_issueable = ?,
               fine_per_day = ?
         WHERE id = ?
    ");
    $ok = $stmt->execute([$bookType, $isIssueable, $finePerDay, $bookId]);

    if ($ok) {
        $statusLabel = $isIssueable ? 'Issueable' : 'Non-Issueable';
        logAction(
            'BOOK_SETTINGS_UPDATED',
            "Book ID $bookId | Type: $bookType | Status: $statusLabel | Fine/Day: $finePerDay"
        );
        $message = success_alert("Book settings updated. Status set to <strong>$statusLabel</strong>.");
    } else {
        $message = danger_alert('Failed to update book settings. Please try again.');
    }
}

// ═══════════════════════════════════════════════════════════
// SNIPPET 2 — Return & Time Tracker with Fine Calculation
//
//   Uses the per-book fine_per_day column instead of the
//   hardcoded Rs. 10 in checkInBook(). This snippet
//   overrides the generic checkInBook() for full control.
// ═══════════════════════════════════════════════════════════
if (isset($_GET['return_id'])) {

    $borrowingId = (int) $_GET['return_id'];

    try {
        $pdo->beginTransaction();

        // — Fetch loan with book fine rate ——————————————
        $stmt = $pdo->prepare("
            SELECT br.id,
                   br.user_id,
                   br.book_id,
                   br.due_date,
                   br.status,
                   b.title,
                   b.fine_per_day
              FROM lib_borrowings br
              JOIN lib_books b ON br.book_id = b.id
             WHERE br.id = ?
               AND br.status != 'returned'
        ");
        $stmt->execute([$borrowingId]);
        $loan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$loan) {
            $pdo->rollBack();
            $message = danger_alert('Loan record not found or already returned.');
        } else {

            // — Time Tracker calculation ——————————————
            $today   = new DateTime('today');
            $due     = new DateTime($loan['due_date']);
            $diff    = $today->diff($due);
            $daysOverdue = ($today > $due) ? (int)$diff->days : 0;

            $fineAmount = round($daysOverdue * (float)$loan['fine_per_day'], 2);
            $returnDate = $today->format('Y-m-d');

            // — Update borrowing record ———————————————
            $stmt = $pdo->prepare("
                UPDATE lib_borrowings
                   SET return_date  = ?,
                       fine_amount  = ?,
                       status       = 'returned'
                 WHERE id = ?
            ");
            $stmt->execute([$returnDate, $fineAmount, $borrowingId]);

            // — Accumulate fine in Users table ————————
            $stmt = $pdo->prepare("
                UPDATE users
                   SET fines = fines + ?
                 WHERE id = ?
            ");
            $stmt->execute([$fineAmount, $loan['user_id']]);

            // — Restore available copy ————————————————
            $stmt = $pdo->prepare("
                UPDATE lib_books
                   SET available_copies = available_copies + 1
                 WHERE id = ?
            ");
            $stmt->execute([$loan['book_id']]);

            $pdo->commit();

            // — Audit Log ————————————————————————————
            logAction(
                'BOOK_RETURNED',
                "Borrowing ID $borrowingId | Book: \"{$loan['title']}\" | " .
                "Days Overdue: $daysOverdue | Fine Charged: Rs. $fineAmount | " .
                "Student ID: {$loan['user_id']}"
            );

            if ($daysOverdue > 0) {
                $message = warning_alert(
                    "Book returned <strong>$daysOverdue day(s) late</strong>. " .
                    "Fine of <strong>Rs. $fineAmount</strong> has been added to Student ID #{$loan['user_id']}'s account."
                );
            } else {
                $message = success_alert("Book returned on time. No fine applied.");
            }
        }

    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('BookOps Return Error: ' . $e->getMessage());
        $message = danger_alert('A database error occurred during return processing.');
    }
}

// ═══════════════════════════════════════════════════════════
// SNIPPET 3 — Issueable Check (block non-issueable)
//
//   Called by the Issue Book form on this page.
//   The Library::checkOutBook() already enforces this, but
//   we surface a clear UI block here too.
// ═══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_checkout'])) {

    $bookId    = (int) ($_POST['book_id']    ?? 0);
    $studentId = (int) ($_POST['student_id'] ?? 0);
    $dueDate   =        $_POST['due_date']   ?? '';
    $isOverride = isset($_POST['is_override']) && $_POST['is_override'] == '1';

    // Security check for override: Only librarians can override
    if ($isOverride && !in_array($_SESSION['role'], ['librarian', 'super_admin'])) {
        $message = danger_alert('Unauthorized: Only Librarians can perform an override.');
    } else {
        // Pre-flight check — fetch is_issueable status
        $stmt = $pdo->prepare("SELECT title, is_issueable FROM lib_books WHERE id = ?");
        $stmt->execute([$bookId]);
        $bookCheck = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bookCheck) {
            $message = danger_alert('Book not found.');
        } elseif (!$bookCheck['is_issueable']) {
            // ── BLOCKED (Non-issueable) ──────────────────
            logAction(
                'CHECKOUT_BLOCKED',
                "Attempt to issue Non-Issueable Book ID $bookId (\"{$bookCheck['title']}\") to Student ID $studentId"
            );
            $message = danger_alert(
                '<strong>Transaction Blocked:</strong> The book "' . htmlspecialchars($bookCheck['title']) .
                '" is marked as <strong>Non-Issueable</strong>.'
            );
        } else {
            // Check student vitals if not overriding
            $vitals = $lib->getBorrowingVitals($studentId);
            if (!$isOverride && ($vitals['has_exceeded'] || $vitals['has_fines'])) {
                $reason = $vitals['has_exceeded'] ? 'Limit Exceeded' : 'Active Fines';
                logAction('CHECKOUT_BLOCKED', "TRX_BLOCKED: $reason for Student ID $studentId");
                $message = danger_alert("<strong>Transaction Blocked:</strong> Student has $reason. Librarian override required.");
            } else {
                if ($isOverride) {
                    logAction('CHECKOUT_OVERRIDE', "Manual override by " . $_SESSION['role'] . " for Student ID $studentId");
                }
                $res = $lib->checkOutBook($bookId, $studentId, $dueDate, $isOverride);
                if ($res['success']) {
                    $message = success_alert($res['message'] . ($isOverride ? " (Override Applied)" : ""));
                } else {
                    $message = danger_alert($res['message']);
                }
            }
        }
    }
}

// ── Refresh overdue states (passive, runs on page load) ──
$lib->refreshOverdueStates();

// ── Data for the view ————————————————————————────————————
$books           = $lib->getBooks();
$students        = $lib->getStudents();
$activeBorrowings = $lib->getActiveBorrowings();

// ═══════════════════════════════════════════════════════════
// SNIPPET 4 — Overdue Alerts (Librarian & Asst-Manager only)
// ═══════════════════════════════════════════════════════════
$overdueAlerts = [];
if (in_array($userRole, ['librarian', 'assistant_manager', 'super_admin'])) {
    $stmt = $pdo->query("
        SELECT *
          FROM v_overdue_alerts
         ORDER BY days_overdue DESC
    ");
    $overdueAlerts = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

// ── Helper functions ─────────────────────────────────────
function success_alert(string $msg): string {
    return '<div class="alert alert-success alert-dismissible fade show shadow-sm border-0">
                <i class="bi bi-check-circle-fill me-2"></i>' . $msg . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}
function danger_alert(string $msg): string {
    return '<div class="alert alert-danger alert-dismissible fade show shadow-sm border-0">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $msg . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}
function warning_alert(string $msg): string {
    return '<div class="alert alert-warning alert-dismissible fade show shadow-sm border-0">
                <i class="bi bi-clock-history me-2"></i>' . $msg . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}
?>

<!-- ════════════════════════════════════════════════════════
     PAGE HEADER
     ════════════════════════════════════════════════════════ -->
<div class="content-header px-4 pt-4">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-muted">Librarian</a></li>
                        <li class="breadcrumb-item active">Book Operations</li>
                    </ol>
                </nav>
                <h1 class="m-0 fw-bold text-dark">
                    <i class="bi bi-book-half me-2 text-danger"></i>Book Operations
                </h1>
            </div>
            <div class="col-sm-6 text-end">
                <button class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#issueModal">
                    <i class="bi bi-plus-lg me-2"></i>Issue Book
                </button>
            </div>
        </div>
    </div>
</div>

<div class="content px-4">
    <div class="container-fluid">

        <!-- Flash message output -->
        <?= $message ?>

        <!-- ══════════════════════════════════════════════════
             CARD 1 — Overdue Alert Panel
             Visible to: librarian, assistant_manager, super_admin
             ══════════════════════════════════════════════════ -->
        <?php if (!empty($overdueAlerts)): ?>
        <div class="card glass-card border-danger border-opacity-50 mb-4">
            <div class="card-header bg-danger bg-opacity-10 border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title fw-bold m-0 text-danger">
                    <i class="bi bi-alarm-fill me-2"></i>
                    Overdue Alerts
                    <span class="badge bg-danger ms-2"><?= count($overdueAlerts) ?></span>
                </h5>
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3">
                    Staff View Only
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Book</th>
                                <th class="text-center">Due Date</th>
                                <th class="text-center">Days Overdue</th>
                                <th class="text-center">Projected Fine</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($overdueAlerts as $alert): ?>
                            <tr class="table-danger table-danger-soft">
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($alert['student_name']) ?></div>
                                    <span class="text-muted small"><?= htmlspecialchars($alert['student_email']) ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($alert['book_title']) ?></div>
                                    <span class="text-muted small">Rs. <?= number_format($alert['fine_per_day'], 2) ?>/day</span>
                                </td>
                                <td class="text-center text-danger fw-medium">
                                    <?= date('d M Y', strtotime($alert['due_date'])) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger rounded-pill px-3">
                                        <?= $alert['days_overdue'] ?> day(s)
                                    </span>
                                </td>
                                <td class="text-center fw-bold text-danger">
                                    Rs. <?= number_format($alert['projected_fine'], 2) ?>
                                </td>
                                <td class="text-center">
                                    <a href="?return_id=<?= $alert['borrowing_id'] ?>"
                                       class="btn btn-sm btn-outline-success px-3 rounded-pill"
                                       onclick="return confirm('Confirm return & apply fine of Rs. <?= $alert['projected_fine'] ?> to this student?')">
                                        <i class="bi bi-box-arrow-in-left me-1"></i>Return
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ══════════════════════════════════════════════════
             CARD 2 — Active Borrowings with Return Tracker
             ══════════════════════════════════════════════════ -->
        <div class="card glass-card mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="card-title fw-bold m-0 text-dark">
                    <i class="bi bi-clock-history me-2 text-primary"></i>Return & Time Tracker
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Book</th>
                                <th class="text-center">Issued</th>
                                <th class="text-center">Due Date</th>
                                <th class="text-center">Days Status</th>
                                <th class="text-center">Est. Fine</th>
                                <th class="text-center">Return</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($activeBorrowings)): ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">No active borrowings found.</td></tr>
                        <?php else: foreach ($activeBorrowings as $loan):
                            $today      = new DateTime('today');
                            $due        = new DateTime($loan['due_date']);
                            $diff       = $today->diff($due);
                            $isOverdue  = ($today > $due);
                            $daysText   = $isOverdue
                                ? '<span class="badge bg-danger rounded-pill px-3"><i class="bi bi-exclamation-circle me-1"></i>' . $diff->days . ' days overdue</span>'
                                : '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3">' . $diff->days . ' days left</span>';

                            // Fetch per-book fine rate
                            $fineStmt = $pdo->prepare("SELECT fine_per_day FROM lib_books WHERE id = ?");
                            $fineStmt->execute([$loan['book_id']]);
                            $finePerDay   = (float)($fineStmt->fetchColumn() ?: 10.00);
                            $estimatedFine = $isOverdue ? round($diff->days * $finePerDay, 2) : 0;
                        ?>
                            <tr class="<?= $isOverdue ? 'table-danger table-danger-soft' : '' ?>">
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($loan['student_name']) ?></div>
                                    <span class="text-muted small">ID: #<?= $loan['user_id'] ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($loan['title']) ?></div>
                                    <span class="text-muted small">Rs. <?= number_format($finePerDay, 2) ?>/day</span>
                                </td>
                                <td class="text-center"><?= date('d M Y', strtotime($loan['borrow_date'])) ?></td>
                                <td class="text-center <?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                    <?= date('d M Y', strtotime($loan['due_date'])) ?>
                                </td>
                                <td class="text-center"><?= $daysText ?></td>
                                <td class="text-center <?= $estimatedFine > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    <?= $estimatedFine > 0 ? 'Rs. ' . number_format($estimatedFine, 2) : '—' ?>
                                </td>
                                <td class="text-center">
                                    <a href="?return_id=<?= $loan['id'] ?>"
                                       class="btn btn-sm btn-outline-success px-3 rounded-pill"
                                       onclick="return confirm('Confirm return<?= $estimatedFine > 0 ? " and charge fine of Rs. $estimatedFine" : "" ?>?')">
                                        <i class="bi bi-box-arrow-in-left me-1"></i>Return
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             CARD 3 — Book Categorisation & Issueable Toggle
             ══════════════════════════════════════════════════ -->
        <div class="card glass-card mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="card-title fw-bold m-0 text-dark">
                    <i class="bi bi-tags-fill me-2 text-warning"></i>Book Settings (Category, Issueable Status & Fine Rate)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th class="text-center">Type / Category</th>
                                <th class="text-center">Fine / Day</th>
                                <th class="text-center">Issueable</th>
                                <th class="text-center">Save</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($books)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">No books registered.</td></tr>
                        <?php else: foreach ($books as $book): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($book['title']) ?></div>
                                    <span class="text-muted small">ID: #<?= $book['id'] ?> &middot; <?= htmlspecialchars($book['category_name'] ?? 'Uncategorised') ?></span>
                                </td>
                                <form method="POST" action="">
                                    <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                    <input type="hidden" name="action_update_book" value="1">
                                <td class="text-center">
                                    <select name="book_type" class="form-select form-select-sm border-0 bg-light">
                                        <?php
                                        $types = ['Textbook','Reference','Periodical','Fiction','Non-Fiction','Other'];
                                        foreach ($types as $t):
                                            $sel = (($book['book_type'] ?? 'Textbook') === $t) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $t ?>" <?= $sel ?>><?= $t ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <div class="input-group input-group-sm" style="width:120px; margin:auto;">
                                        <span class="input-group-text bg-light border-0">Rs.</span>
                                        <input type="number" name="fine_per_day" step="0.50" min="0"
                                               value="<?= htmlspecialchars($book['fine_per_day'] ?? '10.00') ?>"
                                               class="form-control border-0 bg-light text-center">
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               name="is_issueable" value="1" id="toggle_<?= $book['id'] ?>"
                                               <?= ($book['is_issueable'] ?? 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label ms-2" for="toggle_<?= $book['id'] ?>">
                                            <?= ($book['is_issueable'] ?? 1)
                                                ? '<span class="text-success fw-semibold">Yes</span>'
                                                : '<span class="text-danger fw-semibold">No</span>' ?>
                                        </label>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button type="submit" class="btn btn-sm btn-primary px-3 rounded-pill shadow-sm">
                                        <i class="bi bi-save me-1"></i>Save
                                    </button>
                                </td>
                                </form>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- /container-fluid -->
</div><!-- /content -->

<!-- ═══════════════════════════════════════════════════════
     Issue Book Modal (with Non-Issueable safety check)
     ═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="issueModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-0 pb-0">
                <h4 class="modal-title fw-bold text-dark">
                    <i class="bi bi-journal-check me-2 text-success"></i>Issue a Book
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action_checkout" value="1">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Book
                            <span class="text-danger small ms-1">(Non-Issueable books are blocked)</span>
                        </label>
                        <select name="book_id" class="form-select border-0 bg-light py-2" required id="selectBook">
                            <option value="">-- Select Book --</option>
                            <?php foreach ($books as $b): ?>
                                <?php if ($b['available_copies'] > 0): ?>
                                <option value="<?= $b['id'] ?>"
                                    data-issueable="<?= $b['is_issueable'] ?? 1 ?>"
                                    <?= !($b['is_issueable'] ?? 1) ? 'style="color:#dc2626;font-style:italic;"' : '' ?>>
                                    <?= htmlspecialchars($b['title']) ?>
                                    (<?= $b['available_copies'] ?> avail)
                                    <?= !($b['is_issueable'] ?? 1) ? ' — NON-ISSUEABLE' : '' ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <!-- JS real-time block notice -->
                        <div id="issueBlock" class="alert alert-danger mt-2 p-2 d-none small">
                            <i class="bi bi-ban me-1"></i>
                            This book is <strong>Non-Issueable</strong>. Submission will be blocked.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Student</label>
                        <select name="student_id" class="form-select border-0 bg-light py-2" required id="selectStudent">
                            <option value="">-- Select Student --</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['email'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <!-- Student Vitals Alert -->
                        <div id="studentVitals" class="mt-2 d-none">
                            <div class="alert alert-warning p-2 small border-0 mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                <span id="vitalsText"></span>
                            </div>
                        </div>
                    </div>
                    <!-- Override Toggle (Librarian/Admin Only) -->
                    <?php if (in_array($_SESSION['role'], ['librarian', 'super_admin'])): ?>
                    <div id="overrideSection" class="mb-3 d-none">
                        <div class="form-check form-switch p-3 bg-light rounded-3">
                            <input class="form-check-input ms-0" type="checkbox" name="is_override" value="1" id="overrideSwitch">
                            <label class="form-check-label ms-2 fw-bold text-danger" for="overrideSwitch">
                                <i class="bi bi-shield-exclamation me-1"></i>Enable Librarian Override
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Return Due Date</label>
                        <input type="date" name="due_date" class="form-control border-0 bg-light py-2"
                               value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4 me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="issueSubmitBtn" class="btn btn-primary px-4">
                        <i class="bi bi-send me-1"></i>Complete Issue
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     Inline JS — Real-time issueable toggle label update
     and modal book check
     ═══════════════════════════════════════════════════════ -->
<script>
/* Toggle label refresh */
document.querySelectorAll('.form-check-input[role="switch"]').forEach(sw => {
    sw.addEventListener('change', function () {
        const label = this.nextElementSibling;
        if (this.checked) {
            label.innerHTML = '<span class="text-success fw-semibold">Yes</span>';
        } else {
            label.innerHTML = '<span class="text-danger fw-semibold">No</span>';
        }
    });
});

/* Student selection vitals check */
const studentSelect = document.getElementById('selectStudent');
const vitalsBox     = document.getElementById('studentVitals');
const vitalsText    = document.getElementById('vitalsText');
const overrideBox   = document.getElementById('overrideSection');
const overrideSwitch = document.getElementById('overrideSwitch');

if (studentSelect) {
    studentSelect.addEventListener('change', function() {
        const studentId = this.value;
        if (!studentId) {
            vitalsBox.classList.add('d-none');
            if (overrideBox) overrideBox.classList.add('d-none');
            return;
        }

        fetch(`ajax_student_vitals.php?student_id=${studentId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const v = data.vitals;
                    let warnings = [];
                    if (v.has_exceeded) warnings.push(`Limit Exceeded (${v.active_loans}/${v.borrow_limit})`);
                    if (v.has_fines) warnings.push(`Active Fines (Rs. ${v.fines})`);

                    if (warnings.length > 0) {
                        vitalsBox.classList.remove('d-none');
                        vitalsText.innerHTML = `<strong>Attention:</strong> ${warnings.join(' & ')}. Interaction blocked.`;
                        submitBtn.setAttribute('disabled', 'disabled');
                        if (overrideBox) overrideBox.classList.remove('d-none');
                    } else {
                        vitalsBox.classList.add('d-none');
                        submitBtn.removeAttribute('disabled');
                        if (overrideBox) overrideBox.classList.add('d-none');
                        if (overrideSwitch) overrideSwitch.checked = false;
                    }
                }
            });
    });
}

if (overrideSwitch) {
    overrideSwitch.addEventListener('change', function() {
        if (this.checked) {
            submitBtn.removeAttribute('disabled');
        } else {
            // Re-check if it should be disabled
            studentSelect.dispatchEvent(new Event('change'));
        }
    });
}
</script>

<?php require_once '../../includes/footer.php'; ?>
