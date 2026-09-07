<?php
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

$student_id = $_SESSION['user_id'];

// 1. Enforce proctoring checks (failed attempts redirect to failed.php)
enforce_test_security($student_id);

try {
    $stmt = $pdo->prepare("SELECT status FROM attempts WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $attempt = $stmt->fetch();
    
    if ($attempt) {
        if ($attempt['status'] === 'completed') {
            header("Location: result.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    }
} catch (PDOException $e) {
    error_log("Instructions check failed: " . $e->getMessage());
}

$page_title = "Exam Rules & Instructions";
$test_title = get_setting('test_title', 'PHP & MySQL Technical Assessment');
$duration = get_setting('test_duration', '30');
$instructions = get_setting('instructions', '');
$security_mode = get_setting('exam_security_mode', '1') === '1';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8 animate-fade-in">
        <div class="card shadow-sm border-0 mb-4 mt-4">
            <div class="card-header bg-white py-3 border-bottom text-center">
                <h3 class="fw-bold mb-0 text-primary"><?php echo escape($test_title); ?></h3>
            </div>
            <div class="card-body p-4">
                
                <div class="row g-3 mb-4">
                    <div class="col-sm-4 text-center">
                        <div class="bg-light p-3 rounded">
                            <span class="text-muted small d-block">Test Duration</span>
                            <span class="fw-bold fs-5 text-dark"><i class="bi bi-clock me-1 text-primary"></i> <?php echo escape($duration); ?> Min</span>
                        </div>
                    </div>
                    <div class="col-sm-4 text-center">
                        <div class="bg-light p-3 rounded">
                            <span class="text-muted small d-block">Passing Score</span>
                            <span class="fw-bold fs-5 text-dark"><i class="bi bi-award me-1 text-success"></i> <?php echo escape(get_setting('passing_percentage', '40')); ?>%</span>
                        </div>
                    </div>
                    <div class="col-sm-4 text-center">
                        <div class="bg-light p-3 rounded">
                            <span class="text-muted small d-block">Test Security</span>
                            <span class="fw-bold fs-5 text-danger"><i class="bi bi-shield-fill-check me-1"></i> <?php echo $security_mode ? 'Active' : 'Standard'; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Custom General Instructions -->
                <?php if (!empty($instructions)): ?>
                    <h5 class="fw-bold mb-3 border-bottom pb-2">General Instructions:</h5>
                    <div class="text-dark mb-4" style="line-height: 1.7; white-space: pre-line;">
                        <?php echo escape($instructions); ?>
                    </div>
                <?php endif; ?>

                <!-- Proctoring warnings -->
                <?php if ($security_mode): ?>
                    <h5 class="fw-bold text-danger mb-3 border-bottom pb-2"><i class="bi bi-shield-exclamation me-1"></i> Exam Security Rules</h5>
                    <div class="bg-danger bg-opacity-10 text-danger p-3 rounded mb-4 border border-danger-subtle">
                        <p class="fw-bold mb-2">During the test, the following anti-cheating measures are active:</p>
                        <ul class="mb-3" style="line-height: 1.7;">
                            <li>Do not switch tabs or leave the browser screen.</li>
                            <li>Do not minimize or change focus from the test window.</li>
                            <li>Do not exit fullscreen mode.</li>
                            <li>Do not copy questions, options, or explanations (right-click and copying are disabled).</li>
                            <li>Do not paste any answers into inputs.</li>
                            <li>Do not use keyboard shortcuts (like Ctrl+C, Ctrl+V, F12, Ctrl+P, etc.).</li>
                            <li>Do not inspect elements or open browser developer tools.</li>
                            <li>Do not print or photograph the test screen.</li>
                        </ul>
                        <strong class="d-block text-center text-uppercase fs-6 border-top border-danger-subtle pt-2 mt-2">
                            "Violation of exam security rules may automatically terminate your test."
                        </strong>
                    </div>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <a href="start_test.php" class="btn btn-danger btn-lg px-5 py-3 fw-bold shadow-sm" onclick="return confirm('Starting the test now will enable fullscreen and trigger exam proctoring rules. Proceed?');">
                        <i class="bi bi-shield-fill-check me-2"></i> I Understand & Start Test
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
