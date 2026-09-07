<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';

$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$student = null;
$attempt = null;
$answers = [];
$question_order = [];

try {
    // 1. Fetch Student profile details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        $_SESSION['error_message'] = "Student record not found.";
        header("Location: results.php");
        exit();
    }

    // 2. Fetch completed attempt details
    $stmt = $pdo->prepare("SELECT * FROM attempts WHERE student_id = ? AND status = 'completed'");
    $stmt->execute([$student_id]);
    $attempt = $stmt->fetch();

    if (!$attempt) {
        $_SESSION['error_message'] = "No completed test attempt found for this student.";
        header("Location: results.php");
        exit();
    }

    // 3. Fetch all saved answers and link with questions
    $ansStmt = $pdo->prepare("
        SELECT sa.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.explanation
        FROM student_answers sa
        JOIN questions q ON sa.question_id = q.id
        WHERE sa.attempt_id = ?
    ");
    $ansStmt->execute([$attempt['id']]);
    $answers_raw = $ansStmt->fetchAll();

    // Index answers by question ID
    $answers_by_qid = [];
    foreach ($answers_raw as $ans) {
        $answers_by_qid[$ans['question_id']] = $ans;
    }

    // Order answers based on attempt question_order
    $question_order = explode(',', $attempt['question_order']);

} catch (PDOException $e) {
    error_log("Failed to inspect student results: " . $e->getMessage());
    $_SESSION['error_message'] = "Database error fetching test results.";
    header("Location: results.php");
    exit();
}

$page_title = "Inspect Result Sheet";
$passing_percentage = (float)get_setting('passing_percentage', '40');
$is_pass = ($attempt['percentage'] >= $passing_percentage);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h2 fw-bold text-dark"><i class="bi bi-file-earmark-bar-graph text-primary me-2"></i>Inspect Answer Sheet</h1>
    <a href="results.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to Results</a>
</div>

<!-- Score overview block -->
<div class="row g-4 mb-4">
    <!-- Student Details -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-person-fill text-primary me-2"></i>Student Details</h5>
            </div>
            <div class="card-body p-4">
                <table class="table table-borderless mb-0 align-middle">
                    <tbody>
                        <tr>
                            <td class="text-muted ps-0" style="width: 35%;">Full Name:</td>
                            <td class="fw-bold text-dark"><?php echo escape($student['full_name']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Username:</td>
                            <td><code><?php echo escape($student['username']); ?></code></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Email:</td>
                            <td><?php echo escape($student['email']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Started At:</td>
                            <td class="small"><?php echo date('M j, Y, g:i a', strtotime($attempt['started_at'])); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Submitted At:</td>
                            <td class="small"><?php echo date('M j, Y, g:i a', strtotime($attempt['submitted_at'])); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Test Evaluation Summary -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-card-checklist text-primary me-2"></i>Evaluation Summary</h5>
                <?php if ($is_pass): ?>
                    <span class="badge bg-success px-3 py-2 fs-6">PASS</span>
                <?php else: ?>
                    <span class="badge bg-danger px-3 py-2 fs-6">FAIL</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-4">
                <div class="row text-center g-2 mb-3">
                    <div class="col-4 border-end">
                        <span class="text-muted small d-block mb-1">Percentage</span>
                        <h3 class="fw-bold text-primary mb-0"><?php echo format_percentage($attempt['percentage']); ?></h3>
                    </div>
                    <div class="col-4 border-end">
                        <span class="text-muted small d-block mb-1">Obtained Marks</span>
                        <h3 class="fw-bold mb-0"><?php echo escape($attempt['obtained_marks']); ?> / <?php echo escape($attempt['total_marks']); ?></h3>
                    </div>
                    <div class="col-4">
                        <span class="text-muted small d-block mb-1">Correct answers</span>
                        <h3 class="fw-bold text-success mb-0"><?php echo escape($attempt['correct_answers']); ?> / <?php echo escape($attempt['total_questions']); ?></h3>
                    </div>
                </div>

                <div class="bg-light p-3 rounded">
                    <div class="row text-center text-muted small g-2">
                        <div class="col-4">Attempted: <strong><?php echo escape($attempt['attempted_questions']); ?></strong></div>
                        <div class="col-4">Wrong: <strong><?php echo escape($attempt['wrong_answers']); ?></strong></div>
                        <div class="col-4">Unanswered: <strong><?php echo escape($attempt['unanswered_questions']); ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Question breakdown sheet -->
<h4 class="fw-bold text-dark mb-3"><i class="bi bi-list-ol text-primary me-2"></i>Detailed Responses</h4>
<div class="row justify-content-center">
    <div class="col-12">
        <?php 
        $counter = 1;
        foreach ($question_order as $q_id):
            $ans = $answers_by_qid[$q_id] ?? null;
            if (!$ans) continue;

            $selected = $ans['selected_answer'];
            $correct = $ans['correct_answer'];
            $status = 'unanswered';
            
            if ($selected !== null && $selected !== '') {
                $status = ($selected === $correct) ? 'correct' : 'wrong';
            }
        ?>
            <div class="card shadow-sm border-0 mb-4 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <span class="badge bg-secondary px-3 py-2 fs-6">Question <?php echo $counter++; ?></span>
                    
                    <?php if ($status === 'correct'): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i> Correct (+<?php echo escape($ans['marks_obtained']); ?> Marks)</span>
                    <?php elseif ($status === 'wrong'): ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2"><i class="bi bi-x-circle-fill me-1"></i> Wrong (0 Marks)</span>
                    <?php else: ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2"><i class="bi bi-exclamation-circle-fill me-1"></i> Unanswered (0 Marks)</span>
                    <?php endif; ?>
                </div>

                <div class="card-body p-4">
                    <!-- Question Text -->
                    <h5 class="fw-semibold text-dark mb-4 lh-base"><?php echo nl2br(escape($ans['question_text'])); ?></h5>

                    <!-- Option layout -->
                    <div class="options-container mb-3">
                        <div class="option-box <?php 
                            if ($correct === 'A') echo 'correct ';
                            if ($selected === 'A' && $status === 'wrong') echo 'wrong ';
                            if ($selected === 'A') echo 'selected ';
                        ?>" style="pointer-events: none;">
                            <label class="form-check-label w-100">
                                <strong>A.</strong> <?php echo escape($ans['option_a']); ?>
                                <?php if ($correct === 'A'): ?><span class="text-success ms-2 fw-bold">(Correct Answer)</span><?php endif; ?>
                                <?php if ($selected === 'A' && $status === 'wrong'): ?><span class="text-danger ms-2 fw-bold">(Student Selected)</span><?php endif; ?>
                                <?php if ($selected === 'A' && $status === 'correct'): ?><span class="text-success ms-2 fw-bold">(Student Selected)</span><?php endif; ?>
                            </label>
                        </div>
                        
                        <div class="option-box <?php 
                            if ($correct === 'B') echo 'correct ';
                            if ($selected === 'B' && $status === 'wrong') echo 'wrong ';
                            if ($selected === 'B') echo 'selected ';
                        ?>" style="pointer-events: none;">
                            <label class="form-check-label w-100">
                                <strong>B.</strong> <?php echo escape($ans['option_b']); ?>
                                <?php if ($correct === 'B'): ?><span class="text-success ms-2 fw-bold">(Correct Answer)</span><?php endif; ?>
                                <?php if ($selected === 'B' && $status === 'wrong'): ?><span class="text-danger ms-2 fw-bold">(Student Selected)</span><?php endif; ?>
                                <?php if ($selected === 'B' && $status === 'correct'): ?><span class="text-success ms-2 fw-bold">(Student Selected)</span><?php endif; ?>
                            </label>
                        </div>

                        <div class="option-box <?php 
                            if ($correct === 'C') echo 'correct ';
                            if ($selected === 'C' && $status === 'wrong') echo 'wrong ';
                            if ($selected === 'C') echo 'selected ';
                        ?>" style="pointer-events: none;">
                            <label class="form-check-label w-100">
                                <strong>C.</strong> <?php echo escape($ans['option_c']); ?>
                                <?php if ($correct === 'C'): ?><span class="text-success ms-2 fw-bold">(Correct Answer)</span><?php endif; ?>
                                <?php if ($selected === 'C' && $status === 'wrong'): ?><span class="text-danger ms-2 fw-bold">(Student Selected)</span><?php endif; ?>
                                <?php if ($selected === 'C' && $status === 'correct'): ?><span class="text-success ms-2 fw-bold">(Student Selected)</span><?php endif; ?>
                            </label>
                        </div>

                        <div class="option-box <?php 
                            if ($correct === 'D') echo 'correct ';
                            if ($selected === 'D' && $status === 'wrong') echo 'wrong ';
                            if ($selected === 'D') echo 'selected ';
                        ?>" style="pointer-events: none;">
                            <label class="form-check-label w-100">
                                <strong>D.</strong> <?php echo escape($ans['option_d']); ?>
                                <?php if ($correct === 'D'): ?><span class="text-success ms-2 fw-bold">(Correct Answer)</span><?php endif; ?>
                                <?php if ($selected === 'D' && $status === 'wrong'): ?><span class="text-danger ms-2 fw-bold">(Student Selected)</span><?php endif; ?>
                                <?php if ($selected === 'D' && $status === 'correct'): ?><span class="text-success ms-2 fw-bold">(Student Selected)</span><?php endif; ?>
                            </label>
                        </div>
                    </div>

                    <?php if (!empty($ans['explanation'])): ?>
                        <div class="bg-light p-3 rounded border-start border-primary border-4 mt-3">
                            <h6 class="fw-bold mb-1 text-primary"><i class="bi bi-info-circle me-1"></i> Explanation:</h6>
                            <p class="text-muted small mb-0"><?php echo nl2br(escape($ans['explanation'])); ?></p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
