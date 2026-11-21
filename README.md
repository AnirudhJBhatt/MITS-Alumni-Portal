# MITS Connect

> **Official Alumni Engagement, Networking, and Career Platform for Muthoot Institute of Technology and Science (MITS)**  
> Built with HTML5, CSS3, Vanilla JavaScript, Bootstrap 5.3, Procedural PHP 8+, and MySQL.

---

## 🎨 Design System & Theme
- **Primary Brand Color:** `#d8151c` (MITS Crimson Red)
- **Primary Dark / Hover:** `#b91016`
- **Surface & Background:** Pure White (`#ffffff`) and Crisp Light (`#fcfcfd`)
- **Accents:** Soft rose-red tint (`#fef2f2`), subtle red border (`#fecaca`)
- **Typography:** *Plus Jakarta Sans* (Headings) and *Inter* (Body)
- **Institutional Branding:** Features the official MITS College Logo (`MITS Logo.png`) alongside the OSAM Alumni Association emblem (`OSAM logo.jpg`) in the header and footer.

---

## 🚀 Key Modules & Features

1. **Authentication, Onboarding & Domain Policy:**
   - **Google Identity Services (GIS):** Seamless sign-in using Google One-Tap and standard GIS button with server-side token verification via Google's `oauth2.googleapis.com/tokeninfo`.
   - **Configurable Email Domain Policy:**
     - `BLOCK_COLLEGE_EMAIL_DOMAIN=true`: Rejects student college accounts (`@mgits.ac.in`) and forces permanent personal emails (e.g., Gmail) so alumni never lose portal access when college email IDs are deactivated post-graduation.
     - `RESTRICT_TO_COLLEGE_DOMAIN=true`: Optional inverse mode to restrict sign-ins strictly to institutional Google Workspace domains (`data-hd="mgits.ac.in"`).
     - **Admin Exception (`osam@mgits.ac.in`):** The official college administrative email `osam@mgits.ac.in` (and any comma-separated email in `SUPER_ADMIN_EMAIL`) is explicitly exempted from the college domain block, auto-promoted to `role = 'admin'` and `status = 'verified'`, and routed directly to the Admin Console.
   - **First-Time Onboarding Wizard (`auth/complete-profile.php`):** Captures college roll number, department, graduation batch, employer, and mentorship availability.
   - **Local Dev Switchers:** Fast-login demo buttons for instant testing when `APP_DEBUG = true`.

2. **Alumni Verification & Onboarding Lifecycle:**
   - **Step 1 — Google Sign-In:** Graduate signs in using their permanent personal email (e.g., Gmail). College student IDs (`@mgits.ac.in`) are rejected to preserve long-term access.
   - **Step 2 — Onboarding Wizard:** First-time login automatically redirects to `/auth/complete-profile.php` to collect graduation year, department, university roll number, current organization, and job title.
   - **Step 3 — Access-Controlled Holding Area:** The user is placed in `pending` status and directed to `/auth/pending-verification.php`. Public pages (Homepage, OSAM Gallery) remain accessible, but all community features (Feed, Member Directory, Network, Jobs, Member Profiles) are strictly locked by `includes/auth_check.php`.
   - **Step 4 — Admin Review:** Portal administrators review credentials against institutional student databases in `/admin/verify-alumni.php` and approve or reject the request.
   - **Step 5 — Automated Email Notification:** Approval immediately triggers `includes/mailer.php`, sending a responsive, branded HTML verification email to the alumnus.
   - **Step 6 — Instant Platform Unlock:** The moment verification is approved in the database, the alumnus's next page load or click on the "Check Verification Status" button immediately unlocks full verified access without requiring re-authentication.

3. **Transactional Email Notifications (SMTP / PHP mail):**
   - **Automated Verification Email (`includes/mailer.php`):** When an administrator verifies an alumnus in the queue, a branded, responsive HTML congratulatory email is automatically dispatched to their registered email address.
   - **Zero-Dependency SMTP Socket Client:** Connects directly via TLS (port 587) or SSL (port 465) with base64 AUTH LOGIN, falling back to native PHP `mail()`.
   - **Audit Logging:** Every email attempt is logged with timestamp, recipient, and status in `uploads/mail_logs/`.

4. **Alumni Profiles & Directory:**
   - **Public Profile View (`profile.php`):** Highlights department, graduation batch, verified college status badge, current designation, employer, location, LinkedIn link, and mentorship status.
   - **Profile Editor (`edit-profile.php`):** Photo upload with MIME verification (`image/jpeg`, `image/png`, `image/webp`), size cap, and live preview.
   - **Alumni Directory (`directory.php`):** Protected member directory with real-time text search and multi-filtering by Department, Graduation Batch, and Mentors-only toggle. Verified alumni are given priority ranking.

5. **Networking & Connections Hub (`network.php`):**
   - **My Connections:** Grid of active connections with email triggers and disconnect options.
   - **Invitations Received:** Pending connection requests with instant Accept / Decline actions.
   - **Sent Requests:** Outgoing outreach with Cancel/Withdraw options.
   - **Recommended Alumni:** Smart recommendation engine calculating relevance based on shared department, graduation batch ($\pm 2$ years), and mentorship.

6. **Community Discussion Feed (`feed.php`):**
   - Three-column layout (mini user overview, stream, campus & job widgets).
   - Rich post composer with category selector (💬 *General*, 💼 *Hiring Referral*, 🏆 *Achievement*, 📅 *College Event*) and image attachments.
   - Verified alumnus checkmark (`<i class="bi bi-patch-check-fill"></i>`) next to verified author names.
   - Asynchronous Like/Unlike toggling with optimistic UI updates and live counters.
   - Expandable threaded comments with dynamic list rendering.

7. **Career & Job Board (`jobs.php`):**
   - Job listings with filters for Full-Time, Internship, Remote, and Part-Time roles.
   - Comprehensive Job Details Modal with structured requirements, poster credentials, and deadline countdowns.
   - Opportunity Creator (`post-job.php`) with dual application pathways (direct career URL and referral email).

8. **Event Gallery & Unified Album Management:**
   - **Public Gallery (`gallery.php`):** Grouped by events with high-resolution lightbox viewing.
   - **Admin Management (`admin/manage-gallery.php`):**
     - **Unified Creation Flow:** Create an event and simultaneously upload multiple gallery photos in one submission.
     - **Multi-File Upload:** File picker with real-time selection counter badge.
     - **Event Editing:** Dedicated modal (`#editEventModal`) to update event title, date, venue, description, and replace/retain cover photos.
     - **Live Filter:** Instant search filter to find albums by title or venue.

9. **Institutional Admin Dashboard (`admin/index.php`):**
   - Live analytics metric cards (Registered Alumni, Pending Queue, Active Jobs, Discussion Posts).
   - **Alumni Verification Queue (`admin/verify-alumni.php`):** Review roll numbers against institutional records to approve or reject alumni accounts, automatically dispatching activation emails upon approval.
   - **User & Role Management (`admin/manage-users.php`):** Assign roles (Admin, Alumni, Student), manage suspension states, and toggle verification with email notifications.
   - **Content Moderation (`admin/manage-posts.php`, `admin/manage-jobs.php`):** Oversight and deletion of flagged posts or obsolete job listings.

---

## 📁 Directory Structure

```text
MITS-Alumni-Portal/
├── .env                       # Environment credentials (DB, API keys, secrets, SMTP - git-ignored)
├── .env.example               # Template for environment configuration
├── .gitignore                 # Excludes .env, sensitive logs, and user upload files
├── .htaccess                  # Enterprise Apache security, clean URLs, HTTPS & hardening
├── config/
│   ├── env.php                # Lightweight native .env parser & env() helper
│   ├── config.php             # App constants, BASE_URL, Google Client ID, email policies, timezone
│   ├── db.php                 # Procedural MySQLi connection with utf8mb4 encoding
│   └── session.php            # Secure cookie configurations & session start
│
├── includes/
│   ├── header.php             # HTML head, Bootstrap 5.3 CSS, meta tags, CSRF token
│   ├── navbar.php             # Responsive top navbar with dynamic auth status & verification badge
│   ├── footer.php             # Footer layout, MITS branding links, toast container, JS scripts
│   ├── functions.php          # Core procedural helpers (XSS escaping, CSRF, flash, queries)
│   ├── mailer.php             # Transactional email dispatcher (SMTP socket client, mail(), templates)
│   ├── auth_check.php         # Gatekeeper: enforces login, profile completion & admin verification
│   └── admin_check.php        # Gatekeeper: restricts access strictly to admin role
│
├── auth/
│   ├── login.php              # Sign-In page with Google Identity Services (GIS) widget & alumni notice
│   ├── google-callback.php    # Server-side Google token verification, domain policy, session creation
│   ├── complete-profile.php   # Onboarding wizard for new alumni (roll no, branch, batch)
│   ├── pending-verification.php # Holding page for alumni awaiting admin credential verification
│   └── logout.php             # Destroys session, clears cookies, redirects to login
│
├── api/                       # Asynchronous JSON REST endpoints
│   ├── posts.php              # Feed: create, fetch, delete posts
│   ├── likes.php              # Toggle post likes (AJAX)
│   ├── comments.php           # Add, retrieve, delete post comments (AJAX)
│   ├── connections.php        # Send, accept, decline, remove connections
│   ├── jobs.php               # Job queries and quick application modal data
│   └── notifications.php      # Unread badge counter & mark-read actions
│
├── admin/
│   ├── index.php              # Dashboard overview & platform metric cards
│   ├── verify-alumni.php      # Verification queue with automated approval email dispatch
│   ├── manage-users.php       # User directory, role assignment, status toggling
│   ├── manage-posts.php       # Content moderation and post deletion
│   ├── manage-jobs.php        # Job listing review & status toggle
│   └── manage-gallery.php     # Unified event creation, album editing & photo uploads
│
├── assets/
│   ├── css/
│   │   ├── style.css          # Custom styling in MITS Red (#d8151c) & White
│   │   └── admin.css          # Dedicated Admin Console styles & tables
│   ├── js/
│   │   ├── main.js            # General UI helpers, toast alerts, notification poller
│   │   ├── auth.js            # GIS token receiver and AJAX dispatcher
│   │   ├── feed.js            # Social feed interactions (likes, comments, media preview)
│   │   └── network.js         # Connection requests & acceptance AJAX handling
│   └── images/
│       ├── MITS Logo.png      # Official MITS College Logo (Header & Footer)
│       ├── OSAM logo.jpg      # Official OSAM Alumni Association Logo
│       ├── College Image.jpeg # Campus banner image
│       └── default-avatar.png # Fallback avatar
│
├── uploads/                   # Upload storage (protected by .htaccess)
│   ├── avatars/               # User profile pictures
│   ├── posts/                 # Images attached to community posts
│   ├── gallery/               # Event covers and alumni memories
│   ├── mail_logs/             # Transactional email dispatch audit logs
│   └── resumes/               # Candidate resumes
│
├── database/
│   ├── schema.sql             # Complete table structures, constraints, and indexes
│   └── seed.sql               # Default admin user and sample test data
├── index.php                  # Landing Page: Hero with Campus Background Carousel, OSAM Overview
├── gallery.php                # Campus & Event Gallery (grouped by events with lightbox)
├── feed.php                   # Alumni community feed
├── directory.php              # Filterable Alumni Directory (Verified access)
├── profile.php                # View user / alumni profile
├── edit-profile.php           # Edit bio, skills, work experience, photo upload
├── network.php                # Connection hub (Connections, Invitations, Sent, Recommendations)
├── jobs.php                   # Career opportunities board with search & filters
├── post-job.php               # Form for alumni & admins to post new opportunities
├── notifications.php          # Detailed notifications activity page
└── PROJECT_PLAN.md            # Comprehensive project architecture & roadmap
```

---

## 🛠️ Installation & Setup (XAMPP)

### 1. Prerequisites
- **XAMPP** installed with Apache and MySQL services running.
- **PHP 8.0+** enabled with `openssl`, `curl`, and `mysqli` extensions.

### 2. Database Import
1. Open phpMyAdmin at `http://localhost/phpmyadmin/`.
2. Create a new database named `mits_alumni_portal`.
3. Import `database/schema.sql`.
4. *(Optional)* Import `database/seed.sql` to populate sample verified alumni, admin accounts, and job postings:
   ```bash
   C:\xampp\mysql\bin\mysql.exe -u root < database/schema.sql
   C:\xampp\mysql\bin\mysql.exe -u root < database/seed.sql
   ```

### 3. Application Configuration (.env)
The portal uses an environment file (`.env`) for sensitive credentials and policies:

```bash
cp .env.example .env
```

Key variables in `.env`:
```dotenv
# Application Environment
APP_ENV=development
APP_DEBUG=true

# Database Credentials
DB_HOST=localhost
DB_PORT=3306
DB_NAME=mits_alumni_portal
DB_USER=root
DB_PASS=

# Google OAuth 2.0 Web Client
GOOGLE_CLIENT_ID="YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com"
GOOGLE_CLIENT_SECRET=

# Super Administrator Email (Auto-promoted on first Google Sign-In, exempt from college domain block)
SUPER_ADMIN_EMAIL=osam@mgits.ac.in,admin@mits.ac.in

# Email Domain Policy
COLLEGE_EMAIL_DOMAINS="mgits.ac.in,mits.ac.in"
BLOCK_COLLEGE_EMAIL_DOMAIN=true    # Forces personal emails (e.g. Gmail) for alumni (osam@mgits.ac.in is exempted)
RESTRICT_TO_COLLEGE_DOMAIN=false   # Set true only if restricting strictly to college email

# SMTP Email Configuration (Used for verification emails)
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=noreply@mits.ac.in
MAIL_FROM_NAME="MITS Connect"
```

---

## 🌐 Hostinger VPS Deployment Guide (Ubuntu / Debian Apache 2.4)

### 1. Enable Required Apache Modules
```bash
sudo a2enmod rewrite headers expires deflate ssl
sudo systemctl restart apache2
```

### 2. Configure VirtualHost & Enable `.htaccess` Overrides
Edit your Apache virtual host file (e.g. `/etc/apache2/sites-available/yourdomain.conf`):
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

### 3. Deploy Files & Set Permissions
```bash
sudo chown -R www-data:www-data /var/www/html
sudo find /var/www/html -type d -exec chmod 755 {} \;
sudo find /var/www/html -type f -exec chmod 644 {} \;
sudo chmod -R 775 /var/www/html/uploads
```

### 4. Create Production MySQL Database
```bash
sudo mysql -u root -p
```
```sql
CREATE DATABASE mits_alumni_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mits_user'@'localhost' IDENTIFIED BY 'StrongPasswordHere!';
GRANT ALL PRIVILEGES ON mits_alumni_portal.* TO 'mits_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```
Import schema:
```bash
mysql -u mits_user -p mits_alumni_portal < database/schema.sql
mysql -u mits_user -p mits_alumni_portal < database/seed.sql
```

### 5. Configure Production Environment (.env)
```bash
cp /var/www/html/.env.example /var/www/html/.env
```
Update `/var/www/html/.env` with your production settings:
```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=localhost
DB_PORT=3306
DB_NAME=mits_alumni_portal
DB_USER=mits_user
DB_PASS=StrongPasswordHere!

GOOGLE_CLIENT_ID="YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com"
SUPER_ADMIN_EMAIL=osam@mgits.ac.in,admin@mits.ac.in

BLOCK_COLLEGE_EMAIL_DOMAIN=true
RESTRICT_TO_COLLEGE_DOMAIN=false

MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-google-app-password
MAIL_FROM_ADDRESS=noreply@mits.ac.in
MAIL_FROM_NAME="MITS Connect"
```
Secure file permissions:
```bash
chmod 600 /var/www/html/.env
chown www-data:www-data /var/www/html/.env
```

### 6. Free SSL Certificate with Let's Encrypt (Certbot)
```bash
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
```

---

## 🔐 Google Cloud Console Setup Guide

1. Go to the [Google Cloud Console](https://console.cloud.google.com/).
2. Create a new project named **MITS Alumni Portal**.
3. Under **APIs & Services > OAuth consent screen**:
   - Choose **External** User Type.
   - Enter App name: `MITS Alumni Portal` and user support email.
4. Under **APIs & Services > Credentials**:
   - Click **Create Credentials > OAuth client ID**.
   - Application type: **Web application**.
   - Name: `MITS Alumni Web Client`.
   - **Authorized JavaScript origins:**
     - `http://localhost` (for local development)
     - `https://yourdomain.com` (for production)
   - **Authorized redirect URIs:**
     - `http://localhost/MITS-Alumni-Portal/auth/login.php`
     - `https://yourdomain.com/auth/login.php`
5. Copy the generated **Client ID** into `.env`:
   ```dotenv
   GOOGLE_CLIENT_ID="YOUR_COPIED_CLIENT_ID.apps.googleusercontent.com"
   ```

---

## 🧪 Demo Accounts (Local Development)

When `APP_DEBUG=true` in `.env`, fast-login demo switchers appear on the login page for rapid module testing:

| Account Name | Email | Role | Verification Status | Features to Test |
|---|---|---|---|---|
| **OSAM Administrator** | `osam@mgits.ac.in` | `admin` | Verified | Admin Dashboard, Verification Queue, User Moderation *(Exempt from college domain block)* |
| **MITS Secondary Admin** | `admin@mits.ac.in` | `admin` | Verified | Admin Console, Multi-admin support |
| **Kavya Sharma** | `kavya.sharma@example.com` | `alumni` | Verified | Community Feed, Network, Jobs, Member Directory |
| **Rahul Reddy** | `rahul.reddy@example.com` | `alumni` | Verified | Connections, Mentorship, Job Applications |
| **New Graduate** | `new.alumnus@gmail.com` | `alumni` | Pending | First-Time Profile Wizard & Pending Verification Holding Screen |

---

## 🛡️ Security Architecture

1. **Strict Verification Gatekeeper:** Non-admin alumni cannot access member directories, social feeds, job boards, or member profiles until their credentials are confirmed by an administrator. Live database checks ensure access is granted immediately upon approval.
2. **Environment Credential Isolation (`.env`):** Sensitive database credentials, Google OAuth keys, and SMTP secrets reside exclusively in `.env`, blocked from direct web access by Apache rules.
3. **Google ID Token Server-Side Verification:** Cryptographically validates tokens via Google's tokeninfo API, matching `aud`, `iss`, and expiry.
4. **Alumni Email Policy:** Configurable blocking of college email IDs (`@mgits.ac.in`) to prevent alumni from losing account access when student inboxes are retired.
5. **Session Hardening:** `cookie_httponly = true`, `cookie_samesite = 'Lax'`, `use_strict_mode = 1`, and session ID regeneration on login.
6. **100% Prepared Statements:** Parameterized queries via `mysqli_prepare` and `mysqli_stmt_bind_param`. Zero string concatenation into SQL statements.
7. **CSRF Protection:** Per-session cryptographic CSRF tokens embedded in all forms and injected into AJAX headers.
8. **Output Sanitization (XSS):** Dynamic output passed through `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
9. **Upload Hardening:** MIME validation via `finfo_file`, size limitation (5 MB), file renaming, and execution blocking via `uploads/.htaccess`.
10. **Apache Hardening:** Root `.htaccess` enforces HTTPS (with Certbot bypass), blocks access to sensitive files (`.sql`, `.log`, `.env`, `.md`), denies direct requests to internal folders (`config/`, `database/`, `includes/`), and injects modern security headers (HSTS, X-Frame-Options, X-Content-Type-Options).

---

## 📄 License
This project is proprietary to **Muthoot Institute of Technology and Science (MITS)** for alumni community engagement and institutional networking.
