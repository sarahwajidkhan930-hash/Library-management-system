<aside class="app-sidebar bg-body-secondary shadow">
    <div class="sidebar-brand d-flex align-items-center justify-content-between px-3">
        <a href="<?= BASE_URL ?>index.php" class="brand-link flex-grow-1">
            <img src="<?= $settings['system_logo'] ?>" alt="Logo" class="brand-image opacity-75 shadow">
            <span class="brand-text fw-light"><?= $settings['system_name'] ?></span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">

                <?php
                /**
                 * Recursively checks if a menu item or any of its children matches the current page.
                 */
                function isMenuPageActive($pdo, $pageId, $currentBasename, $userRole)
                {
                    // Fetch current menu item's URL
                    $stmt = $pdo->prepare("SELECT page_url FROM sys_pages WHERE id = ?");
                    $stmt->execute([$pageId]);
                    $page = $stmt->fetch();

                    // If URL matches current basename, it's active
                    if ($page && $page['page_url'] !== '#' && basename($page['page_url']) === $currentBasename) {
                        return true;
                    }

                    // Check children recursively
                    $sql = "
                        SELECT p.id FROM sys_pages p
                        JOIN role_access ra ON p.id = ra.page_id
                        WHERE p.parent_id = ? AND ra.role_key = ?
                    ";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$pageId, $userRole]);
                    $children = $stmt->fetchAll();

                    foreach ($children as $child) {
                        if (isMenuPageActive($pdo, $child['id'], $currentBasename, $userRole)) {
                            return true;
                        }
                    }

                    return false;
                }

                function buildMenu($pdo, $parentId = 0, $userRole, $currentBasename, $skipIds = [])
                {
                    // Fetch pages visible to this role
                    $sql = "
                        SELECT p.* FROM sys_pages p
                        JOIN role_access ra ON p.id = ra.page_id
                        WHERE p.parent_id = ? AND ra.role_key = ?
                        ORDER BY p.sort_order ASC
                    ";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$parentId, $userRole]);
                    $items = $stmt->fetchAll();

                    foreach ($items as $item) {
                        // Skip specific pages (like the standalone Dashboard)
                        if (in_array($item['id'], $skipIds)) continue;

                        // Check children
                        $childStmt = $pdo->prepare("SELECT COUNT(*) FROM sys_pages WHERE parent_id = ?");
                        $childStmt->execute([$item['id']]);
                        $hasChildren = $childStmt->fetchColumn() > 0;

                        // Advanced Active State Detection
                        $isActive = isMenuPageActive($pdo, $item['id'], $currentBasename, $userRole);
                        $menuOpen = ($hasChildren && $isActive) ? 'menu-open' : '';
                        $activeClass = $isActive ? 'active' : '';

                        echo '<li class="nav-item ' . $menuOpen . '">';
                        echo '<a href="' . ($hasChildren ? '#' : BASE_URL . $item['page_url']) . '" class="nav-link ' . $activeClass . '">';
                        echo '<i class="nav-icon ' . $item['icon_class'] . '"></i>';
                        echo '<p>' . htmlspecialchars($item['page_name']);
                        if ($hasChildren) {
                            echo '<i class="nav-arrow bi bi-chevron-right"></i>';
                        }
                        echo '</p></a>';

                        if ($hasChildren) {
                            echo '<ul class="nav nav-treeview">';
                            buildMenu($pdo, $item['id'], $userRole, $currentBasename, $skipIds);
                            echo '</ul>';
                        }
                        echo '</li>';
                    }
                }

                // Get current basename and role
                $currentBasename = basename($_SERVER['PHP_SELF']);
                $userRole = $_SESSION['role'] ?? '';

                // Render Dynamic Menu
                buildMenu($pdo, 0, $userRole, $currentBasename);
                ?>

            </ul>
        </nav>
    </div>
</aside>


<script>
/**
 * Safe Sidebar Persistence
 * Saves the sidebar state without interfering with AdminLTE's native toggle logic.
 */
document.addEventListener('DOMContentLoaded', function() {
    const body = document.body;
    const savedState = localStorage.getItem('sidebar-state');
    if (savedState === 'collapsed') {
        body.classList.add('sidebar-collapse');
    }
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === "class") {
                const isCollapsed = body.classList.contains('sidebar-collapse');
                localStorage.setItem('sidebar-state', isCollapsed ? 'collapsed' : 'expanded');
            }
        });
    });
    observer.observe(body, { attributes: true });
});
</script>
