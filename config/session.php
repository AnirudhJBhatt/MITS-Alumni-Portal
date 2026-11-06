<?php
/**
 * Secure Session Management
 * MITS Alumni Portal
 */

require_once __DIR__ . '/config.php';

// Configure secure session cookie attributes if session hasn't started
if (session_status() === PHP_SESSION_NONE) {
    // Session lifetime (e.g., 7 days)
    $lifetime = 60 * 60 * 24 * 7;

    $is_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? null) == 443);

    // Apply strict session cookie configuration
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $is_secure,
        'httponly' => true,      // Prevent JavaScript access to session cookie
        'samesite' => 'Lax'      // CSRF protection for cross-site requests
    ]);

    // Force strict session mode
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_start();
}

/**
 * Regenerate session ID safely to prevent session fixation
 */
function secure_session_regenerate() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}
