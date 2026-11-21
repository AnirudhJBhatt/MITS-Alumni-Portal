<?php
/**
 * Authentication Gatekeeper (Middleware)
 * MITS Alumni Portal - MITS Connect
 */

require_once __DIR__ . '/functions.php';

if (!is_logged_in()) {
    // Remember requested URL for post-login redirect
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    set_flash('warning', 'Please sign in to access this page.');
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Ensure database connection is active for dynamic live status refresh
if (!isset($conn) || !$conn) {
    require_once __DIR__ . '/../config/db.php';
}

// Always refresh live status from database so that approval is instant without relogging
$current_uid = (int)($_SESSION['user']['id'] ?? 0);
if ($current_uid > 0 && isset($conn) && $conn) {
    $live_user = db_fetch_one($conn, "SELECT role, status, full_name, email FROM users WHERE id = ? LIMIT 1", "i", [$current_uid]);
    if ($live_user) {
        $_SESSION['user']['role'] = $live_user['role'];
        $_SESSION['user']['status'] = $live_user['status'];
        $_SESSION['user']['full_name'] = $live_user['full_name'];
        $_SESSION['user']['email'] = $live_user['email'];
    }
}

// 1. Check if user is suspended
if (($_SESSION['user']['status'] ?? '') === 'suspended') {
    set_flash('danger', 'Your account has been suspended. Please contact the college administration.');
    header('Location: ' . BASE_URL . '/auth/logout.php');
    exit;
}

$current_script = basename($_SERVER['PHP_SELF']);

// 2. Check if user has completed profile onboarding (only for non-admin alumni)
if (!is_admin() && empty($_SESSION['user']['profile_completed']) && !in_array($current_script, ['complete-profile.php', 'logout.php'])) {
    set_flash('info', 'Please complete your alumni profile details to continue.');
    header('Location: ' . BASE_URL . '/auth/complete-profile.php');
    exit;
}

// 3. Strict Verification Gate: Non-admin alumni MUST be verified to access portal pages
if (!is_admin() && ($_SESSION['user']['status'] ?? '') !== 'verified') {
    $allowed_unverified_scripts = ['pending-verification.php', 'complete-profile.php', 'logout.php'];
    if (!in_array($current_script, $allowed_unverified_scripts)) {
        header('Location: ' . BASE_URL . '/auth/pending-verification.php');
        exit;
    }
}


