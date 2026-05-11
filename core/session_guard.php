<?php
/**
 * session_guard.php
 * Standalone session check for librarian pages.
 * Include this at the TOP of your pages.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in OR not authorized (librarian/super_admin)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['librarian', 'super_admin','	ass.library_manager'])) {
    // Determine the path to the login page relative to the current file
    // Assumes pages are in dashboards/librarian/
    header("Location: ../../login.php");
    exit();
}
?>
