<?php
/**
 * Asynchronous Posts & Feed API Endpoint
 * MITS Alumni Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
           (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
           isset($_GET['ajax']) || isset($_POST['ajax']);

function post_api_response($success, $message, $data = [], $code = 200, $is_ajax = true) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
        exit;
    } else {
        set_flash($success ? 'success' : 'danger', $message);
        header('Location: ' . BASE_URL . '/feed.php');
        exit;
    }
}

// 1. Handle GET: Fetch feed posts
if ($method === 'GET') {
    header('Content-Type: application/json');
    $filter = sanitize_input($_GET['filter'] ?? 'all');
    $page   = max((int)($_GET['page'] ?? 1), 1);
    $limit  = min(max((int)($_GET['limit'] ?? 10), 1), 50);
    $offset = ($page - 1) * $limit;

    $sql = "SELECT p.*, 
                   u.id as author_id, u.full_name, u.avatar_url, u.role, u.status as author_status,
                   ap.department, ap.graduation_year, ap.current_company, ap.designation
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN alumni_profiles ap ON u.id = ap.user_id";

    $types = "";
    $params = [];

    if ($filter !== 'all' && in_array($filter, ['general', 'achievement', 'hiring', 'event'])) {
        $sql .= " WHERE p.post_type = ?";
        $types .= "s";
        $params[] = $filter;
    }

    $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
    $types .= "ii";
    $params[] = $limit;
    $params[] = $offset;

    $posts = db_fetch_all($conn, $sql, $types, $params);
    $current_id = current_user_id();

    // Map whether current user has liked each post
    $my_likes = [];
    if (is_logged_in() && !empty($posts)) {
        $post_ids = array_column($posts, 'id');
        $placeholders = implode(',', array_fill(0, count($post_ids), '?'));
        $like_types = str_repeat('i', count($post_ids)) . 'i';
        $like_params = array_merge($post_ids, [$current_id]);
        
        $likes_res = db_fetch_all($conn, "SELECT post_id FROM likes WHERE post_id IN ($placeholders) AND user_id = ?", $like_types, $like_params);
        foreach ($likes_res as $lr) {
            $my_likes[$lr['post_id']] = true;
        }
    }

    $formatted = [];
    foreach ($posts as $post) {
        $formatted[] = [
            'id' => $post['id'],
            'content' => e($post['content']),
            'image_url' => $post['image_url'],
            'post_type' => $post['post_type'],
            'likes_count' => (int)$post['likes_count'],
            'comments_count' => (int)$post['comments_count'],
            'has_liked' => !empty($my_likes[$post['id']]),
            'created_at_human' => time_ago($post['created_at']),
            'author' => [
                'id' => $post['author_id'],
                'name' => e($post['full_name']),
                'avatar' => get_avatar_url($post['avatar_url'] ?? '', $post['full_name']),
                'role' => $post['role'],
                'status' => $post['author_status'],
                'department' => e($post['department'] ?? ''),
                'graduation_year' => $post['graduation_year'],
                'headline' => !empty($post['designation']) && !empty($post['current_company']) ?
                              e($post['designation']) . ' at ' . e($post['current_company']) : 
                              (!empty($post['current_company']) ? e($post['current_company']) : 'MITS Alumni')
            ],
            'can_delete' => is_logged_in() && ($post['author_id'] == $current_id || is_admin())
        ];
    }

    echo json_encode([
        'success' => true,
        'posts' => $formatted,
        'page' => $page,
        'has_more' => count($formatted) === $limit
    ]);
    exit;
}

// All modifying actions require authentication
if (!is_logged_in()) {
    post_api_response(false, 'Please sign in to publish or manage posts.', [], 401, $is_ajax);
}

// 2. Handle POST: Create new post
if ($method === 'POST') {
    $raw_input = file_get_contents('php://input');
    $json = json_decode($raw_input, true);

    $content   = sanitize_input($_POST['content'] ?? $json['content'] ?? '');
    $post_type = sanitize_input($_POST['post_type'] ?? $json['post_type'] ?? 'general');
    $csrf      = $_POST['csrf_token'] ?? $json['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

    if (!verify_csrf_token($csrf)) {
        post_api_response(false, 'Security token expired or invalid.', [], 403, $is_ajax);
    }

    if (empty($content) || mb_strlen($content) < 2) {
        post_api_response(false, 'Post content cannot be empty.', [], 400, $is_ajax);
    }

    if (mb_strlen($content) > 5000) {
        post_api_response(false, 'Post content exceeds 5,000 characters limit.', [], 400, $is_ajax);
    }

    $valid_types = ['general', 'achievement', 'hiring', 'event'];
    if (!in_array($post_type, $valid_types)) {
        $post_type = 'general';
    }

    $current_id = current_user_id();
    $image_url = null;

    // Handle optional image file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_size = $_FILES['image']['size'];

        if ($file_size > MAX_UPLOAD_SIZE) {
            post_api_response(false, 'Attached image exceeds maximum 5 MB limit.', [], 400, $is_ajax);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);

        $allowed_mimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif'
        ];

        if (!array_key_exists($mime_type, $allowed_mimes)) {
            post_api_response(false, 'Invalid image format. Allowed: JPG, PNG, WebP, GIF.', [], 400, $is_ajax);
        }

        $ext = $allowed_mimes[$mime_type];
        $filename = 'post_' . $current_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $target_path = UPLOAD_DIR . '/posts/' . $filename;

        if (move_uploaded_file($file_tmp, $target_path)) {
            $image_url = BASE_URL . '/uploads/posts/' . $filename;
        } else {
            post_api_response(false, 'Failed to save attached image.', [], 500, $is_ajax);
        }
    }

    // Insert post into database
    $stmt = db_execute($conn,
        "INSERT INTO posts (user_id, content, image_url, post_type) VALUES (?, ?, ?, ?)",
        "isss",
        [$current_id, $content, $image_url, $post_type]
    );

    if ($stmt) {
        $new_post_id = mysqli_insert_id($conn);
        post_api_response(true, 'Post published successfully!', [
            'post_id' => $new_post_id,
            'redirect' => BASE_URL . '/feed.php'
        ], 200, $is_ajax);
    } else {
        post_api_response(false, 'Database error while saving post.', [], 500, $is_ajax);
    }
}

// 3. Handle DELETE: Delete post
if ($method === 'DELETE' || ($method === 'POST' && ($_POST['action'] ?? '') === 'delete')) {
    $raw_input = file_get_contents('php://input');
    $json = json_decode($raw_input, true);

    $post_id = (int)($_POST['post_id'] ?? $json['post_id'] ?? 0);
    $csrf    = $_POST['csrf_token'] ?? $json['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

    if (!verify_csrf_token($csrf)) {
        post_api_response(false, 'Security token expired or invalid.', [], 403, $is_ajax);
    }

    if (!$post_id) {
        post_api_response(false, 'Post ID missing.', [], 400, $is_ajax);
    }

    $post = db_fetch_one($conn, "SELECT id, user_id, image_url FROM posts WHERE id = ? LIMIT 1", "i", [$post_id]);
    if (!$post) {
        post_api_response(false, 'Post not found.', [], 404, $is_ajax);
    }

    $current_id = current_user_id();
    if ($post['user_id'] != $current_id && !is_admin()) {
        post_api_response(false, 'Permission denied: You can only delete your own posts.', [], 403, $is_ajax);
    }

    // Clean up local uploaded file if present
    if (!empty($post['image_url']) && strpos($post['image_url'], '/uploads/posts/') !== false) {
        $basename = basename($post['image_url']);
        $filepath = UPLOAD_DIR . '/posts/' . $basename;
        if (file_exists($filepath)) {
            @unlink($filepath);
        }
    }

    // Delete post record (cascade deletes likes & comments)
    db_execute($conn, "DELETE FROM posts WHERE id = ?", "i", [$post_id]);

    post_api_response(true, 'Post deleted successfully.', ['post_id' => $post_id], 200, $is_ajax);
}

post_api_response(false, 'Invalid request method.', [], 405, $is_ajax);
