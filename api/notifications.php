<?php
/**
 * Asynchronous Notifications API Endpoint
 * MITS Alumni Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

function notif_response($success, $message, $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// 1. Must be authenticated
if (!is_logged_in()) {
    notif_response(false, 'Unauthorized', [], 401);
}

$current_id = current_user_id();
$method = $_SERVER['REQUEST_METHOD'];

// Handle GET: Fetch unread count or list
if ($method === 'GET') {
    $action = $_GET['action'] ?? 'count';

    if ($action === 'count') {
        $row = db_fetch_one($conn, "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0", "i", [$current_id]);
        $unread = (int)($row['unread'] ?? 0);
        notif_response(true, 'Count retrieved', ['unread' => $unread]);
    }

    if ($action === 'list') {
        $limit = min(max((int)($_GET['limit'] ?? 15), 1), 50);
        $sql = "SELECT n.*, 
                       u.full_name as actor_name, u.avatar_url as actor_avatar
                FROM notifications n
                JOIN users u ON n.actor_id = u.id
                WHERE n.user_id = ?
                ORDER BY n.created_at DESC
                LIMIT ?";

        $list = db_fetch_all($conn, $sql, "ii", [$current_id, $limit]);
        $formatted = [];

        foreach ($list as $item) {
            // Determine target destination URL based on notification type
            $target_url = BASE_URL . '/notifications.php';
            if (in_array($item['type'], ['like', 'comment'])) {
                $target_url = BASE_URL . '/feed.php#post-card-' . $item['entity_id'];
            } elseif (in_array($item['type'], ['connection_request', 'connection_accepted'])) {
                $target_url = BASE_URL . '/network.php';
            } elseif ($item['type'] === 'job_alert') {
                $target_url = BASE_URL . '/jobs.php?id=' . $item['entity_id'];
            } elseif ($item['type'] === 'admin_verification') {
                $target_url = BASE_URL . '/profile.php';
            }

            $formatted[] = [
                'id' => $item['id'],
                'type' => $item['type'],
                'message' => e($item['message']),
                'is_read' => (bool)$item['is_read'],
                'created_at_human' => time_ago($item['created_at']),
                'target_url' => $target_url,
                'actor' => [
                    'name' => e($item['actor_name']),
                    'avatar' => get_avatar_url($item['actor_avatar'] ?? '', $item['actor_name'])
                ]
            ];
        }

        notif_response(true, 'Notifications loaded', ['notifications' => $formatted]);
    }
}

// Handle POST: Mark as read / Delete
if ($method === 'POST') {
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true) ?: $_POST;
    $csrf = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

    if (!verify_csrf_token($csrf)) {
        notif_response(false, 'Security token invalid or expired.', [], 403);
    }

    $action = $input['action'] ?? '';

    if ($action === 'mark_read') {
        $notif_id = (int)($input['notification_id'] ?? 0);
        if ($notif_id > 0) {
            db_execute($conn, "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?", "ii", [$notif_id, $current_id]);
        }
        notif_response(true, 'Notification marked as read.');
    }

    if ($action === 'mark_all_read') {
        db_execute($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = ?", "i", [$current_id]);
        notif_response(true, 'All notifications marked as read.');
    }

    if ($action === 'delete') {
        $notif_id = (int)($input['notification_id'] ?? 0);
        db_execute($conn, "DELETE FROM notifications WHERE id = ? AND user_id = ?", "ii", [$notif_id, $current_id]);
        notif_response(true, 'Notification removed.');
    }
}

notif_response(false, 'Unsupported request method.', [], 405);
