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

// 6. Super Administrator Email
define('SUPER_ADMIN_EMAIL', env('SUPER_ADMIN_EMAIL', 'admin@mits.ac.in'));

// 7. Timezone
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
