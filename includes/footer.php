<?php
/**
 * Global Footer Component
 * MITS Alumni Portal
 */

require_once __DIR__ . '/../config/config.php';
?>
</main>

<!-- Modern Footer -->
<footer class="footer mt-auto py-5 bg-white border-top">
    <div class="container">
        <div class="row g-4 justify-content-between">
            <div class="col-lg-5 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <img src="<?= e(BASE_URL) ?>/assets/images/OSAM%20logo.jpg" alt="<?= e(APP_NAME) ?> Logo" class="brand-logo-img" style="width: 42px; height: 42px;">
                    <span class="fs-5 fw-bold text-dark"><?= e(APP_NAME) ?></span>
                </div>
                <p class="text-secondary small pe-lg-4 mb-3">
                    Muthoot Institute of Technology and Science (MITS) Connect platform connects graduates across batches, empowers mentorship, fosters career networking, and celebrates institutional legacy.
                </p>
                <div class="d-flex gap-2">
                    <!-- Give their social media icons their native color -->
                    <a href="https://www.linkedin.com" target="_blank" rel="noopener" class="btn btn-sm btn-light rounded-circle" title="LinkedIn"><i class="bi bi-linkedin" style="color: #0077b5;"></i></a>
                    <a href="https://twitter.com" target="_blank" rel="noopener" class="btn btn-sm btn-light rounded-circle" title="Twitter"><i class="bi bi-twitter-x" style="color: #1da1f2;"></i></a>
                    <a href="https://instagram.com" target="_blank" rel="noopener" class="btn btn-sm btn-light rounded-circle" title="Instagram"><i class="bi bi-instagram" style="color: #e4405f;"></i></a>
                    <a href="https://facebook.com" target="_blank" rel="noopener" class="btn btn-sm btn-light rounded-circle" title="Facebook"><i class="bi bi-facebook" style="color: #1877f2;"></i></a>
                    <a href="https://mgmits.ac.in" target="_blank" rel="noopener" class="btn btn-sm btn-light rounded-circle text-primary" title="College Website"><i class="bi bi-globe2"></i></a>
                </div>
            </div>

            <div class="col-6 col-lg-2 col-md-3">
                <h6 class="fw-bold text-dark mb-3">Quick Links</h6>
                <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
                    <li><a href="<?= e(BASE_URL) ?>/#about" class="text-decoration-none text-secondary hover-primary">About OSAM</a></li>
                    <li><a href="<?= e(BASE_URL) ?>/gallery" class="text-decoration-none text-secondary hover-primary">Event Gallery</a></li>
                    <li><a href="<?= e(BASE_URL) ?>/directory" class="text-decoration-none text-secondary hover-primary">Alumni Directory</a></li>
                    <li><a href="<?= e(BASE_URL) ?>/jobs" class="text-decoration-none text-secondary hover-primary">Career Board</a></li>
                    <li><a href="<?= e(BASE_URL) ?>/feed" class="text-decoration-none text-secondary hover-primary">Community Feed</a></li>
                    <li><a href="<?= e(BASE_URL) ?>/auth/login" class="text-decoration-none text-secondary hover-primary">Sign In with Google</a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-2 col-md-3">
                <h6 class="fw-bold text-dark mb-3">Institution</h6>
                <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
                    <li><a href="https://mgmits.ac.in" target="_blank" rel="noopener" class="text-decoration-none text-secondary hover-primary">MITS Official Site</a></li>
                    <li><a href="https://mgmits.ac.in/osam" target="_blank" rel="noopener" class="text-decoration-none text-secondary hover-primary">Alumni Association</a></li>
                    <li><a href="https://mgmits.ac.in/placement" target="_blank" rel="noopener" class="text-decoration-none text-secondary hover-primary">Placement Cell</a></li>
                    <li><a href="mailto:info@mgits.ac.in" class="text-decoration-none text-secondary hover-primary">Contact Support</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold text-dark mb-3">College Address</h6>
                <p class="text-secondary small mb-2">
                    <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                    Muthoot Institute of Technology and Science (Autonomous), Varikoli P.O., Puthencruz - 682308
                </p>
                <p class="text-secondary small mb-0">
                    <i class="bi bi-envelope-fill text-primary me-2"></i>info@mgits.ac.in
                </p>
            </div>
        </div>

        <hr class="my-4 text">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-secondary gap-2">
            <div class="w-100 text-center">
                &copy; <?= date('Y') ?> <span class="fw-semibold text-dark">Anirudh J Bhatt - Muthoot Institute of Technology and Science</span>. All rights reserved.
            </div>
        </div>
    </div>
</footer>

<!-- Toast Container for Notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer" style="z-index: 1100;"></div>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Custom Main Application JS -->
<script src="<?= e(BASE_URL) ?>/assets/js/main.js?v=<?= APP_VERSION ?>"></script>

</body>
</html>
