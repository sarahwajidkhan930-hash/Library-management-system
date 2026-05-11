<?php
require_once __DIR__ . '/../core/session.php';

// 1. Fetch System Settings
$settings = [];
$stmt = $pdo->query("SELECT * FROM system_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// 2. Identify Current Page & Security Check
$current_url = substr($_SERVER['SCRIPT_NAME'], strlen('/universal/')); // Adjust offset
// Clean URL for DB matching (assuming DB stores relative paths)
$db_url_match = $current_url; 
// If your script is in a folder, the DB url should match "dashboards/super_admin/file.php"

// Fetch Page Info
$pageStmt = $pdo->prepare("SELECT * FROM sys_pages WHERE page_url LIKE ? LIMIT 1");
$pageStmt->execute(["%$current_url%"]); 
$currentPageData = $pageStmt->fetch();

$pageTitle = $currentPageData['page_name'] ?? 'Dashboard';
$pageId = $currentPageData['id'] ?? 0;

// 3. Security Access Check (The Gatekeeper)
if ($pageId > 0 && $_SESSION['role'] !== 'super_admin') {
    $accessStmt = $pdo->prepare("SELECT * FROM role_access WHERE role_key = ? AND page_id = ?");
    $accessStmt->execute([$_SESSION['role'], $pageId]);
    if ($accessStmt->rowCount() == 0) {
        die('<div class="alert alert-danger m-5">⛔ Access Denied: You do not have permission to view this page.</div>');
    }
}

// 4. Breadcrumb Logic (Recursive Upwards)
$breadcrumbs = [];
if ($currentPageData && isset($currentPageData['id'])) {
    $crumbId = $currentPageData['id'];
    $safety_counter = 0;
    while($crumbId != 0 && $safety_counter < 10) {
        $safety_counter++;
        $crumbStmt = $pdo->prepare("SELECT id, parent_id, page_name, page_url FROM sys_pages WHERE id = ?");
        $crumbStmt->execute([$crumbId]);
        $crumb = $crumbStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($crumb && is_array($crumb)) {
            array_unshift($breadcrumbs, $crumb);
            $crumbId = (int)$crumb['parent_id'];
        } else {
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars($settings['system_name']) ?></title>
    
    <script>
        // Immediately check local storage to prevent "White Flash"
        const storedTheme = localStorage.getItem('theme');
        if (storedTheme) {
            document.documentElement.setAttribute('data-bs-theme', storedTheme);
        } else {
            // Default to system preference if no choice made
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-bs-theme', systemTheme);
        }
    </script>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/adminlte.min.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/theme.css?v=8.0" />
    
    <?php
    // Fetch Notifications for Header
    require_once __DIR__ . '/../core/library_functions.php';
    $lib = new Library($pdo);
    $unreadNotifs = $lib->getUnreadNotifications($_SESSION['user_id']);
    $unreadCount = count($unreadNotifs);
    ?>
    
    <style> 
        .app-brand-logo { height: 30px; width: auto; } 
        .user-image { width: 30px; height: 30px; object-fit: cover; }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item"> <a class="nav-link" id="sidebar-toggle-btn" data-lte-toggle="sidebar" href="#" role="button"><i class="bi bi-list"></i></a> </li>

                <li class="nav-item d-none d-md-block"> <a href="#" class="nav-link"><?= $pageTitle ?></a> </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                 <li class="nav-item">
                    <button class="btn btn-link nav-link" id="theme-toggle" type="button">
                        <i class="bi bi-sun-fill" id="theme-icon"></i>
                    </button>
                </li>
                <!-- Notifications Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                        <?php if($unreadCount > 0): ?>
                            <span class="navbar-badge badge text-bg-danger" style="font-size: 0.6rem; padding: 2px 4px;"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end shadow border-0" style="border-radius: 12px; width: 320px;">
                        <span class="dropdown-item dropdown-header fw-bold"><?= $unreadCount ?> Notifications</span>
                        <div class="dropdown-divider"></div>
                        
                        <div id="notification-list" style="max-height: 300px; overflow-y: auto;">
                        <?php if($unreadCount == 0): ?>
                            <div class="dropdown-item text-center py-3 text-muted small">No new notifications</div>
                        <?php else: foreach($unreadNotifs as $n): ?>
                            <a href="#" class="dropdown-item p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 bg-<?= $n['type'] ?> bg-opacity-10 text-<?= $n['type'] ?> rounded-circle p-2 me-3">
                                        <i class="bi bi-info-circle"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-0 fw-bold small text-dark"><?= htmlspecialchars($n['title']) ?></p>
                                        <p class="mb-0 text-muted extra-small"><?= htmlspecialchars($n['message']) ?></p>
                                        <p class="mb-0 extra-small text-secondary mt-1 opacity-75"><?= date('h:i A', strtotime($n['created_at'])) ?></p>
                                    </div>
                                </div>
                            </a>
                            <div class="dropdown-divider"></div>
                        <?php endforeach; endif; ?>
                        </div>

                        <div class="dropdown-divider"></div>
                        <a href="#" id="mark-notifications-read" class="dropdown-item dropdown-footer small text-primary fw-bold text-center py-2">Mark all as read</a>
                    </div>
                </li>
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                        <img src="<?= !empty($_SESSION['avatar']) ? $_SESSION['avatar'] : BASE_URL.'assets/img/avatar.png' ?>" class="user-image rounded-circle shadow" alt="User Image">
                        <span class="d-none d-md-inline ms-1"><?= htmlspecialchars($_SESSION['name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                        <li class="user-header text-bg-primary">
                            <img src="<?= !empty($_SESSION['avatar']) ? $_SESSION['avatar'] : BASE_URL.'assets/img/avatar.png' ?>" class="rounded-circle shadow" alt="User Image">
                            <p>
                                <?= htmlspecialchars($_SESSION['name']) ?>
                                <small><?= ucfirst(str_replace('_', ' ', $_SESSION['role'])) ?></small>
                            </p>
                        </li>
                        <li class="user-footer"> 
                            <a href="<?= BASE_URL ?>profile.php" class="btn btn-default btn-flat">Profile</a>
                            <a href="<?= BASE_URL ?>logout.php" class="btn btn-default btn-flat float-end">Sign out</a> 
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    
    <?php include 'sidebar.php'; ?>
    
    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6"><h3 class="mb-0"><?= $pageTitle ?></h3></div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
                            <?php foreach($breadcrumbs as $b): if($b): ?>
                                <li class="breadcrumb-item <?= ($b['id'] == $pageId) ? 'active' : '' ?>">
                                    <?= htmlspecialchars($b['page_name']) ?>
                                </li>
                            <?php endif; endforeach; ?>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const badge = document.querySelector('.navbar-badge');
        const list = document.getElementById('notification-list');
        const markReadBtn = document.getElementById('mark-notifications-read');

        function pollNotifications() {
            fetch('<?= BASE_URL ?>core/ajax_notifications_poll.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Update Badge
                        if (data.count > 0) {
                            if (badge) {
                                badge.innerText = data.count;
                                badge.classList.remove('d-none');
                            } else {
                                // Create badge if it didn't exist
                                const bell = document.querySelector('.bi-bell');
                                const newBadge = document.createElement('span');
                                newBadge.className = 'navbar-badge badge text-bg-danger';
                                newBadge.style.cssText = 'font-size: 0.6rem; padding: 2px 4px;';
                                newBadge.innerText = data.count;
                                bell.after(newBadge);
                            }
                        } else if (badge) {
                            badge.classList.add('d-none');
                        }

                        // Update List
                        if (list) list.innerHTML = data.html;
                    }
                });
        }

        if (markReadBtn) {
            markReadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const formData = new FormData();
                formData.append('action', 'mark_read');
                
                fetch('<?= BASE_URL ?>dashboards/student/ajax_student_actions.php', { // Reusing existing action handler
                    method: 'POST',
                    body: (function(){
                        const fd = new FormData();
                        fd.append('action', 'mark_notifications_read'); // Update: matches method name or map accordingly
                        return fd;
                    })()
                }); // Note: We should probably have a more global handler but student actions work for now
                
                // For now, let's just trigger the hide
                if (badge) badge.classList.add('d-none');
                if (list) list.innerHTML = '<div class="dropdown-item text-center py-3 text-muted small">No new notifications</div>';
            });
        }

        // Poll every 30 seconds
        setInterval(pollNotifications, 30000);
    });
    </script>