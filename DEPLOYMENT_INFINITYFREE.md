# InfinityFree deployment guide / Gabay sa pag-deploy

**Target layout:** one domain, one document root: `https://YOUR-DOMAIN/`, with `/admin/`, `/students/`, `/counselor/`, and `/reports/`. Use the contents of the `htdocs/` directory as the upload payload; do not upload this repository's parent folders.

> This package is prepared for hosting, not certified production-ready. Live PHP/database version, DNS/TLS, SMTP delivery, file permissions, and every protected workflow still require verification on the actual account.

## 1) Domain, hosting, at document root

1. Add your domain in the InfinityFree control panel and point DNS/nameservers as instructed by the panel. Wait for the domain to become active.
2. Open the hosting account's website root. InfinityFree commonly exposes it as `htdocs`; confirm the exact document root in the account/file manager before uploading.
3. Back up any existing site files first. Upload the **contents** of this project's `htdocs/` into the site root. If the account has hosting-required files, preserve them and merge only the app files; do not replace panel-generated files blindly.
4. The landing page is `index.html`; the portal entry points are `/admin/`, `/students/`, `/counselor/`, `/reports/`.
5. Turn on HTTPS/SSL in the panel and test the domain over HTTPS before entering real records.

## 2) Database / phpMyAdmin

1. Create a MySQL database in the control panel. Record the exact **database hostname, database name, database username, and password** shown there. The hostname must be the supplied `sqlNNN.infinityfree.com`/panel value, **not `localhost`**. Do not try to connect to a free-host database from your laptop; use the PHP app on that hosting account or its phpMyAdmin.
2. In phpMyAdmin, select the new empty database and import `database/schema.sql`. `database/demo_seed.sql` is intentionally empty: no personal records or fake accounts are distributed.
3. The supplied archive's SQL dump contains populated rows and sensitive logs. It is deliberately not part of the deploy ZIP/repository. If you need your own existing records, make a private phpMyAdmin export and migrate it into a separately backed-up database; never import or publish that dump as demo content.
4. Before using `database/migration.sql` on an existing installation: make a full export; run `SELECT VERSION(), @@version_comment;` and inspect `SHOW ENGINES;`; test the migration on a copy; then run each reviewed statement **once**. It changes selected MyISAM tables to InnoDB and invalidates old plaintext password-reset links. It does not drop tables or user rows. Verify available storage, downtime/locking, constraints and index names first. The new schema uses `utf8mb4_unicode_ci` for broad MySQL/MariaDB compatibility instead of MySQL 8-only `utf8mb4_0900_ai_ci`. Collation changes can change unique comparisons: do not bulk-convert an existing live database until duplicate/case-collision checks pass.
5. The schema preserves source table/column/enum relationships and auto-increment behavior; added indexes cover actual role/status, owner/date, and audit-time lookups. The database import was not executable in this workspace (no MySQL client/server available).

InfinityFree's own support material says to use the hostname shown in the panel rather than `localhost`, and documents that free-host databases are available to PHP running within the hosting account/phpMyAdmin, not remote clients: [database connection docs](https://forum.infinityfree.com/docs?topic=49340) and [common connection errors](https://forum.infinityfree.com/t/common-mysql-connection-errors/49338). Confirm current account-specific values in the control panel.

## 3) Secret configuration

1. Edit `htdocs/_private/config.php` **on the server using the file manager**. It is a secret-free placeholder in this ZIP; put real values there only after upload. Alternatively copy `htdocs/config/config.example.php` as a starting point, then move the configured file into `htdocs/_private/config.php`.
2. Set all of these keys: `APP_BASE_URL`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `SMTP_HOST`, `SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `SMTP_ENCRYPTION`, `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME`.
3. `APP_BASE_URL` must be the trusted HTTPS origin, for example `https://your-domain.example` (no trailing slash). Never use a request `Host` header to build email links. The database host is the exact panel value, never `localhost`.
4. `_private/.htaccess` denies direct HTTP access to configuration. Do not put credentials in a public web-root config, SQL dump, issue, screenshot, email, or Git commit. Keep local server configuration out of Git.
5. Set PHP to a supported PHP 8.x release in the panel. The included code uses PHP 8 syntax. Verify PDO MySQL, Fileinfo, OpenSSL and session support in the live account.

## 4) HTTPS, sessions, date/time

- Require HTTPS for sign-in and all portal paths. The shared session cookie is `HttpOnly`, `SameSite=Lax`, and marked `Secure` when PHP reports HTTPS. Check the live proxy's `HTTPS` server variable before accepting real sign-ins.
- PHP timezone and each PDO session use **Asia/Manila / UTC+08:00**. Application-generated SQL `NOW()`/`CURDATE()` runs in that DB session timezone. Existing `TIMESTAMP` columns are stored/returned per MySQL session timezone; `DATETIME` values have no timezone conversion. Keep hosting/database time handling consistent and verify boundary dates around midnight.

## 5) Email setup and delivery testing

- InfinityFree does not provide an SMTP mailbox. Do not rely on `mail()` for password recovery. Configure a third-party SMTP sender and verify its domain/from-address. The included PHPMailer wrapper uses configured SMTP and does not claim successful delivery unless the SMTP send returns success.
- As checked against InfinityFree community support guidance on **2026-10-25**, PHP `mail()` is restricted and external SMTP is supported; current guidance recommends outbound port **587 with TLS/STARTTLS**. Other posts/configurations can differ by account/provider, so verify in the current account and with your SMTP vendor: [outbound SMTP guidance](https://forum.infinityfree.com/t/smtp-support-and-pwa-installation-issues/121369), [SMTP discussion](https://forum.infinityfree.com/t/smtp/112198), [PHP mail restrictions](https://forum.infinityfree.com/t/sending-email-from-your-website-php-mail/49242).
- This workspace has no InfinityFree account or SMTP credentials, therefore email delivery is **not tested**. After configuration, use a real controlled test inbox, verify provider logs and spam folder, and exercise both password reset and account invitation. Never paste SMTP secrets in chat.

## 6) First administrator and initial verification

1. Import schema and set database configuration.
2. Visit `https://YOUR-DOMAIN/setup-first-admin.php`. This one-time endpoint only creates an account while `users` is empty; it asks the operator to provide a password (12+ characters) and has no default password.
3. Sign in at `/admin/`; then remove `setup-first-admin.php` from the server immediately. It will return HTTP 410 once any user exists, but deletion is still required.
4. Verify each role using test accounts, direct URLs, form actions, records, uploads, and logout. Only then use real student/counseling data.

## 7) Upload and folder mapping

Upload `htdocs/` contents as-is. Main paths:

- `admin.codeflix.site/` → `htdocs/admin/`
- `students.codeflix.site/` → `htdocs/students/`
- `public_html/counselor/` → `htdocs/counselor/`
- `reports.codeflix.site/` (recovered from supplied archive) → `htdocs/reports/`
- `public_html/shared/` → `htdocs/shared/`
- `public_html/index.html` → `htdocs/index.html`
- `code2025_login_system.sql` (source dump with populated data) → replaced in deliverables by clean `database/schema.sql`, empty `database/demo_seed.sql`, and reviewed `database/migration.sql`; source dump is excluded.
- Old `/home/code2025/public_html/shared/config/mail_config.php` → shared `htdocs/_private/config.php` + `htdocs/shared/mail.php`; use relative `__DIR__` includes.
- `set_password.php` / `set-password.php` links → unified `htdocs/shared/reset-password.php`; old reset URLs are compatibility redirects, but plaintext tokens issued before the migration will not work.
- `forgot-password.php` (Reports typo/plural differences) → existing portal forgot-password route posting to the shared endpoint.

Uploads are placed under each portal's `uploads/` folder, have a deny-all `.htaccess`, use MIME/size validation and random names, and are read through authenticated avatar endpoints. Do not chmod 777.

## 8) Troubleshooting

- **DB connection refused / “No such file or directory”:** check the exact `sqlNNN` host, database prefix, username/password, selected database, and that PHP is running from this same hosting account. It is not `localhost`.
- **Blank/503 page:** review the hosting's private error log, configuration file path/permissions, PHP version/extensions, and schema import. Public errors intentionally omit SQL/credential details.
- **Reset email not received:** check SMTP config, sender verification, port 587/TLS, provider delivery logs, spam folder, and PHP OpenSSL availability. Verify delivery before relying on account recovery.
- **“Unknown collation utf8mb4_0900_ai_ci”:** use the supplied portable schema for a new DB. For existing data, back up and convert/re-import only after reviewing collation and uniqueness consequences.
- **Forbidden image:** uploads are private by design. Use the portal's authenticated avatar route; do not make upload folders public.
- **404 / links point to subdomains:** upload only the `htdocs` contents, ensure the four folders exist, and configure `APP_BASE_URL` to the root origin.
- **First-admin page says setup disabled:** an account already exists. Sign in or use the portal recovery route after SMTP is tested.
