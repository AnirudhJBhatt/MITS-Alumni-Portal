<?php
/**
 * Asynchronous Comments API Endpoint
 * MITS Alumni Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

function comment_response($success, $message, $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Handle GET: Fetch comments for a post
if ($method === 'GET') {
    $post_id = (int)($_GET['post_id'] ?? 0);
    if (!$post_id) {
        comment_response(false, 'Invalid post specified.', [], 400);
    }

    $sql = "SELECT c.id, c.content, c.created_at, c.user_id,
                   u.full_name, u.avatar_url, u.role,
                   p.department, p.graduation_year, p.current_company
            FROM comments c
            JOIN users u ON c.user_id = u.id
            LEFT JOIN alumni_profiles p ON u.id = p.user_id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC";

    $comments = db_fetch_all($conn, $sql, "i", [$post_id]);
    
    // Format response items
    $formatted = [];
    $current_id = current_user_id();
    $is_current_admin = is_admin();

    foreach ($comments as $c) {
        $formatted[] = [
            'id' => $c['id'],
            'content' => e($c['content']),
            'created_at' => time_ago($c['created_at']),
            'user' => [
                'id' => $c['user_id'],
                'name' => e($c['full_name']),
                'avatar' => get_avatar_url($c['avatar_url'] ?? '', $c['full_name']),
                'details' => (!empty($c['department']) ? $c['department'] : 'Alumnus') . 
                             (!empty($c['graduation_year']) ? ' • Class of ' . $c['graduation_year'] : '')
            ],
            'can_delete' => ($c['user_id'] == $current_id || $is_current_admin)
        ];
    }

    comment_response(true, 'Comments loaded', ['comments' => $formatted, 'count' => count($formatted)]);
}

// All modifying actions require authentication
if (!is_logged_in()) {
    comment_response(false, 'Please sign in to comment.', [], 401);
}

// Read payload
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true) ?: $_POST;
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

if (!verify_csrf_token($csrf_token)) {
    comment_response(false, 'Security token invalid or expired.', [], 403);
}

$current_id = current_user_id();
$current_user = current_user();

// Handle POST: Add new comment
if ($method === 'POST' && ($input['action'] ?? 'add') === 'add') {
    $post_id = (int)($input['post_id'] ?? 0);
    $content = sanitize_input($input['content'] ?? '');

    if (!$post_id) {
        comment_response(false, 'Invalid post specified.');
    }
    if (empty($content) || mb_strlen($content) < 1) {
        comment_response(false, 'Comment text cannot be empty.');
    }
    if (mb_strlen($content) > 1000) {
        comment_response(false, 'Comment exceeds 1,000 characters limit.');
    }

    // Verify post exists
    $post = db_fetch_one($conn, "SELECT id, user_id FROM posts WHERE id = ? LIMIT 1", "i", [$post_id]);
    if (!$post) {
        comment_response(false, 'Post does not exist.');
    }

    // Insert comment
    $stmt = db_execute($conn,
        "INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)",
        "iis",
        [$post_id, $current_id, $content]
    );

    if (!$stmt) {
        comment_response(false, 'Failed to save comment.');
    }

    $comment_id = mysqli_insert_id($conn);
    db_execute($conn, "UPDATE posts SET comments_count = comments_count + 1 WHERE id = ?", "i", [$post_id]);

    // Send notification to post owner if not self
    if ($post['user_id'] != $current_id) {
        $msg = $current_user['full_name'] . ' commented on your post: "' . mb_substr($content, 0, 45) . '..."';
        db_execute($conn,
            "INSERT INTO notifications (user_id, actor_id, type, entity_id, message) VALUES (?, ?, 'comment', ?, ?)",
            "iiis",
            [$post['user_id'], $current_id, $post_id, $msg]
        );
    }

    // Fetch refreshed comment count
    $refreshed = db_fetch_one($conn, "SELECT comments_count FROM posts WHERE id = ? LIMIT 1", "i", [$post_id]);

    comment_response(true, 'Comment added successfully!', [
        'comment' => [
            'id' => $comment_id,
            'content' => e($content),
            'created_at' => 'just now',
            'user' => [
                'id' => $current_user['id'],
                'name' => e($current_user['full_name']),
                'avatar' => get_avatar_url($current_user['avatar_url'] ?? '', $current_user['full_name']),
                'details' => 'You'
            ],
            'can_delete' => true
        ],
        'comments_count' => (int)($refreshed['comments_count'] ?? 1)
    ]);
}

// Handle DELETE: Remove comment
if ($method === 'DELETE' || ($input['action'] ?? '') === 'delete') {
    $comment_id = (int)($input['comment_id'] ?? 0);
    $comment = db_fetch_one($conn, "SELECT c.*, p.user_id as post_author_id FROM comments c JOIN posts p ON c.post_id = p.id WHERE c.id = ? LIMIT 1", "i", [$comment_id]);

    if (!$comment) {
        comment_response(false, 'Comment not found.');
    }

    // Permission check: Author of comment, Post owner, or Admin
    if ($comment['user_id'] != $current_id && $comment['post_author_id'] != $current_id && !is_admin()) {
        comment_response(false, 'You do not have permission to delete this comment.', [], 403);
    }

    db_execute($conn, "DELETE FROM comments WHERE id = ?", "i", [$comment_id]);
    db_execute($conn, "UPDATE posts SET comments_count = GREATEST(comments_count - 1, 0) WHERE id = ?", "i", [$comment['post_id']]);

    $refreshed = db_fetch_one($conn, "SELECT comments_count FROM posts WHERE id = ? LIMIT 1", "i", [$comment['post_id']]);

    comment_response(true, 'Comment deleted successfully.', [
        'comments_count' => (int)($refreshed['comments_count'] ?? 0),
        'post_id' => $comment['post_id']
    ]);
}

comment_response(false, 'Unsupported comment request method.');
