<?php 
require_once '../../includes/header.php'; 

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_role'])) {
    $rName = trim($_POST['role_name']);
    $rKey = strtolower(str_replace(' ', '_', $rName)); // Auto-gen key
    $stmt = $pdo->prepare("INSERT INTO sys_roles (role_name, role_key) VALUES (?, ?)");
    try {
        $stmt->execute([$rName, $rKey]);
        echo "<script>window.location.href='manage_roles.php';</script>";
    } catch(Exception $e) { $error = "Role Key exists."; }
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $rId = (int)$_POST['role_id'];
    $rName = trim($_POST['role_name']);
    $stmt = $pdo->prepare("UPDATE sys_roles SET role_name = ? WHERE id = ?");
    try {
        $stmt->execute([$rName, $rId]);
        echo "<script>window.location.href='manage_roles.php';</script>";
    } catch(Exception $e) { $error = "Failed to update role."; }
}

// Handle Delete (with User Migration Check)
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Check if system role
    $check = $pdo->prepare("SELECT role_key, is_system_role FROM sys_roles WHERE id = ?");
    $check->execute([$id]);
    $roleData = $check->fetch();

    if ($roleData['is_system_role'] == 1) {
        $error = "Cannot delete a system protected role.";
    } else {
        // Migrate users to 'suspended'
        $pdo->prepare("UPDATE users SET role = 'suspended', is_active = 0 WHERE role = ?")->execute([$roleData['role_key']]);
        // Delete Access
        $pdo->prepare("DELETE FROM role_access WHERE role_key = ?")->execute([$roleData['role_key']]);
        // Delete Role
        $pdo->prepare("DELETE FROM sys_roles WHERE id = ?")->execute([$id]);
        echo "<script>window.location.href='manage_roles.php';</script>";
    }
}
?>

<div class="card card-primary card-outline mb-4">
    <div class="card-header">
        <h3 class="card-title">Role Management</h3>
    </div>
    <div class="card-body">
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        
        <form method="POST" class="row g-3 mb-4 align-items-end">
            <div class="col-md-4">
                <label class="form-label">New Role Name</label>
                <input type="text" name="role_name" class="form-control" required placeholder="e.g. Librarian">
            </div>
            <div class="col-md-2">
                <button type="submit" name="create_role" class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Create</button>
            </div>
        </form>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Role Name</th>
                    <th>Key</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM sys_roles");
                while($row = $stmt->fetch()):
                    $badge = $row['is_system_role'] ? '<span class="badge text-bg-warning">System</span>' : '<span class="badge text-bg-success">Custom</span>';
                ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['role_name']) ?></td>
                    <td><code><?= $row['role_key'] ?></code></td>
                    <td><?= $badge ?></td>
                    <td>
                        <button class="btn btn-sm btn-info text-white me-1" onclick='openEditModal(<?= json_encode($row) ?>)'>
                            <i class="bi bi-pencil-square"></i> Edit
                        </button>
                        <?php if(!$row['is_system_role']): ?>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Users with this role will be suspended. Continue?')">
                            <i class="bi bi-trash"></i> Delete
                        </a>
                        <?php else: ?>
                            <span class="text-muted fst-italic ms-2">Protected from deletion</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="role_id" id="edit_role_id">
            <input type="hidden" name="update_role" value="1">
            <div class="modal-header">
                <h5 class="modal-title">Edit Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Role Name</label>
                    <input type="text" name="role_name" id="edit_role_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Role Key (Cannot be changed)</label>
                    <input type="text" id="edit_role_key" class="form-control" disabled>
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
function openEditModal(roleData) {
    document.getElementById('edit_role_id').value = roleData.id;
    document.getElementById('edit_role_name').value = roleData.role_name;
    document.getElementById('edit_role_key').value = roleData.role_key;
    new bootstrap.Modal(document.getElementById('editRoleModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>