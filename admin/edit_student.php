<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';

$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$student = null;
$error = '';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        $_SESSION['error_message'] = "Student not found.";
        header("Location: students.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Failed to load student details: " . $e->getMessage());
    $_SESSION['error_message'] = "Database error loading record.";
    header("Location: students.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if (empty($full_name) || empty($username) || empty($email)) {
        $error = "Required fields cannot be empty.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        try {
            // Check username uniqueness
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $checkStmt->execute([$username, $student_id]);
            if ($checkStmt->fetch()) {
                $error = "Username '$username' is already taken.";
            } else {
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $error = "New password must be at least 6 characters long.";
                    } else {
                        // Update details + password
                        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                        $upStmt = $pdo->prepare("
                            UPDATE users 
                            SET full_name = ?, username = ?, email = ?, password = ?, status = ? 
                            WHERE id = ?
                        ");
                        $upStmt->execute([$full_name, $username, $email, $hashed_password, $status, $student_id]);
                    }
                } else {
                    // Update details only
                    $upStmt = $pdo->prepare("
                        UPDATE users 
                        SET full_name = ?, username = ?, email = ?, status = ? 
                        WHERE id = ?
                    ");
                    $upStmt->execute([$full_name, $username, $email, $status, $student_id]);
                }

                if (empty($error)) {
                    $_SESSION['success_message'] = "Student '$full_name' updated successfully.";
                    header("Location: students.php");
                    exit();
                }
            }
        } catch (PDOException $e) {
            error_log("Failed updating student record: " . $e->getMessage());
            $error = "Database error. Could not update record.";
        }
    }
}

$page_title = "Edit Student";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h2 fw-bold text-dark"><i class="bi bi-pencil text-primary me-2"></i>Edit Student Profile</h1>
    <a href="students.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to List</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div><?php echo escape($error); ?></div>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm col-lg-8 animate-fade-in">
    <div class="card-body p-4">
        <form action="edit_student.php?id=<?php echo $student_id; ?>" method="POST">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
            
            <div class="row g-3">
                <!-- Full Name -->
                <div class="col-md-6">
                    <label for="full_name" class="form-label fw-semibold">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo escape($student['full_name']); ?>" required>
                </div>
                
                <!-- Email -->
                <div class="col-md-6">
                    <label for="email" class="form-label fw-semibold">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo escape($student['email']); ?>" required>
                </div>
                
                <!-- Username -->
                <div class="col-md-6">
                    <label for="username" class="form-label fw-semibold">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo escape($student['username']); ?>" required>
                </div>
                
                <!-- Password (Optional Reset) -->
                <div class="col-md-6">
                    <label for="password" class="form-label fw-semibold">Reset Password <span class="text-muted font-normal">(Leave blank to keep current)</span></label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password">
                </div>

                <!-- Account Status -->
                <div class="col-md-6">
                    <label for="status" class="form-label fw-semibold">Account Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php echo ($student['status'] === 'active') ? 'selected' : ''; ?>>Active / Enabled</option>
                        <option value="inactive" <?php echo ($student['status'] === 'inactive') ? 'selected' : ''; ?>>Disabled / Inactive</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4 py-2"><i class="bi bi-save me-1"></i> Update Student</button>
                <a href="students.php" class="btn btn-light px-4 py-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
