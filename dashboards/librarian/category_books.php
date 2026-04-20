<?php
require_once '../../core/config.php';
require_once '../../core/db.php';
require_once '../../core/library_functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Role guard
$allowedRoles = ['librarian', 'assistant_manager', 'super_admin'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $allowedRoles)) {
    header('Location: ../../login.php');
    exit;
}

$catId = (int) ($_GET['cat_id'] ?? 0);
if ($catId <= 0) {
    header('Location: book_categories.php');
    exit;
}

$lib = new Library($pdo);
$category = $pdo->prepare("SELECT * FROM lib_categories WHERE id = ?");
$category->execute([$catId]);
$catInfo = $category->fetch();

if (!$catInfo) {
    header('Location: book_categories.php');
    exit;
}

$books = $lib->getBooksByCategory($catId);

require_once '../../includes/header.php';
?>

<div class="content-header px-4 pt-4">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="book_categories.php" class="text-decoration-none text-muted">Book Categories</a></li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($catInfo['category_name']) ?></li>
                    </ol>
                </nav>
                <h1 class="m-0 fw-bold text-dark">
                    <i class="bi bi-collection-fill me-2 text-primary"></i>Collection: <?= htmlspecialchars($catInfo['category_name']) ?>
                </h1>
                <p class="text-muted small mt-1 mb-0"><?= htmlspecialchars($catInfo['description'] ?? 'Explore our ' . $catInfo['category_name'] . ' collection.') ?></p>
            </div>
        </div>
    </div>
</div>

<div class="content px-4 pb-5">
    <div class="container-fluid">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold m-0 text-dark">
                    Books in this Category
                    <span class="badge bg-primary ms-2"><?= count($books) ?></span>
                </h5>
                <a href="book_categories.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i>Back to Categories
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($books)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-book-half fs-1 d-block mb-2 opacity-50"></i>
                        No books assigned to this category yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Book Details</th>
                                    <th style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Status</th>
                                    <th class="text-center" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Availability</th>
                                    <th class="text-center pe-4" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($books as $book): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <a href="book_details.php?id=<?= $book['id'] ?>" class="text-decoration-none">
                                                <div class="fw-bold text-primary" style="font-size: 1.1rem;"><?= htmlspecialchars($book['title']) ?></div>
                                            </a>
                                            <div class="text-muted small"><i class="bi bi-person me-1"></i><?= htmlspecialchars($book['author_name'] ?? 'Unknown Author') ?></div>
                                        </td>
                                        <td>
                                            <?php if ($book['is_issueable'] == 1): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill">
                                                    <i class="bi bi-check2-circle me-1"></i>Issueable
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2 rounded-pill">
                                                    <i class="bi bi-shield-lock me-1"></i>Library Use Only
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="fw-bold <?= $book['available_copies'] > 0 ? 'text-success' : 'text-danger' ?>">
                                                <?= $book['available_copies'] ?> / <?= $book['total_copies'] ?>
                                            </div>
                                            <div class="text-muted extra-small" style="font-size: 0.75rem;">copies available</div>
                                        </td>
                                        <td class="text-center pe-4">
                                            <a href="book_details.php?id=<?= $book['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-4">
                                                <i class="bi bi-eye me-1"></i>View File
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
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
