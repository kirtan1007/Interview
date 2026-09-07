<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$page_title = "Login";
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic rate limit protection / delay to prevent brute-forcing
    usleep(100000); // 0.1 sec delay
    
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $error = "Your account has been deactivated. Please contact the administrator.";
                } else {
                    // Regenerate session to prevent session fixation
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['created_time'] = time();
                    
                    if ($user['role'] === 'admin') {
                        header("Location: admin/dashboard.php");
                    } else {
                        header("Location: student/dashboard.php");
                    }
                    exit();
                }
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            error_log("Login query failed: " . $e->getMessage());
            $error = "An error occurred. Please try again later.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container d-flex align-items-center justify-content-center" style="min-height: calc(80vh - 56px);">
    <div class="login-container w-100 animate-fade-in">
        <div class="text-center mb-4">
            <div class="bg-primary text-white d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px;">
                <i class="bi bi-shield-lock-fill fs-3"></i>
            </div>
            <h3 class="fw-bold mb-1">Welcome Back</h3>
            <p class="text-muted">Sign in to your assessment portal</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div><?php echo escape($error); ?></div>
            </div>
        <?php endif; ?>
        
        <?php echo display_alerts(); ?>

        <form action="login.php" method="POST">
            <!-- CSRF Protection -->
            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
            
            <div class="mb-3">
                <label for="username" class="form-label fw-semibold">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" id="username" name="username" placeholder="Enter username" required autofocus autocomplete="username">
                </div>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-key"></i></span>
                    <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="Enter password" required autocomplete="current-password">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold" style="border-radius: var(--radius-sm);">
                Sign In <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </form>
        
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
