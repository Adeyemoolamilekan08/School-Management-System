<?php
// ============================================================
// ADMIN — STUDENT MANAGEMENT
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('admin');

$page_title = 'Students';

// ---- Handle form actions ----

// ADD STUDENT
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $fullname  = clean($conn, $_POST['fullname']);
    $class_id  = (int)$_POST['class_id'];
    $gender    = clean($conn, $_POST['gender']);
    $dob       = clean($conn, $_POST['date_of_birth']);
    $parent_id = (int)$_POST['parent_id'];
    $username  = clean($conn, $_POST['username']);
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $student_id = generate_id('STU', $conn, 'students', 'student_id');

    // Check if username already exists
    $ucheck = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' LIMIT 1");
    if (mysqli_num_rows($ucheck) > 0) {
        alert('danger', 'Username "' . htmlspecialchars($username) . '" is already taken. Please choose a different username.');
        redirect('students.php');
    }

    // Upload photo
    $photo = 'default.png';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $ext   = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowed)) {
            $photo = 'stu_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], '../uploads/students/' . $photo);
        }
    }

    // Create login account first
    $ins_user = mysqli_query($conn, "INSERT INTO users (username, password, role) VALUES ('$username', '$password', 'student')");
    if (!$ins_user) {
        alert('danger', 'Could not create login account: ' . mysqli_error($conn));
        redirect('students.php');
    }
    $user_id = mysqli_insert_id($conn);

    // Now insert student record
    $sql = "INSERT INTO students (student_id, fullname, class_id, gender, date_of_birth, parent_id, photo, user_id)
            VALUES ('$student_id','$fullname',$class_id,'$gender','$dob',$parent_id,'$photo',$user_id)";

    if (mysqli_query($conn, $sql)) {
        alert('success', 'Student added successfully! Login username: ' . htmlspecialchars($username));
    } else {
        // Roll back the user we just created since student insert failed
        mysqli_query($conn, "DELETE FROM users WHERE id=$user_id");
        alert('danger', 'Error saving student: ' . mysqli_error($conn));
    }
    redirect('students.php');
}

// EDIT STUDENT
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id        = (int)$_POST['id'];
    $fullname  = clean($conn, $_POST['fullname']);
    $class_id  = (int)$_POST['class_id'];
    $gender    = clean($conn, $_POST['gender']);
    $dob       = clean($conn, $_POST['date_of_birth']);
    $parent_id = (int)$_POST['parent_id'];

    mysqli_query($conn, "UPDATE students SET fullname='$fullname', class_id=$class_id, gender='$gender', date_of_birth='$dob', parent_id=$parent_id WHERE id=$id");
    alert('success', 'Student updated successfully!');
    redirect('students.php');
}

// DELETE STUDENT
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Get user_id first
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_id FROM students WHERE id=$id"));
    mysqli_query($conn, "DELETE FROM students WHERE id=$id");
    if ($row['user_id']) mysqli_query($conn, "DELETE FROM users WHERE id=" . $row['user_id']);
    alert('success', 'Student deleted.');
    redirect('students.php');
}

// ---- Load data ----
$search = isset($_GET['q']) ? clean($conn, $_GET['q']) : '';
$where  = '';
if ($search) {
    $where = "WHERE s.fullname LIKE '%$search%' OR s.student_id LIKE '%$search%' OR c.class_name LIKE '%$search%'";
}

$students = mysqli_query($conn, "
    SELECT s.*, c.class_name, p.fullname AS parent_name
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN parents p ON s.parent_id = p.id
    $where
    ORDER BY s.created_at DESC
");

$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");
$parents = mysqli_query($conn, "SELECT * FROM parents ORDER BY fullname");

// Fetch student for editing
$edit_student = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $edit_student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE id=$eid"));
}

require_once '../includes/header.php';
?>

<?php show_alert(); ?>

<div class="card">
    <div class="card-header">
        <span class="card-title">All Students</span>
        <div class="btn-group">
            <!-- Search -->
            <form method="GET" class="search-bar">
                <input type="text" name="q" id="tableSearch" placeholder="Search by name, ID, class..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            <button class="btn btn-primary" onclick="openModal('addStudentModal')">
                <i class="fa-solid fa-plus"></i> Add Student
            </button>
        </div>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>ID</th>
                    <th>Class</th>
                    <th>Gender</th>
                    <th>Parent</th>
                    <th>Date of Birth</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $c = mysqli_num_rows($students); ?>
            <?php if ($c === 0): ?>
                <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-muted);">No students found.</td></tr>
            <?php else: ?>
            <?php while ($s = mysqli_fetch_assoc($students)): ?>
                <tr>
                    <td>
                        <div class="table-avatar">
                            <div class="avatar-circle"><?= strtoupper(substr($s['fullname'],0,1)) ?></div>
                            <span><?= htmlspecialchars($s['fullname']) ?></span>
                        </div>
                    </td>
                    <td><code><?= $s['student_id'] ?></code></td>
                    <td><?= $s['class_name'] ?? '—' ?></td>
                    <td><?= $s['gender'] ?></td>
                    <td><?= $s['parent_name'] ?? '—' ?></td>
                    <td><?= $s['date_of_birth'] ? date('M d, Y', strtotime($s['date_of_birth'])) : '—' ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="?edit=<?= $s['id'] ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-pen"></i></a>
                            <button class="btn btn-danger btn-sm" onclick="confirmDelete('?delete=<?= $s['id'] ?>', '<?= htmlspecialchars($s['fullname']) ?>')">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ADD STUDENT MODAL -->
<div class="modal-overlay" id="addStudentModal">
    <div class="modal">
        <div class="modal-header">
            <span>Add New Student</span>
            <button class="modal-close" onclick="closeModal('addStudentModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group" style="grid-column:1/-1;">
                        <label>Full Name *</label>
                        <input type="text" name="fullname" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Class *</label>
                        <select name="class_id" class="form-control" required>
                            <option value="">-- Select Class --</option>
                            <?php $c2 = mysqli_query($conn, "SELECT * FROM classes"); while ($cl = mysqli_fetch_assoc($c2)): ?>
                            <option value="<?= $cl['id'] ?>"><?= $cl['class_name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Gender *</label>
                        <select name="gender" class="form-control" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Parent/Guardian</label>
                        <select name="parent_id" class="form-control">
                            <option value="0">-- None --</option>
                            <?php $pq = mysqli_query($conn, "SELECT * FROM parents"); while ($pr = mysqli_fetch_assoc($pq)): ?>
                            <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['fullname']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Photo</label>
                        <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
                        <img id="photoPreview" style="display:none;max-width:80px;margin-top:6px;border-radius:6px;">
                    </div>
                    <div class="form-group">
                        <label>Login Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Login Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addStudentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Student</button>
            </div>
        </form>
    </div>
</div>

<?php if ($edit_student): ?>
<!-- EDIT STUDENT MODAL (auto-open) -->
<div class="modal-overlay active" id="editStudentModal">
    <div class="modal">
        <div class="modal-header">
            <span>Edit Student</span>
            <a href="students.php" class="modal-close"><i class="fa-solid fa-xmark"></i></a>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= $edit_student['id'] ?>">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group" style="grid-column:1/-1;">
                        <label>Full Name *</label>
                        <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($edit_student['fullname']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Class *</label>
                        <select name="class_id" class="form-control" required>
                            <?php $c3 = mysqli_query($conn, "SELECT * FROM classes"); while ($cl = mysqli_fetch_assoc($c3)): ?>
                            <option value="<?= $cl['id'] ?>" <?= $edit_student['class_id'] == $cl['id'] ? 'selected' : '' ?>><?= $cl['class_name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Gender *</label>
                        <select name="gender" class="form-control">
                            <option value="Male" <?= $edit_student['gender']=='Male'?'selected':'' ?>>Male</option>
                            <option value="Female" <?= $edit_student['gender']=='Female'?'selected':'' ?>>Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?= $edit_student['date_of_birth'] ?>">
                    </div>
                    <div class="form-group">
                        <label>Parent/Guardian</label>
                        <select name="parent_id" class="form-control">
                            <option value="0">-- None --</option>
                            <?php $pq2 = mysqli_query($conn, "SELECT * FROM parents"); while ($pr2 = mysqli_fetch_assoc($pq2)): ?>
                            <option value="<?= $pr2['id'] ?>" <?= $edit_student['parent_id'] == $pr2['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pr2['fullname']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="students.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Update Student</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
