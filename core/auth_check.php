<?php
/**
 * auth_check.php
 * Secure session validator for Librarian ERP.
 * Include at the very top of restricted pages.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Gate: Check if user is logged in AND has the required librarian/admin role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['librarian', 'super_admin'])) {
    // Immediate termination and redirect for unauthorized access
    header("Location: ../../login.php?error=unauthorized");
    exit();
}
?>
