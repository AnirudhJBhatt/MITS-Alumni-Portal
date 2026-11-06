# MITS Alumni Portal - Project Specification & Implementation Plan

## 1. Project Overview
- **Project Name:** MITS Alumni Portal
- **Technology Stack:** HTML5, CSS3, JavaScript (ES6+), Bootstrap 5, Procedural PHP 8+, MySQL
- **Authentication:** Google Sign-In via Google Identity Services (GIS)
- **Theme & Brand Aesthetics:**
  - Primary Brand Red: `#d8151c`
  - Primary Dark / Hover: `#b91016`
  - Background & Surface: White (`#ffffff`) and Crisp Light Surface (`#fcfcfd`)
  - Accent / Highlights: `#fef2f2` (soft red tint), `#fecaca` (subtle red border)
  - Typography: Plus Jakarta Sans (Headings) and Inter (Body)
- **Core Modules:**
  1. Authentication & Onboarding
  2. Alumni Profiles & Directory
  3. Networking & Connections
  4. Community Feed (Posts, Likes, Comments)
  5. Job & Internship Board
  6. Real-time Notifications
  7. Admin Moderation & Verification Dashboard

---

## 2. Directory & File Structure

```text
MITS-Alumni-Portal/
├── config/
│   ├── config.php             # Global constants, BASE_URL, Google Client ID, app settings
│   ├── db.php                 # MySQLi database connection (procedural)
│   └── session.php            # Secure session initialization & session helper functions
│
├── includes/
│   ├── header.php             # HTML <head>, Bootstrap 5 CSS, Google Fonts, meta tags
│   ├── navbar.php             # Responsive top navbar with dynamic auth states & badges
│   ├── footer.php             # Page footer, Bootstrap 5 JS bundle, custom scripts
│   ├── functions.php          # Procedural helper functions (sanitization, CSRF, formatting)
│   ├── auth_check.php         # Gatekeeper: redirects unauthenticated users to login
│   └── admin_check.php        # Gatekeeper: restricts access to admin-only pages
│
├── auth/
│   ├── login.php              # Sign-In page with Google Identity Services button
│   ├── google-callback.php    # Backend verification of Google ID token & session creation
│   ├── complete-profile.php   # Onboarding wizard for new alumni (roll no, branch, batch)
│   └── logout.php             # Destroys session, clears cookies, redirects to login
│
├── api/                       # RESTful AJAX endpoints (JSON responses)
│   ├── posts.php              # Feed: create, fetch, delete posts
│   ├── likes.php              # Toggle like on posts
│   ├── comments.php           # Fetch & add post comments
│   ├── connections.php        # Send, accept, reject, remove connections
│   ├── jobs.php               # Job queries and quick application
│   └── notifications.php      # Fetch unread count & mark notifications as read
│
├── admin/
│   ├── index.php              # Dashboard overview & analytics summary
│   ├── verify-alumni.php      # Verification queue for pending alumni accounts
│   ├── manage-users.php       # User directory, role assignment, status toggling
│   ├── manage-posts.php       # Content moderation and flag removal
│   ├── manage-jobs.php        # Job listing approval and review
│   └── manage-gallery.php     # Event album creation & photo upload management
│
├── assets/
│   ├── css/
│   │   ├── style.css          # Custom styling, modern cards, glassmorphism accents
│   │   └── admin.css          # Admin-specific styles & sidebar layout
│   ├── js/
│   │   ├── main.js            # General UI helpers (toasts, tooltips, CSRF helper)
│   │   ├── auth.js            # GIS token receiver and AJAX dispatcher
│   │   ├── feed.js            # Infinite scroll/AJAX feed, like/comment handling
│   │   └── network.js         # Connection requests & acceptance AJAX handling
│   └── images/
│       ├── logo.png           # Portal logo
│       └── default-avatar.png # Fallback avatar
│
├── uploads/                   # User-uploaded files (with .htaccess execution block)
│   ├── avatars/               # Profile pictures
│   ├── posts/                 # Images attached to community posts
│   ├── gallery/               # Event and alumni group photos
│   └── resumes/               # Optional resume attachments for job applications
│
├── database/
│   ├── schema.sql             # Complete MySQL table definitions & foreign keys
│   └── seed.sql               # Initial sample data (admin user, test alumni, sample posts)
├── index.php                  # Unified Landing Page: Hero, OSAM Overview, Vision, Mission & Objectives
├── gallery.php                # Campus & Event Gallery (grouped by events with lightbox)
├── feed.php                   # Central social feed with post composer & interactive stream
├── directory.php              # Filterable Alumni Directory (search by branch, batch, company)
├── profile.php                # View user profile (public/alumni view)
├── edit-profile.php           # Edit bio, skills, work experience, social links
├── network.php                # Connection hub (My Connections, Requests, Suggestions)
├── jobs.php                   # Career opportunities board with filters
├── post-job.php               # Form for alumni & admins to post new opportunities
├── notifications.php          # Detailed notifications activity page
└── README.md                  # Project setup and running instructions
```



---

## 3. Database Schema Design (MySQL)

### 3.1. `users`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | INT | AUTO_INCREMENT, PRIMARY KEY | Unique user identifier |
| `google_id` | VARCHAR(100) | UNIQUE, NULLABLE | Google sub ID |
| `email` | VARCHAR(150) | UNIQUE, NOT NULL | User email address |
| `full_name` | VARCHAR(100) | NOT NULL | User's full name from Google profile |
| `avatar_url` | VARCHAR(255) | NULLABLE | Profile picture URL |
| `role` | ENUM | 'alumni', 'student', 'admin' | User authorization role |
| `status` | ENUM | 'pending', 'verified', 'rejected', 'suspended' | Verification status |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Registration timestamp |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Last update timestamp |

### 3.2. `alumni_profiles`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | INT | AUTO_INCREMENT, PRIMARY KEY | Profile ID |
| `user_id` | INT | UNIQUE, FK -> users(id) | Associated user |
| `roll_number` | VARCHAR(50) | NULLABLE | College Roll / Registration Number |
| `department` | VARCHAR(100) | NOT NULL | Degree/Department (CSE, ECE, MECH, etc.) |
| `graduation_year`| YEAR | NOT NULL | Passing out year |
| `current_company`| VARCHAR(150) | NULLABLE | Present employer |
| `designation` | VARCHAR(100) | NULLABLE | Current job title |
| `industry` | VARCHAR(100) | NULLABLE | Domain/Sector |
| `location` | VARCHAR(100) | NULLABLE | City, Country |
| `linkedin_url` | VARCHAR(255) | NULLABLE | LinkedIn profile link |
| `bio` | TEXT | NULLABLE | Professional summary / about me |
| `skills` | TEXT | NULLABLE | Comma-delimited list of skills |
| `is_mentor` | TINYINT(1) | DEFAULT 0 | Willingness to mentor juniors |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Created at |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Updated at |

### 3.3. `posts`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | INT | AUTO_INCREMENT, PRIMARY KEY | Post identifier |
| `user_id` | INT | FK -> users(id) | Post author |
| `content` | TEXT | NOT NULL | Body of the post |
| `image_url` | VARCHAR(255) | NULLABLE | Optional attached media |
| `post_type` | ENUM | 'general', 'achievement', 'hiring', 'event' | Category of post |
| `likes_count` | INT | DEFAULT 0 | Denormalized count for performance |
| `comments_count`| INT | DEFAULT 0 | Denormalized count for performance |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Publish time |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Edit time |

### 3.4. `likes`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | INT | AUTO_INCREMENT, PRIMARY KEY | Like ID |
| `post_id` | INT | FK -> posts(id) | Liked post |
| `user_id` | INT | FK -> users(id) | Liking user |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Liked timestamp |
| *Constraint* | UNIQUE (`post_id`, `user_id`) | Prevents duplicate likes |

### 3.5. `comments`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | INT | AUTO_INCREMENT, PRIMARY KEY | Comment identifier |
| `post_id` | INT | FK -> posts(id) | Target post |
| `user_id` | INT | FK -> users(id) | Commenting user |
| `content` | TEXT | NOT NULL | Comment text |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Comment timestamp |

### 3.6. `jobs`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | INT | AUTO_INCREMENT, PRIMARY KEY | Job listing ID |
| `posted_by` | INT | FK -> users(id) | Creator (Alumni or Admin) |
| `title` | VARCHAR(150) | NOT NULL | Role title |
| `company` | VARCHAR(150) | NOT NULL | Hiring company name |
| `location` | VARCHAR(100) | NOT NULL | Location or 'Remote' |
| `job_type` | ENUM | 'full-time', 'part-time', 'internship', 'remote' | Employment type |
| `experience_level`| VARCHAR(50)| NOT NULL | e.g. '0-2 years', 'Senior' |
| `description` | TEXT | NOT NULL | Job responsibilities & overview |
| `requirements` | TEXT | NULLABLE | Required qualifications |
| `apply_link` | VARCHAR(255) | NULLABLE | External URL or application link |
| `contact_email`| VARCHAR(150) | NULLABLE | Direct contact email |
| `status` | ENUM | 'active', 'closed', 'under_review' | Visibility status |
| `deadline` | DATE | NULLABLE | Application cutoff |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Posting timestamp |

### 3.7. `connections`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | INT | AUTO_INCREMENT, PRIMARY KEY | Connection ID |
| `sender_id` | INT | FK -> users(id) | Requester |
| `receiver_id` | INT | FK -> users(id) | Target recipient |
| `status` | ENUM | 'pending', 'accepted', 'rejected', 'blocked' | Connection status |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Request timestamp |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Response timestamp |
| *Constraint* | UNIQUE (`sender_id`, `receiver_id`) | Prevents duplicate requests |

### 3.8. `notifications`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | INT | AUTO_INCREMENT, PRIMARY KEY | Notification ID |
| `user_id` | INT | FK -> users(id) | Recipient user |
| `actor_id` | INT | FK -> users(id) | Triggering user |
| `type` | ENUM | 'like', 'comment', 'connection_request', 'connection_accepted', 'job_alert', 'admin_verification' | Event type |
| `entity_id` | INT | NULLABLE | Associated ID (post_id, job_id, etc.) |
| `message` | VARCHAR(255) | NOT NULL | Notification text |
| `is_read` | TINYINT(1) | DEFAULT 0 | Read state (0 = unread, 1 = read) |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Timestamp |

---

## 4. Security Architecture

1. **Google ID Token Server-Side Verification:**
   - Client sends Google JWT `credential` to `auth/google-callback.php`.
   - Server verifies token cryptographically or queries Google's tokeninfo endpoint (`https://oauth2.googleapis.com/tokeninfo?id_token=...`) with cURL.
   - Verifies `aud` matches the configured `GOOGLE_CLIENT_ID` and `iss` is valid (`accounts.google.com`).
2. **Secure PHP Sessions:**
   - `session.cookie_httponly = 1`, `session.use_only_cookies = 1`, `session.cookie_samesite = 'Lax'`.
   - Regenerate session ID (`session_regenerate_id(true)`) upon successful authentication to prevent session fixation.
3. **Prepared Statements:**
   - 100% of SQL queries executed using parameterized prepared statements via `mysqli_prepare`, `mysqli_stmt_bind_param`, and `mysqli_stmt_execute`. Zero raw query string interpolation.
4. **CSRF Protection:**
   - Cryptographic CSRF tokens generated per session stored in `$_SESSION['csrf_token']`.
   - Validated on every state-changing POST/PUT/DELETE request.
5. **XSS & Output Sanitization:**
   - HTML-escape all user-generated content using `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')`.
6. **Alumni Verification Workflow:**
   - New accounts start with `status = 'pending'`.
   - Admins can verify roll numbers against college records from the admin dashboard before granting full verified badges or specific privileges.

---

## 5. Incremental Implementation Phases

### Phase 1: Foundation & Core Scaffold (Completed)
- [x] Set up directory structure, configuration files (`config/config.php`, `config/db.php`, `config/session.php`).
- [x] Create and execute `database/schema.sql` in MySQL/phpMyAdmin (all 8 tables created).
- [x] Implement reusable view layouts: `includes/header.php`, `includes/navbar.php`, `includes/footer.php`, `includes/functions.php`.
- [x] Establish responsive modern UI design system using Bootstrap 5 + custom CSS (`assets/css/style.css`) in MITS Red (`#d8151c`) and White.
- [x] Implemented landing page `index.php` with dynamic metrics.

### Phase 2: Google Sign-In & Onboarding (Completed)
- [x] Configured Google Cloud Console credentials hooks (`GOOGLE_CLIENT_ID` in `config/config.php`).
- [x] Built `auth/login.php` with Google Identity Services (GIS) button, One-Tap hooks, and instant local test switchers.
- [x] Created `assets/js/auth.js` client-side token dispatcher.
- [x] Created backend `auth/google-callback.php` to verify Google JWT tokens via Google's tokeninfo API, upsert user records, and create secure PHP sessions.
- [x] Built onboarding flow `auth/complete-profile.php` for newly registered alumni (capturing roll number, branch, batch, company, and mentorship flag).
- [x] Built secure logout in `auth/logout.php`.

### Phase 3: Profile Management & Alumni Directory (Completed)
- [x] Built `profile.php` with rich alumni credentials, verified badge, company highlights, mentorship badge, recent posts, and shared jobs.
- [x] Built `edit-profile.php` with CSRF protection, image upload handling (JPG/PNG/WebP validation), and instant feedback.
- [x] Built `directory.php` with real-time keyword search and multi-filtering (Department, Batch, Mentors-only, Company).
- [x] Built asynchronous `api/connections.php` to handle connection requests, acceptance, and cancellations.

### Phase 4: Networking & Connections (Completed)
- [x] Enhanced `api/connections.php` to handle send, accept, decline, withdraw, and remove connection actions with notification triggers.
- [x] Created `network.php` with 4 interactive tabs:
  - My Connections (active connections grid with email link and removal option)
  - Invitations Received (with live Accept and Decline actions)
  - Sent Requests (with Cancel/Withdraw request controls)
  - Recommended Alumni (smart suggestion engine ranking shared departments, graduation batches, and mentors)
- [x] Built client-side `assets/js/network.js` for instant asynchronous UI state changes and badge counter updates.

### Phase 5: Social Feed, Posts, Likes & Comments (Completed)
- [x] Created `feed.php` 3-column layout (mini user profile, central feed stream, and campus/jobs widgets).
- [x] Built rich post creator with category selection (General, Hiring Referral, Achievement, Event) and image attachment preview.
- [x] Built `api/posts.php` supporting multipart image upload, category filtering, paginated retrieval, and post deletion.
- [x] Built `api/likes.php` with instantaneous like/unlike count toggling, heart animations, and owner notifications.
- [x] Built `api/comments.php` supporting threaded comments retrieval, real-time posting, and deletion with notifications.
- [x] Built client-side `assets/js/feed.js` managing live interactions, comments drawer, and share link tools.

### Phase 6: Job & Opportunity Board (Completed)
- [x] Built `jobs.php` with keyword search, job type filtering (Full-time, Internship, Remote, Part-time), and location selectors.
- [x] Implemented responsive job details modal with complete requirements breakdown, deadline indicator, and poster card.
- [x] Built `post-job.php` for alumni to post opportunities with dual application channels (direct URL and referral email).
- [x] Built asynchronous `api/jobs.php` for fetching job specifications, toggling listing status, and managing job deletions.

### Phase 7: Notification System & Admin Dashboard (Completed)
- [x] Implemented real-time polling notification counter in `includes/navbar.php` and `assets/js/main.js`.
- [x] Built `notifications.php` displaying full activity history (likes, comments, connection requests, and institutional approvals).
- [x] Built asynchronous `api/notifications.php` for unread count polling, list retrieval, and mark-as-read/delete actions.
- [x] Built Admin Dashboard (`admin/index.php`) featuring live metrics (Registered Alumni, Pending Queue, Active Jobs, Discussion Posts) and department statistics.
- [x] Built Alumni Verification Queue (`admin/verify-alumni.php`) for reviewing roll numbers, approving accounts, and sending automated congratulatory alerts.
- [x] Built User & Role Management console (`admin/manage-users.php`) for managing directory members, roles, and suspension states.
- [x] Built Post Moderation console (`admin/manage-posts.php`) and Job Moderation console (`admin/manage-jobs.php`).

### Phase 8: Hardening, Polish & Documentation (Completed)
- [x] Comprehensive audit of CSRF tokens, prepared statements, and XSS escaping across all forms and endpoints.
- [x] Secure file upload folder (`uploads/`) with strict MIME verification (`finfo`), randomized file names, and `.htaccess` script execution prevention.
- [x] Protective `index.html` and `index.php` access blocks on internal directories (`uploads/`, `config/`, `includes/`, `database/`).
- [x] Security headers (`X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`).
- [x] Custom administration stylesheet (`assets/css/admin.css`) and responsive layout refinement.
- [x] Mobile responsiveness, cross-browser compatibility, and performance optimization.
- [x] Complete project documentation with detailed setup guide and test accounts in `README.md`.
