<?php
// ============================================================
// ADMIN — ATTENDANCE
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('admin');

$page_title = 'Attendance';

// MARK ATTENDANCE (bulk submit)
if (isset($_POST['mark_attendance'])) {
    $class_id = (int)$_POST['class_id'];
    $att_date = clean($conn, $_POST['att_date']);
    $statuses = $_POST['status'] ?? [];
    $user_id  = $_SESSION['user_id'];

    foreach ($statuses as $student_id => $status) {
        $student_id = (int)$student_id;
        $status = in_array($status, ['Present','Absent','Late']) ? $status : 'Absent';

        // Insert or update
        $check = mysqli_query($conn, "SELECT id FROM attendance WHERE student_id=$student_id AND attendance_date='$att_date'");
        if (mysqli_num_rows($check) > 0) {
            $att_id = mysqli_fetch_assoc($check)['id'];
            mysqli_query($conn, "UPDATE attendance SET status='$status', marked_by=$user_id WHERE id=$att_id");
        } else {
            mysqli_query($conn, "INSERT INTO attendance (student_id, class_id, attendance_date, status, marked_by) VALUES ($student_id,$class_id,'$att_date','$status',$user_id)");
        }
    }
    alert('success', 'Attendance marked successfully!');
    redirect('attendance.php');
}

$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$selected_date  = isset($_GET['att_date']) ? clean($conn, $_GET['att_date']) : date('Y-m-d');
$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");

// Get students for selected class
$students = [];
$existing_att = [];
if ($selected_class) {
    $sq = mysqli_query($conn, "SELECT * FROM students WHERE class_id=$selected_class ORDER BY fullname");
    while ($s = mysqli_fetch_assoc($sq)) $students[] = $s;

    // Existing attendance for this date
    $aq = mysqli_query($conn, "SELECT student_id, status FROM attendance WHERE class_id=$selected_class AND attendance_date='$selected_date'");
    while ($a = mysqli_fetch_assoc($aq)) $existing_att[$a['student_id']] = $a['status'];
}

// Attendance history
$history_class  = isset($_GET['hist_class']) ? (int)$_GET['hist_class'] : 0;
$history_date   = isset($_GET['hist_date'])  ? clean($conn, $_GET['hist_date']) : '';
$history = [];
if ($history_class && $history_date) {
    $hq = mysqli_query($conn, "
        SELECT s.fullname, s.student_id, a.status, a.attendance_date
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        WHERE a.class_id=$history_class AND a.attendance_date='$history_date'
        ORDER BY s.fullname
    ");
    while ($h = mysqli_fetch_assoc($hq)) $history[] = $h;
}

require_once '../includes/header.php';
?>

<?php show_alert(); ?>

<!-- Mark Attendance -->
<div class="card">
    <div class="card-header"><span class="card-title">Mark Attendance</span></div>
    <div class="card-body">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
            <div class="form-group" style="flex:1;min-width:180px;">
                <label>Select Class</label>
                <select name="class_id" class="form-control" required>
                    <option value="">-- Select Class --</option>
                    <?php $c2 = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name"); while ($cl = mysqli_fetch_assoc($c2)): ?>
                    <option value="<?= $cl['id'] ?>" <?= $selected_class == $cl['id'] ? 'selected' : '' ?>><?= $cl['class_name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="flex:1;min-width:180px;">
                <label>Date</label>
                <input type="date" name="att_date" class="form-control" value="<?= $selected_date ?>" required>
            </div>
            <div class="form-group" style="align-self:flex-end;">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> Load Students</button>
            </div>
        </form>

        <?php if ($selected_class && count($students) > 0): ?>
        <form method="POST">
            <input type="hidden" name="class_id" value="<?= $selected_class ?>">
            <input type="hidden" name="att_date" value="<?= $selected_date ?>">

            <div style="display:flex;gap:10px;margin-bottom:12px;">
                <button type="button" class="btn btn-success btn-sm" onclick="markAll('Present')">Mark All Present</button>
                <button type="button" class="btn btn-danger btn-sm" onclick="markAll('Absent')">Mark All Absent</button>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead><tr><th>#</th><th>Student Name</th><th>Student ID</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($students as $i => $s): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><strong><?= htmlspecialchars($s['fullname']) ?></strong></td>
                        <td><?= $s['student_id'] ?></td>
                        <td>
                            <select name="status[<?= $s['id'] ?>]" class="form-control status-select" style="width:140px;">
                                <?php $cur = $existing_att[$s['id']] ?? 'Present'; ?>
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
            <div style="margin-top:16px;">
                <button type="submit" name="mark_attendance" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Save Attendance
                </button>
            </div>
        </form>
        <?php elseif ($selected_class): ?>
            <p style="color:var(--text-muted);padding:20px 0;">No students found in this class.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Attendance History -->
<div class="card">
    <div class="card-header"><span class="card-title">View Attendance History</span></div>
    <div class="card-body">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
            <div class="form-group" style="flex:1;min-width:180px;">
                <label>Class</label>
                <select name="hist_class" class="form-control">
                    <option value="">-- Select Class --</option>
                    <?php $c3 = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name"); while ($cl = mysqli_fetch_assoc($c3)): ?>
                    <option value="<?= $cl['id'] ?>" <?= $history_class==$cl['id']?'selected':'' ?>><?= $cl['class_name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="flex:1;min-width:180px;">
                <label>Date</label>
                <input type="date" name="hist_date" class="form-control" value="<?= $history_date ?>">
            </div>
            <div class="form-group" style="align-self:flex-end;">
                <button type="submit" class="btn btn-accent"><i class="fa-solid fa-eye"></i> View</button>
            </div>
        </form>

        <?php if (count($history) > 0): ?>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>#</th><th>Student</th><th>ID</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($history as $i => $h): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($h['fullname']) ?></td>
                    <td><?= $h['student_id'] ?></td>
                    <td><?= $h['attendance_date'] ?></td>
                    <td>
                        <?php
                        $badges = ['Present' => 'badge-success', 'Absent' => 'badge-danger', 'Late' => 'badge-warning'];
                        $bc = $badges[$h['status']] ?? 'badge-info';
                        ?>
                        <span class="badge <?= $bc ?>"><?= $h['status'] ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php elseif ($history_class && $history_date): ?>
            <p style="color:var(--text-muted);">No attendance records for this selection.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function markAll(status) {
    document.querySelectorAll('.status-select').forEach(function(sel) {
        sel.value = status;
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
