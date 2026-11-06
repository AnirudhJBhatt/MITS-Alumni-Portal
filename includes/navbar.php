<?php
/**
 * Responsive Navigation Bar Component
 * MITS Alumni Portal
 */

require_once __DIR__ . '/functions.php';

$current_page = $current_page ?? basename($_SERVER['PHP_SELF'], '.php');
$logged_in = is_logged_in();
$user = current_user();
?>
<nav class="navbar navbar-expand-lg navbar-glass sticky-top shadow-sm py-2">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(BASE_URL) ?>/index.php">
            <div class="brand-icon-box">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <div>
                <span class="fw-bold tracking-tight text-gradient">MITS Alumni Connect</span>
            </div>
        </a>

        <!-- Mobile Toggler Button -->
        <button class="navbar-toggler border-0 shadow-none p-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list fs-2 text-dark"></i>
        </button>

        <!-- Navbar Menu Items -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link px-3 <?= ($current_page === 'index' && !$logged_in) ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/index.php">
                        <i class="bi bi-house-door me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 <?= ($current_page === 'gallery') ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/gallery.php">
                        <i class="bi bi-images me-1"></i>Gallery
                    </a>
                </li>
                <?php if ($logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3 <?= ($current_page === 'feed') ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/feed.php">
                            <i class="bi bi-collection-play me-1"></i>Feed
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link px-3 <?= ($current_page === 'directory') ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/directory.php">
                        <i class="bi bi-people me-1"></i>Directory
                    </a>
                </li>
                <?php if ($logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3 <?= ($current_page === 'network') ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/network.php">
                            <i class="bi bi-diagram-3 me-1"></i>Network
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link px-3 <?= ($current_page === 'jobs') ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/jobs.php">
                        <i class="bi bi-briefcase me-1"></i>Jobs
                    </a>
                </li>
            </ul>

            <!-- Right-Hand Side (Auth / User Profile / Actions) -->
            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <?php if ($logged_in): ?>
                    <!-- Notification Bell -->
                    <a href="<?= e(BASE_URL) ?>/notifications.php" class="btn btn-icon btn-light rounded-circle position-relative me-1" title="Notifications">
                        <i class="bi bi-bell fs-5 text-secondary"></i>
                        <span id="nav-notif-badge" class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle d-none">
                            <span class="visually-hidden">New notifications</span>
                        </span>
                    </a>

                    <!-- User Profile Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-user-pill dropdown-toggle d-flex align-items-center gap-2 p-1 pe-3 border rounded-pill shadow-none" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?= e(get_avatar_url($user['avatar_url'] ?? '', $user['full_name'] ?? 'User')) ?>" 
                                 alt="<?= e($user['full_name'] ?? 'User') ?>" 
                                 class="rounded-circle object-fit-cover nav-avatar">
                            <div class="text-start d-none d-md-block line-height-tight">
                                <div class="fw-semibold text-truncate user-name-label"><?= e($user['full_name'] ?? 'Alumnus') ?></div>
                                <small class="text-muted text-capitalize user-role-label"><?= e($user['role'] ?? 'alumni') ?></small>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 py-2 user-dropdown-menu" aria-labelledby="userDropdown">
                            <li class="px-3 py-2 border-bottom mb-2 bg-light-subtle rounded-top-4">
                                <div class="fw-bold text-dark"><?= e($user['full_name'] ?? '') ?></div>
                                <div class="small text-muted text-truncate"><?= e($user['email'] ?? '') ?></div>
                                <div class="mt-1"><?= get_status_badge($user['status'] ?? 'pending') ?></div>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= e(BASE_URL) ?>/profile.php">
                                    <i class="bi bi-person-badge me-2 text-primary"></i>My Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= e(BASE_URL) ?>/edit-profile.php">
                                    <i class="bi bi-pencil-square me-2 text-secondary"></i>Edit Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= e(BASE_URL) ?>/post-job.php">
                                    <i class="bi bi-plus-circle me-2 text-success"></i>Post a Job
                                </a>
                            </li>

                            <?php if (is_admin()): ?>
                                <li><hr class="dropdown-divider my-2"></li>
                                <li>
                                    <a class="dropdown-item py-2 text-primary fw-semibold" href="<?= e(BASE_URL) ?>/admin/index.php">
                                        <i class="bi bi-shield-check me-2"></i>Admin Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 text-danger" href="<?= e(BASE_URL) ?>/admin/manage-gallery.php">
                                        <i class="bi bi-images me-2"></i>Manage Gallery
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li><hr class="dropdown-divider my-2"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger" href="<?= e(BASE_URL) ?>/auth/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                                </a>
                            </li>
                        </ul>
                    </div>

                <?php else: ?>
                    <!-- Guest Sign In Actions -->
                    <a href="<?= e(BASE_URL) ?>/auth/login.php" class="btn btn-outline-primary rounded-pill px-4 fw-semibold">
                        <i class="bi bi-google me-2"></i>Sign In
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
