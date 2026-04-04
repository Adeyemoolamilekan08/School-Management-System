<?php
// ============================================================
// STUDENT DASHBOARD
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('student');

$page_title = 'My Dashboard';

$user_id = (int)$_SESSION['user_id'];
$student = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT s.*, c.class_name, p.fullname as parent_name, p.phone as parent_phone
    FROM students s
    LEFT JOIN classes c ON s.class_id=c.id
    LEFT JOIN parents p ON s.parent_id=p.id
    WHERE s.user_id=$user_id
"));

if (!$student) {
    echo "<p>Student profile not found.</p>";
    require_once '../includes/footer.php'; exit();
}

$student_id = (int)$student['id'];
$class_id   = (int)$student['class_id'];

// Attendance summary
$total_att     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM attendance WHERE student_id=$student_id"))['c'];
$present_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM attendance WHERE student_id=$student_id AND status='Present'"))['c'];
$pct = $total_att > 0 ? round(($present_count / $total_att) * 100) : 0;

// Latest results summary
$latest_term    = 'First Term';
$latest_session = date('Y') . '/' . (date('Y') + 1);
$result_count   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM results WHERE student_id=$student_id"))['c'];

// Payments
$paid_count    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE student_id=$student_id AND status='Paid'"))['c'];
$pending_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE student_id=$student_id AND status='Pending'"))['c'];

// Notifications for student
$notifications = mysqli_query($conn, "SELECT * FROM notifications WHERE is_active=1 AND (target_role='student' OR target_role='all') ORDER BY created_at DESC LIMIT 5");

require_once '../includes/header.php';
?>

<!-- Profile Card -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
        <div style="width:72px;height:72px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;flex-shrink:0;">
            <?= strtoupper(substr($student['fullname'],0,1)) ?>
        </div>
        <div style="flex:1;">
            <div style="font-size:20px;font-weight:800;"><?= htmlspecialchars($student['fullname']) ?></div>
            <div style="color:var(--text-muted);font-size:13px;margin-top:4px;">
                <span class="badge badge-primary" style="margin-right:6px;"><?= $student['student_id'] ?></span>
                <span class="badge badge-info"><?= $student['class_name'] ?? '—' ?></span>
                <span style="margin-left:10px;"><?= $student['gender'] ?></span>
                <?php if ($student['date_of_birth']): ?>
                <span style="margin-left:10px;"><i class="fa-solid fa-cake-candles"></i> <?= date('M d, Y', strtotime($student['date_of_birth'])) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($student['parent_name']): ?>
            <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">
                <i class="fa-solid fa-people-roof"></i> Parent: <?= htmlspecialchars($student['parent_name']) ?> — <?= $student['parent_phone'] ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $pct ?>%</div>
            <div class="stat-label">Attendance Rate (<?= $present_count ?>/<?= $total_att ?>)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-chart-bar"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $result_count ?></div>
            <div class="stat-label">Result Records</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-money-bill-wave"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $paid_count ?></div>
            <div class="stat-label">Payments Made</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-circle-exclamation"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $pending_count ?></div>
            <div class="stat-label">Pending Payments</div>
        </div>
    </div>
</div>

<div class="dash-grid">
    <!-- Quick links -->
    <div class="card">
        <div class="card-header"><span class="card-title">Quick Access</span></div>
        <div class="card-body" style="display:flex;flex-wrap:wrap;gap:12px;">
            <a href="results.php" class="btn btn-primary"><i class="fa-solid fa-chart-bar"></i> View My Results</a>
            <a href="attendance.php" class="btn btn-outline"><i class="fa-solid fa-calendar-check"></i> My Attendance</a>
            <a href="payments.php" class="btn btn-outline"><i class="fa-solid fa-money-bill-wave"></i> Payment History</a>
            <a href="timetable.php" class="btn btn-outline"><i class="fa-solid fa-clock"></i> View Timetable</a>
        </div>
    </div>

    <!-- Notifications -->
    <div class="card">
        <div class="card-header"><span class="card-title"><i class="fa-solid fa-bell" style="color:var(--accent);"></i> Announcements</span></div>
        <div class="card-body">
            <?php $nc = 0; while ($n = mysqli_fetch_assoc($notifications)): $nc++; ?>
            <div class="notification-item">
                <div class="notif-dot"></div>
                <div>
                    <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
                    <div class="notif-msg"><?= htmlspecialchars(substr($n['message'],0,90)) ?>...</div>
                    <div class="notif-time"><?= date('M d, Y', strtotime($n['created_at'])) ?></div>
                </div>
            </div>
            <?php endwhile; if ($nc === 0): ?>
                <p style="color:var(--text-muted);text-align:center;padding:20px 0;">No announcements.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
