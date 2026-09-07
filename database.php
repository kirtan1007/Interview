<?php
$host = getenv('DB_HOST') ?: "127.0.0.1";
$port = getenv('DB_PORT') ?: "3306";
$db   = getenv('DB_NAME') ?: "interview";
$user = getenv('DB_USER') ?: "root";
$pass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : "");
$charset = "utf8mb4";

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Fallback: If default root connection fails (common on cloud containers), try container's dedicated user
    try {
        $dsn_fallback = "mysql:host=127.0.0.1;port=3306;dbname=$db;charset=$charset";
        $pdo = new PDO($dsn_fallback, "interview_user", "Interview@123", $options);
    } catch (\PDOException $e2) {
        error_log("Database connection error: " . $e->getMessage() . " | " . $e2->getMessage());
        die("<div style='padding: 20px; font-family: sans-serif; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; border-radius: 6px; margin: 20px auto; max-width: 650px;'>
               <h3>Database Connection Failed</h3>
               <p>Could not connect to the database <strong>" . htmlspecialchars($db) . "</strong>.</p>
               <p><strong>Connection Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
               <p><strong>Container Error:</strong> " . htmlspecialchars($e2->getMessage()) . "</p>
             </div>");
    }
}
?>
