<?php
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';

$student_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM attempts WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $attempt = $stmt->fetch();
    
    if (!$attempt || $attempt['status'] !== 'failed') {
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Failed loading failed screen details: " . $e->getMessage());
    die("Database error loading security screen.");
}

$page_title = "Exam Terminated";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6 animate-fade-in mt-5">
        <div class="card shadow-lg border-0 bg-dark text-white p-4">
            <div class="card-body text-center">
                <div class="text-danger mb-4">
                    <i class="bi bi-shield-slash-fill" style="font-size: 5rem;"></i>
                </div>
                
                <h1 class="fw-bold text-danger mb-2">EXAM TERMINATED</h1>
                <p class="text-secondary fs-5 mb-4">Your interview test has been terminated due to a security violation.</p>
                
                <div class="bg-secondary bg-opacity-25 p-4 rounded text-start mb-4 border border-secondary">
                    <h5 class="fw-bold mb-3 border-bottom border-secondary pb-2"><i class="bi bi-info-circle me-2"></i>Termination Details</h5>
                    
                    <div class="mb-2">
                        <span class="text-secondary small">Reason:</span>
                        <div class="fw-semibold text-danger"><?php echo escape($attempt['failed_reason']); ?></div>
                    </div>
                    
                    <div class="row g-2 mt-2">
                        <div class="col-6">
                            <span class="text-secondary small">Violation Status:</span>
                            <div class="fw-bold text-warning"><?php echo escape($attempt['security_status']); ?></div>
                        </div>
                        <div class="col-6">
                            <span class="text-secondary small">Time:</span>
                            <div class="fw-semibold"><?php echo date('h:i:s A', strtotime($attempt['failed_at'])); ?></div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-secondary bg-dark text-secondary border-secondary mb-4 small" role="alert">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    A failed security attempt counts as your single allowed attempt. You cannot restart the assessment.
                </div>

                <a href="dashboard.php" class="btn btn-outline-danger px-4 py-2 fw-bold">
                    <i class="bi bi-house me-1"></i> Return to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
