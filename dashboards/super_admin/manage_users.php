<?php 
require_once '../../includes/header.php'; 

// Fetch Roles for Dropdown
$roles = $pdo->query("SELECT * FROM sys_roles")->fetchAll();

$success = '';
$error = '';

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        if ($action === 'add_user') {
            $name = $_POST['name'];
            $email = $_POST['email'];
            $role = $_POST['role'];
            $identity_no = $_POST['identity_no'] ?? null;
            $registration_no = $_POST['registration_no'] ?? null;
            $department = $_POST['department'] ?? 'General';
            $phone = $_POST['phone'] ?? null;
            $borrow_limit = $_POST['borrow_limit'] ?? 5;
            $pass = password_hash('123456', PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (name, email, role, password, identity_no, registration_no, department, phone, borrow_limit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $role, $pass, $identity_no, $registration_no, $department, $phone, $borrow_limit]);
            $success = "User added successfully with default password '123456'.";
        } 
        elseif ($action === 'edit_user') {
            $id = $_POST['user_id'];
            $name = $_POST['name'];
            $email = $_POST['email'];
            $role = $_POST['role'];
            $identity_no = $_POST['identity_no'];
            $registration_no = $_POST['registration_no'];
            $department = $_POST['department'];
            $phone = $_POST['phone'];
            $borrow_limit = $_POST['borrow_limit'];
            
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, identity_no = ?, registration_no = ?, department = ?, phone = ?, borrow_limit = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $identity_no, $registration_no, $department, $phone, $borrow_limit, $id]);
            $success = "User updated successfully.";
        } 
        elseif ($action === 'delete_user') {
            $id = $_POST['user_id'];
            // Protection: don't delete yourself
            if ($id == $_SESSION['user_id']) {
                throw new Exception("You cannot delete your own account.");
            }
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $success = "User deleted successfully.";
        } 
        elseif ($action === 'toggle_status') {
            $id = $_POST['user_id'];
            $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            $success = "User status toggled.";
        } 
        elseif ($action === 'reset_password') {
            $id = $_POST['user_id'];
            $pass = password_hash('123456', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$pass, $id]);
            $success = "Password reset to '123456'.";
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="container-fluid px-4 mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h1 class="h3 mb-0 text-gray-800">User Management</h1>
            <p class="text-muted small">Manage system users, roles, and access settings.</p>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus-fill me-2"></i>Create New User
            </button>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase fw-bold">
                        <tr>
                            <th class="ps-4">User Details</th>
                            <th>Role & Dept</th>
                            <th>Contact Info</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $users = $pdo->query("SELECT u.*, r.role_name FROM users u JOIN sys_roles r ON u.role = r.role_key ORDER BY u.created_at DESC");
                        while($u = $users->fetch()):
                            $badgeClass = getRoleBadgeColor($u['role_name']); 
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3 bg-<?= str_contains($badgeClass, 'danger') ? 'danger' : (str_contains($badgeClass, 'warning') ? 'warning' : 'primary') ?>-subtle text-<?= str_contains($badgeClass, 'danger') ? 'danger' : (str_contains($badgeClass, 'warning') ? 'warning' : 'primary') ?>">
                                        <?= strtoupper(substr($u['name'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($u['name']) ?></div>
                                        <div class="text-muted small">Reg: <?= htmlspecialchars($u['registration_no'] ?? 'N/A') ?> | ID: <?= htmlspecialchars($u['identity_no'] ?? 'N/A') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $badgeClass ?> mb-1 d-inline-block"><?= htmlspecialchars($u['role_name']) ?></span>
                                <div class="small text-muted"><?= htmlspecialchars($u['department']) ?></div>
                            </td>
                            <td>
                                <div class="small"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($u['email']) ?></div>
                                <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($u['phone'] ?? '---') ?></div>
                            </td>
                            <td>
                                <?php if ($u['is_active']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2">
                                        <i class="bi bi-check-circle me-1"></i>Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2">
                                        <i class="bi bi-slash-circle me-1"></i>Suspended
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><a class="dropdown-item edit-user-btn" href="#" 
                                               data-user='<?= json_encode($u) ?>' 
                                               data-bs-toggle="modal" data-bs-target="#editUserModal">
                                            <i class="bi bi-pencil me-2 text-primary"></i>Edit Profile</a></li>
                                        
                                        <li><form method="POST" class="d-inline" onsubmit="return confirm('Reset this users password to 123456?');">
                                            <input type="hidden" name="action" value="reset_password">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="dropdown-item">
                                                <i class="bi bi-shield-lock me-2 text-warning"></i>Reset Password
                                            </button>
                                        </form></li>

                                        <li><form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="dropdown-item">
                                                <i class="bi bi-power me-2 text-info"></i><?= $u['is_active'] ? 'Suspend' : 'Activate' ?> Account
                                            </button>
                                        </form></li>
                                        
                                        <li><hr class="dropdown-divider"></li>
                                        
                                        <li><form method="POST" class="d-inline" onsubmit="return confirm('Are you absolutely sure you want to delete this user? This cannot be undone.');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-trash me-2"></i>Delete User Account
                                            </button>
                                        </form></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="action" value="add_user">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Register New System User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="email@example.com" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Identity Number / ID Card</label>
                        <input type="text" name="identity_no" class="form-control" placeholder="CNIC or Student ID">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Registration Number</label>
                        <input type="text" name="registration_no" class="form-control" placeholder="University Reg No">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Role Assignment</label>
                        <select name="role" class="form-select" required>
                            <?php foreach($roles as $r): ?>
                                <option value="<?= $r['role_key'] ?>"><?= $r['role_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Department / Section</label>
                        <input type="text" name="department" class="form-control" placeholder="e.g. Computer Science">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="+92 XXX XXXXXXX">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Borrow Limit (Books)</label>
                        <input type="number" name="borrow_limit" class="form-control" value="5" min="1">
                    </div>
                </div>
                <div class="mt-3 text-muted small italic">
                    <i class="bi bi-info-circle me-1"></i> New users are assigned a default password: <strong>123456</strong>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Register User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Update User Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Identity Number / ID Card</label>
                        <input type="text" name="identity_no" id="edit_identity_no" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Registration Number</label>
                        <input type="text" name="registration_no" id="edit_registration_no" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Role Assignment</label>
                        <select name="role" id="edit_role" class="form-select" required>
                            <?php foreach($roles as $r): ?>
                                <option value="<?= $r['role_key'] ?>"><?= $r['role_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Department / Section</label>
                        <input type="text" name="department" id="edit_department" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Phone Number</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Borrow Limit (Books)</label>
                        <input type="number" name="borrow_limit" id="edit_borrow_limit" class="form-control" min="1">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-dark px-4">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<style>
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
    }
    .btn-icon {
        padding: 0.25rem 0.5rem;
        background: transparent;
        border: none;
        color: #6c757d;
    }
    .btn-icon:hover {
        background: #f8fafc;
        color: #000;
    }
</style>

<script>
document.querySelectorAll('.edit-user-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const u = JSON.parse(this.dataset.user);
        document.getElementById('edit_user_id').value = u.id;
        document.getElementById('edit_name').value = u.name;
        document.getElementById('edit_email').value = u.email;
        document.getElementById('edit_identity_no').value = u.identity_no;
        document.getElementById('edit_registration_no').value = u.registration_no;
        document.getElementById('edit_role').value = u.role;
        document.getElementById('edit_department').value = u.department;
        document.getElementById('edit_phone').value = u.phone;
        document.getElementById('edit_borrow_limit').value = u.borrow_limit;
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>