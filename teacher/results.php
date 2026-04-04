<?php
// ============================================================
// TEACHER — ENTER RESULTS
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('teacher');

$page_title = 'Enter Results';

$user_id    = (int)$_SESSION['user_id'];
$teacher    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM teachers WHERE user_id=$user_id"));
$teacher_id = $teacher['id'] ?? 0;

// Save result
if (isset($_POST['save_result'])) {
    $student_id = (int)$_POST['student_id'];
    $subject_id = (int)$_POST['subject_id'];
    $class_id   = (int)$_POST['class_id'];
    $term       = clean($conn, $_POST['term']);
    $session    = clean($conn, $_POST['session']);
    $ca         = min(40, max(0, (float)$_POST['ca_score']));
    $exam       = min(60, max(0, (float)$_POST['exam_score']));
    $total      = $ca + $exam;
    list($grade, $remark) = calculate_grade($total);

    $sql = "INSERT INTO results (student_id, subject_id, class_id, term, session, ca_score, exam_score, total_score, grade, remark)
            VALUES ($student_id,$subject_id,$class_id,'$term','$session',$ca,$exam,$total,'$grade','$remark')
            ON DUPLICATE KEY UPDATE ca_score=$ca, exam_score=$exam, total_score=$total, grade='$grade', remark='$remark'";

    if (mysqli_query($conn, $sql)) { alert('success', 'Result saved!'); }
    else { alert('danger', mysqli_error($conn)); }
    redirect('results.php?class_id=' . $class_id . '&subject_id=' . $subject_id . '&term=' . urlencode($term) . '&session=' . urlencode($session));
}

$sel_class   = isset($_GET['class_id'])   ? (int)$_GET['class_id']             : 0;
$sel_subject = isset($_GET['subject_id']) ? (int)$_GET['subject_id']           : 0;
$sel_term    = isset($_GET['term'])       ? clean($conn, $_GET['term'])         : '';
$sel_session = isset($_GET['session'])    ? clean($conn, $_GET['session'])      : '';

// My classes & subjects
$my_assignments = mysqli_query($conn, "
    SELECT cs.class_id, cs.subject_id, c.class_name, s.subject_name
    FROM class_subjects cs
    JOIN classes c ON cs.class_id=c.id
    JOIN subjects s ON cs.subject_id=s.id
    WHERE cs.teacher_id=$teacher_id ORDER BY c.class_name, s.subject_name
");

$terms    = ['First Term', 'Second Term', 'Third Term'];
$yr = (int)date('Y');
$sessions = [];
for ($y = $yr; $y >= $yr-3; $y--) $sessions[] = $y . '/' . ($y+1);

// Existing results
$result_rows = [];
if ($sel_class && $sel_subject && $sel_term && $sel_session) {
    $rq = mysqli_query($conn, "
        SELECT s.id, s.fullname, s.student_id as sid, r.ca_score, r.exam_score, r.total_score, r.grade, r.remark
        FROM students s
        LEFT JOIN results r ON s.id=r.student_id AND r.subject_id=$sel_subject AND r.term='$sel_term' AND r.session='$sel_session'
        WHERE s.class_id=$sel_class ORDER BY s.fullname
    ");
    while ($r = mysqli_fetch_assoc($rq)) $result_rows[] = $r;
}

require_once '../includes/header.php';
?>

<?php show_alert(); ?>

<div class="card">
    <div class="card-header"><span class="card-title">Enter / View Results</span></div>
    <div class="card-body">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
            <div class="form-group" style="flex:2;min-width:200px;">
                <label>Class & Subject</label>
                <select name="class_id" class="form-control" onchange="this.form.submit()" id="assignSelect">
                    <option value="">-- Select --</option>
                    <?php while ($a = mysqli_fetch_assoc($my_assignments)): ?>
                    <option value="<?= $a['class_id'] ?>" data-subject="<?= $a['subject_id'] ?>"
                        <?= ($sel_class==$a['class_id'] && $sel_subject==$a['subject_id']) ? 'selected' : '' ?>>
                        <?= $a['class_name'] ?> — <?= htmlspecialchars($a['subject_name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <input type="hidden" name="subject_id" id="hidSubject" value="<?= $sel_subject ?>">
            </div>
            <div class="form-group" style="flex:1;min-width:140px;">
                <label>Term</label>
                <select name="term" class="form-control">
                    <?php foreach ($terms as $t): ?>
                    <option value="<?= $t ?>" <?= $sel_term==$t?'selected':'' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="flex:1;min-width:140px;">
                <label>Session</label>
                <select name="session" class="form-control">
                    <?php foreach ($sessions as $s): ?>
                    <option value="<?= $s ?>" <?= $sel_session==$s?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="align-self:flex-end;">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> Load</button>
            </div>
        </form>
    </div>
</div>

<?php if ($sel_class && $sel_subject && $sel_term && $sel_session && count($result_rows) > 0): ?>
<!-- Bulk entry table -->
<div class="card">
    <div class="card-header"><span class="card-title">Students — Score Entry</span>
        <span style="font-size:12px;color:var(--text-muted);">CA max: 40 | Exam max: 60</span>
    </div>
    <form method="POST">
        <input type="hidden" name="save_result" value="1">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>#</th><th>Student</th><th>CA (40)</th><th>Exam (60)</th><th>Total</th><th>Grade</th></tr></thead>
                <tbody>
                <?php foreach ($result_rows as $i => $r): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($r['fullname']) ?> <span style="color:var(--text-muted);font-size:11px;">(<?= $r['sid'] ?>)</span></td>
                    <td><input type="number" name="ca[<?= $r['id'] ?>]" class="form-control ca-in" style="width:80px;" min="0" max="40" step="0.5" value="<?= $r['ca_score'] ?? '' ?>" data-row="<?= $i ?>"></td>
                    <td><input type="number" name="exam[<?= $r['id'] ?>]" class="form-control ex-in" style="width:80px;" min="0" max="60" step="0.5" value="<?= $r['exam_score'] ?? '' ?>" data-row="<?= $i ?>"></td>
                    <td><span id="total_<?= $i ?>" style="font-weight:700;"><?= $r['total_score'] ?? '—' ?></span></td>
                    <td><span id="grade_<?= $i ?>"><?= $r['grade'] ?? '—' ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Hidden fields for batch submit -->
        <input type="hidden" name="student_id" value="0"><!-- handled via JS below -->
        <input type="hidden" name="subject_id" value="<?= $sel_subject ?>">
        <input type="hidden" name="class_id" value="<?= $sel_class ?>">
        <input type="hidden" name="term" value="<?= htmlspecialchars($sel_term) ?>">
        <input type="hidden" name="session" value="<?= htmlspecialchars($sel_session) ?>">
        <div style="padding:16px;">
            <p style="color:var(--text-muted);font-size:12.5px;margin-bottom:10px;">
                <i class="fa-solid fa-info-circle"></i> To save individual scores, use the form below.
            </p>
        </div>
    </form>
</div>

<!-- Single student save form -->
<div class="card">
    <div class="card-header"><span class="card-title">Save Score for One Student</span></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="save_result" value="1">
            <input type="hidden" name="subject_id" value="<?= $sel_subject ?>">
            <input type="hidden" name="class_id" value="<?= $sel_class ?>">
            <input type="hidden" name="term" value="<?= htmlspecialchars($sel_term) ?>">
            <input type="hidden" name="session" value="<?= htmlspecialchars($sel_session) ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label>Student</label>
                    <select name="student_id" class="form-control" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($result_rows as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['fullname']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>CA Score (max 40)</label>
                    <input type="number" id="ca_score" name="ca_score" class="form-control" min="0" max="40" step="0.5">
                </div>
                <div class="form-group">
                    <label>Exam Score (max 60)</label>
                    <input type="number" id="exam_score" name="exam_score" class="form-control" min="0" max="60" step="0.5">
                </div>
                <div class="form-group">
                    <label>Auto Total & Grade</label>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input type="number" id="total_score" name="total_score" class="form-control" readonly style="background:#f8fafc;width:80px;">
                        <span id="grade_display" style="font-weight:700;font-size:16px;color:var(--primary);">—</span>
                    </div>
                </div>
            </div>
            <div style="margin-top:12px;">
                <button type="submit" class="btn btn-accent"><i class="fa-solid fa-save"></i> Save Score</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
// sync class+subject selector
document.getElementById('assignSelect') && document.getElementById('assignSelect').addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    document.getElementById('hidSubject').value = opt.dataset.subject || '';
});
</script>

<?php require_once '../includes/footer.php'; ?>
