<?php
require_once '../../core/config.php';
require_once '../../core/db.php';
session_start();

// Security check: Only librarians can access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['librarian', 'super_admin'])) {
    header("Location: ../../login.php");
    exit();
}

// MySQLi Connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$message = "";
$messageType = "";

// Handle Delete Request
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt = $mysqli->prepare("DELETE FROM lib_books WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Book deleted successfully.";
    } else {
        $_SESSION['error_msg'] = "Error deleting book: " . $mysqli->error;
    }
    $stmt->close();
    header("Location: inventory_management.php");
    exit();
}

// Handle Update Request (Simple example)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_book'])) {
    $book_id = (int)$_POST['book_id'];
    $title = $_POST['title'];
    $isbn = $_POST['isbn'];
    $total_copies = (int)$_POST['total_copies'];

    $stmt = $mysqli->prepare("UPDATE lib_books SET title = ?, isbn = ?, total_copies = ? WHERE id = ?");
    $stmt->bind_param("ssii", $title, $isbn, $total_copies, $book_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Book updated successfully!";
    } else {
        $_SESSION['error_msg'] = "Update failed: " . $stmt->error;
    }
    $stmt->close();
    header("Location: inventory_management.php");
    exit();
}

if (isset($_SESSION['success_msg'])) {
    $message = $_SESSION['success_msg'];
    $messageType = "success";
    unset($_SESSION['success_msg']);
}
if (isset($_SESSION['error_msg'])) {
    $message = $_SESSION['error_msg'];
    $messageType = "danger";
    unset($_SESSION['error_msg']);
}

// Handle Search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT b.*, a.author_name, c.category_name 
          FROM lib_books b 
          LEFT JOIN lib_authors a ON b.author_id = a.id 
          LEFT JOIN lib_categories c ON b.category_id = c.id";

if (!empty($search)) {
    $query .= " WHERE b.title LIKE ? OR a.author_name LIKE ? OR b.isbn LIKE ?";
    $stmt = $mysqli->prepare($query);
    $searchTerm = "%$search%";
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $mysqli->query($query);
}

require_once '../../includes/header.php';
?>

<style>
    :root {
        --premium-crimson: var(--erp-primary);
        --soft-crimson: var(--erp-primary-light);
        --surface-grey: var(--erp-bg-main);
        --border-color: var(--erp-border);
    }

    body { background-color: var(--surface-grey) !important; }

    /* Global Redundancy Fix */
    .app-content-header { display: none !important; }

    .premium-card {
        background: white;
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
        overflow: hidden;
    }

    .premium-header {
        background: white;
        padding: 1.5rem;
        border-bottom: 2px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .premium-title {
        color: var(--premium-crimson);
        font-weight: 800;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .table-premium thead th {
        background-color: #f8fafc;
        color: #64748b;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        font-weight: 700;
        padding: 1.25rem 1rem;
        border-bottom: 2px solid #f1f5f9;
    }

    .table-premium tbody td {
        padding: 1.25rem 1rem;
        vertical-align: middle;
        font-size: 0.9rem;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }

    .table-premium tbody tr:hover {
        background-color: #fcfdfe;
    }

    .stock-status {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0.8rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        border: 1px solid transparent;
    }

    .status-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }

    /* Healthy Stock */
    .status-healthy {
        background-color: #f0fdf4;
        color: #166534;
        border-color: #dcfce7;
    }
    .status-healthy .status-indicator { background-color: #22c55e; }

    /* Low Stock */
    .status-low {
        background-color: #fffbeb;
        color: #92400e;
        border-color: #fef3c7;
    }
    .status-low .status-indicator { 
        background-color: #f59e0b; 
        box-shadow: 0 0 0 rgba(245, 158, 11, 0.4);
        animation: status-pulse 2s infinite;
    }

    /* Out of Stock */
    .status-out {
        background-color: #fef2f2;
        color: var(--erp-primary);
        border-color: #fee2e2;
    }
    .status-out .status-indicator { background-color: #ef4444; }

    @keyframes status-pulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(245, 158, 11, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
    }

    .btn-action {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        transition: all 0.2s;
        border: none;
    }

    .btn-edit { background: #f1f5f9; color: #475569; }
    .btn-edit:hover { background: #e2e8f0; color: #1e293b; }

    .btn-delete { background: #fee2e2; color: #ef4444; }
    .btn-delete:hover { background: #fecaca; color: #dc2626; }

    .search-input {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.5rem 1rem;
        transition: all 0.3s;
    }

    .search-input:focus {
        border-color: var(--premium-crimson);
        box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        background: white;
    }

    .search-btn {
        background: var(--premium-crimson);
        color: white;
        border-radius: 10px;
        border: none;
        padding: 0 1rem;
    }
</style>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="premium-title"><i class="bi bi-stack me-3 p-2 bg-light rounded-3"></i>Inventory Management</h2>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <a href="register_book.php" class="btn btn-crimson px-4 py-2 rounded-pill shadow-sm" style="background: var(--premium-crimson); color: white; border: none; font-weight: 600;">
            <i class="bi bi-plus-lg me-2"></i>Register New Volume
        </a>
    </div>
</div>

<div class="premium-card shadow-sm border-0">
    <div class="premium-header">
        <p class="mb-0 text-muted small fw-600">Active Book Catalog</p>
        <form action="inventory_management.php" method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="search-input form-control-sm" placeholder="Search by ISBN or Title..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="search-btn"><i class="bi bi-search"></i></button>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table table-premium mb-0">
            <thead>
                <tr>
                    <th>Ref ID</th>
                    <th>Book Specification</th>
                    <th>Category</th>
                    <th class="text-center">Stock Details</th>
                    <th class="text-center">Availability</th>
                    <th class="text-center">Operations</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="text-muted small fw-bold">#<?= $row['id'] ?></td>
                            <td>
                                <a href="book_details.php?id=<?= $row['id'] ?>" class="text-decoration-none">
                                    <div class="fw-bold text-primary mb-1"><?= htmlspecialchars($row['title']) ?></div>
                                </a>
                                <div class="small d-flex align-items-center text-muted">
                                    <span class="me-2"><i class="bi bi-person me-1"></i><?= htmlspecialchars($row['author_name'] ?: 'System Entity') ?></span>
                                    <span><i class="bi bi-upc me-1"></i><?= htmlspecialchars($row['isbn']) ?></span>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['category_name'] ?: 'General') ?></span></td>
                            <td class="text-center fw-bold"><?= $row['total_copies'] ?> <small class="text-muted fw-normal">Units</small></td>
                            <td class="text-center">
                                <?php 
                                $avail = (int)$row['available_copies'];
                                if ($avail == 0) {
                                    $statusClass = "status-out";
                                    $statusLabel = "Out of Stock";
                                } elseif ($avail <= 3) {
                                    $statusClass = "status-low";
                                    $statusLabel = "Low Stock: $avail In";
                                } else {
                                    $statusClass = "status-healthy";
                                    $statusLabel = "$avail In Stock";
                                }
                                ?>
                                <div class="stock-status <?= $statusClass ?>">
                                    <span class="status-indicator"></span>
                                    <span><?= $statusLabel ?></span>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn-action btn-edit" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>" title="Edit Entry">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <a href="inventory_management.php?delete_id=<?= $row['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Archive this record? This action cannot be reversed.')" title="Remove Entry">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </div>

                                <!-- Premium Edit Modal -->
                                <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow-lg rounded-4">
                                            <form action="inventory_management.php" method="POST">
                                                <div class="modal-header border-0 pb-0">
                                                    <h5 class="modal-title fw-bold text-crimson" style="color: var(--premium-crimson);">Update Catalog Metadata</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <input type="hidden" name="book_id" value="<?= $row['id'] ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold text-uppercase">Book Title</label>
                                                        <input type="text" name="title" class="form-control rounded-3" value="<?= htmlspecialchars($row['title']) ?>" required>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label small fw-bold text-uppercase">ISBN</label>
                                                            <input type="text" name="isbn" class="form-control rounded-3" value="<?= htmlspecialchars($row['isbn']) ?>" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label small fw-bold text-uppercase">Inventory Units</label>
                                                            <input type="number" name="total_copies" class="form-control rounded-3" value="<?= $row['total_copies'] ?>" min="1" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-0 pt-0 pb-4">
                                                    <button type="button" class="btn btn-light px-4 rounded-3" data-bs-dismiss="modal">Discard</button>
                                                    <button type="submit" name="update_book" class="btn px-4 rounded-3 text-white fw-bold" style="background: var(--premium-crimson);">Commit Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No catalog mappings found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
$mysqli->close();
require_once '../../includes/footer.php'; 
?>
