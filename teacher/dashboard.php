<?php
// ============================================================
// TEACHER DASHBOARD
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('teacher');

$page_title = 'Teacher Dashboard';

// Get teacher record
$user_id = (int)$_SESSION['user_id'];
$teacher = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM teachers WHERE user_id=$user_id"));
$teacher_id = $teacher['id'] ?? 0;

// Subjects & classes assigned
$my_subjects = mysqli_query($conn, "
    SELECT cs.*, s.subject_name, c.class_name
    FROM class_subjects cs
    JOIN subjects s ON cs.subject_id=s.id
    JOIN classes c ON cs.class_id=c.id
    WHERE cs.teacher_id=$teacher_id
");

// Count
$subj_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM class_subjects WHERE teacher_id=$teacher_id"))['c'];
$today = date('Y-m-d');

// Notifications
$notifications = mysqli_query($conn, "SELECT * FROM notifications WHERE is_active=1 AND (target_role='teacher' OR target_role='all') ORDER BY created_at DESC LIMIT 5");

require_once '../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-chalkboard-user"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= htmlspecialchars($teacher['fullname'] ?? 'Teacher') ?></div>
            <div class="stat-label">Welcome back!</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-book"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $subj_count ?></div>
            <div class="stat-label">Assigned Subjects</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-id-card"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $teacher['teacher_id'] ?? '—' ?></div>
            <div class="stat-label">Teacher ID</div>
        </div>
    </div>
</div>

<div class="dash-grid">
    <!-- My Subjects -->
    <div class="card">
        <div class="card-header"><span class="card-title">My Classes & Subjects</span></div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Class</th><th>Subject</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (mysqli_num_rows($my_subjects) === 0): ?>
                    <tr><td colspan="3" style="text-align:center;padding:20px;color:var(--text-muted);">No subjects assigned yet.</td></tr>
                <?php else: ?>
                <?php while ($ms = mysqli_fetch_assoc($my_subjects)): ?>
                <tr>
                    <td><span class="badge badge-primary"><?= $ms['class_name'] ?></span></td>
                    <td><?= htmlspecialchars($ms['subject_name']) ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="attendance.php?class_id=<?= $ms['class_id'] ?>" class="btn btn-outline btn-sm">
                                <i class="fa-solid fa-calendar-check"></i> Attendance
                            </a>
                            <a href="results.php?class_id=<?= $ms['class_id'] ?>&subject_id=<?= $ms['subject_id'] ?>" class="btn btn-outline btn-sm">
                                <i class="fa-solid fa-chart-bar"></i> Results
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Notifications -->
    <div class="card">
        <div class="card-header"><span class="card-title"><i class="fa-solid fa-bell" style="color:var(--accent);"></i> Announcements</span></div>
        <div class="card-body">
            <?php $notif_count = 0; while ($n = mysqli_fetch_assoc($notifications)): $notif_count++; ?>
            <div class="notification-item">
                <div class="notif-dot"></div>
                <div>
                    <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
                    <div class="notif-msg"><?= htmlspecialchars(substr($n['message'], 0, 90)) ?>...</div>
                    <div class="notif-time"><?= date('M d, Y', strtotime($n['created_at'])) ?></div>
                </div>
            </div>
            <?php endwhile; ?>
            <?php if ($notif_count === 0): ?>
                <p style="color:var(--text-muted);text-align:center;padding:20px 0;">No announcements.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
