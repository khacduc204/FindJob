# JobFind Copilot Instructions

## Quick Context
- JobFind is a TopCV-inspired PHP/MySQL job portal scaffold that runs directly under Apache/XAMPP; no framework or composer autoloading is used.
- Sessions, mysqli connection, charset, upload directories, and the base schema/migration pointers (see [db/schema.sql](db/schema.sql) plus [db/migrations](db/migrations) and [db/seed.php](db/seed.php)) are centralized in [config/config.php](config/config.php); always include it first instead of calling `session_start()` manually.
- Ready-made demo accounts and UX expectations are captured in [README_AUTH.md](README_AUTH.md) and [README_WEBSITE.md](README_WEBSITE.md).

## Architecture & Routing
- There is no central router; every page under [public](public) or [admin](admin) is a standalone entrypoint that manually requires the needed controllers/models and usually renders inline HTML guarded by shared includes.
- Shared headers/footers and asset stacks live in [public/includes](public/includes) and [admin/includes](admin/includes); update these when adding global CSS/JS.
- Controllers in [app/controllers](app/controllers) orchestrate high-level flows, but rendering still happens inside the public/admin scripts.

## Data Layer Patterns
- All models extend [app/models/Database.php](app/models/Database.php), rely on prepared statements, and only fall back to raw queries for read-only listings as shown in [app/models/User.php](app/models/User.php) and [app/models/Employer.php](app/models/Employer.php).
- `Job` status values are limited to `draft|published|closed` per [app/models/Job.php](app/models/Job.php); `Application` statuses map to `applied|viewed|shortlisted|rejected|hired|withdrawn` in [app/models/Application.php](app/models/Application.php) and the model auto-patches missing enum/columns at runtime.
- Candidate and employer rows get auto-created when missing via [app/controllers/JobController.php](app/controllers/JobController.php) (`ensureEmployer`) and [app/controllers/CandidateController.php](app/controllers/CandidateController.php) / [app/models/SavedJob.php](app/models/SavedJob.php); rely on these helpers instead of duplicating inserts.

## Authentication, Roles & Permissions
- `AuthController` in [app/controllers/AuthController.php](app/controllers/AuthController.php) handles register/login, hashes passwords, seeds `$_SESSION['user_id']` and `$_SESSION['role_id']`, and redirects based on `role_id` (1=admin, 2=employer, 3=candidate).
- Login and register flows in [public/account/login.php](public/account/login.php) and [public/account/register.php](public/account/register.php) demonstrate how to call the controller and route users; mimic their redirect logic for new auth surfaces.
- Admin ACL checks use the function + class combo in [app/controllers/AuthMiddleware.php](app/controllers/AuthMiddleware.php) backed by [app/models/Permission.php](app/models/Permission.php); when adding gated pages, call `checkPermission()` with the permission slug stored in `permissions`/`role_permissions` tables.

## Frontend Surfaces
- The marketing homepage [public/index.php](public/index.php) queries stats via `Job`, `Employer`, `User`, and `SavedJob` models and wires data attributes consumed by [public/assets/js/homepage.js](public/assets/js/homepage.js); follow this pattern for other data-driven landing sections.
- Candidate dashboards run through [public/dashboard.php](public/dashboard.php), which switches layouts per role and pulls shared styling from [public/assets/css/dashboard.css](public/assets/css/dashboard.css).
- When linking assets or internal routes, always use the `BASE_URL` and `ASSETS_URL` constants from [config/config.php](config/config.php) to avoid broken paths under XAMPP subfolders.

## File Uploads & Media
- Avatar uploads are centralized in [app/helpers/avatar.php](app/helpers/avatar.php); use `handle_avatar_upload()` and `remove_avatar_file()` so size/mime constraints and thumbnail generation stay consistent.
- Employer logos follow the analogous helpers inside [app/helpers/company_logo.php](app/helpers/company_logo.php) and CV uploads use [app/helpers/cv.php](app/helpers/cv.php); never write ad-hoc `move_uploaded_file` logic.
- Uploaded files live under [public/uploads](public/uploads); returned paths are web-relative (e.g., `uploads/avatars/...`) and should be concatenated with `BASE_URL` when rendered.

## Database & Seed Data
- Import `db/schema.sql` once to create tables plus default roles/permissions/test data; `db/seed.php` is safe to rerun for refreshing fixtures.
- Extra migrations (e.g., `2025-11-17-add-withdrawn-status.sql`) document schema drift; update both the SQL file and the relevant model guards (`ensureDecisionNoteColumn`, `ensureCvColumns`) to keep runtime checks aligned.
- The portal expects the `jobfinder` database name and `root` user by default; override the constants near the top of [config/config.php](config/config.php) if your setup differs.

## Developer Workflow
- Run the site by pointing Apache/Nginx to the [public](public) folder or by visiting `http://localhost/JobFind/public/...` inside XAMPP.
- Use `php tools/smoke_test.php` for a quick DB connectivity + user-creation check; it exits non-zero on connection failures so it doubles as a smoke test.
- Lightweight diagnostics such as [test_db.php](test_db.php) and [test_auth.php](test_auth.php) hit the same models without rendering HTML—handy when verifying credentials or permissions.

## Implementation Tips
- Reuse the existing `includes/header.php` / `footer.php` stacks when adding new public pages so global flash messages, meta tags, and asset inits remain consistent.
- When extending job or talent flows, call the aggregation helpers in [app/models/Job.php](app/models/Job.php) (`getHotJobs`, `getTopCategories`, `getPopularKeywords`) and the auto-creation helpers mentioned above rather than hand-writing SQL/inserts.
- Stick to the established naming: controllers return associative arrays, models return mysqli result sets or arrays, and success responses mirror the `['success' => bool, 'message' => ...]` convention used in `AuthController`.

## QA & Debugging
- Broken redirects or asset paths usually come from missing `BASE_URL`, and permission denials land on [public/403.php](public/403.php); inspect `$currentUri` usage in [public/index.php](public/index.php) and log the evaluated `role_id` plus permission slug before calling `checkPermission()` to debug quickly.
- Because models sometimes issue best-effort schema alterations, database users need `ALTER` privileges in local dev; without them you'll see silent failures when `Application` or `Candidate` tries to patch columns.
