document.addEventListener("DOMContentLoaded", function () {
    // 1. Option selection boxes behavior
    const optionBoxes = document.querySelectorAll(".option-box");
    optionBoxes.forEach(box => {
        box.addEventListener("click", function () {
            const radio = this.querySelector("input[type='radio']");
            if (radio && !radio.disabled) {
                radio.checked = true;
                
                // Visual selected state toggle
                const siblings = this.closest('.options-container').querySelectorAll('.option-box');
                siblings.forEach(sib => {
                    sib.classList.remove("selected");
                });
                this.classList.add("selected");
                
                // Auto-save answer trigger
                const questionId = radio.dataset.questionId;
                const selectedAnswer = radio.value;
                if (questionId && selectedAnswer) {
                    saveAnswerAJAX(questionId, selectedAnswer);
                }
            }
        });
    });

    // 2. AJAX Answer Saver function
    function saveAnswerAJAX(questionId, selectedAnswer) {
        const saveUrl = 'save_answer.php';
        const csrfTokenInput = document.querySelector('input[name="csrf_token"]');
        const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';

        const formData = new FormData();
        formData.append('question_id', questionId);
        formData.append('selected_answer', selectedAnswer);
        formData.append('csrf_token', csrfToken);

        fetch(saveUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Update navigation badge styling
                const badge = document.getElementById(`nav-badge-${questionId}`);
                if (badge) {
                    badge.classList.remove('unanswered');
                    badge.classList.add('answered');
                }
            } else {
                console.error("Failed to save answer: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error saving answer via AJAX:", error);
        });
    }

    // 3. Test Timer countdown logic
    const timerElement = document.getElementById("test-timer");
    if (timerElement) {
        let remainingSeconds = parseInt(timerElement.dataset.timeRemaining, 10);
        
        const countdownInterval = setInterval(function () {
            if (remainingSeconds <= 0) {
                clearInterval(countdownInterval);
                timerElement.innerHTML = "00:00";
                alert("Time has expired! Submitting your test automatically.");
                
                const form = document.getElementById("test-form");
                if (form) {
                    // Create an auto submit input to prevent user confirmations
                    const autoSubmit = document.createElement("input");
                    autoSubmit.setAttribute("type", "hidden");
                    autoSubmit.setAttribute("name", "auto_submit");
                    autoSubmit.setAttribute("value", "1");
                    form.appendChild(autoSubmit);
                    if (window.isSubmittingExam !== undefined) {
                        window.isSubmittingExam = true;
                    }
                    form.submit();
                }
                return;
            }

            remainingSeconds--;
            timerElement.dataset.timeRemaining = remainingSeconds;

            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;
            const displayMinutes = minutes < 10 ? "0" + minutes : minutes;
            const displaySeconds = seconds < 10 ? "0" + seconds : seconds;
            
            timerElement.innerHTML = `${displayMinutes}:${displaySeconds}`;

            // Visual warning when time is low
            if (remainingSeconds <= 60) {
                timerElement.classList.remove("text-primary");
                timerElement.classList.add("text-danger", "fw-bold");
            }
        }, 1000);
    }

    // 4. Instant In-Page Question Navigation (No Page Reload, Keeps Fullscreen Active)
    const questionSlides = document.querySelectorAll('.question-slide');
    const totalQuestions = questionSlides.length;

    function navigateToQuestion(targetQ) {
        targetQ = parseInt(targetQ, 10);
        if (isNaN(targetQ) || targetQ < 1 || targetQ > totalQuestions) return;

        // Hide all slides, show target slide
        questionSlides.forEach(slide => {
            slide.style.display = 'none';
        });

        const activeSlide = document.getElementById('question-slide-' + targetQ);
        if (activeSlide) {
            activeSlide.style.display = 'block';
        }

        // Update header badge
        const badgeEl = document.getElementById('current-q-badge');
        if (badgeEl) {
            badgeEl.textContent = `Question ${targetQ} of ${totalQuestions}`;
        }

        // Update active class on navigator badges
        document.querySelectorAll('.nav-badge.switch-q-btn').forEach(btn => {
            const bTarget = parseInt(btn.dataset.targetQ, 10);
            if (bTarget === targetQ) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Update URL query string without reloading page
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', '?q=' + targetQ);
        }
    }

    // Event delegation for question switching buttons (Previous, Next, Question Navigator badges)
    document.addEventListener('click', function (e) {
        const switchBtn = e.target.closest('.switch-q-btn');
        if (switchBtn && switchBtn.dataset.targetQ) {
            e.preventDefault();
            navigateToQuestion(switchBtn.dataset.targetQ);
        }
    });

    // 5. Global Admin Shortcut: Press Ctrl + Shift + A or Alt + A to open Admin Panel
    document.addEventListener('keydown', function (e) {
        const isCtrlShiftA = e.ctrlKey && e.shiftKey && (e.key === 'A' || e.key === 'a');
        const isAltA = e.altKey && !e.ctrlKey && !e.shiftKey && (e.key === 'A' || e.key === 'a');

        if (isCtrlShiftA || isAltA) {
            e.preventDefault();
            // Get root path to Interview
            const pathParts = window.location.pathname.split('/');
            const interviewIndex = pathParts.indexOf('Interview');
            let adminUrl = '/Interview/admin/dashboard.php';
            if (interviewIndex !== -1) {
                adminUrl = pathParts.slice(0, interviewIndex + 1).join('/') + '/admin/dashboard.php';
            }
            window.location.href = adminUrl;
        }
    });
});


