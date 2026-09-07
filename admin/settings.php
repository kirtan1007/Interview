<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    
    // Extract and trim POST inputs
    $site_name = trim($_POST['site_name'] ?? '');
    $test_title = trim($_POST['test_title'] ?? '');
    $passing_percentage = trim($_POST['passing_percentage'] ?? '');
    $test_duration = trim($_POST['test_duration'] ?? '');
    $randomize_questions = isset($_POST['randomize_questions']) ? '1' : '0';
    $show_answer_key = isset($_POST['show_answer_key']) ? '1' : '0';
    $show_result_immediately = isset($_POST['show_result_immediately']) ? '1' : '0';
    $instructions = trim($_POST['instructions'] ?? '');

    // Proctoring inputs
    $exam_security_mode = isset($_POST['exam_security_mode']) ? '1' : '0';
    $fullscreen_required = isset($_POST['fullscreen_required']) ? '1' : '0';
    $tab_switch_detection = isset($_POST['tab_switch_detection']) ? '1' : '0';
    $window_blur_detection = isset($_POST['window_blur_detection']) ? '1' : '0';
    $copy_protection = isset($_POST['copy_protection']) ? '1' : '0';
    $paste_protection = isset($_POST['paste_protection']) ? '1' : '0';
    $right_click_protection = isset($_POST['right_click_protection']) ? '1' : '0';
    $print_protection = isset($_POST['print_protection']) ? '1' : '0';
    $shortcut_detection = isset($_POST['keyboard_shortcut_detection']) ? '1' : '0';
    $devtools_detection = isset($_POST['devtools_detection']) ? '1' : '0';
    $auto_fail_on_critical = isset($_POST['auto_fail_on_critical']) ? '1' : '0';
    $max_violations = (int)($_POST['max_violations'] ?? 3);

    // Basic Validation
    if (empty($site_name) || empty($test_title) || empty($passing_percentage) || empty($test_duration)) {
        $error = "Please fill in all required settings fields.";
    } elseif (!is_numeric($passing_percentage) || $passing_percentage < 0 || $passing_percentage > 100) {
        $error = "Passing percentage must be a number between 0 and 100.";
    } elseif (!is_numeric($test_duration) || $test_duration < 1) {
        $error = "Test duration must be a number of at least 1 minute.";
    } elseif ($max_violations < 1) {
        $error = "Maximum violations must be at least 1.";
    } else {
        // Update general configurations
        set_setting('site_name', $site_name);
        set_setting('test_title', $test_title);
        set_setting('passing_percentage', $passing_percentage);
        set_setting('test_duration', $test_duration);
        set_setting('randomize_questions', $randomize_questions);
        set_setting('show_answer_key', $show_answer_key);
        set_setting('show_result_immediately', $show_result_immediately);
        set_setting('instructions', $instructions);

        // Update proctoring configurations
        set_setting('exam_security_mode', $exam_security_mode);
        set_setting('fullscreen_required', $fullscreen_required);
        set_setting('tab_switch_detection', $tab_switch_detection);
        set_setting('window_blur_detection', $window_blur_detection);
        set_setting('copy_protection', $copy_protection);
        set_setting('paste_protection', $paste_protection);
        set_setting('right_click_protection', $right_click_protection);
        set_setting('print_protection', $print_protection);
        set_setting('keyboard_shortcut_detection', $shortcut_detection);
        set_setting('devtools_detection', $devtools_detection);
        set_setting('auto_fail_on_critical', $auto_fail_on_critical);
        set_setting('max_violations', $max_violations);
        
        $_SESSION['success_message'] = "System settings updated successfully!";
        header("Location: settings.php");
        exit();
    }
}

// Fetch current general configurations
$site_name = get_setting('site_name', 'TechEval Pro');
$test_title = get_setting('test_title', 'PHP & MySQL Technical Assessment');
$passing_percentage = get_setting('passing_percentage', '40');
$test_duration = get_setting('test_duration', '30');
$randomize_questions = get_setting('randomize_questions', '1');
$show_answer_key = get_setting('show_answer_key', '1');
$show_result_immediately = get_setting('show_result_immediately', '1');
$instructions = get_setting('instructions', '');

// Fetch current proctoring configurations
$exam_security_mode = get_setting('exam_security_mode', '1');
$fullscreen_required = get_setting('fullscreen_required', '1');
$tab_switch_detection = get_setting('tab_switch_detection', '1');
$window_blur_detection = get_setting('window_blur_detection', '1');
$copy_protection = get_setting('copy_protection', '1');
$paste_protection = get_setting('paste_protection', '1');
$right_click_protection = get_setting('right_click_protection', '1');
$print_protection = get_setting('print_protection', '1');
$shortcut_detection = get_setting('keyboard_shortcut_detection', '1');
$devtools_detection = get_setting('devtools_detection', '1');
$auto_fail_on_critical = get_setting('auto_fail_on_critical', '1');
$max_violations = get_setting('max_violations', '3');

$page_title = "Global Settings";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <h1 class="h2 fw-bold text-dark"><i class="bi bi-gear text-primary me-2"></i>Global Test Settings</h1>
</div>

<?php echo display_alerts(); ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div><?php echo escape($error); ?></div>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm col-lg-10 animate-fade-in mb-5">
    <div class="card-body p-4">
        <form action="settings.php" method="POST">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
            
            <!-- 1. General Configs -->
            <h5 class="fw-bold mb-3"><i class="bi bi-display text-primary me-2"></i>General Configurations</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="site_name" class="form-label fw-semibold">Site Name</label>
                    <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo escape($site_name); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="test_title" class="form-label fw-semibold">Test Title</label>
                    <input type="text" class="form-control" id="test_title" name="test_title" value="<?php echo escape($test_title); ?>" required>
                </div>
            </div>

            <!-- 2. Rules -->
            <h5 class="fw-bold mb-3 pt-3 border-top"><i class="bi bi-sliders text-primary me-2"></i>Assessment Rules</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="passing_percentage" class="form-label fw-semibold">Passing Benchmark (%)</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="passing_percentage" name="passing_percentage" min="0" max="100" value="<?php echo (float)$passing_percentage; ?>" required>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="test_duration" class="form-label fw-semibold">Test Duration (Minutes)</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="test_duration" name="test_duration" min="1" value="<?php echo (int)$test_duration; ?>" required>
                        <span class="input-group-text">Min</span>
                    </div>
                </div>
            </div>

            <!-- 3. Preferences -->
            <h5 class="fw-bold mb-3 pt-3 border-top"><i class="bi bi-toggle-on text-primary me-2"></i>Preferences & Toggles</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="form-check form-switch p-3 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2 float-none" type="checkbox" role="switch" id="randomize_questions" name="randomize_questions" <?php echo ($randomize_questions === '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-semibold" for="randomize_questions">Randomize Questions</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch p-3 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2 float-none" type="checkbox" role="switch" id="show_answer_key" name="show_answer_key" <?php echo ($show_answer_key === '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-semibold" for="show_answer_key">Show Answer Key</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch p-3 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2 float-none" type="checkbox" role="switch" id="show_result_immediately" name="show_result_immediately" <?php echo ($show_result_immediately === '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-semibold" for="show_result_immediately">Show Scores Instantly</label>
                    </div>
                </div>
            </div>

            <!-- 4. Proctoring settings -->
            <h5 class="fw-bold text-danger mb-3 pt-3 border-top"><i class="bi bi-shield-lock-fill text-danger me-2"></i>Exam Proctoring & Security Rules</h5>
            
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="form-check form-switch p-3 bg-danger bg-opacity-10 text-danger-emphasis rounded border border-danger-subtle">
                        <input class="form-check-input ms-0 me-2 float-none" type="checkbox" role="switch" id="exam_security_mode" name="exam_security_mode" <?php echo ($exam_security_mode === '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="exam_security_mode">Enable Exam Proctoring System</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch p-3 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2 float-none" type="checkbox" role="switch" id="auto_fail_on_critical" name="auto_fail_on_critical" <?php echo ($auto_fail_on_critical === '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-semibold" for="auto_fail_on_critical">Auto-Fail on Critical Violation</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="max_violations" class="form-label fw-semibold">Maximum Security Warnings Allowed</label>
                    <input type="number" class="form-control" id="max_violations" name="max_violations" min="1" value="<?php echo (int)$max_violations; ?>" required>
                    <span class="text-muted small">Automatic fail occurs when warning count reaches this number.</span>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <h6 class="fw-bold mb-2">Enable Individual Detection Filters:</h6>
                <!-- Fullscreen Required -->
                <div class="col-md-4">
                    <div class="form-check form-switch p-2 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2 float-none" type="checkbox" role="switch" id="fullscreen_required" name="fullscreen_required" <?php echo ($fullscreen_required === '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="fullscreen_required">Fullscreen Required</label>
                    </div>
                </div>
                <!-- Tab Switch -->
                <div class="col-md-4">
                    <div class="form-check form-switch p-2 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2 float-none" type="checkbox" role="switch" id="tab_switch_detection" name="tab_switch_detection" <?php echo ($tab_switch_detection === '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="tab_switch_detection">Tab Switch Detection</label>
                    </div>
                </div>
                <!-- Focus Blur -->
                <div class="col-md-4">
                    <div class="form-check form-switch p-2 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2 float-none" type="checkbox" role="switch" id="window_blur_detection" name="window_blur_detection" <?php echo ($window_blur_detection === '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="window_blur_detection">Window Blur Detection</label>
                    </div>
                </div>
                <!-- Copy block -->
                <div class="col-md-4">
                    <div class="form-check form-switch p-2 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2 float-none" type="checkbox" role="switch" id="copy_protection" name="copy_protection" <?php echo ($copy_protection === '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="copy_protection">Copy/Cut Protection</label>
                    </div>
                </div>
                <!-- Paste Block -->
                <div class="col-md-4">
                    <div class="form-check form-switch p-2 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2 float-none" type="checkbox" role="switch" id="paste_protection" name="paste_protection" <?php echo ($paste_protection === '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="paste_protection">Paste Protection</label>
                    </div>
                </div>
                <!-- Context menu Block -->
                <div class="col-md-4">
                    <div class="form-check form-switch p-2 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2 float-none" type="checkbox" role="switch" id="right_click_protection" name="right_click_protection" <?php echo ($right_click_protection === '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="right_click_protection">Right-Click Protection</label>
                    </div>
                </div>
                <!-- Print Block -->
                <div class="col-md-4">
                    <div class="form-check form-switch p-2 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2 float-none" type="checkbox" role="switch" id="print_protection" name="print_protection" <?php echo ($print_protection === '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="print_protection">Print Protection</label>
                    </div>
                </div>
                <!-- Shortcut Detect -->
                <div class="col-md-4">
                    <div class="form-check form-switch p-2 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2 float-none" type="checkbox" role="switch" id="keyboard_shortcut_detection" name="keyboard_shortcut_detection" <?php echo ($shortcut_detection === '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="keyboard_shortcut_detection">Shortcut Prevention</label>
                    </div>
                </div>
                <!-- DevTools heuristic -->
                <div class="col-md-4">
                    <div class="form-check form-switch p-2 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2 float-none" type="checkbox" role="switch" id="devtools_detection" name="devtools_detection" <?php echo ($devtools_detection === '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="devtools_detection">DevTools Detection Heuristics</label>
                    </div>
                </div>
            </div>

            <!-- Candidate Terms Instructions -->
            <h5 class="fw-bold mb-3 pt-3 border-top"><i class="bi bi-file-earmark-text text-primary me-2"></i>Candidate Instructions</h5>
            <div class="mb-4">
                <textarea class="form-control" id="instructions" name="instructions" rows="6" placeholder="Enter terms/instructions candidates read before launching the test..."><?php echo escape($instructions); ?></textarea>
            </div>

            <div class="d-flex gap-2 pt-3 border-top">
                <button type="submit" class="btn btn-primary px-4 py-2"><i class="bi bi-save me-1"></i> Save Configuration</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
