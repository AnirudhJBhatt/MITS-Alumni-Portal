<?php
/**
 * Alumni Onboarding & Profile Completion Wizard
 * MITS Alumni Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Must be logged in
if (!is_logged_in()) {
    set_flash('warning', 'Please sign in to complete your profile.');
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$user = current_user();
$user_id = $user['id'];
$errors = [];

// Pre-fetch any existing profile data
$profile = db_fetch_one($conn, "SELECT * FROM alumni_profiles WHERE user_id = ? LIMIT 1", "i", [$user_id]) ?: [];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF
    if (!verify_csrf_token()) {
        $errors[] = 'Security token expired or invalid. Please reload and try again.';
    }

    // 2. Sanitize and Extract Input
    $roll_number     = strtoupper(sanitize_input($_POST['roll_number'] ?? ''));
    $department      = sanitize_input($_POST['department'] ?? '');
    $graduation_year = (int)($_POST['graduation_year'] ?? 0);
    $current_company = sanitize_input($_POST['current_company'] ?? '');
    $designation     = sanitize_input($_POST['designation'] ?? '');
    $industry        = sanitize_input($_POST['industry'] ?? '');
    $location        = sanitize_input($_POST['location'] ?? '');
    $linkedin_url    = sanitize_input($_POST['linkedin_url'] ?? '');
    $bio             = sanitize_input($_POST['bio'] ?? '');
    $skills          = sanitize_input($_POST['skills'] ?? '');
    $is_mentor       = !empty($_POST['is_mentor']) ? 1 : 0;

    // Optional user name update if provided
    $full_name       = sanitize_input($_POST['full_name'] ?? $user['full_name']);

    // 3. Validation
    if (empty($roll_number)) {
        $errors[] = 'College Roll / Registration Number is required for verification.';
    }
    if (empty($department)) {
        $errors[] = 'Please select your engineering department / course.';
    }
    if ($graduation_year < 1998 || $graduation_year > (date('Y') + 5)) {
        $errors[] = 'Please specify a valid graduation year.';
    }

    if (!empty($linkedin_url) && !filter_var($linkedin_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Please enter a valid URL for your LinkedIn profile.';
    }

    // 4. Save to Database
    if (empty($errors)) {
        // Update user name in `users`
        db_execute($conn, "UPDATE users SET full_name = ? WHERE id = ?", "si", [$full_name, $user_id]);
        $_SESSION['user']['full_name'] = $full_name;

        // Upsert `alumni_profiles`
        $sql = "INSERT INTO alumni_profiles 
                (user_id, roll_number, department, graduation_year, current_company, designation, industry, location, linkedin_url, bio, skills, is_mentor)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                roll_number = VALUES(roll_number),
                department = VALUES(department),
                graduation_year = VALUES(graduation_year),
                current_company = VALUES(current_company),
                designation = VALUES(designation),
                industry = VALUES(industry),
                location = VALUES(location),
                linkedin_url = VALUES(linkedin_url),
                bio = VALUES(bio),
                skills = VALUES(skills),
                is_mentor = VALUES(is_mentor)";

        $stmt = db_execute($conn, $sql, "ississsssssi", [
            $user_id,
            $roll_number,
            $department,
            $graduation_year,
            $current_company,
            $designation,
            $industry,
            $location,
            $linkedin_url,
            $bio,
            $skills,
            $is_mentor
        ]);

        if ($stmt) {
            $_SESSION['user']['profile_completed'] = true;
            set_flash('success', 'Profile saved successfully! Welcome to MITS Connect.');
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        } else {
            $errors[] = 'Failed to save profile. Please try again.';
        }
    }
}

$page_title = 'Complete Your Alumni Profile';
$current_page = 'complete-profile';

// List of MITS Departments
$departments = [
    'Computer Science & Engineering',
    'Computer Science & Technology',
    'Artificial Intelligence & Data Science',
    'Computer Science & Engineering (AI)',
    'Computer Science & Engineering (Cyber Security)',
    'Electronics & Communication Engineering',
    'Electrical & Electronics Engineering',
    'Mechanical Engineering',
    'Civil Engineering',
    'Master of Computer Applications (MCA)',
    'Master of Business Administration (MBA)',
    'Other'
];

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card-modern border-0 shadow-lg p-4 p-md-5">

                <!-- Header / Progress -->
                <div class="border-bottom pb-4 mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                            <i class="bi bi-person-check-fill me-1"></i>Alumni Onboarding
                        </span>
                        <small class="text-muted">Step 1 of 1</small>
                    </div>
                    <h2 class="fw-bold font-heading mb-1 text-dark">Complete Your Profile</h2>
                    <p class="text-secondary small mb-0">
                        Help fellow alumni and college administrators identify and connect with you.
                    </p>
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

                <form action="<?= e(BASE_URL) ?>/auth/complete-profile.php" method="POST">
                    <?= csrf_field(); ?>

                    <!-- Account Info Overview -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Full Name</label>
                            <input type="text" name="full_name" class="form-control rounded-3" value="<?= e($_POST['full_name'] ?? $user['full_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Google Email Address</label>
                            <input type="email" class="form-control bg-light rounded-3" value="<?= e($user['email']) ?>" readonly disabled>
                            <div class="form-text small">Linked with your Google Identity</div>
                        </div>
                    </div>

                    <h5 class="fw-bold font-heading text-dark mb-3">Academic Information</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">
                                College Roll Number <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="roll_number" class="form-control rounded-3 text-uppercase" placeholder="e.g. 17691A0589" value="<?= e($_POST['roll_number'] ?? $profile['roll_number'] ?? '') ?>" required>
                            <div class="form-text small">Used for college verification</div>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label fw-semibold small text-secondary">
                                Department / Branch <span class="text-danger">*</span>
                            </label>
                            <select name="department" class="form-select rounded-3" required>
                                <option value="">-- Select Department --</option>
                                <?php 
                                $selected_dept = $_POST['department'] ?? $profile['department'] ?? '';
                                foreach ($departments as $dept): ?>
                                    <option value="<?= e($dept) ?>" <?= ($selected_dept === $dept) ? 'selected' : '' ?>>
                                        <?= e($dept) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">
                                Graduation Year <span class="text-danger">*</span>
                            </label>
                            <select name="graduation_year" class="form-select rounded-3" required>
                                <option value="">-- Batch --</option>
                                <?php 
                                $selected_year = (int)($_POST['graduation_year'] ?? $profile['graduation_year'] ?? date('Y'));
                                for ($yr = date('Y') + 4; $yr >= 1998; $yr--): ?>
                                    <option value="<?= $yr ?>" <?= ($selected_year === $yr) ? 'selected' : '' ?>>
                                        <?= $yr ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <h5 class="fw-bold font-heading text-dark mb-3">Professional Experience</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Current Employer / Organization</label>
                            <input type="text" name="current_company" class="form-control rounded-3" placeholder="e.g. Google, Infosys, Startup, Higher Ed" value="<?= e($_POST['current_company'] ?? $profile['current_company'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Current Designation / Role</label>
                            <input type="text" name="designation" class="form-control rounded-3" placeholder="e.g. Software Engineer, Tech Lead, Analyst" value="<?= e($_POST['designation'] ?? $profile['designation'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Industry / Domain</label>
                            <input type="text" name="industry" class="form-control rounded-3" placeholder="e.g. Information Technology, FinTech, Auto" value="<?= e($_POST['industry'] ?? $profile['industry'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Current Location (City, Country)</label>
                            <input type="text" name="location" class="form-control rounded-3" placeholder="e.g. Bengaluru, India or San Jose, USA" value="<?= e($_POST['location'] ?? $profile['location'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">LinkedIn Profile URL</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-primary border-end-0"><i class="bi bi-linkedin"></i></span>
                                <input type="url" name="linkedin_url" class="form-control rounded-end-3" placeholder="https://www.linkedin.com/in/username" value="<?= e($_POST['linkedin_url'] ?? $profile['linkedin_url'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <h5 class="fw-bold font-heading text-dark mb-3">About & Skills</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Brief Bio / Professional Summary</label>
                            <textarea name="bio" rows="3" class="form-control rounded-3" placeholder="Tell fellow alumni about your professional focus, interests, and college memories..."><?= e($_POST['bio'] ?? $profile['bio'] ?? '') ?></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Skills & Expertise (Comma-separated)</label>
                            <input type="text" name="skills" class="form-control rounded-3" placeholder="e.g. Python, Cloud Architecture, Product Management, Machine Learning" value="<?= e($_POST['skills'] ?? $profile['skills'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <div class="form-check p-3 border rounded-3 bg-light-subtle">
                                <input class="form-check-input ms-0 me-3" type="checkbox" name="is_mentor" value="1" id="mentorCheck" <?= (!empty($_POST['is_mentor']) || !empty($profile['is_mentor'])) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold text-dark" for="mentorCheck">
                                    <i class="bi bi-award-fill text-danger me-1"></i>
                                    Open to Mentorship
                                    <div class="text-secondary small fw-normal">
                                        I am willing to mentor juniors and current MITS students (mock interviews, career guidance, resume reviews).
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="<?= e(BASE_URL) ?>/index.php" class="btn btn-link text-secondary text-decoration-none">Skip for now</a>
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm">
                            <i class="bi bi-check-circle-fill me-2"></i>Save & Complete Profile
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
