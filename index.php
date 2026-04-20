<?php 
require_once 'includes/header.php'; 

// Fetch Counts for Admin
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$roleCount = $pdo->query("SELECT COUNT(*) FROM sys_roles")->fetchColumn();
$pageCount = $pdo->query("SELECT COUNT(*) FROM sys_pages")->fetchColumn();

// Fetch specific data for current user (Example: My Active Permissions)
$myPerms = $pdo->prepare("SELECT COUNT(*) FROM role_access WHERE role_key = ?");
$myPerms->execute([$_SESSION['role']]);
$myPermCount = $myPerms->fetchColumn();
?>

<div class="row">
    <?php if($_SESSION['role'] === 'super_admin'): ?>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h3><?= $userCount ?></h3>
                <p>Total Users</p>
            </div>
            <div class="icon">
                <i class="bi bi-people-fill"></i>
            </div>
            <a href="dashboards/super_admin/manage_users.php" class="small-box-footer">More info <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3><?= $roleCount ?></h3>
                <p>System Roles</p>
            </div>
            <div class="icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <a href="dashboards/super_admin/manage_roles.php" class="small-box-footer">More info <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3><?= $pageCount ?></h3>
                <p>Dynamic Pages</p>
            </div>
            <div class="icon">
                <i class="bi bi-file-earmark-code-fill"></i>
            </div>
            <a href="dashboards/super_admin/manage_pages.php" class="small-box-footer">More info <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    <?php endif; ?>

    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-info">
            <div class="inner">
                <h3><?= $myPermCount ?></h3>
                <p>My Access Privileges</p>
            </div>
            <div class="icon">
                <i class="bi bi-key-fill"></i>
            </div>
            <a href="#" class="small-box-footer">View Access <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
</div>

<div class="card card-outline card-secondary mt-4">
    <div class="card-header">
        <h3 class="card-title">Welcome, <?= htmlspecialchars($_SESSION['name']) ?>!</h3>
    </div>
    <div class="card-body">
        <p>You are logged in as <strong><?= ucfirst($_SESSION['role']) ?></strong>.</p>
        <p class="text-muted">Use the sidebar to navigate the system.</p>
        <a href="profile.php" class="btn btn-primary"><i class="bi bi-person-circle"></i> Update My Profile</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>