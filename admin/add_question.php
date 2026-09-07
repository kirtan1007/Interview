<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$question_text = '';
$option_a = '';
$option_b = '';
$option_c = '';
$option_d = '';
$correct_answer = 'A';
$explanation = '';
$marks = 1;
$status = 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    
    $question_text = trim($_POST['question_text'] ?? '');
    $option_a = trim($_POST['option_a'] ?? '');
    $option_b = trim($_POST['option_b'] ?? '');
    $option_c = trim($_POST['option_c'] ?? '');
    $option_d = trim($_POST['option_d'] ?? '');
    $correct_answer = $_POST['correct_answer'] ?? 'A';
    $explanation = trim($_POST['explanation'] ?? '');
    $marks = (int)($_POST['marks'] ?? 1);
    $status = $_POST['status'] ?? 'active';

    if (empty($question_text) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d)) {
        $error = "All options and question text are required.";
    } elseif (!in_array($correct_answer, ['A', 'B', 'C', 'D'])) {
        $error = "Please specify a valid correct answer choice.";
    } elseif ($marks < 1) {
        $error = "Marks value must be at least 1.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, marks, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $explanation, $marks, $status]);
            
            $_SESSION['success_message'] = "Question added to the bank successfully!";
            header("Location: questions.php");
            exit();
        } catch (PDOException $e) {
            error_log("Failed to insert question: " . $e->getMessage());
            $error = "Database error. Could not save question details.";
        }
    }
}

$page_title = "Add Question";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h2 fw-bold text-dark"><i class="bi bi-plus-circle text-primary me-2"></i>Add Question</h1>
    <a href="questions.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to Bank</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div><?php echo escape($error); ?></div>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm col-lg-9 animate-fade-in mb-5">
    <div class="card-body p-4">
        <form action="add_question.php" method="POST">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
            
            <!-- Question text -->
            <div class="mb-4">
                <label for="question_text" class="form-label fw-semibold">Question Description</label>
                <textarea class="form-control" id="question_text" name="question_text" rows="4" placeholder="Enter question description here..." required><?php echo escape($question_text); ?></textarea>
            </div>

            <!-- Options Grid -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="option_a" class="form-label fw-semibold text-primary">Option A</label>
                    <input type="text" class="form-control" id="option_a" name="option_a" placeholder="Enter option A" value="<?php echo escape($option_a); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="option_b" class="form-label fw-semibold text-primary">Option B</label>
                    <input type="text" class="form-control" id="option_b" name="option_b" placeholder="Enter option B" value="<?php echo escape($option_b); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="option_c" class="form-label fw-semibold text-primary">Option C</label>
                    <input type="text" class="form-control" id="option_c" name="option_c" placeholder="Enter option C" value="<?php echo escape($option_c); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="option_d" class="form-label fw-semibold text-primary">Option D</label>
                    <input type="text" class="form-control" id="option_d" name="option_d" placeholder="Enter option D" value="<?php echo escape($option_d); ?>" required>
                </div>
            </div>

            <div class="row g-3 mb-4 pt-3 border-top">
                <!-- Correct Option -->
                <div class="col-md-4">
                    <label for="correct_answer" class="form-label fw-semibold">Correct Answer</label>
                    <select class="form-select border-success" id="correct_answer" name="correct_answer">
                        <option value="A" <?php echo ($correct_answer === 'A') ? 'selected' : ''; ?>>Option A</option>
                        <option value="B" <?php echo ($correct_answer === 'B') ? 'selected' : ''; ?>>Option B</option>
                        <option value="C" <?php echo ($correct_answer === 'C') ? 'selected' : ''; ?>>Option C</option>
                        <option value="D" <?php echo ($correct_answer === 'D') ? 'selected' : ''; ?>>Option D</option>
                    </select>
                </div>
                
                <!-- Marks -->
                <div class="col-md-4">
                    <label for="marks" class="form-label fw-semibold">Question Marks</label>
                    <input type="number" class="form-control" id="marks" name="marks" min="1" value="<?php echo $marks; ?>" required>
                </div>

                <!-- Status -->
                <div class="col-md-4">
                    <label for="status" class="form-label fw-semibold">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php echo ($status === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Explanation -->
            <div class="mb-4 pt-3 border-top">
                <label for="explanation" class="form-label fw-semibold">Explanation <span class="text-muted font-normal">(Optional, shown on review)</span></label>
                <textarea class="form-control" id="explanation" name="explanation" rows="3" placeholder="Enter question solution details..."><?php echo escape($explanation); ?></textarea>
            </div>

            <div class="d-flex gap-2 pt-3 border-top">
                <button type="submit" class="btn btn-primary px-4 py-2"><i class="bi bi-save me-1"></i> Save Question</button>
                <a href="questions.php" class="btn btn-light px-4 py-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
