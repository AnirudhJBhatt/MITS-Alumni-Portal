<?php
/**
 * Post a Job or Career Opportunity
 * MITS Alumni Portal
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

$user = current_user();
$current_id = $user['id'];
$errors = [];

// Pre-fill company if available in alumni profile
$profile = db_fetch_one($conn, "SELECT current_company FROM alumni_profiles WHERE user_id = ? LIMIT 1", "i", [$current_id]);
$default_company = $profile['current_company'] ?? '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF
    if (!verify_csrf_token()) {
        $errors[] = 'Security token invalid or expired. Please reload and try again.';
    }

    // 2. Sanitize Inputs
    $title            = sanitize_input($_POST['title'] ?? '');
    $company          = sanitize_input($_POST['company'] ?? '');
    $location         = sanitize_input($_POST['location'] ?? '');
    $job_type         = sanitize_input($_POST['job_type'] ?? 'full-time');
    $experience_level = sanitize_input($_POST['experience_level'] ?? '');
    $apply_link       = sanitize_input($_POST['apply_link'] ?? '');
    $contact_email    = sanitize_input($_POST['contact_email'] ?? '');
    $deadline         = !empty($_POST['deadline']) ? sanitize_input($_POST['deadline']) : null;
    $description      = sanitize_input($_POST['description'] ?? '');
    $requirements     = sanitize_input($_POST['requirements'] ?? '');

    // 3. Validation
    if (empty($title)) {
        $errors[] = 'Job Title is required.';
    }
    if (empty($company)) {
        $errors[] = 'Hiring Company name is required.';
    }
    if (empty($location)) {
        $errors[] = 'Job Location or "Remote" is required.';
    }
    if (empty($experience_level)) {
        $errors[] = 'Experience level is required.';
    }
    if (empty($description)) {
        $errors[] = 'Job description cannot be empty.';
    }

    // Ensure at least one application method is given
    if (empty($apply_link) && empty($contact_email)) {
        $errors[] = 'Please provide at least an Application URL or Contact Email for candidates to apply.';
    }
    if (!empty($apply_link) && !filter_var($apply_link, FILTER_VALIDATE_URL)) {
        $errors[] = 'Please enter a valid Application URL (e.g. https://...).';
    }
    if (!empty($contact_email) && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid contact email address.';
    }

    $valid_job_types = ['full-time', 'internship', 'remote', 'part-time'];
    if (!in_array($job_type, $valid_job_types)) {
        $job_type = 'full-time';
    }

    // 4. Insert into Database
    if (empty($errors)) {
        $sql = "INSERT INTO jobs 
                (posted_by, title, company, location, job_type, experience_level, description, requirements, apply_link, contact_email, deadline, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";

        $stmt = db_execute($conn, $sql, "issssssssss", [
            $current_id,
            $title,
            $company,
            $location,
            $job_type,
            $experience_level,
            $description,
            $requirements,
            $apply_link,
            $contact_email,
            $deadline
        ]);

        if ($stmt) {
            $new_job_id = mysqli_insert_id($conn);
            set_flash('success', 'Opportunity posted successfully! Thank you for sharing with fellow MITS alumni.');
            header('Location: ' . BASE_URL . '/jobs.php?id=' . $new_job_id);
            exit;
        } else {
            $errors[] = 'Database error while posting job. Please try again.';
        }
    }
}

$page_title = 'Post an Opportunity';
$page_description = 'Share a job vacancy, internship, or referral opportunity with the MITS alumni and student network.';
$current_page = 'post-job';

include_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card-modern border-0 shadow-lg p-4 p-md-5 bg-white">
                
                <!-- Page Header -->
                <div class="border-bottom pb-3 mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                            <i class="bi bi-briefcase-fill me-1"></i>Career Opportunities
                        </span>
                        <a href="<?= e(BASE_URL) ?>/jobs.php" class="btn btn-outline-secondary rounded-pill btn-sm px-3">
                            <i class="bi bi-arrow-left me-1"></i>Back to Jobs
                        </a>
                    </div>
                    <h2 class="fw-bold font-heading mb-1 text-dark">Share an Opportunity</h2>
                    <p class="text-secondary small mb-0">Help fellow graduates, fresh alumni, and final-year students discover exciting roles and company referrals.</p>
                </div>

                <!-- Display Validation Errors -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please resolve the following:</div>
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $error): ?>
                                <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="<?= e(BASE_URL) ?>/post-job.php" method="POST">
                    <?= csrf_field(); ?>

                    <h5 class="fw-bold font-heading text-dark mb-3">Role & Organization</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold small text-secondary">Job Title / Role <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control rounded-3" placeholder="e.g. Software Engineer, Data Analyst, Cloud Intern" value="<?= e($_POST['title'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label fw-semibold small text-secondary">Hiring Company <span class="text-danger">*</span></label>
                            <input type="text" name="company" class="form-control rounded-3" placeholder="e.g. Google, Microsoft, Tech Startup" value="<?= e($_POST['company'] ?? $default_company) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Job Location <span class="text-danger">*</span></label>
                            <input type="text" name="location" class="form-control rounded-3" placeholder="e.g. Bengaluru, Hyderabad, or 'Remote'" value="<?= e($_POST['location'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Employment Type <span class="text-danger">*</span></label>
                            <select name="job_type" class="form-select rounded-3" required>
                                <option value="full-time" <?= (($_POST['job_type'] ?? '') === 'full-time') ? 'selected' : '' ?>>Full-Time</option>
                                <option value="internship" <?= (($_POST['job_type'] ?? '') === 'internship') ? 'selected' : '' ?>>Internship</option>
                                <option value="remote" <?= (($_POST['job_type'] ?? '') === 'remote') ? 'selected' : '' ?>>Remote / Flexible</option>
                                <option value="part-time" <?= (($_POST['job_type'] ?? '') === 'part-time') ? 'selected' : '' ?>>Part-Time</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Experience Level <span class="text-danger">*</span></label>
                            <select name="experience_level" class="form-select rounded-3" required>
                                <option value="">-- Select Experience --</option>
                                <option value="Fresh Graduate (2025/2026 Batch)" <?= (($_POST['experience_level'] ?? '') === 'Fresh Graduate (2025/2026 Batch)') ? 'selected' : '' ?>>Fresh Graduate (2025/2026 Batch)</option>
                                <option value="0 - 2 Years" <?= (($_POST['experience_level'] ?? '') === '0 - 2 Years') ? 'selected' : '' ?>>Entry Level (0 - 2 Years)</option>
                                <option value="2 - 5 Years" <?= (($_POST['experience_level'] ?? '') === '2 - 5 Years') ? 'selected' : '' ?>>Mid Level (2 - 5 Years)</option>
                                <option value="5+ Years" <?= (($_POST['experience_level'] ?? '') === '5+ Years') ? 'selected' : '' ?>>Senior Level (5+ Years)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Application Deadline (Optional)</label>
                            <input type="date" name="deadline" class="form-control rounded-3" min="<?= date('Y-m-d') ?>" value="<?= e($_POST['deadline'] ?? '') ?>">
                        </div>
                    </div>

                    <h5 class="fw-bold font-heading text-dark mb-3">Application Instructions</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Application URL</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-primary"><i class="bi bi-link-45deg"></i></span>
                                <input type="url" name="apply_link" class="form-control rounded-end-3" placeholder="https://careers.company.com/job/..." value="<?= e($_POST['apply_link'] ?? '') ?>">
                            </div>
                            <div class="form-text small">Official careers portal or application form</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Contact / Referral Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-primary"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="contact_email" class="form-control rounded-end-3" placeholder="referrals@company.com" value="<?= e($_POST['contact_email'] ?? '') ?>">
                            </div>
                            <div class="form-text small">Where candidates can submit resumes directly</div>
                        </div>
                    </div>

                    <h5 class="fw-bold font-heading text-dark mb-3">Job Details & Requirements</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Job Description & Responsibilities <span class="text-danger">*</span></label>
                            <textarea name="description" rows="5" class="form-control rounded-3" placeholder="Describe the role, day-to-day responsibilities, and team culture..." required><?= e($_POST['description'] ?? '') ?></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Key Qualifications & Skills Required</label>
                            <textarea name="requirements" rows="3" class="form-control rounded-3" placeholder="e.g. B.Tech in CSE/ECE, proficiency in Python/Java, strong problem-solving skills..."><?= e($_POST['requirements'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="<?= e(BASE_URL) ?>/jobs.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm">
                            <i class="bi bi-check-circle-fill me-2"></i>Publish Opportunity
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
