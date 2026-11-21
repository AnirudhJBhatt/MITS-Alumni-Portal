<?php
/**
 * Alumni Profile View Page
 * MITS Alumni Portal
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// Determine profile user ID
$target_user_id = isset($_GET['id']) ? (int)$_GET['id'] : current_user_id();

if (!$target_user_id) {
    set_flash('warning', 'Please sign in or select an alumni member to view their profile.');
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Fetch user and profile
$sql = "SELECT u.id, u.full_name, u.email, u.avatar_url, u.role, u.status, u.created_at as joined_date,
               p.roll_number, p.department, p.graduation_year, p.current_company, p.designation,
               p.industry, p.location, p.linkedin_url, p.bio, p.skills, p.is_mentor
        FROM users u
        LEFT JOIN alumni_profiles p ON u.id = p.user_id
        WHERE u.id = ?
        LIMIT 1";

$profile_user = db_fetch_one($conn, $sql, "i", [$target_user_id]);

if (!$profile_user) {
    $page_title = 'User Not Found';
    include_once __DIR__ . '/includes/header.php';
    echo '
    <div class="container py-5 text-center">
        <div class="card-modern p-5 max-w-600 mx-auto">
            <i class="bi bi-person-x fs-1 text-danger mb-3"></i>
            <h3 class="fw-bold">Alumni Member Not Found</h3>
            <p class="text-secondary">The requested profile does not exist or may have been removed.</p>
            <a href="' . e(BASE_URL) . '/directory.php" class="btn btn-primary rounded-pill px-4 mt-2">
                <i class="bi bi-people me-2"></i>Explore Alumni Directory
            </a>
        </div>
    </div>';
    include_once __DIR__ . '/includes/footer.php';
    exit;
}

$is_own_profile = is_logged_in() && (current_user_id() == $target_user_id);
$current_id = current_user_id();

// Check connection status if viewing someone else's profile
$connection = null;
if (is_logged_in() && !$is_own_profile) {
    $conn_sql = "SELECT id, sender_id, receiver_id, status FROM connections 
                 WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) 
                 LIMIT 1";
    $connection = db_fetch_one($conn, $conn_sql, "iiii", [$current_id, $target_user_id, $target_user_id, $current_id]);
}

// Fetch user's recent posts
$posts_sql = "SELECT id, content, image_url, post_type, likes_count, comments_count, created_at 
              FROM posts 
              WHERE user_id = ? 
              ORDER BY created_at DESC 
              LIMIT 5";
$recent_posts = db_fetch_all($conn, $posts_sql, "i", [$target_user_id]);

// Fetch user's posted jobs (if any)
$jobs_sql = "SELECT id, title, company, location, job_type, created_at 
             FROM jobs 
             WHERE posted_by = ? AND status = 'active' 
             ORDER BY created_at DESC 
             LIMIT 5";
$posted_jobs = db_fetch_all($conn, $jobs_sql, "i", [$target_user_id]);

$page_title = $profile_user['full_name'] . ' - Profile';
$page_description = $profile_user['full_name'] . ', ' . ($profile_user['designation'] ?? 'Alumnus') . ' from MITS.';
$current_page = $is_own_profile ? 'profile' : 'directory';

include_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Profile Cover & Header Card -->
    <div class="card-modern border-0 shadow-sm overflow-hidden mb-4">
        <!-- Brand Gradient Banner -->
        <div class="profile-banner position-relative" style="height: 160px; background: var(--brand-gradient);">
            <div class="position-absolute bottom-0 end-0 p-3 text-white-50 small d-none d-md-block">
                <i class="bi bi-mortarboard-fill me-1"></i> MITS Connect
            </div>
        </div>

        <div class="card-body p-4 pt-0">
            <div class="row align-items-end g-3">
                <!-- Avatar Column -->
                <div class="col-auto position-relative" style="margin-top: -60px;">
                    <img src="<?= e(get_avatar_url($profile_user['avatar_url'] ?? '', $profile_user['full_name'])) ?>" 
                         alt="<?= e($profile_user['full_name']) ?>" 
                         class="rounded-circle border border-4 border-white shadow object-fit-cover" 
                         style="width: 120px; height: 120px;">
                </div>

                <!-- Main Info Column -->
                <div class="col">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h2 class="fw-bold font-heading mb-0 text-dark"><?= e($profile_user['full_name']) ?></h2>
                        <?= get_status_badge($profile_user['status']) ?>
                        <?php if (!empty($profile_user['is_mentor'])): ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1">
                                <i class="bi bi-award-fill me-1"></i>Mentor
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="text-secondary mb-2">
                        <?php if (!empty($profile_user['designation']) && !empty($profile_user['current_company'])): ?>
                            <span class="fw-semibold text-dark"><?= e($profile_user['designation']) ?></span> at <span class="fw-semibold text-dark"><?= e($profile_user['current_company']) ?></span>
                        <?php elseif (!empty($profile_user['current_company'])): ?>
                            <span><?= e($profile_user['current_company']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">MITS Alumnus</span>
                        <?php endif; ?>

                        <?php if (!empty($profile_user['location'])): ?>
                            <span class="mx-2">•</span>
                            <i class="bi bi-geo-alt text-danger me-1"></i><?= e($profile_user['location']) ?>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
                        <?php if (!empty($profile_user['department'])): ?>
                            <div>
                                <i class="bi bi-building me-1 text-primary"></i>
                                <?= e($profile_user['department']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($profile_user['graduation_year'])): ?>
                            <div>
                                <i class="bi bi-calendar3 me-1 text-primary"></i>
                                Class of <?= e($profile_user['graduation_year']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($is_own_profile || is_admin()): ?>
                            <?php if (!empty($profile_user['roll_number'])): ?>
                                <div>
                                    <i class="bi bi-card-heading me-1 text-primary"></i>
                                    Roll No: <strong><?= e($profile_user['roll_number']) ?></strong>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Profile Actions Column -->
                <div class="col-12 col-md-auto text-md-end pt-2 pt-md-0">
                    <?php if ($is_own_profile): ?>
                        <a href="<?= e(BASE_URL) ?>/edit-profile.php" class="btn btn-outline-primary rounded-pill px-4">
                            <i class="bi bi-pencil-square me-2"></i>Edit Profile
                        </a>
                    <?php elseif (is_logged_in()): ?>
                        <div class="d-flex gap-2 justify-content-md-end" id="connection-actions-container">
                            <?php if (!$connection): ?>
                                <button type="button" class="btn btn-primary rounded-pill px-4" onclick="sendConnectionRequest(<?= $profile_user['id'] ?>)">
                                    <i class="bi bi-person-plus-fill me-2"></i>Connect
                                </button>
                            <?php elseif ($connection['status'] === 'pending' && $connection['sender_id'] == $current_id): ?>
                                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" disabled>
                                    <i class="bi bi-clock-history me-2"></i>Request Sent
                                </button>
                            <?php elseif ($connection['status'] === 'pending' && $connection['receiver_id'] == $current_id): ?>
                                <button type="button" class="btn btn-success rounded-pill px-3" onclick="respondConnectionRequest(<?= $connection['id'] ?>, 'accepted')">
                                    <i class="bi bi-check-lg me-1"></i>Accept
                                </button>
                                <button type="button" class="btn btn-outline-danger rounded-pill px-3" onclick="respondConnectionRequest(<?= $connection['id'] ?>, 'rejected')">
                                    Decline
                                </button>
                            <?php elseif ($connection['status'] === 'accepted'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fs-6">
                                    <i class="bi bi-check-circle-fill me-1"></i>Connected
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($profile_user['linkedin_url'])): ?>
                                <a href="<?= e($profile_user['linkedin_url']) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary rounded-circle btn-icon" title="View LinkedIn">
                                    <i class="bi bi-linkedin text-primary"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <a href="<?= e(BASE_URL) ?>/auth/login.php" class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-person-plus-fill me-2"></i>Sign In to Connect
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Profile Content Grid -->
    <div class="row g-4">
        <!-- Left Sidebar: About, Skills, Details -->
        <div class="col-lg-4">
            <!-- About Me Card -->
            <div class="card-modern p-4 mb-4">
                <h5 class="fw-bold font-heading mb-3 text-dark">
                    <i class="bi bi-person-lines-fill text-primary me-2"></i>About
                </h5>
                <?php if (!empty($profile_user['bio'])): ?>
                    <p class="text-secondary small mb-0 line-height-relaxed">
                        <?= nl2br(e($profile_user['bio'])) ?>
                    </p>
                <?php else: ?>
                    <p class="text-muted small mb-0 fst-italic">No bio provided yet.</p>
                <?php endif; ?>
            </div>

            <!-- Skills Card -->
            <div class="card-modern p-4 mb-4">
                <h5 class="fw-bold font-heading mb-3 text-dark">
                    <i class="bi bi-stars text-primary me-2"></i>Skills & Expertise
                </h5>
                <?php if (!empty($profile_user['skills'])): 
                    $skills_array = array_map('trim', explode(',', $profile_user['skills']));
                ?>
                    <div>
                        <?php foreach ($skills_array as $skill): if (!empty($skill)): ?>
                            <span class="skill-tag"><?= e($skill) ?></span>
                        <?php endif; endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-0 fst-italic">No skills listed yet.</p>
                <?php endif; ?>
            </div>

            <!-- Profile Summary Card -->
            <div class="card-modern p-4">
                <h5 class="fw-bold font-heading mb-3 text-dark">
                    <i class="bi bi-info-circle text-primary me-2"></i>Information
                </h5>
                <ul class="list-unstyled small mb-0 d-flex flex-column gap-2 text-secondary">
                    <?php if (!empty($profile_user['industry'])): ?>
                        <li>
                            <strong class="text-dark">Industry:</strong> <?= e($profile_user['industry']) ?>
                        </li>
                    <?php endif; ?>
                    <li>
                        <strong class="text-dark">Role:</strong> <span class="text-capitalize"><?= e($profile_user['role']) ?></span>
                    </li>
                    <li>
                        <strong class="text-dark">Member Since:</strong> <?= format_date($profile_user['joined_date']) ?>
                    </li>
                    <?php if (!empty($profile_user['linkedin_url'])): ?>
                        <li class="pt-2 border-top">
                            <a href="<?= e($profile_user['linkedin_url']) ?>" target="_blank" rel="noopener" class="text-decoration-none text-primary fw-semibold">
                                <i class="bi bi-linkedin me-1"></i>LinkedIn Profile <i class="bi bi-box-arrow-up-right small"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Right Content: Activity, Posts, Jobs -->
        <div class="col-lg-8">
            <!-- Recent Community Posts -->
            <div class="card-modern p-4 mb-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h5 class="fw-bold font-heading mb-0 text-dark">
                        <i class="bi bi-chat-square-text text-primary me-2"></i>Recent Activity
                    </h5>
                    <?php if ($is_own_profile): ?>
                        <a href="<?= e(BASE_URL) ?>/feed.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="bi bi-plus-circle me-1"></i>Create Post
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($recent_posts)): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($recent_posts as $post): ?>
                            <div class="p-3 border rounded-3 bg-light-subtle">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-white text-secondary border rounded-pill px-2 py-1 small">
                                        <?= e(ucfirst($post['post_type'])) ?>
                                    </span>
                                    <small class="text-muted"><?= time_ago($post['created_at']) ?></small>
                                </div>
                                <p class="text-dark mb-2 small line-height-relaxed">
                                    <?= nl2br(e($post['content'])) ?>
                                </p>
                                <div class="d-flex align-items-center gap-3 text-secondary small pt-2 border-top">
                                    <span><i class="bi bi-heart text-danger me-1"></i><?= $post['likes_count'] ?> Likes</span>
                                    <span><i class="bi bi-chat me-1"></i><?= $post['comments_count'] ?> Comments</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-chat-square-dots fs-2 opacity-50 mb-2 d-block"></i>
                        <p class="small mb-0">No public posts shared yet.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Opportunities Posted -->
            <?php if (!empty($posted_jobs)): ?>
                <div class="card-modern p-4">
                    <h5 class="fw-bold font-heading mb-3 text-dark">
                        <i class="bi bi-briefcase text-primary me-2"></i>Career Opportunities Shared
                    </h5>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($posted_jobs as $job): ?>
                            <div class="p-3 border rounded-3 d-flex justify-content-between align-items-center bg-white">
                                <div>
                                    <h6 class="fw-bold text-dark mb-1"><?= e($job['title']) ?></h6>
                                    <div class="small text-secondary">
                                        <i class="bi bi-building me-1"></i><?= e($job['company']) ?>
                                        <span class="mx-2">•</span>
                                        <i class="bi bi-geo-alt me-1 text-danger"></i><?= e($job['location']) ?>
                                        <span class="mx-2">•</span>
                                        <span class="badge bg-light text-dark rounded-pill"><?= e(ucfirst($job['job_type'])) ?></span>
                                    </div>
                                </div>
                                <a href="<?= e(BASE_URL) ?>/jobs.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    View
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Simple Connection Request JS Handler for Profile Page -->
<script>
async function sendConnectionRequest(targetId) {
    try {
        const res = await apiFetch(getBaseUrl() + '/api/connections.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'send', target_user_id: targetId })
        });
        const data = await res.json();
        if (data.success) {
            showToast('success', data.message);
            const container = document.getElementById('connection-actions-container');
            if (container) {
                container.innerHTML = '<button class="btn btn-outline-secondary rounded-pill px-4" disabled><i class="bi bi-clock-history me-2"></i>Request Sent</button>';
            }
        } else {
            showToast('danger', data.message || 'Failed to send connection request.');
        }
    } catch (e) {
        showToast('danger', 'Error sending connection request.');
    }
}

async function respondConnectionRequest(connectionId, action) {
    try {
        const res = await apiFetch(getBaseUrl() + '/api/connections.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: action, connection_id: connectionId })
        });
        const data = await res.json();
        if (data.success) {
            showToast('success', data.message);
            location.reload();
        } else {
            showToast('danger', data.message || 'Action failed.');
        }
    } catch (e) {
        showToast('danger', 'Error updating connection.');
    }
}
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
