<?php
/**
 * Event Gallery Page - Grouped Event & Alumni Photos
 * MITS Connect
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Event Gallery & Campus Moments | MITS Connect';
$page_description = 'Explore memories, group photos, annual OSAM alumni meets, convocation milestones, and technical summits of Muthoot Institute of Technology and Science.';
$current_page = 'gallery';

// 1. Fetch all events with their photo count
$events_sql = "SELECT e.*, COUNT(p.id) as photos_count 
               FROM events e 
               LEFT JOIN event_photos p ON e.id = p.event_id 
               GROUP BY e.id 
               ORDER BY e.event_date DESC";
$events = db_fetch_all($conn, $events_sql);

// 2. Fetch all photos grouped by event
$all_photos = db_fetch_all($conn, 
    "SELECT p.*, e.title as event_title, e.event_date, e.venue 
     FROM event_photos p 
     JOIN events e ON p.event_id = e.id 
     ORDER BY e.event_date DESC, p.created_at DESC"
);

// Map photos by event_id for fast grouped rendering
$photos_by_event = [];
foreach ($all_photos as $ph) {
    $photos_by_event[$ph['event_id']][] = $ph;
}

$total_photos = count($all_photos);

include_once __DIR__ . '/includes/header.php';
?>

<!-- Gallery Hero Banner -->
<section class="hero-section py-5 text-center text-md-start">
    <div class="container py-2">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="badge badge-hero rounded-pill px-3 py-1 mb-2">
                    <i class="bi bi-camera-fill me-1"></i>MITS Memories & Moments
                </span>
                <h1 class="display-5 fw-bold font-heading mb-2 text-dark">
                    Event Gallery & <span class="text-gradient">Group Photos</span>
                </h1>
                <p class="lead text-secondary mb-3 pe-lg-4">
                    Relive unforgettable reunions, annual OSAM meets, convocation milestones, and campus memories across generations of MITSians.
                </p>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-xs">
                        <i class="bi bi-calendar-event text-danger me-1"></i><?= count($events) ?> Event Albums
                    </span>
                    <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-xs">
                        <i class="bi bi-images text-danger me-1"></i><?= $total_photos ?> Photos Captured
                    </span>
                </div>
            </div>

            <div class="col-lg-4 text-lg-end">
                <?php if (is_admin()): ?>
                    <a href="<?= e(BASE_URL) ?>/admin/manage-gallery" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm">
                        <i class="bi bi-plus-circle me-2"></i>Manage & Add Photos
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<div class="container py-4">
    <!-- Event Quick Filter Pills -->
    <?php if (!empty($events)): ?>
        <div class="card card-modern border-0 shadow-sm p-3 mb-5 bg-white">
            <div class="d-flex align-items-center gap-2 overflow-x-auto py-1">
                <span class="small text-secondary fw-semibold text-nowrap me-2">
                    <i class="bi bi-funnel me-1"></i>Filter by Event:
                </span>
                <button type="button" class="btn btn-sm btn-outline-danger active rounded-pill px-3 gallery-filter-btn text-nowrap" data-event-id="all">
                    All Albums (<?= $total_photos ?>)
                </button>
                <?php foreach ($events as $ev): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 gallery-filter-btn text-nowrap" data-event-id="<?= $ev['id'] ?>">
                        <?= e($ev['title']) ?> (<?= $ev['photos_count'] ?>)
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Event Galleries (Grouped Photos) -->
    <?php if (empty($events)): ?>
        <div class="card card-modern border-0 shadow-sm p-5 text-center bg-white my-4">
            <i class="bi bi-images display-3 text-muted mb-3"></i>
            <h4 class="fw-bold text-dark font-heading">No Event Photos Available Yet</h4>
            <p class="text-secondary small mb-4">The alumni association and administration will be posting photos from past and upcoming reunions soon.</p>
            <?php if (is_admin()): ?>
                <div>
                    <a href="<?= e(BASE_URL) ?>/admin/manage-gallery" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-plus-circle me-2"></i>Create First Event Gallery
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-5 mb-5" id="gallery-albums-container">
            <?php foreach ($events as $ev): 
                $ev_photos = $photos_by_event[$ev['id']] ?? [];
            ?>
                <section class="gallery-event-section" id="event-album-<?= $ev['id'] ?>" data-event-id="<?= $ev['id'] ?>">
                    <!-- Event Heading Card -->
                    <div class="card card-modern border-0 shadow-xs p-4 mb-4 bg-white position-relative overflow-hidden">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1 small fw-semibold">
                                        <i class="bi bi-calendar3 me-1"></i><?= date('F d, Y', strtotime($ev['event_date'])) ?>
                                    </span>
                                    <?php if (!empty($ev['venue'])): ?>
                                        <span class="text-secondary small">
                                            <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= e($ev['venue']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="fw-bold font-heading text-dark mb-1"><?= e($ev['title']) ?></h3>
                                <?php if (!empty($ev['description'])): ?>
                                    <p class="text-secondary small mb-0 pe-md-4"><?= e($ev['description']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="flex-shrink-0 text-md-end">
                                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small">
                                    <i class="bi bi-image me-1 text-danger"></i><?= count($ev_photos) ?> Photos
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Photo Grid for this Event -->
                    <?php if (empty($ev_photos)): ?>
                        <div class="card card-modern border-0 p-4 text-center bg-white text-secondary small">
                            <i class="bi bi-camera me-1"></i>No photos uploaded to this album yet.
                        </div>
                    <?php else: ?>
                        <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4">
                            <?php foreach ($ev_photos as $ph): ?>
                                <div class="col">
                                    <div class="card card-modern border-0 shadow-xs h-100 overflow-hidden gallery-card position-relative" 
                                         role="button"
                                         onclick="openLightbox('<?= e(addslashes($ph['photo_url'])) ?>', '<?= e(addslashes($ph['caption'] ?? $ev['title'])) ?>', '<?= e(addslashes($ev['title'])) ?>', '<?= date('M d, Y', strtotime($ev['event_date'])) ?>', '<?= e(addslashes($ev['venue'] ?? 'MITS')) ?>')">
                                        
                                        <div class="gallery-image-wrapper position-relative overflow-hidden" style="height: 220px; background-color: #f1f5f9;">
                                            <img src="<?= e($ph['photo_url']) ?>" 
                                                 alt="<?= e($ph['caption'] ?? $ev['title']) ?>" 
                                                 class="w-100 h-100 object-fit-cover gallery-img transition-all"
                                                 loading="lazy">

                                            <!-- Hover Overlay -->
                                            <div class="gallery-hover-overlay position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-between p-3 opacity-0 transition-all" style="background: linear-gradient(180deg, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0.75) 100%);">
                                                <div class="text-end">
                                                    <span class="btn btn-sm btn-light rounded-circle shadow-sm" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                                        <i class="bi bi-arrows-fullscreen text-dark small"></i>
                                                    </span>
                                                </div>

                                                <div class="text-white">
                                                    <div class="fw-semibold small line-clamp-2"><?= e($ph['caption'] ?: $ev['title']) ?></div>
                                                    <span class="badge bg-white bg-opacity-25 rounded-pill px-2 py-0 text-white mt-1" style="font-size: 0.7rem;">
                                                        <?= date('M Y', strtotime($ev['event_date'])) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if (!empty($ph['caption'])): ?>
                                            <div class="card-body p-2 px-3 bg-white border-top">
                                                <p class="small text-dark mb-0 text-truncate fw-medium" title="<?= e($ph['caption']) ?>">
                                                    <?= e($ph['caption']) ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Lightbox Modal -->
<div class="modal fade" id="galleryLightboxModal" tabindex="-1" aria-labelledby="lightboxModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-xl">
        <div class="modal-content bg-dark border-0 shadow-2xl overflow-hidden rounded-4">
            <div class="modal-header border-0 pb-0 position-absolute top-0 end-0 z-3 p-3">
                <button type="button" class="btn btn-light rounded-circle shadow p-1" data-bs-dismiss="modal" aria-label="Close" style="width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="bi bi-x-lg fs-6"></i>
                </button>
            </div>

            <div class="modal-body p-0 text-center position-relative bg-black d-flex align-items-center justify-content-center" style="min-height: 480px; max-height: 80vh;">
                <img id="lightboxImg" src="" alt="Preview" class="img-fluid object-fit-contain w-100" style="max-height: 80vh;">
            </div>

            <div class="modal-footer border-0 bg-dark text-white d-flex justify-content-between p-3 px-4">
                <div class="text-start">
                    <h5 id="lightboxEventTitle" class="fw-bold mb-1 text-white font-heading"></h5>
                    <p id="lightboxCaption" class="text-white-50 small mb-0"></p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span id="lightboxDateBadge" class="badge bg-danger rounded-pill px-3 py-1 small"></span>
                    <a id="lightboxDownloadBtn" href="#" target="_blank" download class="btn btn-sm btn-outline-light rounded-pill px-3">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Open Full
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.gallery-card {
    transition: transform 0.25s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.25s ease;
}
.gallery-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -4px rgba(216, 21, 28, 0.12), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
}
.gallery-card:hover .gallery-img {
    transform: scale(1.05);
}
.gallery-card:hover .gallery-hover-overlay {
    opacity: 1 !important;
}
.transition-all {
    transition: all 0.3s ease;
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
// Open Lightbox Function
function openLightbox(imageUrl, caption, eventTitle, eventDate, venue) {
    document.getElementById('lightboxImg').src = imageUrl;
    document.getElementById('lightboxEventTitle').innerText = eventTitle;
    document.getElementById('lightboxCaption').innerText = caption || eventTitle;
    document.getElementById('lightboxDateBadge').innerText = eventDate;
    document.getElementById('lightboxDownloadBtn').href = imageUrl;

    const modal = new bootstrap.Modal(document.getElementById('galleryLightboxModal'));
    modal.show();
}

// Client-side Event Filter Tabs
document.addEventListener('DOMContentLoaded', function () {
    const filterButtons = document.querySelectorAll('.gallery-filter-btn');
    const sections = document.querySelectorAll('.gallery-event-section');

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            filterButtons.forEach(b => {
                b.classList.remove('btn-outline-danger', 'active');
                b.classList.add('btn-outline-secondary');
            });

            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-outline-danger', 'active');

            const eventId = this.getAttribute('data-event-id');

            sections.forEach(sec => {
                if (eventId === 'all' || sec.getAttribute('data-event-id') === eventId) {
                    sec.style.display = 'block';
                } else {
                    sec.style.display = 'none';
                }
            });
        });
    });
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
