<?php
require_once 'core/db.php';

try {
    $pdo->beginTransaction();

    // 1. Identify the Parent Page (Student)
    $parentPageName = 'Student';
    $stmt = $pdo->prepare("SELECT id FROM sys_pages WHERE page_name = ? AND page_url = '#'");
    $stmt->execute([$parentPageName]);
    $parent = $stmt->fetch();

    if (!$parent) {
        throw new Exception("Parent page 'Student' not found.");
    }
    $parentId = $parent['id'];
    echo "Parent ID identified: $parentId\n";

    // 2. Update Parent Icon and Style
    $stmt = $pdo->prepare("UPDATE sys_pages SET icon_class = 'bi bi-person-badge', sort_order = 50 WHERE id = ?");
    $stmt->execute([$parentId]);
    echo "Updated parent icon and sort order.\n";

    // 3. Move Child Pages under the Parent
    // IDs identified: 11 (Dashboard), 29 (Reservations), 30 (Fines), 31 (History)
    $childUrls = [
        'dashboards/student/student_dashboard.php',
        'dashboards/student/reservations.php',
        'dashboards/student/fines.php',
        'dashboards/student/history.php'
    ];

    $placeholders = implode(',', array_fill(0, count($childUrls), '?'));
    $stmt = $pdo->prepare("UPDATE sys_pages SET parent_id = ? WHERE page_url IN ($placeholders)");
    $stmt->execute(array_merge([$parentId], $childUrls));
    echo "Moved student pages under the parent menu.\n";

    // 4. Update Student Dashboard Icon specifically
    $stmt = $pdo->prepare("UPDATE sys_pages SET icon_class = 'bi bi-speedometer2' WHERE page_url = 'dashboards/student/student_dashboard.php'");
    $stmt->execute();
    echo "Updated Student Dashboard icon.\n";

    // 5. Ensure Role Access for Parent Page
    // Grant access to super_admin and student roles
    $rolesToGrant = ['super_admin', 'student', 'librarian'];
    foreach ($rolesToGrant as $role) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES (?, ?)");
        $stmt->execute([$role, $parentId]);
    }
    echo "Verified role access for parent menu.\n";

    $pdo->commit();
    echo "SUCCESS: All changes applied to the database.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
}
