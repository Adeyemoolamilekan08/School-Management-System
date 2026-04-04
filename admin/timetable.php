<?php
// ============================================================
// ADMIN — TIMETABLE
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('admin');

$page_title = 'Timetable';

// ADD SLOT
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $class_id   = (int)$_POST['class_id'];
    $subject_id = (int)$_POST['subject_id'];
    $teacher_id = (int)$_POST['teacher_id'];
    $day        = clean($conn, $_POST['day_of_week']);
    $start      = clean($conn, $_POST['start_time']);
    $end        = clean($conn, $_POST['end_time']);

    if (mysqli_query($conn, "INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, start_time, end_time)
        VALUES ($class_id,$subject_id,$teacher_id,'$day','$start','$end')")) {
        alert('success', 'Timetable slot added!');
    } else {
        alert('danger', mysqli_error($conn));
    }
    redirect('timetable.php?view_class=' . $class_id);
}

// DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT class_id FROM timetable WHERE id=$id"));
    mysqli_query($conn, "DELETE FROM timetable WHERE id=$id");
    alert('success', 'Slot removed.');
    redirect('timetable.php?view_class=' . ($row['class_id'] ?? ''));
}

$sel_class = isset($_GET['view_class']) ? (int)$_GET['view_class'] : 0;
$classes   = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");
$days      = ['Monday','Tuesday','Wednesday','Thursday','Friday'];

// Timetable data for selected class
$tt_data = [];
if ($sel_class) {
    $ttq = mysqli_query($conn, "
        SELECT t.*, s.subject_name, s.subject_code, tch.fullname as teacher_name
        FROM timetable t
        JOIN subjects s ON t.subject_id = s.id
        LEFT JOIN teachers tch ON t.teacher_id = tch.id
        WHERE t.class_id=$sel_class
        ORDER BY FIELD(t.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday'), t.start_time
    ");
    while ($r = mysqli_fetch_assoc($ttq)) {
        $tt_data[$r['day_of_week']][] = $r;
    }
}

require_once '../includes/header.php';
?>

<?php show_alert(); ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
    <!-- Timetable View -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Class Timetable</span>
            <form method="GET" style="display:flex;gap:8px;align-items:center;">
                <select name="view_class" class="form-control" style="width:150px;" onchange="this.form.submit()">
                    <option value="">-- Select Class --</option>
                    <?php $c2 = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name"); while ($cl = mysqli_fetch_assoc($c2)): ?>
                    <option value="<?= $cl['id'] ?>" <?= $sel_class==$cl['id']?'selected':'' ?>><?= $cl['class_name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>
        <div class="card-body">
        <?php if ($sel_class): ?>
            <?php foreach ($days as $day): ?>
            <div style="margin-bottom:18px;">
                <div style="font-weight:700;font-size:13px;color:var(--primary);margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px;">
                    <i class="fa-solid fa-calendar-day"></i> <?= $day ?>
                </div>
                <?php if (!empty($tt_data[$day])): ?>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <?php foreach ($tt_data[$day] as $slot): ?>
                    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;min-width:160px;position:relative;">
                        <div style="font-weight:700;font-size:13px;color:#1e40af;"><?= htmlspecialchars($slot['subject_name']) ?></div>
                        <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px;">
                            <?= date('g:i A', strtotime($slot['start_time'])) ?> – <?= date('g:i A', strtotime($slot['end_time'])) ?>
                        </div>
                        <?php if ($slot['teacher_name']): ?>
                        <div style="font-size:11px;color:#3b82f6;margin-top:2px;"><i class="fa-solid fa-user-tie"></i> <?= htmlspecialchars($slot['teacher_name']) ?></div>
                        <?php endif; ?>
                        <button onclick="confirmDelete('?delete=<?= $slot['id'] ?>&view_class=<?= $sel_class ?>','this slot')"
                            style="position:absolute;top:6px;right:6px;background:none;border:none;color:#ef4444;cursor:pointer;font-size:12px;">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="color:var(--text-muted);font-size:12.5px;font-style:italic;">No classes scheduled.</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color:var(--text-muted);text-align:center;padding:30px 0;">Select a class to view its timetable.</p>
        <?php endif; ?>
        </div>
    </div>

    <!-- Add Slot -->
    <div class="card">
        <div class="card-header"><span class="card-title">Add Timetable Slot</span></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Class *</label>
                    <select name="class_id" class="form-control" required>
                        <option value="">-- Select --</option>
                        <?php $c3 = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name"); while ($cl = mysqli_fetch_assoc($c3)): ?>
                        <option value="<?= $cl['id'] ?>" <?= $sel_class==$cl['id']?'selected':'' ?>><?= $cl['class_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Subject *</label>
                    <select name="subject_id" class="form-control" required>
                        <option value="">-- Select --</option>
                        <?php $sq = mysqli_query($conn, "SELECT * FROM subjects ORDER BY subject_name"); while ($sub = mysqli_fetch_assoc($sq)): ?>
                        <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['subject_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Teacher</label>
                    <select name="teacher_id" class="form-control">
                        <option value="0">-- None --</option>
                        <?php $tq = mysqli_query($conn, "SELECT * FROM teachers ORDER BY fullname"); while ($tch = mysqli_fetch_assoc($tq)): ?>
                        <option value="<?= $tch['id'] ?>"><?= htmlspecialchars($tch['fullname']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Day *</label>
                    <select name="day_of_week" class="form-control" required>
                        <?php foreach ($days as $d): ?>
                        <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Start Time *</label>
                    <input type="time" name="start_time" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label>End Time *</label>
                    <input type="time" name="end_time" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    <i class="fa-solid fa-plus"></i> Add Slot
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
