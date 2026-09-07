<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';

$attempt_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$attempt = null;
$student = null;
$violations = [];

try {
    // 1. Fetch attempt details
    $stmt = $pdo->prepare("SELECT * FROM attempts WHERE id = ?");
    $stmt->execute([$attempt_id]);
    $attempt = $stmt->fetch();
    
    if (!$attempt) {
        $_SESSION['error_message'] = "Attempt record not found.";
        header("Location: security_logs.php");
        exit();
    }

    // 2. Fetch student details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$attempt['student_id']]);
    $student = $stmt->fetch();

    // 3. Fetch chronological violations list
    $stmt = $pdo->prepare("SELECT * FROM security_violations WHERE attempt_id = ? ORDER BY created_at ASC");
    $stmt->execute([$attempt_id]);
    $violations = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Failed loading security timeline audit: " . $e->getMessage());
    $_SESSION['error_message'] = "Database error fetching audit timeline.";
    header("Location: security_logs.php");
    exit();
}

$page_title = "Proctoring Timeline Audit";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h2 fw-bold text-dark"><i class="bi bi-timeline text-primary me-2"></i>Security Audit Timeline</h1>
    <a href="security_logs.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to Logs</a>
</div>

<div class="row g-4">
    <!-- Candidate Info Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-person-fill text-primary me-2"></i>Candidate Profile</h5>
            </div>
            <div class="card-body p-4">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted ps-0">Full Name:</td>
                            <td class="fw-bold text-dark"><?php echo escape($student['full_name']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Username:</td>
                            <td><code><?php echo escape($student['username']); ?></code></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Test Status:</td>
                            <td>
                                <?php 
                                $status = $attempt['status'];
                                if ($status === 'completed') {
                                    echo '<span class="badge bg-success">Completed</span>';
                                } elseif ($status === 'failed') {
                                    echo '<span class="badge bg-danger">FAILED</span>';
                                } else {
                                    echo '<span class="badge bg-warning text-dark">In Progress</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Warnings Issued:</td>
                            <td>
                                <strong class="text-danger"><?php echo (int)$attempt['violation_count']; ?></strong> 
                                / <?php echo escape(get_setting('max_violations', '3')); ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Started At:</td>
                            <td class="small"><?php echo date('M j, g:i:s a', strtotime($attempt['started_at'])); ?></td>
                        </tr>
                        <?php if ($attempt['submitted_at'] || $attempt['failed_at']): ?>
                        <tr>
                            <td class="text-muted ps-0">Ended At:</td>
                            <td class="small">
                                <?php echo date('M j, g:i:s a', strtotime($attempt['submitted_at'] ? $attempt['submitted_at'] : $attempt['failed_at'])); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($attempt['status'] === 'failed'): ?>
            <div class="alert alert-danger border-0 shadow-sm p-4">
                <h5 class="fw-bold mb-2"><i class="bi bi-shield-slash-fill me-2"></i>Termination Event</h5>
                <p class="mb-0 small">This attempt was locked and terminated automatically. Reason:</p>
                <strong class="d-block mt-2 text-uppercase small text-danger-emphasis">"<?php echo escape($attempt['failed_reason']); ?>"</strong>
            </div>
        <?php endif; ?>
    </div>

    <!-- Chronological Audit Timeline list -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-primary me-2"></i>Chrono Audit Log</h5>
            </div>
            <div class="card-body p-4">
                
                <div class="position-relative ps-4 border-start border-primary border-2" style="margin-left: 10px;">
                    <!-- Test Started Node -->
                    <div class="mb-4 position-relative">
                        <span class="position-absolute bg-primary rounded-circle" style="left: -33px; top: 4px; width: 14px; height: 14px; border: 3px solid #ffffff;"></span>
                        <div class="small text-muted mb-1"><?php echo date('h:i:s A', strtotime($attempt['started_at'])); ?></div>
                        <div class="fw-bold text-dark">Test Attempt Started</div>
                        <span class="text-muted small">Proctoring rules activated. Candidate window was put in fullscreen mode.</span>
                    </div>

                    <!-- Chronological violations -->
                    <?php if (count($violations) === 0 && $attempt['status'] !== 'failed'): ?>
                        <div class="mb-4 position-relative">
                            <span class="position-absolute bg-success rounded-circle" style="left: -33px; top: 4px; width: 14px; height: 14px; border: 3px solid #ffffff;"></span>
                            <div class="fw-bold text-success">No Violations Logged</div>
                            <span class="text-muted small">No browser-detectable cheating activities have been triggered.</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($violations as $v): ?>
                            <div class="mb-4 position-relative">
                                <span class="position-absolute bg-danger rounded-circle" style="left: -33px; top: 4px; width: 14px; height: 14px; border: 3px solid #ffffff;"></span>
                                <div class="small text-muted mb-1"><?php echo date('h:i:s A', strtotime($v['created_at'])); ?></div>
                                
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="fw-bold text-danger"><?php echo escape($v['violation_type']); ?></span>
                                    <span class="badge bg-secondary-subtle text-secondary border small">IP: <?php echo escape($v['ip_address']); ?></span>
                                </div>
                                
                                <div class="text-dark small"><strong>Details:</strong> <?php echo escape($v['violation_reason']); ?></div>
                                <?php if (!empty($v['details'])): ?>
                                    <div class="text-muted small bg-light p-2 rounded mt-1 border"><code><?php echo escape($v['details']); ?></code></div>
                                <?php endif; ?>
                                <div class="text-secondary small mt-1">
                                    <i class="bi bi-link-45deg"></i> Page URL: <code><?php echo escape($v['page_url']); ?></code>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Final Submission or Termination Node -->
                    <?php if ($attempt['status'] === 'failed'): ?>
                        <div class="mb-2 position-relative">
                            <span class="position-absolute bg-danger rounded-circle" style="left: -33px; top: 4px; width: 14px; height: 14px; border: 3px solid #ffffff;"></span>
                            <div class="small text-muted mb-1"><?php echo date('h:i:s A', strtotime($attempt['failed_at'])); ?></div>
                            <div class="fw-bold text-danger"><i class="bi bi-shield-slash-fill me-1"></i>Test Terminated (FAILED)</div>
                            <span class="text-muted small">Auto-fail policy applied. Attempt locked. Result marked as FAILED - SECURITY VIOLATION.</span>
                        </div>
                    <?php elseif ($attempt['status'] === 'completed'): ?>
                        <div class="mb-2 position-relative">
                            <span class="position-absolute bg-success rounded-circle" style="left: -33px; top: 4px; width: 14px; height: 14px; border: 3px solid #ffffff;"></span>
                            <div class="small text-muted mb-1"><?php echo date('h:i:s A', strtotime($attempt['submitted_at'])); ?></div>
                            <div class="fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i>Test Completed Successfully</div>
                            <span class="text-muted small">Answers submitted. Score and outcomes evaluated on server.</span>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
