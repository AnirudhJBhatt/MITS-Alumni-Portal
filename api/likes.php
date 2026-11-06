<?php
/**
 * Asynchronous Likes API Endpoint
 * MITS Alumni Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

function like_response($success, $message, $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// 1. Must be authenticated
if (!is_logged_in()) {
    like_response(false, 'Please sign in to like posts.', [], 401);
}

// 2. Read Input & Verify CSRF
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true) ?: $_POST;
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

if (!verify_csrf_token($csrf_token)) {
    like_response(false, 'Security token invalid or expired.', [], 403);
}

$post_id = (int)($input['post_id'] ?? 0);
if (!$post_id) {
    like_response(false, 'Invalid post specified.');
}

$current_id = current_user_id();
$current_user = current_user();

// 3. Verify post exists
$post = db_fetch_one($conn, "SELECT id, user_id, likes_count FROM posts WHERE id = ? LIMIT 1", "i", [$post_id]);
if (!$post) {
    like_response(false, 'Post not found.', [], 404);
}

// 4. Check if already liked
$existing_like = db_fetch_one($conn, "SELECT id FROM likes WHERE post_id = ? AND user_id = ? LIMIT 1", "ii", [$post_id, $current_id]);

if ($existing_like) {
    // Unlike post
    db_execute($conn, "DELETE FROM likes WHERE id = ?", "i", [$existing_like['id']]);
    db_execute($conn, "UPDATE posts SET likes_count = GREATEST(likes_count - 1, 0) WHERE id = ?", "i", [$post_id]);
    
    // Get refreshed count
    $refreshed = db_fetch_one($conn, "SELECT likes_count FROM posts WHERE id = ? LIMIT 1", "i", [$post_id]);
    $new_count = (int)($refreshed['likes_count'] ?? 0);

    like_response(true, 'Post unliked', [
        'has_liked' => false,
        'likes_count' => $new_count,
        'post_id' => $post_id
    ]);
} else {
    // Like post
    db_execute($conn, "INSERT INTO likes (post_id, user_id) VALUES (?, ?)", "ii", [$post_id, $current_id]);
    db_execute($conn, "UPDATE posts SET likes_count = likes_count + 1 WHERE id = ?", "i", [$post_id]);

    // Send notification to post owner if not self
    if ($post['user_id'] != $current_id) {
        $msg = $current_user['full_name'] . ' liked your post.';
        db_execute($conn,
            "INSERT INTO notifications (user_id, actor_id, type, entity_id, message) VALUES (?, ?, 'like', ?, ?)",
            "iiis",
            [$post['user_id'], $current_id, $post_id, $msg]
        );
    }

    // Get refreshed count
    $refreshed = db_fetch_one($conn, "SELECT likes_count FROM posts WHERE id = ? LIMIT 1", "i", [$post_id]);
    $new_count = (int)($refreshed['likes_count'] ?? 1);

    like_response(true, 'Post liked', [
        'has_liked' => true,
        'likes_count' => $new_count,
        'post_id' => $post_id
    ]);
}
