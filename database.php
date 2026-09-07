<?php
$host = "localhost";
$db = "interview";
$user = "root";
$pass = ""; // Configurable (XAMPP default is empty)
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Do not expose database credentials or details in production
     error_log("Database connection error: " . $e->getMessage());
     die("<div style='padding: 20px; font-family: sans-serif; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; border-radius: 6px; margin: 20px auto; max-width: 600px;'>
            <h3>Database Connection Failed</h3>
            <p>Could not connect to the database <strong>interview</strong>.</p>
            <p>Please ensure that:</p>
            <ul>
                <li>Your local server (like XAMPP, WampServer or Docker MySQL) is running.</li>
                <li>You have imported the database tables from <a href='interview_database.md'>interview_database.md</a>.</li>
                <li>The login credentials in <code>database.php</code> match your local MySQL configuration.</li>
            </ul>
          </div>");
}
?>
