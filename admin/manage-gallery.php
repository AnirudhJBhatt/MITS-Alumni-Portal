<?php
/**
 * Administrator Gallery & Event Photos Management
 * Unified Event Creation & Gallery Photo Upload with Event Editing
 * MITS Connect
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_check.php';

$current_user_id = current_user_id();
$page_title = 'Manage Event Gallery';
$current_page = 'admin';

/**
 * Helper: Process event cover image upload or URL
 */
function process_cover_upload($cover_file_array, $cover_url = '', $old_cover = '') {
    $final_cover = $old_cover;
    $allowed_mimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    if (!empty($cover_file_array) && isset($cover_file_array['error']) && $cover_file_array['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $cover_file_array['tmp_name'];
        $file_size = $cover_file_array['size'];

        if ($file_size <= MAX_UPLOAD_SIZE) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);

            if (array_key_exists($mime_type, $allowed_mimes)) {
                $ext = $allowed_mimes[$mime_type];
                $filename = 'event_cover_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $target_path = UPLOAD_DIR . '/gallery/' . $filename;
                if (move_uploaded_file($file_tmp, $target_path)) {
                    // Remove old local cover if updating
                    if (!empty($old_cover) && strpos($old_cover, BASE_URL . '/uploads/gallery/') === 0) {
                        $old_filename = basename(str_replace(BASE_URL . '/uploads/gallery/', '', $old_cover));
                        $old_path = UPLOAD_DIR . '/gallery/' . $old_filename;
                        if (file_exists($old_path)) {
                            @unlink($old_path);
                        }
                    }
                    $final_cover = BASE_URL . '/uploads/gallery/' . $filename;
                }
            }
        }
    } elseif (!empty($cover_url)) {
        $final_cover = $cover_url;
    }

    return $final_cover;
}

/**
 * Helper: Process multiple photo uploads or direct URL to an event
 */
function process_photo_uploads($conn, $event_id, $current_user_id, $files_array, $caption = '', $photo_url = '') {
    $uploaded_count = 0;
    $allowed_mimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    // 1. Process Multi-File Uploads
    if (!empty($files_array['name']) && is_array($files_array['name'])) {
        $total_files = count($files_array['name']);
        for ($i = 0; $i < $total_files; $i++) {
            if ($files_array['error'][$i] === UPLOAD_ERR_OK) {
                $file_tmp = $files_array['tmp_name'][$i];
                $file_size = $files_array['size'][$i];

                if ($file_size <= MAX_UPLOAD_SIZE) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $file_tmp);
                    finfo_close($finfo);

                    if (array_key_exists($mime_type, $allowed_mimes)) {
                        $ext = $allowed_mimes[$mime_type];
                        $filename = 'photo_' . $event_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                        $target_path = UPLOAD_DIR . '/gallery/' . $filename;

                        if (move_uploaded_file($file_tmp, $target_path)) {
                            $url = BASE_URL . '/uploads/gallery/' . $filename;
                            $stmt = db_execute($conn,
                                "INSERT INTO event_photos (event_id, photo_url, caption, uploaded_by) VALUES (?, ?, ?, ?)",
                                "issi",
                                [$event_id, $url, $caption, $current_user_id]
                            );
                            if ($stmt) {
                                mysqli_stmt_close($stmt);
                                $uploaded_count++;
                            }
                        }
                    }
                }
            }
        }
    }

    // 2. Process Direct Image URL if supplied
    if (!empty($photo_url)) {
        $stmt = db_execute($conn,
            "INSERT INTO event_photos (event_id, photo_url, caption, uploaded_by) VALUES (?, ?, ?, ?)",
            "issi",
            [$event_id, $photo_url, $caption, $current_user_id]
        );
        if ($stmt) {
            mysqli_stmt_close($stmt);
            $uploaded_count++;
        }
    }

    return $uploaded_count;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Security validation failed (CSRF token mismatch). Please try again.');
        header('Location: ' . BASE_URL . '/admin/manage-gallery.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // ==============================================================
    // Action 1: Create a New Event (With Optional Multi-Photos Together)
    // ==============================================================
    if ($action === 'create_event') {
        $title = sanitize_input($_POST['title'] ?? '');
        $event_date = sanitize_input($_POST['event_date'] ?? '');
        $venue = sanitize_input($_POST['venue'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $cover_url = sanitize_input($_POST['cover_url'] ?? '');
        $photo_caption = sanitize_input($_POST['photo_caption'] ?? '');
        $photo_url_input = sanitize_input($_POST['photo_url'] ?? '');

        if (empty($title) || empty($event_date)) {
            set_flash('danger', 'Event title and event date are required fields.');
            header('Location: ' . BASE_URL . '/admin/manage-gallery.php');
            exit;
        }

        // Process cover photo
        $cover_image = process_cover_upload($_FILES['cover_file'] ?? null, $cover_url);

        $stmt = db_execute($conn,
            "INSERT INTO events (title, description, event_date, venue, cover_image, created_by) VALUES (?, ?, ?, ?, ?, ?)",
            "sssssi",
            [$title, $description, $event_date, $venue, $cover_image, $current_user_id]
        );

        if ($stmt) {
            $new_event_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Process simultaneously uploaded gallery photos
            $uploaded_photos_count = 0;
            if ($new_event_id && ((isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) || !empty($photo_url_input))) {
                $uploaded_photos_count = process_photo_uploads(
                    $conn,
                    $new_event_id,
                    $current_user_id,
                    $_FILES['photos'] ?? [],
                    $photo_caption,
                    $photo_url_input
                );
            }

            if ($uploaded_photos_count > 0) {
                set_flash('success', 'Event "' . e($title) . '" and ' . $uploaded_photos_count . ' gallery photo(s) were successfully created!');
            } else {
                set_flash('success', 'Event "' . e($title) . '" created successfully! You can now add photos to it.');
            }

            header('Location: ' . BASE_URL . '/admin/manage-gallery.php?event_id=' . $new_event_id);
            exit;
        } else {
            set_flash('danger', 'Failed to create event. Please verify your inputs and try again.');
            header('Location: ' . BASE_URL . '/admin/manage-gallery.php');
            exit;
        }
    }

    // ==============================================================
    // Action 2: Update an Existing Event (Edit Event Details)
    // ==============================================================
    if ($action === 'update_event') {
        $event_id = (int)($_POST['event_id'] ?? 0);
        $title = sanitize_input($_POST['title'] ?? '');
        $event_date = sanitize_input($_POST['event_date'] ?? '');
        $venue = sanitize_input($_POST['venue'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $cover_url = sanitize_input($_POST['cover_url'] ?? '');

        // Verify event exists
        $event = db_fetch_one($conn, "SELECT * FROM events WHERE id = ?", "i", [$event_id]);
        if (!$event) {
            set_flash('danger', 'Selected event album does not exist.');
            header('Location: ' . BASE_URL . '/admin/manage-gallery.php');
            exit;
        }

        if (empty($title) || empty($event_date)) {
            set_flash('danger', 'Event title and event date are required fields.');
            header('Location: ' . BASE_URL . '/admin/manage-gallery.php?event_id=' . $event_id);
            exit;
        }

        // Handle cover image replacement or retention
        $final_cover = process_cover_upload($_FILES['cover_file'] ?? null, $cover_url, $event['cover_image']);

        $stmt = db_execute($conn,
            "UPDATE events SET title = ?, description = ?, event_date = ?, venue = ?, cover_image = ? WHERE id = ?",
            "sssssi",
            [$title, $description, $event_date, $venue, $final_cover, $event_id]
        );

        if ($stmt) {
            mysqli_stmt_close($stmt);
            set_flash('success', 'Event "' . e($title) . '" details updated successfully!');
        } else {
            set_flash('danger', 'Failed to update event details.');
        }

        header('Location: ' . BASE_URL . '/admin/manage-gallery.php?event_id=' . $event_id);
        exit;
    }

    // ==============================================================
    // Action 3: Upload Photos to an Existing Event
    // ==============================================================
    if ($action === 'upload_photos') {
        $event_id = (int)($_POST['event_id'] ?? 0);
        $caption = sanitize_input($_POST['caption'] ?? '');
        $photo_url_input = sanitize_input($_POST['photo_url'] ?? '');

        $event = db_fetch_one($conn, "SELECT id, title FROM events WHERE id = ?", "i", [$event_id]);
        if (!$event) {
            set_flash('danger', 'Selected event does not exist.');
            header('Location: ' . BASE_URL . '/admin/manage-gallery.php');
            exit;
        }

        $uploaded_count = process_photo_uploads($conn, $event_id, $current_user_id, $_FILES['photos'] ?? [], $caption, $photo_url_input);

        if ($uploaded_count > 0) {
            set_flash('success', "Successfully added $uploaded_count photo(s) to \"{$event['title']}\".");
        } else {
            set_flash('warning', 'No valid photos were uploaded. Ensure files are JPG, PNG, or WebP under 5 MB.');
        }

        header('Location: ' . BASE_URL . '/admin/manage-gallery.php?event_id=' . $event_id);
        exit;
    }

    // ==============================================================
    // Action 4: Delete a Specific Photo
    // ==============================================================
    if ($action === 'delete_photo') {
        $photo_id = (int)($_POST['photo_id'] ?? 0);
        $photo = db_fetch_one($conn, "SELECT * FROM event_photos WHERE id = ?", "i", [$photo_id]);

        if ($photo) {
            // Delete local file if it resides in uploads/gallery
            if (strpos($photo['photo_url'], BASE_URL . '/uploads/gallery/') === 0) {
                $relative_filename = str_replace(BASE_URL . '/uploads/gallery/', '', $photo['photo_url']);
                $filepath = UPLOAD_DIR . '/gallery/' . basename($relative_filename);
                if (file_exists($filepath)) {
                    @unlink($filepath);
                }
            }

            $stmt = db_execute($conn, "DELETE FROM event_photos WHERE id = ?", "i", [$photo_id]);
            if ($stmt) {
                mysqli_stmt_close($stmt);
            }
            set_flash('success', 'Photo removed successfully from gallery.');
        }

        header('Location: ' . BASE_URL . '/admin/manage-gallery.php' . (!empty($_POST['redirect_event']) ? '?event_id=' . (int)$_POST['redirect_event'] : ''));
        exit;
    }

    // ==============================================================
    // Action 5: Delete an Event and all its photos
    // ==============================================================
    if ($action === 'delete_event') {
        $event_id = (int)($_POST['event_id'] ?? 0);
        $event = db_fetch_one($conn, "SELECT * FROM events WHERE id = ?", "i", [$event_id]);

        if ($event) {
            // Remove photos files
            $photos = db_fetch_all($conn, "SELECT photo_url FROM event_photos WHERE event_id = ?", "i", [$event_id]);
            foreach ($photos as $p) {
                if (strpos($p['photo_url'], BASE_URL . '/uploads/gallery/') === 0) {
                    $relative_filename = str_replace(BASE_URL . '/uploads/gallery/', '', $p['photo_url']);
                    $filepath = UPLOAD_DIR . '/gallery/' . basename($relative_filename);
                    if (file_exists($filepath)) {
                        @unlink($filepath);
                    }
                }
            }

            // Remove cover image if local
            if (!empty($event['cover_image']) && strpos($event['cover_image'], BASE_URL . '/uploads/gallery/') === 0) {
                $relative_filename = str_replace(BASE_URL . '/uploads/gallery/', '', $event['cover_image']);
                $filepath = UPLOAD_DIR . '/gallery/' . basename($relative_filename);
                if (file_exists($filepath)) {
                    @unlink($filepath);
                }
            }

            $stmt = db_execute($conn, "DELETE FROM events WHERE id = ?", "i", [$event_id]);
            if ($stmt) {
                mysqli_stmt_close($stmt);
            }
            set_flash('success', 'Event "' . e($event['title']) . '" and all its photos were permanently deleted.');
        }

        header('Location: ' . BASE_URL . '/admin/manage-gallery.php');
        exit;
    }
}

// Fetch all events with photo count
$events_sql = "SELECT e.*, COUNT(p.id) as photos_count 
               FROM events e 
               LEFT JOIN event_photos p ON e.id = p.event_id 
               GROUP BY e.id 
               ORDER BY e.event_date DESC";
$events = db_fetch_all($conn, $events_sql);

// Compute overall statistics
$total_events = count($events);
$total_photos = 0;
foreach ($events as $ev) {
    $total_photos += (int)$ev['photos_count'];
}
$events_with_photos = count(array_filter($events, fn($e) => (int)$e['photos_count'] > 0));

// Selected event filter for photo review
$selected_event_id = (int)($_GET['event_id'] ?? 0);

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Header with Breadcrumbs & Action Toolbar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center border-bottom pb-3 mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-danger text-white rounded-pill px-3 py-1">
                    <i class="bi bi-images me-1"></i>Gallery Moderation
                </span>
                <span class="text-secondary small">Events, Albums & Photos</span>
            </div>
            <h2 class="fw-bold font-heading mb-0 text-dark">Event & Gallery Management</h2>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="#createSection" class="btn btn-primary rounded-pill px-3 shadow-sm">
                <i class="bi bi-plus-circle me-1"></i>Create Event & Album
            </a>
            <a href="<?= e(BASE_URL) ?>/gallery" target="_blank" class="btn btn-outline-danger rounded-pill px-3">
                <i class="bi bi-eye me-1"></i>Public Gallery
            </a>
            <a href="<?= e(BASE_URL) ?>/admin/" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i>Dashboard
            </a>
        </div>
    </div>

    <!-- Quick Stats Cards Row -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-modern border-0 shadow-sm p-3 bg-white d-flex flex-row align-items-center gap-3">
                <div class="stat-icon bg-primary-subtle text-primary mb-0" style="width: 48px; height: 48px; font-size: 1.3rem;">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-dark"><?= $total_events ?></h3>
                    <span class="text-secondary small">Total Event Albums</span>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-modern border-0 shadow-sm p-3 bg-white d-flex flex-row align-items-center gap-3">
                <div class="stat-icon bg-success-subtle text-success mb-0" style="width: 48px; height: 48px; font-size: 1.3rem;">
                    <i class="bi bi-images"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-dark"><?= $total_photos ?></h3>
                    <span class="text-secondary small">Total Gallery Photos</span>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-modern border-0 shadow-sm p-3 bg-white d-flex flex-row align-items-center gap-3">
                <div class="stat-icon bg-info-subtle text-info mb-0" style="width: 48px; height: 48px; font-size: 1.3rem;">
                    <i class="bi bi-collection"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-dark"><?= $events_with_photos ?></h3>
                    <span class="text-secondary small">Active Albums</span>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-modern border-0 shadow-sm p-3 bg-white d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="fw-bold text-dark d-block">Quick Photos Add</span>
                    <span class="text-secondary small">Append to an album</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addPhotosModal">
                    <i class="bi bi-cloud-arrow-up me-1"></i>Upload
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- Unified Section: Create Event & Gallery Photos Together        -->
    <!-- ============================================================== -->
    <div id="createSection" class="card card-modern border-0 shadow-sm mb-5 bg-white overflow-hidden">
        <div class="p-4 bg-light-subtle border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary text-white mb-0" style="width: 44px; height: 44px; font-size: 1.25rem;">
                    <i class="bi bi-camera-reels"></i>
                </div>
                <div>
                    <h4 class="fw-bold font-heading mb-0 text-dark">Create Event & Upload Gallery Photos</h4>
                    <span class="text-secondary small">Set up the event details and immediately attach multiple gallery memories in one step</span>
                </div>
            </div>
            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 fw-semibold">
                Unified Event Flow
            </span>
        </div>

        <div class="p-4 p-lg-5">
            <form action="<?= e(BASE_URL) ?>/admin/manage-gallery.php" method="POST" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="create_event">

                <div class="row g-4">
                    <!-- Column 1: Event Information -->
                    <div class="col-lg-6 pe-lg-4 border-end-lg">
                        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                            <span class="badge bg-danger rounded-circle p-1" style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;">1</span>
                            <h5 class="fw-bold text-dark font-heading mb-0">Event Information</h5>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-secondary">Event Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control rounded-3 py-2" placeholder="e.g. OSAM Annual Alumni Reunion 2026" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small text-secondary">Event Date <span class="text-danger">*</span></label>
                                <input type="date" name="event_date" class="form-control rounded-3 py-2" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small text-secondary">Venue / Location</label>
                                <input type="text" name="venue" class="form-control rounded-3 py-2" placeholder="e.g. MITS Main Auditorium, Varikoli">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-secondary">Event Description / Highlights</label>
                            <textarea name="description" rows="3" class="form-control rounded-3" placeholder="Brief summary of the celebration, speeches, batch meets, or milestone events..."></textarea>
                        </div>

                        <div class="row g-2">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small text-secondary">Cover Photo File</label>
                                <input type="file" name="cover_file" class="form-control rounded-3" accept="image/jpeg,image/png,image/webp">
                                <div class="form-text small">Upload main banner (JPG, PNG, WebP)</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold small text-secondary">Or Cover Image URL</label>
                                <input type="url" name="cover_url" class="form-control rounded-3" placeholder="https://images.unsplash.com/...">
                                <div class="form-text small">Direct CDN or web image URL</div>
                            </div>
                        </div>
                    </div>

                    <!-- Column 2: Gallery Photos Multi-Upload -->
                    <div class="col-lg-6 ps-lg-4">
                        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                            <span class="badge bg-danger rounded-circle p-1" style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;">2</span>
                            <h5 class="fw-bold text-dark font-heading mb-0">Gallery Photos (Optional / Simultaneous)</h5>
                        </div>

                        <!-- Dropzone styled container -->
                        <div class="border-2 border-dashed rounded-4 p-4 text-center bg-light-subtle mb-3 position-relative">
                            <i class="bi bi-cloud-arrow-up display-5 text-primary mb-2 d-block"></i>
                            <h6 class="fw-bold text-dark mb-1">Select Event Memories & Group Photos</h6>
                            <p class="text-secondary small mb-3">Choose multiple images from your computer to attach to this event album</p>

                            <input type="file" name="photos[]" class="form-control rounded-3 multi-photo-input" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
                            <div class="mt-2 text-start">
                                <span class="badge bg-primary text-white d-none file-count-badge py-2 px-3 rounded-pill"></span>
                            </div>
                            <div class="form-text small text-muted mt-2">
                                <i class="bi bi-info-circle me-1"></i>Supports JPG, PNG, WebP, GIF (up to 5 MB per file). Multiple selection allowed.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-secondary">Default Caption / Tag for Uploaded Photos</label>
                            <input type="text" name="photo_caption" class="form-control rounded-3 py-2" placeholder="e.g. Batch of 2021 celebration, Keynote address">
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold small text-secondary">Or Attach Single Photo by Web URL</label>
                            <input type="url" name="photo_url" class="form-control rounded-3 py-2" placeholder="https://example.com/photo.jpg">
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Submit Bar -->
                <div class="d-flex flex-column flex-sm-row justify-content-end align-items-center gap-3">
                    <button type="reset" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="bi bi-x-circle me-1"></i>Reset Form
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm fw-semibold">
                        <i class="bi bi-check2-circle me-2"></i>Create Event & Save Album
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- Event Galleries & Photo Browser                                 -->
    <!-- ============================================================== -->
    <div class="card card-modern border-0 shadow-sm p-4 bg-white">
        <!-- Section Header with Search Filter -->
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center pb-3 mb-4 border-bottom gap-3">
            <div>
                <h4 class="fw-bold text-dark font-heading mb-0">Event Galleries & Photos</h4>
                <span class="text-secondary small">Review, edit, add photos, or manage created albums</span>
            </div>

            <!-- Instant Search Input -->
            <div class="d-flex align-items-center gap-2">
                <div class="input-group" style="min-width: 260px;">
                    <span class="input-group-text bg-white border-end-0 rounded-start-pill"><i class="bi bi-search text-secondary"></i></span>
                    <input type="text" id="albumSearchInput" class="form-control border-start-0 rounded-end-pill" placeholder="Filter albums by title or venue...">
                </div>
                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small flex-shrink-0">
                    <?= count($events) ?> Albums
                </span>
            </div>
        </div>

        <div id="noSearchMatch" class="alert alert-info d-none text-center rounded-3">
            <i class="bi bi-info-circle me-1"></i>No event albums match your search query.
        </div>

        <?php if (empty($events)): ?>
            <div class="text-center py-5 text-secondary">
                <i class="bi bi-images display-3 text-muted d-block mb-3"></i>
                <h5 class="fw-bold text-dark">No Event Albums Created Yet</h5>
                <p class="mb-3 text-secondary">Use the unified form above to create your first event album and attach gallery photos.</p>
                <a href="#createSection" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-plus-circle me-1"></i>Create First Event
                </a>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-4" id="albumListContainer">
                <?php foreach ($events as $ev): 
                    $event_photos = db_fetch_all($conn, "SELECT * FROM event_photos WHERE event_id = ? ORDER BY created_at DESC", "i", [$ev['id']]);
                ?>
                    <div class="border rounded-4 p-4 bg-light-subtle position-relative event-album-card shadow-xs"
                         data-title="<?= strtolower(e($ev['title'])) ?>"
                         data-venue="<?= strtolower(e($ev['venue'] ?? '')) ?>"
                         id="event-card-<?= $ev['id'] ?>">

                        <!-- Album Top Header Row -->
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-3">
                            <div class="d-flex gap-3 align-items-start">
                                <!-- Cover Image -->
                                <?php if (!empty($ev['cover_image'])): ?>
                                    <img src="<?= e($ev['cover_image']) ?>" alt="Cover" class="rounded-3 object-fit-cover shadow-sm flex-shrink-0" style="width: 90px; height: 68px;">
                                <?php else: ?>
                                    <div class="rounded-3 bg-secondary-subtle d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 90px; height: 68px;">
                                        <i class="bi bi-image fs-3"></i>
                                    </div>
                                <?php endif; ?>

                                <div>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <h5 class="fw-bold text-dark mb-0 font-heading"><?= e($ev['title']) ?></h5>
                                        <span class="badge bg-danger-subtle text-danger fw-semibold rounded-pill px-2">
                                            <?= count($event_photos) ?> Photos
                                        </span>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 align-items-center text-secondary small mb-2">
                                        <span><i class="bi bi-calendar3 me-1 text-danger"></i><?= date('d M Y', strtotime($ev['event_date'])) ?></span>
                                        <?php if (!empty($ev['venue'])): ?>
                                            <span>&bull;</span>
                                            <span><i class="bi bi-geo-alt me-1 text-danger"></i><?= e($ev['venue']) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($ev['description'])): ?>
                                        <p class="text-secondary small mb-0 pe-lg-4" style="line-height: 1.6;"><?= e($ev['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Action Toolbar for Event -->
                            <div class="d-flex flex-wrap gap-2 align-items-center flex-shrink-0">
                                <!-- Edit Event Button (Triggers Modal) -->
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary rounded-pill px-3 btn-edit-event"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editEventModal"
                                        data-event-id="<?= $ev['id'] ?>"
                                        data-title="<?= htmlspecialchars($ev['title'], ENT_QUOTES) ?>"
                                        data-date="<?= htmlspecialchars($ev['event_date'], ENT_QUOTES) ?>"
                                        data-venue="<?= htmlspecialchars($ev['venue'] ?? '', ENT_QUOTES) ?>"
                                        data-description="<?= htmlspecialchars($ev['description'] ?? '', ENT_QUOTES) ?>"
                                        data-cover="<?= htmlspecialchars($ev['cover_image'] ?? '', ENT_QUOTES) ?>">
                                    <i class="bi bi-pencil-square me-1"></i>Edit Event
                                </button>

                                <!-- Add Photos Button (Triggers Modal) -->
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-add-photos"
                                        data-bs-toggle="modal"
                                        data-bs-target="#addPhotosModal"
                                        data-event-id="<?= $ev['id'] ?>"
                                        data-event-title="<?= htmlspecialchars($ev['title'], ENT_QUOTES) ?>">
                                    <i class="bi bi-cloud-arrow-up me-1"></i>Add Photos
                                </button>

                                <!-- View in Public Gallery Link -->
                                <a href="<?= e(BASE_URL) ?>/gallery#event-<?= $ev['id'] ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-3" title="View in public gallery">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>View
                                </a>

                                <!-- Delete Event Form -->
                                <form action="<?= e(BASE_URL) ?>/admin/manage-gallery.php" method="POST" onsubmit="return confirm('Permanently delete event album &quot;<?= e(addslashes($ev['title'])) ?>&quot; and all <?= count($event_photos) ?> photos?');" class="d-inline">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete_event">
                                    <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Photos Grid for this Event -->
                        <?php if (empty($event_photos)): ?>
                            <div class="p-3 bg-white rounded-3 border text-center text-secondary small d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2">
                                <div>
                                    <i class="bi bi-image me-1 text-muted"></i>No photos uploaded to this album yet.
                                </div>
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addPhotosModal" data-event-id="<?= $ev['id'] ?>" data-event-title="<?= htmlspecialchars($ev['title'], ENT_QUOTES) ?>">
                                    <i class="bi bi-plus-circle me-1"></i>Upload Photos Now
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="row g-2 row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6">
                                <?php foreach ($event_photos as $ph): ?>
                                    <div class="col">
                                        <div class="card h-100 border rounded-3 overflow-hidden shadow-xs position-relative group-photo-card bg-white">
                                            <a href="<?= e($ph['photo_url']) ?>" target="_blank" class="d-block">
                                                <img src="<?= e($ph['photo_url']) ?>" alt="Photo" class="w-100 object-fit-cover" style="height: 110px;" loading="lazy">
                                            </a>
                                            
                                            <?php if (!empty($ph['caption'])): ?>
                                                <div class="p-1 px-2 bg-white small text-truncate text-secondary border-top" style="font-size: 0.72rem;" title="<?= e($ph['caption']) ?>">
                                                    <?= e($ph['caption']) ?>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Delete Photo Button -->
                                            <form action="<?= e(BASE_URL) ?>/admin/manage-gallery.php" method="POST" onsubmit="return confirm('Delete this photo?');" class="position-absolute top-0 end-0 m-1">
                                                <?= csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete_photo">
                                                <input type="hidden" name="photo_id" value="<?= $ph['id'] ?>">
                                                <input type="hidden" name="redirect_event" value="<?= $ev['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm rounded-circle p-1 shadow-sm" style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;" title="Delete Photo">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================== -->
<!-- Modal 1: Edit Event Details                                    -->
<!-- ============================================================== -->
<div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom pb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="stat-icon bg-primary-subtle text-primary mb-0" style="width: 36px; height: 36px; font-size: 1rem;">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark font-heading mb-0" id="editEventModalLabel">Edit Event Details</h5>
                        <small class="text-secondary">Update title, date, venue, or cover photo</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="<?= e(BASE_URL) ?>/admin/manage-gallery.php" method="POST" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="update_event">
                <input type="hidden" name="event_id" id="edit_event_id" value="">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Event Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="edit_title" class="form-control rounded-3 py-2" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small text-secondary">Event Date <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" id="edit_event_date" class="form-control rounded-3 py-2" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small text-secondary">Venue / Location</label>
                            <input type="text" name="venue" id="edit_venue" class="form-control rounded-3 py-2">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Event Description</label>
                        <textarea name="description" id="edit_description" rows="3" class="form-control rounded-3"></textarea>
                    </div>

                    <!-- Current Cover Display -->
                    <div id="edit_cover_preview_wrapper" class="p-3 bg-light-subtle rounded-3 border mb-3 d-none">
                        <label class="form-label fw-semibold small text-secondary mb-2 d-block">Current Cover Photo</label>
                        <div class="d-flex align-items-center gap-3">
                            <img id="edit_current_cover_img" src="" alt="Current Cover" class="rounded-3 object-fit-cover shadow-sm" style="width: 100px; height: 60px;">
                            <small class="text-muted">Upload a new file below only if you wish to replace this cover image.</small>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small text-secondary">Replace Cover File (Optional)</label>
                            <input type="file" name="cover_file" id="edit_cover_file" class="form-control rounded-3" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small text-secondary">Or Replace with Image URL</label>
                            <input type="url" name="cover_url" id="edit_cover_url" class="form-control rounded-3" placeholder="https://example.com/cover.jpg">
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top p-3 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold">
                        <i class="bi bi-save me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- Modal 2: Add Photos to Existing Event Album                    -->
<!-- ============================================================== -->
<div class="modal fade" id="addPhotosModal" tabindex="-1" aria-labelledby="addPhotosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom pb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="stat-icon bg-success-subtle text-success mb-0" style="width: 36px; height: 36px; font-size: 1rem;">
                        <i class="bi bi-cloud-arrow-up"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark font-heading mb-0" id="addPhotosModalLabel">Add Photos to Event Album</h5>
                        <small class="text-secondary">Upload multiple photos or specify photo link</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="<?= e(BASE_URL) ?>/admin/manage-gallery.php" method="POST" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="upload_photos">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Target Event Album <span class="text-danger">*</span></label>
                        <select name="event_id" id="modal_event_id" class="form-select rounded-3 py-2" required>
                            <?php foreach ($events as $ev): ?>
                                <option value="<?= $ev['id'] ?>" <?= ($selected_event_id === (int)$ev['id']) ? 'selected' : '' ?>>
                                    <?= e($ev['title']) ?> (<?= date('M Y', strtotime($ev['event_date'])) ?>) — <?= $ev['photos_count'] ?> photos
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Dropzone upload -->
                    <div class="border-2 border-dashed rounded-4 p-4 text-center bg-light-subtle mb-3">
                        <i class="bi bi-images display-6 text-success mb-2 d-block"></i>
                        <h6 class="fw-bold text-dark mb-1">Choose Images from Computer</h6>
                        <input type="file" name="photos[]" class="form-control rounded-3 multi-photo-input" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
                        <div class="mt-2 text-start">
                            <span class="badge bg-success text-white d-none file-count-badge py-2 px-3 rounded-pill"></span>
                        </div>
                        <div class="form-text small text-muted mt-2">
                            Select one or multiple images (JPG, PNG, WebP up to 5 MB each).
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Optional Caption / Tag</label>
                        <input type="text" name="caption" class="form-control rounded-3 py-2" placeholder="e.g. Batch of 2024 group photograph, Keynote Address">
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold small text-secondary">Or Add Single Photo by URL</label>
                        <input type="url" name="photo_url" class="form-control rounded-3 py-2" placeholder="https://example.com/event-photo.jpg">
                    </div>
                </div>

                <div class="modal-footer border-top p-3 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm fw-semibold">
                        <i class="bi bi-upload me-1"></i>Upload & Save Photos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- Dynamic Interactive Scripts                                    -->
<!-- ============================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Populate Edit Event Modal with data from clicked button
    const editModal = document.getElementById('editEventModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            if (!btn) return;

            const eventId = btn.getAttribute('data-event-id') || '';
            const title = btn.getAttribute('data-title') || '';
            const date = btn.getAttribute('data-date') || '';
            const venue = btn.getAttribute('data-venue') || '';
            const description = btn.getAttribute('data-description') || '';
            const cover = btn.getAttribute('data-cover') || '';

            editModal.querySelector('#edit_event_id').value = eventId;
            editModal.querySelector('#edit_title').value = title;
            editModal.querySelector('#edit_event_date').value = date;
            editModal.querySelector('#edit_venue').value = venue;
            editModal.querySelector('#edit_description').value = description;
            editModal.querySelector('#edit_cover_url').value = '';
            editModal.querySelector('#edit_cover_file').value = '';

            const previewWrapper = editModal.querySelector('#edit_cover_preview_wrapper');
            const coverImg = editModal.querySelector('#edit_current_cover_img');

            if (cover && cover.trim() !== '') {
                coverImg.src = cover;
                previewWrapper.classList.remove('d-none');
            } else {
                previewWrapper.classList.add('d-none');
            }
        });
    }

    // 2. Preselect Target Event in Add Photos Modal
    const addPhotosModal = document.getElementById('addPhotosModal');
    if (addPhotosModal) {
        addPhotosModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            if (!btn) return;

            const eventId = btn.getAttribute('data-event-id');
            if (eventId) {
                const select = addPhotosModal.querySelector('#modal_event_id');
                if (select) {
                    select.value = eventId;
                }
            }
        });
    }

    // 3. Multi-Photo Live File Counter Badge
    document.querySelectorAll('.multi-photo-input').forEach(function (input) {
        input.addEventListener('change', function () {
            const form = this.closest('form') || this.parentElement;
            const badge = form.querySelector('.file-count-badge');
            if (!badge) return;

            if (this.files && this.files.length > 0) {
                let totalBytes = 0;
                for (let i = 0; i < this.files.length; i++) {
                    totalBytes += this.files[i].size;
                }
                const mb = (totalBytes / (1024 * 1024)).toFixed(1);
                badge.textContent = `${this.files.length} photo(s) selected (${mb} MB total)`;
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }
        });
    });

    // 4. Instant Live Search Filter for Albums
    const albumSearchInput = document.getElementById('albumSearchInput');
    if (albumSearchInput) {
        albumSearchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.event-album-card');
            let visibleCount = 0;

            cards.forEach(function (card) {
                const title = card.getAttribute('data-title') || '';
                const venue = card.getAttribute('data-venue') || '';
                if (query === '' || title.includes(query) || venue.includes(query)) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            const noMatch = document.getElementById('noSearchMatch');
            if (noMatch) {
                if (visibleCount === 0 && query !== '') {
                    noMatch.classList.remove('d-none');
                } else {
                    noMatch.classList.add('d-none');
                }
            }
        });
    }
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
