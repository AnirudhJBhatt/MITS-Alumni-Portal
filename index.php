<?php
/**
 * MITS Alumni Portal - Home & About OSAM
 * Muthoot Institute of Technology and Science
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Welcome to MITS Connect | OSAM';
$page_description = 'Official Alumni Portal and MITS Connect - Old Student Association of MITS (OSAM). Connect with graduates of Muthoot Institute of Technology and Science, explore careers, and foster mentorship.';
$current_page = 'index';

include_once __DIR__ . '/includes/header.php';
?>

<!-- ============================================================== -->
<!-- 1. Hero Section                                                -->
<!-- ============================================================== -->
<section class="hero-section py-5">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7 text-center text-lg-start">
                <div class="d-inline-flex align-items-center gap-2 badge-hero rounded-pill px-3 py-1 mb-3">
                    <span class="badge bg-primary text-white rounded-pill px-2">Official</span>
                    <span class="small">Old Student Association of MITS (OSAM)</span>
                </div>
                <h1 class="display-4 fw-bold font-heading mb-3 text-dark">
                    Empowering the <span class="text-gradient">MITS Alumni</span> Community
                </h1>
                <p class="lead text-secondary mb-4 pe-lg-5">
                    Reconnect with classmates, unlock exclusive career opportunities, mentor current students, and expand your global professional network across top industries.
                </p>
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start mb-4">
                    <?php if (is_logged_in()): ?>
                        <a href="<?= e(BASE_URL) ?>/feed" class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm">
                            <i class="bi bi-collection-play me-2"></i>Go to Community Feed
                        </a>
                        <a href="<?= e(BASE_URL) ?>/directory" class="btn btn-outline-secondary btn-lg rounded-pill px-4">
                            <i class="bi bi-people me-2"></i>Explore Directory
                        </a>
                    <?php else: ?>
                        <a href="<?= e(BASE_URL) ?>/auth/login" class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm">
                            <i class="bi bi-google me-2"></i>Join with Google Sign-In
                        </a>
                        <a href="#about" class="btn btn-outline-secondary btn-lg rounded-pill px-4">
                            <i class="bi bi-info-circle me-2"></i>Learn About OSAM
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card card-modern border-0 shadow-lg overflow-hidden p-2 bg-white position-relative">
                    <img src="assets/images/College Image.jpeg" alt="Muthoot Institute of Technology and Science Campus" class="img-fluid rounded-4 w-100 object-fit-cover shadow-sm" style="height: 380px;">
                </div>
            </div>
        </div>
    </div>

</section>

<!-- ============================================================== -->
<!-- 2. About OSAM Section                                          -->
<!-- ============================================================== -->
<section id="about" class="py-5 bg-white border-top">
    <div class="container py-4">
        <div class="row g-4 align-items-center mb-5">
            <div class="col-lg-7">
                <div class="pe-lg-4">
                    <div class="d-inline-flex align-items-center gap-2 text-danger fw-semibold small text-uppercase tracking-wider mb-2">
                        <i class="bi bi-buildings"></i> Who We Are
                    </div>
                    <h2 class="display-6 fw-bold font-heading text-dark mb-4">Old Student Association of MITS (OSAM)</h2>
                    
                    <p class="text-secondary fs-6 mb-3" style="line-height: 1.8;">
                        The Alumni Association of Muthoot Institute of Technology and Science (MITS) serves as a dynamic platform to foster lifelong connections among the institute’s alumni, current students, and faculty. Committed to nurturing a strong sense of community and collaboration, the association encourages continuous interaction, professional networking, and knowledge sharing across generations of MITS graduates.
                    </p>
                    <p class="text-secondary fs-6 mb-3" style="line-height: 1.8;">
                        The association actively facilitates engagement through events, mentorship programs, and alumni-student interactions, creating meaningful opportunities for career growth, academic exchange, and institutional development. With MITS alumni occupying esteemed positions in leading national and international organizations, the association acts as a hub for celebrating achievements and promoting mutual support.
                    </p>
                    <p class="text-secondary fs-6 mb-0" style="line-height: 1.8;">
                        By strengthening ties among its members, the MITS Alumni Association contributes to the collective growth of the MITS community and upholds the values of excellence, innovation, and service to society.
                    </p>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card card-modern border-0 shadow-lg p-4 bg-white position-relative">
                    <div class="d-flex align-items-center gap-3 pb-3 mb-3 border-bottom">
                        <img src="<?= e(BASE_URL) ?>/assets/images/OSAM%20logo.jpg" alt="OSAM Logo" class="brand-logo-img" style="width: 52px; height: 52px; border-radius: 12px;">
                        <div>
                            <h5 class="fw-bold text-dark mb-0 font-heading">OSAM Overview</h5>
                            <span class="text-secondary small">MITS Autonomous Alumni Body</span>
                        </div>
                    </div>

                    <ul class="list-unstyled d-flex flex-column gap-3 mb-4">
                        <li class="d-flex align-items-start gap-3">
                            <div class="bg-primary-subtle text-primary p-2 rounded-circle flex-shrink-0">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div>
                                <span class="fw-semibold text-dark d-block small">Alma Mater</span>
                                <span class="text-secondary small">Muthoot Institute of Technology & Science, Varikoli, Ernakulam, Kerala</span>
                            </div>
                        </li>
                        <li class="d-flex align-items-start gap-3">
                            <div class="bg-primary-subtle text-primary p-2 rounded-circle flex-shrink-0">
                                <i class="bi bi-globe-americas"></i>
                            </div>
                            <div>
                                <span class="fw-semibold text-dark d-block small">Global Presence</span>
                                <span class="text-secondary small">Active chapters across India, Middle East, Europe & Americas</span>
                            </div>
                        </li>
                        <li class="d-flex align-items-start gap-3">
                            <div class="bg-primary-subtle text-primary p-2 rounded-circle flex-shrink-0">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div>
                                <span class="fw-semibold text-dark d-block small">Official Accreditation</span>
                                <span class="text-secondary small">Recognized autonomous alumni body supporting institutional growth</span>
                            </div>
                        </li>
                    </ul>

                    <div class="d-grid gap-2">
                        <a href="<?= e(BASE_URL) ?>/directory" class="btn btn-primary rounded-pill py-2 shadow-sm">
                            <i class="bi bi-search me-2"></i>Explore Alumni Directory
                        </a>
                        <?php if (!is_logged_in()): ?>
                            <a href="<?= e(BASE_URL) ?>/auth/login" class="btn btn-outline-primary rounded-pill py-2">
                                <i class="bi bi-google me-2"></i>Join OSAM Network
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vision & Mission Banner -->
        <div class="row g-4 mb-5">
            <!-- Vision -->
            <div class="col-lg-5">
                <div class="card card-modern h-100 p-4 border-0 text-white position-relative overflow-hidden" style="background: var(--brand-gradient);">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-white text-danger fw-bold rounded-pill px-3 py-1 text-uppercase small">
                            <i class="bi bi-compass-fill me-1"></i> Our Vision
                        </span>
                    </div>
                    <h3 class="fw-bold text-white font-heading mb-3">Strategic Vision</h3>
                    <p class="fs-5 fw-medium mb-4" style="line-height: 1.6;">
                        "To be known as a hub where alumni and industry can collaborate with other institutional stakeholders for holistic development of students and betterment of society."
                    </p>
                    <div class="mt-auto pt-3 border-top border-white border-opacity-25 small opacity-75">
                        <i class="bi bi-check2-circle me-1"></i> Guiding every initiative of the OSAM fraternity.
                    </div>
                </div>
            </div>

            <!-- Mission -->
            <div class="col-lg-7">
                <div class="card card-modern h-100 p-4 bg-white border">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-danger-subtle text-danger fw-bold rounded-pill px-3 py-1 text-uppercase small">
                            <i class="bi bi-bullseye me-1"></i> Our Mission
                        </span>
                    </div>
                    <h3 class="fw-bold font-heading text-dark mb-4">Core Mission Pillars</h3>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <div class="text-danger fs-5 flex-shrink-0">
                                    <i class="bi bi-mortarboard-fill"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Student Role Models</h6>
                                    <p class="text-secondary small mb-0">Providing guidance and support for higher education, placements, and research.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <div class="text-danger fs-5 flex-shrink-0">
                                    <i class="bi bi-bezier2"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Stakeholder Bridge</h6>
                                    <p class="text-secondary small mb-0">Acting as an active conduit between internal and external institutional partners.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <div class="text-danger fs-5 flex-shrink-0">
                                    <i class="bi bi-chat-square-quote-fill"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Constructive Feedback</h6>
                                    <p class="text-secondary small mb-0">Providing feedback on curriculum relevance and academic processes.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <div class="text-danger fs-5 flex-shrink-0">
                                    <i class="bi bi-lightning-charge-fill"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Industry Alignment</h6>
                                    <p class="text-secondary small mb-0">Keeping MITS abreast with rapid evolutions in industry, deep-tech, and research.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <div class="text-danger fs-5 flex-shrink-0">
                                    <i class="bi bi-trophy-fill"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Institutional Acclaim</h6>
                                    <p class="text-secondary small mb-0">Highlighting MITS achievements globally via awards, honours, and publicity.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <div class="text-danger fs-5 flex-shrink-0">
                                    <i class="bi bi-heart-fill"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Social Relevance</h6>
                                    <p class="text-secondary small mb-0">Executing community and socially impactful initiatives collaboratively.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aims & Objectives Section -->
        <div>
            <div class="text-center mb-4">
                <span class="badge bg-danger-subtle text-danger fw-bold rounded-pill px-3 py-1 text-uppercase small">
                    <i class="bi bi-flag-fill me-1"></i> Purpose & Mandate
                </span>
                <h3 class="fw-bold font-heading text-dark mt-2">Aims & Objectives</h3>
                <p class="text-secondary small mx-auto" style="max-width: 650px;">
                    Defined principles guiding the association's ongoing operations and stakeholder collaboration.
                </p>
            </div>

            <div class="row g-4">
                <!-- Objective 1 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card card-modern h-100 p-4 border bg-white card-hover">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-danger rounded-3 mb-3" style="width: 48px; height: 48px; font-size: 1.25rem;">
                            <i class="bi bi-people"></i>
                        </div>
                        <h5 class="fw-bold text-dark font-heading mb-2">Fraternity & Outreach</h5>
                        <p class="text-secondary small mb-0" style="line-height: 1.7;">
                            To foster the feeling of MITS fraternity among college alumni through interaction by promoting and propagating the achievements of members and the Alma Mater through exhibitions, newsletters, media, and digital channels.
                        </p>
                    </div>
                </div>

                <!-- Objective 2 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card card-modern h-100 p-4 border bg-white card-hover">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-danger rounded-3 mb-3" style="width: 48px; height: 48px; font-size: 1.25rem;">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </div>
                        <h5 class="fw-bold text-dark font-heading mb-2">Academic Excellence</h5>
                        <p class="text-secondary small mb-0" style="line-height: 1.7;">
                            Promoting academic and professional excellence by organizing or sponsoring seminars, technical conferences, expert guest lectures, and publications for the benefit of society at large.
                        </p>
                    </div>
                </div>

                <!-- Objective 3 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card card-modern h-100 p-4 border bg-white card-hover">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-danger rounded-3 mb-3" style="width: 48px; height: 48px; font-size: 1.25rem;">
                            <i class="bi bi-signpost-split-fill"></i>
                        </div>
                        <h5 class="fw-bold text-dark font-heading mb-2">Career Guidance & Tech Transfer</h5>
                        <p class="text-secondary small mb-0" style="line-height: 1.7;">
                            Providing career guidance in both academic and extracurricular fields to students and the public, creating fertile opportunities for technology and knowledge transfer between alumni and the Alma Mater.
                        </p>
                    </div>
                </div>

                <!-- Objective 4 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card card-modern h-100 p-4 border bg-white card-hover">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-danger rounded-3 mb-3" style="width: 48px; height: 48px; font-size: 1.25rem;">
                            <i class="bi bi-pin-map-fill"></i>
                        </div>
                        <h5 class="fw-bold text-dark font-heading mb-2">Global Chapter Expansion</h5>
                        <p class="text-secondary small mb-0" style="line-height: 1.7;">
                            Propagating the ideas and ethos of the association through the establishment and active maintenance of alumni chapters within India and across global metropolitan regions.
                        </p>
                    </div>
                </div>

                <!-- Objective 5 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card card-modern h-100 p-4 border bg-white card-hover">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-danger rounded-3 mb-3" style="width: 48px; height: 48px; font-size: 1.25rem;">
                            <i class="bi bi-cpu-fill"></i>
                        </div>
                        <h5 class="fw-bold text-dark font-heading mb-2">Industry-Academia Synergy</h5>
                        <p class="text-secondary small mb-0" style="line-height: 1.7;">
                            Encouraging advancement of Science & Technology through robust Industry-Academia interactions, research sponsorships, and innovative student capstone mentorship.
                        </p>
                    </div>
                </div>

                <!-- Objective 6 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card card-modern h-100 p-4 border bg-white card-hover">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-danger rounded-3 mb-3" style="width: 48px; height: 48px; font-size: 1.25rem;">
                            <i class="bi bi-link-45deg"></i>
                        </div>
                        <h5 class="fw-bold text-dark font-heading mb-2">Inter-Association Alliances</h5>
                        <p class="text-secondary small mb-0" style="line-height: 1.7;">
                            Interacting and collaborating with peer alumni associations and collegiate bodies nationally on common opportunities, policy discussions, and professional networks.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================== -->
<!-- 3. Call to Action Banner                                       -->
<!-- ============================================================== -->
<section class="py-5 bg-surface border-top">
    <div class="container text-center py-4">
        <div class="card-modern p-5 text-white shadow-lg" style="background: var(--brand-gradient);">
            <h2 class="display-6 fw-bold mb-3 text-white font-heading">Ready to Reconnect with MITS?</h2>
            <p class="lead opacity-90 mb-4 mx-auto" style="max-width: 650px;">
                Join the official portal using your Google account in seconds and step into an active network of fellow graduates, mentors, and industry pioneers.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <?php if (is_logged_in()): ?>
                    <a href="<?= e(BASE_URL) ?>/feed" class="btn btn-light btn-lg rounded-pill px-5 fw-bold text-primary shadow">
                        <i class="bi bi-collection-play me-2"></i>Go to Community Feed
                    </a>
                <?php else: ?>
                    <a href="<?= e(BASE_URL) ?>/auth/login" class="btn btn-light btn-lg rounded-pill px-5 fw-bold text-primary shadow">
                        <i class="bi bi-google me-2"></i>Sign In with Google
                    </a>
                <?php endif; ?>
                <a href="<?= e(BASE_URL) ?>/directory" class="btn btn-outline-light btn-lg rounded-pill px-4">
                    <i class="bi bi-search me-2"></i>Find Batchmates
                </a>
            </div>
        </div>
    </div>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
