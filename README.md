# Online Case and Information Management System

This repository keeps the existing PHP/MySQL portals and provides a canonical single-domain InfinityFree deployment under `htdocs/`.

## Portals
- `/admin/` — system administration
- `/students/` — student portal
- `/counselor/` — counseling portal
- `/reports/` — reports portal

## Start here
1. Review `AUDIT_AND_IMPLEMENTATION.md` and `TEST_RESULTS.md` (important unresolved live checks are listed there).
2. Follow `DEPLOYMENT_INFINITYFREE.md` before uploading. Upload the **contents of `htdocs/`** to the hosting document root; do not upload the legacy module folders.
3. Configure secrets only in `htdocs/_private/config.php` on the hosting account. The tracked file contains placeholders only.
4. Import `database/schema.sql`; `database/demo_seed.sql` is intentionally empty. Review/backup before using `database/migration.sql` on an existing database.
5. Create the first administrator at `/setup-first-admin.php`, then remove the setup file from the server.
6. Build/download package: `dist/InfinityFree-Student-Support-Portal.zip` (generated at release time from `htdocs/`, `database/`, and required docs; does not include the original archive or personal data).

`MARCH 29 FULLCODE.zip` is kept only as a local ignored backup; it is not tracked or included in the deployment ZIP. Do not re-add it: it contains populated SQL and private logs. Do not commit hosting credentials or real student/counseling records.

## Development
- Requires PHP 8.x plus PDO MySQL, Fileinfo and sessions. This workspace did not include a PHP interpreter or MySQL server; see static checks and untested workflows in `TEST_RESULTS.md`.
- Shared runtime helpers: `htdocs/_private/bootstrap.php`.
- Shared UI: `htdocs/shared/assets/css/design-system.css`.
- PHPMailer runtime: `htdocs/shared/vendor/PHPMailer/`.
- Legacy subdomain folders are retained as source reference; only `htdocs/` is deployable for the single-domain setup.
