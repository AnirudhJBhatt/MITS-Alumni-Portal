<?php
/**
 * Administrator Dashboard Overview
 * MITS Alumni Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_check.php';

$page_title = 'Admin Dashboard';
$current_page = 'admin';

// 1. Fetch Analytics & Metrics
$alumni_count_row = db_fetch_one($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'alumni'");
$total_alumni = (int)($alumni_count_row['total'] ?? 0);

$pending_count_row = db_fetch_one($conn, "SELECT COUNT(*) as total FROM users WHERE status = 'pending'");
$pending_alumni = (int)($pending_count_row['total'] ?? 0);

$jobs_count_row = db_fetch_one($conn, "SELECT COUNT(*) as total FROM jobs WHERE status = 'active'");
$active_jobs = (int)($jobs_count_row['total'] ?? 0);

$posts_count_row = db_fetch_one($conn, "SELECT COUNT(*) as total FROM posts");
$total_posts = (int)($posts_count_row['total'] ?? 0);

// 2. Fetch Pending Alumni for Quick Review
$pending_sql = "SELECT u.id, u.full_name, u.email, u.avatar_url, u.created_at,
                       p.roll_number, p.department, p.graduation_year, p.current_company
                FROM users u
                LEFT JOIN alumni_profiles p ON u.id = p.user_id
                WHERE u.status = 'pending'
                ORDER BY u.created_at DESC
                LIMIT 5";
$recent_pending = db_fetch_all($conn, $pending_sql);

// 3. Department Distribution Stats
$dept_stats = db_fetch_all($conn, 
    "SELECT department, COUNT(*) as count 
     FROM alumni_profiles 
     WHERE department IS NOT NULL AND department != '' 
     GROUP BY department 
     ORDER BY count DESC 
     LIMIT 5"
);

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Admin Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center border-bottom pb-3 mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-danger text-white rounded-pill px-3 py-1">
                    <i class="bi bi-shield-lock-fill me-1"></i>Administration Portal
                </span>
                <span class="text-secondary small">MITS Management Console</span>
            </div>
            <h2 class="fw-bold font-heading mb-0 text-dark">Institutional Dashboard</h2>
        </div>
        
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= e(BASE_URL) ?>/admin/verify-alumni.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-patch-check me-2"></i>Verification Queue
                <?php if ($pending_alumni > 0): ?>
                    <span class="badge bg-white text-danger rounded-pill ms-2"><?= $pending_alumni ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= e(BASE_URL) ?>/admin/manage-gallery.php" class="btn btn-outline-danger rounded-pill px-3">
                <i class="bi bi-images me-1"></i>Manage Gallery
            </a>
            <a href="<?= e(BASE_URL) ?>/admin/manage-users.php" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-people me-1"></i>Manage Users
            </a>
        </div>
    </div>

    <!-- Metric Cards Grid -->
    <div class="row g-4 mb-4">
        <!-- 1. Registered Alumni -->
        <div class="col-md-6 col-lg-3">
            <div class="card-modern p-4 border bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-secondary small fw-semibold">Registered Alumni</span>
                    <div class="stat-icon bg-primary-subtle text-primary mb-0" style="width: 40px; height: 40px; font-size: 1.15rem;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold font-heading mb-1 text-dark"><?= number_format($total_alumni) ?></h3>
                <small class="text-muted"><i class="bi bi-check2 text-success me-1"></i>Active graduates</small>
            </div>
        </div>

        <!-- 2. Pending Verification Queue -->
        <div class="col-md-6 col-lg-3">
            <div class="card-modern p-4 border bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-secondary small fw-semibold">Pending Verifications</span>
                    <div class="stat-icon bg-warning-subtle text-warning mb-0" style="width: 40px; height: 40px; font-size: 1.15rem;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
                <h3 class="fw-bold font-heading mb-1 text-danger"><?= number_format($pending_alumni) ?></h3>
                <small class="text-muted"><a href="<?= e(BASE_URL) ?>/admin/verify-alumni.php" class="text-decoration-none text-danger fw-semibold">Review queue &rarr;</a></small>
            </div>
        </div>

        <!-- 3. Active Career Opportunities -->
        <div class="col-md-6 col-lg-3">
            <div class="card-modern p-4 border bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-secondary small fw-semibold">Active Job Listings</span>
                    <div class="stat-icon bg-success-subtle text-success mb-0" style="width: 40px; height: 40px; font-size: 1.15rem;">
                        <i class="bi bi-briefcase-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold font-heading mb-1 text-dark"><?= number_format($active_jobs) ?></h3>
                <small class="text-muted"><a href="<?= e(BASE_URL) ?>/admin/manage-jobs.php" class="text-decoration-none text-success fw-semibold">Moderate openings &rarr;</a></small>
            </div>
        </div>

        <!-- 4. Community Feed Posts -->
        <div class="col-md-6 col-lg-3">
            <div class="card-modern p-4 border bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-secondary small fw-semibold">Discussion Posts</span>
                    <div class="stat-icon bg-info-subtle text-info mb-0" style="width: 40px; height: 40px; font-size: 1.15rem;">
                        <i class="bi bi-chat-left-text-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold font-heading mb-1 text-dark"><?= number_format($total_posts) ?></h3>
                <small class="text-muted"><a href="<?= e(BASE_URL) ?>/admin/manage-posts.php" class="text-decoration-none text-info fw-semibold">Moderate feed &rarr;</a></small>
            </div>
        </div>
    </div>

    <!-- Management Tables Grid -->
    <div class="row g-4">
        <!-- Left: Recent Pending Verifications -->
        <div class="col-lg-8">
            <div class="card-modern p-4 border bg-white shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <h5 class="fw-bold font-heading mb-0 text-dark">
                        <i class="bi bi-clock text-warning me-2"></i>Pending Verification Queue
                    </h5>
                    <a href="<?= e(BASE_URL) ?>/admin/verify-alumni.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        View All (<?= $pending_alumni ?>)
                    </a>
                </div>

                <?php if (!empty($recent_pending)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Candidate</th>
                                    <th>Roll Number</th>
                                    <th>Department</th>
                                    <th>Batch</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_pending as $p): ?>
                                    <tr id="admin-row-<?= $p['id'] ?>">
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?= e(get_avatar_url($p['avatar_url'] ?? '', $p['full_name'])) ?>" 
                                                     alt="<?= e($p['full_name']) ?>" 
                                                     class="rounded-circle avatar-sm object-fit-cover">
                                                <div>
                                                    <div class="fw-semibold text-dark small"><?= e($p['full_name']) ?></div>
                                                    <div class="text-muted" style="font-size: 0.725rem;"><?= e($p['email']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                                <?= e($p['roll_number'] ?? 'Pending') ?>
                                            </span>
                                        </td>
                                        <td class="small text-secondary"><?= e($p['department'] ?? 'Not specified') ?></td>
                                        <td class="small fw-semibold"><?= e($p['graduation_year'] ?? '—') ?></td>
                                        <td class="text-end">
                                            <a href="<?= e(BASE_URL) ?>/admin/verify-alumni.php?user_id=<?= $p['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3 py-1">
                                                Verify
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-check-circle fs-2 text-success opacity-75 mb-2 d-block"></i>
                        <p class="small mb-0">No pending verification requests at this moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Department Distribution & Quick Actions -->
        <div class="col-lg-4">
            <!-- Department Distribution -->
            <div class="card-modern p-4 border bg-white shadow-sm mb-4">
                <h5 class="fw-bold font-heading mb-3 text-dark">Top Departments</h5>
                <div class="d-flex flex-column gap-2">
                    <?php if (!empty($dept_stats)): ?>
                        <?php foreach ($dept_stats as $ds): ?>
                            <div class="d-flex justify-content-between align-items-center small p-2 border-bottom">
                                <span class="text-dark fw-medium text-truncate" style="max-width: 200px;"><?= e($ds['department']) ?></span>
                                <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1"><?= $ds['count'] ?> alumni</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-muted small">No department data collected yet.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Admin Navigation -->
            <div class="card-modern p-4 border bg-white shadow-sm">
                <h5 class="fw-bold font-heading mb-3 text-dark">Moderation Tools</h5>
                <div class="d-grid gap-2">
                    <a href="<?= e(BASE_URL) ?>/admin/verify-alumni.php" class="btn btn-light border text-start d-flex justify-content-between align-items-center py-2 px-3 rounded-3">
                        <span class="small fw-semibold"><i class="bi bi-patch-check text-success me-2"></i>Verify Alumni Credentials</span>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </a>
                    <a href="<?= e(BASE_URL) ?>/admin/manage-users.php" class="btn btn-light border text-start d-flex justify-content-between align-items-center py-2 px-3 rounded-3">
                        <span class="small fw-semibold"><i class="bi bi-person-gear text-primary me-2"></i>User & Role Management</span>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </a>
                    <a href="<?= e(BASE_URL) ?>/admin/manage-posts.php" class="btn btn-light border text-start d-flex justify-content-between align-items-center py-2 px-3 rounded-3">
                        <span class="small fw-semibold"><i class="bi bi-chat-square-dots text-info me-2"></i>Content & Post Moderation</span>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </a>
                    <a href="<?= e(BASE_URL) ?>/admin/manage-jobs.php" class="btn btn-light border text-start d-flex justify-content-between align-items-center py-2 px-3 rounded-3">
                        <span class="small fw-semibold"><i class="bi bi-briefcase text-warning me-2"></i>Job Listings Moderation</span>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
