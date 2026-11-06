<?php
/**
 * Alumni Verification Queue
 * MITS Alumni Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_check.php';

$current_admin = current_user();

// Handle Verification Action (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Security token invalid or expired.');
        header('Location: ' . BASE_URL . '/admin/verify-alumni.php');
        exit;
    }

    $target_user_id = (int)($_POST['user_id'] ?? 0);
    $action = sanitize_input($_POST['action'] ?? '');

    $target_user = db_fetch_one($conn, "SELECT id, full_name, email FROM users WHERE id = ? LIMIT 1", "i", [$target_user_id]);

    if (!$target_user) {
        set_flash('danger', 'Candidate record not found.');
        header('Location: ' . BASE_URL . '/admin/verify-alumni.php');
        exit;
    }

    if ($action === 'verify') {
        db_execute($conn, "UPDATE users SET status = 'verified' WHERE id = ?", "i", [$target_user_id]);
        
        // Send congratulatory notification to the alumnus
        $msg = 'Congratulations ' . $target_user['full_name'] . '! Your alumni credentials have been verified by the MITS administration.';
        db_execute($conn,
            "INSERT INTO notifications (user_id, actor_id, type, message) VALUES (?, ?, 'admin_verification', ?)",
            "iis",
            [$target_user_id, $current_admin['id'], $msg]
        );

        set_flash('success', 'Candidate ' . e($target_user['full_name']) . ' has been successfully verified!');
    } elseif ($action === 'reject') {
        db_execute($conn, "UPDATE users SET status = 'rejected' WHERE id = ?", "i", [$target_user_id]);

        $msg = 'Your alumni verification request was reviewed and could not be verified against college records. Please update your details.';
        db_execute($conn,
            "INSERT INTO notifications (user_id, actor_id, type, message) VALUES (?, ?, 'admin_verification', ?)",
            "iis",
            [$target_user_id, $current_admin['id'], $msg]
        );

        set_flash('warning', 'Candidate ' . e($target_user['full_name']) . ' was rejected.');
    }

    header('Location: ' . BASE_URL . '/admin/verify-alumni.php');
    exit;
}

$page_title = 'Alumni Verification Queue';
$current_page = 'admin';

// Search & Filter
$q = sanitize_input($_GET['q'] ?? '');
$dept = sanitize_input($_GET['dept'] ?? '');

$sql = "SELECT u.id, u.full_name, u.email, u.avatar_url, u.status, u.created_at,
               p.roll_number, p.department, p.graduation_year, p.current_company, p.designation, p.linkedin_url
        FROM users u
        LEFT JOIN alumni_profiles p ON u.id = p.user_id
        WHERE u.status = 'pending'";

$types = "";
$params = [];

if (!empty($q)) {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR p.roll_number LIKE ?)";
    $like = '%' . $q . '%';
    $types .= "sss";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($dept)) {
    $sql .= " AND p.department = ?";
    $types .= "s";
    $params[] = $dept;
}

$sql .= " ORDER BY u.created_at ASC";

$pending_list = db_fetch_all($conn, $sql, $types, $params);

// Fetch departments for filter
$depts = db_fetch_all($conn, "SELECT DISTINCT department FROM alumni_profiles WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center border-bottom pb-3 mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1">
                    <i class="bi bi-shield-check me-1"></i>Administrative Verification
                </span>
                <span class="text-secondary small">Credentials Review</span>
            </div>
            <h2 class="fw-bold font-heading mb-0 text-dark">Alumni Verification Queue</h2>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= e(BASE_URL) ?>/admin/index.php" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i>Dashboard Overview
            </a>
            <a href="<?= e(BASE_URL) ?>/admin/manage-users.php" class="btn btn-outline-primary rounded-pill px-3">
                <i class="bi bi-people me-1"></i>All Users Directory
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card-modern border-0 shadow-sm p-4 mb-4 bg-white">
        <form action="<?= e(BASE_URL) ?>/admin/verify-alumni.php" method="GET" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label fw-semibold small text-secondary">Search Candidate</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control border-start-0 rounded-end-3" placeholder="Name, Email, or College Roll Number..." value="<?= e($q) ?>">
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold small text-secondary">Department</label>
                <select name="dept" class="form-select rounded-3">
                    <option value="">All Departments</option>
                    <?php foreach ($depts as $d): ?>
                        <option value="<?= e($d['department']) ?>" <?= ($dept === $d['department']) ? 'selected' : '' ?>>
                            <?= e($d['department']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-3 w-100">Filter</button>
                <?php if (!empty($q) || !empty($dept)): ?>
                    <a href="<?= e(BASE_URL) ?>/admin/verify-alumni.php" class="btn btn-outline-secondary rounded-3" title="Clear">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Pending Verification Table Card -->
    <div class="card-modern border-0 shadow-sm p-4 bg-white">
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
            <h5 class="fw-bold font-heading mb-0 text-dark">
                Pending Applications (<?= count($pending_list) ?>)
            </h5>
            <small class="text-secondary">Verify submitted college roll numbers against institutional records.</small>
        </div>

        <?php if (!empty($pending_list)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Alumni Candidate</th>
                            <th>College Roll No</th>
                            <th>Department & Batch</th>
                            <th>Current Employment</th>
                            <th>Applied Date</th>
                            <th class="text-end">Verification Decision</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_list as $candidate): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= e(get_avatar_url($candidate['avatar_url'] ?? '', $candidate['full_name'])) ?>" 
                                             alt="<?= e($candidate['full_name']) ?>" 
                                             class="rounded-circle avatar-md object-fit-cover shadow-sm">
                                        <div>
                                            <div class="fw-bold text-dark mb-0">
                                                <a href="<?= e(BASE_URL) ?>/profile.php?id=<?= $candidate['id'] ?>" target="_blank" class="text-dark text-decoration-none hover-primary">
                                                    <?= e($candidate['full_name']) ?> <i class="bi bi-box-arrow-up-right small text-muted"></i>
                                                </a>
                                            </div>
                                            <div class="text-muted small" style="font-size: 0.775rem;"><?= e($candidate['email']) ?></div>
                                            <?php if (!empty($candidate['linkedin_url'])): ?>
                                                <a href="<?= e($candidate['linkedin_url']) ?>" target="_blank" class="small text-primary text-decoration-none">
                                                    <i class="bi bi-linkedin me-1"></i>LinkedIn Profile
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <?php if (!empty($candidate['roll_number'])): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 font-monospace px-3 py-2 rounded-3">
                                            <?= e($candidate['roll_number']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border px-2 py-1">Profile Incomplete</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="small fw-semibold text-dark"><?= e($candidate['department'] ?? 'Pending') ?></div>
                                    <div class="small text-muted">Class of <?= e($candidate['graduation_year'] ?? '—') ?></div>
                                </td>

                                <td>
                                    <div class="small fw-semibold text-dark"><?= e($candidate['designation'] ?? 'Graduate') ?></div>
                                    <div class="small text-secondary"><?= e($candidate['current_company'] ?? 'Not specified') ?></div>
                                </td>

                                <td class="small text-muted">
                                    <?= format_date($candidate['created_at']) ?>
                                </td>

                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <!-- Approve Form -->
                                        <form action="<?= e(BASE_URL) ?>/admin/verify-alumni.php" method="POST" onsubmit="return confirm('Verify alumni credentials for <?= e(addslashes($candidate['full_name'])) ?>?');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="user_id" value="<?= $candidate['id'] ?>">
                                            <input type="hidden" name="action" value="verify">
                                            <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm">
                                                <i class="bi bi-check-circle me-1"></i>Approve
                                            </button>
                                        </form>

                                        <!-- Reject Form -->
                                        <form action="<?= e(BASE_URL) ?>/admin/verify-alumni.php" method="POST" onsubmit="return confirm('Reject alumni credentials for <?= e(addslashes($candidate['full_name'])) ?>?');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="user_id" value="<?= $candidate['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="brand-icon-box mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem; opacity: 0.85;">
                    <i class="bi bi-check2-all"></i>
                </div>
                <h4 class="fw-bold font-heading mb-1 text-dark">Queue is Empty</h4>
                <p class="text-secondary small mb-3">All registered alumni have been reviewed and verified.</p>
                <a href="<?= e(BASE_URL) ?>/admin/manage-users.php" class="btn btn-outline-primary rounded-pill px-4">
                    View User Directory
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
