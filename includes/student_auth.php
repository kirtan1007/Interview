<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    $_SESSION['error_message'] = "Please log in to access the test.";
    
    $login_path = '../login.php';
    if (file_exists('login.php')) {
        $login_path = 'login.php';
    }
    header("Location: " . $login_path);
    exit();
}

// Check if student status is still active in database
try {
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['status'] !== 'active') {
        session_destroy();
        session_start();
        $_SESSION['error_message'] = "Your account has been deactivated. Please contact the administrator.";
        header("Location: ../login.php");
        exit();
    }
} catch (PDOException $e) {
    // Fail-safe: do not halt but log
    error_log("Student status check failed: " . $e->getMessage());
}
?>
