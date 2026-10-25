# Changelog

## 2026-10-25 — InfinityFree single-domain preparation

### Code and structure
- Added canonical `htdocs/` upload tree with `/admin`, `/students`, `/counselor`, `/reports`; recovered Reports module from supplied archive.
- Added shared application bootstrap, URL/escaping/CSRF/flash/role/PDO helpers and secret-free config template. Added counselor DB/auth/footer files that were missing.
- Removed hard-coded database credentials, `localhost` DB configs, fixed old `/home/code2025/public_html` includes, legacy domain links, and Firebase recovery code from deploy source.
- Wired counselor login to the PHP session/password store and removed the missing Firebase script dependency.
- Implemented shared one-hour SHA-256-hashed, single-use reset links; trusted configured base URL; generic recovery response; password hashing; session ID regeneration after login; secure portal logout.
- Added secure first-admin setup requiring an operator-provided password; route disables itself once any account exists.
- Added CSRF and role/ownership checks for portal sign-in, student profile/messages, counselor case/schedule/message/broadcast/profile actions, admin account creation/deactivation/profile actions, reset and setup forms.
- Added safe upload type/size checks, random image names, deny-all upload folders and authenticated avatar read endpoints.

### UI/UX and performance
- Added shared PHINMA/Union purple-and-gold design tokens, system-font fallback, readable controls, focus states, semantic alerts/badges, responsive layouts, table overflow, reduced-motion support, and duplicate-submit/destructive-confirmation behavior.
- Kept existing PHINMA/Union logos and role-specific screens; losslessly recompressed duplicate PNG logo assets (pixel-difference check: 0); did not add fake records or statistics.
- Converted student pages to shared PDO, selected columns, scoped query IDs, removed N+1 message-name lookups, bounded/paginated user/case/message/report lists, added counselor ownership scope and actual aggregate reporting metrics.
- Removed external Select2/jQuery from the simple case entry form; retained existing chart and icon use where the screens depend on them.

### Security and database
- Removed tracked error logs and excluded the populated source archive from Git/deploy package. The original ZIP remains locally ignored as a backup, not included in the new package.
- Added a clean 10-table schema without INSERT data, empty synthetic seed, existing-installation migration notes, portable `utf8mb4_unicode_ci`, source relationships/auto-increment, and composite indexes for actual list filters/sorts.
- Migration instructions explicitly warn about MyISAM conversion, collation semantics and invalidation of old plaintext reset tokens; no rows are dropped.

### Deployment and verification
- Added `DEPLOYMENT_INFINITYFREE.md`, `AUDIT_AND_IMPLEMENTATION.md`, `TEST_RESULTS.md`, and an InfinityFree ZIP assembled from only the canonical deploy tree, clean database deliverables and guides.
- Static PHP parser and include-target checks passed. Live PHP/database import, functional, browser, SMTP and hosting checks remain outstanding; see `TEST_RESULTS.md`.
