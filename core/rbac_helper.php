<?php
/**
 * rbac_helper.php
 * Role-Based Access Control configurations and checks.
 */

/**
 * Checks if the current user has permission for a specific action.
 * 
 * @param string $role User role (e.g., 'librarian', 'assistant_manager')
 * @param string $action Action/Feature to check (e.g., 'view_audit', 'delete_record')
 * @return bool
 */
function can($action) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $role = $_SESSION['role'] ?? '';
    
    // Librarian and Super Admin have full access
    if (in_array($role, ['librarian', 'super_admin'])) {
        return true;
    }
    
    // Assistant Manager restrictions
    if ($role === 'assistant_manager') {
        $restrictedActions = ['view_audit', 'delete_record'];
        if (in_array($action, $restrictedActions)) {
            return false;
        }
        return true; 
    }
    
    return false;
}

/**
 * Enforce RBAC on a page.
 */
function restrictTo($action) {
    if (!can($action)) {
        header("Location: ../../dashboards/librarian/librarian_dashboard.php?error=access_denied");
        exit();
    }
}
?>
