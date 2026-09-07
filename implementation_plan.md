# Implementation Plan - PHP + MySQL Interview Q&A Application

We will build a responsive Online Interview Question & Answer Web Application with an Admin Panel and a Student Panel. The database name is `interview`, and the layout will support screens from mobile to desktop.

## Proposed Database Schema (Database: `interview`)

1. **`users` Table**:
   - `id` INT AUTO_INCREMENT PRIMARY KEY
   - `full_name` VARCHAR(255)
   - `username` VARCHAR(100) UNIQUE
   - `password` VARCHAR(255) (Hashed using `password_hash()`)
   - `role` ENUM('admin', 'student') NOT NULL
   - `email` VARCHAR(150)
   - `status` ENUM('active', 'inactive') DEFAULT 'active'
   - `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP

2. **`questions` Table**:
   - `id` INT AUTO_INCREMENT PRIMARY KEY
   - `question_text` TEXT NOT NULL
   - `option_a` TEXT NOT NULL
   - `option_b` TEXT NOT NULL
   - `option_c` TEXT NOT NULL
   - `option_d` TEXT NOT NULL
   - `correct_answer` CHAR(1) NOT NULL (Values: 'A', 'B', 'C', 'D')
   - `explanation` TEXT NULL
   - `marks` INT DEFAULT 1
   - `status` ENUM('active', 'inactive') DEFAULT 'active'
   - `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP

3. **`attempts` Table**:
   - `id` INT AUTO_INCREMENT PRIMARY KEY
   - `student_id` INT UNIQUE NOT NULL (Enforces a single attempt row per student)
   - `status` ENUM('in_progress', 'completed') DEFAULT 'in_progress'
   - `total_questions` INT DEFAULT 0
   - `attempted_questions` INT DEFAULT 0
   - `correct_answers` INT DEFAULT 0
   - `wrong_answers` INT DEFAULT 0
   - `unanswered_questions` INT DEFAULT 0
   - `total_marks` INT DEFAULT 0
   - `obtained_marks` INT DEFAULT 0
   - `percentage` DECIMAL(5,2) DEFAULT 0.00
   - `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   - `submitted_at` TIMESTAMP NULL DEFAULT NULL
   - `question_order` TEXT NOT NULL (Comma-separated list of question IDs to lock randomized or sequential order)
   - FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE

4. **`student_answers` Table**:
   - `id` INT AUTO_INCREMENT PRIMARY KEY
   - `attempt_id` INT NOT NULL
   - `student_id` INT NOT NULL
   - `question_id` INT NOT NULL
   - `selected_answer` CHAR(1) NULL (A/B/C/D or NULL)
   - `correct_answer` CHAR(1) NOT NULL (Stored at time of attempt to preserve historical correct answer)
   - `is_correct` TINYINT(1) DEFAULT 0
   - `marks_obtained` INT DEFAULT 0
   - `answered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
   - FOREIGN KEY (`attempt_id`) REFERENCES `attempts`(`id`) ON DELETE CASCADE
   - FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
   - FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
   - UNIQUE KEY `unique_attempt_question` (`attempt_id`, `question_id`)

5. **`settings` Table**:
   - `id` INT AUTO_INCREMENT PRIMARY KEY
   - `setting_key` VARCHAR(50) UNIQUE NOT NULL
   - `setting_value` TEXT NULL
   - `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

## Key Architecture & Features

1. **Authentication**:
   - Role-based redirection: Admin -> `admin/dashboard.php`, Student -> `student/dashboard.php`.
   - Access control via includes (`includes/admin_auth.php` and `includes/student_auth.php`).
   - Secure passwords via `password_hash()` and validation with `password_verify()`.

2. **One Attempt Constraint (Server-Side)**:
   - Verified at multiple points:
     - `attempts` table has a `UNIQUE` constraint on `student_id`.
     - When starting, check if a row already exists in `attempts`. If it has status `completed`, show the Completed screen immediately.
     - When submitting, check if status is already `completed` to block double submission.
     - All routes inside `student/` verify the attempt status.

3. **Randomized Questions with Page Refresh Safety**:
   - If randomize is `ON`, the order of questions is shuffled once when the test starts.
   - The order (e.g. `4,2,7,1,3`) is saved in `attempts.question_order`.
   - On page refreshes, the app reads the order from the database, ensuring the sequence is constant for the student.

4. **Timer Security**:
   - The test page polls and updates the remaining time using JavaScript.
   - For security, PHP computes the actual difference between `started_at` and current time. If it exceeds the allowed duration + a small grace window, the test is auto-submitted/invalidated on submission.

5. **Mobile Responsiveness**:
   - The style sheet (`assets/css/style.css`) will build on top of Bootstrap 5 CSS with a custom, high-end theme (dark navbars, card-based layouts, and responsive off-canvas menus for admin/student views).
   - Tables will be wrapped in scrollable containers or display as responsive flexcards on screens `< 768px`.

---

## Proposed Changes

### Configuration and Setup Files
#### [NEW] [database.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/database.php)
Establishes the PDO database connection to MySQL database `interview`.

#### [NEW] [interview_database.md](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/interview_database.md)
Detailed setup guide, DB architecture description, and the copy-paste-ready SQL queries for tables, indexes, keys, admin/student seeds, and settings.

### Shared Headers, Footers, and Libraries
#### [NEW] [includes/auth.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/includes/auth.php)
Global auth functions, session checks, and CSRF token generation.
#### [NEW] [includes/admin_auth.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/includes/admin_auth.php)
Asserts that the logged-in user is an admin.
#### [NEW] [includes/student_auth.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/includes/student_auth.php)
Asserts that the logged-in user is an active student.
#### [NEW] [includes/header.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/includes/header.php)
Common HTML header with CSS links, favicon details, and responsive navigation header.
#### [NEW] [includes/footer.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/includes/footer.php)
Common HTML footer with scripts and JS helper imports.
#### [NEW] [includes/functions.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/includes/functions.php)
Helper functions (XSS escaping, alert messages, percentage formatting).

### Core Login & Gateway Pages
#### [NEW] [index.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/index.php)
Checks session status and routes logged-in users directly to their respective dashboards; otherwise redirects to `login.php`.
#### [NEW] [login.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/login.php)
Renders a secure, beautifully styled login container. Handles validation and initial redirection.
#### [NEW] [logout.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/logout.php)
Destroys session values securely and redirects to `login.php`.

### Admin Dashboard and Management
#### [NEW] [admin/dashboard.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/admin/dashboard.php)
Displays summary cards with core statistics (Total Students, Total Questions, Average Score, Pass/Fail counts).
#### [NEW] [admin/students.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/admin/students.php)
List of all students with active status indicators. Search and filtering capabilities.
#### [NEW] [admin/add_student.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/admin/add_student.php)
Form to create new student accounts manually. Validates username uniqueness.
#### [NEW] [admin/edit_student.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/admin/edit_student.php)
Form to edit student name, email, status, and reset passwords safely.
#### [NEW] [admin/questions.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/admin/questions.php)
List of questions with filters and CRUD operations.
#### [NEW] [admin/add_question.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/admin/add_question.php)
Add questions with multiple options, marks, correct answer identifier, and explanation.
#### [NEW] [admin/edit_question.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/admin/edit_question.php)
Edit existing questions and update correctness metadata.
#### [NEW] [admin/results.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/admin/results.php)
Overview of student test results with search, filter, and quick view toggles.
#### [NEW] [admin/view_result.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/admin/view_result.php)
Detailed answer-sheet inspector displaying what the student selected versus the correct response.
#### [NEW] [admin/settings.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/admin/settings.php)
Settings controller allowing configuration of passing percentages, randomization, review visibility, test titles, and time durations.

### Student Panel Pages
#### [NEW] [student/dashboard.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/student/dashboard.php)
Landing page for students, inspecting test status (`not_started`, `in_progress`, `completed`) and routing to instructions or results.
#### [NEW] [student/instructions.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/student/instructions.php)
Lists rules, time limits, scoring details, and shows the "START TEST" action.
#### [NEW] [student/start_test.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/student/start_test.php)
Initializes an attempt row in the database, locks question sequence, and sets start time.
#### [NEW] [student/test.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/student/test.php)
Interactive test interface. Employs question navigator grids, interactive choices, timer indicators, and Previous/Next/Submit actions.
#### [NEW] [student/save_answer.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/student/save_answer.php)
Background endpoint (accessed via AJAX or direct POST backup) to persist selected answers continuously.
#### [NEW] [student/submit_test.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/student/submit_test.php)
Calculates scores, correct/wrong counts on the server, updates attempt status to `completed`, and locks responses.
#### [NEW] [student/result.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/student/result.php)
Displays results card showing raw scores, percentage, pass/fail status, and a button to view the key (if allowed).
#### [NEW] [student/answer_key.php](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/student/answer_key.php)
Lists questions with side-by-side selected vs correct answers and question explanations.

### Custom Styling & Logic Files
#### [NEW] [assets/css/style.css](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/assets/css/style.css)
Modern UI styling. Glassmorphic log-in fields, smooth animations, dark sidebar layouts, responsive table view rules.
#### [NEW] [assets/js/script.js](file:///c:/Users/kpanc/OneDrive/Desktop/Interview/assets/js/script.js)
Frontend interactions: modal confirmation, AJAX auto-save handler, countdown timer, responsive sidebars.

---

## Verification Plan

### Manual Verification
- **One-Attempt Test**: Verify that completing the test blocks access to the test page. Verify that refreshing, logging out/in, or accessing `/student/test.php` directly correctly redirects back to `/student/result.php`.
- **Auto-Submission Test**: Simulate test timing constraints and ensure database persistence.
- **Mobile Responsive Checklist**: Inspect on multiple viewport simulations (mobile, tablet, desktop) to ensure full responsive behavior.
