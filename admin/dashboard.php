<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = "Admin Dashboard";
require_once __DIR__ . '/../includes/header.php';

// Fetch stats
$total_students = 0;
$total_questions = 0;
$total_attempts = 0;
$completed_tests = 0;
$avg_score = 0.00;
$pass_count = 0;
$fail_count = 0;

try {
    // 1. Total Students
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
    $total_students = $stmt->fetchColumn();

    // 2. Total Questions
    $stmt = $pdo->query("SELECT COUNT(*) FROM questions");
    $total_questions = $stmt->fetchColumn();

    // 3. Attempts Stats
    $stmt = $pdo->query("SELECT COUNT(*) FROM attempts");
    $total_attempts = $stmt->fetchColumn();

    // 4. Completed Attempts Stats
    $stmt = $pdo->query("SELECT COUNT(*) FROM attempts WHERE status = 'completed'");
    $completed_tests = $stmt->fetchColumn();

    if ($completed_tests > 0) {
        // Average Score
        $stmt = $pdo->query("SELECT AVG(percentage) FROM attempts WHERE status = 'completed'");
        $avg_score = (float)$stmt->fetchColumn();

        // Get passing percentage from settings
        $passing_percentage = (float)get_setting('passing_percentage', '40');

        // Pass count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM attempts WHERE status = 'completed' AND percentage >= ?");
        $stmt->execute([$passing_percentage]);
        $pass_count = $stmt->fetchColumn();

        // Fail count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM attempts WHERE status = 'completed' AND percentage < ?");
        $stmt->execute([$passing_percentage]);
        $fail_count = $stmt->fetchColumn();
    }

} catch (PDOException $e) {
    error_log("Dashboard stats query error: " . $e->getMessage());
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h2 fw-bold text-dark"><i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard Overview</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <span class="badge bg-light text-dark border p-2"><i class="bi bi-calendar3 me-1"></i> Live Stats</span>
    </div>
</div>

<?php echo display_alerts(); ?>

<!-- Stats Cards Grid -->
<div class="row g-3 mb-4">
    <!-- Total Students -->
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0 animate-fade-in">
            <div class="card-body d-flex align-items-center">
                <div class="bg-primary-subtle text-primary rounded-3 p-3 me-3">
                    <i class="bi bi-people-fill fs-2"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1 font-semibold">Total Students</h6>
                    <h3 class="fw-bold mb-0 text-dark"><?php echo $total_students; ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total Questions -->
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0 animate-fade-in">
            <div class="card-body d-flex align-items-center">
                <div class="bg-info-subtle text-info rounded-3 p-3 me-3">
                    <i class="bi bi-question-circle-fill fs-2"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1 font-semibold">Total Questions</h6>
                    <h3 class="fw-bold mb-0 text-dark"><?php echo $total_questions; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Attempts -->
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0 animate-fade-in">
            <div class="card-body d-flex align-items-center">
                <div class="bg-warning-subtle text-warning-emphasis rounded-3 p-3 me-3">
                    <i class="bi bi-play-fill fs-2"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1 font-semibold">Total Attempts</h6>
                    <h3 class="fw-bold mb-0 text-dark"><?php echo $total_attempts; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Completed Tests -->
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0 animate-fade-in">
            <div class="card-body d-flex align-items-center">
                <div class="bg-success-subtle text-success rounded-3 p-3 me-3">
                    <i class="bi bi-check2-all fs-2"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1 font-semibold">Completed Tests</h6>
                    <h3 class="fw-bold mb-0 text-dark"><?php echo $completed_tests; ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Academic Statistics -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-bar-chart-fill text-primary me-2"></i>Evaluation Metrics</h5>
            </div>
            <div class="card-body p-4">
                <div class="row text-center g-3">
                    <!-- Average Percentage -->
                    <div class="col-md-4 border-end">
                        <span class="text-muted small d-block mb-1">Average Score</span>
                        <h2 class="fw-bold text-primary mb-0"><?php echo format_percentage($avg_score); ?></h2>
                    </div>
                    <!-- Pass Count -->
                    <div class="col-md-4 border-end">
                        <span class="text-muted small d-block mb-1">Passed Students</span>
                        <h2 class="fw-bold text-success mb-0"><?php echo $pass_count; ?></h2>
                    </div>
                    <!-- Fail Count -->
                    <div class="col-md-4">
                        <span class="text-muted small d-block mb-1">Failed Students</span>
                        <h2 class="fw-bold text-danger mb-0"><?php echo $fail_count; ?></h2>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <div class="progress" style="height: 12px; border-radius: var(--radius-sm);">
                        <?php 
                        $pass_percentage = 0;
                        $fail_percentage = 0;
                        if ($completed_tests > 0) {
                            $pass_percentage = ($pass_count / $completed_tests) * 100;
                            $fail_percentage = ($fail_count / $completed_tests) * 100;
                        }
                        ?>
                        <div class="progress-bar bg-success animate-fade-in" role="progressbar" style="width: <?php echo $pass_percentage; ?>%" aria-valuenow="<?php echo $pass_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        <div class="progress-bar bg-danger animate-fade-in" role="progressbar" style="width: <?php echo $fail_percentage; ?>%" aria-valuenow="<?php echo $fail_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mt-2">
                        <span>Passed: <?php echo number_format($pass_percentage, 1); ?>%</span>
                        <span>Failed: <?php echo number_format($fail_percentage, 1); ?>%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Panel -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-lightning-fill text-primary me-2"></i>Quick Tasks</h5>
            </div>
            <div class="card-body p-4 d-grid gap-2">
                <a href="add_student.php" class="btn btn-outline-primary py-2.5 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-person-plus-fill"></i> Add New Student
                </a>
                <a href="add_question.php" class="btn btn-outline-success py-2.5 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-plus-circle-fill"></i> Add New Question
                </a>
                <a href="settings.php" class="btn btn-outline-secondary py-2.5 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-gear-fill"></i> Edit System Settings
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
