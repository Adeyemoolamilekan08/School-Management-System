<?php
// ============================================================
// LOGIN PAGE
// ============================================================
session_start();
require_once 'config/db.php';

// If already logged in, redirect to their dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin')        redirect(BASE_URL . '/admin/dashboard.php');
    elseif ($_SESSION['user_role'] === 'teacher')  redirect(BASE_URL . '/teacher/dashboard.php');
    else                                            redirect(BASE_URL . '/student/dashboard.php');
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($conn, $_POST['username']);
    $password = $_POST['password']; // Don't clean password before verify

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        // Fetch user from database
        $sql  = "SELECT * FROM users WHERE username = '$username' LIMIT 1";
        $result = mysqli_query($conn, $sql);
        $user   = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['user_role'] = $user['role'];

            // Get full name based on role
            if ($user['role'] === 'admin') {
                $_SESSION['user_name'] = 'Administrator';
                redirect(BASE_URL . '/admin/dashboard.php');
            } elseif ($user['role'] === 'teacher') {
                $tq = mysqli_query($conn, "SELECT fullname FROM teachers WHERE user_id = {$user['id']} LIMIT 1");
                $tr = mysqli_fetch_assoc($tq);
                $_SESSION['user_name'] = $tr['fullname'] ?? 'Teacher';
                redirect(BASE_URL . '/teacher/dashboard.php');
            } else {
                $sq = mysqli_query($conn, "SELECT fullname, id FROM students WHERE user_id = {$user['id']} LIMIT 1");
                $sr = mysqli_fetch_assoc($sq);
                $_SESSION['user_name']       = $sr['fullname'] ?? 'Student';
                $_SESSION['student_db_id']   = $sr['id'] ?? null;
                redirect(BASE_URL . '/student/dashboard.php');
            }
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SchoolMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <i class="fa-solid fa-graduation-cap"></i>
            <h1>SchoolMS</h1>
            <p>School Management System</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group" style="margin-bottom:16px;">
                <label>Username</label>
                <div style="position:relative;">
                    <i class="fa-solid fa-user" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;"></i>
                    <input type="text" name="username" class="form-control" style="padding-left:38px;" placeholder="Enter your username" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>
            </div>

            <div class="form-group" style="margin-bottom:24px;">
                <label>Password</label>
                <div style="position:relative;">
                    <i class="fa-solid fa-lock" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;"></i>
                    <input type="password" name="password" id="passwordField" class="form-control" style="padding-left:38px;" placeholder="Enter your password" required>
                    <button type="button" onclick="togglePwd()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;">
                        <i class="fa-solid fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In
            </button>
        </form>

        <div style="text-align:center;margin-top:24px;color:#94a3b8;font-size:12px;">
            <p>Default admin: <strong>admin</strong> / <strong>admin123</strong></p>
        </div>
    </div>
</div>

<script>
function togglePwd() {
    var field = document.getElementById('passwordField');
    var icon  = document.getElementById('eyeIcon');
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'fa-solid fa-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'fa-solid fa-eye';
    }
}
</script>
</body>
</html>
