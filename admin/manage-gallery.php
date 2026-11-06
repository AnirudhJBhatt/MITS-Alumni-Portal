<?php
/**
 * Administrator Gallery & Event Photos Management
 * MITS Connect
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_check.php';

$current_user_id = current_user_id();
$page_title = 'Manage Event Gallery';
$current_page = 'admin';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Security validation failed (CSRF token mismatch). Please try again.');
        header('Location: ' . BASE_URL . '/admin/manage-gallery.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Action 1: Create a New Event
    if ($action === 'create_event') {
        $title = sanitize_input($_POST['title'] ?? '');
        $event_date = sanitize_input($_POST['event_date'] ?? '');
        $venue = sanitize_input($_POST['venue'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $cover_url = sanitize_input($_POST['cover_url'] ?? '');

        if (empty($title) || empty($event_date)) {
            set_flash('danger', 'Event title and event date are required.');
            header('Location: ' . BASE_URL . '/admin/manage-gallery.php');
            exit;
        }

        // Handle optional cover image file upload
        if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['cover_file']['tmp_name'];
            $file_size = $_FILES['cover_file']['size'];

            if ($file_size > MAX_UPLOAD_SIZE) {
                set_flash('danger', 'Cover image exceeds 5 MB limit.');
                header('Location: ' . BASE_URL . '/admin/manage-gallery.php');
                exit;
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);

            $allowed_mimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (array_key_exists($mime_type, $allowed_mimes)) {
                $ext = $allowed_mimes[$mime_type];
                $filename = 'event_cover_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $target_path = UPLOAD_DIR . '/gallery/' . $filename;
                if (move_uploaded_file($file_tmp, $target_path)) {
                    $cover_url = BASE_URL . '/uploads/gallery/' . $filename;
                }
            }
        }

        $stmt = db_execute($conn,
            "INSERT INTO events (title, description, event_date, venue, cover_image, created_by) VALUES (?, ?, ?, ?, ?, ?)",
            "sssssi",
            [$title, $description, $event_date, $venue, $cover_url, $current_user_id]
        );

        if ($stmt) {
            set_flash('success', 'Event "' . e($title) . '" was created successfully! You can now add photos to it.');
        } else {
            set_flash('danger', 'Failed to create event. Please try again.');
        }

        header('Location: ' . BASE_URL . '/admin/manage-gallery.php');
        exit;
    }

    // Action 2: Upload/Add Photos to an Event
    if ($action === 'upload_photos') {
        $event_id = (int)($_POST['event_id'] ?? 0);
        $caption = sanitize_input($_POST['caption'] ?? '');
        $photo_url_input = sanitize_input($_POST['photo_url'] ?? '');

        // Verify event exists
        $event = db_fetch_one($conn, "SELECT id, title FROM events WHERE id = ?", "i", [$event_id]);
        if (!$event) {
            set_flash('danger', 'Selected event does not exist.');
            header('Location: ' . BASE_URL . '/admin/manage-gallery.php');
            exit;
        }

        $uploaded_count = 0;
        $allowed_mimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

        // 1. Process File Uploads (Multiple files support)
        if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
            $total_files = count($_FILES['photos']['name']);
            for ($i = 0; $i < $total_files; $i++) {
                if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_tmp = $_FILES['photos']['tmp_name'][$i];
                    $file_size = $_FILES['photos']['size'][$i];

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
                                db_execute($conn,
                                    "INSERT INTO event_photos (event_id, photo_url, caption, uploaded_by) VALUES (?, ?, ?, ?)",
                                    "issi",
                                    [$event_id, $url, $caption, $current_user_id]
                                );
                                $uploaded_count++;
                            }
                        }
                    }
                }
            }
        }

        // 2. Process Direct URL if provided
        if (!empty($photo_url_input)) {
            db_execute($conn,
                "INSERT INTO event_photos (event_id, photo_url, caption, uploaded_by) VALUES (?, ?, ?, ?)",
                "issi",
                [$event_id, $photo_url_input, $caption, $current_user_id]
            );
            $uploaded_count++;
        }

        if ($uploaded_count > 0) {
            set_flash('success', "Successfully added $uploaded_count photo(s) to \"{$event['title']}\".");
        } else {
            set_flash('warning', 'No valid photos were uploaded. Ensure files are JPG, PNG, or WebP under 5 MB.');
        }

        header('Location: ' . BASE_URL . '/admin/manage-gallery.php?event_id=' . $event_id);
        exit;
    }

    // Action 3: Delete a Specific Photo
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

            db_execute($conn, "DELETE FROM event_photos WHERE id = ?", "i", [$photo_id]);
            set_flash('success', 'Photo removed successfully.');
        }

        header('Location: ' . BASE_URL . '/admin/manage-gallery.php' . (!empty($_POST['redirect_event']) ? '?event_id=' . (int)$_POST['redirect_event'] : ''));
        exit;
    }

    // Action 4: Delete an Event and all its photos
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

            db_execute($conn, "DELETE FROM events WHERE id = ?", "i", [$event_id]);
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

// Selected event filter for photo review
$selected_event_id = (int)($_GET['event_id'] ?? 0);

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center border-bottom pb-3 mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-danger text-white rounded-pill px-3 py-1">
                    <i class="bi bi-images me-1"></i>Gallery Moderation
                </span>
                <span class="text-secondary small">Events & Albums</span>
            </div>
            <h2 class="fw-bold font-heading mb-0 text-dark">Manage Event Gallery</h2>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= e(BASE_URL) ?>/gallery" target="_blank" class="btn btn-outline-danger rounded-pill px-3">
                <i class="bi bi-eye me-1"></i>View Public Gallery
            </a>
            <a href="<?= e(BASE_URL) ?>/admin/" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i>Dashboard Overview
            </a>
        </div>
    </div>

    <!-- Dual Form Row: Create Event + Upload Photos -->
    <div class="row g-4 mb-5">
        <!-- Form 1: Create New Event -->
        <div class="col-lg-5">
            <div class="card card-modern border-0 shadow-sm p-4 bg-white h-100">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <div class="stat-icon bg-primary-subtle text-primary mb-0" style="width: 38px; height: 38px; font-size: 1.1rem;">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark font-heading mb-0">Create New Event</h5>
                        <span class="text-secondary small">Group gallery photos by occasion</span>
                    </div>
                </div>

                <form action="<?= e(BASE_URL) ?>/admin/manage-gallery.php" method="POST" enctype="multipart/form-data">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="action" value="create_event">

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Event Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control rounded-3" placeholder="e.g. OSAM Alumni Reunion 2026" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small text-secondary">Event Date <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small text-secondary">Venue / Location</label>
                            <input type="text" name="venue" class="form-control rounded-3" placeholder="e.g. MITS Campus">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Description</label>
                        <textarea name="description" rows="2" class="form-control rounded-3" placeholder="Brief event highlights or overview..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Cover Photo File (optional)</label>
                        <input type="file" name="cover_file" class="form-control rounded-3" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text small">Or provide image URL below</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-secondary">Cover Image URL (optional)</label>
                        <input type="url" name="cover_url" class="form-control rounded-3" placeholder="https://example.com/cover.jpg">
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill w-100 shadow-sm py-2">
                        <i class="bi bi-plus-circle me-1"></i>Create Event Album
                    </button>
                </form>
            </div>
        </div>

        <!-- Form 2: Upload Photos to Event -->
        <div class="col-lg-7">
            <div class="card card-modern border-0 shadow-sm p-4 bg-white h-100">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <div class="stat-icon bg-success-subtle text-success mb-0" style="width: 38px; height: 38px; font-size: 1.1rem;">
                        <i class="bi bi-cloud-arrow-up"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark font-heading mb-0">Add Event Photos</h5>
                        <span class="text-secondary small">Upload multiple images or group photos</span>
                    </div>
                </div>

                <?php if (empty($events)): ?>
                    <div class="alert alert-warning small rounded-3 mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>Please create an event album on the left first before uploading photos.
                    </div>
                <?php else: ?>
                    <form action="<?= e(BASE_URL) ?>/admin/manage-gallery.php" method="POST" enctype="multipart/form-data">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="upload_photos">

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-secondary">Select Event Album <span class="text-danger">*</span></label>
                            <select name="event_id" class="form-select rounded-3" required>
                                <?php foreach ($events as $ev): ?>
                                    <option value="<?= $ev['id'] ?>" <?= ($selected_event_id === (int)$ev['id']) ? 'selected' : '' ?>>
                                        <?= e($ev['title']) ?> (<?= date('M Y', strtotime($ev['event_date'])) ?>) — <?= $ev['photos_count'] ?> photos
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-secondary">Upload Photo(s) from Computer <span class="text-danger">*</span></label>
                            <input type="file" name="photos[]" class="form-control rounded-3" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
                            <div class="form-text small">Select one or multiple images (JPG, PNG, WebP up to 5 MB each).</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-secondary">Optional Caption / Tag</label>
                            <input type="text" name="caption" class="form-control rounded-3" placeholder="e.g. Batch of 2021 group photograph, Keynote Address">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-secondary">Or Add Single Photo by URL</label>
                            <input type="url" name="photo_url" class="form-control rounded-3" placeholder="https://example.com/event-photo.jpg">
                        </div>

                        <button type="submit" class="btn btn-success rounded-pill w-100 shadow-sm py-2">
                            <i class="bi bi-upload me-1"></i>Upload & Save to Event Gallery
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Event Galleries & Photo Browser -->
    <div class="card card-modern border-0 shadow-sm p-4 bg-white">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center pb-3 mb-4 border-bottom gap-2">
            <div>
                <h4 class="fw-bold text-dark font-heading mb-0">Event Galleries & Photos</h4>
                <span class="text-secondary small">Review uploaded photos grouped by event</span>
            </div>
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small">
                <?= count($events) ?> Total Event Albums
            </span>
        </div>

        <?php if (empty($events)): ?>
            <div class="text-center py-5 text-secondary">
                <i class="bi bi-images display-4 text-muted d-block mb-3"></i>
                <p class="mb-0">No event albums created yet. Use the form above to add your first event gallery!</p>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-4">
                <?php foreach ($events as $ev): 
                    $event_photos = db_fetch_all($conn, "SELECT * FROM event_photos WHERE event_id = ? ORDER BY created_at DESC", "i", [$ev['id']]);
                ?>
                    <div class="border rounded-4 p-4 bg-light-subtle position-relative">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-3">
                            <div class="d-flex gap-3 align-items-start">
                                <?php if (!empty($ev['cover_image'])): ?>
                                    <img src="<?= e($ev['cover_image']) ?>" alt="Cover" class="rounded-3 object-fit-cover shadow-sm flex-shrink-0" style="width: 80px; height: 60px;">
                                <?php else: ?>
                                    <div class="rounded-3 bg-secondary-subtle d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 80px; height: 60px;">
                                        <i class="bi bi-images fs-4"></i>
                                    </div>
                                <?php endif; ?>

                                <div>
                                    <h5 class="fw-bold text-dark mb-1 font-heading"><?= e($ev['title']) ?></h5>
                                    <div class="d-flex flex-wrap gap-2 align-items-center text-secondary small">
                                        <span><i class="bi bi-calendar3 me-1 text-danger"></i><?= date('d M Y', strtotime($ev['event_date'])) ?></span>
                                        <?php if (!empty($ev['venue'])): ?>
                                            <span>&bull;</span>
                                            <span><i class="bi bi-geo-alt me-1 text-danger"></i><?= e($ev['venue']) ?></span>
                                        <?php endif; ?>
                                        <span>&bull;</span>
                                        <span class="badge bg-danger-subtle text-danger fw-semibold rounded-pill px-2">
                                            <?= count($event_photos) ?> Photos
                                        </span>
                                    </div>
                                    <?php if (!empty($ev['description'])): ?>
                                        <p class="text-secondary small mt-2 mb-0"><?= e($ev['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-2 align-items-center">
                                <a href="<?= e(BASE_URL) ?>/admin/manage-gallery.php?event_id=<?= $ev['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    <i class="bi bi-plus-circle me-1"></i>Add Photos
                                </a>

                                <form action="<?= e(BASE_URL) ?>/admin/manage-gallery.php" method="POST" onsubmit="return confirm('Permanently delete event &quot;<?= e(addslashes($ev['title'])) ?>&quot; and all its photos?');">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete_event">
                                    <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                        <i class="bi bi-trash"></i> Delete Album
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Photos Grid for this Event -->
                        <?php if (empty($event_photos)): ?>
                            <div class="p-3 bg-white rounded-3 border text-center text-secondary small">
                                <i class="bi bi-image me-1"></i>No photos uploaded to this album yet. Click "Add Photos" above to upload photos.
                            </div>
                        <?php else: ?>
                            <div class="row g-3 row-cols-2 row-cols-md-4 row-cols-lg-6">
                                <?php foreach ($event_photos as $ph): ?>
                                    <div class="col">
                                        <div class="card h-100 border rounded-3 overflow-hidden shadow-xs position-relative group-photo-card">
                                            <img src="<?= e($ph['photo_url']) ?>" alt="Photo" class="w-100 object-fit-cover" style="height: 120px;" loading="lazy">
                                            
                                            <?php if (!empty($ph['caption'])): ?>
                                                <div class="p-1 px-2 bg-white small text-truncate text-secondary" style="font-size: 0.75rem;" title="<?= e($ph['caption']) ?>">
                                                    <?= e($ph['caption']) ?>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Delete Photo Button -->
                                            <form action="<?= e(BASE_URL) ?>/admin/manage-gallery.php" method="POST" onsubmit="return confirm('Delete this photo?');" class="position-absolute top-0 end-0 m-1">
                                                <?= csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete_photo">
                                                <input type="hidden" name="photo_id" value="<?= $ph['id'] ?>">
                                                <input type="hidden" name="redirect_event" value="<?= $ev['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm rounded-circle p-1" style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem;" title="Delete Photo">
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

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
