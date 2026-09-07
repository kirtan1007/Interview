<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';

// Handle Question actions (delete / toggle status)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $question_id = (int)$_GET['id'];
    
    // Validate CSRF
    $token = $_GET['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $_SESSION['error_message'] = "Security token mismatch.";
        header("Location: questions.php");
        exit();
    }
    
    try {
        if ($action === 'toggle') {
            $stmt = $pdo->prepare("SELECT status FROM questions WHERE id = ?");
            $stmt->execute([$question_id]);
            $q = $stmt->fetch();
            
            if ($q) {
                $new_status = ($q['status'] === 'active') ? 'inactive' : 'active';
                $upStmt = $pdo->prepare("UPDATE questions SET status = ? WHERE id = ?");
                $upStmt->execute([$new_status, $question_id]);
                $_SESSION['success_message'] = "Question status updated successfully.";
            }
        } elseif ($action === 'delete') {
            $delStmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
            $delStmt->execute([$question_id]);
            $_SESSION['success_message'] = "Question deleted successfully.";
        }
    } catch (PDOException $e) {
        error_log("Failed updating question status: " . $e->getMessage());
        $_SESSION['error_message'] = "Database error processing action.";
    }
    
    header("Location: questions.php");
    exit();
}

// Search filter
$search = trim($_GET['search'] ?? '');
$questions = [];

try {
    if (!empty($search)) {
        $stmt = $pdo->prepare("
            SELECT * FROM questions 
            WHERE question_text LIKE ? OR option_a LIKE ? OR option_b LIKE ? OR option_c LIKE ? OR option_d LIKE ?
            ORDER BY created_at DESC
        ");
        $stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%", "%$search%"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM questions ORDER BY created_at DESC");
    }
    $questions = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed loading questions list: " . $e->getMessage());
}

$page_title = "Manage Questions";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h2 fw-bold text-dark"><i class="bi bi-question-circle text-primary me-2"></i>Questions Bank</h1>
    <a href="add_question.php" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Add Question</a>
</div>

<?php echo display_alerts(); ?>

<!-- Search Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form action="questions.php" method="GET" class="row g-3 align-items-center">
            <div class="col-md-9 col-sm-8">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" name="search" placeholder="Search by question text or choice contents..." value="<?php echo escape($search); ?>">
                </div>
            </div>
            <div class="col-md-3 col-sm-4 d-grid">
                <button type="submit" class="btn btn-secondary">Filter Bank</button>
            </div>
        </form>
    </div>
</div>

<!-- Questions Table -->
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th style="width: 45%;">Question Text</th>
                <th>Correct Ans</th>
                <th>Marks</th>
                <th>Status</th>
                <th>Created Date</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($questions) === 0): ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">No questions found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($questions as $q): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark lh-base text-truncate-custom" style="max-width: 450px; white-space: normal; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                <?php echo escape($q['question_text']); ?>
                            </div>
                            <div class="small text-muted mt-1">
                                <strong>A:</strong> <?php echo escape($q['option_a']); ?> | 
                                <strong>B:</strong> <?php echo escape($q['option_b']); ?> | 
                                <strong>C:</strong> <?php echo escape($q['option_c']); ?> | 
                                <strong>D:</strong> <?php echo escape($q['option_d']); ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 fs-6">
                                <?php echo escape($q['correct_answer']); ?>
                            </span>
                        </td>
                        <td><code><?php echo (int)$q['marks']; ?></code></td>
                        <td>
                            <?php if ($q['status'] === 'active'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?php echo date('M d, Y', strtotime($q['created_at'])); ?></td>
                        <td class="text-end">
                            <div class="btn-group">
                                <!-- Status Toggle -->
                                <a href="questions.php?action=toggle&id=<?php echo $q['id']; ?>&csrf_token=<?php echo get_csrf_token(); ?>" class="btn btn-sm <?php echo $q['status'] === 'active' ? 'btn-outline-warning' : 'btn-outline-success'; ?>" title="<?php echo $q['status'] === 'active' ? 'Deactivate Question' : 'Activate Question'; ?>">
                                    <i class="bi <?php echo $q['status'] === 'active' ? 'bi-lock' : 'bi-unlock'; ?>"></i>
                                </a>
                                
                                <!-- Edit -->
                                <a href="edit_question.php?id=<?php echo $q['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit Question">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                
                                <!-- Delete -->
                                <a href="questions.php?action=delete&id=<?php echo $q['id']; ?>&csrf_token=<?php echo get_csrf_token(); ?>" class="btn btn-sm btn-outline-danger" title="Delete Question" onclick="return confirm('Are you sure you want to delete this question? This will remove it from future tests.');">
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
