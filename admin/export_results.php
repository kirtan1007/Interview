<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';

$type = $_GET['type'] ?? 'all'; // 'pass', 'fail', 'all'
$passing_percentage = (float)get_setting('passing_percentage', '40');

try {
    $query = "
        SELECT a.*, u.full_name, u.username, u.email
        FROM attempts a
        JOIN users u ON a.student_id = u.id
        WHERE u.role = 'student' AND a.status = 'completed'
    ";
    $params = [];

    if ($type === 'pass') {
        $query .= " AND a.percentage >= ?";
        $params[] = $passing_percentage;
        $filename = "Passed_Students_" . date('Y-m-d') . ".csv";
    } elseif ($type === 'fail') {
        $query .= " AND a.percentage < ?";
        $params[] = $passing_percentage;
        $filename = "Failed_Students_" . date('Y-m-d') . ".csv";
    } else {
        $filename = "All_Students_Results_" . date('Y-m-d') . ".csv";
    }

    $query .= " ORDER BY a.percentage DESC, a.submitted_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Disable caching
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-Description: File Transfer');
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Write UTF-8 BOM for Microsoft Excel compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // CSV Header row
    fputcsv($output, [
        'Sr No',
        'Student Name',
        'Username',
        'Email Address',
        'Score Obtained',
        'Total Marks',
        'Percentage',
        'Outcome',
        'Correct Answers',
        'Wrong Answers',
        'Total Questions',
        'Security Status',
        'Violation Count',
        'Started At',
        'Submitted At'
    ]);

    $sr = 1;
    foreach ($rows as $r) {
        $outcome = ($r['percentage'] >= $passing_percentage) ? 'PASS' : 'FAIL';
        fputcsv($output, [
            $sr++,
            $r['full_name'],
            $r['username'],
            $r['email'],
            $r['obtained_marks'],
            $r['total_marks'],
            number_format((float)$r['percentage'], 2) . '%',
            $outcome,
            $r['correct_answers'],
            $r['wrong_answers'],
            $r['total_questions'],
            $r['security_status'] ?? 'SAFE',
            $r['violation_count'] ?? 0,
            $r['started_at'] ? date('Y-m-d H:i:s', strtotime($r['started_at'])) : '',
            $r['submitted_at'] ? date('Y-m-d H:i:s', strtotime($r['submitted_at'])) : ''
        ]);
    }

    fclose($output);
    exit();

} catch (PDOException $e) {
    error_log("Failed exporting results: " . $e->getMessage());
    die("Database error occurred while exporting results.");
}
