<?php
/**
 * Asynchronous Jobs & Career API Endpoint
 * MITS Alumni Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

function job_api_response($success, $message, $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Handle GET: Fetch single job detail for modal
if ($method === 'GET') {
    $job_id = (int)($_GET['id'] ?? 0);
    if (!$job_id) {
        job_api_response(false, 'Invalid job ID.', [], 400);
    }

    $sql = "SELECT j.*, 
                   u.id as poster_id, u.full_name as poster_name, u.avatar_url as poster_avatar, u.role as poster_role,
                   ap.department, ap.graduation_year, ap.current_company as poster_company, ap.designation as poster_designation
            FROM jobs j
            JOIN users u ON j.posted_by = u.id
            LEFT JOIN alumni_profiles ap ON u.id = ap.user_id
            WHERE j.id = ? 
            LIMIT 1";

    $job = db_fetch_one($conn, $sql, "i", [$job_id]);

    if (!$job) {
        job_api_response(false, 'Job listing not found.', [], 404);
    }

    $current_id = current_user_id();
    $can_manage = is_logged_in() && ($job['poster_id'] == $current_id || is_admin());

    job_api_response(true, 'Job loaded', [
        'job' => [
            'id' => $job['id'],
            'title' => e($job['title']),
            'company' => e($job['company']),
            'location' => e($job['location']),
            'job_type' => $job['job_type'],
            'experience_level' => e($job['experience_level']),
            'description' => nl2br(e($job['description'])),
            'requirements' => !empty($job['requirements']) ? nl2br(e($job['requirements'])) : null,
            'apply_link' => $job['apply_link'],
            'contact_email' => $job['contact_email'],
            'status' => $job['status'],
            'deadline' => !empty($job['deadline']) ? format_date($job['deadline']) : null,
            'created_at_human' => time_ago($job['created_at']),
            'poster' => [
                'id' => $job['poster_id'],
                'name' => e($job['poster_name']),
                'avatar' => get_avatar_url($job['poster_avatar'] ?? '', $job['poster_name']),
                'headline' => !empty($job['poster_designation']) && !empty($job['poster_company']) ?
                              e($job['poster_designation']) . ' at ' . e($job['poster_company']) : 'MITS Alumni'
            ],
            'can_manage' => $can_manage
        ]
    ]);
}

// All modifying actions require authentication
if (!is_logged_in()) {
    job_api_response(false, 'Please sign in to manage job listings.', [], 401);
}

// Read input & verify CSRF
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true) ?: $_POST;
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

if (!verify_csrf_token($csrf_token)) {
    job_api_response(false, 'Security token invalid or expired.', [], 403);
}

$action = $input['action'] ?? '';
$job_id = (int)($input['job_id'] ?? 0);
$current_id = current_user_id();

if (!$job_id) {
    job_api_response(false, 'Job listing ID required.', [], 400);
}

// Check job ownership
$job = db_fetch_one($conn, "SELECT id, posted_by, status FROM jobs WHERE id = ? LIMIT 1", "i", [$job_id]);
if (!$job) {
    job_api_response(false, 'Job listing not found.', [], 404);
}

if ($job['posted_by'] != $current_id && !is_admin()) {
    job_api_response(false, 'Permission denied: You can only manage your own job postings.', [], 403);
}

// Handle Status Toggle (Active <-> Closed)
if ($action === 'toggle_status') {
    $new_status = ($job['status'] === 'active') ? 'closed' : 'active';
    db_execute($conn, "UPDATE jobs SET status = ? WHERE id = ?", "si", [$new_status, $job_id]);
    job_api_response(true, 'Job status updated to ' . ucfirst($new_status), ['status' => $new_status]);
}

// Handle Delete Job
if ($action === 'delete') {
    db_execute($conn, "DELETE FROM jobs WHERE id = ?", "i", [$job_id]);
    job_api_response(true, 'Job listing deleted successfully.');
}

job_api_response(false, 'Unsupported job action.');
