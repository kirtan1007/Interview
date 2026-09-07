-- --------------------------------------------------------
-- Interview System Database Setup Script
-- Database Name: interview
-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `interview` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `interview`;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(255) NOT NULL,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'student') NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `questions`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `question_text` TEXT NOT NULL,
  `option_a` TEXT NOT NULL,
  `option_b` TEXT NOT NULL,
  `option_c` TEXT NOT NULL,
  `option_d` TEXT NOT NULL,
  `correct_answer` CHAR(1) NOT NULL,
  `explanation` TEXT NULL,
  `marks` INT DEFAULT 1,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `attempts`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL UNIQUE,
  `status` ENUM('in_progress', 'completed', 'failed') DEFAULT 'in_progress',
  `total_questions` INT DEFAULT 0,
  `attempted_questions` INT DEFAULT 0,
  `correct_answers` INT DEFAULT 0,
  `wrong_answers` INT DEFAULT 0,
  `unanswered_questions` INT DEFAULT 0,
  `total_marks` INT DEFAULT 0,
  `obtained_marks` INT DEFAULT 0,
  `percentage` DECIMAL(5,2) DEFAULT 0.00,
  `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `submitted_at` TIMESTAMP NULL DEFAULT NULL,
  `question_order` TEXT NOT NULL,
  `security_status` ENUM('SAFE', 'WARNING', 'VIOLATION', 'FAILED') DEFAULT 'SAFE',
  `violation_count` INT DEFAULT 0,
  `failed_reason` VARCHAR(255) DEFAULT NULL,
  `failed_at` TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `student_answers`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `student_answers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `attempt_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `question_id` INT NOT NULL,
  `selected_answer` CHAR(1) DEFAULT NULL,
  `correct_answer` CHAR(1) NOT NULL,
  `is_correct` TINYINT(1) DEFAULT 0,
  `marks_obtained` INT DEFAULT 0,
  `answered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`attempt_id`) REFERENCES `attempts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_attempt_question` (`attempt_id`, `question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `security_violations`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `security_violations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `attempt_id` INT NOT NULL,
  `violation_type` VARCHAR(50) NOT NULL,
  `violation_reason` VARCHAR(255) NOT NULL,
  `details` TEXT NULL,
  `page_url` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT NOT NULL,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`attempt_id`) REFERENCES `attempts`(`id`) ON DELETE CASCADE,
  INDEX `idx_violation_student` (`student_id`),
  INDEX `idx_violation_attempt` (`attempt_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `settings`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Seed Data for Default Users
-- --------------------------------------------------------

-- Insert Admin (Password: admin123)
INSERT INTO `users` (`full_name`, `username`, `password`, `role`, `email`, `status`) VALUES
('System Administrator', 'admin', '$2y$10$ul/gVrPhH7RbdXmB4jNwGO8T64M7tyWtWlZdISFFQNY86rqxGl6Be', 'admin', 'admin@example.com', 'active');

-- Insert Sample Student (Password: student123)
INSERT INTO `users` (`full_name`, `username`, `password`, `role`, `email`, `status`) VALUES
('John Doe', 'student01', '$2y$10$s81Qe6cFCWnDZj4qmK2Z9O9Q6IeysHqYBfZcMsaaPtMBQSU4ulms6', 'student', 'student01@example.com', 'active');

-- --------------------------------------------------------
-- Seed Data for Sample Questions
-- --------------------------------------------------------

INSERT INTO `questions` (`question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `explanation`, `marks`, `status`) VALUES
('What does PHP stand for?', 'Personal Home Page', 'Private Home Page', 'PHP: Hypertext Preprocessor', 'Personal Hyper Processor', 'C', 'PHP is a recursive acronym standing for PHP: Hypertext Preprocessor. Originally it stood for Personal Home Page.', 1, 'active'),
('Which of the following is NOT a valid PHP variable name?', '$123var', '$var123', '$__var', '$var_name', 'A', 'PHP variable names cannot start with a number. They must start with a letter or an underscore.', 1, 'active'),
('How do you start a session in PHP?', 'session_begin()', 'session_start()', 'start_session()', 'session_init()', 'B', 'The session_start() function is used to initialize or resume an existing session in PHP.', 1, 'active'),
('Which superglobal array is used to access form data submitted with the HTTP POST method?', '$_GET', '$_POST', '$_REQUEST', '$_SERVER', 'B', '$_POST is the superglobal array used to access variables sent via the POST method.', 1, 'active'),
('What is the default port for MySQL server?', '3306', '8080', '21', '443', 'A', 'Port 3306 is the standard default port for MySQL database engines.', 1, 'active');

-- --------------------------------------------------------
-- Seed Data for Default Settings
-- --------------------------------------------------------

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('passing_percentage', '40'),
('test_duration', '30'),
('test_title', 'PHP & MySQL Technical Assessment'),
('show_answer_key', '1'),
('randomize_questions', '1'),
('show_result_immediately', '1'),
('site_name', 'TechEval Pro'),
('instructions', 'Please read the following instructions carefully before starting the test: \n\n1. The test consists of multiple-choice questions.\n2. You are allowed only ONE attempt. Once submitted, you cannot re-attempt.\n3. Keep track of the timer. If the time expires, your current progress will be automatically submitted.\n4. Do not refresh or navigate away from the test screen to prevent connection loss.'),
('exam_security_mode', '1'),
('fullscreen_required', '1'),
('tab_switch_detection', '1'),
('window_blur_detection', '1'),
('copy_protection', '1'),
('paste_protection', '1'),
('right_click_protection', '1'),
('print_protection', '1'),
('keyboard_shortcut_detection', '1'),
('devtools_detection', '1'),
('auto_fail_on_critical', '1'),
('max_violations', '3');
