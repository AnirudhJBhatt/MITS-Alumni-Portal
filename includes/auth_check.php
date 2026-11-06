<?php
/**
 * Authentication Gatekeeper (Middleware)
 * MITS Alumni Portal
 */

require_once __DIR__ . '/functions.php';

if (!is_logged_in()) {
    // Remember requested URL for post-login redirect
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    set_flash('warning', 'Please sign in to access this page.');
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Check if user is suspended
if (($_SESSION['user']['status'] ?? '') === 'suspended') {
    set_flash('danger', 'Your account has been suspended. Please contact the college administration.');
    header('Location: ' . BASE_URL . '/auth/logout.php');
    exit;
}

// Check if user has completed profile onboarding (only for non-admin alumni)
$current_script = basename($_SERVER['PHP_SELF']);
if (!is_admin() && empty($_SESSION['user']['profile_completed']) && !in_array($current_script, ['complete-profile.php', 'logout.php'])) {
    set_flash('info', 'Please complete your alumni profile details to access this section.');
    header('Location: ' . BASE_URL . '/auth/complete-profile.php');
    exit;
}

