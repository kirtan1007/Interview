<?php
if (session_status() === PHP_SESSION_NONE) {
    // Enable secure session cookie settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    
    // Use secure session cookies if HTTPS is enabled
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['created_time'])) {
    $_SESSION['created_time'] = time();
} elseif (time() - $_SESSION['created_time'] > 1800) { // every 30 minutes
    session_regenerate_id(true);
    $_SESSION['created_time'] = time();
}

/**
 * Generate CSRF Token
 */
function get_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verify CSRF Token from POST/GET
 */
function check_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            http_response_code(403);
            die("Error: Invalid or missing CSRF token.");
        }
    }
}
?>
