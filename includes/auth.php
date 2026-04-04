<?php
// ============================================================
// AUTH.PHP - SESSION & ROLE GUARD
// ============================================================
// Include this file at the top of any protected page.
// Usage: require_once('../includes/auth.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Require login — redirect if not authenticated
function require_login() {
    if (!is_logged_in()) {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}

// Require a specific role
function require_role($role) {
    require_login();
    if ($_SESSION['user_role'] !== $role) {
        // If wrong role, redirect to their own dashboard
        if ($_SESSION['user_role'] === 'admin') {
            header("Location: " . BASE_URL . "/admin/dashboard.php");
        } elseif ($_SESSION['user_role'] === 'teacher') {
            header("Location: " . BASE_URL . "/teacher/dashboard.php");
        } else {
            header("Location: " . BASE_URL . "/student/dashboard.php");
        }
        exit();
    }
}

// Get current user info
function current_user() {
    return [
        'id'       => $_SESSION['user_id']   ?? null,
        'username' => $_SESSION['username']  ?? null,
        'role'     => $_SESSION['user_role'] ?? null,
        'name'     => $_SESSION['user_name'] ?? null,
    ];
}
?>
