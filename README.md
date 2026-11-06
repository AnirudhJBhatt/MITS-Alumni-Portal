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

---

## 🚀 Key Modules & Features

1. **Authentication & Onboarding (Google Identity Services):**
   - Seamless sign-in using Google Identity Services (GIS) with One-Tap prompt and standard GIS button.
   - Server-side cryptographic token verification via Google's `oauth2.googleapis.com/tokeninfo` endpoint.
   - Dedicated first-time onboarding wizard (`auth/complete-profile.php`) capturing college roll number, department, graduation batch, employer, and mentorship availability.
   - Fast-login demo switchers for local testing in development mode (`APP_DEBUG = true`).

2. **Alumni Profiles & Directory:**
   - **Public Profile View (`profile.php`):** Highlighting department, passing batch, verified college status badge, current designation, employer, location, LinkedIn link, and mentorship status.
   - **Profile Editor (`edit-profile.php`):** Photo upload with MIME verification (`image/jpeg`, `image/png`, `image/webp`), size cap, and live preview.
   - **Alumni Directory (`directory.php`):** Real-time text search and multi-filtering by Department, Graduation Batch, and Mentors-only toggle.

3. **Networking & Connections Hub (`network.php`):**
   - **My Connections:** Grid of active connections with email triggers and disconnect options.
   - **Invitations Received:** Pending connection requests with instant Accept / Decline actions.
   - **Sent Requests:** Outgoing outreach with Cancel/Withdraw options.
   - **Recommended Alumni:** Smart recommendation engine calculating relevance based on shared department, graduation batch ($\pm 2$ years), and mentorship.

4. **Community Discussion Feed (`feed.php`):**
   - Three-column layout (mini user overview, stream, campus & job widgets).
   - Rich post composer with category selector (💬 *General*, 💼 *Hiring Referral*, 🏆 *Achievement*, 📅 *College Event*) and image attachments.
   - Asynchronous Like/Unlike toggling with optimistic UI updates and live counters.
   - Expandable threaded comments with dynamic list rendering.

5. **Career & Job Board (`jobs.php`):**
   - Job listings with filters for Full-Time, Internship, Remote, and Part-Time roles.
   - Comprehensive Job Details Modal with structured requirements, poster credentials, and deadline countdowns.
   - Opportunity Creator (`post-job.php`) with dual application pathways (direct career URL and referral email).

6. **In-App Notifications & Alert Center (`notifications.php`):**
   - Real-time polling badge in navigation bar (`#nav-notif-badge`) signaling unread alerts.
   - Dedicated timeline for likes, comments, connection requests, and administrative verifications.
   - One-click "Mark all as read" capability.

7. **Institutional Admin Dashboard (`admin/index.php`):**
   - Live analytics metric cards (Registered Alumni, Pending Queue, Active Jobs, Discussion Posts).
   - **Alumni Verification Queue (`admin/verify-alumni.php`):** Review roll numbers against institutional records to approve or reject alumni accounts.
   - **User & Role Management (`admin/manage-users.php`):** Assign roles (Admin, Alumni, Student) and manage suspension states.
   - **Content Moderation (`admin/manage-posts.php`, `admin/manage-jobs.php`):** Oversight and deletion of flagged posts or obsolete job listings.

---

## 📁 Directory Structure

```text
MITS-Alumni-Portal/
├── .env                       # Environment credentials (DB, API keys, secrets - git-ignored)
├── .env.example               # Template for environment configuration
├── .gitignore                 # Excludes .env, sensitive logs, and user upload files
├── .htaccess                  # Enterprise Apache security, clean URLs, HTTPS & hardening
├── config/
│   ├── env.php                # Lightweight native .env parser & env() helper
│   ├── config.php             # App constants, BASE_URL, Google Client ID, timezone
│   ├── db.php                 # Procedural MySQLi connection with utf8mb4 encoding
│   └── session.php            # Secure cookie configurations & session start
│
├── includes/
│   ├── header.php             # HTML head, Bootstrap 5.3 CSS, meta tags, CSRF token
│   ├── navbar.php             # Responsive top navbar with dynamic auth status & badges
│   ├── footer.php             # Footer layout, MITS links, toast container, JS scripts
│   ├── functions.php          # Core procedural helpers (XSS escaping, CSRF, flash, queries)
│   ├── auth_check.php         # Gatekeeper: redirects guests & prompts profile completion
│   └── admin_check.php        # Gatekeeper: restricts access to admin role
│
├── auth/
│   ├── login.php              # Sign-In page with Google Identity Services (GIS) widget
│   ├── google-callback.php    # Server-side Google JWT token verification & session establishment
│   ├── complete-profile.php   # Onboarding wizard for new alumni (roll no, branch, batch)
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
│   ├── verify-alumni.php      # Verification queue for pending alumni accounts
│   ├── manage-users.php       # User directory, role assignment, status toggling
│   ├── manage-posts.php       # Content moderation and post deletion
│   ├── manage-jobs.php        # Job listing review & status toggle
│   └── manage-gallery.php     # Event album creation & photo upload management
│
├── assets/
│   ├── css/
│   │   ├── style.css          # Modern custom CSS in MITS Red (#d8151c) and White
│   │   └── admin.css          # Admin-specific styling
│   ├── js/
│   │   ├── main.js            # General UI helpers, toast alerts, notification poller
│   │   ├── auth.js            # GIS token receiver and AJAX dispatcher
│   │   ├── feed.js            # Social feed interactions (likes, comments, media preview)
│   │   └── network.js         # Connection requests & acceptance AJAX handling
│   └── images/
│       ├── OSAM logo.jpg      # Official OSAM / MITS Connect portal logo
│       ├── College Image.jpeg # Campus banner image
│       └── default-avatar.png # Fallback avatar
│
├── uploads/                   # Upload storage (protected by .htaccess)
│   ├── avatars/               # User profile pictures
│   ├── posts/                 # Images attached to community posts
│   ├── gallery/               # Event and alumni group photos
│   └── resumes/               # Optional candidate resumes
│
├── database/
│   ├── schema.sql             # Complete table structures, constraints, and indexes
│   └── seed.sql               # Default admin user and sample test data
├── .htaccess                  # Enterprise Apache security, HTTPS enforcement, headers & caching
├── index.php                  # Unified Landing Page: Hero, OSAM Overview, Vision, Mission & Objectives
├── gallery.php                # Campus & Event Gallery (grouped by events with lightbox)
├── feed.php                   # Alumni community feed
├── directory.php              # Filterable Alumni Directory
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
- **PHP 8.0+** enabled.

### 2. Database Import
1. Open phpMyAdmin at `http://localhost/phpmyadmin/`.
2. Create a new database named `mits_alumni_portal` (or let the script create it).
3. Import `database/schema.sql`.
4. (Optional) Import `database/seed.sql` to populate sample verified alumni, admin accounts, and job postings:
   ```bash
   # Or via terminal:
   C:\xampp\mysql\bin\mysql.exe -u root < database/schema.sql
   C:\xampp\mysql\bin\mysql.exe -u root < database/seed.sql
   ```

### 3. Application Configuration (.env)
The portal uses an environment file (`.env`) for sensitive credentials and environment-specific settings.

1. Copy `.env.example` to `.env` in the root directory (already pre-configured for local XAMPP):
   ```bash
   cp .env.example .env
   ```
2. Verify or update the following variables in `.env`:
   - `DB_NAME`: `mits_alumni_portal`
   - `DB_USER`: `root`
   - `DB_PASS`: *(leave blank for default XAMPP)*
   - `GOOGLE_CLIENT_ID`: Paste your Google Cloud Console OAuth 2.0 Web Client ID.
   - `APP_DEBUG`: Set to `true` during local development (enables fast-login switchers and verbose errors).
   - `SUPER_ADMIN_EMAIL`: `admin@mits.ac.in` (auto-promoted to Admin on first sign-in).

---

## 🌐 Hostinger VPS Deployment Guide (Ubuntu / Debian Apache 2.4)

### 1. Enable Required Apache Modules
Connect to your Hostinger VPS via SSH and enable the necessary Apache modules:
```bash
sudo a2enmod rewrite headers expires deflate ssl
sudo systemctl restart apache2
```

### 2. Configure VirtualHost & Enable `.htaccess` Overrides
Edit your Apache virtual host file (e.g. `/etc/apache2/sites-available/000-default.conf` or your domain config):
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
> [!IMPORTANT]
> `AllowOverride All` is required so Apache processes the security rules in `.htaccess`.

### 3. Deploy Files & Set Permissions
Upload or clone the repository to your document root (e.g. `/var/www/html/`):
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
1. On the VPS server, copy the template to `.env`:
   ```bash
   cp /var/www/html/.env.example /var/www/html/.env
   ```
2. Edit `/var/www/html/.env` with your production values:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com

   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=mits_alumni_portal
   DB_USER=mits_user
   DB_PASS=StrongPasswordHere!

   GOOGLE_CLIENT_ID="YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com"
   SUPER_ADMIN_EMAIL=admin@mits.ac.in
   ```
3. Secure the `.env` permissions so only web server/root can read it:
   ```bash
   chmod 600 /var/www/html/.env
   chown www-data:www-data /var/www/html/.env
   ```
4. In Google Cloud Console: Add your production domain (`https://yourdomain.com`) to **Authorized JavaScript Origins** and **Authorized Redirect URIs**.

### 6. Free SSL Certificate with Let's Encrypt (Certbot)
```bash
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
```
Our `.htaccess` contains a safe bypass for `/.well-known/acme-challenge/`, ensuring Certbot automatic certificate renewals execute without interruption.

---

## 🔐 Google Cloud Console Setup Guide

1. Go to the [Google Cloud Console](https://console.cloud.google.com/).
2. Create a new project named **MITS Alumni Portal**.
3. Under **APIs & Services > OAuth consent screen**:
   - Choose **External** User Type.
   - Enter App name: `MITS Alumni Portal` and user support email.
   - Save and proceed.
4. Under **APIs & Services > Credentials**:
   - Click **Create Credentials > OAuth client ID**.
   - Application type: **Web application**.
   - Name: `MITS Alumni Web Client`.
   - **Authorized JavaScript origins:**
     - `http://localhost`
   - **Authorized redirect URIs:**
     - `http://localhost/MITS-Alumni-Portal/auth/login.php`
5. Copy the generated **Client ID** and paste it into `.env`:
   ```env
   GOOGLE_CLIENT_ID="YOUR_COPIED_CLIENT_ID.apps.googleusercontent.com"
   ```

---

## 🧪 Demo Accounts (Local Development)

When `APP_DEBUG=true` in `.env`, you can test any module instantly on the login page using the **Local Dev Fast-Login** buttons:

| Account Name | Email | Role | Verification Status | Features to Test |
|---|---|---|---|---|
| **MITS Portal Administrator** | `admin@mits.ac.in` | `admin` | Verified | Admin Dashboard, Verification Queue, User Moderation |
| **Kavya Sharma** | `kavya.sharma@example.com` | `alumni` | Verified | Feed, Likes, Comments, Network, Jobs, Edit Profile |
| **Rahul Reddy** | `rahul.reddy@example.com` | `alumni` | Verified | Connections, Mentorship, Job Applications |
| **New Graduate** | `new.student@gmail.com` | `alumni` | Pending | First-Time Profile Onboarding Wizard (`complete-profile.php`) |

---

## 🛡️ Security Architecture

1. **Environment Credential Isolation (`.env`):** Sensitive database credentials, Google OAuth keys, and debug flags are stored exclusively in `.env`, which is excluded from version control via `.gitignore` and shielded from web access by Apache rules.
2. **Google ID Token Server-Side Verification:** Cryptographically validates tokens via Google's tokeninfo API, checking `aud`, `iss`, and expiration.
3. **Strict Session Security:** `cookie_httponly = true`, `cookie_samesite = 'Lax'`, `use_strict_mode = 1`, and session ID regeneration on login.
4. **100% Prepared Statements:** All database queries utilize parameterized `mysqli_prepare`, `mysqli_stmt_bind_param`, and `mysqli_stmt_execute`. Zero raw SQL query string interpolation.
5. **CSRF Protection:** Per-session cryptographic CSRF tokens embedded in all forms and injected into AJAX headers via `apiFetch()`.
6. **Output Sanitization (XSS):** Every dynamic output escaped via `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
7. **Upload Security:** Strict MIME type checking via `finfo_file`, size limitation (5 MB), file renaming, and execution blocking via `uploads/.htaccess`.
8. **Hostinger VPS .htaccess Hardening:** Root `.htaccess` enforces HTTPS (with Certbot bypass), blocks access to sensitive files (`.sql`, `.log`, `.env`, `.md`), denies direct requests to internal folders (`config/`, `database/`, `includes/`), blocks script execution in uploads, filters malicious injection strings, injects modern security headers (HSTS, X-Frame-Options, X-Content-Type-Options, CSP-ready), and configures Gzip compression and browser caching.

---

## 📄 License
This project is proprietary to **Muthoot Institute of Technology and Science (MITS)** for alumni community engagement and institutional networking.
