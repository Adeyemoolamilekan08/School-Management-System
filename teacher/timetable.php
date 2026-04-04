<?php
// ============================================================
// TEACHER — MY TIMETABLE
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('teacher');

$page_title = 'My Timetable';

$user_id    = (int)$_SESSION['user_id'];
$teacher    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM teachers WHERE user_id=$user_id"));
$teacher_id = $teacher['id'] ?? 0;

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
$tt_data = [];

$ttq = mysqli_query($conn, "
    SELECT t.*, s.subject_name, c.class_name
    FROM timetable t
    JOIN subjects s ON t.subject_id=s.id
    JOIN classes c ON t.class_id=c.id
    WHERE t.teacher_id=$teacher_id
    ORDER BY FIELD(t.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday'), t.start_time
");
while ($r = mysqli_fetch_assoc($ttq)) $tt_data[$r['day_of_week']][] = $r;

require_once '../includes/header.php';
?>

<div class="card">
    <div class="card-header"><span class="card-title">My Weekly Timetable</span></div>
    <div class="card-body">
    <?php foreach ($days as $day): ?>
        <div style="margin-bottom:20px;">
            <div style="font-weight:700;color:var(--primary);margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px;font-size:13px;">
                <i class="fa-solid fa-calendar-day"></i> <?= $day ?>
            </div>
            <?php if (!empty($tt_data[$day])): ?>
            <div style="display:flex;flex-wrap:wrap;gap:10px;">
                <?php foreach ($tt_data[$day] as $slot): ?>
                <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:12px 16px;min-width:170px;">
                    <div style="font-weight:700;color:#1e40af;font-size:14px;"><?= htmlspecialchars($slot['subject_name']) ?></div>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:3px;"><i class="fa-solid fa-school"></i> <?= $slot['class_name'] ?></div>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                        <i class="fa-solid fa-clock"></i> <?= date('g:i A', strtotime($slot['start_time'])) ?> – <?= date('g:i A', strtotime($slot['end_time'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <p style="color:var(--text-muted);font-size:12.5px;font-style:italic;">No classes.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
