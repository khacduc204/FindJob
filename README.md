JobFind - Simple TopCV-like starter (PHP + MySQL)

Quick start

1. Ensure you have PHP and MySQL (XAMPP) running. Place this project in your webroot (e.g., htdocs/JobFind).
2. Create the database and tables by importing `db/schema.sql` using phpMyAdmin or mysql client.
3. Configure DB connection in `config/config.php` if needed (host/user/pass/dbname).
4. Visit these pages to try functionality:
   - Register: /JobFind/public/register.php
   - Login: /JobFind/public/login.php
   - Jobs listing: /JobFind/public/jobs.php
   - Candidate profile: /JobFind/public/candidate/profile.php (after login as candidate)
   - Employer dashboard: /JobFind/public/employer_jobs.php (after login as employer)

Notes

- This is a starter scaffold focusing on backend logic. Add CSRF protection, input validation, file uploads for CVs, email verification, and nicer frontend templates before production.
- Seeded roles and permissions are inserted by `db/schema.sql`.
