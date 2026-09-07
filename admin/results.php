<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';

// Handle Attempt Reset / Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $attempt_student_id = (int)$_GET['id'];
    
    // Check CSRF
    $token = $_GET['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $_SESSION['error_message'] = "Security token mismatch.";
        header("Location: results.php");
        exit();
    }
    
    try {
        // Deleting the attempt row triggers cascade delete on student_answers automatically
        $stmt = $pdo->prepare("DELETE FROM attempts WHERE student_id = ?");
        $stmt->execute([$attempt_student_id]);
        
        $_SESSION['success_message'] = "Student test attempt reset successfully. The student can now attempt the test again.";
    } catch (PDOException $e) {
        error_log("Failed deleting student attempt: " . $e->getMessage());
        $_SESSION['error_message'] = "Database error resetting student attempt.";
    }
    
    header("Location: results.php");
    exit();
}

$search = trim($_GET['search'] ?? '');
$filter_status = $_GET['status'] ?? ''; // 'pass', 'fail', or ''

$results = [];
try {
    $passing_percentage = (float)get_setting('passing_percentage', '40');
    
    // Build Query
    $query = "
        SELECT a.*, u.full_name, u.username, u.email
        FROM attempts a
        JOIN users u ON a.student_id = u.id
        WHERE u.role = 'student'
    ";
    
    $params = [];
    if (!empty($search)) {
        $query .= " AND (u.full_name LIKE ? OR u.username LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($filter_status === 'pass') {
        $query .= " AND a.percentage >= ?";
        $params[] = $passing_percentage;
    } elseif ($filter_status === 'fail') {
        $query .= " AND a.percentage < ?";
        $params[] = $passing_percentage;
    }
    
    $query .= " ORDER BY a.submitted_at DESC, a.started_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed loading attempts results: " . $e->getMessage());
}

$page_title = "Evaluation Results";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h2 fw-bold text-dark"><i class="bi bi-journal-check text-primary me-2"></i>Test Results</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <span class="badge bg-light text-dark border p-2">Passing Benchmark: <?php echo escape(get_setting('passing_percentage', '40')); ?>%</span>
    </div>
</div>

<?php echo display_alerts(); ?>

<!-- Search and Filter Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="results.php" method="GET" class="row g-3 align-items-center">
            <div class="col-md-5 col-sm-6">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" name="search" placeholder="Search student name or username..." value="<?php echo escape($search); ?>">
                </div>
            </div>
            
            <div class="col-md-4 col-sm-6">
                <select class="form-select" name="status">
                    <option value="">-- All Outcomes --</option>
                    <option value="pass" <?php echo ($filter_status === 'pass') ? 'selected' : ''; ?>>Pass Only</option>
                    <option value="fail" <?php echo ($filter_status === 'fail') ? 'selected' : ''; ?>>Fail Only</option>
                </select>
            </div>
            
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-secondary">Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<!-- Results Table -->
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Student</th>
                <th>Username</th>
                <th>Attempt Status</th>
                <th>Score</th>
                <th>Percentage</th>
                <th>Outcome</th>
                <th>Submission Date</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($results) === 0): ?>
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">No test results found.</td>
                </tr>
            <?php else: ?>
                <?php 
                $pass_pct = (float)get_setting('passing_percentage', '40');
                foreach ($results as $r): 
                    $is_completed = ($r['status'] === 'completed');
                    $is_pass = ($r['percentage'] >= $pass_pct);
                ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark"><?php echo escape($r['full_name']); ?></div>
                            <span class="small text-muted"><?php echo escape($r['email']); ?></span>
                        </td>
                        <td><code><?php echo escape($r['username']); ?></code></td>
                        <td>
                            <?php if ($is_completed): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Completed</span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">In Progress</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($is_completed): ?>
                                <strong class="text-primary"><?php echo escape($r['obtained_marks']); ?> / <?php echo escape($r['total_marks']); ?></strong>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($is_completed): ?>
                                <strong><?php echo format_percentage($r['percentage']); ?></strong>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($is_completed): ?>
                                <?php if ($is_pass): ?>
                                    <span class="badge bg-success">PASS</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">FAIL</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <?php 
                            if ($is_completed) {
                                echo date('M d, Y, g:i a', strtotime($r['submitted_at'])); 
                            } else {
                                echo 'Started: ' . date('M d, g:i a', strtotime($r['started_at']));
                            }
                            ?>
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <?php if ($is_completed): ?>
                                    <!-- View Result Details -->
                                    <a href="view_result.php?id=<?php echo $r['student_id']; ?>" class="btn btn-sm btn-outline-info" title="Inspect Answer Sheet">
                                        <i class="bi bi-eye"></i> Details
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Delete/Reset Attempt -->
                                <a href="results.php?action=delete&id=<?php echo $r['student_id']; ?>&csrf_token=<?php echo get_csrf_token(); ?>" class="btn btn-sm btn-outline-danger" title="Reset/Allow Re-attempt" onclick="return confirm('WARNING: Resetting this test attempt will permanently delete all stored answers and result data for this student, enabling them to attempt the test again from scratch. Proceed?');">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
