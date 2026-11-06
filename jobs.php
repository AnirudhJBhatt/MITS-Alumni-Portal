<?php
/**
 * Career & Job Opportunities Board
 * MITS Alumni Portal
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Career & Job Board';
$page_description = 'Discover hiring opportunities, job referrals, and internships shared by MITS alumni and recruiters.';
$current_page = 'jobs';

// 1. Sanitize Filter Parameters
$q        = sanitize_input($_GET['q'] ?? '');
$job_type = sanitize_input($_GET['type'] ?? 'all');
$exp      = sanitize_input($_GET['exp'] ?? '');
$location = sanitize_input($_GET['location'] ?? '');
$open_job_id = (int)($_GET['id'] ?? 0);

// 2. Build Query
$sql = "SELECT j.*, 
               u.id as poster_id, u.full_name as poster_name, u.avatar_url as poster_avatar,
               ap.department, ap.graduation_year, ap.current_company as poster_company, ap.designation as poster_designation
        FROM jobs j
        JOIN users u ON j.posted_by = u.id
        LEFT JOIN alumni_profiles ap ON u.id = ap.user_id
        WHERE j.status = 'active'";

$types = "";
$params = [];

if (!empty($q)) {
    $sql .= " AND (j.title LIKE ? OR j.company LIKE ? OR j.location LIKE ? OR j.description LIKE ?)";
    $like = '%' . $q . '%';
    $types .= "ssss";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($job_type !== 'all' && in_array($job_type, ['full-time', 'internship', 'remote', 'part-time'])) {
    $sql .= " AND j.job_type = ?";
    $types .= "s";
    $params[] = $job_type;
}

if (!empty($exp)) {
    $sql .= " AND j.experience_level LIKE ?";
    $types .= "s";
    $params[] = '%' . $exp . '%';
}

if (!empty($location)) {
    $sql .= " AND j.location LIKE ?";
    $types .= "s";
    $params[] = '%' . $location . '%';
}

$sql .= " ORDER BY j.created_at DESC";

$jobs = db_fetch_all($conn, $sql, $types, $params);
$total_jobs = count($jobs);

// Pre-fetch distinct locations for filters
$locations = db_fetch_all($conn, "SELECT DISTINCT location FROM jobs WHERE status = 'active' AND location IS NOT NULL AND location != '' ORDER BY location ASC");

$current_id = current_user_id();

include_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Header Hero Banner -->
    <div class="card-modern border-0 shadow-sm p-4 p-md-5 mb-4 text-center text-md-start" style="background: radial-gradient(100% 100% at 85% 0%, rgba(216, 21, 28, 0.06) 0%, rgba(255, 255, 255, 1) 100%);">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 mb-2">
                    <i class="bi bi-briefcase-fill me-1"></i>Career Opportunities
                </span>
                <h1 class="display-6 fw-bold font-heading mb-2 text-dark">Alumni Career Board</h1>
                <p class="text-secondary mb-0">
                    Explore verified openings, alumni employee referrals, and internships at leading companies worldwide.
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <?php if (is_logged_in()): ?>
                    <a href="<?= e(BASE_URL) ?>/post-job.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="bi bi-plus-circle me-2"></i>Post an Opportunity
                    </a>
                <?php else: ?>
                    <a href="<?= e(BASE_URL) ?>/auth/login.php" class="btn btn-outline-primary rounded-pill px-4">
                        <i class="bi bi-google me-2"></i>Sign In to Post
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="card-modern border-0 shadow-sm p-4 mb-4 bg-white">
        <form action="<?= e(BASE_URL) ?>/jobs.php" method="GET" class="row g-3 align-items-end">
            <!-- Keyword Search -->
            <div class="col-lg-5 col-md-6">
                <label class="form-label fw-semibold small text-secondary">Keyword Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control border-start-0 rounded-end-3" placeholder="Job title, company, skills..." value="<?= e($q) ?>">
                </div>
            </div>

            <!-- Job Type Filter -->
            <div class="col-lg-3 col-md-6">
                <label class="form-label fw-semibold small text-secondary">Job Type</label>
                <select name="type" class="form-select rounded-3">
                    <option value="all" <?= ($job_type === 'all') ? 'selected' : '' ?>>All Job Types</option>
                    <option value="full-time" <?= ($job_type === 'full-time') ? 'selected' : '' ?>>Full-Time</option>
                    <option value="internship" <?= ($job_type === 'internship') ? 'selected' : '' ?>>Internship</option>
                    <option value="remote" <?= ($job_type === 'remote') ? 'selected' : '' ?>>Remote</option>
                    <option value="part-time" <?= ($job_type === 'part-time') ? 'selected' : '' ?>>Part-Time</option>
                </select>
            </div>

            <!-- Location Filter -->
            <div class="col-lg-3 col-md-6">
                <label class="form-label fw-semibold small text-secondary">Location</label>
                <select name="location" class="form-select rounded-3">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= e($loc['location']) ?>" <?= ($location === $loc['location']) ? 'selected' : '' ?>>
                            <?= e($loc['location']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="col-lg-1 col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-3 w-100" title="Apply Filters">
                    <i class="bi bi-filter"></i>
                </button>
                <?php if (!empty($q) || $job_type !== 'all' || !empty($location)): ?>
                    <a href="<?= e(BASE_URL) ?>/jobs.php" class="btn btn-outline-secondary rounded-3" title="Clear Filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Job Cards List -->
    <div class="row g-4">
        <?php if (!empty($jobs)): ?>
            <?php foreach ($jobs as $job): 
                $can_manage = is_logged_in() && ($job['posted_by'] == $current_id || is_admin());
                $card_id = 'job-card-' . $job['id'];
            ?>
                <div class="col-md-6 col-lg-6" id="<?= $card_id ?>">
                    <div class="card-modern card-hover h-100 p-4 border bg-white d-flex flex-column">
                        
                        <!-- Top Header: Title, Company, Type -->
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                            <div>
                                <h5 class="fw-bold font-heading mb-1 text-dark">
                                    <a href="javascript:void(0)" onclick="openJobModal(<?= $job['id'] ?>)" class="text-dark text-decoration-none hover-primary">
                                        <?= e($job['title']) ?>
                                    </a>
                                </h5>
                                <div class="text-secondary fw-semibold small">
                                    <i class="bi bi-building me-1 text-primary"></i><?= e($job['company']) ?>
                                    <span class="mx-2">•</span>
                                    <i class="bi bi-geo-alt me-1 text-danger"></i><?= e($job['location']) ?>
                                </div>
                            </div>

                            <span class="badge <?= $job['job_type'] === 'internship' ? 'bg-info-subtle text-info border border-info-subtle' : ($job['job_type'] === 'remote' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle') ?> rounded-pill px-3 py-2 text-capitalize small">
                                <?= e($job['job_type']) ?>
                            </span>
                        </div>

                        <!-- Description Snippet -->
                        <p class="text-secondary small mb-3 line-height-relaxed flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= e($job['description']) ?>
                        </p>

                        <!-- Experience & Deadline Badges -->
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge bg-light text-dark border rounded-pill px-2 py-1 small">
                                <i class="bi bi-person-workspace me-1 text-muted"></i><?= e($job['experience_level']) ?>
                            </span>
                            <?php if (!empty($job['deadline'])): ?>
                                <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small">
                                    <i class="bi bi-clock me-1 text-muted"></i>Deadline: <?= format_date($job['deadline']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Card Footer: Poster Details & Actions -->
                        <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-auto">
                            <!-- Poster Preview -->
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= e(get_avatar_url($job['poster_avatar'] ?? '', $job['poster_name'])) ?>" 
                                     alt="<?= e($job['poster_name']) ?>" 
                                     class="rounded-circle avatar-sm object-fit-cover">
                                <div class="small text-secondary line-height-tight" style="font-size: 0.775rem;">
                                    <div>Posted by <strong class="text-dark"><?= e($job['poster_name']) ?></strong></div>
                                    <small class="text-muted"><?= time_ago($job['created_at']) ?></small>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" onclick="openJobModal(<?= $job['id'] ?>)">
                                    View & Apply
                                </button>

                                <?php if ($can_manage): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light rounded-circle btn-icon shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical text-secondary"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end border shadow-sm py-1">
                                            <li>
                                                <button class="dropdown-item small text-danger" type="button" onclick="deleteJob(<?= $job['id'] ?>, '<?= $card_id ?>')">
                                                    <i class="bi bi-trash me-2"></i>Delete Job
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card-modern border-0 shadow-sm p-5 text-center bg-white my-3">
                    <div class="brand-icon-box mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem; opacity: 0.85;">
                        <i class="bi bi-briefcase"></i>
                    </div>
                    <h4 class="fw-bold font-heading mb-1 text-dark">No Job Openings Found</h4>
                    <p class="text-secondary small mb-3">No active career opportunities match your filter criteria.</p>
                    <a href="<?= e(BASE_URL) ?>/jobs.php" class="btn btn-primary rounded-pill px-4">
                        Reset All Filters
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Job Details Modal -->
<div class="modal fade" id="jobDetailModal" tabindex="-1" aria-labelledby="jobModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            
            <div class="modal-header border-bottom p-4">
                <div>
                    <span id="modal-job-type" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 text-capitalize small mb-2"></span>
                    <h4 class="fw-bold font-heading mb-1 text-dark" id="jobModalTitle">Job Title</h4>
                    <div class="text-secondary small" id="modal-company-info">Company Name • Location</div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Meta tags row -->
                <div class="d-flex flex-wrap gap-2 mb-4 p-3 bg-light rounded-3 border">
                    <div class="small text-secondary me-3">
                        <i class="bi bi-person-workspace text-primary me-1"></i>
                        <span id="modal-experience">0-2 Years</span>
                    </div>
                    <div class="small text-secondary me-3">
                        <i class="bi bi-calendar-check text-primary me-1"></i>
                        Posted: <span id="modal-posted-date">Just now</span>
                    </div>
                    <div class="small text-secondary" id="modal-deadline-container">
                        <i class="bi bi-clock-history text-danger me-1"></i>
                        Deadline: <span id="modal-deadline">None</span>
                    </div>
                </div>

                <!-- Description -->
                <h6 class="fw-bold text-dark mb-2">About the Role</h6>
                <div class="small text-secondary mb-4 line-height-relaxed" id="modal-description"></div>

                <!-- Requirements -->
                <div id="modal-requirements-section" class="mb-4">
                    <h6 class="fw-bold text-dark mb-2">Key Qualifications & Skills</h6>
                    <div class="small text-secondary line-height-relaxed" id="modal-requirements"></div>
                </div>

                <!-- Posted By Alumnus Card -->
                <div class="p-3 border rounded-3 bg-light-subtle d-flex align-items-center gap-3">
                    <img id="modal-poster-avatar" src="" alt="Poster" class="rounded-circle avatar-md object-fit-cover shadow-sm">
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="small fw-bold text-dark" id="modal-poster-name">Alumnus Name</div>
                        <div class="small text-muted" id="modal-poster-headline" style="font-size: 0.775rem;">Designation at Company</div>
                    </div>
                    <a id="modal-poster-profile-btn" href="#" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        View Profile
                    </a>
                </div>
            </div>

            <div class="modal-footer border-top p-4 d-flex justify-content-between">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                <div class="d-flex gap-2" id="modal-apply-buttons">
                    <!-- Injected Apply Buttons -->
                </div>
            </div>

        </div>
    </div>
</div>

<script>
/**
 * Fetch and Open Job Details Modal
 */
async function openJobModal(jobId) {
    try {
        const res = await fetch(`${getBaseUrl()}/api/jobs.php?id=${jobId}`);
        const data = await res.json();

        if (data.success && data.job) {
            const j = data.job;
            document.getElementById('jobModalTitle').textContent = j.title;
            document.getElementById('modal-company-info').textContent = `${j.company} • ${j.location}`;
            document.getElementById('modal-job-type').textContent = j.job_type;
            document.getElementById('modal-experience').textContent = j.experience_level;
            document.getElementById('modal-posted-date').textContent = j.created_at_human;
            document.getElementById('modal-description').innerHTML = j.description;

            // Deadline
            if (j.deadline) {
                document.getElementById('modal-deadline').textContent = j.deadline;
                document.getElementById('modal-deadline-container').classList.remove('d-none');
            } else {
                document.getElementById('modal-deadline-container').classList.add('d-none');
            }

            // Requirements
            const reqSection = document.getElementById('modal-requirements-section');
            if (j.requirements) {
                document.getElementById('modal-requirements').innerHTML = j.requirements;
                reqSection.classList.remove('d-none');
            } else {
                reqSection.classList.add('d-none');
            }

            // Poster
            document.getElementById('modal-poster-avatar').src = j.poster.avatar;
            document.getElementById('modal-poster-name').textContent = j.poster.name;
            document.getElementById('modal-poster-headline').textContent = j.poster.headline;
            document.getElementById('modal-poster-profile-btn').href = `${getBaseUrl()}/profile.php?id=${j.poster.id}`;

            // Apply Actions
            const applyBtnsContainer = document.getElementById('modal-apply-buttons');
            let applyHtml = '';
            if (j.apply_link) {
                applyHtml += `<a href="${j.apply_link}" target="_blank" rel="noopener" class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="bi bi-box-arrow-up-right me-2"></i>Apply on Portal</a>`;
            }
            if (j.contact_email) {
                applyHtml += `<a href="mailto:${j.contact_email}?subject=Application for ${encodeURIComponent(j.title)}" class="btn btn-outline-primary rounded-pill px-4"><i class="bi bi-envelope me-2"></i>Email Resume</a>`;
            }
            applyBtnsContainer.innerHTML = applyHtml;

            const modal = new bootstrap.Modal(document.getElementById('jobDetailModal'));
            modal.show();
        } else {
            showToast('danger', data.message || 'Job listing details could not be loaded.');
        }
    } catch (e) {
        showToast('danger', 'Error loading job details.');
    }
}

/**
 * Delete Job Listing
 */
async function deleteJob(jobId, cardId) {
    if (!confirm('Are you sure you want to delete this job listing?')) return;

    try {
        const res = await apiFetch(`${getBaseUrl()}/api/jobs.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', job_id: jobId })
        });
        const data = await res.json();

        if (data.success) {
            showToast('success', data.message);
            const card = document.getElementById(cardId);
            if (card) card.remove();
        } else {
            showToast('danger', data.message || 'Failed to delete job.');
        }
    } catch (e) {
        showToast('danger', 'Error deleting job.');
    }
}

// Auto open modal if ?id= is in query
document.addEventListener('DOMContentLoaded', () => {
    const autoId = <?= $open_job_id ?>;
    if (autoId > 0) {
        openJobModal(autoId);
    }
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
