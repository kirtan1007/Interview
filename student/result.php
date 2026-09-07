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

    // 2. Fetch configurations
    $show_result = get_setting('show_result_immediately', '1');
    $passing_percentage = (float)get_setting('passing_percentage', '40');
    $show_key = get_setting('show_answer_key', '1');
    $test_title = get_setting('test_title', 'PHP & MySQL Technical Assessment');

    $is_pass = ($attempt['percentage'] >= $passing_percentage);

} catch (PDOException $e) {
    error_log("Failed to fetch results: " . $e->getMessage());
    die("Database error while loading results page.");
}

$page_title = "Test Result";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8 animate-fade-in">
        
        <?php echo display_alerts(); ?>

        <div class="card shadow-sm border-0 mb-4 mt-3">
            <div class="card-header bg-white py-3 border-bottom text-center">
                <h3 class="fw-bold mb-0 text-primary">Interview Test Result</h3>
                <span class="text-muted small"><?php echo escape($test_title); ?></span>
            </div>
            <div class="card-body p-4">
                
                <?php if ($show_result === '0'): ?>
                    <!-- If immediate release is deactivated by Admin -->
                    <div class="text-center py-4">
                        <div class="text-warning mb-3">
                            <i class="bi bi-info-circle-fill fs-1"></i>
                        </div>
                        <h4 class="fw-bold">Test Submitted Successfully</h4>
                        <p class="text-muted">Your answers have been securely saved. The administrator has disabled immediate score release. Please contact your coordinator for details.</p>
                        <a href="dashboard.php" class="btn btn-primary px-4 mt-3">Go to Dashboard</a>
                    </div>
                <?php else: ?>
                    
                    <!-- Result Header Panel -->
                    <div class="text-center mb-5">
                        <?php if ($is_pass): ?>
                            <div class="text-success mb-2">
                                <i class="bi bi-check-circle-fill" style="font-size: 4rem;"></i>
                            </div>
                            <span class="badge bg-success px-4 py-2 fs-5 mb-2">PASS</span>
                            <h3 class="fw-bold text-success mb-1">Congratulations!</h3>
                            <p class="text-muted">You have successfully cleared the assessment benchmark.</p>
                        <?php else: ?>
                            <div class="text-danger mb-2">
                                <i class="bi bi-x-circle-fill" style="font-size: 4rem;"></i>
                            </div>
                            <span class="badge bg-danger px-4 py-2 fs-5 mb-2">FAIL</span>
                            <h3 class="fw-bold text-danger mb-1">Assessment Not Cleared</h3>
                            <p class="text-muted">You did not meet the passing percentage of <?php echo escape($passing_percentage); ?>%.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Student and Test Metadata -->
                    <div class="row g-3 mb-4 bg-light p-3 rounded">
                        <div class="col-sm-6">
                            <span class="text-muted d-block small">Student Name:</span>
                            <strong class="text-dark"><?php echo escape($_SESSION['full_name']); ?></strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block small">Username:</span>
                            <strong class="text-dark"><?php echo escape($_SESSION['username']); ?></strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block small">Date of Attempt:</span>
                            <strong class="text-dark"><?php echo date('F j, Y, g:i a', strtotime($attempt['submitted_at'])); ?></strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block small">Time Spent:</span>
                            <strong class="text-dark">
                                <?php 
                                $start = strtotime($attempt['started_at']);
                                $end = strtotime($attempt['submitted_at']);
                                $diff = $end - $start;
                                $mins = floor($diff / 60);
                                $secs = $diff % 60;
                                echo "$mins Min, $secs Sec";
                                ?>
                            </strong>
                        </div>
                    </div>

                    <!-- Detailed Score Matrix Table -->
                    <h5 class="fw-bold border-bottom pb-2 mb-3">Score Details</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-hover mb-0">
                            <tbody>
                                <tr>
                                    <th>Total Questions</th>
                                    <td class="text-end fw-semibold"><?php echo escape($attempt['total_questions']); ?></td>
                                </tr>
                                <tr>
                                    <th>Attempted Questions</th>
                                    <td class="text-end fw-semibold text-primary"><?php echo escape($attempt['attempted_questions']); ?></td>
                                </tr>
                                <tr>
                                    <th>Correct Answers</th>
                                    <td class="text-end fw-semibold text-success"><?php echo escape($attempt['correct_answers']); ?></td>
                                </tr>
                                <tr>
                                    <th>Wrong Answers</th>
                                    <td class="text-end fw-semibold text-danger"><?php echo escape($attempt['wrong_answers']); ?></td>
                                </tr>
                                <tr>
                                    <th>Unanswered Questions</th>
                                    <td class="text-end fw-semibold text-muted"><?php echo escape($attempt['unanswered_questions']); ?></td>
                                </tr>
                                <tr>
                                    <th>Total Marks</th>
                                    <td class="text-end fw-semibold"><?php echo escape($attempt['total_marks']); ?></td>
                                </tr>
                                <tr>
                                    <th>Marks Obtained</th>
                                    <td class="text-end fw-semibold text-primary"><?php echo escape($attempt['obtained_marks']); ?></td>
                                </tr>
                                <tr class="table-primary">
                                    <th class="fw-bold">Percentage</th>
                                    <td class="text-end fw-bold fs-5 text-primary"><?php echo format_percentage($attempt['percentage']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Action Controls -->
                    <div class="text-center">
                        <a href="dashboard.php" class="btn btn-outline-secondary px-5 py-2.5">
                            <i class="bi bi-house me-1"></i> Back to Dashboard
                        </a>
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
