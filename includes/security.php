<?php
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/functions.php';

/**
 * Checks if the student's active attempt has been failed due to security violations.
 * If failed, redirects the student to student/failed.php.
 */
function enforce_test_security($student_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT status, security_status, failed_reason, failed_at FROM attempts WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $attempt = $stmt->fetch();
        
        if ($attempt) {
            if ($attempt['status'] === 'failed' || $attempt['security_status'] === 'FAILED') {
                // Ensure we are redirecting correctly based on folder depth
                $current_dir = basename(dirname($_SERVER['PHP_SELF']));
                $redirect_path = ($current_dir === 'student') ? 'failed.php' : 'student/failed.php';
                
                header("Location: " . $redirect_path);
                exit();
            }
        }
    } catch (PDOException $e) {
        error_log("Failed security checks: " . $e->getMessage());
    }
}

/**
 * Server-side helper to record a security violation into the database.
 */
function log_security_violation($student_id, $attempt_id, $violation_type, $violation_reason, $details = '') {
    global $pdo;
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $page_url = $_SERVER['REQUEST_URI'] ?? 'Unknown';
    
    try {
        // Log the violation
        $stmt = $pdo->prepare("
            INSERT INTO security_violations (student_id, attempt_id, violation_type, violation_reason, details, page_url, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$student_id, $attempt_id, $violation_type, $violation_reason, $details, $page_url, $ip_address, $user_agent]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Failed to log security violation in database: " . $e->getMessage());
        return false;
    }
}
?>
