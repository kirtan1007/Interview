<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$full_name = '';
$username = '';
$email = '';
$status = 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if (empty($full_name) || empty($username) || empty($password) || empty($email)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($username) < 3) {
        $error = "Username must be at least 3 characters long.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        try {
            // Check uniqueness of username
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $checkStmt->execute([$username]);
            if ($checkStmt->fetch()) {
                $error = "Username '$username' is already taken.";
            } else {
                // Securely hash password and save
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (full_name, username, password, role, email, status) 
                    VALUES (?, ?, ?, 'student', ?, ?)
                ");
                $stmt->execute([$full_name, $username, $hashed_password, $email, $status]);
                
                $_SESSION['success_message'] = "Student '$full_name' created successfully!";
                header("Location: students.php");
                exit();
            }
        } catch (PDOException $e) {
            error_log("Failed to insert student record: " . $e->getMessage());
            $error = "Database error. Could not create student account.";
        }
    }
}

$page_title = "Add Student";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h2 fw-bold text-dark"><i class="bi bi-person-plus text-primary me-2"></i>Add Student Account</h1>
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
        <form action="add_student.php" method="POST">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
            
            <div class="row g-3">
                <!-- Full Name -->
                <div class="col-md-6">
                    <label for="full_name" class="form-label fw-semibold">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" placeholder="full name" value="<?php echo escape($full_name); ?>" required>
                </div>
                
                <!-- Email -->
                <div class="col-md-6">
                    <label for="email" class="form-label fw-semibold">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="your email" value="<?php echo escape($email); ?>" required>
                </div>
                
                <!-- Username -->
                <div class="col-md-6">
                    <label for="username" class="form-label fw-semibold">Username</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="name" value="<?php echo escape($username); ?>" required>
                </div>
                
                <!-- Password -->
                <div class="col-md-6">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Min 6 characters" required>
                </div>

                <!-- Account Status -->
                <div class="col-md-6">
                    <label for="status" class="form-label fw-semibold">Account Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php echo ($status === 'active') ? 'selected' : ''; ?>>Active / Enabled</option>
                        <option value="inactive" <?php echo ($status === 'inactive') ? 'selected' : ''; ?>>Disabled / Inactive</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4 py-2"><i class="bi bi-save me-1"></i> Save Student</button>
                <a href="students.php" class="btn btn-light px-4 py-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
