<?php
// ============================================================
// ADMIN DASHBOARD
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('admin');

$page_title = 'Dashboard';

// --- Count stats ---
$total_students  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM students"))['c'];
$total_teachers  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM teachers"))['c'];
$total_classes   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM classes"))['c'];
$total_subjects  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM subjects"))['c'];

// Payment stats
$total_paid      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as s FROM payments WHERE status='Paid'"))['s'] ?? 0;
$payment_count   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments"))['c'];

// Attendance today
$today = date('Y-m-d');
$present_today   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM attendance WHERE attendance_date='$today' AND status='Present'"))['c'];
$absent_today    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM attendance WHERE attendance_date='$today' AND status='Absent'"))['c'];

// Recent students
$recent_students = mysqli_query($conn, "
    SELECT s.fullname, s.student_id, s.photo, c.class_name, s.created_at
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    ORDER BY s.created_at DESC LIMIT 5
");

// Notifications
$notifications = mysqli_query($conn, "SELECT * FROM notifications WHERE is_active=1 ORDER BY created_at DESC LIMIT 5");

require_once '../includes/header.php';
?>

<?php show_alert(); ?>

<!-- STATS CARDS -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-user-graduate"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $total_students ?></div>
            <div class="stat-label">Total Students</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-chalkboard-user"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $total_teachers ?></div>
            <div class="stat-label">Total Teachers</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-school"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $total_classes ?></div>
            <div class="stat-label">Classes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fa-solid fa-money-bill-wave"></i></div>
        <div class="stat-info">
            <div class="stat-value">₦<?= number_format($total_paid, 0) ?></div>
            <div class="stat-label">Total Payments (<?= $payment_count ?>)</div>
        </div>
    </div>
</div>

<!-- ATTENDANCE SUMMARY -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $present_today ?></div>
            <div class="stat-label">Present Today</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-circle-xmark"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $absent_today ?></div>
            <div class="stat-label">Absent Today</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-book"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $total_subjects ?></div>
            <div class="stat-label">Subjects</div>
        </div>
    </div>
</div>

<!-- RECENT STUDENTS + NOTIFICATIONS -->
<div class="dash-grid">
    <!-- Recent Students -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Recent Students</span>
            <a href="students.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>ID</th>
                        <th>Class</th>
                        <th>Enrolled</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($s = mysqli_fetch_assoc($recent_students)): ?>
                    <tr>
                        <td>
                            <div class="table-avatar">
                                <div class="avatar-circle"><?= strtoupper(substr($s['fullname'],0,1)) ?></div>
                                <span><?= htmlspecialchars($s['fullname']) ?></span>
                            </div>
                        </td>
                        <td><?= $s['student_id'] ?></td>
                        <td><?= $s['class_name'] ?? '—' ?></td>
                        <td><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Notifications -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa-solid fa-bell" style="color:var(--accent);"></i> Announcements</span>
            <a href="notifications.php" class="btn btn-outline btn-sm">Manage</a>
        </div>
        <div class="card-body">
            <?php while ($n = mysqli_fetch_assoc($notifications)): ?>
            <div class="notification-item">
                <div class="notif-dot"></div>
                <div>
                    <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
                    <div class="notif-msg"><?= htmlspecialchars(substr($n['message'], 0, 90)) ?>...</div>
                    <div class="notif-time"><?= date('M d, Y', strtotime($n['created_at'])) ?></div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
