<?php
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';

$student_id = $_SESSION['user_id'];
$auto_submit = isset($_POST['auto_submit']) || isset($_GET['auto_submit']);

// Verify CSRF (skip only for automatic timer submissions)
if (!$auto_submit) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Invalid submit request.";
        header("Location: test.php");
        exit();
    }
}

try {
    // 1. Fetch current attempt details
    $stmt = $pdo->prepare("SELECT * FROM attempts WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $attempt = $stmt->fetch();

    if (!$attempt) {
        header("Location: instructions.php");
        exit();
    }

    if ($attempt['status'] === 'completed') {
        header("Location: result.php");
        exit();
    }

    if ($attempt['status'] === 'failed') {
        header("Location: failed.php");
        exit();
    }

    // 2. Parse the question IDs ordered for this student
    $question_ids = explode(',', $attempt['question_order']);
    
    // Fetch all questions details in order to calculate correct answers securely
    $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
    $qStmt = $pdo->prepare("SELECT id, correct_answer, marks FROM questions WHERE id IN ($placeholders)");
    $qStmt->execute($question_ids);
    $questions = $qStmt->fetchAll();
    
    // Index questions by ID
    $questions_by_id = [];
    foreach ($questions as $q) {
        $questions_by_id[$q['id']] = $q;
    }

    // Fetch existing answers saved by the student
    $ansStmt = $pdo->prepare("SELECT question_id, selected_answer FROM student_answers WHERE attempt_id = ?");
    $ansStmt->execute([$attempt['id']]);
    $saved_answers_raw = $ansStmt->fetchAll();
    
    $saved_answers = [];
    foreach ($saved_answers_raw as $raw) {
        $saved_answers[$raw['question_id']] = $raw['selected_answer'];
    }

    // 3. Score calculation logic
    $total_questions = count($question_ids);
    $attempted_questions = 0;
    $correct_answers = 0;
    $wrong_answers = 0;
    $unanswered_questions = 0;
    $total_marks = 0;
    $obtained_marks = 0;

    // Open transaction for consistency
    $pdo->beginTransaction();

    foreach ($question_ids as $q_id) {
        $question = $questions_by_id[$q_id] ?? null;
        if (!$question) continue;

        $marks = (int)$question['marks'];
        $total_marks += $marks;

        $correct_ans = $question['correct_answer'];
        $selected_ans = $saved_answers[$q_id] ?? null;

        if ($selected_ans !== null && in_array($selected_ans, ['A', 'B', 'C', 'D'])) {
            $attempted_questions++;
            if ($selected_ans === $correct_ans) {
                $correct_answers++;
                $obtained_marks += $marks;
            } else {
                $wrong_answers++;
            }
        } else {
            // Log as unanswered in the database for tracking
            $unanswered_questions++;
            
            $insAns = $pdo->prepare("
                INSERT INTO student_answers (attempt_id, student_id, question_id, selected_answer, correct_answer, is_correct, marks_obtained)
                VALUES (?, ?, ?, NULL, ?, 0, 0)
                ON DUPLICATE KEY UPDATE selected_answer = NULL, is_correct = 0, marks_obtained = 0
            ");
            $insAns->execute([$attempt['id'], $student_id, $q_id, $correct_ans]);
        }
    }

    // Calculate percentage
    $percentage = 0.00;
    if ($total_marks > 0) {
        $percentage = ($obtained_marks / $total_marks) * 100;
    }

    // 4. Update the attempt to 'completed'
    $updateStmt = $pdo->prepare("
        UPDATE attempts 
        SET status = 'completed', 
            total_questions = ?, 
            attempted_questions = ?, 
            correct_answers = ?, 
            wrong_answers = ?, 
            unanswered_questions = ?, 
            total_marks = ?, 
            obtained_marks = ?, 
            percentage = ?, 
            submitted_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $updateStmt->execute([
        $total_questions,
        $attempted_questions,
        $correct_answers,
        $wrong_answers,
        $unanswered_questions,
        $total_marks,
        $obtained_marks,
        $percentage,
        $attempt['id']
    ]);

    $pdo->commit();
    
    $_SESSION['success_message'] = "Your test has been successfully submitted!";
    header("Location: result.php");
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Failed test submission: " . $e->getMessage());
    $_SESSION['error_message'] = "A server error occurred during submission. Please contact support.";
    header("Location: dashboard.php");
    exit();
}
?>
