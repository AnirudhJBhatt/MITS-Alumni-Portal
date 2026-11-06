<?php
/**
 * Content & Post Moderation
 * MITS Alumni Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_check.php';

// Handle Post Deletion (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Security token invalid or expired.');
        header('Location: ' . BASE_URL . '/admin/manage-posts.php');
        exit;
    }

    $post_id = (int)($_POST['post_id'] ?? 0);
    $post = db_fetch_one($conn, "SELECT id, image_url FROM posts WHERE id = ? LIMIT 1", "i", [$post_id]);

    if ($post) {
        if (!empty($post['image_url']) && strpos($post['image_url'], '/uploads/posts/') !== false) {
            $filepath = UPLOAD_DIR . '/posts/' . basename($post['image_url']);
            if (file_exists($filepath)) @unlink($filepath);
        }
        db_execute($conn, "DELETE FROM posts WHERE id = ?", "i", [$post_id]);
        set_flash('success', 'Post removed by administrator.');
    }

    header('Location: ' . BASE_URL . '/admin/manage-posts.php');
    exit;
}

$page_title = 'Moderate Posts';
$current_page = 'admin';

// Fetch all posts with author credentials
$posts = db_fetch_all($conn,
    "SELECT p.*, u.full_name, u.email, u.avatar_url, ap.department, ap.graduation_year 
     FROM posts p 
     JOIN users u ON p.user_id = u.id 
     LEFT JOIN alumni_profiles ap ON u.id = ap.user_id 
     ORDER BY p.created_at DESC"
);

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center border-bottom pb-3 mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-danger text-white rounded-pill px-3 py-1">
                    <i class="bi bi-shield-shaded me-1"></i>Content Moderation
                </span>
                <span class="text-secondary small">Feed Oversight</span>
            </div>
            <h2 class="fw-bold font-heading mb-0 text-dark">Moderate Community Posts</h2>
        </div>

        <a href="<?= e(BASE_URL) ?>/admin/index.php" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i>Dashboard Overview
        </a>
    </div>

    <!-- Posts Table Card -->
    <div class="card-modern border-0 shadow-sm p-4 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>Post ID</th>
                        <th>Author</th>
                        <th>Post Content</th>
                        <th>Category</th>
                        <th>Engagement</th>
                        <th>Published</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $p): ?>
                            <tr>
                                <td class="font-monospace text-muted small">#<?= $p['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= e(get_avatar_url($p['avatar_url'] ?? '', $p['full_name'])) ?>" 
                                             alt="<?= e($p['full_name']) ?>" 
                                             class="rounded-circle avatar-sm object-fit-cover shadow-sm">
                                        <div>
                                            <a href="<?= e(BASE_URL) ?>/profile.php?id=<?= $p['user_id'] ?>" target="_blank" class="fw-bold text-dark text-decoration-none hover-primary small">
                                                <?= e($p['full_name']) ?>
                                            </a>
                                            <div class="text-muted" style="font-size: 0.725rem;"><?= e($p['email']) ?></div>
                                        </div>
                                    </div>
                                </td>

                                <td style="max-width: 320px;">
                                    <div class="text-truncate small text-dark mb-1"><?= e($p['content']) ?></div>
                                    <?php if (!empty($p['image_url'])): ?>
                                        <a href="<?= e($p['image_url']) ?>" target="_blank" class="small text-primary text-decoration-none">
                                            <i class="bi bi-image me-1"></i>View Attachment
                                        </a>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="badge bg-light text-dark border rounded-pill px-2 py-1 text-capitalize small">
                                        <?= e($p['post_type']) ?>
                                    </span>
                                </td>

                                <td class="small text-secondary">
                                    <i class="bi bi-heart text-danger me-1"></i><?= $p['likes_count'] ?>
                                    <span class="mx-2">•</span>
                                    <i class="bi bi-chat text-primary me-1"></i><?= $p['comments_count'] ?>
                                </td>

                                <td class="small text-muted">
                                    <?= time_ago($p['created_at']) ?>
                                </td>

                                <td class="text-end">
                                    <form action="<?= e(BASE_URL) ?>/admin/manage-posts.php" method="POST" onsubmit="return confirm('Permanently remove this post from the community feed?');">
                                        <?= csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                            <i class="bi bi-trash me-1"></i>Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted small">No community posts found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
