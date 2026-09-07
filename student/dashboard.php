<?php
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

$student_id = $_SESSION['user_id'];
enforce_test_security($student_id);
$attempt = null;

try {
    $stmt = $pdo->prepare("SELECT * FROM attempts WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $attempt = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Failed checking student attempt status: " . $e->getMessage());
    $_SESSION['error_message'] = "Could not check test status. Please try again.";
}

// Route based on attempt state
$status = 'not_started';
if ($attempt) {
    $status = $attempt['status']; // 'in_progress' or 'completed'
}

if ($status === 'not_started') {
    header("Location: instructions.php");
    exit();
}

$page_title = "Student Dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm border-0 animate-fade-in mb-4 mt-5">
            <div class="card-body p-4 text-center">
                
                <?php if ($status === 'in_progress'): ?>
                    <div class="text-warning mb-3">
                        <i class="bi bi-clock-history fs-1"></i>
                    </div>
                    <h2 class="fw-bold mb-2">Test In Progress</h2>
                    <p class="text-muted mb-4">You have an ongoing test session. Click below to resume your test. Your timer and answers will continue from where you left off.</p>
                    
                    <a href="test.php" class="btn btn-warning w-100 py-2.5 fw-bold text-dark">
                        <i class="bi bi-play-circle-fill me-1"></i> Resume Test
                    </a>

                <?php elseif ($status === 'completed'): ?>
                    <div class="text-success mb-3">
                        <i class="bi bi-check-circle-fill fs-1"></i>
                    </div>
                    <h2 class="fw-bold mb-2">Test Completed</h2>
                    <p class="text-muted mb-4">You have already completed this assessment. Under our policy, you are allowed only one attempt.</p>
                    
                    <div class="bg-light p-3 rounded mb-4">
                        <div class="row">
                            <div class="col-6 text-center border-end">
                                <span class="text-muted small d-block">Your Score</span>
                                <span class="fs-4 fw-bold text-primary"><?php echo escape($attempt['obtained_marks']); ?> / <?php echo escape($attempt['total_marks']); ?></span>
                            </div>
                            <div class="col-6 text-center">
                                <span class="text-muted small d-block">Percentage</span>
                                <span class="fs-4 fw-bold text-primary"><?php echo format_percentage($attempt['percentage']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        <a href="result.php" class="btn btn-primary w-100 py-2.5 fw-bold">
                            <i class="bi bi-file-earmark-text me-1"></i> View Result
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
