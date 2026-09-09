# Deploying to Qservers (cPanel)

## 1. Debug output — already off

`includes/config.php` now ships with:

    define('DEBUG_MODE', false);

Nothing further to do. Errors are still recorded to the PHP error log, they
are just no longer printed to visitors.

Two things this covers:

- PHP notices/warnings/fatals are hidden from the page (`display_errors=0`)
  and written to the log (`log_errors=1`).
- A database outage returns HTTP 503 and a generic "temporarily unavailable"
  message. Previously the connect handler did
  `die("Database Connection Failed: " . $e->getMessage())`, which prints
  straight to the page and is NOT suppressed by `display_errors` — so it
  would have shown your database name and user to any visitor even with
  DEBUG_MODE off. The detail now goes to the error log instead.

Set `DEBUG_MODE` back to `true` only on a local copy while debugging.

## 2. Upload

Upload the CONTENTS of this zip into `public_html/` (not the folder itself —
the files must sit at the web root, so `index.php` is `public_html/index.php`).

Keep `vendor/` — it contains PHPMailer. You do not need Composer on the server.

## 3. Run the migrations, in this order

Via cPanel → phpMyAdmin → select the database → SQL tab → paste and run:

1. `DataBase/add_department_level.sql`
   Adds departments.level and sets Computer Science = ND, SW/NT = HND.
   Section 3 lists students already in a mismatched department — an empty
   result means nothing to clean up.

2. `DataBase/add_password_resets.sql`
   Creates the password_resets table for the student and admin reset flows.

3. `DataBase/add_payment_settings.sql`
   Creates payment_settings and seeds it with the fees that were previously
   hardcoded (ND 4000 per pair, HND 2000 per student), so prices do not change
   on deploy. Afterwards the admin sets them at admin/payment_settings.php.
   If this is not run, registration still works at those same fallback prices
   and logs a line saying the migration is outstanding.

`add_payment_tracking.sql` is already applied on your database (its columns
are present in the dump) — do not run it again.

## 4. Create includes/config.local.php on the server

Credentials are NOT in the repository — this repo is public, so a database
password or a Paystack secret key in a tracked file would be published the
moment it is pushed. `includes/config.php` reads them from
`includes/config.local.php`, which is gitignored and NOT in this zip.

On the server:

    cp includes/config.local.example.php includes/config.local.php

then edit it and fill in the real values:

- `db` — host, name, user, password. Qservers may use a host other than
  `localhost`, and cPanel prefixes database names and users with the account
  name.
- `paystack_secret` — the LIVE key (`sk_live_` plus exactly 40 hex
  characters). Confirm it is the right one.
- `smtp` — Gmail on port 587. Some shared hosts block outbound SMTP to
  external servers. If reset and OTP emails stop arriving, that is the first
  thing to check with Qservers support; they may require their own mail
  server or an allowlist entry.
- `debug` — leave `false`.

Set the file's permissions to 600 if cPanel allows it.

Without this file the site returns HTTP 503 and a generic "not configured"
message; the reason is written to the PHP error log. It never prints a
credential.

## 5. Point Paystack at the live domain

The callback URL in your Paystack dashboard must match the deployed domain,
or payments will verify against the wrong host.

## 6. Confirm the file guards survived the upload

These should all return 403 (Forbidden), not 200:

    https://yourdomain/DataBase/add_password_resets.sql
    https://yourdomain/composer.json

If they return 200, Qservers has AllowOverride disabled for your account and
the `.htaccess` rules are being ignored — ask them to enable it. Until then,
delete the `DataBase/` folder from the server after running the migrations.

## 7. Move the existing uploads on the live server

The upload-path bug is fixed in this build, but your LIVE server still has
files in the old location. Before or right after uploading, in cPanel File
Manager (or over SSH):

    mv public_html/student/uploads/passports/*  public_html/uploads/passports/
    mv public_html/student/uploads/projects/*   public_html/uploads/projects/

Check for name collisions first — if a file of the same name already exists
in the target, do NOT overwrite it, since that would be two different
students' photos. Then delete the now-empty `student/uploads/`,
`admin/uploads/`, `department/uploads/` and `includes/uploads/` folders.

No database change is needed: `students.passport` stores a bare filename and
`projects.file_path` keeps its historical `uploads/projects/x.pdf` shape.
Both old and new values resolve through the same helper.

### What was wrong

Upload paths in `includes/config.php` were relative:

    define('UPLOAD_PASSPORT_DIR', 'uploads/passports/');

PHP resolves a relative path against the RUNNING SCRIPT's directory, so
registration (running from `student/`) wrote passports into
`student/uploads/passports/`, while admin pages rendered
`<img src="uploads/passports/...">`, which the browser resolved against
`/admin/` — so every passport 404'd on the admin pages. It also scattered
empty `uploads/` folders through `admin/`, `department/` and `includes/`.

Paths are now absolute (`PROJECT_ROOT . '/uploads/...'`) and views build URLs
with `passport_url()` / `project_url()`, so every page links to the same one
place regardless of which folder it lives in. `BASE_URL` is derived from the
document root, so it yields `/` on the live host and `/project_management/`
on a local WAMP install with no change.
