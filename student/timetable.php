<?php
// ============================================================
// STUDENT — MY TIMETABLE
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('student');

$page_title = 'My Timetable';

$user_id  = (int)$_SESSION['user_id'];
$student  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, c.class_name FROM students s LEFT JOIN classes c ON s.class_id=c.id WHERE s.user_id=$user_id"));
$class_id = (int)$student['class_id'];

$days    = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
$tt_data = [];

if ($class_id) {
    $ttq = mysqli_query($conn, "
        SELECT t.*, s.subject_name, tch.fullname as teacher_name
        FROM timetable t
        JOIN subjects s ON t.subject_id=s.id
        LEFT JOIN teachers tch ON t.teacher_id=tch.id
        WHERE t.class_id=$class_id
        ORDER BY FIELD(t.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday'), t.start_time
    ");
    while ($r = mysqli_fetch_assoc($ttq)) $tt_data[$r['day_of_week']][] = $r;
}

require_once '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <span class="card-title">Timetable — <?= htmlspecialchars($student['class_name'] ?? 'My Class') ?></span>
    </div>
    <div class="card-body">
    <?php if (!$class_id): ?>
        <p style="color:var(--text-muted);text-align:center;padding:30px;">You are not assigned to a class yet.</p>
    <?php else: ?>
        <?php foreach ($days as $day): ?>
        <div style="margin-bottom:20px;">
            <div style="font-weight:700;color:var(--primary);margin-bottom:8px;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-calendar-day"></i> <?= $day ?>
                <?php if ($day === date('l')): ?>
                    <span class="badge badge-success" style="font-size:10px;">Today</span>
                <?php endif; ?>
            </div>
            <?php if (!empty($tt_data[$day])): ?>
            <div style="display:flex;flex-wrap:wrap;gap:10px;">
                <?php foreach ($tt_data[$day] as $slot): ?>
                <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:12px 16px;min-width:180px;">
                    <div style="font-weight:700;color:#1e40af;font-size:14px;"><?= htmlspecialchars($slot['subject_name']) ?></div>
                    <?php if ($slot['teacher_name']): ?>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:3px;"><i class="fa-solid fa-user-tie"></i> <?= htmlspecialchars($slot['teacher_name']) ?></div>
                    <?php endif; ?>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                        <i class="fa-solid fa-clock"></i> <?= date('g:i A', strtotime($slot['start_time'])) ?> – <?= date('g:i A', strtotime($slot['end_time'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <p style="color:var(--text-muted);font-size:12.5px;font-style:italic;">No classes scheduled.</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
