<?php
// ============================================================
// INDEX — Redirect to login or dashboard
// ============================================================
session_start();
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'];
    if ($role === 'admin')       header("Location: /school-system/admin/dashboard.php");
    elseif ($role === 'teacher') header("Location: /school-system/teacher/dashboard.php");
    else                         header("Location: /school-system/student/dashboard.php");
} else {
    header("Location: /school-system/login.php");
}
exit();
?>
