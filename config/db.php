<?php
/**
 * Database Connection (Procedural MySQLi)
 * MITS Alumni Portal
 */

require_once __DIR__ . '/config.php';

// Database Credentials from Environment (.env) with fallbacks
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'mits_alumni_portal'));
define('DB_PORT', (int)env('DB_PORT', 3306));

// Establish connection
$conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Check connection
if (!$conn) {
    if (APP_DEBUG) {
        die('<div style="font-family: Arial, sans-serif; padding: 20px; background: #fee2e2; color: #991b1b; border: 1px solid #f87171; border-radius: 8px; margin: 30px auto; max-width: 600px;">' .
            '<h3>Database Connection Error</h3>' .
            '<p>' . htmlspecialchars(mysqli_connect_error()) . '</p>' .
            '<p><small>Ensure MySQL is running in XAMPP or verify database credentials in <code>.env</code>.</small></p>' .
            '</div>');
    } else {
        error_log('Database Connection Error: ' . mysqli_connect_error());
        die('A database error occurred. Please try again later.');
    }
}

// Set UTF-8 encoding
mysqli_set_charset($conn, 'utf8mb4');
