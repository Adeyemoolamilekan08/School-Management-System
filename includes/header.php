<?php
// ============================================================
// HEADER.PHP - Shared HTML head + navbar
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();

$role = $_SESSION['user_role'] ?? '';
$name = $_SESSION['user_name'] ?? 'User';
$base = BASE_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' — ' . SITE_NAME : SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="fa-solid fa-graduation-cap"></i>
        <span><?= SITE_NAME ?></span>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($name, 0, 1)) ?></div>
        <div>
            <div class="user-name"><?= htmlspecialchars($name) ?></div>
            <div class="user-role"><?= ucfirst($role) ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
    <?php if ($role === 'admin'): ?>
        <a href="<?= $base ?>/admin/dashboard.php" class="nav-item <?= (basename($_SERVER['PHP_SELF'])=='dashboard.php')?'active':'' ?>">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>
        <div class="nav-label">MANAGEMENT</div>
        <a href="<?= $base ?>/admin/students.php" class="nav-item">
            <i class="fa-solid fa-user-graduate"></i> Students
        </a>
        <a href="<?= $base ?>/admin/teachers.php" class="nav-item">
            <i class="fa-solid fa-chalkboard-user"></i> Teachers
        </a>
        <a href="<?= $base ?>/admin/parents.php" class="nav-item">
            <i class="fa-solid fa-people-roof"></i> Parents
        </a>
        <a href="<?= $base ?>/admin/classes.php" class="nav-item">
            <i class="fa-solid fa-school"></i> Classes
        </a>
        <a href="<?= $base ?>/admin/subjects.php" class="nav-item">
            <i class="fa-solid fa-book"></i> Subjects
        </a>
        <div class="nav-label">ACADEMICS</div>
        <a href="<?= $base ?>/admin/attendance.php" class="nav-item">
            <i class="fa-solid fa-calendar-check"></i> Attendance
        </a>
        <a href="<?= $base ?>/admin/results.php" class="nav-item">
            <i class="fa-solid fa-chart-bar"></i> Results
        </a>
        <a href="<?= $base ?>/admin/timetable.php" class="nav-item">
            <i class="fa-solid fa-clock"></i> Timetable
        </a>
        <div class="nav-label">FINANCE</div>
        <a href="<?= $base ?>/admin/payments.php" class="nav-item">
            <i class="fa-solid fa-money-bill-wave"></i> Payments
        </a>
        <div class="nav-label">SYSTEM</div>
        <a href="<?= $base ?>/admin/notifications.php" class="nav-item">
            <i class="fa-solid fa-bell"></i> Notifications
        </a>
        <a href="<?= $base ?>/admin/reports.php" class="nav-item">
            <i class="fa-solid fa-file-lines"></i> Reports
        </a>

    <?php elseif ($role === 'teacher'): ?>
        <a href="<?= $base ?>/teacher/dashboard.php" class="nav-item">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>
        <a href="<?= $base ?>/teacher/attendance.php" class="nav-item">
            <i class="fa-solid fa-calendar-check"></i> Mark Attendance
        </a>
        <a href="<?= $base ?>/teacher/results.php" class="nav-item">
            <i class="fa-solid fa-chart-bar"></i> Enter Results
        </a>
        <a href="<?= $base ?>/teacher/timetable.php" class="nav-item">
            <i class="fa-solid fa-clock"></i> My Timetable
        </a>

    <?php elseif ($role === 'student'): ?>
        <a href="<?= $base ?>/student/dashboard.php" class="nav-item">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>
        <a href="<?= $base ?>/student/results.php" class="nav-item">
            <i class="fa-solid fa-chart-bar"></i> My Results
        </a>
        <a href="<?= $base ?>/student/attendance.php" class="nav-item">
            <i class="fa-solid fa-calendar-check"></i> My Attendance
        </a>
        <a href="<?= $base ?>/student/payments.php" class="nav-item">
            <i class="fa-solid fa-money-bill-wave"></i> My Payments
        </a>
        <a href="<?= $base ?>/student/timetable.php" class="nav-item">
            <i class="fa-solid fa-clock"></i> Timetable
        </a>
    <?php endif; ?>
    </nav>

    <a href="<?= $base ?>/logout.php" class="logout-btn">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
</div>

<!-- MAIN WRAPPER -->
<div class="main-wrapper">
    <!-- TOP BAR -->
    <header class="topbar">
        <button class="menu-toggle" id="menuToggle">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="topbar-title"><?= $page_title ?? 'Dashboard' ?></div>
        <div class="topbar-right">
            <span class="topbar-time" id="topbarTime"></span>
            <a href="<?= $base ?>/logout.php" class="topbar-logout">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </header>

    <!-- PAGE CONTENT STARTS -->
    <div class="page-content">
