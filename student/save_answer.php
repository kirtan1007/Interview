<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';

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

$question_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
$selected_answer = isset($_POST['selected_answer']) ? trim($_POST['selected_answer']) : '';

// Validate Answer Format
if (!in_array($selected_answer, ['A', 'B', 'C', 'D'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid answer choice.']);
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
        echo json_encode(['status' => 'error', 'message' => 'Test has already been completed.']);
        exit();
    }

    // 2. Fetch target question details
    $qStmt = $pdo->prepare("SELECT correct_answer, marks FROM questions WHERE id = ? AND status = 'active'");
    $qStmt->execute([$question_id]);
    $question = $qStmt->fetch();

    if (!$question) {
        echo json_encode(['status' => 'error', 'message' => 'Active question not found.']);
        exit();
    }

    // 3. Compute score stats for this answer
    $correct_answer = $question['correct_answer'];
    $is_correct = ($selected_answer === $correct_answer) ? 1 : 0;
    $marks_obtained = $is_correct ? (int)$question['marks'] : 0;

    // 4. Save/Update Answer
    $ansStmt = $pdo->prepare("
        INSERT INTO student_answers (attempt_id, student_id, question_id, selected_answer, correct_answer, is_correct, marks_obtained)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            selected_answer = VALUES(selected_answer),
            correct_answer = VALUES(correct_answer),
            is_correct = VALUES(is_correct),
            marks_obtained = VALUES(marks_obtained)
    ");
    
    $ansStmt->execute([
        $attempt['id'], 
        $student_id, 
        $question_id, 
        $selected_answer, 
        $correct_answer, 
        $is_correct, 
        $marks_obtained
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Answer saved successfully.']);
    exit();

} catch (PDOException $e) {
    error_log("Failed to save student answer: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error while saving response.']);
    exit();
}
?>
