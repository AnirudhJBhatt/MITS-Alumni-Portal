<?php
/**
 * Job Listings Moderation
 * MITS Alumni Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_check.php';

// Handle Actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Security token invalid or expired.');
        header('Location: ' . BASE_URL . '/admin/manage-jobs.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $job_id = (int)($_POST['job_id'] ?? 0);

    if ($action === 'toggle_status') {
        $job = db_fetch_one($conn, "SELECT status FROM jobs WHERE id = ? LIMIT 1", "i", [$job_id]);
        if ($job) {
            $new_status = ($job['status'] === 'active') ? 'closed' : 'active';
            db_execute($conn, "UPDATE jobs SET status = ? WHERE id = ?", "si", [$new_status, $job_id]);
            set_flash('success', 'Job status changed to ' . ucfirst($new_status));
        }
    }

    if ($action === 'delete') {
        db_execute($conn, "DELETE FROM jobs WHERE id = ?", "i", [$job_id]);
        set_flash('success', 'Job listing removed by administrator.');
    }

    header('Location: ' . BASE_URL . '/admin/manage-jobs.php');
    exit;
}

$page_title = 'Moderate Job Listings';
$current_page = 'admin';

// Fetch all jobs
$jobs = db_fetch_all($conn,
    "SELECT j.*, u.full_name as poster_name, u.email as poster_email 
     FROM jobs j 
     JOIN users u ON j.posted_by = u.id 
     ORDER BY j.created_at DESC"
);

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center border-bottom pb-3 mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-danger text-white rounded-pill px-3 py-1">
                    <i class="bi bi-briefcase-fill me-1"></i>Career Moderation
                </span>
                <span class="text-secondary small">Job Board Oversight</span>
            </div>
            <h2 class="fw-bold font-heading mb-0 text-dark">Moderate Job Listings</h2>
        </div>

        <a href="<?= e(BASE_URL) ?>/admin/index.php" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i>Dashboard Overview
        </a>
    </div>

    <!-- Jobs Table Card -->
    <div class="card-modern border-0 shadow-sm p-4 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th>Location</th>
                        <th>Type</th>
                        <th>Posted By</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($jobs)): ?>
                        <?php foreach ($jobs as $j): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark mb-0">
                                        <a href="<?= e(BASE_URL) ?>/jobs.php?id=<?= $j['id'] ?>" target="_blank" class="text-dark text-decoration-none hover-primary small">
                                            <?= e($j['title']) ?> <i class="bi bi-box-arrow-up-right small text-muted"></i>
                                        </a>
                                    </div>
                                    <small class="text-muted"><?= e($j['experience_level']) ?></small>
                                </td>

                                <td class="small fw-semibold text-dark"><?= e($j['company']) ?></td>
                                <td class="small text-secondary"><?= e($j['location']) ?></td>

                                <td>
                                    <span class="badge bg-light text-dark border rounded-pill px-2 py-1 text-capitalize small">
                                        <?= e($j['job_type']) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="small fw-semibold text-dark"><?= e($j['poster_name']) ?></div>
                                    <div class="text-muted" style="font-size: 0.725rem;"><?= e($j['poster_email']) ?></div>
                                </td>

                                <td>
                                    <span class="badge <?= $j['status'] === 'active' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border' ?> rounded-pill px-2 py-1 text-capitalize small">
                                        <?= e($j['status']) ?>
                                    </span>
                                </td>

                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <form action="<?= e(BASE_URL) ?>/admin/manage-jobs.php" method="POST">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="job_id" value="<?= $j['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-light border rounded-pill px-3" title="Toggle Active / Closed">
                                                <?= $j['status'] === 'active' ? 'Close' : 'Reopen' ?>
                                            </button>
                                        </form>

                                        <form action="<?= e(BASE_URL) ?>/admin/manage-jobs.php" method="POST" onsubmit="return confirm('Permanently delete this job opportunity?');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="job_id" value="<?= $j['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted small">No job postings recorded.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
