<?php
// ============================================================
// ADMIN — PARENTS MANAGEMENT
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('admin');

$page_title = 'Parents / Guardians';

if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $fullname   = clean($conn, $_POST['fullname']);
    $phone      = clean($conn, $_POST['phone']);
    $email      = clean($conn, $_POST['email']);
    $address    = clean($conn, $_POST['address']);
    $occupation = clean($conn, $_POST['occupation']);

    if (mysqli_query($conn, "INSERT INTO parents (fullname,phone,email,address,occupation) VALUES ('$fullname','$phone','$email','$address','$occupation')")) {
        alert('success', 'Parent added!');
    } else { alert('danger', mysqli_error($conn)); }
    redirect('parents.php');
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM parents WHERE id=$id");
    alert('success', 'Parent record deleted.');
    redirect('parents.php');
}

$parents = mysqli_query($conn, "SELECT p.*, COUNT(s.id) as child_count FROM parents p LEFT JOIN students s ON p.id=s.parent_id GROUP BY p.id ORDER BY p.fullname");

require_once '../includes/header.php';
?>

<?php show_alert(); ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
    <div class="card">
        <div class="card-header"><span class="card-title">All Parents / Guardians</span></div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Occupation</th><th>Children</th><th>Action</th></tr></thead>
                <tbody>
                <?php while ($p = mysqli_fetch_assoc($parents)): ?>
                <tr>
                    <td><?= htmlspecialchars($p['fullname']) ?></td>
                    <td><?= $p['phone'] ?></td>
                    <td><?= $p['email'] ?></td>
                    <td><?= $p['occupation'] ?></td>
                    <td><span class="badge badge-success"><?= $p['child_count'] ?></span></td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="confirmDelete('?delete=<?= $p['id'] ?>','<?= htmlspecialchars($p['fullname']) ?>')">
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
        <div class="card-header"><span class="card-title">Add Parent / Guardian</span></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Full Name *</label>
                    <input type="text" name="fullname" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Occupation</label>
                    <input type="text" name="occupation" class="form-control">
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    <i class="fa-solid fa-plus"></i> Add Parent
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
