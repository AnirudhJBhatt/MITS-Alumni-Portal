<?php
/**
 * Sign-In Page (Google Identity Services)
 * MITS Alumni Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$page_title = 'Sign In with Google';
$page_description = 'Sign in to the official MITS Alumni Portal using your Google account.';
$current_page = 'login';

$is_dev_client_id = (GOOGLE_CLIENT_ID === 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com' || empty(GOOGLE_CLIENT_ID));

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center align-items-center min-vh-75">
        <div class="col-md-8 col-lg-5">
            <!-- Login Card -->
            <div class="card-modern border-0 shadow-lg p-4 p-md-5 text-center position-relative">
                
                <!-- Brand Header -->
                <div class="mb-4">
                    <img src="<?= e(BASE_URL) ?>/assets/images/OSAM%20logo.jpg" alt="<?= e(APP_NAME) ?> Logo" class="brand-logo-img mx-auto mb-3 shadow-sm d-block" style="width: 64px; height: 64px; border-radius: 14px;">
                    <h3 class="fw-bold font-heading mb-1 text-dark">Welcome to <span class="text-gradient"><?= e(APP_NAME) ?></span></h3>
                    <p class="text-secondary small mb-0">Sign in with your Google account to connect with alumni, discover career openings, and give back.</p>
                </div>

                <!-- Loading State Indicator -->
                <div id="login-loading" class="d-none my-4">
                    <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;">
                        <span class="visually-hidden">Authenticating...</span>
                    </div>
                    <div class="small text-secondary mt-2 fw-semibold">Authenticating with Google...</div>
                </div>

                <!-- Google Sign-In Actions Container -->
                <div id="login-actions" class="my-4">
                    <!-- Google Identity Services (GIS) Initializer -->
                    <div id="g_id_onload"
                         data-client_id="<?= e(GOOGLE_CLIENT_ID) ?>"
                         data-callback="handleGoogleCredentialResponse"
                         data-auto_prompt="false"
                         <?php if (defined('RESTRICT_TO_COLLEGE_DOMAIN') && RESTRICT_TO_COLLEGE_DOMAIN && !empty(COLLEGE_EMAIL_DOMAINS[0])): ?>
                         data-hd="<?= e(COLLEGE_EMAIL_DOMAINS[0]) ?>"
                         <?php endif; ?>
                         data-context="signin">
                    </div>

                    <!-- GIS Standard Styled Button -->
                    <div class="d-flex justify-content-center mb-3">
                        <div class="g_id_signin"
                             data-type="standard"
                             data-shape="pill"
                             data-theme="outline"
                             data-text="signin_with"
                             data-size="large"
                             data-logo_alignment="left"
                             data-width="320">
                        </div>
                    </div>

                    <div class="small text-muted mb-2">
                        <i class="bi bi-shield-lock-fill text-success me-1"></i>
                        Safe & secure sign-in via Google Identity Services
                    </div>

                    <?php if (defined('BLOCK_COLLEGE_EMAIL_DOMAIN') && BLOCK_COLLEGE_EMAIL_DOMAIN && (!defined('RESTRICT_TO_COLLEGE_DOMAIN') || !RESTRICT_TO_COLLEGE_DOMAIN)): ?>
                        <div class="alert alert-light border small text-secondary mt-3 mb-0 text-start py-2 px-3 rounded-3" style="font-size: 0.8rem;">
                            <i class="bi bi-info-circle text-primary me-1"></i>
                            <strong>Alumni Notice:</strong> Please sign in with your permanent personal Google account (e.g. Gmail). College email IDs (@mgits.ac.in) get deactivated after graduation.
                        </div>
                    <?php elseif (defined('RESTRICT_TO_COLLEGE_DOMAIN') && RESTRICT_TO_COLLEGE_DOMAIN): ?>
                        <div class="alert alert-light border small text-secondary mt-3 mb-0 text-start py-2 px-3 rounded-3" style="font-size: 0.8rem;">
                            <i class="bi bi-shield-check text-success me-1"></i>
                            <strong>Institutional Notice:</strong> Only official college Google accounts (@<?= e(implode(', @', COLLEGE_EMAIL_DOMAINS)) ?>) are permitted to sign in.
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($is_dev_client_id): ?>
                    <!-- Setup Notice for Developer -->
                    <div class="alert alert-warning text-start small border border-warning-subtle rounded-3 p-3 mb-4">
                        <div class="d-flex align-items-center gap-2 fw-bold text-dark mb-1">
                            <i class="bi bi-gear-wide-connected fs-5 text-warning"></i>
                            Google Client ID Setup
                        </div>
                        <p class="mb-2 text-secondary">
                            To enable live Google Sign-In, generate an <strong>OAuth 2.0 Web Client ID</strong> in the Google Cloud Console and paste it into <code class="text-danger">config/config.php</code>.
                        </p>
                        <div class="small text-muted">
                            Authorized JavaScript origin: <code>http://localhost</code><br>
                            Authorized redirect URI: <code>http://localhost/MITS-Alumni-Portal/auth/login.php</code>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (APP_DEBUG): ?>
                    <!-- Local Development Fast Sign-In Simulator -->
                    <div class="border-top pt-4 text-start">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2">Local Dev Fast-Login</span>
                            <small class="text-muted">Instant test switch</small>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="button" onclick="signInAsDemo('kavya.sharma@example.com')" class="btn btn-sm btn-light border text-start d-flex align-items-center justify-content-between py-2 px-3 rounded-3">
                                <div>
                                    <div class="fw-semibold text-dark">Kavya Sharma</div>
                                    <small class="text-muted">Verified Alumna • Google (Batch 2021)</small>
                                </div>
                                <i class="bi bi-arrow-right-short fs-4 text-secondary"></i>
                            </button>
                            <button type="button" onclick="signInAsDemo('osam@mgits.ac.in')" class="btn btn-sm btn-light border text-start d-flex align-items-center justify-content-between py-2 px-3 rounded-3">
                                <div>
                                    <div class="fw-semibold text-danger">OSAM Admin (osam@mgits.ac.in)</div>
                                    <small class="text-muted">Exempt Admin College Email • Verification Dashboard</small>
                                </div>
                                <i class="bi bi-shield-lock-fill fs-5 text-danger"></i>
                            </button>
                            <button type="button" onclick="signInAsDemo('new.alumnus@gmail.com')" class="btn btn-sm btn-light border text-start d-flex align-items-center justify-content-between py-2 px-3 rounded-3">
                                <div>
                                    <div class="fw-semibold text-dark">New Graduate (Unregistered)</div>
                                    <small class="text-muted">Triggers Onboarding & Profile Completion Wizard</small>
                                </div>
                                <i class="bi bi-magic fs-5 text-primary"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Terms and Privacy note -->
                <div class="mt-4 pt-2 small text-muted">
                    By signing in, you agree to the <a href="#" class="text-secondary text-decoration-underline">MITS Connect Guidelines</a> and <a href="#" class="text-secondary text-decoration-underline">Privacy Policy</a>.
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Load Auth Client Script -->
<script src="<?= e(BASE_URL) ?>/assets/js/auth.js?v=<?= APP_VERSION ?>"></script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
