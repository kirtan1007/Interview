<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Dynamically determine base path for relative URLs
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$is_subfolder = ($current_dir === 'admin' || $current_dir === 'student');
$base_path = $is_subfolder ? '../' : './';
$site_name = get_setting('site_name', 'TechEval Pro');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? escape($page_title) . " - " . escape($site_name) : escape($site_name); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom Style Sheet -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css">
</head>
<body>
    
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand d-flex align-items-center" href="<?php echo $base_path; ?>index.php">
                <i class="bi bi-cpu-fill me-2 text-primary"></i>
                <span class="fw-bold"><?php echo escape($site_name); ?></span>
            </a>
            
            <!-- Navbar Toggler for Mobile -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item d-lg-none">
                                <a class="nav-link" href="<?php echo $base_path; ?>admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
                            </li>
                            <li class="nav-item d-lg-none">
                                <a class="nav-link" href="<?php echo $base_path; ?>admin/students.php"><i class="bi bi-people me-2"></i>Students</a>
                            </li>
                            <li class="nav-item d-lg-none">
                                <a class="nav-link" href="<?php echo $base_path; ?>admin/questions.php"><i class="bi bi-question-circle me-2"></i>Questions</a>
                            </li>
                            <li class="nav-item d-lg-none">
                                <a class="nav-link" href="<?php echo $base_path; ?>admin/results.php"><i class="bi bi-journal-check me-2"></i>Results</a>
                            </li>
                            <li class="nav-item d-lg-none">
                                <a class="nav-link" href="<?php echo $base_path; ?>admin/settings.php"><i class="bi bi-gear me-2"></i>Settings</a>
                            </li>
                            <li class="nav-item d-lg-none">
                                <a class="nav-link" href="<?php echo $base_path; ?>admin/security_logs.php"><i class="bi bi-shield-exclamation me-2 text-danger"></i>Security Logs</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-badge-fill me-2 text-primary"></i>
                                    <span><?php echo escape($_SESSION['full_name']); ?> (Admin)</span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="adminDropdown">
                                    <li><a class="dropdown-menu-item dropdown-item" href="<?php echo $base_path; ?>admin/settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-menu-item dropdown-item text-danger" href="<?php echo $base_path; ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <span class="nav-link text-light me-3"><i class="bi bi-person-fill text-success me-2"></i><?php echo escape($_SESSION['full_name']); ?></span>
                            </li>
                            <li class="nav-item">
                                <a class="btn btn-outline-danger btn-sm" href="<?php echo $base_path; ?>logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
                            </li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link active" href="<?php echo $base_path; ?>login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Admin View Layout Grid -->
    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin' && $is_subfolder): ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation for Desktop -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse shadow-sm border-end border-secondary">
                <div class="position-sticky pt-3 sidebar-sticky">
                    <ul class="nav flex-column gap-2">
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2 py-2 px-3 rounded text-light <?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'bg-primary active' : 'hover-bg'; ?>" href="<?php echo $base_path; ?>admin/dashboard.php">
                                <i class="bi bi-speedometer2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2 py-2 px-3 rounded text-light <?php echo (basename($_SERVER['PHP_SELF']) === 'students.php' || basename($_SERVER['PHP_SELF']) === 'add_student.php' || basename($_SERVER['PHP_SELF']) === 'edit_student.php') ? 'bg-primary active' : 'hover-bg'; ?>" href="<?php echo $base_path; ?>admin/students.php">
                                <i class="bi bi-people"></i>
                                Students
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2 py-2 px-3 rounded text-light <?php echo (basename($_SERVER['PHP_SELF']) === 'questions.php' || basename($_SERVER['PHP_SELF']) === 'add_question.php' || basename($_SERVER['PHP_SELF']) === 'edit_question.php') ? 'bg-primary active' : 'hover-bg'; ?>" href="<?php echo $base_path; ?>admin/questions.php">
                                <i class="bi bi-question-circle"></i>
                                Questions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2 py-2 px-3 rounded text-light <?php echo (basename($_SERVER['PHP_SELF']) === 'results.php' || basename($_SERVER['PHP_SELF']) === 'view_result.php') ? 'bg-primary active' : 'hover-bg'; ?>" href="<?php echo $base_path; ?>admin/results.php">
                                <i class="bi bi-journal-check"></i>
                                Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2 py-2 px-3 rounded text-light <?php echo (basename($_SERVER['PHP_SELF']) === 'settings.php') ? 'bg-primary active' : 'hover-bg'; ?>" href="<?php echo $base_path; ?>admin/settings.php">
                                <i class="bi bi-gear"></i>
                                Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2 py-2 px-3 rounded text-light <?php echo (basename($_SERVER['PHP_SELF']) === 'security_logs.php' || basename($_SERVER['PHP_SELF']) === 'view_security_log.php') ? 'bg-primary active' : 'hover-bg'; ?>" href="<?php echo $base_path; ?>admin/security_logs.php">
                                <i class="bi bi-shield-exclamation text-danger"></i>
                                Security Logs
                            </a>
                        </li>
                        <li class="nav-item mt-4 border-top border-secondary pt-3">
                            <a class="nav-link d-flex align-items-center gap-2 py-2 px-3 rounded text-danger hover-bg-danger" href="<?php echo $base_path; ?>logout.php">
                                <i class="bi bi-box-arrow-right"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            <!-- Main Content panel Wrapper -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-container">
    <?php else: ?>
    <main class="py-4">
        <div class="container">
    <?php endif; ?>
