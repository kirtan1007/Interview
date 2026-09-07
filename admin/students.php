<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';

// Handle Student Actions (Toggle Status / Delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $student_id = (int)$_GET['id'];
    
    // Check CSRF from URL
    $token = $_GET['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $_SESSION['error_message'] = "Invalid security token.";
        header("Location: students.php");
        exit();
    }
    
    try {
        if ($action === 'toggle') {
            $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ? AND role = 'student'");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();
            
            if ($student) {
                $new_status = ($student['status'] === 'active') ? 'inactive' : 'active';
                $upStmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                $upStmt->execute([$new_status, $student_id]);
                $_SESSION['success_message'] = "Student status updated successfully.";
            }
        } elseif ($action === 'delete') {
            $delStmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
            $delStmt->execute([$student_id]);
            $_SESSION['success_message'] = "Student deleted successfully.";
        }
    } catch (PDOException $e) {
        error_log("Failed updating student record: " . $e->getMessage());
        $_SESSION['error_message'] = "Database error processing action.";
    }
    
    header("Location: students.php");
    exit();
}

// Search & Pagination Logic
$search = trim($_GET['search'] ?? '');
$students = [];

try {
    if (!empty($search)) {
        $stmt = $pdo->prepare("
            SELECT u.*, a.status AS test_status, a.percentage, a.obtained_marks, a.total_marks
            FROM users u
            LEFT JOIN attempts a ON u.id = a.student_id
            WHERE u.role = 'student' AND (u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)
            ORDER BY u.created_at DESC
        ");
        $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    } else {
        $stmt = $pdo->query("
            SELECT u.*, a.status AS test_status, a.percentage, a.obtained_marks, a.total_marks
            FROM users u
            LEFT JOIN attempts a ON u.id = a.student_id
            WHERE u.role = 'student'
            ORDER BY u.created_at DESC
        ");
    }
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed fetching students list: " . $e->getMessage());
}

$page_title = "Manage Students";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h2 fw-bold text-dark"><i class="bi bi-people text-primary me-2"></i>Students Management</h1>
    <a href="add_student.php" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> Add Student</a>
</div>

<?php echo display_alerts(); ?>

<!-- Search Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form action="students.php" method="GET" class="row g-3 align-items-center">
            <div class="col-md-9 col-sm-8">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" name="search" placeholder="Search by name, username, or email..." value="<?php echo escape($search); ?>">
                </div>
            </div>
            <div class="col-md-3 col-sm-4 d-grid">
                <button type="submit" class="btn btn-secondary">Filter Results</button>
            </div>
        </form>
    </div>
</div>

<!-- Students Listing Table -->
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Status</th>
                <th>Test Status</th>
                <th>Score</th>
                <th>Created Date</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($students) === 0): ?>
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">No students found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark"><?php echo escape($s['full_name']); ?></div>
                        </td>
                        <td><code><?php echo escape($s['username']); ?></code></td>
                        <td><?php echo escape($s['email']); ?></td>
                        <td>
                            <?php if ($s['status'] === 'active'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $test_status = $s['test_status'] ?? 'not_started';
                            if ($test_status === 'completed') {
                                echo '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Completed</span>';
                            } elseif ($test_status === 'in_progress') {
                                echo '<span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i> In Progress</span>';
                            } else {
                                echo '<span class="badge bg-secondary"><i class="bi bi-circle me-1"></i> Not Started</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            if ($test_status === 'completed') {
                                echo '<strong class="text-primary">' . escape($s['obtained_marks']) . '/' . escape($s['total_marks']) . '</strong> <span class="text-muted small">(' . format_percentage($s['percentage']) . ')</span>';
                            } else {
                                echo '<span class="text-muted">-</span>';
                            }
                            ?>
                        </td>
                        <td class="small text-muted"><?php echo date('M d, Y', strtotime($s['created_at'])); ?></td>
                        <td class="text-end">
                            <div class="btn-group">
                                <!-- View result if completed -->
                                <?php if ($test_status === 'completed'): ?>
                                    <a href="view_result.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-info" title="View Result">
                                        <i class="bi bi-file-earmark-bar-graph"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Toggle status button -->
                                <a href="students.php?action=toggle&id=<?php echo $s['id']; ?>&csrf_token=<?php echo get_csrf_token(); ?>" class="btn btn-sm <?php echo $s['status'] === 'active' ? 'btn-outline-warning' : 'btn-outline-success'; ?>" title="<?php echo $s['status'] === 'active' ? 'Disable Account' : 'Enable Account'; ?>">
                                    <i class="bi <?php echo $s['status'] === 'active' ? 'bi-lock' : 'bi-unlock'; ?>"></i>
                                </a>
                                
                                <!-- Edit Student -->
                                <a href="edit_student.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit Student">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                
                                <!-- Delete Student -->
                                <a href="students.php?action=delete&id=<?php echo $s['id']; ?>&csrf_token=<?php echo get_csrf_token(); ?>" class="btn btn-sm btn-outline-danger" title="Delete Student" onclick="return confirm('Are you sure you want to delete this student account? This will permanently remove all associated test records and answers.');">
                                    <i class="bi bi-trash"></i>
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
