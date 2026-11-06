<?php
/**
 * Edit Alumni Profile Page
 * MITS Alumni Portal
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

$user = current_user();
$user_id = $user['id'];
$errors = [];

// Fetch existing profile
$profile = db_fetch_one($conn, "SELECT * FROM alumni_profiles WHERE user_id = ? LIMIT 1", "i", [$user_id]) ?: [];

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

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF
    if (!verify_csrf_token()) {
        $errors[] = 'Security token expired or invalid. Please reload the page.';
    }

    // 2. Sanitize and Extract Input
    $full_name       = sanitize_input($_POST['full_name'] ?? '');
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

    // 3. Validation
    if (empty($full_name)) {
        $errors[] = 'Full Name cannot be empty.';
    }
    if (empty($roll_number)) {
        $errors[] = 'Roll Number is required for verification.';
    }
    if (empty($department)) {
        $errors[] = 'Please select your department.';
    }
    if ($graduation_year < 1998 || $graduation_year > (date('Y') + 5)) {
        $errors[] = 'Please specify a valid graduation year.';
    }
    if (!empty($linkedin_url) && !filter_var($linkedin_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Please enter a valid LinkedIn URL.';
    }

    // 4. Handle Avatar File Upload (if provided)
    $new_avatar_url = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['avatar']['tmp_name'];
        $file_size = $_FILES['avatar']['size'];
        
        if ($file_size > MAX_UPLOAD_SIZE) {
            $errors[] = 'Avatar image must not exceed 5 MB.';
        } else {
            // Verify MIME type safely
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);

            $allowed_mimes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            if (!array_key_exists($mime_type, $allowed_mimes)) {
                $errors[] = 'Invalid image format. Only JPG, PNG, and WebP are allowed.';
            } else {
                $ext = $allowed_mimes[$mime_type];
                $filename = 'avatar_' . $user_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $target_path = UPLOAD_DIR . '/avatars/' . $filename;

                if (move_uploaded_file($file_tmp, $target_path)) {
                    $new_avatar_url = BASE_URL . '/uploads/avatars/' . $filename;
                } else {
                    $errors[] = 'Failed to upload profile photo. Check directory permissions.';
                }
            }
        }
    }

    // 5. Update Database if no errors
    if (empty($errors)) {
        // Update `users` table
        if ($new_avatar_url) {
            db_execute($conn, "UPDATE users SET full_name = ?, avatar_url = ? WHERE id = ?", "ssi", [$full_name, $new_avatar_url, $user_id]);
            $_SESSION['user']['avatar_url'] = $new_avatar_url;
        } else {
            db_execute($conn, "UPDATE users SET full_name = ? WHERE id = ?", "si", [$full_name, $user_id]);
        }
        $_SESSION['user']['full_name'] = $full_name;

        // Upsert `alumni_profiles` table
        $profile_sql = "INSERT INTO alumni_profiles 
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

        $stmt = db_execute($conn, $profile_sql, "ississsssssi", [
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
            set_flash('success', 'Profile updated successfully!');
            header('Location: ' . BASE_URL . '/profile.php');
            exit;
        } else {
            $errors[] = 'Failed to update profile details.';
        }
    }
}

$page_title = 'Edit Profile';
$current_page = 'edit-profile';

include_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card-modern border-0 shadow-lg p-4 p-md-5">

                <!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
                    <div>
                        <h2 class="fw-bold font-heading mb-1 text-dark">Edit Profile</h2>
                        <p class="text-secondary small mb-0">Update your personal details, academic record, and work experience.</p>
                    </div>
                    <a href="<?= e(BASE_URL) ?>/profile.php" class="btn btn-outline-secondary rounded-pill px-3 btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>View Profile
                    </a>
                </div>

                <!-- Display Errors -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please correct the following:</div>
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $error): ?>
                                <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="<?= e(BASE_URL) ?>/edit-profile.php" method="POST" enctype="multipart/form-data">
                    <?= csrf_field(); ?>

                    <!-- Profile Photo & Basic Info -->
                    <div class="row g-4 align-items-center mb-4 p-3 bg-light-subtle rounded-3 border">
                        <div class="col-auto">
                            <img id="avatar-preview" 
                                 src="<?= e(get_avatar_url($user['avatar_url'] ?? '', $user['full_name'])) ?>" 
                                 alt="<?= e($user['full_name']) ?>" 
                                 class="rounded-circle border border-2 border-white shadow-sm object-fit-cover avatar-xl">
                        </div>
                        <div class="col">
                            <label class="form-label fw-semibold text-dark mb-1">Profile Photo</label>
                            <input type="file" name="avatar" class="form-control rounded-3" accept="image/png, image/jpeg, image/webp" onchange="previewAvatar(this)">
                            <div class="form-text small">Recommended: Square format (JPG, PNG, or WebP), max 5 MB.</div>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <h5 class="fw-bold font-heading text-dark mb-3">
                        <i class="bi bi-person text-primary me-2"></i>Personal Details
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control rounded-3" value="<?= e($_POST['full_name'] ?? $user['full_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Email Address</label>
                            <input type="email" class="form-control bg-light rounded-3" value="<?= e($user['email']) ?>" readonly disabled>
                            <div class="form-text small">Associated with your Google Account</div>
                        </div>
                    </div>

                    <!-- Academic Information -->
                    <h5 class="fw-bold font-heading text-dark mb-3">
                        <i class="bi bi-mortarboard text-primary me-2"></i>Academic Information
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Roll Number <span class="text-danger">*</span></label>
                            <input type="text" name="roll_number" class="form-control rounded-3 text-uppercase" placeholder="e.g. 17691A0589" value="<?= e($_POST['roll_number'] ?? $profile['roll_number'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small text-secondary">Department / Branch <span class="text-danger">*</span></label>
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
                            <label class="form-label fw-semibold small text-secondary">Graduation Year <span class="text-danger">*</span></label>
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

                    <!-- Career & Work -->
                    <h5 class="fw-bold font-heading text-dark mb-3">
                        <i class="bi bi-briefcase text-primary me-2"></i>Career & Professional Details
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Company / Organization</label>
                            <input type="text" name="current_company" class="form-control rounded-3" placeholder="e.g. Google, Infosys, Microsoft" value="<?= e($_POST['current_company'] ?? $profile['current_company'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Job Title / Designation</label>
                            <input type="text" name="designation" class="form-control rounded-3" placeholder="e.g. Software Engineer, Tech Lead" value="<?= e($_POST['designation'] ?? $profile['designation'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Industry / Field</label>
                            <input type="text" name="industry" class="form-control rounded-3" placeholder="e.g. Information Technology, Automobile" value="<?= e($_POST['industry'] ?? $profile['industry'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Current Location (City, Country)</label>
                            <input type="text" name="location" class="form-control rounded-3" placeholder="e.g. Bengaluru, India" value="<?= e($_POST['location'] ?? $profile['location'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">LinkedIn Profile URL</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-primary"><i class="bi bi-linkedin"></i></span>
                                <input type="url" name="linkedin_url" class="form-control rounded-end-3" placeholder="https://www.linkedin.com/in/username" value="<?= e($_POST['linkedin_url'] ?? $profile['linkedin_url'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Bio, Skills & Mentorship -->
                    <h5 class="fw-bold font-heading text-dark mb-3">
                        <i class="bi bi-stars text-primary me-2"></i>Bio & Skills
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">About Me / Bio</label>
                            <textarea name="bio" rows="4" class="form-control rounded-3" placeholder="Share your career journey, achievements, and interests..."><?= e($_POST['bio'] ?? $profile['bio'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Skills & Technologies (Comma-separated)</label>
                            <input type="text" name="skills" class="form-control rounded-3" placeholder="e.g. Java, Python, React, Cloud Architecture, Product Design" value="<?= e($_POST['skills'] ?? $profile['skills'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <div class="form-check p-3 border rounded-3 bg-light-subtle">
                                <input class="form-check-input ms-0 me-3" type="checkbox" name="is_mentor" value="1" id="mentorCheckEdit" <?= (!empty($_POST['is_mentor']) || !empty($profile['is_mentor'])) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold text-dark" for="mentorCheckEdit">
                                    <i class="bi bi-award-fill text-danger me-1"></i>
                                    Open to Mentorship
                                    <div class="text-secondary small fw-normal">
                                        Flag your profile so students and junior alumni can reach out to you for guidance, mock interviews, and career advice.
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="<?= e(BASE_URL) ?>/profile.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm">
                            <i class="bi bi-check2-circle me-2"></i>Save Changes
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar-preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
