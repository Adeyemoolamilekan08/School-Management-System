<?php
// ============================================================
// ADMIN — TEACHER MANAGEMENT
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('admin');

$page_title = 'Teachers';

// ADD TEACHER
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $fullname      = clean($conn, $_POST['fullname']);
    $gender        = clean($conn, $_POST['gender']);
    $email         = clean($conn, $_POST['email']);
    $phone         = clean($conn, $_POST['phone']);
    $qualification = clean($conn, $_POST['qualification']);
    $username      = clean($conn, $_POST['username']);
    $password      = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $teacher_id    = generate_id('TCH', $conn, 'teachers', 'teacher_id');

    // Check for duplicate username before inserting
    $ucheck = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' LIMIT 1");
    if (mysqli_num_rows($ucheck) > 0) {
        alert('danger', 'Username "' . htmlspecialchars($username) . '" is already taken. Please choose a different username.');
        redirect('teachers.php');
    }

    $ins_user = mysqli_query($conn, "INSERT INTO users (username, password, role) VALUES ('$username','$password','teacher')");
    if (!$ins_user) {
        alert('danger', 'Could not create login account: ' . mysqli_error($conn));
        redirect('teachers.php');
    }
    $user_id = mysqli_insert_id($conn);

    $sql = "INSERT INTO teachers (teacher_id, fullname, gender, email, phone, qualification, user_id)
            VALUES ('$teacher_id','$fullname','$gender','$email','$phone','$qualification',$user_id)";

    if (mysqli_query($conn, $sql)) {
        alert('success', 'Teacher added successfully! Login username: ' . htmlspecialchars($username));
    } else {
        // Roll back the user account if teacher insert fails
        mysqli_query($conn, "DELETE FROM users WHERE id=$user_id");
        alert('danger', 'Error saving teacher: ' . mysqli_error($conn));
    }
    redirect('teachers.php');
}

// EDIT TEACHER
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id            = (int)$_POST['id'];
    $fullname      = clean($conn, $_POST['fullname']);
    $gender        = clean($conn, $_POST['gender']);
    $email         = clean($conn, $_POST['email']);
    $phone         = clean($conn, $_POST['phone']);
    $qualification = clean($conn, $_POST['qualification']);

    mysqli_query($conn, "UPDATE teachers SET fullname='$fullname',gender='$gender',email='$email',phone='$phone',qualification='$qualification' WHERE id=$id");
    alert('success', 'Teacher updated!');
    redirect('teachers.php');
}

// DELETE TEACHER
if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_id FROM teachers WHERE id=$id"));
    mysqli_query($conn, "DELETE FROM teachers WHERE id=$id");
    if ($row['user_id']) mysqli_query($conn, "DELETE FROM users WHERE id=" . $row['user_id']);
    alert('success', 'Teacher deleted.');
    redirect('teachers.php');
}

// Load
$teachers = mysqli_query($conn, "SELECT * FROM teachers ORDER BY fullname");
$edit_teacher = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $edit_teacher = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM teachers WHERE id=$eid"));
}

require_once '../includes/header.php';
?>

<?php show_alert(); ?>

<div class="card">
    <div class="card-header">
        <span class="card-title">All Teachers</span>
        <button class="btn btn-primary" onclick="openModal('addTeacherModal')">
            <i class="fa-solid fa-plus"></i> Add Teacher
        </button>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>Teacher</th><th>ID</th><th>Gender</th><th>Email</th><th>Phone</th><th>Qualification</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($teachers) === 0): ?>
                <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-muted);">No teachers found.</td></tr>
            <?php else: ?>
            <?php while ($t = mysqli_fetch_assoc($teachers)): ?>
                <tr>
                    <td>
                        <div class="table-avatar">
                            <div class="avatar-circle" style="background:var(--success);"><?= strtoupper(substr($t['fullname'],0,1)) ?></div>
                            <?= htmlspecialchars($t['fullname']) ?>
                        </div>
                    </td>
                    <td><code><?= $t['teacher_id'] ?></code></td>
                    <td><?= $t['gender'] ?></td>
                    <td><?= $t['email'] ?></td>
                    <td><?= $t['phone'] ?></td>
                    <td><?= $t['qualification'] ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="?edit=<?= $t['id'] ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-pen"></i></a>
                            <button class="btn btn-danger btn-sm" onclick="confirmDelete('?delete=<?= $t['id'] ?>','<?= htmlspecialchars($t['fullname']) ?>')">
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

<!-- ADD TEACHER MODAL -->
<div class="modal-overlay" id="addTeacherModal">
    <div class="modal">
        <div class="modal-header">
            Add New Teacher
            <button class="modal-close" onclick="closeModal('addTeacherModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group" style="grid-column:1/-1;">
                        <label>Full Name *</label>
                        <input type="text" name="fullname" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Gender *</label>
                        <select name="gender" class="form-control">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Qualification</label>
                        <input type="text" name="qualification" class="form-control" placeholder="e.g. B.Ed, M.Sc">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control">
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
                <button type="button" class="btn btn-outline" onclick="closeModal('addTeacherModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Teacher</button>
            </div>
        </form>
    </div>
</div>

<?php if ($edit_teacher): ?>
<div class="modal-overlay active" id="editTeacherModal">
    <div class="modal">
        <div class="modal-header">Edit Teacher <a href="teachers.php" class="modal-close"><i class="fa-solid fa-xmark"></i></a></div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= $edit_teacher['id'] ?>">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group" style="grid-column:1/-1;">
                        <label>Full Name *</label>
                        <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($edit_teacher['fullname']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="Male" <?= $edit_teacher['gender']=='Male'?'selected':'' ?>>Male</option>
                            <option value="Female" <?= $edit_teacher['gender']=='Female'?'selected':'' ?>>Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Qualification</label>
                        <input type="text" name="qualification" class="form-control" value="<?= htmlspecialchars($edit_teacher['qualification']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($edit_teacher['email']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($edit_teacher['phone']) ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="teachers.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Update</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
