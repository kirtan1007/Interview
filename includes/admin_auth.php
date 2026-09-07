<?php
require_once __DIR__ . '/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = "Unauthorized access. Please log in as Admin.";
    
    // Determine path back to login
    $login_path = '../login.php';
    if (file_exists('login.php')) {
        $login_path = 'login.php';
    }
    header("Location: " . $login_path);
    exit();
}
?>
