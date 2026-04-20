<?php
/**
 * book_categories.php
 * ─────────────────────────────────────────────────────────────
 * Dedicated Book Category Management & Issueable Status Page
 *
 * Sections:
 *   1. Manage Categories  — Add / Edit / Delete lib_categories
 *   2. Issueable Books    — All books where is_issueable = 1
 *   3. Non-Issueable Books— All books where is_issueable = 0
 * ─────────────────────────────────────────────────────────────
 */

require_once '../../core/config.php';
require_once '../../core/db.php';
require_once '../../core/audit_helper.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// ── Role guard ───────────────────────────────────────────────
$allowedRoles = ['librarian', 'assistant_manager', 'super_admin'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $allowedRoles)) {
    header('Location: ../../login.php');
    exit;
}

$message = '';

// ═══════════════════════════════════════════════════════════
// ACTION: Add New Category
// ═══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $catName = trim($_POST['category_name'] ?? '');
    $catDesc = trim($_POST['category_description'] ?? '');

    if ($catName === '') {
        $message = alert('danger', 'Category name cannot be empty.');
    } else {
        // Check duplicate
        $chk = $pdo->prepare("SELECT id FROM lib_categories WHERE category_name = ?");
        $chk->execute([$catName]);
        if ($chk->fetch()) {
            $message = alert('warning', "Category \"$catName\" already exists.");
        } else {
            $stmt = $pdo->prepare("INSERT INTO lib_categories (category_name, description) VALUES (?, ?)");
            if ($stmt->execute([$catName, $catDesc])) {
                logAction('CATEGORY_ADDED', "Category added: \"$catName\"");
                $message = alert('success', "Category <strong>\"$catName\"</strong> added successfully.");
            } else {
                $message = alert('danger', 'Failed to add category.');
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════
// ACTION: Edit / Rename Category
// ═══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $catId      = (int)   ($_POST['cat_id']              ?? 0);
    $newName    = trim(    $_POST['edit_category_name']  ?? '');
    $newDesc    = trim(    $_POST['edit_category_desc']  ?? '');

    if ($catId > 0 && $newName !== '') {
        $stmt = $pdo->prepare("UPDATE lib_categories SET category_name = ?, description = ? WHERE id = ?");
        if ($stmt->execute([$newName, $newDesc, $catId])) {
            logAction('CATEGORY_UPDATED', "Category ID $catId renamed to \"$newName\"");
            $message = alert('success', "Category updated to <strong>\"$newName\"</strong>.");
        } else {
            $message = alert('danger', 'Failed to update category.');
        }
    } else {
        $message = alert('warning', 'Invalid category data submitted.');
    }
}

// ═══════════════════════════════════════════════════════════
// ACTION: Delete Category
// ═══════════════════════════════════════════════════════════
if (isset($_GET['delete_cat'])) {
    $catId = (int) $_GET['delete_cat'];
    // Safety: check if any books still use this category
    $usedCheck = $pdo->prepare("SELECT COUNT(*) FROM lib_books WHERE category_id = ?");
    $usedCheck->execute([$catId]);
    $usedCount = (int) $usedCheck->fetchColumn();

    if ($usedCount > 0) {
        $message = alert('warning', "Cannot delete: <strong>$usedCount book(s)</strong> are still assigned to this category. Re-assign them first.");
    } else {
        $nameStmt = $pdo->prepare("SELECT category_name FROM lib_categories WHERE id = ?");
        $nameStmt->execute([$catId]);
        $catName  = $nameStmt->fetchColumn();
        $del = $pdo->prepare("DELETE FROM lib_categories WHERE id = ?");
        if ($del->execute([$catId])) {
            logAction('CATEGORY_DELETED', "Category ID $catId (\"$catName\") deleted.");
            $message = alert('success', "Category <strong>\"$catName\"</strong> deleted.");
        } else {
            $message = alert('danger', 'Failed to delete category.');
        }
    }
}

// ═══════════════════════════════════════════════════════════
// ACTION: Quick-toggle a book's issueable status
// ═══════════════════════════════════════════════════════════
if (isset($_GET['toggle_issue'])) {
    $bookId    = (int) $_GET['toggle_issue'];
    $newStatus = (int) ($_GET['to'] ?? 0); // 0 or 1

    $titleStmt = $pdo->prepare("SELECT title FROM lib_books WHERE id = ?");
    $titleStmt->execute([$bookId]);
    $bookTitle = $titleStmt->fetchColumn();

    $stmt = $pdo->prepare("UPDATE lib_books SET is_issueable = ? WHERE id = ?");
    if ($stmt->execute([$newStatus, $bookId])) {
        $label = $newStatus ? 'Issueable' : 'Non-Issueable';
        logAction('BOOK_STATUS_TOGGLED', "Book ID $bookId (\"$bookTitle\") set to $label");
        $message = alert('success', "\"$bookTitle\" is now marked as <strong>$label</strong>.");
    } else {
        $message = alert('danger', 'Could not update book status.');
    }
}

// ═══════════════════════════════════════════════════════════
// ACTION: Add Book from Modal
// ═══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book_modal'])) {
    $title    = trim($_POST['book_title'] ?? '');
    $author   = trim($_POST['author_name'] ?? '');
    $catId    = (int) ($_POST['category_id'] ?? 0);
    $isbn     = trim($_POST['isbn'] ?? '');
    $stock    = (int) ($_POST['stock_qty'] ?? 1);

    if ($title !== '' && $author !== '' && $catId > 0) {
        require_once '../../core/library_functions.php';
        $lib = new Library($pdo);
        // Find or Create Author ID
        $stmt = $pdo->prepare("SELECT id FROM lib_authors WHERE author_name = ?");
        $stmt->execute([$author]);
        $authorId = $stmt->fetchColumn();
        if (!$authorId) {
            $stmt = $pdo->prepare("INSERT INTO lib_authors (author_name) VALUES (?)");
            $stmt->execute([$author]);
            $authorId = $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare("INSERT INTO lib_books (title, author_id, category_id, isbn, total_copies, available_copies, is_issueable) VALUES (?, ?, ?, ?, ?, ?, 1)");
        if ($stmt->execute([$title, $authorId, $catId, $isbn, $stock, $stock])) {
            logAction('BOOK_ADDED_MODAL', "Added \"$title\" (Stock: $stock) to Category ID $catId");
            $message = alert('success', "Book <strong>\"$title\"</strong> added to collection.");
        } else {
            $message = alert('danger', 'Failed to register the volume.');
        }
    }
}

// ═══════════════════════════════════════════════════════════
// ACTION: Reissue Book (Extend Due Date)
// ═══════════════════════════════════════════════════════════
if (isset($_GET['reissue_id'])) {
    $borrowId = (int) $_GET['reissue_id'];
    
    // Check borrowing record and student fine balance
    $stmt = $pdo->prepare("
        SELECT br.id, br.user_id, br.book_id, u.fines, b.title 
        FROM lib_borrowings br
        JOIN users u ON br.user_id = u.id
        JOIN lib_books b ON br.book_id = b.id
        WHERE br.id = ? AND br.status != 'returned'
    ");
    $stmt->execute([$borrowId]);
    $loan = $stmt->fetch();

    if ($loan) {
        if ($loan['fines'] <= 0) {
            $newDueDate = date('Y-m-d', strtotime('+14 days'));
            $upd = $pdo->prepare("UPDATE lib_borrowings SET due_date = ?, status = 'borrowed' WHERE id = ?");
            if ($upd->execute([$newDueDate, $borrowId])) {
                logAction('REISSUE', "Extended due date for \"{$loan['title']}\" (Student ID: {$loan['user_id']}) to $newDueDate");
                $message = alert('success', "Success! Due date extended to <strong>$newDueDate</strong>.");
            }
        } else {
            $message = alert('warning', "REISSUE_BLOCKED: Student has outstanding fines (Rs. {$loan['fines']}). Settle fines first.");
        }
    }
}

// ═══════════════════════════════════════════════════════════
// ACTION: Delete Book
// ═══════════════════════════════════════════════════════════
if (isset($_GET['delete_book'])) {
    $bookId = (int) $_GET['delete_book'];
    
    // Safety: check if book is currently on loan
    $loanCheck = $pdo->prepare("SELECT COUNT(*) FROM lib_borrowings WHERE book_id = ? AND status != 'returned'");
    $loanCheck->execute([$bookId]);
    if ($loanCheck->fetchColumn() > 0) {
        $message = alert('warning', "Cannot delete book. It is currently on loan to students.");
    } else {
        $titleStmt = $pdo->prepare("SELECT title FROM lib_books WHERE id = ?");
        $titleStmt->execute([$bookId]);
        $bookTitle = $titleStmt->fetchColumn();
        
        $del = $pdo->prepare("DELETE FROM lib_books WHERE id = ?");
        if ($del->execute([$bookId])) {
            logAction('BOOK_DELETED', "Book ID $bookId (\"$bookTitle\") deleted.");
            $message = alert('success', "Book <strong>\"$bookTitle\"</strong> deleted successfully.");
        } else {
            $message = alert('danger', 'Failed to delete book.');
        }
    }
}

// ═══════════════════════════════════════════════════════════
// ACTION: Sync All Stock (Rectify 8/6 errors)
// ═══════════════════════════════════════════════════════════
if (isset($_GET['sync_all_stock'])) {
    try {
        $pdo->beginTransaction();
        $pdo->exec("UPDATE lib_books SET available_copies = total_copies");
        $loans = $pdo->query("SELECT book_id, COUNT(*) as active_count FROM lib_borrowings WHERE status != 'returned' GROUP BY book_id")->fetchAll();
        foreach ($loans as $loan) {
            $upd = $pdo->prepare("UPDATE lib_books SET available_copies = total_copies - ? WHERE id = ?");
            $upd->execute([$loan['active_count'], $loan['book_id']]);
        }
        $pdo->commit();
        logAction('STOCK_SYNCED', "Performed global inventory reconciliation");
        $message = alert('success', "Inventory reconciled! All volume counts are now accurate.");
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = alert('danger', "Sync failed: " . $e->getMessage());
    }
}

// ═══════════════════════════════════════════════════════════
// ACTION: Update Individual Book Stock
// ═══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock_action'])) {
    $bookId   = (int) ($_POST['book_id'] ?? 0);
    $newTotal = (int) ($_POST['new_total'] ?? 0);

    if ($bookId > 0 && $newTotal >= 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM lib_borrowings WHERE book_id = ? AND status != 'returned'");
        $stmt->execute([$bookId]);
        $activeLoans = (int) $stmt->fetchColumn();

        if ($newTotal < $activeLoans) {
            $message = alert('warning', "Cannot set stock to $newTotal. <strong>$activeLoans</strong> copies are currently on loan.");
        } else {
            $newAvailable = $newTotal - $activeLoans;
            $upd = $pdo->prepare("UPDATE lib_books SET total_copies = ?, available_copies = ? WHERE id = ?");
            if ($upd->execute([$newTotal, $newAvailable, $bookId])) {
                logAction('STOCK_UPDATED', "Book ID $bookId: Stock adjusted to $newTotal (Available: $newAvailable)");
                $message = alert('success', "Stock updated successfully.");
            }
        }
    }
}

// ── Load data for display ────────────────────────────────────
$categories = $pdo->query("
    SELECT c.*, COUNT(b.id) AS book_count, SUM(b.total_copies) AS total_volumes
    FROM lib_categories c
    LEFT JOIN lib_books b ON b.category_id = c.id
    GROUP BY c.id
    ORDER BY c.category_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Calculate global collection total
$totalCollection = array_sum(array_column($categories, 'total_volumes'));

$issueableBooks = $pdo->query("
    SELECT b.id, b.title, b.available_copies, b.total_copies,
           a.author_name, c.category_name, b.fine_per_day
    FROM lib_books b
    LEFT JOIN lib_authors   a ON b.author_id   = a.id
    LEFT JOIN lib_categories c ON b.category_id = c.id
    WHERE b.is_issueable = 1
    ORDER BY b.title ASC
")->fetchAll(PDO::FETCH_ASSOC);

$nonIssueableBooks = $pdo->query("
    SELECT b.id, b.title, b.available_copies, b.total_copies,
           a.author_name, c.category_name, b.fine_per_day
    FROM lib_books b
    LEFT JOIN lib_authors   a ON b.author_id   = a.id
    LEFT JOIN lib_categories c ON b.category_id = c.id
    WHERE b.is_issueable = 0
    ORDER BY b.title ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ── Helper ───────────────────────────────────────────────────
function alert(string $type, string $body): string {
    $icons = [
        'success' => 'bi-check-circle-fill',
        'danger'  => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
    ];
    $icon = $icons[$type] ?? 'bi-info-circle-fill';
    return "<div class=\"alert alert-$type alert-dismissible fade show shadow-sm border-0 rounded-4\">
                <i class=\"bi $icon me-2\"></i>$body
                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
            </div>";
}

require_once '../../includes/header.php';
?>

<!-- ══════════════════════════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════════════════════════ -->
<div class="premium-header px-4 py-5 mb-5">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-7 text-white">
                <nav aria-label="breadcrumb" class="nav-breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="#" class="text-white-50 text-decoration-none">Librarian</a></li>
                        <li class="breadcrumb-item active text-white">Book Categories</li>
                    </ol>
                </nav>
                <h1 class="display-5 fw-900 mb-2">
                    <i class="bi bi-collection-gold me-2"></i>Collection Mastery
                </h1>
                <p class="lead text-white-50 opacity-75 mb-0">Professional curation and inventory sovereignty.</p>
            </div>
            <div class="col-sm-5 text-end">
                <a href="?sync_all_stock=1" class="btn btn-glass text-white border-white-50 me-2 rounded-pill px-4" onclick="return confirm('Synchronize all system counts?')">
                    <i class="bi bi-stars me-2"></i>Recalibrate
                </a>
                <button class="btn btn-light text-crimson fw-bold px-4 rounded-pill shadow-lg" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="bi bi-plus-circle-fill me-2"></i>New Category
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .app-content-header { display: none !important; }
    .text-crimson { color: var(--premium-crimson); }

    /* Override global theme's !important crimson for the main header */
    .premium-header h1 {
        color: #ffffff !important;
    }
    .btn-glass { 
        background: rgba(255,255,255,0.1); 
        backdrop-filter: blur(5px);
        border: 1px solid rgba(255,255,255,0.2);
    }
    .btn-glass:hover { background: rgba(255,255,255,0.2); }
</style>

<div class="content px-4 pb-5">
    <div class="container-fluid">

        <?= $message ?>

        <!-- Stats Row -->
        <div class="row g-4 mb-5 mt-n5 px-2">
            <div class="col-md-3">
                <div class="card card-premium border-0 p-4 text-center rounded-5">
                    <div class="fw-900 fs-1 text-premium"><?= count($categories) ?></div>
                    <div class="text-muted small tracking-widest text-uppercase fw-bold mt-1">Disciplines</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-premium border-0 p-4 text-center rounded-5">
                    <div class="fw-900 fs-1 text-success"><?= count($issueableBooks) ?></div>
                    <div class="text-muted small tracking-widest text-uppercase fw-bold mt-1">Available Titles</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-premium border-0 p-4 text-center rounded-5">
                    <div class="fw-900 fs-1 text-danger"><?= count($nonIssueableBooks) ?></div>
                    <div class="text-muted small tracking-widest text-uppercase fw-bold mt-1">Vault Restricted</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-premium border-0 p-4 text-center rounded-5">
                    <div class="fw-900 fs-1 text-warning"><?= number_format($totalCollection) ?></div>
                    <div class="text-muted small tracking-widest text-uppercase fw-bold mt-1">Global Assets</div>
                </div>
            </div>
        </div>
        
        <style>
            .mt-n5 { margin-top: -3.5rem !important; }
            .text-premium { color: var(--premium-crimson); }
        </style>

        <!-- ══════════════════════════════════════════════════
             SECTION 1 — Manage Categories
        ══════════════════════════════════════════════════ -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 py-3 px-4">
                <h5 class="fw-bold m-0 text-dark">
                    <i class="bi bi-folder2-open me-2 text-warning"></i>Library Categories
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($categories)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-folder-x fs-1 d-block mb-2"></i>
                        No categories found. Add your first one above.
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4 py-3" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">#</th>
                                <th style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Category Name</th>
                                <th style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Description</th>
                                <th class="text-center" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Titles</th>
                                <th class="text-center" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Collection Size</th>
                                <th class="text-center pe-4" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($categories as $i => $cat): ?>
                            <tr>
                                <td class="ps-4 text-muted"><?= $i + 1 ?></td>
                                <td>
                                    <a href="javascript:void(0)" class="view-category-books text-decoration-none" 
                                       data-id="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars($cat['category_name']) ?>">
                                        <span class="badge rounded-pill px-3 py-2"
                                              style="background:rgba(79, 70, 229, 0.08);color:var(--erp-primary);font-size:.85rem;font-weight:600;">
                                            <i class="bi bi-tag me-1"></i><?= htmlspecialchars($cat['category_name']) ?>
                                        </span>
                                    </a>
                                </td>
                                <td class="text-muted small"><?= htmlspecialchars($cat['description'] ?? '—') ?></td>
                                <td class="text-center">
                                    <a href="category_books.php?cat_id=<?= $cat['id'] ?>" class="text-decoration-none">
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3">
                                            <?= $cat['book_count'] ?> title(s)
                                        </span>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3">
                                        <?= number_format($cat['total_volumes'] ?: 0) ?> volume(s)
                                    </span>
                                </td>
                                <td class="text-center pe-4">
                                    <!-- Edit button -->
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 me-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editCatModal"
                                            data-id="<?= $cat['id'] ?>"
                                            data-name="<?= htmlspecialchars($cat['category_name'], ENT_QUOTES) ?>"
                                            data-desc="<?= htmlspecialchars($cat['description'] ?? '', ENT_QUOTES) ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <!-- Delete link -->
                                    <a href="?delete_cat=<?= $cat['id'] ?>"
                                       class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                       onclick="return confirm('Delete category \'<?= htmlspecialchars($cat['category_name'], ENT_QUOTES) ?>\'? This cannot be undone.')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             SECTION 2 — Issueable Books
        ══════════════════════════════════════════════════ -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold m-0 text-dark">
                    <i class="bi bi-check-circle-fill me-2 text-success"></i>Issueable Books
                    <span class="badge bg-success ms-2"><?= count($issueableBooks) ?></span>
                </h5>
                <span class="text-muted small">Available for student checkout</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($issueableBooks)): ?>
                    <div class="text-center text-muted py-4"><i class="bi bi-book fs-1 d-block mb-1"></i>No issueable books found.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4 py-3" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Title</th>
                                <th style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Author</th>
                                <th style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Category</th>
                                <th class="text-center" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Copies</th>
                                <th class="text-center" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Fine/Day</th>
                                <th class="text-center pe-4" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($issueableBooks as $book): ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="javascript:void(0)" class="view-book-details text-decoration-none" data-id="<?= $book['id'] ?>">
                                        <div class="fw-semibold text-primary"><?= htmlspecialchars($book['title']) ?></div>
                                    </a>
                                    <span class="text-muted small">ID: #<?= $book['id'] ?></span>
                                </td>
                                <td class="text-muted small"><?= htmlspecialchars($book['author_name'] ?? '—') ?></td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2">
                                        <?= htmlspecialchars($book['category_name'] ?? 'Uncategorised') ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="<?= $book['available_copies'] < 1 ? 'text-danger fw-bold' : 'text-success fw-semibold' ?>">
                                        <?= $book['available_copies'] ?>/<?= $book['total_copies'] ?>
                                    </span>
                                </td>
                                <td class="text-center text-muted small">Rs. <?= number_format($book['fine_per_day'] ?? 10, 2) ?></td>
                                <td class="text-center pe-4">
                                    <a href="?toggle_issue=<?= $book['id'] ?>&to=0"
                                       class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                       onclick="return confirm('Mark \'<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>\' as Non-Issueable?')">
                                        <i class="bi bi-slash-circle me-1"></i>Block
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             SECTION 3 — Non-Issueable Books
        ══════════════════════════════════════════════════ -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center"
                 style="border-left: 4px solid #dc2626;">
                <h5 class="fw-bold m-0 text-dark">
                    <i class="bi bi-slash-circle-fill me-2 text-danger"></i>Non-Issueable Books
                    <span class="badge bg-danger ms-2"><?= count($nonIssueableBooks) ?></span>
                </h5>
                <span class="text-danger small fw-semibold">
                    <i class="bi bi-shield-lock me-1"></i>Blocked from student checkout
                </span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($nonIssueableBooks)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-check-all fs-1 d-block mb-1 text-success"></i>
                        All books are currently issueable.
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4 py-3" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Title</th>
                                <th style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Author</th>
                                <th style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Category</th>
                                <th class="text-center" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Copies</th>
                                <th class="text-center" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Fine/Day</th>
                                <th class="text-center pe-4" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($nonIssueableBooks as $book): ?>
                            <tr class="table-danger table-danger-soft">
                                <td class="ps-4">
                                    <a href="javascript:void(0)" class="view-book-details text-decoration-none" data-id="<?= $book['id'] ?>">
                                        <div class="fw-semibold text-danger"><?= htmlspecialchars($book['title']) ?></div>
                                    </a>
                                    <span class="text-muted small">ID: #<?= $book['id'] ?></span>
                                </td>
                                <td class="text-muted small"><?= htmlspecialchars($book['author_name'] ?? '—') ?></td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2">
                                        <?= htmlspecialchars($book['category_name'] ?? 'Uncategorised') ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="text-muted"><?= $book['available_copies'] ?>/<?= $book['total_copies'] ?></span>
                                </td>
                                <td class="text-center text-muted small">Rs. <?= number_format($book['fine_per_day'] ?? 10, 2) ?></td>
                                <td class="text-center pe-4">
                                    <a href="?toggle_issue=<?= $book['id'] ?>&to=1"
                                       class="btn btn-sm btn-outline-success rounded-pill px-3"
                                       onclick="return confirm('Re-enable \'<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>\' for student checkout?')">
                                        <i class="bi bi-check-circle me-1"></i>Enable
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /container-fluid -->
</div><!-- /content -->

<!-- ═══════════════════════════════════════════
     MODAL — Add New Category
═══════════════════════════════════════════ -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="bi bi-plus-circle-fill me-2 text-success"></i>Add New Category
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="add_category" value="1">
                <div class="modal-body px-4 pb-2">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="category_name" class="form-control border-0 bg-light py-2"
                               placeholder="e.g. Reference, Textbook, Periodical" required maxlength="50">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-uppercase">Description</label>
                        <textarea name="category_description" class="form-control border-0 bg-light"
                                  rows="2" placeholder="Optional: brief description of this category"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-2 pb-4 px-4 justify-content-end">
                    <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-plus-lg me-1"></i>Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════
     MODAL — Edit Category
═══════════════════════════════════════════ -->
<div class="modal fade" id="editCatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="bi bi-pencil-fill me-2 text-warning"></i>Edit Category
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="edit_category" value="1">
                <input type="hidden" name="cat_id" id="editCatId">
                <div class="modal-body px-4 pb-2">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="edit_category_name" id="editCatName"
                               class="form-control border-0 bg-light py-2" required maxlength="50">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-uppercase">Description</label>
                        <textarea name="edit_category_desc" id="editCatDesc"
                                  class="form-control border-0 bg-light" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-2 pb-4 px-4 justify-content-end">
                    <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4 text-white">
                        <i class="bi bi-save me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════
     MODAL — Category Detail Card (Dynamic)
═══════════════════════════════════════════ -->
<div class="modal fade" id="categoryDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-modal border-0 rounded-5 overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4 bg-white bg-opacity-50">
                <h5 class="modal-title fw-900 text-dark display-6 fs-4">
                    <i class="bi bi-stack me-3 text-premium"></i><span id="modalCategoryName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <!-- Add Book Inline Form -->
                <div class="card bg-light border-0 rounded-4 mb-4">
                    <div class="card-body p-3">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-plus-square-fill text-success me-2"></i>Quick Add Book</h6>
                        <form method="POST" action="" class="row g-2">
                            <input type="hidden" name="add_book_modal" value="1">
                            <input type="hidden" name="category_id" id="modalAddBook_CatId">
                            <div class="col-md-5">
                                <input type="text" name="book_title" class="form-control form-control-sm border-0" placeholder="Book Title" required>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="author_name" class="form-control form-control-sm border-0" placeholder="Author Name" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="stock_qty" class="form-control form-control-sm border-0" placeholder="Qty" min="1" value="1" required>
                            </div>
                            <div class="col-12 mt-2 text-end">
                                <button type="submit" class="btn btn-sm btn-success px-4 rounded-pill">Register volume</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="categoryBooksLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Fetching collection details...</p>
                </div>
                <div id="categoryBooksContent" style="display:none;">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border-0">
                            <thead class="table-light">
                                <tr class="small text-uppercase text-muted">
                                    <th class="border-0">Book Title & Author</th>
                                    <th class="border-0 text-center">Status</th>
                                    <th class="border-0 text-center">In Stock</th>
                                    <th class="border-0 text-end pe-3">Manage</th>
                                </tr>
                            </thead>
                            <tbody id="categoryBooksTableBody">
                                <!-- Dynamic content -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Stock Edit Form (Hidden by default, shown by JS) -->
                <div id="stockEditForm" class="card bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-4 mt-3" style="display:none;">
                    <div class="card-body p-3">
                        <h6 class="fw-bold text-warning mb-2 small"><i class="bi bi-pencil-square me-2"></i>Update Total Inventory</h6>
                        <form method="POST" action="" class="row g-2 align-items-center">
                            <input type="hidden" name="update_stock_action" value="1">
                            <input type="hidden" name="book_id" id="editStock_BookId">
                            <div class="col-sm-8">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text border-0 bg-white text-muted">Total Copies</span>
                                    <input type="number" name="new_total" id="editStock_Value" class="form-control border-0" required min="0">
                                </div>
                            </div>
                            <div class="col-sm-4 text-end">
                                <button type="button" class="btn btn-sm btn-light me-1" onclick="document.getElementById('stockEditForm').style.display='none'">Cancel</button>
                                <button type="submit" class="btn btn-sm btn-warning text-white">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div id="categoryBooksEmpty" style="display:none;" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    This category currently has no books assigned.
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 px-4">
                <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Close Insights</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════
     MODAL — Sophisticated Book Profile (Dynamic)
═══════════════════════════════════════════ -->
<div class="modal fade" id="bookDetailModal" tabindex="-1" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-2xl overflow-hidden" style="border-radius: 28px; background: #ffffff;">
            <div id="bookDetailLoading" class="text-center py-5">
                <div class="spinner-grow text-danger" role="status" style="width: 3rem; height: 3rem;"></div>
                <p class="mt-3 text-muted fw-bold tracking-wider text-uppercase small">Synchronizing Volume Data...</p>
            </div>
            
            <div id="bookDetailContent" style="display:none;">
                <!-- Premium Header with Glassmorphism Overlay -->
                <div class="position-relative overflow-hidden" style="background: linear-gradient(135deg, var(--erp-primary) 0%, var(--erp-primary-dark) 100%); padding: 4rem 2rem 2.5rem;">
                    <div class="position-absolute top-0 end-0 opacity-10" style="font-size: 15rem; margin-top: -3rem; margin-right: -2rem;">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </div>
                    <div class="position-relative z-index-1 text-center">
                        <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-3 py-1 mb-3 small fw-bold text-uppercase tracking-widest border border-white border-opacity-25" id="modalBookCategory_Badge">CATEGORY</span>
                        <h2 class="modal-title fw-900 text-white mb-1 display-6" id="modalBookTitle" style="letter-spacing: -1px;"></h2>
                        <p class="text-white text-opacity-75 fs-5 mb-0 font-italic" id="modalBookAuthor"></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white position-absolute end-0 top-0 m-4 shadow-none" data-bs-dismiss="modal" style="filter: brightness(0) invert(1);"></button>
                </div>

                <div class="modal-body p-0">
                    <!-- High-End Stats Grid -->
                    <div class="row g-0 border-bottom">
                        <div class="col-md-4 py-4 px-3 text-center border-end bg-light bg-opacity-50">
                            <i class="bi bi-upc-scan d-block mb-2 text-primary opacity-50 fs-4"></i>
                            <label class="text-uppercase tracking-tighter text-muted small fw-bold d-block mb-1" style="font-size: 0.65rem;">ISBN Specification</label>
                            <span class="fw-bold text-dark fs-5" id="modalBookISBN"></span>
                        </div>
                        <div class="col-md-4 py-4 px-3 text-center border-end">
                            <i class="bi bi-stack d-block mb-2 text-danger opacity-50 fs-4"></i>
                            <label class="text-uppercase tracking-tighter text-muted small fw-bold d-block mb-1" style="font-size: 0.65rem;">Inventory Status</label>
                            <span class="fw-bold text-dark fs-5" id="modalBookStock"></span>
                        </div>
                        <div class="col-md-4 py-4 px-3 text-center bg-light bg-opacity-50">
                            <i class="bi bi-cash-stack d-block mb-2 text-success opacity-50 fs-4"></i>
                            <label class="text-uppercase tracking-tighter text-muted small fw-bold d-block mb-1" style="font-size: 0.65rem;">Late Fine Policy</label>
                            <span class="fw-bold text-dark fs-5" id="modalBookFine"></span>
                        </div>
                    </div>

                    <div class="px-5 py-5">
                        <div class="row g-5">
                            <!-- Left: Detailed Info -->
                            <div class="col-lg-7">
                                <h6 class="text-uppercase fw-800 text-primary small tracking-widest mb-3 d-flex align-items-center">
                                    <i class="bi bi-text-left me-2"></i>Abstract & Metadata
                                </h6>
                                <div class="bg-light p-4 rounded-4 position-relative">
                                    <i class="bi bi-quote position-absolute top-0 start-0 m-2 opacity-10 fs-1"></i>
                                    <p class="mb-0 text-secondary lh-lg small" id="modalBookDesc" style="font-style: italic;"></p>
                                </div>
                            </div>
                            
                            <!-- Right: Current Ecosystem -->
                            <div class="col-lg-5">
                                <h6 class="text-uppercase fw-800 text-danger small tracking-widest mb-3 d-flex align-items-center">
                                    <i class="bi bi-people me-2"></i>Active Borrowers
                                </h6>
                                <div id="modalBookHistory" class="rounded-4 overflow-hidden border">
                                    <!-- Dynamic Listings -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer border-0 pb-5 px-5 justify-content-center">
                <button type="button" class="btn btn-dark px-5 py-3 rounded-pill fw-bold tracking-wide shadow-lg hover-lift transition-all" data-bs-dismiss="modal">
                    RETURN TO COLLECTION
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .fw-900 { font-weight: 900 !important; }
    .fw-800 { font-weight: 800 !important; }
    .tracking-widest { letter-spacing: 0.15em !important; }
    .tracking-wider { letter-spacing: 0.1em !important; }
    .tracking-tighter { letter-spacing: -0.02em !important; }
    .hover-lift:hover { transform: translateY(-3px); }
    .transition-all { transition: all 0.3s ease; }
    #bookDetailModal .modal-content { border: 1px solid rgba(0,0,0,0.05); }
    .blinking { animation: blinker 1.5s linear infinite; }
    @keyframes blinker { 50% { opacity: 0; } }
    .btn-xs { padding: 1px 5px; font-size: 0.75rem; line-height: 1.5; border-radius: 3px; }

    /* Sophisticated UI Layer */
    :root {
        --premium-crimson: var(--erp-primary);
        --premium-gold: #b2945e;
        --glass-bg: rgba(255, 255, 255, 0.7);
        --glass-border: rgba(255, 255, 255, 0.3);
    }

    .premium-header {
        background: linear-gradient(135deg, var(--erp-primary) 0%, var(--erp-primary-dark) 100%);
        border-radius: 0 0 40px 40px;
        box-shadow: 0 10px 30px rgba(79, 70, 229, 0.2);
    }

    .card-premium {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s ease;
    }

    .card-premium:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    }

    .glass-modal {
        backdrop-filter: blur(15px);
        background: rgba(255, 255, 255, 0.8) !important;
        border: 1px solid rgba(255, 255, 255, 0.45) !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15) !important;
    }

    .badge-premium {
        background: linear-gradient(45deg, var(--erp-primary), var(--erp-primary-light));
        color: white;
        border: none;
        box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);
    }

    .nav-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
        color: rgba(255, 255, 255, 0.5);
    }

    .floating-book-row {
        background: white;
        margin-bottom: 12px;
        border-radius: 15px;
        border: 1px solid transparent;
        transition: all 0.2s ease;
    }

    .floating-book-row:hover {
        border-color: var(--premium-gold);
        background: #fffcf9;
    }
    .btn-glass-warning { background: rgba(255,193,7,0.1); border: 1px solid rgba(255,193,7,0.2); transition: all 0.2s; }
    .btn-glass-warning:hover { background: rgba(255,193,7,0.2); }
    .btn-glass-danger { background: rgba(220,53,69,0.1); border: 1px solid rgba(220,53,69,0.2); transition: all 0.2s; }
    .btn-glass-danger:hover { background: rgba(220,53,69,0.2); }
</style>

<script>
/* Populate the Edit Category modal with row data */
document.getElementById('editCatModal').addEventListener('show.bs.modal', function (event) {
    const btn  = event.relatedTarget;
    document.getElementById('editCatId').value   = btn.getAttribute('data-id');
    document.getElementById('editCatName').value  = btn.getAttribute('data-name');
    document.getElementById('editCatDesc').value  = btn.getAttribute('data-desc');
});

/* Handle Category Detail Modal */
document.querySelectorAll('.view-category-books').forEach(link => {
    link.addEventListener('click', function() {
        const catId = this.dataset.id;
        const catName = this.dataset.name;
        
        document.getElementById('modalCategoryName').innerText = catName;
        const modal = new bootstrap.Modal(document.getElementById('categoryDetailModal'));
        modal.show();
        
        // Reset and Show Loading
        const loading = document.getElementById('categoryBooksLoading');
        const content = document.getElementById('categoryBooksContent');
        const empty = document.getElementById('categoryBooksEmpty');
        const tableBody = document.getElementById('categoryBooksTableBody');
        
        loading.style.display = 'block';
        content.style.display = 'none';
        empty.style.display = 'none';
        tableBody.innerHTML = '';
        
        // Pass Category ID to the "Add Book" form inside the modal
        document.getElementById('modalAddBook_CatId').value = catId;
        
        fetch(`ajax_category_books.php?cat_id=${catId}`)
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                if (data.success && data.books.length > 0) {
                    data.books.forEach(book => {
                        const statusBadge = book.is_issueable == 1 
                            ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2">Issueable</span>'
                            : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2">Non-Issueable</span>';
                        
                        const row = `
                            <tr class="floating-book-row shadow-sm">
                                <td class="ps-4">
                                    <a href="javascript:void(0)" class="view-book-details text-decoration-none" data-id="${book.id}">
                                        <div class="fw-bold text-premium fs-5">${book.title}</div>
                                    </a>
                                    <div class="text-muted small fw-bold">${book.author_name || 'Unknown Author'}</div>
                                    <div class="text-muted extra-small mt-1 opacity-75" style="font-size:0.75rem;">${book.description || 'No description available.'}</div>
                                </td>
                                <td class="text-center align-middle">${statusBadge}</td>
                                <td class="text-center align-middle">
                                    <span class="fw-900 ${book.available_copies > book.total_copies ? 'text-danger blinking' : (book.available_copies > 0 ? 'text-success' : 'text-danger')}">
                                        ${book.available_copies}/${book.total_copies}
                                    </span>
                                    ${book.available_copies > book.total_copies ? '<i class="bi bi-exclamation-triangle-fill text-danger ms-1" title="Data Desync Detected"></i>' : ''}
                                </td>
                                <td class="text-end pe-4 align-middle">
                                    <button class="btn btn-sm btn-glass-warning text-warning me-1 rounded-pill px-3" onclick="showStockEdit(${book.id}, ${book.total_copies})" title="Calibrate">
                                        <i class="bi bi-gear-fill me-1"></i>Edit
                                    </button>
                                    <a href="?delete_book=${book.id}" class="btn btn-sm btn-glass-danger text-danger rounded-pill px-3" 
                                       onclick="return confirm('ERASE VOLUME: \'${book.title}\'? This will vaporize all records.')" title="Destroy Record">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </td>
                            </tr>
                        `;
                        tableBody.innerHTML += row;
                    });
                    content.style.display = 'block';
                } else {
                    empty.style.display = 'block';
                }
            })
            .catch(err => {
                loading.style.display = 'none';
                alert('Error loading category books.');
                console.error(err);
            });
    });
});

/* Handle Book Detail Modal */
function initBookLinks() {
    document.querySelectorAll('.view-book-details').forEach(link => {
        link.removeEventListener('click', openBookModal); // Prevent dupe listeners
        link.addEventListener('click', openBookModal);
    });
}

function openBookModal(e) {
    const bookId = this.dataset.id;
    const modalElement = document.getElementById('bookDetailModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    const loading = document.getElementById('bookDetailLoading');
    const content = document.getElementById('bookDetailContent');
    
    loading.style.display = 'block';
    content.style.display = 'none';

    fetch(`ajax_book_details.php?id=${bookId}`)
        .then(res => res.json())
        .then(data => {
            loading.style.display = 'none';
            if (data.success) {
                const book = data.book;
                document.getElementById('modalBookTitle').innerText = book.title;
                document.getElementById('modalBookAuthor').innerText = (book.author_name || 'System Entity');
                document.getElementById('modalBookCategory_Badge').innerText = book.category_name || 'Uncategorised';
                document.getElementById('modalBookISBN').innerText = book.isbn || 'N/A';
                document.getElementById('modalBookStock').innerText = `${book.available_copies} / ${book.total_copies}`;
                document.getElementById('modalBookFine').innerText = 'Rs. ' + parseFloat(book.fine_per_day || 10).toFixed(2);
                document.getElementById('modalBookDesc').innerText = book.description || 'This volume belongs to the university\'s specialized collection. No extended abstract is currently registered for this index.';

                let historyHtml = '';
                if (data.active_loans.length > 0) {
                    historyHtml = '<div class="list-group list-group-flush">';
                    data.active_loans.forEach(loan => {
                        historyHtml += `
                            <div class="list-group-item d-flex justify-content-between align-items-center p-3 border-start-0 border-end-0">
                                <div>
                                    <div class="fw-bold text-dark small">${loan.student_name}</div>
                                    <div class="text-muted extra-small" style="font-size: 0.65rem;">Active Engagement</div>
                                </div>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2 py-1 small">
                                    DUE: ${loan.due_date}
                                </span>
                                <div class="ms-2">
                                    <a href="?reissue_id=${loan.id}" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0" style="font-size: 0.65rem;">
                                        <i class="bi bi-arrow-repeat me-1"></i>Reissue
                                    </a>
                                </div>
                            </div>`;
                    });
                    historyHtml += '</div>';
                } else {
                    historyHtml = '<div class="p-4 text-center text-muted small bg-light rounded-4 border border-dashed m-3"><i class="bi bi-patch-check me-2"></i>No active holdings detected.</div>';
                }
                document.getElementById('modalBookHistory').innerHTML = historyHtml;
                content.style.display = 'block';
            } else {
                alert(data.message);
                modal.hide();
            }
        })
        .catch(err => {
            loading.style.display = 'none';
            alert('Error fetching book details.');
            console.error(err);
        });
}

function showStockEdit(bookId, total) {
    document.getElementById('stockEditForm').style.display = 'block';
    document.getElementById('editStock_BookId').value = bookId;
    document.getElementById('editStock_Value').value = total;
    document.getElementById('editStock_Value').focus();
}

document.addEventListener('DOMContentLoaded', initBookLinks);
// Also re-init whenever a modal opens (for content inside modals)
document.getElementById('categoryDetailModal').addEventListener('shown.bs.modal', initBookLinks);
</script>

<?php require_once '../../includes/footer.php'; ?>
