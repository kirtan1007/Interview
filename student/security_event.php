<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

// Verify student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

$student_id = $_SESSION['user_id'];

// Check CSRF
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
    exit();
}

$violation_type = isset($_POST['violation_type']) ? trim($_POST['violation_type']) : '';
$violation_reason = isset($_POST['violation_reason']) ? trim($_POST['violation_reason']) : '';
$details = isset($_POST['details']) ? trim($_POST['details']) : '';

// Validate event codes
$valid_codes = [
    'TAB_SWITCH', 'PAGE_HIDDEN', 'WINDOW_BLUR', 'WINDOW_FOCUS_RETURN', 
    'FULLSCREEN_EXIT', 'COPY_ATTEMPT', 'PASTE_ATTEMPT', 'CUT_ATTEMPT', 
    'RIGHT_CLICK', 'PRINT_ATTEMPT', 'KEYBOARD_SHORTCUT', 'DEVTOOLS_SUSPECTED'
];

if (!in_array($violation_type, $valid_codes)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid violation code.']);
    exit();
}

try {
    // 1. Fetch current attempt details
    $stmt = $pdo->prepare("SELECT * FROM attempts WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $attempt = $stmt->fetch();

    if (!$attempt) {
        echo json_encode(['status' => 'error', 'message' => 'No active test session.']);
        exit();
    }

    if ($attempt['status'] === 'completed') {
        echo json_encode(['status' => 'error', 'message' => 'Test is already completed.']);
        exit();
    }

    if ($attempt['status'] === 'failed') {
        echo json_encode(['status' => 'failed', 'message' => 'Exam already terminated.']);
        exit();
    }

    // 2. Log violation in database
    log_security_violation($student_id, $attempt['id'], $violation_type, $violation_reason, $details);

    // Skip warning increments for returns and hidden returns
    if ($violation_type === 'WINDOW_FOCUS_RETURN' || $violation_type === 'PAGE_HIDDEN') {
        echo json_encode(['status' => 'ok', 'message' => 'Focus/visibility return logged.']);
        exit();
    }

    // 3. Update violation count and determine fail policies
    $new_violation_count = $attempt['violation_count'] + 1;
    $max_violations = (int)get_setting('max_violations', '3');
    $auto_fail_critical = get_setting('auto_fail_on_critical', '1') === '1';

    // List critical violations (these bypass the warning counter if auto-fail is enabled)
    $critical_violations = ['DEVTOOLS_SUSPECTED', 'PRINT_ATTEMPT'];
    
    $should_fail = false;
    $fail_reason = '';

    if ($new_violation_count >= $max_violations) {
        $should_fail = true;
        $fail_reason = "Exceeded maximum allowed security warnings ($max_violations).";
    } elseif ($auto_fail_critical && in_array($violation_type, $critical_violations)) {
        $should_fail = true;
        $fail_reason = "Critical security policy violation: $violation_reason";
    }

    if ($should_fail) {
        // Fail the exam permanently
        $upStmt = $pdo->prepare("
            UPDATE attempts 
            SET status = 'failed',
                security_status = 'FAILED',
                violation_count = ?,
                failed_reason = ?,
                failed_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $upStmt->execute([$new_violation_count, $fail_reason, $attempt['id']]);

        echo json_encode([
            'status' => 'failed', 
            'message' => 'Exam terminated due to security policy violations. Code: ' . $violation_type
        ]);
        exit();
    } else {
        // Update warnings count
        $security_status = ($new_violation_count > 1) ? 'VIOLATION' : 'WARNING';
        
        $upStmt = $pdo->prepare("
            UPDATE attempts 
            SET security_status = ?,
                violation_count = ?
            WHERE id = ?
        ");
        $upStmt->execute([$security_status, $new_violation_count, $attempt['id']]);

        $remaining = $max_violations - $new_violation_count;
        echo json_encode([
            'status' => 'warning',
            'message' => "$violation_reason You have $remaining warning(s) left before automatic failure."
        ]);
        exit();
    }

} catch (PDOException $e) {
    error_log("Security event AJAX failed: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Server error logging security event.']);
    exit();
}
?>
