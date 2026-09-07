<?php
require_once __DIR__ . '/database.php';
try {
    // Reset attempts back to in_progress and reset violations
    $pdo->query("UPDATE attempts SET status = 'in_progress', security_status = 'GOOD', violation_count = 0, failed_reason = NULL, failed_at = NULL");
    $pdo->query("DELETE FROM security_violations");
    echo "<div style='font-family: Arial, sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h2 style='color: #2e7d32;'>Exam Status Reset Successfully!</h2>";
    echo "<p>All student attempt failures and security logs have been cleared.</p>";
    echo "<p><a href='student/test.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: #fff; text-decoration: none; border-radius: 4px;'>Return to Test</a></p>";
    echo "</div>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
