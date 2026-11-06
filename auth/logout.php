<?php
/**
 * Sign-Out Handler
 * MITS Alumni Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

// Unset all session values
$_SESSION = [];

// Invalidate session cookie if present
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Start fresh temporary session just for the flash message
session_start();
set_flash('info', 'You have been successfully signed out.');

// Redirect to login page
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
