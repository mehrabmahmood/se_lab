<?php
/**
 * Agrimo Auth + Community (XAMPP defaults)
 * Put folder in: C:\xampp\htdocs\agrimo-auth
 * Open: http://localhost/agrimo-auth/
 */

declare(strict_types=1);

// ===== Database (XAMPP default) =====
const DB_HOST = 'localhost';
const DB_NAME = 'agrimo';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

// ===== Cache Demo Database (separate from main DB) =====
// Used ONLY for the admin "Previous Product Sells" cache demo.
// Create it with the SQL file: agrimo-auth/sql/cache_demo_schema.sql
const CACHE_DB_HOST = 'localhost';
const CACHE_DB_NAME = 'agrimo_cache_demo';
const CACHE_DB_USER = 'root';
const CACHE_DB_PASS = '';
const CACHE_DB_CHARSET = 'utf8mb4';

// ===== Patient Demo Database (separate from main DB) =====
// Used ONLY for the Vet "Previous Patients" JIT token demo.
// Create it with: agrimo-auth/sql/patient_demo_schema.sql
const PATIENT_DB_HOST = 'localhost';
const PATIENT_DB_NAME = 'agrimo_patients_demo';
const PATIENT_DB_USER = 'root';
const PATIENT_DB_PASS = '';
const PATIENT_DB_CHARSET = 'utf8mb4';

// ===== Cache Demo settings =====
// We demonstrate 3 modes (secure cache, insecure cache, no cache) for "Previous Product Sells".
// Cache TTL is 30 seconds (after ~30s, cached data is treated as expired/cleared).
const CACHE_DEMO_TTL_SECONDS = 30;

// ===== JIT token demo settings (Vet - Previous Patients) =====
// We issue a short-lived token (JWT HS256). It expires after 30 seconds.
const JIT_TOKEN_TTL_SECONDS = 30;
// Change this secret before deploying. Keep it private.
const JIT_TOKEN_SECRET = 'CHANGE_ME__jit_token_secret_use_a_long_random_string_64+chars';

// Secure cache is stored under /storage (blocked by .htaccess), so it is not web-accessible.
const CACHE_DEMO_SECURE_CACHE_DIR = __DIR__ . '/storage/cache';

// Insecure cache is stored under a public folder (web-accessible) to demonstrate why that is unsafe.
const CACHE_DEMO_PUBLIC_CACHE_DIR = __DIR__ . '/cache_public';

// (Legacy single-file cache path; kept for compatibility, not used by the 3-mode demo.)
const CACHE_DEMO_FILE = __DIR__ . '/storage/cache/previous_product_sells_secure.json';

// ===== App =====
const APP_NAME = 'Agrimo';
const APP_BASE_URL = 'http://localhost/agrimo-auth';
const SESSION_NAME = 'agrimo_session';

// ===== Admin login (hardcoded) =====
const ADMIN_EMAIL = 'admin@gmail.com';
const ADMIN_PASSWORD = 'admin';

// ===== Security secrets (CHANGE THESE before putting online) =====
// Use long random strings (32+ chars). Do NOT share.
const PASSWORD_PEPPER = 'CHANGE_ME__password_pepper_use_a_long_random_string_32+chars';
const OTP_PEPPER      = 'CHANGE_ME__otp_pepper_use_a_long_random_string_32+chars';
const CSRF_KEY        = 'CHANGE_ME__csrf_key_use_a_long_random_string_32+chars';

// ===== Message encryption key (DO NOT change after you start messaging) =====
// 32-byte random key, base64 encoded. Keep secret.
const MESSAGE_ENC_KEY_B64 = 'rwsrCk8uJo2rx6c8nXJSJxHXVmUPdUMa7kr/4+xp/gU=';

// ===== Manual password hashing (PBKDF2) =====
const PBKDF2_ITERATIONS = 200000; // 200k
const PBKDF2_LENGTH     = 64;     // bytes (stored as hex)

// ===== OTP =====
const OTP_LENGTH = 6;
const OTP_TTL_MINUTES = 10;
const OTP_MAX_ATTEMPTS = 5;

// ===== Login rate limiting =====
// After 3 wrong attempts, block login for 60 seconds (per email + IP).
const LOGIN_MAX_ATTEMPTS = 3;
const LOGIN_LOCK_SECONDS = 60;

// ===== Email rules =====
// true => ONLY allow gmail.com
// false => allow any email, but the domain must be real (DNS MX/A check)
const ONLY_GMAIL = false;

// ===== Email sending =====
// On localhost, PHP mail() usually does not work. Best option is SMTP + PHPMailer.
const SMTP_ENABLED = false; // set true after installing PHPMailer with Composer
const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 587;
const SMTP_USERNAME = 'your@gmail.com';
const SMTP_PASSWORD = 'your_gmail_app_password';
const SMTP_FROM_EMAIL = 'your@gmail.com';
const SMTP_FROM_NAME  = 'Agrimo Support';

// ===== Dev helper =====
// If SMTP is off or email fails, OTP will be written to storage/otp.log so you can test.
const DEV_LOG_OTP = true;
const OTP_LOG_FILE = __DIR__ . '/storage/otp.log';

// OTP for Two-Step (2FA) login will be logged here in local testing.
// (Requested: another opt.log)
const TWO_STEP_OTP_LOG_FILE = __DIR__ . '/storage/opt.log';

// ===== Upload limits =====
const UPLOAD_MAX_FILES = 4;                 // max images per post/comment
const UPLOAD_MAX_BYTES = 2 * 1024 * 1024;   // 2MB each
const UPLOAD_ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];

