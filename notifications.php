<?php
/**
 * Notifications Center
 * MITS Alumni Portal
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

$user = current_user();
$current_id = $user['id'];

$page_title = 'Notifications';
$page_description = 'View your recent activity, connection requests, comments, and community updates.';
$current_page = 'notifications';

// Fetch all notifications
$sql = "SELECT n.*, 
               u.full_name as actor_name, u.avatar_url as actor_avatar, u.role as actor_role
        FROM notifications n
        JOIN users u ON n.actor_id = u.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 50";

$notifications = db_fetch_all($conn, $sql, "i", [$current_id]);
$unread_count = 0;
foreach ($notifications as $n) {
    if (!$n['is_read']) $unread_count++;
}

include_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card-modern border-0 shadow-sm p-4 p-md-5 bg-white">
                
                <!-- Page Header & Actions -->
                <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
                    <div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 mb-2">
                            <i class="bi bi-bell-fill me-1"></i>Activity Center
                        </span>
                        <h2 class="fw-bold font-heading mb-0 text-dark">Notifications</h2>
                    </div>

                    <?php if ($unread_count > 0): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="markAllNotificationsRead(this)">
                            <i class="bi bi-check2-all me-1"></i>Mark all as read
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Notifications List -->
                <div class="d-flex flex-column gap-3" id="notifications-container">
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $notif): 
                            // Determine destination URL
                            $target_url = BASE_URL . '/notifications.php';
                            if (in_array($notif['type'], ['like', 'comment'])) {
                                $target_url = BASE_URL . '/feed.php#post-card-' . $notif['entity_id'];
                            } elseif (in_array($notif['type'], ['connection_request', 'connection_accepted'])) {
                                $target_url = BASE_URL . '/network.php';
                            } elseif ($notif['type'] === 'job_alert') {
                                $target_url = BASE_URL . '/jobs.php?id=' . $notif['entity_id'];
                            } elseif ($notif['type'] === 'admin_verification') {
                                $target_url = BASE_URL . '/profile.php';
                            }

                            // Notification icon
                            $icon_class = 'bi-bell-fill text-secondary';
                            if ($notif['type'] === 'like') $icon_class = 'bi-heart-fill text-danger';
                            if ($notif['type'] === 'comment') $icon_class = 'bi-chat-fill text-primary';
                            if ($notif['type'] === 'connection_request') $icon_class = 'bi-person-plus-fill text-info';
                            if ($notif['type'] === 'connection_accepted') $icon_class = 'bi-person-check-fill text-success';
                            if ($notif['type'] === 'job_alert') $icon_class = 'bi-briefcase-fill text-warning';
                            if ($notif['type'] === 'admin_verification') $icon_class = 'bi-patch-check-fill text-success';
                        ?>
                            <div class="p-3 border rounded-3 position-relative transition-all <?= !$notif['is_read'] ? 'bg-light-subtle border-start border-start-4 border-start-danger' : 'bg-white' ?>" 
                                 id="notif-item-<?= $notif['id'] ?>">
                                
                                <div class="d-flex align-items-center gap-3">
                                    <div class="position-relative">
                                        <img src="<?= e(get_avatar_url($notif['actor_avatar'] ?? '', $notif['actor_name'])) ?>" 
                                             alt="<?= e($notif['actor_name']) ?>" 
                                             class="rounded-circle avatar-md object-fit-cover shadow-sm">
                                        <span class="position-absolute bottom-0 end-0 bg-white rounded-circle p-1 line-height-tight shadow-sm" style="font-size: 0.75rem;">
                                            <i class="bi <?= $icon_class ?>"></i>
                                        </span>
                                    </div>

                                    <div class="flex-grow-1 overflow-hidden">
                                        <a href="<?= e($target_url) ?>" class="text-dark text-decoration-none fw-semibold small d-block mb-1 hover-primary" onclick="markSingleRead(<?= $notif['id'] ?>)">
                                            <?= e($notif['message']) ?>
                                        </a>
                                        <div class="text-muted small" style="font-size: 0.75rem;">
                                            <i class="bi bi-clock me-1"></i><?= time_ago($notif['created_at']) ?>
                                        </div>
                                    </div>

                                    <div>
                                        <button type="button" class="btn btn-sm btn-link text-secondary p-1" title="Delete notification" onclick="deleteNotification(<?= $notif['id'] ?>)">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Empty Notifications State -->
                        <div class="text-center py-5">
                            <div class="brand-icon-box mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem; opacity: 0.85;">
                                <i class="bi bi-bell-slash"></i>
                            </div>
                            <h4 class="fw-bold font-heading mb-1 text-dark">No Notifications Yet</h4>
                            <p class="text-secondary small mb-3">When you receive likes, comments, or connection requests, they'll appear here.</p>
                            <a href="<?= e(BASE_URL) ?>/feed.php" class="btn btn-primary rounded-pill px-4">
                                Browse Community Feed
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
async function markAllNotificationsRead(btn) {
    btn.disabled = true;
    try {
        const res = await apiFetch(getBaseUrl() + '/api/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_all_read' })
        });
        const data = await res.json();
        if (data.success) {
            showToast('success', data.message);
            document.querySelectorAll('#notifications-container .border-start-danger').forEach(el => {
                el.classList.remove('border-start-danger', 'bg-light-subtle');
                el.classList.add('bg-white');
            });
            btn.remove();
            const badge = document.getElementById('nav-notif-badge');
            if (badge) badge.classList.add('d-none');
        }
    } catch (e) {
        showToast('danger', 'Error updating notifications.');
    }
}

async function markSingleRead(id) {
    apiFetch(getBaseUrl() + '/api/notifications.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'mark_read', notification_id: id })
    });
}

async function deleteNotification(id) {
    try {
        const res = await apiFetch(getBaseUrl() + '/api/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', notification_id: id })
        });
        const data = await res.json();
        if (data.success) {
            const item = document.getElementById(`notif-item-${id}`);
            if (item) item.remove();
            showToast('info', 'Notification removed.');
        }
    } catch (e) {
        showToast('danger', 'Error deleting notification.');
    }
}
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
