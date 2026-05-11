<?php
require_once '../../core/db.php';

// 3. Handle Toggle Access (AJAX or Form Post) - MUST BE BEFORE HEADER FOR AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_access'])) {
    $page_id = (int)$_POST['page_id'];
    $role_key = $_POST['role_key'];
    $checked = (int)$_POST['checked'];

    if ($checked) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES (?, ?)");
        $stmt->execute([$role_key, $page_id]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM role_access WHERE role_key = ? AND page_id = ?");
        $stmt->execute([$role_key, $page_id]);
    }
    // Return for AJAX or just continue for Form Post
    if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit;
    }
}

require_once '../../includes/header.php'; 

// 1. Handle Create / Update Page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_page'])) {
    $pId = $_POST['page_id'] ?? 0;
    $pName = trim($_POST['page_name']);
    $pUrl = trim($_POST['page_url']);
    $pParent = (int)$_POST['parent_id'];
    $pIcon = trim($_POST['icon_class']);

    try {
        if ($pId > 0) {
            // Update
            $stmt = $pdo->prepare("UPDATE sys_pages SET parent_id = ?, page_name = ?, page_url = ?, icon_class = ? WHERE id = ?");
            $stmt->execute([$pParent, $pName, $pUrl, $pIcon, $pId]);
            $msg = "Page updated successfully.";
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class) VALUES (?,?,?,?)");
            $stmt->execute([$pParent, $pName, $pUrl, $pIcon]);
            $msg = "New page created successfully.";
        }
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// 2. Handle Delete Page
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $pdo->beginTransaction();
        // Delete role access first (foreign key or just cleanup)
        $pdo->prepare("DELETE FROM role_access WHERE page_id = ?")->execute([$id]);
        // Update children to prevent orphaning
        $pdo->prepare("UPDATE sys_pages SET parent_id = 0 WHERE parent_id = ?")->execute([$id]);
        // Delete page
        $pdo->prepare("DELETE FROM sys_pages WHERE id = ?")->execute([$id]);
        $pdo->commit();
        echo "<script>window.location.href='manage_pages.php';</script>";
        exit;
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Delete Error: " . $e->getMessage();
    }
}


// Fetch Roles
$roles = $pdo->query("SELECT * FROM sys_roles ORDER BY role_name ASC")->fetchAll();

// Fetch Pages & Build Hierarchical Tree
$rawPages = $pdo->query("SELECT * FROM sys_pages ORDER BY sort_order ASC, id ASC")->fetchAll();
$pageTree = [];
foreach ($rawPages as $p) {
    $pageTree[$p['parent_id']][] = $p;
}

$orderedPages = [];
$visited = [];
function flattenTree($parentId, $level, $pageTree, &$orderedPages, &$visited) {
    if (!isset($pageTree[$parentId])) return;
    foreach ($pageTree[$parentId] as $p) {
        if (isset($visited[$p['id']])) continue;
        $visited[$p['id']] = true;
        $p['level'] = $level;
        $orderedPages[] = $p;
        flattenTree($p['id'], $level + 1, $pageTree, $orderedPages, $visited);
    }
}
flattenTree(0, 0, $pageTree, $orderedPages, $visited);

// Append any orphaned pages that couldn't be reached from root
foreach ($rawPages as $p) {
    if (!isset($visited[$p['id']])) {
        $p['level'] = 0;
        $p['page_name'] = '[Orphaned] ' . $p['page_name'];
        $orderedPages[] = $p;
        $visited[$p['id']] = true;
        flattenTree($p['id'], 1, $pageTree, $orderedPages, $visited);
    }
}

// Fetch Current Access (for the matrix)
$accessMap = [];
$accessData = $pdo->query("SELECT * FROM role_access")->fetchAll();
foreach ($accessData as $row) {
    $accessMap[$row['page_id']][$row['role_key']] = true;
}

// Fetch Potential Parents
$parents = $orderedPages;
?>

<div class="card card-primary card-outline">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Manage System Pages & Access Control</h3>
        <button class="btn btn-sm btn-primary" onclick="openModal()"><i class="bi bi-plus-lg"></i> New Page</button>
    </div>
    <div class="card-body">
        <?php if(isset($msg)) echo "<div class='alert alert-success'>$msg</div>"; ?>
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 250px;">Page Name / URL</th>
                        <?php foreach($roles as $r): ?>
                            <th class="text-center"><?= htmlspecialchars($r['role_name']) ?></th>
                        <?php endforeach; ?>
                        <th class="text-center" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($pages)): ?>
                        <tr><td colspan="<?= count($roles) + 2 ?>" class="text-center text-muted">No pages found.</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach($orderedPages as $p): 
                        $padding = $p['level'] * 25;
                    ?>
                    <tr>
                        <td>
                            <div class="fw-bold" style="padding-left: <?= $padding ?>px;">
                                <?= ($p['level'] > 0) ? '<span class="text-muted me-2">|—</span>' : '' ?>
                                <i class="<?= $p['icon_class'] ?: 'bi bi-file-earmark' ?> me-1"></i>
                                <?= htmlspecialchars($p['page_name']) ?>
                            </div>
                            <small class="text-muted" style="padding-left: <?= $padding + ($p['level'] > 0 ? 30 : 25) ?>px;"><?= htmlspecialchars($p['page_url']) ?></small>
                        </td>
                        <?php foreach($roles as $r): 
                            $hasAccess = isset($accessMap[$p['id']][$r['role_key']]);
                            $isDisabled = ($r['role_key'] === 'super_admin') ? 'disabled checked' : '';
                        ?>
                            <td class="text-center">
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input access-toggle" type="checkbox" 
                                           data-page-id="<?= $p['id'] ?>" 
                                           data-role-key="<?= $r['role_key'] ?>"
                                           <?= $hasAccess ? 'checked' : '' ?>
                                           <?= $isDisabled ?>>
                                </div>
                            </td>
                        <?php endforeach; ?>
                        <td class="text-center text-nowrap">
                            <button class="btn btn-sm btn-info text-white" onclick='openModal(<?= json_encode($p) ?>)'>
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this page and all associated access?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for Add/Edit -->
<div class="modal fade" id="pageModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="page_id" id="modal_page_id">
            <input type="hidden" name="save_page" value="1">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add New Page</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">Page Name</label>
                    <input type="text" name="page_name" id="modal_page_name" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">URL Pattern (dashboards/folder/file.php)</label>
                    <input type="text" name="page_url" id="modal_page_url" class="form-control" placeholder="e.g. dashboards/super_admin/manage_pages.php" required>
                    <small class="text-muted">Use '#' for parent menu items with no direct link.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Parent Menu</label>
                    <select name="parent_id" id="modal_parent_id" class="form-select">
                        <option value="0">None (Root)</option>
                        <?php foreach($parents as $pt): ?>
                            <option value="<?= $pt['id'] ?>"><?= str_repeat('&nbsp;&nbsp;&nbsp;', $pt['level']) . htmlspecialchars($pt['page_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Icon Class (Bootstrap)</label>
                    <input type="text" name="icon_class" id="modal_icon_class" class="form-control" placeholder="bi bi-circle">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(data = null) {
    const modal = new bootstrap.Modal(document.getElementById('pageModal'));
    if (data) {
        document.getElementById('modalTitle').innerText = 'Edit Page: ' + data.page_name;
        document.getElementById('modal_page_id').value = data.id;
        document.getElementById('modal_page_name').value = data.page_name;
        document.getElementById('modal_page_url').value = data.page_url;
        document.getElementById('modal_parent_id').value = data.parent_id;
        document.getElementById('modal_icon_class').value = data.icon_class;
    } else {
        document.getElementById('modalTitle').innerText = 'Add New Page';
        document.getElementById('modal_page_id').value = '';
        document.getElementById('modal_page_name').value = '';
        document.getElementById('modal_page_url').value = '';
        document.getElementById('modal_parent_id').value = '0';
        document.getElementById('modal_icon_class').value = 'bi bi-circle';
    }
    modal.show();
}

// Handle Access Toggling via AJAX
document.querySelectorAll('.access-toggle').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const pageId = this.dataset.pageId;
        const roleKey = this.dataset.roleKey;
        const checked = this.checked ? 1 : 0;

        fetch('manage_pages.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `toggle_access=1&page_id=${pageId}&role_key=${roleKey}&checked=${checked}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') {
                alert('Failed to update access.');
                this.checked = !this.checked; // Revert
            }
        })
        .catch(err => {
            console.error(err);
            alert('An error occurred.');
            this.checked = !this.checked; // Revert
        });
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
