<?php
/**
 * login_process.php
 * Secure Authentication Backend for Librarian ERP
 */
require_once 'core/db.php';
require_once 'core/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new Auth($pdo);
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];

    // 1. Authenticate against database (uses password_verify internally)
    if ($auth->login($identifier, $password)) {
        
        // 2. Role-Based Verification & Redirection
        if (in_array($_SESSION['role'], ['librarian', 'super_admin', 'student'])) {
            
            // Redirect based on role
            if ($_SESSION['role'] === 'librarian') {
                require_once 'core/audit_helper.php';
                logAction('LOGIN', 'User (Librarian) logged in successfully');
                header("Location: dashboards/librarian/librarian_dashboard.php");
            } elseif ($_SESSION['role'] === 'student') {
                header("Location: dashboards/student/student_dashboard.php");
            } else {
                header("Location: dashboards/super_admin/manage_pages.php");
            }
            exit();
            
        } else {
            // Unset session if role is unrecognized or insufficient
            session_destroy();
            header("Location: login.php?error=access_denied");
            exit();
        }
    } else {
        header("Location: login.php?error=invalid_credentials");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>
