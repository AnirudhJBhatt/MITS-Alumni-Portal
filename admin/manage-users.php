<?php
/**
 * User & Role Management
 * MITS Alumni Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_check.php';

$current_admin = current_user();

// Handle User Status / Role Modifications (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Security token invalid or expired.');
        header('Location: ' . BASE_URL . '/admin/manage-users.php');
        exit;
    }

    $target_id   = (int)($_POST['user_id'] ?? 0);
    $new_status  = sanitize_input($_POST['new_status'] ?? '');
    $new_role    = sanitize_input($_POST['new_role'] ?? '');

    // Prevent admin from modifying their own critical credentials
    if ($target_id === (int)$current_admin['id']) {
        set_flash('warning', 'You cannot alter your own administrative status or role.');
        header('Location: ' . BASE_URL . '/admin/manage-users.php');
        exit;
    }

    if (!empty($new_status)) {
        $valid_statuses = ['verified', 'pending', 'rejected', 'suspended'];
        if (in_array($new_status, $valid_statuses)) {
            db_execute($conn, "UPDATE users SET status = ? WHERE id = ?", "si", [$new_status, $target_id]);
            
            // If newly verified, dispatch congratulatory in-app notice and email
            if ($new_status === 'verified') {
                $target_user = db_fetch_one($conn, "SELECT id, full_name, email FROM users WHERE id = ? LIMIT 1", "i", [$target_id]);
                if ($target_user) {
                    $msg = 'Congratulations ' . $target_user['full_name'] . '! Your alumni credentials have been verified by the MITS administration.';
                    db_execute($conn,
                        "INSERT INTO notifications (user_id, actor_id, type, message) VALUES (?, ?, 'admin_verification', ?)",
                        "iis",
                        [$target_id, $current_admin['id'], $msg]
                    );
                    send_alumni_verified_email($target_user['email'], $target_user['full_name']);
                }
            }

            set_flash('success', 'User status updated to ' . ucfirst($new_status));
        }
    }

    if (!empty($new_role)) {
        $valid_roles = ['alumni', 'student', 'admin'];
        if (in_array($new_role, $valid_roles)) {
            db_execute($conn, "UPDATE users SET role = ? WHERE id = ?", "si", [$new_role, $target_id]);
            set_flash('success', 'User role updated to ' . ucfirst($new_role));
        }
    }

    header('Location: ' . BASE_URL . '/admin/manage-users.php');
    exit;
}

$page_title = 'Manage Users';
$current_page = 'admin';

// Read Filters
$q      = sanitize_input($_GET['q'] ?? '');
$role   = sanitize_input($_GET['role'] ?? 'all');
$status = sanitize_input($_GET['status'] ?? 'all');

$sql = "SELECT u.*, 
               p.roll_number, p.department, p.graduation_year, p.current_company, p.designation
        FROM users u
        LEFT JOIN alumni_profiles p ON u.id = p.user_id
        WHERE 1=1";

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

if ($role !== 'all' && in_array($role, ['alumni', 'student', 'admin'])) {
    $sql .= " AND u.role = ?";
    $types .= "s";
    $params[] = $role;
}

if ($status !== 'all' && in_array($status, ['verified', 'pending', 'rejected', 'suspended'])) {
    $sql .= " AND u.status = ?";
    $types .= "s";
    $params[] = $status;
}

$sql .= " ORDER BY u.created_at DESC";

$user_list = db_fetch_all($conn, $sql, $types, $params);

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center border-bottom pb-3 mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-danger text-white rounded-pill px-3 py-1">
                    <i class="bi bi-people-fill me-1"></i>Directory Administration
                </span>
                <span class="text-secondary small">User Directory</span>
            </div>
            <h2 class="fw-bold font-heading mb-0 text-dark">User & Role Management</h2>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= e(BASE_URL) ?>/admin/index.php" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i>Dashboard Overview
            </a>
            <a href="<?= e(BASE_URL) ?>/admin/verify-alumni.php" class="btn btn-primary rounded-pill px-3 shadow-sm">
                <i class="bi bi-patch-check me-1"></i>Verification Queue
            </a>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card-modern border-0 shadow-sm p-4 mb-4 bg-white">
        <form action="<?= e(BASE_URL) ?>/admin/manage-users.php" method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-semibold small text-secondary">Search Users</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control border-start-0 rounded-end-3" placeholder="Name, Email, or Roll Number..." value="<?= e($q) ?>">
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold small text-secondary">User Role</label>
                <select name="role" class="form-select rounded-3">
                    <option value="all" <?= ($role === 'all') ? 'selected' : '' ?>>All Roles</option>
                    <option value="alumni" <?= ($role === 'alumni') ? 'selected' : '' ?>>Alumni</option>
                    <option value="student" <?= ($role === 'student') ? 'selected' : '' ?>>Student</option>
                    <option value="admin" <?= ($role === 'admin') ? 'selected' : '' ?>>Administrator</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold small text-secondary">Account Status</label>
                <select name="status" class="form-select rounded-3">
                    <option value="all" <?= ($status === 'all') ? 'selected' : '' ?>>All Statuses</option>
                    <option value="verified" <?= ($status === 'verified') ? 'selected' : '' ?>>Verified</option>
                    <option value="pending" <?= ($status === 'pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="rejected" <?= ($status === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                    <option value="suspended" <?= ($status === 'suspended') ? 'selected' : '' ?>>Suspended</option>
                </select>
            </div>

            <div class="col-md-1 d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-3 w-100" title="Apply Filter">
                    <i class="bi bi-filter"></i>
                </button>
                <?php if (!empty($q) || $role !== 'all' || $status !== 'all'): ?>
                    <a href="<?= e(BASE_URL) ?>/admin/manage-users.php" class="btn btn-outline-secondary rounded-3" title="Clear">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Users Table Card -->
    <div class="card-modern border-0 shadow-sm p-4 bg-white">
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
            <h5 class="fw-bold font-heading mb-0 text-dark">
                Registered Members (<?= count($user_list) ?>)
            </h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>User</th>
                        <th>Roll Number</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Department</th>
                        <th>Joined Date</th>
                        <th class="text-end">Administrative Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($user_list)): ?>
                        <?php foreach ($user_list as $u): 
                            $is_current = ($u['id'] == $current_admin['id']);
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= e(get_avatar_url($u['avatar_url'] ?? '', $u['full_name'])) ?>" 
                                             alt="<?= e($u['full_name']) ?>" 
                                             class="rounded-circle avatar-sm object-fit-cover shadow-sm">
                                        <div>
                                            <div class="fw-bold text-dark mb-0">
                                                <a href="<?= e(BASE_URL) ?>/profile.php?id=<?= $u['id'] ?>" target="_blank" class="text-dark text-decoration-none hover-primary">
                                                    <?= e($u['full_name']) ?>
                                                </a>
                                                <?php if ($is_current): ?>
                                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-0" style="font-size: 0.65rem;">You</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted small" style="font-size: 0.775rem;"><?= e($u['email']) ?></div>
                                        </div>
                                    </div>
                                </td>

                                <td class="font-monospace small">
                                    <?= e($u['roll_number'] ?? '—') ?>
                                </td>

                                <td>
                                    <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-light text-dark border' ?> rounded-pill px-2 py-1 text-capitalize">
                                        <?= e($u['role']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= get_status_badge($u['status']) ?>
                                </td>

                                <td class="small text-secondary">
                                    <?= e($u['department'] ?? '—') ?>
                                </td>

                                <td class="small text-muted">
                                    <?= format_date($u['created_at']) ?>
                                </td>

                                <td class="text-end">
                                    <?php if (!$is_current): ?>
                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-sm btn-light border rounded-pill px-3 dropdown-toggle shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Manage
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end border shadow-sm py-1">
                                                <!-- Status Actions -->
                                                <li class="dropdown-header small text-uppercase">Change Status</li>
                                                <?php if ($u['status'] !== 'verified'): ?>
                                                    <li>
                                                        <form action="<?= e(BASE_URL) ?>/admin/manage-users.php" method="POST">
                                                            <?= csrf_field(); ?>
                                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                            <input type="hidden" name="new_status" value="verified">
                                                            <button type="submit" class="dropdown-item small text-success">
                                                                <i class="bi bi-check-circle me-2"></i>Verify User
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if ($u['status'] !== 'suspended'): ?>
                                                    <li>
                                                        <form action="<?= e(BASE_URL) ?>/admin/manage-users.php" method="POST" onsubmit="return confirm('Suspend user account for <?= e(addslashes($u['full_name'])) ?>?');">
                                                            <?= csrf_field(); ?>
                                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                            <input type="hidden" name="new_status" value="suspended">
                                                            <button type="submit" class="dropdown-item small text-danger">
                                                                <i class="bi bi-slash-circle me-2"></i>Suspend Account
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php else: ?>
                                                    <li>
                                                        <form action="<?= e(BASE_URL) ?>/admin/manage-users.php" method="POST">
                                                            <?= csrf_field(); ?>
                                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                            <input type="hidden" name="new_status" value="verified">
                                                            <button type="submit" class="dropdown-item small text-success">
                                                                <i class="bi bi-unlock me-2"></i>Unsuspend Account
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>

                                                <!-- Role Actions -->
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li class="dropdown-header small text-uppercase">Assign Role</li>
                                                <?php if ($u['role'] !== 'admin'): ?>
                                                    <li>
                                                        <form action="<?= e(BASE_URL) ?>/admin/manage-users.php" method="POST" onsubmit="return confirm('Grant administrative privileges to <?= e(addslashes($u['full_name'])) ?>?');">
                                                            <?= csrf_field(); ?>
                                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                            <input type="hidden" name="new_role" value="admin">
                                                            <button type="submit" class="dropdown-item small text-primary fw-semibold">
                                                                <i class="bi bi-shield-plus me-2"></i>Promote to Admin
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if ($u['role'] === 'admin'): ?>
                                                    <li>
                                                        <form action="<?= e(BASE_URL) ?>/admin/manage-users.php" method="POST" onsubmit="return confirm('Demote <?= e(addslashes($u['full_name'])) ?> to Alumni role?');">
                                                            <?= csrf_field(); ?>
                                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                            <input type="hidden" name="new_role" value="alumni">
                                                            <button type="submit" class="dropdown-item small text-secondary">
                                                                <i class="bi bi-person me-2"></i>Set as Alumni
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border rounded-pill px-3 py-2 small">Super Admin</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
