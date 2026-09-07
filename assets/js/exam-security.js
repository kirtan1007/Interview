document.addEventListener("DOMContentLoaded", function () {
    // Read proctoring configurations from DOM attributes set on the test page
    const configEl = document.getElementById("security-config");
    if (!configEl) return;

    const securityMode = configEl.dataset.securityMode === '1';
    if (!securityMode) return;

    const fullscreenRequired = configEl.dataset.fullscreenRequired === '1';
    const tabSwitchDetection = configEl.dataset.tabSwitchDetection === '1';
    const windowBlurDetection = configEl.dataset.windowBlurDetection === '1';
    const copyProtection = configEl.dataset.copyProtection === '1';
    const pasteProtection = configEl.dataset.pasteProtection === '1';
    const rightClickProtection = configEl.dataset.rightClickProtection === '1';
    const printProtection = configEl.dataset.printProtection === '1';
    const shortcutDetection = configEl.dataset.shortcutDetection === '1';
    const devtoolsDetection = configEl.dataset.devtoolsDetection === '1';

    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
    const lastSentTime = {};

    let isShowingAlert = false;
    let isSubmitting = false;
    window.isSubmittingExam = false;

    // Track when test is submitted to avoid triggering TAB_SWITCH or FULLSCREEN_EXIT during page transition
    const testForm = document.getElementById("test-form");
    if (testForm) {
        testForm.addEventListener("submit", function () {
            isSubmitting = true;
            window.isSubmittingExam = true;
        });

        // Wrap programmatic submit (e.g. from countdown timer)
        const originalSubmit = testForm.submit.bind(testForm);
        testForm.submit = function () {
            isSubmitting = true;
            window.isSubmittingExam = true;
            originalSubmit();
        };
    }

    // Capture submit button clicks
    document.addEventListener("click", function (e) {
        const target = e.target.closest('button[type="submit"], input[type="submit"]');
        if (target) {
            isSubmitting = true;
            window.isSubmittingExam = true;
        }
    });

    window.addEventListener("beforeunload", function () {
        isSubmitting = true;
        window.isSubmittingExam = true;
    });

    window.addEventListener("pagehide", function () {
        isSubmitting = true;
        window.isSubmittingExam = true;
    });

    // 1. Unified AJAX Violation Reporter (with Cooldown)
    function reportViolation(type, reason, details = '') {
        // Do not report security violations if exam is already being submitted
        if (isSubmitting || window.isSubmittingExam) {
            return;
        }

        if (isShowingAlert && (type === 'WINDOW_BLUR' || type === 'WINDOW_FOCUS_RETURN')) {
            return;
        }

        const now = Date.now();
        // Cooldown: limit database records for same event type to once every 3 seconds
        if (lastSentTime[type] && (now - lastSentTime[type] < 3000)) {
            return;
        }
        lastSentTime[type] = now;

        const formData = new FormData();
        formData.append('violation_type', type);
        formData.append('violation_reason', reason);
        formData.append('details', details);
        formData.append('csrf_token', csrfToken);

        fetch('security_event.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'failed') {
                isShowingAlert = true;
                alert("Exam Terminated: " + data.message);
                isShowingAlert = false;
                window.location.href = 'failed.php';
            } else if (data.status === 'warning') {
                isShowingAlert = true;
                alert("Warning: " + data.message);
                isShowingAlert = false;
            }
        })
        .catch(err => console.error("Error logging security event:", err));
    }

    // 2. Fullscreen Enforcement
    if (fullscreenRequired) {
        // Request fullscreen on load (needs user interaction, so we request on first page click if rejected initially)
        function enterFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.log("Fullscreen request rejected. Waiting for user click.");
                });
            }
        }
        
        enterFullscreen();
        document.addEventListener("click", enterFullscreen);

        // Detect exiting fullscreen
        document.addEventListener("fullscreenchange", function () {
            if (!document.fullscreenElement) {
                reportViolation('FULLSCREEN_EXIT', 'Student exited fullscreen mode.');
            }
        });
    }

    // 3. Tab Visibility / Hidden Detection
    if (tabSwitchDetection) {
        document.addEventListener("visibilitychange", function () {
            if (document.visibilityState === 'hidden') {
                reportViolation('TAB_SWITCH', 'Student switched away from the exam page (visibility hidden).');
            } else {
                reportViolation('PAGE_HIDDEN', 'Student returned to the exam page (visibility visible).');
            }
        });
    }

    // 4. Window Focus/Blur Detection
    if (windowBlurDetection) {
        window.addEventListener("blur", function () {
            reportViolation('WINDOW_BLUR', 'Exam window lost focus.');
        });
        window.addEventListener("focus", function () {
            reportViolation('WINDOW_FOCUS_RETURN', 'Exam window regained focus.');
        });
    }

    // 5. Copy & Right-Click Protection
    if (copyProtection) {
        document.addEventListener("copy", function (e) {
            e.preventDefault();
            reportViolation('COPY_ATTEMPT', 'Copying content was blocked.');
        });
        document.addEventListener("cut", function (e) {
            e.preventDefault();
            reportViolation('CUT_ATTEMPT', 'Cutting content was blocked.');
        });
    }

    if (rightClickProtection) {
        document.addEventListener("contextmenu", function (e) {
            e.preventDefault();
            reportViolation('RIGHT_CLICK', 'Right click was blocked.');
        });
    }

    // 6. Paste Protection
    if (pasteProtection) {
        document.addEventListener("paste", function (e) {
            e.preventDefault();
            reportViolation('PASTE_ATTEMPT', 'Pasting content was blocked.');
        });
    }

    // 7. Print Protection
    if (printProtection) {
        window.addEventListener("beforeprint", function () {
            reportViolation('PRINT_ATTEMPT', 'Print screen dialog was requested.');
        });
    }

    // 8. Keyboard Shortcut Block & Monitor
    if (shortcutDetection) {
        document.addEventListener("keydown", function (e) {
            let keysPressed = [];
            if (e.ctrlKey) keysPressed.push("Ctrl");
            if (e.shiftKey) keysPressed.push("Shift");
            if (e.altKey) keysPressed.push("Alt");
            keysPressed.push(e.key);

            const shortcut = keysPressed.join("+");

            // Define blocked shortcuts
            const blockedShortcuts = [
                "Ctrl+c", "Ctrl+C", "Ctrl+x", "Ctrl+X", "Ctrl+v", "Ctrl+V", "Ctrl+a", "Ctrl+A",
                "Ctrl+p", "Ctrl+P", "Ctrl+s", "Ctrl+S", "Ctrl+u", "Ctrl+U",
                "F12", "Ctrl+Shift+I", "Ctrl+Shift+i", "Ctrl+Shift+J", "Ctrl+Shift+j", 
                "Ctrl+Shift+C", "Ctrl+Shift+c", "Alt+Tab"
            ];

            if (blockedShortcuts.includes(shortcut) || e.key === "F12") {
                e.preventDefault();
                reportViolation('KEYBOARD_SHORTCUT', `Prohibited shortcut: ${shortcut} was blocked.`, `Shortcut: ${shortcut}`);
            }
        });
    }

    // 9. Developer Tools Heuristics Detection
    if (devtoolsDetection) {
        let devtoolsOpen = false;
        const threshold = 160;

        setInterval(function () {
            // Only check window size difference when in fullscreen to avoid false positives
            // from browser toolbars, title bars, bookmark bars, and OS taskbars.
            if (document.fullscreenElement) {
                const widthThreshold = window.outerWidth - window.innerWidth > threshold;
                const heightThreshold = window.outerHeight - window.innerHeight > threshold;

                if ((widthThreshold || heightThreshold) && !devtoolsOpen) {
                    devtoolsOpen = true;
                    reportViolation('DEVTOOLS_SUSPECTED', 'Suspected open developer tools (window size check).');
                } else if (!(widthThreshold || heightThreshold)) {
                    devtoolsOpen = false;
                }
            }
        }, 1000);
    }
});
