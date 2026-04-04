<?php
// ============================================================
// ADMIN — RESULTS MANAGEMENT
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('admin');

$page_title = 'Results';

// SAVE RESULT
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

    if (mysqli_query($conn, $sql)) {
        alert('success', 'Result saved!');
    } else {
        alert('danger', mysqli_error($conn));
    }
    redirect('results.php');
}

$sel_class   = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$sel_term    = isset($_GET['term'])     ? clean($conn, $_GET['term']) : '';
$sel_session = isset($_GET['session'])  ? clean($conn, $_GET['session']) : '';

$classes  = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");
$terms    = ['First Term', 'Second Term', 'Third Term'];
$sessions = [];
$yr = (int)date('Y');
for ($y = $yr; $y >= $yr-3; $y--) $sessions[] = $y . '/' . ($y+1);

// Students with results
$results_data = [];
if ($sel_class && $sel_term && $sel_session) {
    $rq = mysqli_query($conn, "
        SELECT s.fullname, s.student_id, s.id as sid,
               sub.subject_name, r.ca_score, r.exam_score, r.total_score, r.grade, r.remark
        FROM students s
        LEFT JOIN results r ON s.id=r.student_id AND r.term='$sel_term' AND r.session='$sel_session' AND r.class_id=$sel_class
        LEFT JOIN subjects sub ON r.subject_id=sub.id
        WHERE s.class_id=$sel_class
        ORDER BY s.fullname, sub.subject_name
    ");
}

require_once '../includes/header.php';
?>

<?php show_alert(); ?>

<!-- Filter -->
<div class="card">
    <div class="card-header"><span class="card-title">Enter / View Results</span></div>
    <div class="card-body">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
            <div class="form-group" style="flex:1;min-width:160px;">
                <label>Class</label>
                <select name="class_id" class="form-control">
                    <option value="">-- Select --</option>
                    <?php $c2 = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name"); while ($cl = mysqli_fetch_assoc($c2)): ?>
                    <option value="<?= $cl['id'] ?>" <?= $sel_class==$cl['id']?'selected':'' ?>><?= $cl['class_name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="flex:1;min-width:160px;">
                <label>Term</label>
                <select name="term" class="form-control">
                    <option value="">-- Select --</option>
                    <?php foreach ($terms as $t): ?>
                    <option value="<?= $t ?>" <?= $sel_term==$t?'selected':'' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="flex:1;min-width:160px;">
                <label>Session</label>
                <select name="session" class="form-control">
                    <option value="">-- Select --</option>
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

<!-- Add Single Result -->
<?php if ($sel_class && $sel_term && $sel_session): ?>
<div class="card">
    <div class="card-header"><span class="card-title">Add / Update a Score</span></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="save_result" value="1">
            <input type="hidden" name="class_id" value="<?= $sel_class ?>">
            <input type="hidden" name="term" value="<?= htmlspecialchars($sel_term) ?>">
            <input type="hidden" name="session" value="<?= htmlspecialchars($sel_session) ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label>Student *</label>
                    <select name="student_id" class="form-control" required>
                        <option value="">-- Select Student --</option>
                        <?php $sq = mysqli_query($conn, "SELECT id,fullname,student_id FROM students WHERE class_id=$sel_class ORDER BY fullname"); while ($s = mysqli_fetch_assoc($sq)): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['fullname']) ?> (<?= $s['student_id'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subject *</label>
                    <select name="subject_id" class="form-control" required>
                        <option value="">-- Select Subject --</option>
                        <?php $subq = mysqli_query($conn, "SELECT s.* FROM subjects s JOIN class_subjects cs ON s.id=cs.subject_id WHERE cs.class_id=$sel_class ORDER BY s.subject_name"); while ($sub = mysqli_fetch_assoc($subq)): ?>
                        <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['subject_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>CA Score (max 40)</label>
                    <input type="number" id="ca_score" name="ca_score" class="form-control" min="0" max="40" step="0.5" placeholder="0">
                </div>
                <div class="form-group">
                    <label>Exam Score (max 60)</label>
                    <input type="number" id="exam_score" name="exam_score" class="form-control" min="0" max="60" step="0.5" placeholder="0">
                </div>
                <div class="form-group">
                    <label>Total (auto)</label>
                    <input type="number" id="total_score" name="total_score" class="form-control" readonly style="background:#f8fafc;">
                </div>
                <div class="form-group">
                    <label>Grade (auto)</label>
                    <div id="grade_display" style="padding:10px;background:#f8fafc;border-radius:8px;font-weight:700;color:var(--primary);border:1.5px solid var(--border);">—</div>
                </div>
            </div>
            <div style="margin-top:12px;">
                <button type="submit" class="btn btn-accent"><i class="fa-solid fa-save"></i> Save Result</button>
            </div>
        </form>
    </div>
</div>

<!-- Results Table -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Results — <?= htmlspecialchars($sel_term) ?>, <?= htmlspecialchars($sel_session) ?></span>
        <a href="reports.php?class_id=<?= $sel_class ?>&term=<?= urlencode($sel_term) ?>&session=<?= urlencode($sel_session) ?>" class="btn btn-outline btn-sm" target="_blank">
            <i class="fa-solid fa-print"></i> Print Report
        </a>
    </div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Student</th><th>Subject</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th><th>Remark</th></tr></thead>
            <tbody>
            <?php
            $count = 0;
            while ($r = mysqli_fetch_assoc($rq)) {
                $count++;
                echo "<tr>";
                echo "<td>" . htmlspecialchars($r['fullname']) . "</td>";
                echo "<td>" . ($r['subject_name'] ?? '—') . "</td>";
                echo "<td>" . ($r['ca_score'] ?? '—') . "</td>";
                echo "<td>" . ($r['exam_score'] ?? '—') . "</td>";
                echo "<td><strong>" . ($r['total_score'] ?? '—') . "</strong></td>";
                $g = $r['grade'] ?? '';
                $gc = ['A'=>'badge-success','B'=>'badge-info','C'=>'badge-warning','D'=>'badge-warning','F'=>'badge-danger'][$g] ?? '';
                echo "<td><span class='badge $gc'>$g</span></td>";
                echo "<td>" . ($r['remark'] ?? '—') . "</td>";
                echo "</tr>";
            }
            if ($count === 0) echo "<tr><td colspan='7' style='text-align:center;padding:20px;color:var(--text-muted);'>No results yet.</td></tr>";
            ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
