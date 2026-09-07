<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';

// Handle Clearing Logs
if (isset($_GET['action']) && $_GET['action'] === 'clear_all') {
    // CSRF Check
    $token = $_GET['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $_SESSION['error_message'] = "Security token mismatch.";
        header("Location: security_logs.php");
        exit();
    }
    try {
        $pdo->query("DELETE FROM security_violations");
        $_SESSION['success_message'] = "All security violation logs cleared successfully.";
    } catch (PDOException $e) {
        error_log("Failed clearing security logs: " . $e->getMessage());
        $_SESSION['error_message'] = "Database error clearing logs.";
    }
    header("Location: security_logs.php");
    exit();
}

$search = trim($_GET['search'] ?? '');
$filter_type = $_GET['type'] ?? '';
$filter_date = $_GET['date'] ?? '';

$violations = [];
try {
    $query = "
        SELECT sv.*, u.full_name, u.username, a.security_status
        FROM security_violations sv
        JOIN users u ON sv.student_id = u.id
        JOIN attempts a ON sv.attempt_id = a.id
        WHERE 1=1
    ";
    
    $params = [];
    if (!empty($search)) {
        $query .= " AND (u.full_name LIKE ? OR u.username LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($filter_type)) {
        $query .= " AND sv.violation_type = ?";
        $params[] = $filter_type;
    }
    
    if (!empty($filter_date)) {
        $query .= " AND DATE(sv.created_at) = ?";
        $params[] = $filter_date;
    }
    
    $query .= " ORDER BY sv.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $violations = $stmt->fetchAll();
    
    // Get unique violation types for filter dropdown
    $typesStmt = $pdo->query("SELECT DISTINCT violation_type FROM security_violations ORDER BY violation_type ASC");
    $violation_types = $typesStmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Failed loading security violations log: " . $e->getMessage());
}

$page_title = "Security Audit Logs";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h2 fw-bold text-dark"><i class="bi bi-shield-exclamation text-danger me-2"></i>Exam Security Logs</h1>
    <?php if (count($violations) > 0): ?>
        <a href="security_logs.php?action=clear_all&csrf_token=<?php echo get_csrf_token(); ?>" class="btn btn-outline-danger" onclick="return confirm('WARNING: Are you sure you want to delete all historical proctoring logs? This action is irreversible.');">
            <i class="bi bi-trash3 me-1"></i> Clear Logs
        </a>
    <?php endif; ?>
</div>

<?php echo display_alerts(); ?>

<!-- Search Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="security_logs.php" method="GET" class="row g-3 align-items-center">
            <!-- Student Search -->
            <div class="col-md-4 col-sm-6">
                <label class="form-label small fw-semibold text-muted">Search Student</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" name="search" placeholder="Name or username..." value="<?php echo escape($search); ?>">
                </div>
            </div>
            
            <!-- Type Filter -->
            <div class="col-md-3 col-sm-6">
                <label class="form-label small fw-semibold text-muted">Violation Type</label>
                <select class="form-select" name="type">
                    <option value="">-- All Types --</option>
                    <?php foreach ($violation_types as $vt): ?>
                        <option value="<?php echo escape($vt); ?>" <?php echo ($filter_type === $vt) ? 'selected' : ''; ?>><?php echo escape($vt); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Date Filter -->
            <div class="col-md-3 col-sm-6">
                <label class="form-label small fw-semibold text-muted">Filter Date</label>
                <input type="date" class="form-control" name="date" value="<?php echo escape($filter_date); ?>">
            </div>
            
            <!-- Submit Button -->
            <div class="col-md-2 col-sm-6 d-grid pt-4">
                <button type="submit" class="btn btn-secondary">Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Student</th>
                <th>Violation Code</th>
                <th>Reason</th>
                <th>Outcome Status</th>
                <th>IP Address</th>
                <th>Timestamp</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($violations) === 0): ?>
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No security logs recorded.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($violations as $v): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark"><?php echo escape($v['full_name']); ?></div>
                            <code><?php echo escape($v['username']); ?></code>
                        </td>
                        <td>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2.5 py-1.5 fs-6">
                                <?php echo escape($v['violation_type']); ?>
                            </span>
                        </td>
                        <td class="small text-muted" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo escape($v['violation_reason']); ?>">
                            <?php echo escape($v['violation_reason']); ?>
                        </td>
                        <td>
                            <?php 
                            $sec_status = $v['security_status'] ?? 'SAFE';
                            if ($sec_status === 'FAILED') {
                                echo '<span class="badge bg-danger">FAILED</span>';
                            } elseif ($sec_status === 'VIOLATION') {
                                echo '<span class="badge bg-warning text-dark">VIOLATION</span>';
                            } else {
                                echo '<span class="badge bg-secondary">WARNING</span>';
                            }
                            ?>
                        </td>
                        <td><code><?php echo escape($v['ip_address']); ?></code></td>
                        <td class="small text-muted"><?php echo date('M d, Y h:i:s A', strtotime($v['created_at'])); ?></td>
                        <td class="text-end">
                            <a href="view_security_log.php?id=<?php echo $v['attempt_id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-timeline"></i> Audit Timeline
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
