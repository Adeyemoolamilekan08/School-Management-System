<?php
// ============================================================
// ADMIN — SUBJECTS MANAGEMENT
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('admin');

$page_title = 'Subjects';

if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = clean($conn, $_POST['subject_name']);
    $code = clean($conn, $_POST['subject_code']);
    if (mysqli_query($conn, "INSERT INTO subjects (subject_name, subject_code) VALUES ('$name','$code')")) {
        alert('success', 'Subject added!');
    } else { alert('danger', mysqli_error($conn)); }
    redirect('subjects.php');
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM subjects WHERE id=$id");
    alert('success', 'Subject deleted.');
    redirect('subjects.php');
}

$subjects = mysqli_query($conn, "SELECT s.*, COUNT(cs.id) as class_count FROM subjects s LEFT JOIN class_subjects cs ON s.id=cs.subject_id GROUP BY s.id ORDER BY s.subject_name");

require_once '../includes/header.php';
?>

<?php show_alert(); ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
    <div class="card">
        <div class="card-header"><span class="card-title">All Subjects</span></div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Subject Name</th><th>Code</th><th>Classes Using</th><th>Action</th></tr></thead>
                <tbody>
                <?php while ($s = mysqli_fetch_assoc($subjects)): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($s['subject_name']) ?></strong></td>
                    <td><code><?= $s['subject_code'] ?></code></td>
                    <td><span class="badge badge-info"><?= $s['class_count'] ?></span></td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="confirmDelete('?delete=<?= $s['id'] ?>','<?= htmlspecialchars($s['subject_name']) ?>')">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Add Subject</span></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group" style="margin-bottom:12px;">
                    <label>Subject Name *</label>
                    <input type="text" name="subject_name" class="form-control" placeholder="e.g. Mathematics" required>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label>Subject Code *</label>
                    <input type="text" name="subject_code" class="form-control" placeholder="e.g. MTH" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    <i class="fa-solid fa-plus"></i> Add Subject
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
