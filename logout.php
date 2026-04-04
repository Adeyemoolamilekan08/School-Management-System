<?php
// ============================================================
// LOGOUT
// ============================================================
session_start();
session_destroy();
header("Location: /school-system/login.php");
exit();
?>
