<?php
// ============================================================
// TEACHER — MARK ATTENDANCE
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('teacher');

$page_title = 'Mark Attendance';

$user_id    = (int)$_SESSION['user_id'];
$teacher    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM teachers WHERE user_id=$user_id"));
$teacher_id = $teacher['id'] ?? 0;

// Save attendance
if (isset($_POST['mark_attendance'])) {
    $class_id = (int)$_POST['class_id'];
    $att_date = clean($conn, $_POST['att_date']);
    $statuses = $_POST['status'] ?? [];

    foreach ($statuses as $sid => $status) {
        $sid    = (int)$sid;
        $status = in_array($status, ['Present','Absent','Late']) ? $status : 'Absent';
        $check  = mysqli_query($conn, "SELECT id FROM attendance WHERE student_id=$sid AND attendance_date='$att_date'");
        if (mysqli_num_rows($check) > 0) {
            $aid = mysqli_fetch_assoc($check)['id'];
            mysqli_query($conn, "UPDATE attendance SET status='$status', marked_by=$user_id WHERE id=$aid");
        } else {
            mysqli_query($conn, "INSERT INTO attendance (student_id, class_id, attendance_date, status, marked_by) VALUES ($sid,$class_id,'$att_date','$status',$user_id)");
        }
    }
    alert('success', 'Attendance saved!');
    redirect('attendance.php');
}

// My classes
$my_classes = mysqli_query($conn, "
    SELECT DISTINCT c.id, c.class_name
    FROM class_subjects cs
    JOIN classes c ON cs.class_id=c.id
    WHERE cs.teacher_id=$teacher_id
    ORDER BY c.class_name
");

$sel_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$sel_date  = isset($_GET['att_date']) ? clean($conn, $_GET['att_date']) : date('Y-m-d');

$students = [];
$existing_att = [];
if ($sel_class) {
    $sq = mysqli_query($conn, "SELECT * FROM students WHERE class_id=$sel_class ORDER BY fullname");
    while ($s = mysqli_fetch_assoc($sq)) $students[] = $s;
    $aq = mysqli_query($conn, "SELECT student_id, status FROM attendance WHERE class_id=$sel_class AND attendance_date='$sel_date'");
    while ($a = mysqli_fetch_assoc($aq)) $existing_att[$a['student_id']] = $a['status'];
}

require_once '../includes/header.php';
?>

<?php show_alert(); ?>

<div class="card">
    <div class="card-header"><span class="card-title">Mark Attendance</span></div>
    <div class="card-body">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
            <div class="form-group" style="flex:1;min-width:160px;">
                <label>Select Class</label>
                <select name="class_id" class="form-control" required>
                    <option value="">-- My Classes --</option>
                    <?php while ($cl = mysqli_fetch_assoc($my_classes)): ?>
                    <option value="<?= $cl['id'] ?>" <?= $sel_class==$cl['id']?'selected':'' ?>><?= $cl['class_name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="flex:1;min-width:160px;">
                <label>Date</label>
                <input type="date" name="att_date" class="form-control" value="<?= $sel_date ?>">
            </div>
            <div class="form-group" style="align-self:flex-end;">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> Load</button>
            </div>
        </form>

        <?php if ($sel_class && count($students) > 0): ?>
        <form method="POST">
            <input type="hidden" name="class_id" value="<?= $sel_class ?>">
            <input type="hidden" name="att_date" value="<?= $sel_date ?>">
            <div style="display:flex;gap:10px;margin-bottom:12px;">
                <button type="button" class="btn btn-success btn-sm" onclick="markAll('Present')">All Present</button>
                <button type="button" class="btn btn-danger btn-sm" onclick="markAll('Absent')">All Absent</button>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>#</th><th>Student Name</th><th>ID</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($students as $i => $s): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($s['fullname']) ?></td>
                        <td><?= $s['student_id'] ?></td>
                        <td>
                            <?php $cur = $existing_att[$s['id']] ?? 'Present'; ?>
                            <select name="status[<?= $s['id'] ?>]" class="form-control status-select" style="width:130px;">
                                <option value="Present" <?= $cur=='Present'?'selected':'' ?>>✅ Present</option>
                                <option value="Absent"  <?= $cur=='Absent' ?'selected':'' ?>>❌ Absent</option>
                                <option value="Late"    <?= $cur=='Late'   ?'selected':'' ?>>⏰ Late</option>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:14px;">
                <button type="submit" name="mark_attendance" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Save Attendance
                </button>
            </div>
        </form>
        <?php elseif ($sel_class): ?>
            <p style="color:var(--text-muted);">No students in this class.</p>
        <?php endif; ?>
    </div>
</div>
<script>
function markAll(s) { document.querySelectorAll('.status-select').forEach(function(el){el.value=s;}); }
</script>
<?php require_once '../includes/footer.php'; ?>
