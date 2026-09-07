<?php
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';

$student_id = $_SESSION['user_id'];

try {
    // 1. Double check attempt constraint
    $stmt = $pdo->prepare("SELECT id FROM attempts WHERE student_id = ?");
    $stmt->execute([$student_id]);
    if ($stmt->fetch()) {
        $_SESSION['error_message'] = "You have already started or completed this test.";
        header("Location: dashboard.php");
        exit();
    }

    // 2. Fetch active questions
    $qStmt = $pdo->prepare("SELECT id, marks FROM questions WHERE status = 'active'");
    $qStmt->execute();
    $questions = $qStmt->fetchAll();
    
    $total_questions = count($questions);
    if ($total_questions === 0) {
        $_SESSION['error_message'] = "No questions are currently active. Please contact the administrator.";
        header("Location: instructions.php");
        exit();
    }

    // 3. Prepare list of IDs & calculate total marks
    $question_ids = array_column($questions, 'id');
    $total_marks = array_sum(array_column($questions, 'marks'));

    // 4. Randomize question order if setting is active
    $randomize = get_setting('randomize_questions', '1');
    if ($randomize === '1') {
        shuffle($question_ids);
    }
    
    $question_order = implode(',', $question_ids);

    // 5. Create new attempt
    $insertStmt = $pdo->prepare("INSERT INTO attempts (student_id, status, total_questions, total_marks, question_order, started_at) VALUES (?, 'in_progress', ?, ?, ?, CURRENT_TIMESTAMP)");
    $insertStmt->execute([$student_id, $total_questions, $total_marks, $question_order]);

    header("Location: test.php");
    exit();

} catch (PDOException $e) {
    error_log("Failed to start test attempt: " . $e->getMessage());
    $_SESSION['error_message'] = "Database error. Could not initiate test attempt.";
    header("Location: instructions.php");
    exit();
}
?>
