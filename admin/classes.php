<?php
// ============================================================
// ADMIN — CLASS MANAGEMENT
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('admin');

$page_title = 'Classes';

// ADD CLASS
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $class_name  = clean($conn, $_POST['class_name']);
    $description = clean($conn, $_POST['description']);
    if (mysqli_query($conn, "INSERT INTO classes (class_name, description) VALUES ('$class_name','$description')")) {
        alert('success', 'Class added!');
    } else {
        alert('danger', mysqli_error($conn));
    }
    redirect('classes.php');
}

// DELETE CLASS
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM classes WHERE id=$id");
    alert('success', 'Class deleted.');
    redirect('classes.php');
}

// ASSIGN SUBJECT TO CLASS
if (isset($_POST['action']) && $_POST['action'] === 'assign') {
    $class_id   = (int)$_POST['class_id'];
    $subject_id = (int)$_POST['subject_id'];
    $teacher_id = (int)$_POST['teacher_id'];
    $sql = "INSERT IGNORE INTO class_subjects (class_id, subject_id, teacher_id) VALUES ($class_id,$subject_id,$teacher_id)";
    if (mysqli_query($conn, $sql)) { alert('success', 'Subject assigned to class!'); }
    else                           { alert('danger', mysqli_error($conn)); }
    redirect('classes.php');
}

// REMOVE CLASS-SUBJECT
if (isset($_GET['unassign'])) {
    $id = (int)$_GET['unassign'];
    mysqli_query($conn, "DELETE FROM class_subjects WHERE id=$id");
    alert('success', 'Removed.');
    redirect('classes.php');
}

$classes    = mysqli_query($conn, "SELECT c.*, (SELECT COUNT(*) FROM students WHERE class_id=c.id) as student_count FROM classes c ORDER BY c.class_name");
$subjects   = mysqli_query($conn, "SELECT * FROM subjects ORDER BY subject_name");
$teachers   = mysqli_query($conn, "SELECT * FROM teachers ORDER BY fullname");
$class_subj = mysqli_query($conn, "SELECT cs.*, c.class_name, s.subject_name, t.fullname as teacher_name FROM class_subjects cs
    JOIN classes c ON cs.class_id=c.id
    JOIN subjects s ON cs.subject_id=s.id
    LEFT JOIN teachers t ON cs.teacher_id=t.id ORDER BY c.class_name, s.subject_name");

require_once '../includes/header.php';
?>

<?php show_alert(); ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <!-- Classes List -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">All Classes</span>
            <button class="btn btn-primary btn-sm" onclick="openModal('addClassModal')">
                <i class="fa-solid fa-plus"></i> Add
            </button>
        </div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Class</th><th>Description</th><th>Students</th><th>Action</th></tr></thead>
                <tbody>
                <?php while ($cl = mysqli_fetch_assoc($classes)): ?>
                <tr>
                    <td><strong><?= $cl['class_name'] ?></strong></td>
                    <td><?= $cl['description'] ?></td>
                    <td><span class="badge badge-info"><?= $cl['student_count'] ?></span></td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="confirmDelete('?delete=<?= $cl['id'] ?>','<?= $cl['class_name'] ?>')">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Assign Subject to Class -->
    <div class="card">
        <div class="card-header"><span class="card-title">Assign Subject to Class</span></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="assign">
                <div class="form-group" style="margin-bottom:12px;">
                    <label>Class</label>
                    <select name="class_id" class="form-control" required>
                        <option value="">-- Select Class --</option>
                        <?php $c2 = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name"); while ($cl = mysqli_fetch_assoc($c2)): ?>
                        <option value="<?= $cl['id'] ?>"><?= $cl['class_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:12px;">
                    <label>Subject</label>
                    <select name="subject_id" class="form-control" required>
                        <option value="">-- Select Subject --</option>
                        <?php $s2 = mysqli_query($conn, "SELECT * FROM subjects ORDER BY subject_name"); while ($sub = mysqli_fetch_assoc($s2)): ?>
                        <option value="<?= $sub['id'] ?>"><?= $sub['subject_name'] ?> (<?= $sub['subject_code'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label>Assigned Teacher (optional)</label>
                    <select name="teacher_id" class="form-control">
                        <option value="0">-- No Teacher --</option>
                        <?php $t2 = mysqli_query($conn, "SELECT * FROM teachers ORDER BY fullname"); while ($tch = mysqli_fetch_assoc($t2)): ?>
                        <option value="<?= $tch['id'] ?>"><?= htmlspecialchars($tch['fullname']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-accent"><i class="fa-solid fa-link"></i> Assign</button>
            </form>
        </div>
    </div>
</div>

<!-- Class-Subject Assignments -->
<div class="card" style="margin-top:20px;">
    <div class="card-header"><span class="card-title">Class Subject Assignments</span></div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Class</th><th>Subject</th><th>Teacher</th><th>Action</th></tr></thead>
            <tbody>
            <?php while ($cs = mysqli_fetch_assoc($class_subj)): ?>
            <tr>
                <td><span class="badge badge-primary"><?= $cs['class_name'] ?></span></td>
                <td><?= $cs['subject_name'] ?></td>
                <td><?= $cs['teacher_name'] ?? '—' ?></td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="confirmDelete('?unassign=<?= $cs['id'] ?>','this assignment')">
                        <i class="fa-solid fa-unlink"></i>
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ADD CLASS MODAL -->
<div class="modal-overlay" id="addClassModal">
    <div class="modal">
        <div class="modal-header">Add Class <button class="modal-close" onclick="closeModal('addClassModal')"><i class="fa-solid fa-xmark"></i></button></div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group" style="margin-bottom:12px;">
                    <label>Class Name *</label>
                    <input type="text" name="class_name" class="form-control" placeholder="e.g. JSS1, SS2" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" class="form-control" placeholder="e.g. Junior Secondary School 1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addClassModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
