<?php
/**
 * Google Identity Services - Server-side Token Verification & Session Handler
 * MITS Alumni Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Set response header for JSON if AJAX
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
           (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

// Read request data
$raw_input = file_get_contents('php://input');
$json_data = json_decode($raw_input, true) ?: [];

$credential = $_POST['credential'] ?? $json_data['credential'] ?? null;
$demo_email = $_POST['demo_email'] ?? $json_data['demo_email'] ?? null;
$csrf = $_POST['csrf_token'] ?? $json_data['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

// Helper to respond with error
function auth_error($message, $is_ajax) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    } else {
        set_flash('danger', $message);
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

// 1. Verify CSRF
if (!verify_csrf_token($csrf)) {
    auth_error('Invalid security token. Please refresh the page and try again.', $is_ajax);
}

$google_user = null;

// 2. Handle Local Development Demo Sign-in (Only when APP_DEBUG is true)
if (APP_DEBUG && !empty($demo_email)) {
    $demo_email = strtolower(trim($demo_email));
    $user = db_fetch_one($conn, "SELECT * FROM users WHERE email = ? LIMIT 1", "s", [$demo_email]);

    if (!$user) {
        // Create demo account on the fly if not existing
        $name = ucwords(str_replace(['.', '_'], ' ', explode('@', $demo_email)[0]));
        $role = ($demo_email === strtolower(SUPER_ADMIN_EMAIL)) ? 'admin' : 'alumni';
        $status = 'verified';
        
        $stmt = db_execute($conn, 
            "INSERT INTO users (email, full_name, role, status, avatar_url) VALUES (?, ?, ?, ?, ?)",
            "sssss",
            [$demo_email, $name, $role, $status, get_avatar_url('', $name)]
        );
        $user_id = mysqli_insert_id($conn);
        $user = db_fetch_one($conn, "SELECT * FROM users WHERE id = ? LIMIT 1", "i", [$user_id]);
    }

    $google_user = [
        'email' => $user['email'],
        'name' => $user['full_name'],
        'sub' => $user['google_id'] ?? ('demo_' . $user['id']),
        'picture' => $user['avatar_url']
    ];
}

// 3. Handle Real Google GIS ID Token Verification
if (!$google_user && !empty($credential)) {
    // Verify token using Google's public tokeninfo endpoint
    $verify_url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $verify_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curl_error || $http_code !== 200 || !$response) {
        auth_error('Google token verification failed. Please try again.', $is_ajax);
    }

    $payload = json_decode($response, true);

    if (empty($payload['email']) || empty($payload['sub'])) {
        auth_error('Invalid Google account payload received.', $is_ajax);
    }

    // Verify Audience (Audience must match GOOGLE_CLIENT_ID unless in default dev placeholder)
    if (defined('GOOGLE_CLIENT_ID') && 
        GOOGLE_CLIENT_ID !== 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com' && 
        $payload['aud'] !== GOOGLE_CLIENT_ID) {
        auth_error('Google Client ID mismatch. Token rejected.', $is_ajax);
    }

    // Verify Issuer
    $valid_issuers = ['accounts.google.com', 'https://accounts.google.com'];
    if (!in_array($payload['iss'] ?? '', $valid_issuers)) {
        auth_error('Untrusted Google token issuer.', $is_ajax);
    }

    $google_user = [
        'email' => strtolower(trim($payload['email'])),
        'name' => trim($payload['name'] ?? 'MITS Alumnus'),
        'sub' => $payload['sub'],
        'picture' => $payload['picture'] ?? ''
    ];
}

if (!$google_user) {
    auth_error('No valid authentication credentials provided.', $is_ajax);
}

// 4. Query or Create User in MySQL Database
$email = strtolower(trim($google_user['email']));
$name = $google_user['name'];
$google_id = $google_user['sub'];
$avatar = $google_user['picture'];

// 4.1 Validate Email Domain Policy (Super Admin is always exempted)
$is_super_admin = ($email === strtolower(trim(SUPER_ADMIN_EMAIL)));
if (!$is_super_admin) {
    $email_domain = substr(strrchr($email, "@"), 1);
    $college_domains = defined('COLLEGE_EMAIL_DOMAINS') ? COLLEGE_EMAIL_DOMAINS : ['mgits.ac.in', 'mits.ac.in'];
    $is_college_domain = in_array($email_domain, $college_domains, true);

    // Policy 1: Restrict ONLY to college email accounts
    if (defined('RESTRICT_TO_COLLEGE_DOMAIN') && RESTRICT_TO_COLLEGE_DOMAIN) {
        if (!$is_college_domain) {
            $allowed_str = '@' . implode(', @', $college_domains);
            auth_error("Access restricted: Please sign in with your official college email account ({$allowed_str}).", $is_ajax);
        }
    }
    // Policy 2: BLOCK college email accounts (Force permanent personal emails)
    elseif (defined('BLOCK_COLLEGE_EMAIL_DOMAIN') && BLOCK_COLLEGE_EMAIL_DOMAIN) {
        if ($is_college_domain) {
            auth_error("Please sign in using your permanent personal Google account (e.g., Gmail). College student email IDs (@{$email_domain}) are deactivated after graduation.", $is_ajax);
        }
    }
}

$existing_user = db_fetch_one($conn, "SELECT * FROM users WHERE email = ? OR google_id = ? LIMIT 1", "ss", [$email, $google_id]);

if ($existing_user) {
    // Existing user: update avatar & google_id if needed
    $user_id = $existing_user['id'];
    
    // Auto-promote super admin if designated in config
    $role = $existing_user['role'];
    $status = $existing_user['status'];
    if (strtolower($email) === strtolower(SUPER_ADMIN_EMAIL) && $role !== 'admin') {
        $role = 'admin';
        $status = 'verified';
    }

    db_execute($conn,
        "UPDATE users SET google_id = ?, avatar_url = COALESCE(NULLIF(avatar_url, ''), ?), role = ?, status = ? WHERE id = ?",
        "ssssi",
        [$google_id, $avatar, $role, $status, $user_id]
    );

    $user = db_fetch_one($conn, "SELECT * FROM users WHERE id = ? LIMIT 1", "i", [$user_id]);
} else {
    // New user registration
    $role = (strtolower($email) === strtolower(SUPER_ADMIN_EMAIL)) ? 'admin' : 'alumni';
    $status = ($role === 'admin') ? 'verified' : 'pending';

    $stmt = db_execute($conn,
        "INSERT INTO users (google_id, email, full_name, avatar_url, role, status) VALUES (?, ?, ?, ?, ?, ?)",
        "ssssss",
        [$google_id, $email, $name, $avatar, $role, $status]
    );

    $user_id = mysqli_insert_id($conn);
    $user = db_fetch_one($conn, "SELECT * FROM users WHERE id = ? LIMIT 1", "i", [$user_id]);
}

// 5. Establish Session
secure_session_regenerate();
$_SESSION['user_id'] = $user['id'];
$_SESSION['user'] = $user;

// 6. Check if Profile is Completed
$profile = db_fetch_one($conn, "SELECT id, department, graduation_year FROM alumni_profiles WHERE user_id = ? LIMIT 1", "i", [$user['id']]);

$is_admin_user = ($user['role'] === 'admin');
$is_profile_complete = $is_admin_user || (!empty($profile) && !empty($profile['department']) && !empty($profile['graduation_year']));
$_SESSION['user']['profile_completed'] = $is_profile_complete;

// 7. Determine Destination URL
if (!$is_profile_complete) {
    $redirect_url = BASE_URL . '/auth/complete-profile.php';
    set_flash('info', 'Welcome ' . e($user['full_name']) . '! Please complete your alumni profile to unlock full access.');
} elseif (!$is_admin_user && ($user['status'] ?? '') !== 'verified') {
    $redirect_url = BASE_URL . '/auth/pending-verification.php';
    set_flash('info', 'Welcome back! Your alumni profile is currently awaiting administrator verification.');
} else {
    $default_home = $is_admin_user ? (BASE_URL . '/admin/index.php') : (BASE_URL . '/feed.php');
    $redirect_url = $_SESSION['redirect_url'] ?? $default_home;
    unset($_SESSION['redirect_url']);
    set_flash('success', 'Welcome back, ' . e($user['full_name']) . '!');
}

// 8. Send Response
if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Signed in successfully!',
        'redirect' => $redirect_url,
        'user' => [
            'id' => $user['id'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'status' => $user['status'],
            'profile_completed' => $is_profile_complete
        ]
    ]);
    exit;
} else {
    header('Location: ' . $redirect_url);
    exit;
}
