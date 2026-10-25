# Audit & implementation notes

## Brief plan followed
1. Inspect the supplied source archive, repository tree and SQL **structure only**; do not expose personal rows/secrets.
2. Build a canonical one-domain `htdocs/` tree while preserving the PHP modules and routes; repair missing counselor/reports files and centralize shared configuration/session/DB helpers.
3. Patch high-impact blockers first: hard-coded credentials/paths, auth/CSRF on selected actions, SQL injection/IDOR surfaces, password reset, uploads, and portable schema.
4. Apply shared responsive style, test static paths/syntax and package the upload tree separately from the source archive.

## Verified audit findings
- Source locations: `admin.codeflix.site/`, `students.codeflix.site/`, `public_html/counselor/`, `public_html/shared/`, `public_html/index.html`; Reports existed only inside the attached archive. The generated deployment tree restores `reports/`.
- The source SQL dump had 10 tables and populated `INSERT` statements. It included personal records and production-like audit rows; none are included in the clean schema, demo seed, or deploy ZIP. Existing `error_log` files were present in the checkout/archive and have been removed from the deliverable/worktree.
- Source DB configs used a literal DB password and `localhost`; student message/profile code opened a separate MySQLi connection. The shared deployment uses a PDO factory, panel-supplied host settings, `utf8mb4`, prepared statements and DB-session timezone. The legacy DB configuration files are scrubbed and marked as non-deploy; deploy only `htdocs/`.
- Found hard-coded `codeflix.site` routes, an obsolete `/home/code2025/public_html/...` include, different password setup spellings, a Reports reset route targeting a missing file, missing Counselor DB/guard/footer files, Firebase client recovery code despite PHP login, and unneeded references to a missing counselor JS file. These are repaired in canonical `htdocs/` or replaced with shared routes.
- Source SQL included `utf8mb4_0900_ai_ci` (MySQL 8-specific) and mixed InnoDB/MyISAM. The fresh schema uses `utf8mb4_unicode_ci` and retains source engines; optional migration statements document when/why to convert select MyISAM tables. No live target DB/version was available to verify conversions.
- Student dashboard/profile/records/schedule/message pages mixed MySQLi and PDO, used broad `SELECT *`, and had a schedule lookup comparing the numeric user ID to a student number. These student pages now use the shared PDO connection, scoped IDs and limited result sets; student messages join counterpart names in one query and paginate.
- Counselor case joins compared IDs against usernames with `OR`, and case details/status updates lacked owner restrictions. New cases are assigned to the creating counselor; case list/detail/update scope to that counselor. Message and schedule writes validate role/ownership and use CSRF on their forms/actions.
- Existing reset-token flow stored raw tokens in the DB and used inconsistent/fixed domains; Counselor recovery relied on Firebase. Recovery now uses a shared random, one-hour, single-use reset token stored by SHA-256, generic account-enumeration-safe responses, password hashing and a configured base URL. Existing old plaintext tokens require invalidation/reissue.

## Main implementation changes
- `htdocs/_private/bootstrap.php`: shared config loading, Asia/Manila timezone, session cookie options, PDO, URL/escape/CSRF/flash/role helpers.
- Module `config/db.php` wrappers and role guards, plus missing Counselor `config/db.php`, `config/counselor_auth_guard.php`, and `includes/footer.php`.
- Same-origin `/admin`, `/students`, `/counselor`, `/reports` links and shared `APP_BASE_URL` for email/reset URLs.
- Central SMTP PHPMailer adapter uses private config only. Email delivery remains unverified until live credentials/provider tests.
- Password setup/recovery, first-admin bootstrap (no fixed password; disabled if any user exists), POST/CSRF-protected account deactivation, role-checked login/session ID regeneration, role/action guards, and secure private avatar upload/serve paths.
- Shared design tokens and responsive base CSS, semantic form defaults, focus indicators, table scrolling, mobile spacing, empty/alert states, reduced-motion handling and duplicate-submit/confirmation JS. Duplicate PNG logo assets were losslessly recompressed; pixel comparison found zero changed pixels.
- `database/schema.sql` reconstructed from DDL only, with no row records, broad-compatible collation, original AUTO_INCREMENT and relationship definitions, and justified composite indexes.
- `database/demo_seed.sql` is deliberately empty. No invented accounts/statistics were added.

## Known remaining risks / blockers
- No PHP interpreter, MySQL server, browser automation, SMTP account, InfinityFree account or real database were available here. Static parse/path checks are not an end-to-end test.
- The existing app has broad legacy modules. Although role guards and selected record ownership were strengthened, **every** historic report/case/message/profile endpoint and every action must receive live role-by-role and cross-ID testing before use with sensitive data. The code is not certified secure or privacy-compliant.
- Existing Counselor case rows with `counselor_id IS NULL` will not appear in the new counselor-scoped case list. Assign them deliberately after confirming the institution's case assignment rule; no business rule is inferred here.
- Student profile update expects a pre-existing `student_profiles` row because the schema requires a valid `student_id`; it reports missing initialization instead of inventing an identifier.
- Legacy password setup links using unhashed tokens become invalid when the migration is applied. SMTP delivery, rate limiting/abuse controls, account verification, audit-log data minimization/retention, and any regulatory requirements need live review.
- Existing pages outside `htdocs/` are retained only as legacy source/reference; upload the canonical `htdocs/` tree, not the old subdomain directories.
