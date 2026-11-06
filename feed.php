<?php
/**
 * Community Discussion Feed
 * MITS Alumni Portal
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

$user = current_user();
$current_id = $user['id'];

$page_title = 'Community Feed';
$page_description = 'Share updates, celebrate career achievements, post referral openings, and interact with the MITS Connect alumni community.';
$current_page = 'feed';

// Fetch current user's alumni profile details
$user_profile = db_fetch_one($conn, "SELECT * FROM alumni_profiles WHERE user_id = ? LIMIT 1", "i", [$current_id]) ?: [];

// Get connection count for left sidebar
$conn_count_row = db_fetch_one($conn, 
    "SELECT COUNT(*) as total FROM connections 
     WHERE (sender_id = ? OR receiver_id = ?) AND status = 'accepted'",
    "ii",
    [$current_id, $current_id]
);
$connections_count = (int)($conn_count_row['total'] ?? 0);

// Filter by category
$filter = sanitize_input($_GET['filter'] ?? 'all');
$valid_filters = ['all', 'hiring', 'achievement', 'event', 'general'];
if (!in_array($filter, $valid_filters)) {
    $filter = 'all';
}

// Build Posts Query
$posts_sql = "SELECT p.*, 
                     u.id as author_id, u.full_name, u.avatar_url, u.role, u.status as author_status,
                     ap.department, ap.graduation_year, ap.current_company, ap.designation
              FROM posts p
              JOIN users u ON p.user_id = u.id
              LEFT JOIN alumni_profiles ap ON u.id = ap.user_id";

$types = "";
$params = [];

if ($filter !== 'all') {
    $posts_sql .= " WHERE p.post_type = ?";
    $types .= "s";
    $params[] = $filter;
}

$posts_sql .= " ORDER BY p.created_at DESC LIMIT 30";

$posts = db_fetch_all($conn, $posts_sql, $types, $params);

// Map whether current user has liked each post
$my_likes = [];
if (!empty($posts)) {
    $post_ids = array_column($posts, 'id');
    $placeholders = implode(',', array_fill(0, count($post_ids), '?'));
    $like_types = str_repeat('i', count($post_ids)) . 'i';
    $like_params = array_merge($post_ids, [$current_id]);

    $likes_res = db_fetch_all($conn, "SELECT post_id FROM likes WHERE post_id IN ($placeholders) AND user_id = ?", $like_types, $like_params);
    foreach ($likes_res as $lr) {
        $my_likes[$lr['post_id']] = true;
    }
}

// Fetch 3 recommended mentors for right sidebar
$mentors = db_fetch_all($conn, 
    "SELECT u.id, u.full_name, u.avatar_url, p.current_company, p.designation 
     FROM users u 
     JOIN alumni_profiles p ON u.id = p.user_id 
     WHERE p.is_mentor = 1 AND u.id != ? 
     LIMIT 3", 
    "i", 
    [$current_id]
);

// Fetch 3 active jobs for right sidebar
$sidebar_jobs = db_fetch_all($conn, "SELECT id, title, company, location, job_type FROM jobs WHERE status = 'active' ORDER BY created_at DESC LIMIT 3");

include_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row g-4">

        <!-- Left Sidebar: Mini Profile & Navigation Shortcuts -->
        <div class="col-lg-3 d-none d-lg-block">
            <!-- Mini User Card -->
            <div class="card-modern border-0 shadow-sm overflow-hidden mb-4 bg-white text-center">
                <div style="height: 65px; background: var(--brand-gradient);"></div>
                <div class="p-3 pt-0">
                    <img src="<?= e(get_avatar_url($user['avatar_url'] ?? '', $user['full_name'])) ?>" 
                         alt="<?= e($user['full_name']) ?>" 
                         class="rounded-circle border border-3 border-white shadow-sm object-fit-cover mb-2"
                         style="width: 72px; height: 72px; margin-top: -36px;">

                    <h6 class="fw-bold font-heading mb-0 text-dark">
                        <a href="<?= e(BASE_URL) ?>/profile.php" class="text-dark text-decoration-none hover-primary">
                            <?= e($user['full_name']) ?>
                        </a>
                    </h6>
                    <div class="small text-secondary text-truncate mb-2" style="font-size: 0.8rem;">
                        <?php if (!empty($user_profile['designation']) && !empty($user_profile['current_company'])): ?>
                            <?= e($user_profile['designation']) ?> at <?= e($user_profile['current_company']) ?>
                        <?php else: ?>
                            <?= e($user_profile['department'] ?? 'MITS Alumnus') ?>
                        <?php endif; ?>
                    </div>

                    <div class="border-top pt-2 d-flex justify-content-around text-center small">
                        <div>
                            <div class="fw-bold text-dark"><?= $connections_count ?></div>
                            <span class="text-secondary" style="font-size: 0.75rem;">Connections</span>
                        </div>
                        <div>
                            <div class="fw-bold text-dark"><?= !empty($user_profile['graduation_year']) ? $user_profile['graduation_year'] : '—' ?></div>
                            <span class="text-secondary" style="font-size: 0.75rem;">Batch</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Portal Navigation Shortcuts -->
            <div class="card-modern border-0 shadow-sm p-3 bg-white">
                <h6 class="fw-bold text-dark small text-uppercase tracking-wider mb-2">Shortcuts</h6>
                <div class="d-flex flex-column gap-1">
                    <a href="<?= e(BASE_URL) ?>/directory.php" class="nav-link py-2 px-3 small d-flex align-items-center">
                        <i class="bi bi-people text-primary me-2 fs-6"></i>Alumni Directory
                    </a>
                    <a href="<?= e(BASE_URL) ?>/network.php" class="nav-link py-2 px-3 small d-flex align-items-center">
                        <i class="bi bi-diagram-3 text-primary me-2 fs-6"></i>My Network
                    </a>
                    <a href="<?= e(BASE_URL) ?>/jobs.php" class="nav-link py-2 px-3 small d-flex align-items-center">
                        <i class="bi bi-briefcase text-primary me-2 fs-6"></i>Career Board
                    </a>
                    <a href="<?= e(BASE_URL) ?>/post-job.php" class="nav-link py-2 px-3 small d-flex align-items-center text-danger fw-semibold">
                        <i class="bi bi-plus-circle me-2 fs-6"></i>Share an Opportunity
                    </a>
                </div>
            </div>
        </div>

        <!-- Center Column: Post Composer & Community Stream -->
        <div class="col-lg-6 col-md-12">
            
            <!-- Post Composer Card -->
            <div class="card-modern border-0 shadow-sm p-3 p-md-4 mb-4 bg-white">
                <form id="post-composer-form" enctype="multipart/form-data">
                    <?= csrf_field(); ?>

                    <div class="d-flex gap-3 mb-3">
                        <img src="<?= e(get_avatar_url($user['avatar_url'] ?? '', $user['full_name'])) ?>" 
                             alt="<?= e($user['full_name']) ?>" 
                             class="rounded-circle avatar-md object-fit-cover">
                        
                        <div class="flex-grow-1">
                            <textarea id="post-content-textarea" 
                                      name="content" 
                                      class="form-control border-0 p-0 shadow-none" 
                                      rows="3" 
                                      placeholder="Share an achievement, referral opportunity, or college memory..." 
                                      style="resize: none;" required></textarea>
                        </div>
                    </div>

                    <!-- Image Attachment Preview Box -->
                    <div id="composer-image-preview" class="position-relative d-none mb-3">
                        <img id="preview-img" src="" alt="Preview" class="img-fluid rounded-3 border w-100" style="max-height: 280px; object-fit: cover;">
                        <button type="button" id="remove-image-btn" class="btn btn-sm btn-dark rounded-circle position-absolute top-0 end-0 m-2" title="Remove Image">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                        <div class="d-flex align-items-center gap-2">
                            <!-- Image Upload Trigger -->
                            <label for="post-image-input" class="btn btn-sm btn-light text-primary rounded-pill px-3 mb-0" style="cursor: pointer;">
                                <i class="bi bi-image me-1"></i>Photo
                            </label>
                            <input type="file" id="post-image-input" name="image" class="d-none" accept="image/png, image/jpeg, image/webp, image/gif">

                            <!-- Post Category Selector -->
                            <select name="post_type" class="form-select form-select-sm rounded-pill border-0 bg-light-subtle text-secondary fw-semibold ps-3 pe-4" style="width: auto;">
                                <option value="general">💬 General</option>
                                <option value="hiring">💼 Hiring Referral</option>
                                <option value="achievement">🏆 Achievement</option>
                                <option value="event">📅 College Event</option>
                            </select>
                        </div>

                        <!-- Publish Button -->
                        <button type="submit" id="publish-post-btn" class="btn btn-primary rounded-pill px-4 btn-sm shadow-sm">
                            <i class="bi bi-send-fill me-1"></i>Publish
                        </button>
                    </div>
                </form>
            </div>

            <!-- Feed Filter Tabs -->
            <div class="d-flex gap-2 mb-4 overflow-x-auto pb-1" style="scrollbar-width: none;">
                <a href="<?= e(BASE_URL) ?>/feed.php?filter=all" class="btn btn-sm rounded-pill px-3 fw-semibold <?= ($filter === 'all') ? 'btn-primary' : 'btn-white border text-secondary' ?>">
                    All Posts
                </a>
                <a href="<?= e(BASE_URL) ?>/feed.php?filter=hiring" class="btn btn-sm rounded-pill px-3 fw-semibold <?= ($filter === 'hiring') ? 'btn-primary' : 'btn-white border text-secondary' ?>">
                    <i class="bi bi-briefcase me-1 text-success"></i>Hiring
                </a>
                <a href="<?= e(BASE_URL) ?>/feed.php?filter=achievement" class="btn btn-sm rounded-pill px-3 fw-semibold <?= ($filter === 'achievement') ? 'btn-primary' : 'btn-white border text-secondary' ?>">
                    <i class="bi bi-trophy me-1 text-warning"></i>Achievements
                </a>
                <a href="<?= e(BASE_URL) ?>/feed.php?filter=event" class="btn btn-sm rounded-pill px-3 fw-semibold <?= ($filter === 'event') ? 'btn-primary' : 'btn-white border text-secondary' ?>">
                    <i class="bi bi-calendar-event me-1 text-info"></i>Events
                </a>
                <a href="<?= e(BASE_URL) ?>/feed.php?filter=general" class="btn btn-sm rounded-pill px-3 fw-semibold <?= ($filter === 'general') ? 'btn-primary' : 'btn-white border text-secondary' ?>">
                    General
                </a>
            </div>

            <!-- Stream of Posts -->
            <div id="posts-stream" class="d-flex flex-column gap-4">
                <?php if (!empty($posts)): ?>
                    <?php foreach ($posts as $p): 
                        $is_author = ($p['author_id'] == $current_id);
                        $can_delete = $is_author || is_admin();
                        $has_liked = !empty($my_likes[$p['id']]);
                    ?>
                        <div class="card-modern border-0 shadow-sm p-4 bg-white" id="post-card-<?= $p['id'] ?>">
                            <!-- Post Author Header -->
                            <div class="d-flex align-items-start justify-content-between mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <a href="<?= e(BASE_URL) ?>/profile.php?id=<?= $p['author_id'] ?>">
                                        <img src="<?= e(get_avatar_url($p['avatar_url'] ?? '', $p['full_name'])) ?>" 
                                             alt="<?= e($p['full_name']) ?>" 
                                             class="rounded-circle avatar-md object-fit-cover shadow-sm">
                                    </a>
                                    <div>
                                        <div class="d-flex align-items-center gap-1">
                                            <h6 class="fw-bold font-heading mb-0 text-dark">
                                                <a href="<?= e(BASE_URL) ?>/profile.php?id=<?= $p['author_id'] ?>" class="text-dark text-decoration-none hover-primary">
                                                    <?= e($p['full_name']) ?>
                                                </a>
                                            </h6>
                                            <?php if ($p['author_status'] === 'verified'): ?>
                                                <i class="bi bi-patch-check-fill text-success small" title="Verified Alumni"></i>
                                            <?php endif; ?>
                                        </div>

                                        <div class="small text-secondary line-height-tight" style="font-size: 0.775rem;">
                                            <?php if (!empty($p['designation']) && !empty($p['current_company'])): ?>
                                                <?= e($p['designation']) ?> • <span class="text-dark fw-semibold"><?= e($p['current_company']) ?></span>
                                            <?php elseif (!empty($p['department'])): ?>
                                                <?= e($p['department']) ?> <?= !empty($p['graduation_year']) ? "('{$p['graduation_year']})" : '' ?>
                                            <?php else: ?>
                                                MITS Alumni
                                            <?php endif; ?>
                                        </div>

                                        <div class="text-muted small" style="font-size: 0.725rem;">
                                            <?= time_ago($p['created_at']) ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <!-- Post Category Badge -->
                                    <?php if ($p['post_type'] === 'hiring'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 small">
                                            <i class="bi bi-briefcase-fill me-1"></i>Hiring
                                        </span>
                                    <?php elseif ($p['post_type'] === 'achievement'): ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1 small">
                                            <i class="bi bi-trophy-fill me-1"></i>Achievement
                                        </span>
                                    <?php elseif ($p['post_type'] === 'event'): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2 py-1 small">
                                            <i class="bi bi-calendar-event me-1"></i>Event
                                        </span>
                                    <?php endif; ?>

                                    <!-- Post Options Dropdown -->
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light rounded-circle btn-icon shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical text-secondary"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end border shadow-sm py-1">
                                            <li>
                                                <button class="dropdown-item small" type="button" onclick="copyPostLink(<?= $p['id'] ?>)">
                                                    <i class="bi bi-link-45deg me-2 text-primary"></i>Copy Link
                                                </button>
                                            </li>
                                            <?php if ($can_delete): ?>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <button class="dropdown-item small text-danger" type="button" onclick="deletePost(<?= $p['id'] ?>)">
                                                        <i class="bi bi-trash me-2"></i>Delete Post
                                                    </button>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Post Content Body -->
                            <div class="post-content text-dark mb-3 line-height-relaxed">
                                <?= nl2br(e($p['content'])) ?>
                            </div>

                            <!-- Optional Media Attachment -->
                            <?php if (!empty($p['image_url'])): ?>
                                <div class="post-media mb-3 rounded-3 overflow-hidden border">
                                    <img src="<?= e($p['image_url']) ?>" alt="Post Attachment" class="img-fluid w-100 object-fit-cover" style="max-height: 440px;">
                                </div>
                            <?php endif; ?>

                            <!-- Actions Row (Like, Comment, Share) -->
                            <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                <!-- Like Button -->
                                <button type="button" 
                                        class="btn btn-sm btn-light border-0 rounded-pill px-3 d-flex align-items-center <?= $has_liked ? 'active-liked' : '' ?>" 
                                        onclick="toggleLike(<?= $p['id'] ?>, this)">
                                    <i class="bi <?= $has_liked ? 'bi-heart-fill text-danger' : 'bi-heart' ?> me-1 like-icon"></i>
                                    <span class="like-count fw-semibold"><?= $p['likes_count'] ?></span>
                                    <span class="d-none d-sm-inline ms-1">Likes</span>
                                </button>

                                <!-- Comment Button -->
                                <button type="button" 
                                        class="btn btn-sm btn-light border-0 rounded-pill px-3 d-flex align-items-center" 
                                        onclick="toggleComments(<?= $p['id'] ?>)">
                                    <i class="bi bi-chat me-1 text-primary"></i>
                                    <span class="post-comments-count-<?= $p['id'] ?> fw-semibold"><?= $p['comments_count'] ?></span>
                                    <span class="d-none d-sm-inline ms-1">Comments</span>
                                </button>

                                <!-- Share Button -->
                                <button type="button" 
                                        class="btn btn-sm btn-light border-0 rounded-pill px-3" 
                                        onclick="copyPostLink(<?= $p['id'] ?>)" title="Share link">
                                    <i class="bi bi-share me-1 text-secondary"></i>
                                    <span class="d-none d-sm-inline">Share</span>
                                </button>
                            </div>

                            <!-- Expandable Comments Drawer -->
                            <div id="comments-section-<?= $p['id'] ?>" class="comments-section d-none border-top mt-3 pt-3">
                                <!-- Add Comment Input Box -->
                                <form onsubmit="event.preventDefault(); submitComment(<?= $p['id'] ?>, this);" class="d-flex gap-2 mb-3">
                                    <img src="<?= e(get_avatar_url($user['avatar_url'] ?? '', $user['full_name'])) ?>" 
                                         alt="<?= e($user['full_name']) ?>" 
                                         class="rounded-circle avatar-sm object-fit-cover mt-1">
                                    
                                    <div class="input-group">
                                        <input type="text" name="content" class="form-control rounded-start-pill border shadow-none" placeholder="Write a comment..." required>
                                        <button type="submit" class="btn btn-primary rounded-end-pill px-3">
                                            <i class="bi bi-send-fill"></i>
                                        </button>
                                    </div>
                                </form>

                                <!-- Loaded Comments Container -->
                                <div id="comments-list-<?= $p['id'] ?>"></div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card-modern border-0 shadow-sm p-5 text-center bg-white">
                        <div class="brand-icon-box mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem; opacity: 0.85;">
                            <i class="bi bi-chat-heart"></i>
                        </div>
                        <h4 class="fw-bold font-heading mb-1 text-dark">No Posts in this Feed</h4>
                        <p class="text-secondary small mb-3">Be the first to share an update, job opening, or college memory!</p>
                        <div>
                            <button type="button" onclick="document.getElementById('post-content-textarea').focus()" class="btn btn-primary rounded-pill px-4">
                                <i class="bi bi-plus-circle me-1"></i>Write First Post
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Right Sidebar: Widgets (Mentors, Jobs, Highlights) -->
        <div class="col-lg-3 d-none d-lg-block">
            <!-- Mentors Widget -->
            <?php if (!empty($mentors)): ?>
                <div class="card-modern border-0 shadow-sm p-3 mb-4 bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <h6 class="fw-bold text-dark mb-0 small">
                            <i class="bi bi-award-fill text-danger me-1"></i>Top Mentors
                        </h6>
                        <a href="<?= e(BASE_URL) ?>/directory.php?mentor=1" class="small text-danger text-decoration-none fw-semibold">View all</a>
                    </div>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($mentors as $m): ?>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= e(get_avatar_url($m['avatar_url'] ?? '', $m['full_name'])) ?>" 
                                     alt="<?= e($m['full_name']) ?>" 
                                     class="rounded-circle avatar-sm object-fit-cover">
                                <div class="overflow-hidden flex-grow-1">
                                    <div class="small fw-bold text-dark text-truncate">
                                        <a href="<?= e(BASE_URL) ?>/profile.php?id=<?= $m['id'] ?>" class="text-dark text-decoration-none hover-primary">
                                            <?= e($m['full_name']) ?>
                                        </a>
                                    </div>
                                    <div class="text-muted text-truncate" style="font-size: 0.725rem;">
                                        <?= e($m['current_company'] ?? 'MITS Mentor') ?>
                                    </div>
                                </div>
                                <a href="<?= e(BASE_URL) ?>/profile.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0" style="font-size: 0.75rem;">
                                    View
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Latest Jobs Widget -->
            <?php if (!empty($sidebar_jobs)): ?>
                <div class="card-modern border-0 shadow-sm p-3 mb-4 bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <h6 class="fw-bold text-dark mb-0 small">
                            <i class="bi bi-briefcase-fill text-primary me-1"></i>Recent Openings
                        </h6>
                        <a href="<?= e(BASE_URL) ?>/jobs.php" class="small text-danger text-decoration-none fw-semibold">Browse</a>
                    </div>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($sidebar_jobs as $sj): ?>
                            <div class="border-bottom pb-2">
                                <h6 class="fw-bold mb-1" style="font-size: 0.85rem;">
                                    <a href="<?= e(BASE_URL) ?>/jobs.php?id=<?= $sj['id'] ?>" class="text-dark text-decoration-none hover-primary">
                                        <?= e($sj['title']) ?>
                                    </a>
                                </h6>
                                <div class="text-secondary small d-flex justify-content-between" style="font-size: 0.75rem;">
                                    <span><?= e($sj['company']) ?></span>
                                    <span class="badge bg-light text-dark rounded-pill"><?= e($sj['location']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- College Highlights -->
            <div class="card-modern border-0 shadow-sm p-3 bg-white">
                <h6 class="fw-bold text-dark mb-2 small">MITS Campus Connect</h6>
                <p class="text-secondary small mb-2" style="font-size: 0.775rem;">
                    Annual Alumni Meet 2026 registration will open shortly. Stay tuned to the community feed for schedule announcements!
                </p>
                <a href="https://mits.ac.in" target="_blank" rel="noopener" class="small text-danger text-decoration-none fw-semibold">
                    Visit Official College Portal <i class="bi bi-box-arrow-up-right ms-1"></i>
                </a>
            </div>
        </div>

    </div>
</div>

<!-- Load Feed Interactions JS -->
<script src="<?= e(BASE_URL) ?>/assets/js/feed.js?v=<?= APP_VERSION ?>"></script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
