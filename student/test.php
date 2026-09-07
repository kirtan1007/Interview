<?php
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

$student_id = $_SESSION['user_id'];
enforce_test_security($student_id);

try {
    // 1. Fetch current attempt details
    $stmt = $pdo->prepare("SELECT * FROM attempts WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $attempt = $stmt->fetch();

    if (!$attempt) {
        header("Location: instructions.php");
        exit();
    }

    if ($attempt['status'] === 'completed') {
        header("Location: result.php");
        exit();
    }
    
    if ($attempt['status'] === 'failed') {
        header("Location: failed.php");
        exit();
    }

    // 2. Validate/Compute Time Limit
    $duration_minutes = (int)get_setting('test_duration', '30');
    $duration_seconds = $duration_minutes * 60;
    
    $started_time = strtotime($attempt['started_at']);
    $current_time = time();
    $elapsed_seconds = $current_time - $started_time;
    $time_remaining = $duration_seconds - $elapsed_seconds;

    // Server-side check for expiry
    if ($time_remaining <= -5) { // Add 5 second grace window
        header("Location: submit_test.php?auto_submit=1");
        exit();
    }

    // 3. Parse Question Order
    $question_order = explode(',', $attempt['question_order']);
    $total_questions = count($question_order);

    // 4. Resolve Active Question Index (1-indexed for user display)
    $current_q_num = isset($_GET['q']) ? (int)$_GET['q'] : 1;
    if ($current_q_num < 1) {
        $current_q_num = 1;
    } elseif ($current_q_num > $total_questions) {
        $current_q_num = $total_questions;
    }
    
    $active_question_id = $question_order[$current_q_num - 1];

    // 5. Fetch all questions for this attempt
    $placeholders = implode(',', array_fill(0, $total_questions, '?'));
    $qStmt = $pdo->prepare("SELECT * FROM questions WHERE id IN ($placeholders) AND status = 'active'");
    $qStmt->execute($question_order);
    $questions_rows = $qStmt->fetchAll();

    $questions_by_id = [];
    foreach ($questions_rows as $q) {
        $questions_by_id[$q['id']] = $q;
    }

    // 6. Fetch Already Answered Questions for Navigation Highlighting
    $ansStmt = $pdo->prepare("SELECT question_id, selected_answer FROM student_answers WHERE attempt_id = ?");
    $ansStmt->execute([$attempt['id']]);
    $answered_rows = $ansStmt->fetchAll();
    
    $saved_answers = [];
    foreach ($answered_rows as $row) {
        $saved_answers[$row['question_id']] = $row['selected_answer'];
    }

} catch (PDOException $e) {
    error_log("Failed loading test page: " . $e->getMessage());
    die("Database error occurred while loading test questions.");
}

$page_title = "Online Test";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-4 mt-2">
    <!-- Question Section -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <span id="current-q-badge" class="badge bg-primary px-3 py-2 fs-6">Question <?php echo $current_q_num; ?> of <?php echo $total_questions; ?></span>
                <div class="d-flex align-items-center">
                    <i class="bi bi-clock-history text-muted me-2 fs-5"></i>
                    <span id="test-timer" class="fs-5 fw-semibold text-primary" data-time-remaining="<?php echo $time_remaining; ?>">
                        --:--
                    </span>
                </div>
            </div>
            <div class="card-body p-4" id="questions-wrapper">
                
                <?php 
                $csrf_token_val = get_csrf_token();
                for ($i = 1; $i <= $total_questions; $i++): 
                    $qid = $question_order[$i - 1];
                    $question = $questions_by_id[$qid] ?? null;
                    if (!$question) continue;
                    $current_saved_answer = $saved_answers[$qid] ?? '';
                ?>
                <div class="question-slide" id="question-slide-<?php echo $i; ?>" data-q-num="<?php echo $i; ?>" style="<?php echo ($i === $current_q_num) ? '' : 'display: none;'; ?>">
                    <div class="mb-4">
                        <h4 class="fw-bold text-dark lh-base"><?php echo nl2br(escape($question['question_text'])); ?></h4>
                    </div>

                    <div class="options-container mb-4">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token_val; ?>">
                        
                        <!-- Option A -->
                        <div class="option-box <?php echo ($current_saved_answer === 'A') ? 'selected' : ''; ?>" data-option="A">
                            <input class="form-check-input me-3" type="radio" name="answer_<?php echo $qid; ?>" id="option_a_<?php echo $qid; ?>" value="A" data-question-id="<?php echo $qid; ?>" <?php echo ($current_saved_answer === 'A') ? 'checked' : ''; ?>>
                            <label class="form-check-label w-100 cursor-pointer" for="option_a_<?php echo $qid; ?>">
                                <strong>A.</strong> <?php echo escape($question['option_a']); ?>
                            </label>
                        </div>
                        
                        <!-- Option B -->
                        <div class="option-box <?php echo ($current_saved_answer === 'B') ? 'selected' : ''; ?>" data-option="B">
                            <input class="form-check-input me-3" type="radio" name="answer_<?php echo $qid; ?>" id="option_b_<?php echo $qid; ?>" value="B" data-question-id="<?php echo $qid; ?>" <?php echo ($current_saved_answer === 'B') ? 'checked' : ''; ?>>
                            <label class="form-check-label w-100 cursor-pointer" for="option_b_<?php echo $qid; ?>">
                                <strong>B.</strong> <?php echo escape($question['option_b']); ?>
                            </label>
                        </div>
                        
                        <!-- Option C -->
                        <div class="option-box <?php echo ($current_saved_answer === 'C') ? 'selected' : ''; ?>" data-option="C">
                            <input class="form-check-input me-3" type="radio" name="answer_<?php echo $qid; ?>" id="option_c_<?php echo $qid; ?>" value="C" data-question-id="<?php echo $qid; ?>" <?php echo ($current_saved_answer === 'C') ? 'checked' : ''; ?>>
                            <label class="form-check-label w-100 cursor-pointer" for="option_c_<?php echo $qid; ?>">
                                <strong>C.</strong> <?php echo escape($question['option_c']); ?>
                            </label>
                        </div>
                        
                        <!-- Option D -->
                        <div class="option-box <?php echo ($current_saved_answer === 'D') ? 'selected' : ''; ?>" data-option="D">
                            <input class="form-check-input me-3" type="radio" name="answer_<?php echo $qid; ?>" id="option_d_<?php echo $qid; ?>" value="D" data-question-id="<?php echo $qid; ?>" <?php echo ($current_saved_answer === 'D') ? 'checked' : ''; ?>>
                            <label class="form-check-label w-100 cursor-pointer" for="option_d_<?php echo $qid; ?>">
                                <strong>D.</strong> <?php echo escape($question['option_d']); ?>
                            </label>
                        </div>
                    </div>

                    <!-- Navigation Controls -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <div>
                            <?php if ($i > 1): ?>
                                <button type="button" class="btn btn-outline-secondary px-4 py-2 switch-q-btn" data-target-q="<?php echo $i - 1; ?>" onclick="navigateToQuestion(<?php echo $i - 1; ?>)">
                                    <i class="bi bi-arrow-left me-1"></i> Previous
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <?php if ($i < $total_questions): ?>
                                <button type="button" class="btn btn-primary px-4 py-2 switch-q-btn" data-target-q="<?php echo $i + 1; ?>" onclick="navigateToQuestion(<?php echo $i + 1; ?>)">
                                    Next <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            <?php else: ?>
                                <form action="submit_test.php" method="POST" class="d-inline" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = '<span class=\'spinner-border spinner-border-sm me-1\'></span> Submitting...';">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token_val; ?>">
                                    <button type="submit" class="btn btn-success px-4 py-2 fw-bold">
                                        <i class="bi bi-check2-circle me-1"></i> SUBMIT TEST
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>

            </div>
        </div>
    </div>

    <!-- Question Navigator Panel -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 sticky-lg-top" style="top: 80px;">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fw-bold mb-0"><i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Question Navigator</h5>
            </div>
            <div class="card-body p-4">
                <div class="nav-badge-grid mb-4">
                    <?php 
                    for ($i = 1; $i <= $total_questions; $i++) {
                        $q_id = $question_order[$i - 1];
                        
                        $badge_class = 'unanswered';
                        if ($i === $current_q_num) {
                            $badge_class = 'active';
                        } elseif (isset($saved_answers[$q_id]) && $saved_answers[$q_id] !== '') {
                            $badge_class = 'answered';
                        }
                        
                        echo '<a href="javascript:void(0);" id="nav-badge-' . $q_id . '" class="nav-badge ' . $badge_class . ' switch-q-btn" data-target-q="' . $i . '" onclick="navigateToQuestion(' . $i . ')">' . $i . '</a>';
                    }
                    ?>
                </div>

                <!-- Legend Indicator -->
                <div class="border-top pt-3">
                    <div class="d-flex align-items-center mb-2 small">
                        <span class="d-inline-block rounded-circle bg-primary me-2" style="width: 12px; height: 12px;"></span>
                        <span class="text-muted">Current Question</span>
                    </div>
                    <div class="d-flex align-items-center mb-2 small">
                        <span class="d-inline-block rounded-circle bg-success me-2" style="width: 12px; height: 12px;"></span>
                        <span class="text-muted">Answered Question</span>
                    </div>
                    <div class="d-flex align-items-center small">
                        <span class="d-inline-block rounded-circle bg-light border me-2" style="width: 12px; height: 12px;"></span>
                        <span class="text-muted">Unanswered Question</span>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <form action="submit_test.php" method="POST" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = '<span class=\'spinner-border spinner-border-sm me-1\'></span> Submitting...';">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token_val; ?>">
                        <button type="submit" class="btn btn-outline-danger w-100 fw-bold">
                            <i class="bi bi-check2-circle me-1"></i> Submit Test
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Hidden config parameters read by exam-security.js -->
<div id="security-config" 
     data-security-mode="<?php echo escape(get_setting('exam_security_mode', '1')); ?>"
     data-fullscreen-required="<?php echo escape(get_setting('fullscreen_required', '1')); ?>"
     data-tab-switch-detection="<?php echo escape(get_setting('tab_switch_detection', '1')); ?>"
     data-window-blur-detection="<?php echo escape(get_setting('window_blur_detection', '1')); ?>"
     data-copy-protection="<?php echo escape(get_setting('copy_protection', '1')); ?>"
     data-paste-protection="<?php echo escape(get_setting('paste_protection', '1')); ?>"
     data-right-click-protection="<?php echo escape(get_setting('right_click_protection', '1')); ?>"
     data-print-protection="<?php echo escape(get_setting('print_protection', '1')); ?>"
     data-shortcut-detection="<?php echo escape(get_setting('keyboard_shortcut_detection', '1')); ?>"
     data-devtools-detection="<?php echo escape(get_setting('devtools_detection', '1')); ?>">
</div>

<script>
window.navigateToQuestion = function(targetQ) {
    targetQ = parseInt(targetQ, 10);
    var slides = document.querySelectorAll('.question-slide');
    var total = slides.length;
    if (isNaN(targetQ) || targetQ < 1 || targetQ > total) return;

    for (var j = 0; j < slides.length; j++) {
        slides[j].style.display = 'none';
    }

    var active = document.getElementById('question-slide-' + targetQ);
    if (active) {
        active.style.display = 'block';
    }

    var badge = document.getElementById('current-q-badge');
    if (badge) {
        badge.textContent = 'Question ' + targetQ + ' of ' + total;
    }

    var navBadges = document.querySelectorAll('.nav-badge.switch-q-btn');
    for (var k = 0; k < navBadges.length; k++) {
        var t = parseInt(navBadges[k].getAttribute('data-target-q'), 10);
        if (t === targetQ) {
            navBadges[k].classList.add('active');
        } else {
            navBadges[k].classList.remove('active');
        }
    }

    if (window.history && window.history.replaceState) {
        window.history.replaceState(null, '', '?q=' + targetQ);
    }
};
</script>

<script src="../assets/js/exam-security.js?v=<?php echo time(); ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
