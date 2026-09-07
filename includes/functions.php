<?php
require_once __DIR__ . '/../database.php';

/**
 * Escape output for XSS Protection
 */
function escape($html) {
    return htmlspecialchars($html ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Fetch setting value by key
 */
function get_setting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        error_log("Failed to fetch setting '$key': " . $e->getMessage());
        return $default;
    }
}

/**
 * Set or update a setting value
 */
function set_setting($key, $value) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = CURRENT_TIMESTAMP");
        return $stmt->execute([$key, $value, $value]);
    } catch (PDOException $e) {
        error_log("Failed to set setting '$key': " . $e->getMessage());
        return false;
    }
}

/**
 * Render Bootstrap alert boxes from sessions
 */
function display_alerts() {
    $html = '';
    if (isset($_SESSION['success_message'])) {
        $html .= '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>' . escape($_SESSION['success_message']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        $html .= '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>' . escape($_SESSION['error_message']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        unset($_SESSION['error_message']);
    }
    return $html;
}

/**
 * Format score percentage helper
 */
function format_percentage($value) {
    return number_format((float)$value, 1) . '%';
}
?>
