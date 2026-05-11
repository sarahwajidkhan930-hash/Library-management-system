<?php
/**
 * Professional Sidebar Reorganization Script
 * ============================================
 * Restructures all sys_pages and role_access to create a clean,
 * professional, role-based sidebar navigation hierarchy.
 *
 * Final Structure:
 * - Library Dashboard        (sort:1)  [librarian, super_admin, assistant_manager]
 * - System Management        (sort:10) [super_admin only] GROUP
 *     |- Manage Users
 *     |- Manage Roles
 *     |- Manage Pages
 * - Library Operations       (sort:20) [librarian, super_admin, assistant_manager] GROUP (NEW)
 *     |- Register Book
 *     |- Inventory Management
 *     |- Book Categories
 * - Circulation              (sort:30) [librarian, super_admin] GROUP (NEW)
 *     |- Circulation Logs
 * - Student                  (sort:40) [librarian, super_admin, student] GROUP (existing ID 35)
 *     |- Student Dashboard   [student, super_admin, assistant_manager]
 *     |- Student Directory   [librarian, super_admin]
 *     |- My Reservations     [student, super_admin]
 *     |- Fine Payments       [student, super_admin]
 *     |- Reading History     [student, super_admin]
 * - Digital Audit Trail      (sort:50) [librarian, super_admin]
 */

require_once 'core/db.php';

try {
    $pdo->beginTransaction();

    // =========================================================================
    // STEP 1: Create "Library Operations" parent group
    // =========================================================================
    $stmt = $pdo->prepare("SELECT id FROM sys_pages WHERE page_name = 'Library Operations' AND page_url = '#'");
    $stmt->execute();
    $libOps = $stmt->fetch();

    if (!$libOps) {
        $stmt = $pdo->prepare("INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) VALUES (0, 'Library Operations', '#', 'bi bi-book-fill', 20)");
        $stmt->execute();
        $libOpsId = $pdo->lastInsertId();
        echo "[OK] Created 'Library Operations' group with ID $libOpsId\n";
    } else {
        $libOpsId = $libOps['id'];
        $pdo->prepare("UPDATE sys_pages SET icon_class='bi bi-book-fill', sort_order=20 WHERE id=?")->execute([$libOpsId]);
        echo "[OK] 'Library Operations' group already exists with ID $libOpsId\n";
    }

    // =========================================================================
    // STEP 2: Create "Circulation" parent group
    // =========================================================================
    $stmt = $pdo->prepare("SELECT id FROM sys_pages WHERE page_name = 'Circulation' AND page_url = '#'");
    $stmt->execute();
    $circ = $stmt->fetch();

    if (!$circ) {
        $stmt = $pdo->prepare("INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) VALUES (0, 'Circulation', '#', 'bi bi-arrow-left-right', 30)");
        $stmt->execute();
        $circId = $pdo->lastInsertId();
        echo "[OK] Created 'Circulation' group with ID $circId\n";
    } else {
        $circId = $circ['id'];
        $pdo->prepare("UPDATE sys_pages SET icon_class='bi bi-arrow-left-right', sort_order=30 WHERE id=?")->execute([$circId]);
        echo "[OK] 'Circulation' group already exists with ID $circId\n";
    }

    // Student group ID (already created = ID 35)
    $studentGroupId = 35;

    // =========================================================================
    // STEP 3: Update all ROOT-LEVEL pages (parent_id=0) with proper sort orders
    // =========================================================================
    $rootUpdates = [
        // ID => [sort_order, icon_class, page_name (for reference)]
        12 => [1,  'bi bi-speedometer2',       'Library Dashboard'],
        2  => [10, 'bi bi-gear-fill',           'System Management'],
        27 => [50, 'bi bi-shield-check',        'Digital Audit Trail'],
        35 => [40, 'bi bi-person-badge',        'Student'],
    ];

    foreach ($rootUpdates as $id => $data) {
        $pdo->prepare("UPDATE sys_pages SET sort_order=?, icon_class=?, parent_id=0 WHERE id=?")
            ->execute([$data[0], $data[1], $id]);
        echo "[OK] Updated root page ID $id '{$data[2]}' -> sort={$data[0]}, icon={$data[1]}\n";
    }
    // Also update the new groups
    $pdo->prepare("UPDATE sys_pages SET parent_id=0, sort_order=20 WHERE id=?")->execute([$libOpsId]);
    $pdo->prepare("UPDATE sys_pages SET parent_id=0, sort_order=30 WHERE id=?")->execute([$circId]);

    // =========================================================================
    // STEP 4: Move pages into proper parent groups with sort orders
    // =========================================================================

    // --- Under System Management (ID 2) ---
    $systemMgmtChildren = [
        3 => [1, 'bi bi-people',            'Manage Users'],
        4 => [2, 'bi bi-shield-lock',       'Manage Roles'],
        5 => [3, 'bi bi-file-earmark-text', 'Manage Pages'],
    ];
    foreach ($systemMgmtChildren as $id => $data) {
        $pdo->prepare("UPDATE sys_pages SET parent_id=2, sort_order=?, icon_class=? WHERE id=?")
            ->execute([$data[0], $data[1], $id]);
        echo "[OK] '{$data[2]}' -> parent=System Management, sort={$data[0]}\n";
    }

    // --- Under Library Operations (new group) ---
    $libOpsChildren = [
        20 => [1, 'bi bi-journal-plus', 'Register Book'],
        21 => [2, 'bi bi-stack',        'Inventory Management'],
        28 => [3, 'bi bi-tags-fill',    'Book Categories'],
    ];
    foreach ($libOpsChildren as $id => $data) {
        $pdo->prepare("UPDATE sys_pages SET parent_id=?, sort_order=?, icon_class=? WHERE id=?")
            ->execute([$libOpsId, $data[0], $data[1], $id]);
        echo "[OK] '{$data[2]}' -> parent=Library Operations, sort={$data[0]}\n";
    }

    // --- Under Circulation (new group) ---
    $circChildren = [
        22 => [1, 'bi bi-arrow-left-right', 'Circulation Logs'],
    ];
    foreach ($circChildren as $id => $data) {
        $pdo->prepare("UPDATE sys_pages SET parent_id=?, sort_order=?, icon_class=? WHERE id=?")
            ->execute([$circId, $data[0], $data[1], $id]);
        echo "[OK] '{$data[2]}' -> parent=Circulation, sort={$data[0]}\n";
    }

    // --- Under Student (ID 35) ---
    $studentChildren = [
        6  => [1, 'bi bi-speedometer2',   'Student Dashboard'],
        18 => [2, 'bi bi-people-fill',    'Student Directory'],
        29 => [3, 'bi bi-calendar-check', 'My Reservations'],
        30 => [4, 'bi bi-credit-card',    'Fine Payments'],
        31 => [5, 'bi bi-journal-text',   'Reading History'],
    ];
    foreach ($studentChildren as $id => $data) {
        $pdo->prepare("UPDATE sys_pages SET parent_id=?, sort_order=?, icon_class=? WHERE id=?")
            ->execute([$studentGroupId, $data[0], $data[1], $id]);
        echo "[OK] '{$data[2]}' -> parent=Student, sort={$data[0]}\n";
    }

    // =========================================================================
    // STEP 5: Set up Role Access for all groups and pages
    // =========================================================================

    // Wipe and rebuild role_access cleanly for a fresh, correct state
    // We'll use INSERT IGNORE to avoid duplicates

    // Helper: grant access
    $grant = function($role, $pageId) use ($pdo) {
        $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES (?, ?)")
            ->execute([$role, $pageId]);
    };
    // Helper: revoke access
    $revoke = function($role, $pageId) use ($pdo) {
        $pdo->prepare("DELETE FROM role_access WHERE role_key=? AND page_id=?")
            ->execute([$role, $pageId]);
    };

    // -- Library Dashboard (ID 12) --
    $grant('super_admin',        12);
    $grant('librarian',          12);
    $grant('assistant_manager',  12);

    // -- System Management (ID 2) and children (IDs 3, 4, 5) --
    // Only super_admin sees System Management
    $grant('super_admin', 2);
    $grant('super_admin', 3);
    $grant('super_admin', 4);
    $grant('super_admin', 5);
    $revoke('librarian', 2); $revoke('librarian', 3); $revoke('librarian', 4); $revoke('librarian', 5);
    $revoke('student',   2); $revoke('student',   3); $revoke('student',   4); $revoke('student',   5);

    // -- Library Operations group + children --
    $grant('super_admin',       $libOpsId);
    $grant('librarian',         $libOpsId);
    $grant('assistant_manager', $libOpsId);
    foreach ([20, 21, 28] as $pid) {
        $grant('super_admin',       $pid);
        $grant('librarian',         $pid);
        $grant('assistant_manager', $pid);
        $revoke('student', $pid);
    }

    // -- Circulation group + children --
    $grant('super_admin', $circId);
    $grant('librarian',   $circId);
    foreach ([22] as $pid) {
        $grant('super_admin', $pid);
        $grant('librarian',   $pid);
        $revoke('student', $pid);
        $revoke('assistant_manager', $pid);
    }

    // -- Student group (ID 35) --
    $grant('super_admin', $studentGroupId);
    $grant('librarian',   $studentGroupId);
    $grant('student',     $studentGroupId);

    // Student Dashboard (ID 6) - student, super_admin, assistant_manager; NOT regular librarian
    $grant('super_admin',       6);
    $grant('student',           6);
    $grant('assistant_manager', 6);
    $revoke('librarian', 6);

    // Student Directory (ID 18) - librarian, super_admin; NOT student
    $grant('super_admin', 18);
    $grant('librarian',   18);
    $revoke('student', 18);

    // My Reservations (ID 29) - student, super_admin only
    $grant('super_admin', 29);
    $grant('student',     29);
    $revoke('librarian', 29);

    // Fine Payments (ID 30) - student, super_admin only
    $grant('super_admin', 30);
    $grant('student',     30);
    $revoke('librarian', 30);

    // Reading History (ID 31) - student, super_admin only
    $grant('super_admin', 31);
    $grant('student',     31);
    $revoke('librarian', 31);

    // -- Digital Audit Trail (ID 27) - librarian, super_admin --
    $grant('super_admin', 27);
    $grant('librarian',   27);
    $revoke('student', 27);

    $pdo->commit();
    echo "\n============================================\n";
    echo "SUCCESS: Sidebar reorganization complete!\n";
    echo "All data saved to the database.\n";
    echo "============================================\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "All changes have been ROLLED BACK. No data was changed.\n";
}
