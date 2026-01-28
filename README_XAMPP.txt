Agrimo (XAMPP) - Full Setup Guide

This project includes:
- Login + Registration (multi-role): farmer / volunteer / vet
- Email OTP verification during registration
- Forgot password + OTP reset
- Manual password hashing (PBKDF2 + salt + pepper)
- Password rules: min 6, 1 uppercase, 1 lowercase, 1 number, 1 special character
- Farmer-only Community Blog (Reddit-like): posts, nested replies, image upload
- Block user (hides posts/comments + disables messaging)
- Report post/comment -> goes to Admin Reports
- Admin can ban user: 1 week (7 days) or lifetime, and unban
- Farmer-only Private Messages: saved in DB, delete from your side

---------------------------------
1) Put project into XAMPP htdocs
---------------------------------
Folder name expected by config.php:
  C:\xampp\htdocs\agrimo-auth\

So you should have:
  C:\xampp\htdocs\agrimo-auth\index.php

Open in browser:
  http://localhost/agrimo-auth/

---------------------------------
2) Create database + import schema
---------------------------------
Open phpMyAdmin:
  http://localhost/phpmyadmin/

Create database:
  agrimo

Import:
  sql/schema.sql

(Default DB config is in config.php: user=root, password='', host=localhost)

---------------------------------
3) Admin login (hardcoded)
---------------------------------
Email:    admin@gmail.com
Password: admin

Admin pages:
  /admin/dashboard.php
  /admin/reports.php
  /admin/users.php

---------------------------------
4) SMTP + PHPMailer (Optional but recommended)
---------------------------------
If you do NOT configure SMTP:
- OTP emails may fail on localhost
- OTP will be written into: storage/otp.log
  (DEV_LOG_OTP = true in config.php)

If you want real email OTP:
A) Install Composer
   Download official Composer installer for Windows:
   https://getcomposer.org/download/

B) Install PHPMailer inside the project folder
   Open Windows CMD or PowerShell:

   cd C:\xampp\htdocs\agrimo-auth
   composer require phpmailer/phpmailer

C) Turn on SMTP in config.php
   Set:
     SMTP_ENABLED = true
     SMTP_USERNAME = your real gmail
     SMTP_PASSWORD = your Gmail App Password
     SMTP_FROM_EMAIL = your real gmail

NOTE: Gmail requires an App Password (not your normal gmail password).

Proxy URL during Composer install:
- If you are NOT using a company proxy, leave it empty / click Next.
- Only fill it if your internet requires a proxy (rare in home networks).

---------------------------------
5) Roles and redirects
---------------------------------
- Farmer -> /home.php (your existing home.html is inside home.php)
- Volunteer -> /dashboard_volunteer.php
- Vet -> /dashboard_vet.php

Blog + Messages are Farmers-only.

---------------------------------
6) Uploads
---------------------------------
- You can upload images in Blog posts and comments.
- Max files per post/comment: 4
- Max size each: 2MB
- Allowed types: JPG, PNG, WEBP

---------------------------------
7) Reporting + Ban system
---------------------------------
- Farmers can report posts/comments.
- Admin reviews reports in /admin/reports.php
- Admin can ban 7 days or lifetime.
- Banned users cannot log in.

---------------------------------
8) Important security note
---------------------------------
Before putting online:
- Change PASSWORD_PEPPER, OTP_PEPPER, CSRF_KEY in config.php
- Move admin credentials to DB (hardcoded admin is NOT secure)

