<?php
/**
 * Asynchronous Connections API Endpoint
 * MITS Alumni Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

function api_response($success, $message, $data = [], $status_code = 200) {
    http_response_code($status_code);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// 1. Must be authenticated
if (!is_logged_in()) {
    api_response(false, 'Please sign in to manage connections.', [], 401);
}

// 2. Read JSON or form input
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true) ?: $_POST;
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

// 3. Verify CSRF
if (!verify_csrf_token($csrf_token)) {
    api_response(false, 'Security token invalid or expired. Please refresh the page.', [], 403);
}

$action = $input['action'] ?? '';
$current_id = current_user_id();
$current_user = current_user();

switch ($action) {
    case 'send':
        $target_id = (int)($input['target_user_id'] ?? 0);
        if (!$target_id || $target_id === $current_id) {
            api_response(false, 'Invalid user target specified.');
        }

        // Verify target exists
        $target = db_fetch_one($conn, "SELECT id, full_name FROM users WHERE id = ? LIMIT 1", "i", [$target_id]);
        if (!$target) {
            api_response(false, 'User does not exist.');
        }

        // Check for existing connection
        $existing = db_fetch_one($conn, 
            "SELECT id, status, sender_id FROM connections 
             WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) 
             LIMIT 1",
            "iiii",
            [$current_id, $target_id, $target_id, $current_id]
        );

        if ($existing) {
            if ($existing['status'] === 'accepted') {
                api_response(false, 'You are already connected with ' . $target['full_name']);
            } elseif ($existing['status'] === 'pending') {
                api_response(false, 'A connection request is already pending.');
            }
        }

        // Insert new request
        $stmt = db_execute($conn,
            "INSERT INTO connections (sender_id, receiver_id, status) VALUES (?, ?, 'pending')",
            "ii",
            [$current_id, $target_id]
        );

        if ($stmt) {
            $conn_id = mysqli_insert_id($conn);
            // Create notification for target user
            $msg = $current_user['full_name'] . ' sent you a connection request.';
            db_execute($conn,
                "INSERT INTO notifications (user_id, actor_id, type, entity_id, message) VALUES (?, ?, 'connection_request', ?, ?)",
                "iiis",
                [$target_id, $current_id, $conn_id, $msg]
            );

            api_response(true, 'Connection request sent to ' . $target['full_name'], ['connection_id' => $conn_id]);
        } else {
            api_response(false, 'Failed to send connection request.');
        }
        break;

    case 'accept':
    case 'accepted':
        $connection_id = (int)($input['connection_id'] ?? 0);
        $conn_row = db_fetch_one($conn, 
            "SELECT c.*, u.full_name as sender_name 
             FROM connections c
             JOIN users u ON c.sender_id = u.id
             WHERE c.id = ? AND c.receiver_id = ? AND c.status = 'pending' 
             LIMIT 1", 
            "ii", 
            [$connection_id, $current_id]
        );

        if (!$conn_row) {
            api_response(false, 'Connection request not found or already processed.');
        }

        db_execute($conn, "UPDATE connections SET status = 'accepted' WHERE id = ?", "i", [$connection_id]);

        // Send acceptance notification to original sender
        $msg = $current_user['full_name'] . ' accepted your connection request.';
        db_execute($conn,
            "INSERT INTO notifications (user_id, actor_id, type, entity_id, message) VALUES (?, ?, 'connection_accepted', ?, ?)",
            "iiis",
            [$conn_row['sender_id'], $current_id, $connection_id, $msg]
        );

        api_response(true, 'Connection request accepted!');
        break;

    case 'reject':
    case 'rejected':
    case 'withdraw':
    case 'remove':
        $connection_id = (int)($input['connection_id'] ?? 0);
        $target_user_id = (int)($input['target_user_id'] ?? 0);

        if ($connection_id > 0) {
            $stmt = db_execute($conn, 
                "DELETE FROM connections WHERE id = ? AND (receiver_id = ? OR sender_id = ?)", 
                "iii", 
                [$connection_id, $current_id, $current_id]
            );
        } elseif ($target_user_id > 0) {
            $stmt = db_execute($conn,
                "DELETE FROM connections WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))",
                "iiii",
                [$current_id, $target_user_id, $target_user_id, $current_id]
            );
        } else {
            api_response(false, 'Connection identifier missing.');
        }

        if ($stmt) {
            api_response(true, 'Connection updated successfully.');
        } else {
            api_response(false, 'Failed to update connection.');
        }
        break;

    default:
        api_response(false, 'Unsupported connection action.');
}
