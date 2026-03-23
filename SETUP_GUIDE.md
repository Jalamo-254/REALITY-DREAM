# Reality Dream Institute Setup Guide

This project uses MySQL/MariaDB for contact submissions, enrollments, and admin login.

## 1) Start services

1. Open XAMPP Control Panel.
2. Start `Apache`.
3. Start `MySQL`.

## 2) Create database tables

Run [`DATABASE_SETUP.sql`](./DATABASE_SETUP.sql) in phpMyAdmin:

1. Open `http://localhost/phpmyadmin/`.
2. Click `SQL`.
3. Paste the script from `DATABASE_SETUP.sql`.
4. Execute.

The script creates and updates:

1. `contacts`
2. `enrollments`
3. `admin_users`

It also seeds a default admin user if missing:

1. Username: `admin`
2. Password: `Admin@2026`

## 3) Database config used by website

`db_config.php` defaults:

1. Host: `localhost`
2. User: `root`
3. Password: (empty)
4. Database: `Reality_Dream`

You can override via environment variables:

1. `DB_HOST`
2. `DB_USER`
3. `DB_PASSWORD`
4. `DB_NAME`

## 4) Local URLs

1. Main site: `http://localhost/Reality-Dream-Institute-main-main/index.html`
2. Contact page: `http://localhost/Reality-Dream-Institute-main-main/contact.php`
3. Enroll page: `http://localhost/Reality-Dream-Institute-main-main/enroll.php`
4. Admin login: `http://localhost/Reality-Dream-Institute-main-main/login.php`
5. Admin dashboard: `http://localhost/Reality-Dream-Institute-main-main/admin.php`

## 5) Optional: SMTP mail setup

Mail helper is in `mail_config.php`.

For better delivery, install Composer dependencies:

```bash
composer install
```

Then configure SMTP-related variables used in `mail_config.php`.

