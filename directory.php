<?php
/**
 * Alumni Directory & Search Portal
 * MITS Alumni Portal
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Alumni Directory';
$page_description = 'Search, explore, and connect with graduates across batches, departments, and industries.';
$current_page = 'directory';

// 1. Sanitize & Read Filter Parameters
$q           = sanitize_input($_GET['q'] ?? '');
$department  = sanitize_input($_GET['department'] ?? '');
$batch       = (int)($_GET['batch'] ?? 0);
$mentor_only = !empty($_GET['mentor']) ? 1 : 0;
$company     = sanitize_input($_GET['company'] ?? '');

// 2. Build Prepared SQL Query
$sql = "SELECT u.id, u.full_name, u.email, u.avatar_url, u.role, u.status,
               p.roll_number, p.department, p.graduation_year, p.current_company, p.designation,
               p.location, p.skills, p.is_mentor, p.linkedin_url
        FROM users u
        LEFT JOIN alumni_profiles p ON u.id = p.user_id
        WHERE u.role = 'alumni' AND (u.status = 'verified' OR u.status = 'pending')";

$types = "";
$params = [];

if (!empty($q)) {
    $sql .= " AND (u.full_name LIKE ? OR p.current_company LIKE ? OR p.designation LIKE ? OR p.skills LIKE ? OR p.location LIKE ?)";
    $like = '%' . $q . '%';
    $types .= "sssss";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($department)) {
    $sql .= " AND p.department = ?";
    $types .= "s";
    $params[] = $department;
}

if (!empty($batch)) {
    $sql .= " AND p.graduation_year = ?";
    $types .= "i";
    $params[] = $batch;
}

if ($mentor_only) {
    $sql .= " AND p.is_mentor = 1";
}

if (!empty($company)) {
    $sql .= " AND p.current_company LIKE ?";
    $types .= "s";
    $params[] = '%' . $company . '%';
}

$sql .= " ORDER BY (u.status = 'verified') DESC, p.graduation_year DESC, u.full_name ASC";

$alumni_list = db_fetch_all($conn, $sql, $types, $params);
$total_found = count($alumni_list);

// Pre-fetch filter options
$departments_list = db_fetch_all($conn, "SELECT DISTINCT department FROM alumni_profiles WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
$batches_list = db_fetch_all($conn, "SELECT DISTINCT graduation_year FROM alumni_profiles WHERE graduation_year IS NOT NULL ORDER BY graduation_year DESC");

// Current user connection map (if logged in)
$my_connections = [];
$current_id = current_user_id();
if (is_logged_in()) {
    $c_rows = db_fetch_all($conn, 
        "SELECT id, sender_id, receiver_id, status FROM connections 
         WHERE sender_id = ? OR receiver_id = ?", 
        "ii", 
        [$current_id, $current_id]
    );
    foreach ($c_rows as $c) {
        $other_id = ($c['sender_id'] == $current_id) ? $c['receiver_id'] : $c['sender_id'];
        $my_connections[$other_id] = [
            'id' => $c['id'],
            'status' => $c['status'],
            'is_sender' => ($c['sender_id'] == $current_id)
        ];
    }
}

include_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Directory Hero Banner -->
    <div class="card-modern border-0 shadow-sm p-4 p-md-5 mb-4 text-center text-md-start" style="background: radial-gradient(100% 100% at 90% 0%, rgba(216, 21, 28, 0.06) 0%, rgba(255, 255, 255, 1) 100%);">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 mb-2">
                    <i class="bi bi-people-fill me-1"></i>MITS Connect
                </span>
                <h1 class="display-6 fw-bold font-heading mb-2 text-dark">Alumni Directory</h1>
                <p class="text-secondary mb-0">
                    Connect with fellow graduates, discover industry peers across leading tech hubs, and find experienced mentors.
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <div class="stat-card d-inline-block py-2 px-4 shadow-sm bg-white">
                    <div class="fs-3 fw-bold text-gradient"><?= number_format($total_found) ?></div>
                    <small class="text-secondary fw-semibold">Alumni Listed</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Controls Card -->
    <div class="card-modern border-0 shadow-sm p-4 mb-4">
        <form action="<?= e(BASE_URL) ?>/directory.php" method="GET" class="row g-3 align-items-end">
            <!-- Search Text -->
            <div class="col-lg-4 col-md-6">
                <label class="form-label fw-semibold small text-secondary">Search Keyword</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control border-start-0 rounded-end-3" placeholder="Name, role, company, skills..." value="<?= e($q) ?>">
                </div>
            </div>

            <!-- Department Filter -->
            <div class="col-lg-3 col-md-6">
                <label class="form-label fw-semibold small text-secondary">Department / Branch</label>
                <select name="department" class="form-select rounded-3">
                    <option value="">All Departments</option>
                    <?php foreach ($departments_list as $d): ?>
                        <option value="<?= e($d['department']) ?>" <?= ($department === $d['department']) ? 'selected' : '' ?>>
                            <?= e($d['department']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Batch Filter -->
            <div class="col-lg-2 col-md-4">
                <label class="form-label fw-semibold small text-secondary">Batch / Year</label>
                <select name="batch" class="form-select rounded-3">
                    <option value="">All Batches</option>
                    <?php foreach ($batches_list as $b): ?>
                        <option value="<?= $b['graduation_year'] ?>" <?= ($batch === (int)$b['graduation_year']) ? 'selected' : '' ?>>
                            Class of <?= $b['graduation_year'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Mentor Filter Checkbox -->
            <div class="col-lg-2 col-md-4">
                <div class="form-check p-2 border rounded-3 bg-white h-100 d-flex align-items-center">
                    <input class="form-check-input ms-1 me-2" type="checkbox" name="mentor" value="1" id="mentorFilter" <?= $mentor_only ? 'checked' : '' ?>>
                    <label class="form-check-label small fw-semibold text-dark text-nowrap" for="mentorFilter">
                        <i class="bi bi-award-fill text-danger me-1"></i>Mentors Only
                    </label>
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="col-lg-1 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-3 w-100" title="Apply Filters">
                    <i class="bi bi-filter"></i>
                </button>
                <?php if (!empty($q) || !empty($department) || !empty($batch) || $mentor_only || !empty($company)): ?>
                    <a href="<?= e(BASE_URL) ?>/directory.php" class="btn btn-outline-secondary rounded-3" title="Clear Filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Active Filters Summary Badge -->
    <?php if (!empty($q) || !empty($department) || !empty($batch) || $mentor_only): ?>
        <div class="d-flex align-items-center flex-wrap gap-2 mb-4">
            <span class="small text-secondary fw-semibold">Active filters:</span>
            <?php if (!empty($q)): ?>
                <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
                    Keyword: "<?= e($q) ?>"
                </span>
            <?php endif; ?>
            <?php if (!empty($department)): ?>
                <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
                    Dept: <?= e($department) ?>
                </span>
            <?php endif; ?>
            <?php if (!empty($batch)): ?>
                <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
                    Batch: Class of <?= $batch ?>
                </span>
            <?php endif; ?>
            <?php if ($mentor_only): ?>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2">
                    <i class="bi bi-award-fill me-1"></i>Mentors Only
                </span>
            <?php endif; ?>
            <a href="<?= e(BASE_URL) ?>/directory.php" class="small text-danger ms-2 text-decoration-none fw-semibold">
                Reset All
            </a>
        </div>
    <?php endif; ?>

    <!-- Alumni Card Grid -->
    <?php if (!empty($alumni_list)): ?>
        <div class="row g-4">
            <?php foreach ($alumni_list as $alumnus): 
                $is_self = is_logged_in() && ($current_id == $alumnus['id']);
                $conn_info = $my_connections[$alumnus['id']] ?? null;
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card-modern card-hover h-100 d-flex flex-column p-4 border bg-white">
                        
                        <!-- Top Row: Avatar & Status -->
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="position-relative">
                                <img src="<?= e(get_avatar_url($alumnus['avatar_url'] ?? '', $alumnus['full_name'])) ?>" 
                                     alt="<?= e($alumnus['full_name']) ?>" 
                                     class="rounded-circle object-fit-cover shadow-sm avatar-lg">
                                <?php if ($alumnus['status'] === 'verified'): ?>
                                    <span class="position-absolute bottom-0 end-0 bg-white rounded-circle p-1 line-height-tight" title="Verified Alumni">
                                        <i class="bi bi-patch-check-fill text-success fs-6"></i>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="flex-grow-1 overflow-hidden">
                                <h5 class="fw-bold font-heading mb-1 text-truncate">
                                    <a href="<?= e(BASE_URL) ?>/profile.php?id=<?= $alumnus['id'] ?>" class="text-dark text-decoration-none hover-primary">
                                        <?= e($alumnus['full_name']) ?>
                                    </a>
                                </h5>
                                
                                <div class="small text-secondary text-truncate mb-1">
                                    <?php if (!empty($alumnus['designation']) && !empty($alumnus['current_company'])): ?>
                                        <span class="fw-semibold text-dark"><?= e($alumnus['designation']) ?></span><br>
                                        <span class="text-primary"><?= e($alumnus['current_company']) ?></span>
                                    <?php elseif (!empty($alumnus['current_company'])): ?>
                                        <span class="fw-semibold text-dark"><?= e($alumnus['current_company']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">MITS Graduate</span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($alumnus['is_mentor'])): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1 small">
                                        <i class="bi bi-award-fill me-1"></i>Mentor
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Academic Info -->
                        <div class="small text-secondary mb-3 pb-3 border-bottom">
                            <?php if (!empty($alumnus['department'])): ?>
                                <div class="text-truncate mb-1">
                                    <i class="bi bi-building me-1 text-muted"></i><?= e($alumnus['department']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between align-items-center">
                                <?php if (!empty($alumnus['graduation_year'])): ?>
                                    <span>
                                        <i class="bi bi-mortarboard me-1 text-muted"></i>Class of <?= e($alumnus['graduation_year']) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($alumnus['location'])): ?>
                                    <span class="text-truncate" style="max-width: 140px;">
                                        <i class="bi bi-geo-alt me-1 text-danger"></i><?= e($alumnus['location']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Skills preview -->
                        <?php if (!empty($alumnus['skills'])): 
                            $skills = array_slice(array_map('trim', explode(',', $alumnus['skills'])), 0, 3);
                        ?>
                            <div class="mb-3 flex-grow-1">
                                <?php foreach ($skills as $skill): if (!empty($skill)): ?>
                                    <span class="skill-tag" style="font-size: 0.725rem;"><?= e($skill) ?></span>
                                <?php endif; endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="mb-3 flex-grow-1"></div>
                        <?php endif; ?>

                        <!-- Card Actions -->
                        <div class="d-flex align-items-center justify-content-between gap-2 pt-2 border-top mt-auto">
                            <a href="<?= e(BASE_URL) ?>/profile.php?id=<?= $alumnus['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                View Profile
                            </a>

                            <?php if ($is_self): ?>
                                <span class="badge bg-light text-secondary rounded-pill px-3 py-2 small">You</span>
                            <?php elseif (is_logged_in()): ?>
                                <?php if (!$conn_info): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="sendDirectoryConnect(<?= $alumnus['id'] ?>, this)">
                                        <i class="bi bi-person-plus me-1"></i>Connect
                                    </button>
                                <?php elseif ($conn_info['status'] === 'pending' && $conn_info['is_sender']): ?>
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2 small">
                                        <i class="bi bi-clock me-1"></i>Sent
                                    </span>
                                <?php elseif ($conn_info['status'] === 'accepted'): ?>
                                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 small">
                                        <i class="bi bi-check2 me-1"></i>Connected
                                    </span>
                                <?php else: ?>
                                    <a href="<?= e(BASE_URL) ?>/network.php" class="btn btn-sm btn-light rounded-pill px-2 small">
                                        Respond
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="<?= e(BASE_URL) ?>/auth/login.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    Connect
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="card-modern border-0 shadow-sm p-5 text-center my-4">
            <div class="brand-icon-box mx-auto mb-3" style="width: 54px; height: 54px; font-size: 1.5rem; opacity: 0.85;">
                <i class="bi bi-search"></i>
            </div>
            <h4 class="fw-bold font-heading mb-2 text-dark">No Alumni Found</h4>
            <p class="text-secondary small mb-4 mx-auto" style="max-width: 500px;">
                We couldn't find any alumni matching your search filters. Try using broader keywords or clearing specific department or batch filters.
            </p>
            <div>
                <a href="<?= e(BASE_URL) ?>/directory.php" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-arrow-counterclockwise me-2"></i>Reset All Filters
                </a>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
async function sendDirectoryConnect(targetId, btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Connecting...';
    try {
        const res = await apiFetch(getBaseUrl() + '/api/connections.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'send', target_user_id: targetId })
        });
        const data = await res.json();
        if (data.success) {
            showToast('success', data.message);
            btn.outerHTML = '<span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2 small"><i class="bi bi-clock me-1"></i>Sent</span>';
        } else {
            showToast('danger', data.message || 'Failed to send request.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-person-plus me-1"></i>Connect';
        }
    } catch (e) {
        showToast('danger', 'Error connecting to server.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-person-plus me-1"></i>Connect';
    }
}
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
