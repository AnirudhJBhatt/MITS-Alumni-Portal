<?php
/**
 * Global Configuration Settings
 * MITS Alumni Portal
 */

// Prevent direct access if needed
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

// Application Information
define('APP_NAME', 'MITS Connect');
define('APP_SHORT_NAME', 'MITS Connect');
define('APP_TAGLINE', 'Connecting Generations of Excellence');
define('APP_VERSION', '1.0.0');

// Base URL configuration (auto-detect or customize)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

// Calculate root path relative to web root
$base_path = '/MITS-Alumni-Portal';
define('BASE_URL', $protocol . $host . $base_path);

// Paths
define('ROOT_PATH', realpath(__DIR__ . '/..'));
define('UPLOAD_DIR', ROOT_PATH . '/uploads');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// Google Identity Services (GIS) Credentials
// Replace with your Google Cloud Console OAuth 2.0 Web Client ID
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com');

// Default Admin Credentials / Super Admin Email (auto-promoted on first login)
define('SUPER_ADMIN_EMAIL', 'admin@mits.ac.in');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Security Headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Error Reporting (Set to false for production)
define('APP_DEBUG', true);
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
