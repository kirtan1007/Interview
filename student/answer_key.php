<?php
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';

$student_id = $_SESSION['user_id'];

try {
    // 1. Fetch attempt details
    $stmt = $pdo->prepare("SELECT * FROM attempts WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $attempt = $stmt->fetch();

    if (!$attempt || $attempt['status'] !== 'completed') {
        header("Location: dashboard.php");
        exit();
    }

    // 2. Validate configuration permissions
    if (get_setting('show_answer_key', '1') !== '1') {
        $_SESSION['error_message'] = "Viewing the answer key is disabled for this test.";
        header("Location: result.php");
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
    error_log("Failed to load answer key: " . $e->getMessage());
    die("Database error while loading review key.");
}

$page_title = "Answer Key Review";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mt-3">
    <div class="col-lg-10 animate-fade-in">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold mb-0 text-dark"><i class="bi bi-file-earmark-check text-primary me-2"></i>Test Review</h3>
            <a href="result.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i> Back to Result</a>
        </div>

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
                <!-- Status Colored Header border -->
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <span class="badge bg-secondary px-3 py-2 fs-6">Question <?php echo $counter++; ?></span>
                    
                    <?php if ($status === 'correct'): ?>
                        <span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i> Correct (+<?php echo escape($ans['marks_obtained']); ?> Marks)</span>
                    <?php elseif ($status === 'wrong'): ?>
                        <span class="badge bg-danger px-3 py-2"><i class="bi bi-x-circle-fill me-1"></i> Wrong (0 Marks)</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark px-3 py-2"><i class="bi bi-exclamation-circle-fill me-1"></i> Unanswered (0 Marks)</span>
                    <?php endif; ?>
                </div>

                <div class="card-body p-4">
                    <!-- Question Text -->
                    <h5 class="fw-bold text-dark mb-4 lh-base"><?php echo nl2br(escape($ans['question_text'])); ?></h5>

                    <!-- Options Grid Layout -->
                    <div class="options-container mb-3">
                        <!-- Option A -->
                        <div class="option-box <?php 
                            if ($correct === 'A') echo 'correct ';
                            if ($selected === 'A' && $status === 'wrong') echo 'wrong ';
                            if ($selected === 'A') echo 'selected ';
                        ?>" style="pointer-events: none;">
                            <label class="form-check-label w-100">
                                <strong>A.</strong> <?php echo escape($ans['option_a']); ?>
                                <?php if ($correct === 'A'): ?><span class="text-success ms-2 fw-bold">(Correct Answer)</span><?php endif; ?>
                                <?php if ($selected === 'A' && $status === 'wrong'): ?><span class="text-danger ms-2 fw-bold">(Your Answer)</span><?php endif; ?>
                                <?php if ($selected === 'A' && $status === 'correct'): ?><span class="text-success ms-2 fw-bold">(Your Answer)</span><?php endif; ?>
                            </label>
                        </div>
                        
                        <!-- Option B -->
                        <div class="option-box <?php 
                            if ($correct === 'B') echo 'correct ';
                            if ($selected === 'B' && $status === 'wrong') echo 'wrong ';
                            if ($selected === 'B') echo 'selected ';
                        ?>" style="pointer-events: none;">
                            <label class="form-check-label w-100">
                                <strong>B.</strong> <?php echo escape($ans['option_b']); ?>
                                <?php if ($correct === 'B'): ?><span class="text-success ms-2 fw-bold">(Correct Answer)</span><?php endif; ?>
                                <?php if ($selected === 'B' && $status === 'wrong'): ?><span class="text-danger ms-2 fw-bold">(Your Answer)</span><?php endif; ?>
                                <?php if ($selected === 'B' && $status === 'correct'): ?><span class="text-success ms-2 fw-bold">(Your Answer)</span><?php endif; ?>
                            </label>
                        </div>

                        <!-- Option C -->
                        <div class="option-box <?php 
                            if ($correct === 'C') echo 'correct ';
                            if ($selected === 'C' && $status === 'wrong') echo 'wrong ';
                            if ($selected === 'C') echo 'selected ';
                        ?>" style="pointer-events: none;">
                            <label class="form-check-label w-100">
                                <strong>C.</strong> <?php echo escape($ans['option_c']); ?>
                                <?php if ($correct === 'C'): ?><span class="text-success ms-2 fw-bold">(Correct Answer)</span><?php endif; ?>
                                <?php if ($selected === 'C' && $status === 'wrong'): ?><span class="text-danger ms-2 fw-bold">(Your Answer)</span><?php endif; ?>
                                <?php if ($selected === 'C' && $status === 'correct'): ?><span class="text-success ms-2 fw-bold">(Your Answer)</span><?php endif; ?>
                            </label>
                        </div>

                        <!-- Option D -->
                        <div class="option-box <?php 
                            if ($correct === 'D') echo 'correct ';
                            if ($selected === 'D' && $status === 'wrong') echo 'wrong ';
                            if ($selected === 'D') echo 'selected ';
                        ?>" style="pointer-events: none;">
                            <label class="form-check-label w-100">
                                <strong>D.</strong> <?php echo escape($ans['option_d']); ?>
                                <?php if ($correct === 'D'): ?><span class="text-success ms-2 fw-bold">(Correct Answer)</span><?php endif; ?>
                                <?php if ($selected === 'D' && $status === 'wrong'): ?><span class="text-danger ms-2 fw-bold">(Your Answer)</span><?php endif; ?>
                                <?php if ($selected === 'D' && $status === 'correct'): ?><span class="text-success ms-2 fw-bold">(Your Answer)</span><?php endif; ?>
                            </label>
                        </div>
                    </div>

                    <!-- Explanation -->
                    <?php if (!empty($ans['explanation'])): ?>
                        <div class="bg-light p-3 rounded border-start border-primary border-4 mt-3">
                            <h6 class="fw-bold mb-1 text-primary"><i class="bi bi-info-circle me-1"></i> Explanation:</h6>
                            <p class="text-muted small mb-0"><?php echo nl2br(escape($ans['explanation'])); ?></p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-5">
            <a href="result.php" class="btn btn-outline-primary"><i class="bi-arrow-left me-1"></i> Back to Result</a>
            <a href="dashboard.php" class="btn btn-primary px-4">Dashboard</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
