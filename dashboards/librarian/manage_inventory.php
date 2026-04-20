<?php 
require_once '../../core/session_guard.php';
require_once '../../core/rbac_helper.php';
require_once '../../core/audit_helper.php';
require_once '../../includes/header.php'; 
require_once '../../core/library_functions.php';

$lib = new Library($pdo);
$message = '';

// Handle AJAX Update (Inline Edit)
if (isset($_POST['ajax_update'])) {
    header('Content-Type: application/json');
    $book_id = $_POST['book_id'];
    $total_copies = $_POST['total_copies'];
    $category_name = $_POST['category_name'];
    
    // Get existing book to keep other fields the same
    $book = $lib->getBookById($book_id);
    if ($book) {
        if ($lib->updateBook($book_id, $book['title'], $book['author_name'], $book['isbn'], $total_copies, $category_name)) {
            logAction('UPDATE_STOCK', "Updated stock to $total_copies and category to $category_name for Book ID: $book_id");
            echo json_encode(['success' => true, 'message' => 'Book updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update book']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
    }
    exit;
}

// Handle Add Book
if (isset($_POST['add_book'])) {
    $title = $_POST['title'];
    $author = $_POST['author_name'];
    $isbn = $_POST['isbn'];
    $total_copies = $_POST['total_copies'];
    $category = $_POST['category_name'];
    
        if ($lib->addBook($title, $author, $isbn, $total_copies, $category)) {
            $bookId = $pdo->lastInsertId();
            logAction('REGISTER_BOOK', "Quick register: $title (ID: $bookId)");
            $message = '<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm"><i class="bi bi-check-circle-fill me-2"></i> <strong>Success!</strong> New book has been registered to the inventory. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"><i class="bi bi-exclamation-octagon-fill me-2"></i> <strong>Error!</strong> Could not add the book. Please check your input. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    // RBAC Guard: Block Assistant Manager from deletion
    restrictTo('delete_record');
    
    $bookId = $_GET['delete'];
    $book = $lib->getBookById($bookId);
    if ($lib->deleteBook($bookId)) {
        logAction('DELETE_BOOK', "Removed book: " . ($book['title'] ?? 'Unknown') . " (ID: $bookId)");
        $message = '<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm"><i class="bi bi-trash-fill me-2"></i> Book has been removed from inventory. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"><i class="bi bi-exclamation-triangle-fill me-2"></i> Error occurred while deleting the record. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

$search = $_GET['search'] ?? '';
$books = $lib->getBooks($search);
?>

<style>
    :root {
        --inv-primary: var(--erp-primary); /* Professional Indigo */
        --inv-accent: #f87171;
        --inv-bg: #f8fafc;
        --inv-card-bg: #ffffff;
        --inv-text-dark: #0f172a;
        --inv-text-muted: #64748b;
        --inv-border: #e2e8f0;
    }
    
    body {
        background-color: var(--inv-bg);
        color: var(--inv-text-dark);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }
    
    .inventory-wrapper {
        padding: 1.5rem;
    }
    
    .page-header {
        margin-bottom: 2rem;
    }
    
    .page-title {
        font-weight: 800;
        letter-spacing: -0.025em;
        color: var(--inv-primary);
        font-size: 1.75rem;
    }
    
    .glass-card {
        background: var(--inv-card-bg);
        border: 1px solid var(--inv-border);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 2rem;
        transition: transform 0.2s ease;
    }
    
    .card-header-custom {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--inv-border);
        background: #fff;
    }
    
    .form-control:focus {
        border-color: var(--inv-primary);
        box-shadow: 0 0 0 3px rgba(153, 27, 27, 0.1);
    }
    
    .btn-inv {
        padding: 0.625rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.2s;
    }
    
    .btn-inv-primary {
        background-color: var(--inv-primary);
        border: none;
        color: white;
    }
    
    .btn-inv-primary:hover {
        background-color: #7f1d1d;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(153, 27, 27, 0.2);
    }
    
    /* Table Styling */
    .table-custom {
        margin-bottom: 0;
    }
    
    .table-custom thead th {
        background-color: #f8fafc;
        padding: 1rem 1.5rem;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--inv-text-muted);
        border-top: none;
    }
    
    .table-custom tbody td {
        padding: 1.25rem 1.5rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--inv-border);
    }
    
    .book-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--inv-text-dark);
        margin-bottom: 0.125rem;
    }
    
    .book-author {
        font-size: 0.875rem;
        color: var(--inv-text-muted);
    }
    
    .badge-category {
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        padding: 0.4rem 0.8rem;
        font-weight: 500;
        border-radius: 8px;
    }
    
    .copies-count {
        font-weight: 700;
        font-size: 1.125rem;
        color: var(--inv-primary);
    }
    
    /* Edit Mode Styling */
    .edit-input {
        display: none;
        max-width: 150px;
    }
    
    .edit-mode .display-text { display: none; }
    .edit-mode .edit-input { display: block; }
    .edit-mode .btn-edit { display: none; }
    .edit-mode .btn-save { display: inline-flex; }
    
    .btn-save { display: none; }
    
    .action-btn {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        transition: all 0.2s;
        border: 1px solid var(--inv-border);
        background: white;
        color: var(--inv-text-muted);
    }
    
    .action-btn:hover {
        background: var(--inv-bg);
        color: var(--inv-primary);
        border-color: var(--inv-primary);
    }
    
    .action-btn.delete:hover {
        color: #ef4444;
        border-color: #ef4444;
        background: #fef2f2;
    }
</style>

<div class="inventory-wrapper">
    <div class="page-header d-flex justify-content-between align-items-end">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-muted">Librarian</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Inventory Management</li>
                </ol>
            </nav>
            <h1 class="page-title"><i class="bi bi-stack me-2"></i>Inventory Management</h1>
        </div>
        <div class="search-container">
            <div class="input-group" style="min-width: 320px;">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Filter by book title..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
    </div>

    <?= $message ?>

    <div class="row g-4">
        <!-- Add Book Sidebar/Section -->
        <div class="col-xl-3">
            <div class="card glass-card">
                <div class="card-header-custom bg-white">
                    <h5 class="m-0 fw-bold"><i class="bi bi-journal-plus me-2 text-primary"></i>Quick Register</h5>
                </div>
                <div class="card-body">
                    <form action="manage_inventory.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Book Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Clean Code" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Author Name</label>
                            <input type="text" name="author_name" class="form-control" placeholder="e.g. Robert C. Martin" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Category / Genre</label>
                            <input type="text" name="category_name" class="form-control" placeholder="e.g. Software Engineering">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">ISBN-13</label>
                            <input type="text" name="isbn" class="form-control" placeholder="978-...">
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Initial Stock</label>
                            <input type="number" name="total_copies" class="form-control" value="1" min="1" required>
                        </div>
                        <button type="submit" name="add_book" class="btn btn-inv btn-inv-primary w-100">
                            <i class="bi bi-plus-lg me-2"></i>Add to Inventory
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Inventory List -->
        <div class="col-xl-9">
            <div class="card glass-card">
                <div class="table-responsive">
                    <table class="table table-custom align-middle" id="inventoryTable">
                        <thead>
                            <tr>
                                <th>Book Information</th>
                                <th>Category</th>
                                <th class="text-center">Stock</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($books)): ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">No records found in inventory.</td></tr>
                            <?php else: foreach ($books as $book): ?>
                                <tr id="row-<?= $book['id'] ?>" data-title="<?= strtolower(htmlspecialchars($book['title'])) ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 48px; height: 60px;">
                                                    <i class="bi bi-book text-muted fs-4"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <a href="book_details.php?id=<?= $book['id'] ?>" class="text-decoration-none">
                                                    <div class="book-title text-primary"><?= htmlspecialchars($book['title']) ?></div>
                                                </a>
                                                <div class="book-author"><?= htmlspecialchars($book['author_name']) ?></div>
                                                <div class="text-muted" style="font-size: 0.75rem;">ISBN: <?= htmlspecialchars($book['isbn'] ?: 'N/A') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="editable-field" data-field="category">
                                        <span class="display-text badge-category"><?= htmlspecialchars($book['category_name'] ?: 'General') ?></span>
                                        <input type="text" class="form-control form-control-sm edit-input" value="<?= htmlspecialchars($book['category_name']) ?>">
                                    </td>
                                    <td class="text-center editable-field" data-field="copies">
                                        <?php 
                                            // Inventory Threshold Alert (below 20% availability)
                                            $stockLevel = ($book['total_copies'] > 0) ? ($book['available_copies'] / $book['total_copies']) : 0;
                                            $isLowStock = ($stockLevel <= 0.2 && $book['total_copies'] > 0);
                                        ?>
                                        <div class="display-text">
                                            <span class="copies-count"><?= $book['total_copies'] ?></span>
                                            <?php if ($isLowStock): ?>
                                                <div class="mt-1">
                                                    <span class="badge text-bg-warning text-dark px-2 py-1" style="font-size: 0.65rem;">
                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> LOW STOCK
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <input type="number" class="form-control form-control-sm edit-input mx-auto text-center" value="<?= $book['total_copies'] ?>" min="0">
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <button class="action-btn btn-edit" title="Edit Row" onclick="toggleEdit(<?= $book['id'] ?>)">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="action-btn btn-save text-success border-success" title="Save Changes" onclick="saveData(<?= $book['id'] ?>)" style="display: none;">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button class="action-btn delete" title="Remove Book" onclick="deleteBook(<?= $book['id'] ?>)">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>
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

<script>
// Search logic
document.getElementById('searchInput').addEventListener('input', function(e) {
    const val = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#inventoryTable tbody tr');
    rows.forEach(row => {
        const title = row.getAttribute('data-title');
        if (title) {
            row.style.display = title.includes(val) ? '' : 'none';
        }
    });
});

function toggleEdit(id) {
    const row = document.getElementById('row-' + id);
    const isEditing = row.classList.contains('edit-mode');
    
    if (!isEditing) {
        row.classList.add('edit-mode');
        row.querySelector('.btn-save').style.display = 'inline-flex';
    } else {
        row.classList.remove('edit-mode');
        row.querySelector('.btn-save').style.display = 'none';
    }
}

function saveData(id) {
    const row = document.getElementById('row-' + id);
    const categoryName = row.querySelector('[data-field="category"] input').value;
    const totalCopies = row.querySelector('[data-field="copies"] input').value;
    
    const formData = new FormData();
    formData.append('ajax_update', '1');
    formData.append('book_id', id);
    formData.append('category_name', categoryName);
    formData.append('total_copies', totalCopies);
    
    // Smooth transition
    row.style.opacity = '0.5';
    
    fetch('manage_inventory.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        row.style.opacity = '1';
        if (data.success) {
            row.querySelector('[data-field="category"] .display-text').textContent = categoryName || 'General';
            row.querySelector('[data-field="copies"] .display-text').textContent = totalCopies;
            toggleEdit(id);
        } else {
            alert('Failed: ' + data.message);
        }
    })
    .catch(err => {
        row.style.opacity = '1';
        console.error(err);
    });
}

function deleteBook(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will remove the book and its borrowing history permanently!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--erp-primary)',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'manage_inventory.php?delete=' + id;
        }
    });
}

// Fallback if SweetAlert2 is not loaded
function deleteBook(id) {
    if (confirm('Are you sure you want to permanently remove this book from the library inventory?')) {
        window.location.href = 'manage_inventory.php?delete=' + id;
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
