<?php 
require_once '../../includes/header.php';

// Security: Super Admin Only
if ($_SESSION['role'] !== 'super_admin') {
    echo '<div class="alert alert-danger m-5">⛔ Access Denied. Super Admin only.</div>';
    include '../../includes/footer.php';
    exit;
}

$message = '';

// Handle Update
if (isset($_POST['update_settings'])) {
    try {
        $pdo->beginTransaction();
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        $pdo->commit();
        $message = '<div class="alert alert-success alert-dismissible fade show shadow-sm border-0"><i class="bi bi-check-circle-fill me-2"></i> System configurations updated successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        
        // Refresh local settings array
        $stmt = $pdo->query("SELECT * FROM system_settings");
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = '<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"><i class="bi bi-exclamation-triangle-fill me-2"></i> Error: ' . $e->getMessage() . ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Group settings for better UI
$groups = [
    'General Information' => ['system_name', 'system_logo', 'contact_email'],
    'Circulation Rules' => ['fine_per_day', 'grace_period_days', 'max_books_per_student', 'default_borrow_duration'],
    'System Appearance' => ['primary_color', 'accent_color']
];
?>

<style>
    :root {
        --settings-primary: #1e293b;
        --settings-bg: #f8fafc;
    }
    body { background-color: var(--settings-bg) !important; }
    .settings-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .group-header {
        background: #f8fafc;
        padding: 1rem 1.5rem;
        border-bottom: 2px solid #e2e8f0;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.1em;
    }
    .form-label { font-weight: 600; color: #334155; font-size: 0.85rem; }
    .form-control:focus { border-color: var(--erp-primary); box-shadow: 0 0 0 3px rgba(var(--erp-primary-rgb), 0.1); }
    .save-btn { background-color: var(--erp-primary); border: none; padding: 0.75rem 2rem; border-radius: 12px; font-weight: 700; color: white; transition: all 0.2s; }
    .save-btn:hover { background-color: #7f1d1d; transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    
    /* Remove Redundant Global Header */
    .app-content-header { display: none !important; }
</style>

<div class="content-header p-4">
    <div class="container">
        <div class="row align-items-center mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small text-uppercase fw-bold">
                        <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-muted">Admin</a></li>
                        <li class="breadcrumb-item active text-primary">System Settings</li>
                    </ol>
                </nav>
                <h1 class="fw-extrabold text-dark m-0"><i class="bi bi-gear-wide-connected me-3 text-primary"></i>System Configuration</h1>
            </div>
        </div>

        <?= $message ?>

        <form action="" method="POST">
            <div class="row">
                <div class="col-lg-8">
                    <?php foreach ($groups as $title => $keys): ?>
                        <div class="settings-card mb-4">
                            <div class="group-header d-flex justify-content-between align-items-center">
                                <span><?= $title ?></span>
                                <i class="bi bi-shield-lock-fill opacity-50"></i>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <?php foreach ($keys as $key): 
                                        $label = ucwords(str_replace('_', ' ', $key));
                                        $value = $settings[$key] ?? '';
                                        $type = (strpos($key, 'color') !== false) ? 'color' : (is_numeric($value) ? 'number' : 'text');
                                    ?>
                                        <div class="col-md-6">
                                            <label class="form-label mb-2"><?= $label ?></label>
                                            <input type="<?= $type ?>" name="settings[<?= $key ?>]" class="form-control py-2 shadow-sm border-light bg-light" value="<?= htmlspecialchars($value) ?>" required>
                                            <div class="form-text small text-muted opacity-75">Config key: <code><?= $key ?></code></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="text-center mt-2 mb-5">
                        <button type="submit" name="update_settings" class="save-btn shadow">
                            <i class="bi bi-cloud-arrow-up-fill me-2"></i>Save Global Configuration
                        </button>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card settings-card border-0 bg-primary text-white p-4 shadow-lg mb-4" style="background: linear-gradient(135deg, var(--erp-primary) 0%, #7f1d1d 100%) !important;">
                        <h4 class="fw-extrabold mb-3"><i class="bi bi-info-circle-fill me-2"></i>Platform Notice</h4>
                        <p class="small opacity-90">These rules are applied in real-time across all library modules including Student Dashboard, Librarian Circulation, and Automated Overdue checks.</p>
                        <hr class="border-white opacity-25">
                        <div class="small">
                            <strong>Current Branding:</strong><br>
                            <?= htmlspecialchars($settings['system_name']) ?>
                        </div>
                    </div>
                    
                    <div class="card settings-card p-4 border-0 shadow-sm text-center">
                        <i class="bi bi-database-check fs-1 text-success mb-3 opacity-25"></i>
                        <h6 class="fw-bold">Database Integrity</h6>
                        <p class="extra-small text-muted mb-0">Configurations are stored in <code>system_settings</code> and protected by transactional integrity.</p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
