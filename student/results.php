<?php
// ============================================================
// STUDENT — MY RESULTS
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('student');

$page_title = 'My Results';

$user_id = (int)$_SESSION['user_id'];
$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, c.class_name FROM students s LEFT JOIN classes c ON s.class_id=c.id WHERE s.user_id=$user_id"));
$student_id = (int)$student['id'];
$class_id   = (int)$student['class_id'];

$terms    = ['First Term', 'Second Term', 'Third Term'];
$yr = (int)date('Y');
$sessions = [];
for ($y = $yr; $y >= $yr-3; $y--) $sessions[] = $y . '/' . ($y+1);

$sel_term    = isset($_GET['term'])    ? clean($conn, $_GET['term'])    : 'First Term';
$sel_session = isset($_GET['session']) ? clean($conn, $_GET['session']) : ($sessions[0] ?? '');

// My results
$results = mysqli_query($conn, "
    SELECT r.*, s.subject_name, s.subject_code
    FROM results r
    JOIN subjects s ON r.subject_id=s.id
    WHERE r.student_id=$student_id AND r.term='$sel_term' AND r.session='$sel_session'
    ORDER BY s.subject_name
");

// Class ranking
$rank_q = mysqli_query($conn, "
    SELECT student_id, SUM(total_score) as grand_total
    FROM results
    WHERE class_id=$class_id AND term='$sel_term' AND session='$sel_session'
    GROUP BY student_id ORDER BY grand_total DESC
");
$rank = 1; $my_rank = '—'; $my_total = 0; $class_size = 0;
while ($rr = mysqli_fetch_assoc($rank_q)) {
    $class_size++;
    if ($rr['student_id'] == $student_id) { $my_rank = $rank; $my_total = $rr['grand_total']; }
    $rank++;
}

require_once '../includes/header.php';
?>

<!-- Term Filter -->
<div class="card no-print" style="margin-bottom:20px;">
    <div class="card-body">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="flex:1;min-width:160px;">
                <label>Term</label>
                <select name="term" class="form-control">
                    <?php foreach ($terms as $t): ?>
                    <option value="<?= $t ?>" <?= $sel_term==$t?'selected':'' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="flex:1;min-width:160px;">
                <label>Session</label>
                <select name="session" class="form-control">
                    <?php foreach ($sessions as $s): ?>
                    <option value="<?= $s ?>" <?= $sel_session==$s?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> View</button>
                <button type="button" onclick="window.print()" class="btn btn-outline"><i class="fa-solid fa-print"></i> Print</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);">
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-trophy"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $my_rank ?> <span style="font-size:14px;font-weight:400;">of <?= $class_size ?></span></div>
            <div class="stat-label">Class Position</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-calculator"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($my_total, 1) ?></div>
            <div class="stat-label">Grand Total</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-percent"></i></div>
        <div class="stat-info">
            <?php $subj_count = mysqli_num_rows($results); $avg = $subj_count > 0 ? round($my_total / $subj_count, 1) : 0; ?>
            <div class="stat-value"><?= $avg ?>%</div>
            <div class="stat-label">Average Score</div>
        </div>
    </div>
</div>

<!-- Results Table -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Results — <?= htmlspecialchars($sel_term) ?>, <?= htmlspecialchars($sel_session) ?></span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>Subject</th><th>Code</th><th>CA (40)</th><th>Exam (60)</th><th>Total</th><th>Grade</th><th>Remark</th></tr>
            </thead>
            <tbody>
            <?php
            // Reset pointer
            mysqli_data_seek($results, 0);
            $count = 0;
            while ($r = mysqli_fetch_assoc($results)):
                $count++;
                $grade_colors = ['A'=>'badge-success','B'=>'badge-info','C'=>'badge-warning','D'=>'badge-warning','F'=>'badge-danger'];
                $gc = $grade_colors[$r['grade']] ?? 'badge-info';
            ?>
            <tr>
                <td><?= htmlspecialchars($r['subject_name']) ?></td>
                <td><code><?= $r['subject_code'] ?></code></td>
                <td><?= $r['ca_score'] ?></td>
                <td><?= $r['exam_score'] ?></td>
                <td><strong><?= $r['total_score'] ?></strong></td>
                <td><span class="badge <?= $gc ?>"><?= $r['grade'] ?></span></td>
                <td><?= $r['remark'] ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if ($count === 0): ?>
                <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--text-muted);">
                    No results available for <?= htmlspecialchars($sel_term) ?>, <?= htmlspecialchars($sel_session) ?>.
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
