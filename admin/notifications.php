<?php
// ============================================================
// ADMIN — NOTIFICATIONS / ANNOUNCEMENTS
// ============================================================
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role('admin');

$page_title = 'Notifications';

// ADD
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $title       = clean($conn, $_POST['title']);
    $message     = clean($conn, $_POST['message']);
    $target_role = clean($conn, $_POST['target_role']);
    $user_id     = (int)$_SESSION['user_id'];

    if (mysqli_query($conn, "INSERT INTO notifications (title, message, target_role, created_by) VALUES ('$title','$message','$target_role',$user_id)")) {
        alert('success', 'Notification published!');
    } else {
        alert('danger', mysqli_error($conn));
    }
    redirect('notifications.php');
}

// TOGGLE ACTIVE
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    mysqli_query($conn, "UPDATE notifications SET is_active = 1 - is_active WHERE id=$id");
    redirect('notifications.php');
}

// DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM notifications WHERE id=$id");
    alert('success', 'Notification deleted.');
    redirect('notifications.php');
}

$notifications = mysqli_query($conn, "SELECT n.*, u.username FROM notifications n LEFT JOIN users u ON n.created_by=u.id ORDER BY n.created_at DESC");

require_once '../includes/header.php';
?>

<?php show_alert(); ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
    <!-- Notifications List -->
    <div class="card">
        <div class="card-header"><span class="card-title">All Announcements</span></div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Title</th><th>Target</th><th>Created By</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (mysqli_num_rows($notifications) === 0): ?>
                    <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">No notifications yet.</td></tr>
                <?php else: ?>
                <?php while ($n = mysqli_fetch_assoc($notifications)): ?>
                <tr>
                    <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($n['title']) ?></div>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?= htmlspecialchars(substr($n['message'],0,70)) ?>...</div>
                    </td>
                    <td>
                        <?php
                        $tc = ['all'=>'badge-primary','admin'=>'badge-danger','teacher'=>'badge-success','student'=>'badge-info'];
                        echo "<span class='badge " . ($tc[$n['target_role']] ?? 'badge-info') . "'>" . ucfirst($n['target_role']) . "</span>";
                        ?>
                    </td>
                    <td><?= $n['username'] ?? '—' ?></td>
                    <td><?= date('M d, Y', strtotime($n['created_at'])) ?></td>
                    <td>
                        <span class="badge <?= $n['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                            <?= $n['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="?toggle=<?= $n['id'] ?>" class="btn btn-outline btn-sm" title="Toggle">
                                <i class="fa-solid fa-toggle-<?= $n['is_active'] ? 'on' : 'off' ?>"></i>
                            </a>
                            <button class="btn btn-danger btn-sm" onclick="confirmDelete('?delete=<?= $n['id'] ?>','this notification')">
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

    <!-- Add Notification -->
    <div class="card">
        <div class="card-header"><span class="card-title">New Announcement</span></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="Announcement title" required>
                </div>
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Message *</label>
                    <textarea name="message" class="form-control" rows="4" placeholder="Write your message here..." required></textarea>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label>Target Audience</label>
                    <select name="target_role" class="form-control">
                        <option value="all">Everyone</option>
                        <option value="student">Students Only</option>
                        <option value="teacher">Teachers Only</option>
                        <option value="admin">Admin Only</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    <i class="fa-solid fa-bullhorn"></i> Publish
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
