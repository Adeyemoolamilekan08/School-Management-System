<?php
// ============================================================
// ADMIN — REPORTS (Printable Result Sheets)
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('admin');

$page_title = 'Reports';

$sel_class   = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$sel_student = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$sel_term    = isset($_GET['term'])     ? clean($conn, $_GET['term']) : '';
$sel_session = isset($_GET['session'])  ? clean($conn, $_GET['session']) : '';

$terms    = ['First Term', 'Second Term', 'Third Term'];
$yr = (int)date('Y');
$sessions = [];
for ($y = $yr; $y >= $yr-3; $y--) $sessions[] = $y . '/' . ($y+1);

// Get result data for printing
$report_rows = [];
$student_info = null;
if ($sel_student && $sel_term && $sel_session) {
    $student_info = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT s.*, c.class_name, p.fullname as parent_name
        FROM students s
        LEFT JOIN classes c ON s.class_id=c.id
        LEFT JOIN parents p ON s.parent_id=p.id
        WHERE s.id=$sel_student
    "));

    $rq = mysqli_query($conn, "
        SELECT r.*, sub.subject_name, sub.subject_code
        FROM results r
        JOIN subjects sub ON r.subject_id=sub.id
        WHERE r.student_id=$sel_student AND r.term='$sel_term' AND r.session='$sel_session'
        ORDER BY sub.subject_name
    ");
    while ($r = mysqli_fetch_assoc($rq)) $report_rows[] = $r;

    // Class ranking
    $rank_q = mysqli_query($conn, "
        SELECT student_id, SUM(total_score) as grand_total
        FROM results
        WHERE class_id=$sel_class AND term='$sel_term' AND session='$sel_session'
        GROUP BY student_id
        ORDER BY grand_total DESC
    ");
    $rank = 1;
    $student_rank = '—';
    $student_total = 0;
    while ($rr = mysqli_fetch_assoc($rank_q)) {
        if ($rr['student_id'] == $sel_student) {
            $student_rank  = $rank;
            $student_total = $rr['grand_total'];
        }
        $rank++;
    }
    $class_size = $rank - 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports — SchoolMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            .main-wrapper { margin: 0 !important; }
            .page-content { padding: 0 !important; }
            .result-sheet { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>
<?php require_once '../includes/header.php'; ?>

<?php show_alert(); ?>

<!-- Filter Panel -->
<div class="card no-print">
    <div class="card-header"><span class="card-title">Generate Report / Result Sheet</span></div>
    <div class="card-body">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;">
            <div class="form-group" style="flex:1;min-width:150px;">
                <label>Class</label>
                <select name="class_id" class="form-control" onchange="loadStudents(this.value)">
                    <option value="">-- Select --</option>
                    <?php $c2 = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name"); while ($cl = mysqli_fetch_assoc($c2)): ?>
                    <option value="<?= $cl['id'] ?>" <?= $sel_class==$cl['id']?'selected':'' ?>><?= $cl['class_name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="flex:1;min-width:150px;">
                <label>Student</label>
                <select name="student_id" class="form-control" id="studentSelect">
                    <option value="">-- Select Student --</option>
                    <?php if ($sel_class): $sq = mysqli_query($conn, "SELECT id,fullname FROM students WHERE class_id=$sel_class ORDER BY fullname"); while ($s = mysqli_fetch_assoc($sq)): ?>
                    <option value="<?= $s['id'] ?>" <?= $sel_student==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['fullname']) ?></option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
            <div class="form-group" style="flex:1;min-width:150px;">
                <label>Term</label>
                <select name="term" class="form-control">
                    <option value="">-- Select --</option>
                    <?php foreach ($terms as $t): ?>
                    <option value="<?= $t ?>" <?= $sel_term==$t?'selected':'' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="flex:1;min-width:150px;">
                <label>Session</label>
                <select name="session" class="form-control">
                    <option value="">-- Select --</option>
                    <?php foreach ($sessions as $s): ?>
                    <option value="<?= $s ?>" <?= $sel_session==$s?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="align-self:flex-end;">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> Generate</button>
            </div>
        </form>
    </div>
</div>

<?php if ($student_info && count($report_rows) > 0): ?>

<!-- Printable Result Sheet -->
<div class="card result-sheet" style="max-width:800px;margin:0 auto;">
    <!-- Print Button -->
    <div class="no-print" style="padding:16px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px;">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fa-solid fa-print"></i> Print Result Sheet
        </button>
    </div>

    <div style="padding:30px;">
        <!-- Header -->
        <div style="text-align:center;margin-bottom:20px;border-bottom:3px double var(--primary);padding-bottom:16px;">
            <i class="fa-solid fa-graduation-cap" style="font-size:40px;color:var(--primary);"></i>
            <h1 style="font-size:22px;font-weight:800;color:var(--primary);margin:6px 0 2px;">SCHOOLMS SECONDARY SCHOOL</h1>
            <p style="color:var(--text-muted);font-size:13px;">Academic Excellence — Character Development — Future Leaders</p>
            <h2 style="font-size:15px;margin-top:10px;color:var(--text);border:2px solid var(--primary);display:inline-block;padding:4px 20px;border-radius:4px;">
                STUDENT REPORT CARD
            </h2>
        </div>

        <!-- Student Info -->
        <div class="result-student-info" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;background:#f8fafc;border-radius:8px;padding:16px;margin-bottom:20px;">
            <div style="font-size:13px;"><strong style="color:var(--primary);">Student Name:</strong> <?= htmlspecialchars($student_info['fullname']) ?></div>
            <div style="font-size:13px;"><strong style="color:var(--primary);">Student ID:</strong> <?= $student_info['student_id'] ?></div>
            <div style="font-size:13px;"><strong style="color:var(--primary);">Class:</strong> <?= $student_info['class_name'] ?></div>
            <div style="font-size:13px;"><strong style="color:var(--primary);">Term:</strong> <?= htmlspecialchars($sel_term) ?></div>
            <div style="font-size:13px;"><strong style="color:var(--primary);">Session:</strong> <?= htmlspecialchars($sel_session) ?></div>
            <div style="font-size:13px;"><strong style="color:var(--primary);">Parent/Guardian:</strong> <?= htmlspecialchars($student_info['parent_name'] ?? '—') ?></div>
            <div style="font-size:13px;"><strong style="color:var(--primary);">Class Position:</strong>
                <span style="font-weight:800;color:var(--accent);"><?= $student_rank ?> of <?= $class_size ?></span>
            </div>
            <div style="font-size:13px;"><strong style="color:var(--primary);">Total Score:</strong>
                <span style="font-weight:800;"><?= number_format($student_total, 1) ?></span>
            </div>
        </div>

        <!-- Results Table -->
        <table style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:20px;">
            <thead>
                <tr style="background:var(--primary);color:#fff;">
                    <th style="padding:10px;text-align:left;border:1px solid #ddd;">Subject</th>
                    <th style="padding:10px;text-align:center;border:1px solid #ddd;">CA (40)</th>
                    <th style="padding:10px;text-align:center;border:1px solid #ddd;">Exam (60)</th>
                    <th style="padding:10px;text-align:center;border:1px solid #ddd;">Total (100)</th>
                    <th style="padding:10px;text-align:center;border:1px solid #ddd;">Grade</th>
                    <th style="padding:10px;text-align:center;border:1px solid #ddd;">Remark</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $grand_total = 0;
            $subject_count = 0;
            foreach ($report_rows as $i => $row):
                $grand_total += $row['total_score'];
                $subject_count++;
                $bg = $i % 2 === 0 ? '#fff' : '#f8fafc';
                $grade_color = ['A'=>'#065f46','B'=>'#1e40af','C'=>'#92400e','D'=>'#713f12','F'=>'#991b1b'][$row['grade']] ?? '#333';
            ?>
            <tr style="background:<?= $bg ?>;">
                <td style="padding:9px 10px;border:1px solid #eee;"><?= htmlspecialchars($row['subject_name']) ?> <span style="color:#94a3b8;font-size:11px;">(<?= $row['subject_code'] ?>)</span></td>
                <td style="padding:9px 10px;border:1px solid #eee;text-align:center;"><?= $row['ca_score'] ?></td>
                <td style="padding:9px 10px;border:1px solid #eee;text-align:center;"><?= $row['exam_score'] ?></td>
                <td style="padding:9px 10px;border:1px solid #eee;text-align:center;font-weight:700;"><?= $row['total_score'] ?></td>
                <td style="padding:9px 10px;border:1px solid #eee;text-align:center;font-weight:800;color:<?= $grade_color ?>;"><?= $row['grade'] ?></td>
                <td style="padding:9px 10px;border:1px solid #eee;text-align:center;"><?= $row['remark'] ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="background:#1a237e;color:#fff;font-weight:700;">
                <td style="padding:10px;border:1px solid #ddd;" colspan="3">GRAND TOTAL</td>
                <td style="padding:10px;border:1px solid #ddd;text-align:center;"><?= number_format($grand_total, 1) ?></td>
                <td colspan="2" style="padding:10px;border:1px solid #ddd;text-align:center;">
                    Average: <?= $subject_count > 0 ? number_format($grand_total/$subject_count, 1) : 0 ?>%
                </td>
            </tr>
            </tbody>
        </table>

        <!-- Grade Key -->
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
            <strong style="font-size:12px;">GRADE KEY:</strong>
            <span style="font-size:12px;color:#065f46;"><strong>A</strong> = 70–100 (Excellent)</span>
            <span style="font-size:12px;color:#1e40af;"><strong>B</strong> = 60–69 (Very Good)</span>
            <span style="font-size:12px;color:#92400e;"><strong>C</strong> = 50–59 (Good)</span>
            <span style="font-size:12px;color:#713f12;"><strong>D</strong> = 40–49 (Pass)</span>
            <span style="font-size:12px;color:#991b1b;"><strong>F</strong> = 0–39 (Fail)</span>
        </div>

        <!-- Signature Line -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:30px;margin-top:30px;">
            <div style="border-top:1px solid #333;padding-top:6px;text-align:center;font-size:12px;color:var(--text-muted);">Class Teacher's Signature</div>
            <div style="border-top:1px solid #333;padding-top:6px;text-align:center;font-size:12px;color:var(--text-muted);">Principal's Signature</div>
        </div>

        <div style="text-align:center;margin-top:20px;font-size:11px;color:var(--text-muted);border-top:1px dashed #ddd;padding-top:12px;">
            Generated by SchoolMS — <?= date('F d, Y \a\t H:i') ?>
        </div>
    </div>
</div>

<?php elseif ($sel_student && $sel_term && $sel_session): ?>
<div class="card">
    <div class="card-body" style="text-align:center;padding:40px;color:var(--text-muted);">
        <i class="fa-solid fa-file-circle-xmark" style="font-size:48px;margin-bottom:12px;opacity:0.4;"></i>
        <p>No results found for the selected student, term and session.</p>
    </div>
</div>
<?php endif; ?>

<script>
// Dynamically load students when class changes
function loadStudents(classId) {
    if (!classId) return;
    var sel = document.getElementById('studentSelect');
    sel.innerHTML = '<option value="">Loading...</option>';

    // Simple reload approach — submit the form with just class_id to reload students list
    var form = sel.closest('form');
    var termVal = form.querySelector('[name=term]').value;
    var sessionVal = form.querySelector('[name=session]').value;
    window.location.href = 'reports.php?class_id=' + classId + '&term=' + encodeURIComponent(termVal) + '&session=' + encodeURIComponent(sessionVal);
}
</script>

<?php require_once '../includes/footer.php'; ?>
