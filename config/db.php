<?php
// ============================================================
// DATABASE CONFIGURATION
// ============================================================
// Edit these settings to match your server setup

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Your MySQL username
define('DB_PASS', '');           // Your MySQL password
define('DB_NAME', 'school_db');

// ============================================================
// CONNECT TO DATABASE
// ============================================================
// Disable mysqli throwing exceptions — we handle errors manually
mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("
        <div style='font-family:sans-serif;padding:40px;text-align:center;'>
            <h2 style='color:red;'>Database Connection Failed</h2>
            <p>" . mysqli_connect_error() . "</p>
            <p>Please check your database settings in <strong>config/db.php</strong></p>
        </div>
    ");
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8");

// ============================================================
// HELPER FUNCTIONS
// ============================================================

// Sanitize input to prevent XSS
function clean($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// Redirect helper
function redirect($url) {
    header("Location: $url");
    exit();
}

// Show alert message
function alert($type, $message) {
    $_SESSION['alert_type'] = $type;
    $_SESSION['alert_msg'] = $message;
}

// Display alert
function show_alert() {
    if (isset($_SESSION['alert_msg'])) {
        $type = $_SESSION['alert_type'];
        $msg  = $_SESSION['alert_msg'];
        $color = ($type == 'success') ? '#28a745' : (($type == 'warning') ? '#ffc107' : '#dc3545');
        echo "<div class='alert alert-{$type}' style='background:{$color};color:#fff;padding:12px 20px;border-radius:6px;margin-bottom:15px;'>{$msg}</div>";
        unset($_SESSION['alert_msg']);
        unset($_SESSION['alert_type']);
    }
}

// Generate unique ID (e.g. STU-2024-001)
function generate_id($prefix, $conn, $table, $id_column) {
    $year = date('Y');
    $result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM $table");
    $row = mysqli_fetch_assoc($result);
    $count = str_pad($row['cnt'] + 1, 4, '0', STR_PAD_LEFT);
    return $prefix . '-' . $year . '-' . $count;
}

// Calculate grade from total score
function calculate_grade($total) {
    if ($total >= 70) return ['A', 'Excellent'];
    if ($total >= 60) return ['B', 'Very Good'];
    if ($total >= 50) return ['C', 'Good'];
    if ($total >= 40) return ['D', 'Pass'];
    return ['F', 'Fail'];
}

// Base URL helper
define('BASE_URL', '/school-system');
define('SITE_NAME', 'SchoolMS');


// git init
// git add README.md
// git commit -m "first commit"
// git branch -M main
// git remote add origin https://github.com/Adeyemoolamilekan08/School-Management-System.git
// git push -u origin main

?>
