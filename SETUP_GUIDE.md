# Reality Dream Institute - Database Setup Guide

## ✅ What Has Been Setup

Your website is now connected to a local MySQL database with the following features:

### Files Created/Modified:
1. **db_config.php** - Database configuration connecting to "Reality Dream" database
2. **contact.php** - Updated to save form submissions to database
3. **admin.php** - Admin dashboard to view all contact form submissions

---

## 🚀 Getting Started

### Step 1: Start XAMPP
1. Open XAMPP Control Panel
2. Click **Start** next to **Apache** and **MySQL**

### Step 2: Create Database (First Time Only)

Go to http://localhost/phpmyadmin/ and run this SQL query:

```sql
CREATE DATABASE IF NOT EXISTS `Reality_Dream`;
USE `Reality_Dream`;

CREATE TABLE IF NOT EXISTS contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    course VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    attachment VARCHAR(255) DEFAULT NULL,
    submitted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'New'
);

CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**OR simply load the site once** - the tables will be created automatically by `db_config.php`.

---

## 🌐 Access Your Website

### Local URLs:
- **Main Website**: [http://localhost/Reality-Dream-Institute-main-main/index.html](http://localhost/Reality-Dream-Institute-main-main/index.html)
- **Contact Form**: [http://localhost/Reality-Dream-Institute-main-main/contact.php](http://localhost/Reality-Dream-Institute-main-main/contact.php)
- **Admin Dashboard**: [http://localhost/Reality-Dream-Institute-main-main/admin.php](http://localhost/Reality-Dream-Institute-main-main/admin.php)

---

## 📝 How It Works

### Contact Form Submission:
1. User fills the contact form on the website
2. Form data is **saved to the database** (contacts table)
3. **Email is sent** to admin and user (as before)
4. Success message is displayed

### Admin Dashboard:
1. View all contact submissions
2. See submission count and status
3. View individual message details
4. Sort by date (newest first)

---

## 🔧 Database Connection Details

**File**: `db_config.php`
```
Host: localhost
User: ouma
Password: jalamo@2025
Database: Reality_Dream
Port: 3306
```

---

## ✨ Features

✅ Automatic table creation on first run
✅ Form validation (client & server side)
✅ Email notifications (admin & user)
✅ Database storage for records
✅ Admin dashboard with search
✅ Responsive design
✅ Status tracking (New/Reviewed)

---

## 📧 Email Settings

Currently emails are sent to:
- **Admin Email**: realitydreaminternational@gmail.com
- **User Email**: Automatically sent to the email they provide

---

## 📦 Install PHPMailer (recommended for reliable email delivery)

PHPMailer is optional but recommended. Install it with Composer from the project root:

```bash
composer install
```

Or to add PHPMailer explicitly:

```bash
composer require phpmailer/phpmailer
```

After installing, open `mail_config.php` and fill in your SMTP provider settings:

- `$MAIL_SMTP_HOST`, `$MAIL_SMTP_PORT`, `$MAIL_SMTP_USERNAME`, `$MAIL_SMTP_PASSWORD`, and optionally `$MAIL_SMTP_SECURE`.

Default admin account (seeded automatically):

- Username: `admin`
- Password: `Admin@2026`

You can change the password via the admin UI (`change_password.php`).

## 🛠️ Troubleshooting

### Issue: "Database Connection Error"
- **Solution**: Make sure MySQL is running in XAMPP
- Check phpmyadmin: http://localhost/phpmyadmin/

### Issue: Form not saving to database
- **Solution**: Check if the `contacts` table exists
- Run the SQL query from Step 2 above

### Issue: Emails not sending
- **Solution**: Your system's mail function may need SMTP configuration
- For local testing, check XAMPP logs in `php/php.ini`

---

## 📁 Project Structure

```
Reality-Dream-Institute-main-main/
├── index.html                 # Main website
├── contact.php               # Contact form (with DB)
├── admin.php                 # Admin dashboard
├── db_config.php             # Database configuration
├── logo.svg                  # Logo
└── Bronchure.pdf            # Brochure
```

---

## 🚀 Next Steps

1. ✅ Database connected
2. ✅ Contact form saves to DB
3. ✅ Admin dashboard ready

**Optional Improvements:**
- Add password protection to admin.php
- Add more courses to the dropdown
- Create export/reporting features
- Add email confirmation for submissions

---

**All set! Your website is now live on localhost with full database integration.** 🎉

