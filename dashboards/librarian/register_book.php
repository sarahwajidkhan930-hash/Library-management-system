<?php
require_once '../../core/config.php';
require_once '../../core/db.php';
session_start();

// Security check: Only librarians and super admins can access
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

// Handle Bulk CSV Import
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bulk_import'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        $header = fgetcsv($handle); // Skip header line
        
        $count = 0;
        $errors = 0;
        
        require_once '../../core/audit_helper.php';
        
        while (($row = fgetcsv($handle)) !== FALSE) {
            if (count($row) < 5) continue;
            
            $title = trim($row[0]);
            $author_name = trim($row[1]);
            $category_name = trim($row[2]);
            $isbn = trim($row[3]);
            $qty = (int)$row[4];
            
            if (empty($title) || empty($author_name)) {
                $errors++;
                continue;
            }

            // 1. Author Handing
            $stmt = $mysqli->prepare("SELECT id FROM lib_authors WHERE author_name = ?");
            $stmt->bind_param("s", $author_name);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($exists = $res->fetch_assoc()) {
                $author_id = $exists['id'];
            } else {
                $stmt = $mysqli->prepare("INSERT INTO lib_authors (author_name) VALUES (?)");
                $stmt->bind_param("s", $author_name);
                $stmt->execute();
                $author_id = $mysqli->insert_id;
            }

            // 2. Category Handling
            $stmt = $mysqli->prepare("SELECT id FROM lib_categories WHERE category_name = ?");
            $stmt->bind_param("s", $category_name);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($exists = $res->fetch_assoc()) {
                $category_id = $exists['id'];
            } else {
                $stmt = $mysqli->prepare("INSERT INTO lib_categories (category_name) VALUES (?)");
                $stmt->bind_param("s", $category_name);
                $stmt->execute();
                $category_id = $mysqli->insert_id;
            }

            // 3. Book Insert
            $stmt = $mysqli->prepare("INSERT INTO lib_books (title, author_id, category_id, isbn, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siisii", $title, $author_id, $category_id, $isbn, $qty, $qty);
            if ($stmt->execute()) {
                $count++;
            } else {
                $errors++;
            }
        }
        fclose($handle);
        
        logAction('BULK_IMPORT_BOOKS', "Imported $count books from CSV. Errors: $errors");
        $_SESSION['success_msg'] = "Bulk assimilation complete: $count volumes added successfully ($errors errors).";
        header("Location: register_book.php");
        exit();
    }
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $existing_book_id = (int)($_POST['existing_book_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $category_input = (int)($_POST['category'] ?? 0);
    $new_category_name = trim($_POST['new_category_name'] ?? '');
    $total_copies_to_add = (int)$_POST['total_copies'];
    $is_issueable = isset($_POST['is_issueable']) ? 1 : 0;
    
    $cover_image_path = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        if (in_array($_FILES['cover_image']['type'], $allowed_types)) {
            $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('cover_') . '.' . $ext;
            $destination = '../../assets/img/covers/' . $filename;
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $destination)) {
                $cover_image_path = $filename;
            }
        }
    }

    require_once '../../core/audit_helper.php';

    if ($existing_book_id > 0) {
        // --- EXISTING BOOK MODE (STOCK REPLENISHMENT + METADATA UPDATE) ---
        // Basic Stock Update
        $stmt = $mysqli->prepare("UPDATE lib_books SET total_copies = total_copies + ?, available_copies = available_copies + ? WHERE id = ?");
        $stmt->bind_param("iii", $total_copies_to_add, $total_copies_to_add, $existing_book_id);
        $stmt->execute();

        // Metadata Update (ISBN and Category in existing mode)
        $new_isbn = trim($_POST['isbn'] ?? '');
        $cat_input = (int)($_POST['category'] ?? 0);
        $new_cat_name = trim($_POST['new_category_name'] ?? '');
        $final_cat_id = $cat_input;

        if ($new_cat_name !== '') {
            $chkCat = $mysqli->prepare("SELECT id FROM lib_categories WHERE category_name = ?");
            $chkCat->bind_param("s", $new_cat_name);
            $chkCat->execute();
            $catRes = $chkCat->get_result();
            if ($existsCat = $catRes->fetch_assoc()) {
                $final_cat_id = $existsCat['id'];
            } else {
                $insCat = $mysqli->prepare("INSERT INTO lib_categories (category_name) VALUES (?)");
                $insCat->bind_param("s", $new_cat_name);
                $insCat->execute();
                $final_cat_id = $mysqli->insert_id;
                logAction('NEW_CATEGORY_ADDED', "Curated new category: $new_cat_name during replenishment");
            }
        }

        if ($final_cat_id > 0) {
            if ($cover_image_path !== null) {
                $updMeta = $mysqli->prepare("UPDATE lib_books SET isbn = ?, category_id = ?, cover_image = ? WHERE id = ?");
                $updMeta->bind_param("sisi", $new_isbn, $final_cat_id, $cover_image_path, $existing_book_id);
            } else {
                $updMeta = $mysqli->prepare("UPDATE lib_books SET isbn = ?, category_id = ? WHERE id = ?");
                $updMeta->bind_param("sii", $new_isbn, $final_cat_id, $existing_book_id);
            }
            $updMeta->execute();
        } elseif ($cover_image_path !== null) {
            $updMeta = $mysqli->prepare("UPDATE lib_books SET cover_image = ? WHERE id = ?");
            $updMeta->bind_param("si", $cover_image_path, $existing_book_id);
            $updMeta->execute();
        }

        $getTit = $mysqli->prepare("SELECT title FROM lib_books WHERE id = ?");
        $getTit->bind_param("i", $existing_book_id);
        $getTit->execute();
        if ($resTit = $getTit->get_result()->fetch_assoc()) {
            $bookTitle = $resTit['title'];
            logAction('STOCK_REPLENISHMENT', "Replenished '$bookTitle' (ID: $existing_book_id) by $total_copies_to_add units. Metadata synchronized.");
            $_SESSION['success_msg'] = "Stock for '$bookTitle' successfully replenished by $total_copies_to_add units.";
            header("Location: register_book.php");
            exit();
        }
    } else {
        // --- NEW BOOK MODE ---
        // Logic for New vs Existing Author
        $author_id = 0;
        $new_author_name = trim($_POST['new_author_name'] ?? '');
        
        // --- Author Curation ---
        if ($new_author_name !== '') {
            $chkAuthor = $mysqli->prepare("SELECT id FROM lib_authors WHERE author_name = ?");
            $chkAuthor->bind_param("s", $new_author_name);
            $chkAuthor->execute();
            $authRes = $chkAuthor->get_result();
            if ($exists = $authRes->fetch_assoc()) {
                $author_id = $exists['id'];
            } else {
                $insAuthor = $mysqli->prepare("INSERT INTO lib_authors (author_name) VALUES (?)");
                $insAuthor->bind_param("s", $new_author_name);
                $insAuthor->execute();
                $author_id = $mysqli->insert_id;
                logAction('NEW_AUTHOR_ADDED', "Curated new author: $new_author_name");
            }
        } else {
            $author_id = (int)($_POST['author'] ?? 0);
        }

        // --- Category Curation ---
        $category_id = $category_input;
        if ($new_category_name !== '') {
            $chkCat = $mysqli->prepare("SELECT id FROM lib_categories WHERE category_name = ?");
            $chkCat->bind_param("s", $new_category_name);
            $chkCat->execute();
            $catRes = $chkCat->get_result();
            if ($existsCat = $catRes->fetch_assoc()) {
                $category_id = $existsCat['id'];
            } else {
                $insCat = $mysqli->prepare("INSERT INTO lib_categories (category_name) VALUES (?)");
                $insCat->bind_param("s", $new_category_name);
                $insCat->execute();
                $category_id = $mysqli->insert_id;
                logAction('NEW_CATEGORY_ADDED', "Curated new category: $new_category_name");
            }
        }

        if ($author_id > 0 && $category_id > 0 && $title !== '') {
            $stmt = $mysqli->prepare("INSERT INTO lib_books (title, author_id, category_id, isbn, total_copies, available_copies, is_issueable, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siisiiis", $title, $author_id, $category_id, $isbn, $total_copies_to_add, $total_copies_to_add, $is_issueable, $cover_image_path);

            if ($stmt->execute()) {
                $book_id = $mysqli->insert_id;
                logAction('REGISTER_BOOK_PREMIUM', "Registered '$title' (Volume ID: $book_id, Initial Stock: $total_copies_to_add)");

                $_SESSION['success_msg'] = "Volume '$title' successfully assimilated into the collection.";
                header("Location: register_book.php");
                exit();
            } else {
                $message = "Registry Error: " . $mysqli->error;
                $messageType = "danger";
            }
        } else {
            $message = "Incomplete Curation: Please ensure all critical metadata is provided.";
            $messageType = "warning";
        }
    }
}

if (isset($_SESSION['success_msg'])) {
    $message = $_SESSION['success_msg'];
    $messageType = "success";
    unset($_SESSION['success_msg']);
}

// Fetch data for dropdowns
$categories = $mysqli->query("SELECT id, category_name FROM lib_categories ORDER BY category_name ASC");
$authors = $mysqli->query("SELECT id, author_name FROM lib_authors ORDER BY author_name ASC");
$allBooks = $mysqli->query("SELECT id, title, isbn FROM lib_books ORDER BY title ASC");

require_once '../../includes/header.php';
?>

<style>
    :root {
        --premium-crimson: var(--erp-primary);
        --premium-black: #0f172a;
        --premium-gold: #b2945e;
        --glass-bg: rgba(255, 255, 255, 0.7);
        --glass-border: rgba(255, 255, 255, 0.3);
    }

    body { 
        background-color: #f1f5f9 !important; 
        font-family: 'Inter', sans-serif;
    }

    .premium-header-bg {
        background: linear-gradient(135deg, var(--erp-primary) 0%, var(--erp-primary-dark) 100%);
        padding: 6rem 0 10rem;
        border-radius: 0 0 50px 50px;
        box-shadow: 0 10px 40px rgba(79, 70, 229, 0.25);
    }

    .glass-card {
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border: 1px solid var(--glass-border);
        border-radius: 30px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.1);
        margin-top: -8rem;
    }

    .stat-badge {
        background: white;
        padding: 1rem 1.5rem;
        border-radius: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border-left: 4px solid var(--premium-gold);
    }

    .form-label {
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.75rem;
        color: #64748b;
    }

    .premium-input {
        border-radius: 15px;
        padding: 0.8rem 1.2rem;
        border: 1px solid #e2e8f0;
        background: white;
        transition: all 0.3s ease;
    }

    .premium-input:focus {
        border-color: var(--premium-crimson);
        box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.1);
    }

    .btn-assimilate {
        background: linear-gradient(135deg, var(--erp-primary) 0%, var(--erp-primary-dark) 100%);
        color: white;
        border: none;
        border-radius: 15px;
        padding: 1rem 3rem;
        font-weight: 800;
        letter-spacing: 1px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.25);
    }

    .btn-assimilate:hover {
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 15px 35px rgba(79, 70, 229, 0.35);
        color: white;
    }

    .switch-link {
        color: var(--premium-crimson);
        font-weight: 900;
        font-size: 0.7rem;
        cursor: pointer;
        padding: 2px 8px;
        background: rgba(0, 0, 0, 0.05);
        border-radius: 6px;
    }

    /* Remove Redundant Global Header for this page only */
    .app-content-header { display: none !important; }
    .premium-header-bg { margin-top: -1rem; } /* Tighten spacing after hiding header */

    /* Override global theme's !important crimson for this specific header */
    .premium-header-bg h1 {
        color: #ffffff !important;
    }
</style>

<div class="premium-header-bg text-center text-white">
    <div class="container">
        <h1 class="display-4 fw-900 mb-0 tracking-tighter">Bibliographic Asset Entry</h1>
        <p class="opacity-50 fs-5">Curating volumes for the modern university ecosystem.</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row justify-content-center">
        <!-- Main Form Column -->
        <div class="col-lg-8">
            <div class="glass-card shadow-2xl p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold m-0 text-dark">Acquisition Form</h4>
                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3" type="button" data-bs-toggle="collapse" data-bs-target="#bulkImportCol">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Bulk Import
                    </button>
                </div>

                <!-- Bulk Import Collapse -->
                <div class="collapse mb-4" id="bulkImportCol">
                    <div class="bg-primary bg-opacity-10 p-4 rounded-4 border border-primary border-opacity-25">
                        <h6 class="fw-bold text-primary mb-2">Bulk CSV Assimilation</h6>
                        <p class="small text-muted mb-3">Format: <code>Title, Author, Category, ISBN, Quantity</code>. Skip the first header row.</p>
                        <form action="register_book.php" method="POST" enctype="multipart/form-data">
                            <div class="input-group">
                                <input type="file" name="csv_file" class="form-control form-control-sm rounded-start-pill" accept=".csv" required>
                                <button type="submit" name="bulk_import" class="btn btn-primary btn-sm rounded-end-pill px-4">Upload & Process</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show border-0 rounded-4 shadow-sm py-3 mb-4" role="alert">
                        <i class="bi <?= $messageType == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="register_book.php" method="POST" class="needs-validation" enctype="multipart/form-data" novalidate>
                    <div class="row g-4">
                        <div class="col-12">
                            <label for="title" class="form-label d-flex justify-content-between">
                                Complete Volume Title
                                <span class="switch-link" onclick="toggleOption('volume')">
                                    <i class="bi bi-journal-check me-1"></i>Existing?
                                </span>
                            </label>
                            
                            <div id="volume_select_wrap" style="display:none;">
                                <select name="existing_book_id" id="volume_existing" class="form-select premium-input border-0 shadow-sm" onchange="autoFillExistingData()">
                                    <option value="" selected disabled>Select Existing Volume</option>
                                    <?php if ($allBooks && $allBooks->num_rows > 0): ?>
                                        <?php while($bk = $allBooks->fetch_assoc()): ?>
                                            <?php 
                                            // Get category name for better identification
                                            $currBkId = $bk['id'];
                                            $extra = $mysqli->query("SELECT c.id as cat_id, c.category_name, b.author_id FROM lib_books b JOIN lib_categories c ON b.category_id = c.id WHERE b.id = $currBkId")->fetch_assoc();
                                            ?>
                                            <option value="<?= $bk['id'] ?>" 
                                                    data-isbn="<?= htmlspecialchars($bk['isbn']) ?>" 
                                                    data-category="<?= $extra['cat_id'] ?>"
                                                    data-author="<?= $extra['author_id'] ?>">
                                                <?= htmlspecialchars($bk['title']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div id="volume_input_wrap">
                                <input type="text" name="title" id="title" class="form-control premium-input border-0 shadow-sm" placeholder="e.g. Principia Mathematica" required>
                            </div>
                        </div>

                        <!-- Author Section -->
                        <div class="col-md-6">
                            <label for="author" class="form-label d-flex justify-content-between">
                                Curation Lead
                                <span class="switch-link" onclick="toggleOption('author')">
                                    <i class="bi bi-person-plus-fill me-1"></i>New?
                                </span>
                            </label>
                            <div id="author_select_wrap">
                                <select name="author" id="author" class="form-select premium-input border-0 shadow-sm">
                                    <option value="" selected disabled>Select Curation</option>
                                    <?php if ($authors && $authors->num_rows > 0): ?>
                                        <?php while($auth = $authors->fetch_assoc()): ?>
                                            <option value="<?= $auth['id'] ?>"><?= htmlspecialchars($auth['author_name']) ?></option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div id="author_input_wrap" style="display:none;">
                                <input type="text" name="new_author_name" id="new_author_name" class="form-control premium-input border-0 shadow-sm" placeholder="Register new author name...">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="isbn" class="form-label">ISBN-13 Specification</label>
                            <input type="text" name="isbn" id="isbn" class="form-control premium-input border-0 shadow-sm" placeholder="978-XX-XXXXXX" required>
                            <div id="isbn-warning" class="extra-small text-danger mt-1 fw-bold" style="display:none;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Warning: This ISBN already exists (Title: <span id="duplicate-title"></span>)
                            </div>
                        </div>

                        <!-- Category Section -->
                        <div class="col-md-6">
                            <label for="category" class="form-label d-flex justify-content-between">
                                Discipline / Category
                                <span class="switch-link" onclick="toggleOption('category')">
                                    <i class="bi bi-folder-plus me-1"></i>New?
                                </span>
                            </label>
                            <div id="category_select_wrap">
                                <select name="category" id="category" class="form-select premium-input border-0 shadow-sm">
                                    <option value="" selected disabled>Select Discipline</option>
                                    <?php if ($categories && $categories->num_rows > 0): ?>
                                        <?php while($cat = $categories->fetch_assoc()): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div id="category_input_wrap" style="display:none;">
                                <input type="text" name="new_category_name" id="new_category_name" class="form-control premium-input border-0 shadow-sm" placeholder="Register new discipline...">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="total_copies" class="form-label">Acquisition Qty</label>
                            <input type="number" name="total_copies" id="total_copies" class="form-control premium-input border-0 shadow-sm" min="1" value="1" required>
                        </div>
                        
                        <div class="col-md-8">
                            <label for="cover_image" class="form-label">Cover Artwork <span class="text-muted fw-normal text-lowercase">(Optional)</span></label>
                            <input type="file" name="cover_image" id="cover_image" class="form-control premium-input border-0 shadow-sm bg-white" accept="image/jpeg, image/png, image/jpg">
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch p-4 bg-white bg-opacity-50 rounded-4 border border-white">
                                <input class="form-check-input ms-0 me-3" type="checkbox" name="is_issueable" id="is_issueable" checked>
                                <label class="form-check-label fw-bold text-premium" for="is_issueable">
                                    Authorize for Circulation (Student Checkout Enable)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 d-flex gap-3 justify-content-end">
                        <button type="submit" name="submit" class="btn btn-assimilate shadow-lg">
                            <i class="bi bi-shield-check me-2"></i>Finalize Acquisition
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar / Stats Column -->
        <div class="col-lg-3 mt-4 mt-lg-0">
            <div class="stat-badge mb-3">
                <div class="text-muted extra-small text-uppercase fw-bold">Daily Thruput</div>
                <div class="h3 fw-900 mb-0"><?= $mysqli->query("SELECT COUNT(*) FROM lib_books WHERE DATE(created_at) = CURDATE()")->fetch_row()[0] ?></div>
                <div class="text-muted small">Books registered today</div>
            </div>
            <div class="stat-badge mb-3">
                <div class="text-muted extra-small text-uppercase fw-bold">Live Catalog</div>
                <div class="h3 fw-900 mb-0"><?= $mysqli->query("SELECT id FROM lib_books")->num_rows ?></div>
                <div class="text-muted small">Total unique titles</div>
            </div>
            <div class="stat-badge">
                <div class="text-muted extra-small text-uppercase fw-bold">Authors</div>
                <div class="h3 fw-900 mb-0"><?= $mysqli->query("SELECT id FROM lib_authors")->num_rows ?></div>
                <div class="text-muted small">Registered contributors</div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleOption(type) {
        if (type === 'volume') {
            const selectWrap = document.getElementById('volume_select_wrap');
            const inputWrap = document.getElementById('volume_input_wrap');
            const selectEl = document.getElementById('volume_existing');
            const inputEl = document.getElementById('title');
            const isbnEl = document.getElementById('isbn');
            const authorEl = document.getElementById('author');
            const categoryEl = document.getElementById('category');

            if (inputWrap.style.display === 'none') {
                // BACK TO NEW BOOK MODE
                inputWrap.style.display = 'block';
                selectWrap.style.display = 'none';
                selectEl.value = "";
                inputEl.setAttribute('required', 'required');
                authorEl.disabled = false;
                // Category/Author might have been hidden by their own New? toggles
                inputEl.focus();
            } else {
                // EXISTING BOOK MODE
                inputWrap.style.display = 'none';
                selectWrap.style.display = 'block';
                inputEl.value = "";
                inputEl.removeAttribute('required');
                // We keep Category/ISBN enabled so they are "Workable" for corrections
                authorEl.disabled = true; // Author is usually immutable for existing titles
                selectEl.focus();
            }
            return;
        }

        const selectWrap = document.getElementById(type + '_select_wrap');
        const inputWrap = document.getElementById(type + '_input_wrap');
        const selectEl = document.getElementById(type);
        const inputEl = document.getElementById('new_' + type + '_name');

        if (inputWrap.style.display === 'none') {
            inputWrap.style.display = 'block';
            selectWrap.style.display = 'none';
            selectEl.value = "";
            selectEl.removeAttribute('required');
            inputEl.setAttribute('required', 'required');
            inputEl.focus();
        } else {
            inputWrap.style.display = 'none';
            selectWrap.style.display = 'block';
            inputEl.value = "";
            inputEl.removeAttribute('required');
            selectEl.setAttribute('required', 'required');
        }
    }

    function autoFillExistingData() {
        const select = document.getElementById('volume_existing');
        const isbnInput = document.getElementById('isbn');
        const categorySelect = document.getElementById('category');
        const authorSelect = document.getElementById('author');
        
        const selectedOption = select.options[select.selectedIndex];
        
        if (selectedOption.dataset.isbn) isbnInput.value = selectedOption.dataset.isbn;
        if (selectedOption.dataset.category) categorySelect.value = selectedOption.dataset.category;
        if (selectedOption.dataset.author) authorSelect.value = selectedOption.dataset.author;
    }

    document.getElementById('isbn').addEventListener('blur', async function() {
        const isbn = this.value.trim();
        const warning = document.getElementById('isbn-warning');
        const titleSpan = document.getElementById('duplicate-title');
        
        if (!isbn) {
            warning.style.display = 'none';
            return;
        }

        try {
            const response = await fetch(`ajax_check_isbn.php?isbn=${encodeURIComponent(isbn)}`);
            const data = await response.json();
            if (data.exists) {
                titleSpan.innerText = data.title;
                warning.style.display = 'block';
                this.classList.add('is-invalid');
            } else {
                warning.style.display = 'none';
                this.classList.remove('is-invalid');
            }
        } catch (e) {
            console.error('ISBN Check failed');
        }
    });

    (function () {
      'use strict'
      var forms = document.querySelectorAll('.needs-validation')
      Array.prototype.slice.call(forms)
        .forEach(function (form) {
          form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
              event.preventDefault()
              event.stopPropagation()
            }
            form.classList.add('was-validated')
          }, false)
        })
    })()
</script>

<?php 
$mysqli->close();
require_once '../../includes/footer.php'; 
?>
