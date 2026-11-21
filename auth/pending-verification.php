<?php
/**
 * Alumni Account Verification Holding Page
 * MITS Alumni Portal - MITS Connect
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// 1. Must be logged in to view verification status
if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$user_id = current_user_id();

// 2. Refresh live user record from database
$live_user = db_fetch_one($conn, "SELECT id, full_name, email, role, status FROM users WHERE id = ? LIMIT 1", "i", [$user_id]);
if ($live_user) {
    $_SESSION['user']['role'] = $live_user['role'];
    $_SESSION['user']['status'] = $live_user['status'];
    $_SESSION['user']['full_name'] = $live_user['full_name'];
    $_SESSION['user']['email'] = $live_user['email'];
}

$user = current_user();

// 3. If user is already verified or an administrator, redirect into the portal immediately!
if (is_admin() || ($user['status'] ?? '') === 'verified') {
    set_flash('success', 'Your account is verified! Welcome to MITS Connect.');
    header('Location: ' . BASE_URL . '/feed.php');
    exit;
}

// 4. Fetch submitted profile details
$profile = db_fetch_one($conn, "SELECT * FROM alumni_profiles WHERE user_id = ? LIMIT 1", "i", [$user_id]) ?: [];

// If profile has not been filled out yet, redirect to completion form
if (empty($profile) || empty($profile['department']) || empty($profile['graduation_year'])) {
    set_flash('info', 'Please submit your academic verification details first.');
    header('Location: ' . BASE_URL . '/auth/complete-profile.php');
    exit;
}

$page_title = 'Account Verification Pending | MITS Connect';
$current_page = 'pending-verification';

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5 my-md-4">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">

            <?php if (($user['status'] ?? '') === 'rejected'): ?>
                <!-- Rejection Notice Card -->
                <div class="card card-modern border-0 shadow-lg p-4 p-md-5 text-center bg-white rounded-4 mb-4">
                    <div class="stat-icon bg-danger-subtle text-danger mx-auto mb-3" style="width: 76px; height: 76px; font-size: 2.2rem; border-radius: 20px;">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>

                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2 fw-semibold fs-6 mb-3 d-inline-block">
                        <i class="bi bi-exclamation-triangle me-1"></i>Verification Unsuccessful
                    </span>

                    <h3 class="fw-bold font-heading text-dark mb-2">Details Could Not Be Verified</h3>
                    <p class="text-secondary small mb-4" style="line-height: 1.7;">
                        The MITS administration reviewed your submitted registration information, but could not match the provided <strong>College Roll Number</strong> or <strong>Department</strong> with the college student archives.
                    </p>

                    <div class="alert alert-light border rounded-3 text-start small mb-4">
                        <div class="fw-semibold text-dark mb-1"><i class="bi bi-info-circle text-primary me-1"></i>What should you do?</div>
                        Please check if there was a typo in your Roll / Registration Number or graduation batch and update your submission.
                    </div>

                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                        <a href="<?= e(BASE_URL) ?>/auth/complete-profile.php" class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-pencil-square me-1"></i>Update Profile Details
                        </a>
                        <a href="<?= e(BASE_URL) ?>/auth/logout.php" class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="bi bi-box-arrow-right me-1"></i>Sign Out
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Pending Verification Holding Card -->
                <div class="card card-modern border-0 shadow-lg p-4 p-md-5 bg-white rounded-4 position-relative overflow-hidden text-center">
                    
                    <!-- Decorative Top Accent Bar -->
                    <div class="position-absolute top-0 start-0 w-100" style="height: 6px; background: linear-gradient(90deg, #f59e0b, #d8151c);"></div>

                    <!-- Glowing Icon -->
                    <div class="stat-icon bg-warning-subtle text-warning mx-auto mb-3 shadow-xs" style="width: 76px; height: 76px; font-size: 2.2rem; border-radius: 22px;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>

                    <!-- Status Pill -->
                    <div class="mb-3">
                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-3 py-2 fw-semibold fs-6">
                            <i class="bi bi-clock-history text-warning me-1"></i>Awaiting College Admin Verification
                        </span>
                    </div>

                    <h2 class="fw-bold font-heading text-dark mb-2">Profile Under Verification</h2>
                    <p class="text-secondary mb-4 pe-md-3 ps-md-3" style="line-height: 1.7;">
                        Thank you for registering, <strong><?= e($user['full_name']) ?></strong>! To protect community privacy and ensure only authentic graduates participate, all alumni registrations are verified against MITS institutional records before full portal access is granted.
                    </p>

                    <!-- Application Summary Card -->
                    <div class="card border rounded-3 p-3 bg-light-subtle text-start mb-4 shadow-none">
                        <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom">
                            <h6 class="fw-bold text-dark font-heading mb-0">Submitted Academic Credentials</h6>
                            <span class="badge bg-white text-secondary border rounded-pill px-2 py-1 small">Submitted for Review</span>
                        </div>

                        <div class="row g-2 small">
                            <div class="col-sm-6">
                                <span class="text-secondary d-block">Full Name</span>
                                <strong class="text-dark"><?= e($user['full_name']) ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-secondary d-block">Registered Email</span>
                                <strong class="text-dark"><?= e($user['email']) ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-secondary d-block">College Roll / Reg No.</span>
                                <strong class="text-danger"><?= e($profile['roll_number'] ?? 'N/A') ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-secondary d-block">Department / Course</span>
                                <strong class="text-dark"><?= e($profile['department'] ?? 'N/A') ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-secondary d-block">Graduation Year</span>
                                <strong class="text-dark">Batch of <?= e($profile['graduation_year'] ?? 'N/A') ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-secondary d-block">Current Company</span>
                                <strong class="text-dark"><?= !empty($profile['current_company']) ? e($profile['current_company']) : 'Not specified' ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Next Steps Timeline -->
                    <div class="bg-white border rounded-3 p-3 text-start small mb-4">
                        <h6 class="fw-bold text-dark mb-2 font-heading"><i class="bi bi-shield-check text-primary me-2"></i>What Happens Next?</h6>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2 text-secondary">
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-1"></i>
                                <span>The MITS administration verifies your Roll Number against the official graduation register.</span>
                            </li>
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi bi-envelope-check-fill text-primary flex-shrink-0 mt-1"></i>
                                <span>An activation confirmation email will be delivered to <strong><?= e($user['email']) ?></strong> as soon as you are approved.</span>
                            </li>
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi bi-unlock-fill text-warning flex-shrink-0 mt-1"></i>
                                <span>Your account will immediately unlock access to the Alumni Directory, Community Feed, and Career Board.</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Action Controls -->
                    <div class="d-flex flex-column flex-sm-row justify-content-center align-items-center gap-3">
                        <a href="<?= e(BASE_URL) ?>/auth/pending-verification.php" class="btn btn-primary rounded-pill px-4 shadow-sm w-100 w-sm-auto">
                            <i class="bi bi-arrow-clockwise me-1"></i>Check Verification Status
                        </a>
                        <a href="<?= e(BASE_URL) ?>/auth/complete-profile.php" class="btn btn-outline-secondary rounded-pill px-4 w-100 w-sm-auto">
                            <i class="bi bi-pencil-square me-1"></i>Edit Submitted Info
                        </a>
                        <a href="<?= e(BASE_URL) ?>/auth/logout.php" class="btn btn-link text-danger text-decoration-none px-3 w-100 w-sm-auto">
                            <i class="bi bi-box-arrow-right me-1"></i>Sign Out
                        </a>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
