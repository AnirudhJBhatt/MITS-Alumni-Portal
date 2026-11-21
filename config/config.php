<?php
/**
 * Global Configuration Settings
 * MITS Alumni Portal
 */

// Prevent direct access if needed
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

// 1. Load Environment Variables from .env
require_once __DIR__ . '/env.php';

// 2. Application Information
define('APP_NAME', env('APP_NAME', 'MITS Connect'));
define('APP_SHORT_NAME', env('APP_SHORT_NAME', 'MITS Connect'));
define('APP_TAGLINE', env('APP_TAGLINE', 'Connecting Generations of Excellence'));
define('APP_VERSION', env('APP_VERSION', '1.0.0'));
define('APP_ENV', env('APP_ENV', 'development'));

// 3. Base URL configuration (from .env or auto-detect domain, port, subfolder)
if (!defined('BASE_URL')) {
    $env_url = env('APP_URL');
    if (!empty($env_url)) {
        define('BASE_URL', rtrim($env_url, '/'));
    } else {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $doc_root = !empty($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
        $app_root = str_replace('\\', '/', realpath(__DIR__ . '/..'));

        $base_path = '';
        if (!empty($doc_root) && strpos($app_root, $doc_root) === 0) {
            $base_path = substr($app_root, strlen($doc_root));
        }

        $base_path = rtrim(str_replace('\\', '/', $base_path), '/');
        define('BASE_URL', $protocol . $host . $base_path);
    }
}

// 4. Paths & Uploads
define('ROOT_PATH', realpath(__DIR__ . '/..'));
define('UPLOAD_DIR', ROOT_PATH . '/uploads');
define('MAX_UPLOAD_SIZE', ((int)env('MAX_UPLOAD_SIZE_MB', 5)) * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// 5. Google Identity Services (GIS) Credentials
define('GOOGLE_CLIENT_ID', env('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com'));
define('GOOGLE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET', ''));

// 6. Super Administrator Email(s)
$raw_admins = (string)env('SUPER_ADMIN_EMAIL', 'osam@mgits.ac.in,admin@mits.ac.in');
$parsed_admins = array_values(array_filter(array_map('trim', explode(',', strtolower($raw_admins)))));
define('SUPER_ADMIN_EMAILS', !empty($parsed_admins) ? $parsed_admins : ['osam@mgits.ac.in', 'admin@mits.ac.in']);
define('SUPER_ADMIN_EMAIL', SUPER_ADMIN_EMAILS[0] ?? 'osam@mgits.ac.in');

// 7. Email Domain Restriction Policy
$raw_domains = (string)env('COLLEGE_EMAIL_DOMAINS', 'mgits.ac.in,mits.ac.in');
$parsed_domains = array_values(array_filter(array_map('trim', explode(',', strtolower($raw_domains)))));
define('COLLEGE_EMAIL_DOMAINS', !empty($parsed_domains) ? $parsed_domains : ['mgits.ac.in', 'mits.ac.in']);
define('BLOCK_COLLEGE_EMAIL_DOMAIN', (bool)env('BLOCK_COLLEGE_EMAIL_DOMAIN', true));
define('RESTRICT_TO_COLLEGE_DOMAIN', (bool)env('RESTRICT_TO_COLLEGE_DOMAIN', false));

// 8. SMTP & Email Notification Settings
define('MAIL_DRIVER', env('MAIL_DRIVER', 'smtp'));
define('MAIL_HOST', env('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', (int)env('MAIL_PORT', 587));
define('MAIL_ENCRYPTION', env('MAIL_ENCRYPTION', 'tls'));
define('MAIL_USERNAME', env('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', env('MAIL_PASSWORD', ''));
define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'noreply@mits.ac.in'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'MITS Connect'));

// 9. Timezone
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Kolkata'));

// 8. Security Headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// 9. Error Reporting
define('APP_DEBUG', (bool)env('APP_DEBUG', true));
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
