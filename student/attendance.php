<?php
// ============================================================
// STUDENT — MY ATTENDANCE
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('student');

$page_title = 'My Attendance';

$user_id    = (int)$_SESSION['user_id'];
$student    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE user_id=$user_id"));
$student_id = (int)$student['id'];

// Summary
$total   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM attendance WHERE student_id=$student_id"))['c'];
$present = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM attendance WHERE student_id=$student_id AND status='Present'"))['c'];
$absent  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM attendance WHERE student_id=$student_id AND status='Absent'"))['c'];
$late    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM attendance WHERE student_id=$student_id AND status='Late'"))['c'];
$pct     = $total > 0 ? round(($present / $total) * 100) : 0;

// Filter by month
$sel_month = isset($_GET['month']) ? clean($conn, $_GET['month']) : date('Y-m');
$month_start = $sel_month . '-01';
$month_end   = date('Y-m-t', strtotime($month_start));

$records = mysqli_query($conn, "
    SELECT * FROM attendance
    WHERE student_id=$student_id AND attendance_date BETWEEN '$month_start' AND '$month_end'
    ORDER BY attendance_date DESC
");

require_once '../includes/header.php';
?>

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-calendar-days"></i></div>
        <div class="stat-info"><div class="stat-value"><?= $total ?></div><div class="stat-label">Total Days</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-info"><div class="stat-value"><?= $present ?></div><div class="stat-label">Present</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-circle-xmark"></i></div>
        <div class="stat-info"><div class="stat-value"><?= $absent ?></div><div class="stat-label">Absent</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-clock"></i></div>
        <div class="stat-info"><div class="stat-value"><?= $pct ?>%</div><div class="stat-label">Attendance Rate</div></div>
    </div>
</div>

<!-- Progress bar -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px;font-weight:600;">
            <span>Attendance Rate</span><span><?= $pct ?>%</span>
        </div>
        <div style="background:#e2e8f0;border-radius:10px;height:12px;overflow:hidden;">
            <div style="width:<?= $pct ?>%;background:<?= $pct >= 75 ? 'var(--success)' : ($pct >= 50 ? 'var(--warning)' : 'var(--danger)') ?>;height:100%;border-radius:10px;transition:width 0.5s;"></div>
        </div>
        <?php if ($pct < 75): ?>
        <p style="color:var(--danger);font-size:12px;margin-top:6px;"><i class="fa-solid fa-triangle-exclamation"></i> Your attendance is below 75%. Please improve your attendance.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Monthly filter + records -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Attendance Record</span>
        <form method="GET" style="display:flex;gap:8px;align-items:center;">
            <input type="month" name="month" class="form-control" value="<?= $sel_month ?>" style="width:180px;">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i></button>
        </form>
    </div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Date</th><th>Day</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (mysqli_num_rows($records) === 0): ?>
                <tr><td colspan="3" style="text-align:center;padding:24px;color:var(--text-muted);">No records for this month.</td></tr>
            <?php else: ?>
            <?php while ($r = mysqli_fetch_assoc($records)):
                $bc = ['Present'=>'badge-success','Absent'=>'badge-danger','Late'=>'badge-warning'][$r['status']] ?? 'badge-info';
            ?>
            <tr>
                <td><?= date('F d, Y', strtotime($r['attendance_date'])) ?></td>
                <td><?= date('l', strtotime($r['attendance_date'])) ?></td>
                <td><span class="badge <?= $bc ?>"><?= $r['status'] ?></span></td>
            </tr>
            <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
