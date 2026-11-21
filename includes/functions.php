<?php
/**
 * Core Helper Functions (Procedural)
 * MITS Alumni Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';

// ==============================================================
// 1. Output Escaping & Input Sanitization
// ==============================================================

/**
 * HTML escape helper for XSS protection
 */
function e($string) {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize basic input string
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    $data = trim((string)$data);
    $data = stripslashes($data);
    return $data;
}

// ==============================================================
// 2. CSRF Token Protection
// ==============================================================

/**
 * Generate or retrieve the current session's CSRF token
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output hidden CSRF token input field
 */
function csrf_field() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Verify CSRF token from POST or request header
 */
function verify_csrf_token($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_CSRF_TOKEN'] ?? '';
        if (empty($token)) {
            $raw = file_get_contents('php://input');
            if (!empty($raw)) {
                $json = json_decode($raw, true);
                $token = $json['csrf_token'] ?? '';
            }
        }
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// ==============================================================
// 3. Flash Messages / Alerts
// ==============================================================

/**
 * Set flash message for the next page load
 * @param string $type success | danger | warning | info
 * @param string $message
 */
function set_flash($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Render and clear all pending flash messages
 */
function display_flash() {
    if (empty($_SESSION['flash_messages'])) {
        return '';
    }

    $output = '<div class="flash-messages-container mb-4">';
    foreach ($_SESSION['flash_messages'] as $flash) {
        $type = e($flash['type']);
        $msg = e($flash['message']);
        
        $icon = 'bi-info-circle-fill';
        if ($type === 'success') $icon = 'bi-check-circle-fill';
        if ($type === 'danger')  $icon = 'bi-exclamation-triangle-fill';
        if ($type === 'warning') $icon = 'bi-exclamation-circle-fill';

        $output .= '
        <div class="alert alert-' . $type . ' alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
            <i class="bi ' . $icon . ' me-2 fs-5"></i>
            <div>' . $msg . '</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
    $output .= '</div>';

    // Clear messages
    unset($_SESSION['flash_messages']);
    return $output;
}

// ==============================================================
// 4. Authentication & User Status Helpers
// ==============================================================

/**
 * Check if a user is currently logged in
 */
function is_logged_in() {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user']);
}

/**
 * Get current logged in user ID
 */
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current logged in user data array
 */
function current_user() {
    return $_SESSION['user'] ?? null;
}

/**
 * Check if the logged in user is an administrator
 */
function is_admin() {
    return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin');
}

/**
 * Check if the user is verified by college admin
 */
function is_verified() {
    return is_logged_in() && (($_SESSION['user']['status'] ?? '') === 'verified');
}

/**
 * Check if the user is pending verification
 */
function is_pending() {
    return is_logged_in() && (($_SESSION['user']['status'] ?? '') === 'pending');
}

require_once __DIR__ . '/mailer.php';

// ==============================================================
// 5. Database Prepared Statement Helpers (Procedural)
// ==============================================================

/**
 * Execute a prepared query and return the statement
 */
function db_execute($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        if (APP_DEBUG) {
            die('SQL Prepare Error: ' . mysqli_error($conn) . ' in query: ' . $sql);
        }
        return false;
    }

    if (!empty($types) && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        if (APP_DEBUG) {
            die('SQL Execute Error: ' . mysqli_stmt_error($stmt));
        }
        return false;
    }

    return $stmt;
}

/**
 * Fetch a single associative row using a prepared statement
 */
function db_fetch_one($conn, $sql, $types = '', $params = []) {
    $stmt = db_execute($conn, $sql, $types, $params);
    if (!$stmt) return null;

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row;
}

/**
 * Fetch all associative rows using a prepared statement
 */
function db_fetch_all($conn, $sql, $types = '', $params = []) {
    $stmt = db_execute($conn, $sql, $types, $params);
    if (!$stmt) return [];

    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

// ==============================================================
// 6. Formatting & UI Helpers
// ==============================================================

/**
 * Format relative time (e.g., '5 mins ago', '2 hours ago', 'Yesterday')
 */
function time_ago($datetime) {
    if (empty($datetime)) return '';
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . 'm ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . 'h ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . 'd ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}

/**
 * Standard date formatter
 */
function format_date($datetime, $format = 'M d, Y') {
    if (empty($datetime)) return 'N/A';
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    return date($format, $timestamp);
}

/**
 * Get profile avatar or fallback initials avatar
 */
function get_avatar_url($avatar_url, $name = 'User') {
    if (!empty($avatar_url)) {
        return $avatar_url;
    }
    // High-quality UI Avatar fallback
    return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=d8151c&color=ffffff&bold=true&size=128';
}

/**
 * Render Bootstrap badge for user status
 */
function get_status_badge($status) {
    switch ($status) {
        case 'verified':
            return '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1"><i class="bi bi-patch-check-fill me-1"></i>Verified</span>';
        case 'pending':
            return '<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1"><i class="bi bi-clock-history me-1"></i>Pending Verification</span>';
        case 'rejected':
            return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1"><i class="bi bi-x-circle-fill me-1"></i>Rejected</span>';
        case 'suspended':
            return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2 py-1"><i class="bi bi-slash-circle me-1"></i>Suspended</span>';
        default:
            return '<span class="badge bg-light text-dark rounded-pill px-2 py-1">' . e($status) . '</span>';
    }
}
