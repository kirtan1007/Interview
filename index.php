<?php
require_once __DIR__ . '/includes/auth.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit();
    } else {
        header("Location: student/dashboard.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>
