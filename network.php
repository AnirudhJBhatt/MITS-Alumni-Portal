<?php
/**
 * Networking & Connections Hub
 * MITS Alumni Portal
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

$user = current_user();
$current_id = $user['id'];

$page_title = 'My Alumni Network';
$page_description = 'Manage your professional network, respond to connection requests, and discover fellow alumni from MITS.';
$current_page = 'network';

// 1. Fetch Current User's Profile Info (for recommendation engine)
$my_profile = db_fetch_one($conn, "SELECT department, graduation_year FROM alumni_profiles WHERE user_id = ? LIMIT 1", "i", [$current_id]) ?: [];
$my_dept = $my_profile['department'] ?? '';
$my_year = (int)($my_profile['graduation_year'] ?? 0);

// 2. Fetch Active Connections (Status = 'accepted')
$conn_sql = "SELECT c.id as connection_id, c.created_at as connected_at,
                    u.id as user_id, u.full_name, u.avatar_url, u.email, u.status,
                    p.department, p.graduation_year, p.current_company, p.designation, p.location, p.is_mentor
             FROM connections c
             JOIN users u ON (u.id = IF(c.sender_id = ?, c.receiver_id, c.sender_id))
             LEFT JOIN alumni_profiles p ON u.id = p.user_id
             WHERE (c.sender_id = ? OR c.receiver_id = ?) AND c.status = 'accepted'
             ORDER BY c.updated_at DESC";
$my_connections = db_fetch_all($conn, $conn_sql, "iii", [$current_id, $current_id, $current_id]);

// 3. Fetch Received Invitations (Status = 'pending', receiver = current_user)
$recv_sql = "SELECT c.id as connection_id, c.created_at as requested_at,
                    u.id as user_id, u.full_name, u.avatar_url, u.email, u.status,
                    p.department, p.graduation_year, p.current_company, p.designation, p.location, p.is_mentor
             FROM connections c
             JOIN users u ON c.sender_id = u.id
             LEFT JOIN alumni_profiles p ON u.id = p.user_id
             WHERE c.receiver_id = ? AND c.status = 'pending'
             ORDER BY c.created_at DESC";
$received_requests = db_fetch_all($conn, $recv_sql, "i", [$current_id]);

// 4. Fetch Sent Requests (Status = 'pending', sender = current_user)
$sent_sql = "SELECT c.id as connection_id, c.created_at as sent_at,
                    u.id as user_id, u.full_name, u.avatar_url, u.email, u.status,
                    p.department, p.graduation_year, p.current_company, p.designation, p.location
             FROM connections c
             JOIN users u ON c.receiver_id = u.id
             LEFT JOIN alumni_profiles p ON u.id = p.user_id
             WHERE c.sender_id = ? AND c.status = 'pending'
             ORDER BY c.created_at DESC";
$sent_requests = db_fetch_all($conn, $sent_sql, "i", [$current_id]);

// 5. Fetch Recommended Alumni (Not self, not connected, not pending)
$rec_sql = "SELECT u.id as user_id, u.full_name, u.avatar_url, u.status,
                   p.department, p.graduation_year, p.current_company, p.designation, p.location, p.is_mentor,
                   (CASE 
                        WHEN p.department = ? THEN 3 
                        WHEN ABS(CAST(p.graduation_year AS SIGNED) - ?) <= 2 THEN 2 
                        WHEN p.is_mentor = 1 THEN 1 
                        ELSE 0 
                    END) as match_score
            FROM users u
            JOIN alumni_profiles p ON u.id = p.user_id
            WHERE u.id != ? 
              AND u.role = 'alumni'
              AND u.id NOT IN (
                  SELECT receiver_id FROM connections WHERE sender_id = ?
                  UNION
                  SELECT sender_id FROM connections WHERE receiver_id = ?
              )
            ORDER BY match_score DESC, (u.status = 'verified') DESC, u.created_at DESC
            LIMIT 6";
$recommendations = db_fetch_all($conn, $rec_sql, "siiii", [$my_dept, $my_year, $current_id, $current_id, $current_id]);

include_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Networking Hero Header -->
    <div class="card-modern border-0 shadow-sm p-4 p-md-5 mb-4 text-center text-md-start" style="background: radial-gradient(100% 100% at 85% 0%, rgba(216, 21, 28, 0.06) 0%, rgba(255, 255, 255, 1) 100%);">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 mb-2">
                    <i class="bi bi-diagram-3-fill me-1"></i>Network Hub
                </span>
                <h1 class="display-6 fw-bold font-heading mb-2 text-dark">My Alumni Network</h1>
                <p class="text-secondary mb-0">
                    Grow your professional circle, connect with classmates, and collaborate with experienced graduates across top organizations.
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="<?= e(BASE_URL) ?>/directory.php" class="btn btn-outline-primary rounded-pill px-4">
                    <i class="bi bi-search me-2"></i>Find More Alumni
                </a>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills gap-2 mb-4 p-2 bg-white rounded-4 border shadow-sm" id="networkTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill active px-4 fw-semibold" id="connections-tab" data-bs-toggle="pill" data-bs-target="#tab-connections" type="button" role="tab" aria-selected="true">
                <i class="bi bi-people me-2"></i>Connections
                <span class="badge bg-light text-dark ms-2" id="badge-connections-count"><?= count($my_connections) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 fw-semibold position-relative" id="invitations-tab" data-bs-toggle="pill" data-bs-target="#tab-invitations" type="button" role="tab" aria-selected="false">
                <i class="bi bi-envelope-paper me-2"></i>Invitations
                <?php if (count($received_requests) > 0): ?>
                    <span class="badge bg-danger text-white ms-2" id="badge-received-count"><?= count($received_requests) ?></span>
                <?php else: ?>
                    <span class="badge bg-light text-secondary ms-2 d-none" id="badge-received-count">0</span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 fw-semibold" id="sent-tab" data-bs-toggle="pill" data-bs-target="#tab-sent" type="button" role="tab" aria-selected="false">
                <i class="bi bi-send me-2"></i>Sent Requests
                <span class="badge bg-light text-secondary ms-2" id="badge-sent-count"><?= count($sent_requests) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 fw-semibold" id="suggestions-tab" data-bs-toggle="pill" data-bs-target="#tab-suggestions" type="button" role="tab" aria-selected="false">
                <i class="bi bi-magic me-2"></i>Recommended For You
            </button>
        </li>
    </ul>

    <!-- Tab Content Panels -->
    <div class="tab-content" id="networkTabContent">

        <!-- 1. My Connections Panel -->
        <div class="tab-pane fade show active" id="tab-connections" role="tabpanel">
            <div id="connections-container" class="row g-4">
                <?php if (!empty($my_connections)): ?>
                    <?php foreach ($my_connections as $conn_item): 
                        $card_id = 'conn-card-' . $conn_item['connection_id'];
                    ?>
                        <div class="col-md-6 col-lg-4 connection-item" id="<?= $card_id ?>">
                            <div class="card-modern card-hover h-100 p-4 border bg-white d-flex flex-column">
                                <div class="d-flex align-items-start gap-3 mb-3">
                                    <img src="<?= e(get_avatar_url($conn_item['avatar_url'] ?? '', $conn_item['full_name'])) ?>" 
                                         alt="<?= e($conn_item['full_name']) ?>" 
                                         class="rounded-circle object-fit-cover shadow-sm avatar-lg">
                                    <div class="overflow-hidden flex-grow-1">
                                        <h5 class="fw-bold font-heading mb-1 text-truncate">
                                            <a href="<?= e(BASE_URL) ?>/profile.php?id=<?= $conn_item['user_id'] ?>" class="text-dark text-decoration-none hover-primary">
                                                <?= e($conn_item['full_name']) ?>
                                            </a>
                                        </h5>
                                        <div class="small text-secondary text-truncate mb-1">
                                            <?php if (!empty($conn_item['designation']) && !empty($conn_item['current_company'])): ?>
                                                <span class="fw-semibold text-dark"><?= e($conn_item['designation']) ?></span><br>
                                                <span class="text-primary"><?= e($conn_item['current_company']) ?></span>
                                            <?php elseif (!empty($conn_item['current_company'])): ?>
                                                <span class="fw-semibold text-dark"><?= e($conn_item['current_company']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">MITS Graduate</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($conn_item['is_mentor'])): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1 small">
                                                <i class="bi bi-award-fill me-1"></i>Mentor
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="small text-secondary mb-3 pb-2 border-bottom">
                                    <?php if (!empty($conn_item['department'])): ?>
                                        <div class="text-truncate mb-1">
                                            <i class="bi bi-building me-1 text-muted"></i><?= e($conn_item['department']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between">
                                        <?php if (!empty($conn_item['graduation_year'])): ?>
                                            <span><i class="bi bi-mortarboard me-1 text-muted"></i>Class of <?= e($conn_item['graduation_year']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($conn_item['location'])): ?>
                                            <span class="text-truncate" style="max-width: 140px;">
                                                <i class="bi bi-geo-alt me-1 text-danger"></i><?= e($conn_item['location']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between pt-2 mt-auto">
                                    <a href="<?= e(BASE_URL) ?>/profile.php?id=<?= $conn_item['user_id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        View Profile
                                    </a>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light rounded-circle btn-icon shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More options">
                                            <i class="bi bi-three-dots-vertical text-secondary"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end border shadow-sm py-1">
                                            <li>
                                                <a class="dropdown-item small" href="mailto:<?= e($conn_item['email']) ?>">
                                                    <i class="bi bi-envelope me-2 text-primary"></i>Send Email
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <button class="dropdown-item small text-danger" type="button" onclick="removeConnection(<?= $conn_item['connection_id'] ?>, '<?= e(addslashes($conn_item['full_name'])) ?>', '<?= $card_id ?>')">
                                                    <i class="bi bi-person-x me-2"></i>Remove Connection
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Empty State for My Connections -->
            <div id="empty-connections-msg" class="card-modern border-0 shadow-sm p-5 text-center my-3 <?= !empty($my_connections) ? 'd-none' : '' ?>">
                <div class="brand-icon-box mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem; opacity: 0.85;">
                    <i class="bi bi-people"></i>
                </div>
                <h4 class="fw-bold font-heading mb-2 text-dark">No Connections Yet</h4>
                <p class="text-secondary small mb-4 mx-auto" style="max-width: 480px;">
                    Start connecting with fellow graduates, colleagues, and mentors across different engineering branches.
                </p>
                <div>
                    <a href="<?= e(BASE_URL) ?>/directory.php" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-search me-2"></i>Explore Alumni Directory
                    </a>
                </div>
            </div>
        </div>

        <!-- 2. Received Invitations Panel -->
        <div class="tab-pane fade" id="tab-invitations" role="tabpanel">
            <div id="invitations-container" class="d-flex flex-column gap-3">
                <?php if (!empty($received_requests)): ?>
                    <?php foreach ($received_requests as $req): 
                        $card_id = 'invitation-card-' . $req['connection_id'];
                    ?>
                        <div class="card-modern p-4 border bg-white connection-item" id="<?= $card_id ?>">
                            <div class="row align-items-center g-3">
                                <div class="col-auto">
                                    <img src="<?= e(get_avatar_url($req['avatar_url'] ?? '', $req['full_name'])) ?>" 
                                         alt="<?= e($req['full_name']) ?>" 
                                         class="rounded-circle object-fit-cover shadow-sm avatar-lg">
                                </div>
                                <div class="col">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <h5 class="fw-bold font-heading mb-0">
                                            <a href="<?= e(BASE_URL) ?>/profile.php?id=<?= $req['user_id'] ?>" class="text-dark text-decoration-none hover-primary">
                                                <?= e($req['full_name']) ?>
                                            </a>
                                        </h5>
                                        <?= get_status_badge($req['status']) ?>
                                    </div>
                                    <div class="text-secondary small mb-1">
                                        <?php if (!empty($req['designation']) && !empty($req['current_company'])): ?>
                                            <span class="fw-semibold text-dark"><?= e($req['designation']) ?></span> at <?= e($req['current_company']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($req['location'])): ?>
                                            <span class="mx-2">•</span><i class="bi bi-geo-alt text-danger me-1"></i><?= e($req['location']) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted">
                                        <?= e($req['department'] ?? 'Alumnus') ?> • Class of <?= e($req['graduation_year'] ?? 'N/A') ?>
                                        <span class="mx-2">•</span>
                                        Requested <?= time_ago($req['requested_at']) ?>
                                    </div>
                                </div>
                                <div class="col-12 col-md-auto d-flex gap-2 justify-content-md-end">
                                    <button type="button" class="btn btn-primary rounded-pill px-4" onclick="acceptConnection(<?= $req['connection_id'] ?>, '<?= $card_id ?>')">
                                        <i class="bi bi-check-lg me-1"></i>Accept
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" onclick="rejectConnection(<?= $req['connection_id'] ?>, '<?= $card_id ?>')">
                                        Decline
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Empty State for Received Invitations -->
            <div id="empty-received-msg" class="card-modern border-0 shadow-sm p-5 text-center my-3 <?= !empty($received_requests) ? 'd-none' : '' ?>">
                <div class="brand-icon-box mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem; opacity: 0.85;">
                    <i class="bi bi-inbox"></i>
                </div>
                <h4 class="fw-bold font-heading mb-1 text-dark">No Pending Invitations</h4>
                <p class="text-secondary small mb-0">When fellow alumni send you connection requests, they will show up here.</p>
            </div>
        </div>

        <!-- 3. Sent Requests Panel -->
        <div class="tab-pane fade" id="tab-sent" role="tabpanel">
            <div id="sent-container" class="d-flex flex-column gap-3">
                <?php if (!empty($sent_requests)): ?>
                    <?php foreach ($sent_requests as $s): 
                        $card_id = 'sent-card-' . $s['connection_id'];
                    ?>
                        <div class="card-modern p-4 border bg-white connection-item" id="<?= $card_id ?>">
                            <div class="row align-items-center g-3">
                                <div class="col-auto">
                                    <img src="<?= e(get_avatar_url($s['avatar_url'] ?? '', $s['full_name'])) ?>" 
                                         alt="<?= e($s['full_name']) ?>" 
                                         class="rounded-circle object-fit-cover shadow-sm avatar-lg">
                                </div>
                                <div class="col">
                                    <h5 class="fw-bold font-heading mb-1">
                                        <a href="<?= e(BASE_URL) ?>/profile.php?id=<?= $s['user_id'] ?>" class="text-dark text-decoration-none hover-primary">
                                            <?= e($s['full_name']) ?>
                                        </a>
                                    </h5>
                                    <div class="text-secondary small mb-1">
                                        <?= e($s['designation'] ?? 'Alumnus') ?> • <?= e($s['current_company'] ?? 'MITS') ?>
                                    </div>
                                    <div class="small text-muted">
                                        Sent <?= time_ago($s['sent_at']) ?>
                                    </div>
                                </div>
                                <div class="col-12 col-md-auto d-flex align-items-center gap-2 justify-content-md-end">
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2 small me-2">
                                        <i class="bi bi-hourglass-split me-1"></i>Pending Response
                                    </span>
                                    <button type="button" class="btn btn-outline-danger rounded-pill px-3 btn-sm" onclick="withdrawConnection(<?= $s['connection_id'] ?>, '<?= $card_id ?>')">
                                        Cancel Request
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Empty State for Sent Requests -->
            <div id="empty-sent-msg" class="card-modern border-0 shadow-sm p-5 text-center my-3 <?= !empty($sent_requests) ? 'd-none' : '' ?>">
                <div class="brand-icon-box mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem; opacity: 0.85;">
                    <i class="bi bi-send-check"></i>
                </div>
                <h4 class="fw-bold font-heading mb-1 text-dark">No Pending Sent Requests</h4>
                <p class="text-secondary small mb-0">You have no outgoing invitations awaiting responses.</p>
            </div>
        </div>

        <!-- 4. Recommended For You Panel -->
        <div class="tab-pane fade" id="tab-suggestions" role="tabpanel">
            <div class="row g-4">
                <?php if (!empty($recommendations)): ?>
                    <?php foreach ($recommendations as $rec): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card-modern card-hover h-100 p-4 border bg-white d-flex flex-column text-center">
                                <div class="mb-3">
                                    <img src="<?= e(get_avatar_url($rec['avatar_url'] ?? '', $rec['full_name'])) ?>" 
                                         alt="<?= e($rec['full_name']) ?>" 
                                         class="rounded-circle object-fit-cover shadow-sm mx-auto avatar-xl mb-2">
                                    
                                    <h5 class="fw-bold font-heading mb-1 text-truncate">
                                        <a href="<?= e(BASE_URL) ?>/profile.php?id=<?= $rec['user_id'] ?>" class="text-dark text-decoration-none hover-primary">
                                            <?= e($rec['full_name']) ?>
                                        </a>
                                    </h5>
                                    
                                    <div class="small text-secondary text-truncate mb-2">
                                        <?php if (!empty($rec['designation']) && !empty($rec['current_company'])): ?>
                                            <?= e($rec['designation']) ?> at <strong class="text-dark"><?= e($rec['current_company']) ?></strong>
                                        <?php elseif (!empty($rec['current_company'])): ?>
                                            <?= e($rec['current_company']) ?>
                                        <?php else: ?>
                                            MITS Graduate
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($rec['department'])): ?>
                                        <div class="badge bg-light text-secondary border rounded-pill px-3 py-1 mb-2 small text-truncate" style="max-width: 220px;">
                                            <?= e($rec['department']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($rec['graduation_year'])): ?>
                                        <div class="small text-muted mb-2">Class of <?= e($rec['graduation_year']) ?></div>
                                    <?php endif; ?>

                                    <?php if (!empty($rec['is_mentor'])): ?>
                                        <div>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1 small">
                                                <i class="bi bi-award-fill me-1"></i>Open to Mentoring
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="pt-3 border-top mt-auto d-flex justify-content-center gap-2">
                                    <a href="<?= e(BASE_URL) ?>/profile.php?id=<?= $rec['user_id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                        Profile
                                    </a>
                                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-4" onclick="sendConnect(<?= $rec['user_id'] ?>, this)">
                                        <i class="bi bi-person-plus-fill me-1"></i>Connect
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="card-modern border-0 shadow-sm p-5 text-center my-3">
                            <div class="brand-icon-box mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem; opacity: 0.85;">
                                <i class="bi bi-check-all"></i>
                            </div>
                            <h4 class="fw-bold font-heading mb-1 text-dark">You're All Caught Up!</h4>
                            <p class="text-secondary small mb-3">You are connected with or have reached out to all recommended peers in your department.</p>
                            <a href="<?= e(BASE_URL) ?>/directory.php" class="btn btn-primary rounded-pill px-4">
                                Browse All Alumni
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Load Networking Client Script -->
<script src="<?= e(BASE_URL) ?>/assets/js/network.js?v=<?= APP_VERSION ?>"></script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
