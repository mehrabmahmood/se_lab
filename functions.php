<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';

// -------- helpers --------
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function e(string $s): string {
    return h($s);
}

function redirect_path(string $path): never {
    header('Location: ' . $path);
    exit;
}

function flash_set(string $type, string $message): void {
    session_start_safe();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array {
    session_start_safe();
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function login_url(): string {
    return APP_BASE_URL . '/login.php';
}

function redirect_login(): never {
    header('Location: ' . login_url());
    exit;
}

// -------- Email validation --------
function validate_email_address(string $email): bool {
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    $domain = substr(strrchr($email, '@') ?: '', 1);
    if ($domain === '') return false;

    if (ONLY_GMAIL && strtolower($domain) !== 'gmail.com') return false;

    // must look like a real domain
    if (!preg_match('/^(?:[a-z0-9-]+\.)+[a-z]{2,}$/i', $domain)) return false;

    // check DNS (MX is best, fallback to A)
    if (function_exists('checkdnsrr')) {
        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
    }
    return true; // if DNS check not available
}

// -------- Password rules --------
function validate_password_rules(string $password): array {
    $errors = [];
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Add at least 1 capital letter (A-Z).';
    if (!preg_match('/[a-z]/', $password)) $errors[] = 'Add at least 1 small letter (a-z).';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'Add at least 1 number (0-9).';
    if (!preg_match('/[^A-Za-z0-9]/', $password)) $errors[] = 'Add at least 1 special character (e.g., !@#$%).';
    return $errors;
}

// -------- Manual hashing (PBKDF2) --------
function password_make_salt(): string {
    return bin2hex(random_bytes(16));
}

function password_hash_manual(string $password, string $salt): string {
    $salt_pepper = $salt . PASSWORD_PEPPER;
    return hash_pbkdf2('sha256', $password, $salt_pepper, PBKDF2_ITERATIONS, PBKDF2_LENGTH, false);
}

function password_verify_manual(string $password, string $salt, string $stored_hash): bool {
    return hash_equals($stored_hash, password_hash_manual($password, $salt));
}

// -------- Time helpers --------
function now_utc(): DateTimeImmutable {
    return new DateTimeImmutable('now', new DateTimeZone('UTC'));
}

function utc_plus_minutes(int $minutes): string {
    return now_utc()->add(new DateInterval('PT' . $minutes . 'M'))->format('Y-m-d H:i:s');
}

function utc_plus_seconds(int $seconds): string {
    return now_utc()->add(new DateInterval('PT' . $seconds . 'S'))->format('Y-m-d H:i:s');
}

function utc_plus_days(int $days): string {
    return now_utc()->add(new DateInterval('P' . $days . 'D'))->format('Y-m-d H:i:s');
}

// -------- Login rate limiting --------
// Tracks wrong login attempts per email (NOT per IP). After LOGIN_MAX_ATTEMPTS wrong tries,
// user is blocked for LOGIN_LOCK_SECONDS seconds.
//
function login_attempt_norm_email(string $email): string {
    return strtolower(trim($email));
}

function login_attempt_check(string $email): array {
    // returns [bool $locked, int $seconds_left]
    $email = login_attempt_norm_email($email);
    if ($email === '') return [false, 0];

    $stmt = db()->prepare('SELECT attempts, locked_until FROM login_attempts WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if (!$row) return [false, 0];

    $locked_until = $row['locked_until'] ?? null;
    if ($locked_until) {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string)$locked_until, new DateTimeZone('UTC'));
        if ($dt && $dt > now_utc()) {
            $seconds_left = max(1, $dt->getTimestamp() - now_utc()->getTimestamp());
            return [true, $seconds_left];
        }
        // lock expired => clear attempts
        login_attempt_clear($email);
    }

    return [false, 0];
}

function login_attempt_record_failure(string $email): array {
    // returns [bool $locked, int $seconds_left, int $attempts]
    $email = login_attempt_norm_email($email);
    if ($email === '') return [false, 0, 0];

    // If currently locked, keep it locked
    [$locked, $sec_left] = login_attempt_check($email);
    if ($locked) return [true, $sec_left, (int)LOGIN_MAX_ATTEMPTS];

    // Get current attempts (if any)
    $stmt = db()->prepare('SELECT attempts, locked_until FROM login_attempts WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    $attempts = $row ? (int)$row['attempts'] : 0;

    // If a stale lock exists, treat as new window
    $locked_until = $row['locked_until'] ?? null;
    if ($locked_until) {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string)$locked_until, new DateTimeZone('UTC'));
        if ($dt && $dt <= now_utc()) {
            $attempts = 0;
        }
    }

    $attempts += 1;

    // If reached max attempts, lock for LOGIN_LOCK_SECONDS
    if ($attempts >= (int)LOGIN_MAX_ATTEMPTS) {
        $lock_until = now_utc()->add(new DateInterval('PT' . (int)LOGIN_LOCK_SECONDS . 'S'))->format('Y-m-d H:i:s');

        if ($row) {
            $upd = db()->prepare('UPDATE login_attempts SET attempts = ?, locked_until = ?, updated_at = UTC_TIMESTAMP() WHERE email = ?');
            $upd->execute([$attempts, $lock_until, $email]);
        } else {
            $ins = db()->prepare('INSERT INTO login_attempts (email, attempts, first_attempt_at, locked_until, updated_at) VALUES (?, ?, UTC_TIMESTAMP(), ?, UTC_TIMESTAMP())');
            $ins->execute([$email, $attempts, $lock_until]);
        }

        return [true, (int)LOGIN_LOCK_SECONDS, $attempts];
    }

    // Not locked yet: just update attempts
    if ($row) {
        $upd = db()->prepare('UPDATE login_attempts SET attempts = ?, locked_until = NULL, updated_at = UTC_TIMESTAMP() WHERE email = ?');
        $upd->execute([$attempts, $email]);
    } else {
        $ins = db()->prepare('INSERT INTO login_attempts (email, attempts, first_attempt_at, locked_until, updated_at) VALUES (?, ?, UTC_TIMESTAMP(), NULL, UTC_TIMESTAMP())');
        $ins->execute([$email, $attempts]);
    }

    return [false, 0, $attempts];
}

function login_attempt_clear(string $email): void {
    $email = login_attempt_norm_email($email);
    if ($email === '') return;

    $del = db()->prepare('DELETE FROM login_attempts WHERE email = ?');
    $del->execute([$email]);
}

// -------- OTP helpers --------
function otp_generate(): string {
    $min = 10 ** (OTP_LENGTH - 1);
    $max = (10 ** OTP_LENGTH) - 1;
    return (string)random_int($min, $max);
}

function otp_make_salt(): string {
    return bin2hex(random_bytes(16));
}

function otp_hash(string $otp, string $salt): string {
    return hash_hmac('sha256', $otp . ':' . $salt, OTP_PEPPER);
}

function otp_verify(string $otp, string $salt, string $stored_hash): bool {
    return hash_equals($stored_hash, otp_hash($otp, $salt));
}

// -------- Users --------
function user_find_by_email(string $email): ?array {
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    return $u ?: null;
}

function user_find_by_id(int $id): ?array {
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    return $u ?: null;
}

function user_create(string $name, string $email, string $role, string $hash, string $salt): int {
    $stmt = db()->prepare('INSERT INTO users (full_name, email, role, password_hash, password_salt, is_verified, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 0, UTC_TIMESTAMP(), UTC_TIMESTAMP())');
    $stmt->execute([$name, $email, $role, $hash, $salt]);
    return (int)db()->lastInsertId();
}

function user_update_pending(string $name, string $email, string $role, string $hash, string $salt): void {
    $stmt = db()->prepare('UPDATE users SET full_name = ?, role = ?, password_hash = ?, password_salt = ?, updated_at = UTC_TIMESTAMP() WHERE email = ?');
    $stmt->execute([$name, $role, $hash, $salt, $email]);
}

function user_mark_verified(int $user_id): void {
    $stmt = db()->prepare("UPDATE users SET is_verified = 1, verified_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE id = ?");
    $stmt->execute([$user_id]);
}

function user_update_password(int $user_id, string $hash, string $salt): void {
    $stmt = db()->prepare('UPDATE users SET password_hash = ?, password_salt = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?');
    $stmt->execute([$hash, $salt, $user_id]);
}

function user_two_step_enabled(array $user): bool {
    return (int)($user['two_step_enabled'] ?? 0) === 1;
}

function user_set_two_step_enabled(int $user_id, bool $enabled): void {
    $stmt = db()->prepare('UPDATE users SET two_step_enabled = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?');
    $stmt->execute([$enabled ? 1 : 0, $user_id]);
}

function user_is_banned(array $user): array {
    // returns [bool banned, string message]
    $ban_type = $user['ban_type'] ?? 'none';
    if ($ban_type === 'perm') {
        return [true, 'You are banned permanently.'];
    }
    if ($ban_type === 'temp') {
        $until = $user['ban_until'] ?? null;
        if ($until) {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string)$until, new DateTimeZone('UTC'));
            if ($dt && $dt > now_utc()) {
                return [true, 'You are banned until ' . $dt->format('Y-m-d H:i') . ' (UTC).'];
            }
        }
    }
    return [false, ''];
}

function require_user_access(): array {
    // returns current user array (DB row). redirects if not allowed.
    if (is_admin()) {
        // admin bypass
        return ['id' => 0, 'email' => ADMIN_EMAIL, 'full_name' => 'Admin', 'role' => 'admin'];
    }

    $uid = current_user_id();
    if (!$uid) redirect_login();

    $user = user_find_by_id($uid);
    if (!$user) {
        logout_user();
        redirect_login();
    }

    if ((int)$user['is_verified'] !== 1) {
        flash_set('error', 'Please verify your email (OTP) before using the system.');
        redirect_path(APP_BASE_URL . '/verify.php?email=' . urlencode((string)$user['email']));
    }

    [$banned, $msg] = user_is_banned($user);
    if ($banned) {
        logout_user();
        flash_set('error', $msg);
        redirect_login();
    }

    return $user;
}

function require_farmer_access(): array {
    $user = require_user_access();
    if (is_admin()) return $user;
    if ((string)$user['role'] !== 'farmer') {
        flash_set('error', 'Community Blog & Messages are available for Farmers only.');
        redirect_path(APP_BASE_URL . '/index.php');
    }
    return $user;
}

function require_farmer_only(): array {
    // same as require_farmer_access but admin is not allowed
    $user = require_user_access();
    if (is_admin()) {
        flash_set('error', 'Admin cannot use Messages.');
        redirect_path(APP_BASE_URL . '/admin/dashboard.php');
    }
    if ((string)$user['role'] !== 'farmer') {
        flash_set('error', 'Messages are available for Farmers only.');
        redirect_path(APP_BASE_URL . '/index.php');
    }
    return $user;
}

// -------- OTP Requests --------
function otp_request_create(string $email, string $purpose, int $user_id): array {
    // one active OTP per email+purpose
    $del = db()->prepare('DELETE FROM otp_requests WHERE email = ? AND purpose = ?');
    $del->execute([$email, $purpose]);

    $otp = otp_generate();
    $salt = otp_make_salt();
    $hash = otp_hash($otp, $salt);
    $expires_at = utc_plus_minutes(OTP_TTL_MINUTES);
    $payload = json_encode(['user_id' => $user_id], JSON_UNESCAPED_SLASHES);

    $stmt = db()->prepare('INSERT INTO otp_requests (email, purpose, otp_hash, otp_salt, payload, expires_at, attempts, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, UTC_TIMESTAMP())');
    $stmt->execute([$email, $purpose, $hash, $salt, $payload, $expires_at]);

    return ['otp' => $otp, 'expires_at' => $expires_at];
}

function otp_request_get(string $email, string $purpose): ?array {
    $stmt = db()->prepare('SELECT * FROM otp_requests WHERE email = ? AND purpose = ? LIMIT 1');
    $stmt->execute([$email, $purpose]);
    $r = $stmt->fetch();
    return $r ?: null;
}

function otp_request_expired(array $row): bool {
    $exp = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string)$row['expires_at'], new DateTimeZone('UTC'));
    if (!$exp) return true;
    return $exp < now_utc();
}

function otp_request_increment_attempts(int $id): void {
    $stmt = db()->prepare('UPDATE otp_requests SET attempts = attempts + 1 WHERE id = ?');
    $stmt->execute([$id]);
}

function otp_request_delete(int $id): void {
    $stmt = db()->prepare('DELETE FROM otp_requests WHERE id = ?');
    $stmt->execute([$id]);
}

function otp_request_user_id(array $row): ?int {
    $payload = json_decode((string)($row['payload'] ?? ''), true);
    if (!is_array($payload) || empty($payload['user_id'])) return null;
    return (int)$payload['user_id'];
}

// -------- Blocking --------
function is_blocked_any_direction(int $a, int $b): bool {
    // if a blocks b OR b blocks a
    $stmt = db()->prepare('SELECT 1 FROM blocks WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?) LIMIT 1');
    $stmt->execute([$a, $b, $b, $a]);
    return (bool)$stmt->fetchColumn();
}

function block_add(int $blocker, int $blocked): void {
    if ($blocker === $blocked) return;
    $stmt = db()->prepare('INSERT IGNORE INTO blocks (blocker_id, blocked_id, created_at) VALUES (?, ?, UTC_TIMESTAMP())');
    $stmt->execute([$blocker, $blocked]);
}

function block_remove(int $blocker, int $blocked): void {
    $stmt = db()->prepare('DELETE FROM blocks WHERE blocker_id = ? AND blocked_id = ?');
    $stmt->execute([$blocker, $blocked]);
}

// -------- Uploads --------
function upload_images(array $files, string $kind): array {
    // $kind: 'posts' or 'comments'
    // Returns array of relative paths (e.g., uploads/posts/202601/file.webp)
    $saved = [];

    if (!isset($files['name'])) return $saved;

    $names = $files['name'];
    $tmp = $files['tmp_name'];
    $errors = $files['error'];
    $sizes = $files['size'];
    $types = $files['type'];

    $count = is_array($names) ? count($names) : 0;
    if ($count === 0) return $saved;

    $count = min($count, UPLOAD_MAX_FILES);

    $subdir = 'uploads/' . $kind . '/' . gmdate('Ym');
    $targetDir = __DIR__ . '/' . $subdir;
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0775, true);
    }

    for ($i = 0; $i < $count; $i++) {
        if (!isset($errors[$i]) || $errors[$i] !== UPLOAD_ERR_OK) continue;
        if (!isset($sizes[$i]) || (int)$sizes[$i] > UPLOAD_MAX_BYTES) continue;
        $mime = (string)($types[$i] ?? '');

        // Stronger mime detection
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = finfo_file($finfo, (string)$tmp[$i]);
                finfo_close($finfo);
                if ($detected) $mime = $detected;
            }
        }

        if (!in_array($mime, UPLOAD_ALLOWED_MIME, true)) continue;

        $ext = 'jpg';
        if ($mime === 'image/png') $ext = 'png';
        if ($mime === 'image/webp') $ext = 'webp';

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $targetDir . '/' . $filename;
        if (move_uploaded_file((string)$tmp[$i], $dest)) {
            $saved[] = $subdir . '/' . $filename;
        }
    }

    return $saved;
}

// -------- Blog (posts & comments) --------
function posts_list(int $viewer_id, int $limit = 20): array {
    // Hide posts from users you blocked or who blocked you
    $sql = "
        SELECT p.*, u.full_name, u.role,
          (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id AND c.is_deleted = 0) AS comment_count
        FROM posts p
        JOIN users u ON u.id = p.user_id
        WHERE p.is_deleted = 0
          AND NOT EXISTS (
            SELECT 1 FROM blocks b
            WHERE (b.blocker_id = ? AND b.blocked_id = u.id)
               OR (b.blocker_id = u.id AND b.blocked_id = ?)
          )
        ORDER BY p.created_at DESC
        LIMIT ?
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$viewer_id, $viewer_id, $limit]);
    return $stmt->fetchAll();
}

function post_get(int $viewer_id, int $post_id): ?array {
    $sql = "
      SELECT p.*, u.full_name, u.role
      FROM posts p
      JOIN users u ON u.id = p.user_id
      WHERE p.id = ? AND p.is_deleted = 0
        AND NOT EXISTS (
          SELECT 1 FROM blocks b
          WHERE (b.blocker_id = ? AND b.blocked_id = u.id)
             OR (b.blocker_id = u.id AND b.blocked_id = ?)
        )
      LIMIT 1
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$post_id, $viewer_id, $viewer_id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function post_images(int $post_id): array {
    $stmt = db()->prepare('SELECT path FROM post_images WHERE post_id = ? ORDER BY id ASC');
    $stmt->execute([$post_id]);
    return array_map(fn($r) => (string)$r['path'], $stmt->fetchAll());
}

function post_create(int $user_id, string $title, string $body, array $image_paths): int {
    $stmt = db()->prepare('INSERT INTO posts (user_id, title, body, is_deleted, created_at, updated_at) VALUES (?, ?, ?, 0, UTC_TIMESTAMP(), UTC_TIMESTAMP())');
    $stmt->execute([$user_id, $title, $body]);
    $post_id = (int)db()->lastInsertId();

    if ($image_paths) {
        $ins = db()->prepare('INSERT INTO post_images (post_id, path, created_at) VALUES (?, ?, UTC_TIMESTAMP())');
        foreach ($image_paths as $p) {
            $ins->execute([$post_id, $p]);
        }
    }

    return $post_id;
}

function comments_for_post(int $viewer_id, int $post_id): array {
    // returns flat list; UI will render nested.
    $sql = "
      SELECT c.*, u.full_name, u.role
      FROM comments c
      JOIN users u ON u.id = c.user_id
      WHERE c.post_id = ? AND c.is_deleted = 0
        AND NOT EXISTS (
          SELECT 1 FROM blocks b
          WHERE (b.blocker_id = ? AND b.blocked_id = u.id)
             OR (b.blocker_id = u.id AND b.blocked_id = ?)
        )
      ORDER BY c.created_at ASC
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$post_id, $viewer_id, $viewer_id]);
    return $stmt->fetchAll();
}

function comment_images(int $comment_id): array {
    $stmt = db()->prepare('SELECT path FROM comment_images WHERE comment_id = ? ORDER BY id ASC');
    $stmt->execute([$comment_id]);
    return array_map(fn($r) => (string)$r['path'], $stmt->fetchAll());
}

function comment_create(int $user_id, int $post_id, ?int $parent_id, string $body, array $image_paths): int {
    $stmt = db()->prepare('INSERT INTO comments (post_id, user_id, parent_id, body, is_deleted, created_at, updated_at) VALUES (?, ?, ?, ?, 0, UTC_TIMESTAMP(), UTC_TIMESTAMP())');
    $stmt->execute([$post_id, $user_id, $parent_id, $body]);
    $cid = (int)db()->lastInsertId();

    if ($image_paths) {
        $ins = db()->prepare('INSERT INTO comment_images (comment_id, path, created_at) VALUES (?, ?, UTC_TIMESTAMP())');
        foreach ($image_paths as $p) {
            $ins->execute([$cid, $p]);
        }
    }

    return $cid;
}

// -------- Reports --------
function report_create(int $reporter_id, string $target_type, int $target_id, string $reason): void {
    $stmt = db()->prepare('INSERT INTO reports (reporter_id, target_type, target_id, reason, status, created_at) VALUES (?, ?, ?, ?, \'open\', UTC_TIMESTAMP())');
    $stmt->execute([$reporter_id, $target_type, $target_id, $reason]);
}

function reports_open_list(int $limit = 200): array {
    $stmt = db()->prepare('SELECT r.*, u.full_name AS reporter_name, u.email AS reporter_email FROM reports r JOIN users u ON u.id = r.reporter_id WHERE r.status = \'open\' ORDER BY r.created_at DESC LIMIT ?');
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function report_mark_reviewed(int $report_id, string $admin_email, ?string $note): void {
    $stmt = db()->prepare('UPDATE reports SET status = \'reviewed\', reviewed_at = UTC_TIMESTAMP(), reviewed_by_admin = ?, admin_note = ? WHERE id = ?');
    $stmt->execute([$admin_email, $note, $report_id]);
}

// -------- Admin bans --------
function admin_ban_user_temp(int $user_id, int $days, string $reason): void {
    $until = utc_plus_days($days);
    $stmt = db()->prepare("UPDATE users SET ban_type='temp', ban_until=?, ban_reason=?, banned_at=UTC_TIMESTAMP(), updated_at=UTC_TIMESTAMP() WHERE id=?");
    $stmt->execute([$until, $reason, $user_id]);
}

function admin_ban_user_perm(int $user_id, string $reason): void {
    $stmt = db()->prepare("UPDATE users SET ban_type='perm', ban_until=NULL, ban_reason=?, banned_at=UTC_TIMESTAMP(), updated_at=UTC_TIMESTAMP() WHERE id=?");
    $stmt->execute([$reason, $user_id]);
}

function admin_unban_user(int $user_id): void {
    $stmt = db()->prepare("UPDATE users SET ban_type='none', ban_until=NULL, ban_reason=NULL, banned_at=NULL, updated_at=UTC_TIMESTAMP() WHERE id=?");
    $stmt->execute([$user_id]);
}

function users_list(int $limit = 200): array {
    $stmt = db()->prepare('SELECT id, full_name, email, role, is_verified, ban_type, ban_until, ban_reason, created_at FROM users ORDER BY created_at DESC LIMIT ?');
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}


// -------- Message encryption (AES-256-GCM) --------
// Stored format: ENCv1:<base64(iv|tag|ciphertext)>
// - Database contains unreadable ciphertext
// - App decrypts back to plaintext for display
function message_crypto_key(): string {
    static $key = null;
    if ($key !== null) return $key;

    if (!defined('MESSAGE_ENC_KEY_B64')) {
        throw new RuntimeException('MESSAGE_ENC_KEY_B64 is not defined in config.php');
    }

    $raw = base64_decode((string)MESSAGE_ENC_KEY_B64, true);
    if ($raw === false || strlen($raw) !== 32) {
        throw new RuntimeException('MESSAGE_ENC_KEY_B64 must be a base64-encoded 32-byte key');
    }

    $key = $raw;
    return $key;
}

function message_encrypt_for_db(string $plaintext): string {
    if ($plaintext === '') return '';

    $key = message_crypto_key();
    $iv  = random_bytes(12); // 96-bit nonce for GCM
    $tag = '';

    $ciphertext = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ($ciphertext === false || $tag === '' || strlen($tag) !== 16) {
        throw new RuntimeException('Message encryption failed');
    }

    return 'ENCv1:' . base64_encode($iv . $tag . $ciphertext);
}

function message_decrypt_from_db(string $stored): string {
    if ($stored === '') return '';
    if (strpos($stored, 'ENCv1:') !== 0) {
        // Backward compatible: old plaintext messages
        return $stored;
    }

    $b64 = substr($stored, 6);
    $bin = base64_decode($b64, true);
    if ($bin === false || strlen($bin) < (12 + 16 + 1)) {
        return '[Unable to decrypt message]';
    }

    $iv  = substr($bin, 0, 12);
    $tag = substr($bin, 12, 16);
    $ct  = substr($bin, 28);

    $pt = openssl_decrypt(
        $ct,
        'aes-256-gcm',
        message_crypto_key(),
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ($pt === false) {
        return '[Unable to decrypt message]';
    }

    return $pt;
}


// -------- Messages --------
function messages_thread_list(int $user_id): array {
    // list conversation partners with last message
    $sql = "
      SELECT other_id, other_name, other_role, MAX(created_at) AS last_time
      FROM (
        SELECT m.receiver_id AS other_id, u.full_name AS other_name, u.role AS other_role, m.created_at
        FROM messages m
        JOIN users u ON u.id = m.receiver_id AND u.role = 'farmer'
        WHERE m.sender_id = ? AND m.deleted_by_sender = 0

        UNION ALL

        SELECT m.sender_id AS other_id, u.full_name AS other_name, u.role AS other_role, m.created_at
        FROM messages m
        JOIN users u ON u.id = m.sender_id AND u.role = 'farmer'
        WHERE m.receiver_id = ? AND m.deleted_by_receiver = 0
      ) t
      GROUP BY other_id, other_name, other_role
      ORDER BY last_time DESC
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$user_id, $user_id]);
    return $stmt->fetchAll();
}

function messages_get_thread(int $user_id, int $other_id, int $limit = 200): array {
    $sql = "
      SELECT m.*, su.full_name AS sender_name, ru.full_name AS receiver_name
      FROM messages m
      JOIN users su ON su.id = m.sender_id
      JOIN users ru ON ru.id = m.receiver_id
      WHERE (
        (m.sender_id = ? AND m.receiver_id = ? AND m.deleted_by_sender = 0)
        OR
        (m.sender_id = ? AND m.receiver_id = ? AND m.deleted_by_receiver = 0)
      )
      ORDER BY m.created_at ASC
      LIMIT ?
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$user_id, $other_id, $other_id, $user_id, $limit]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        if (isset($r['body'])) $r['body'] = message_decrypt_from_db((string)$r['body']);
    }
    unset($r);
    return $rows;
}

function message_send(int $from, int $to, string $body): void {
    $enc = message_encrypt_for_db($body);
    $stmt = db()->prepare('INSERT INTO messages (sender_id, receiver_id, body, created_at, deleted_by_sender, deleted_by_receiver) VALUES (?, ?, ?, UTC_TIMESTAMP(), 0, 0)');
    $stmt->execute([$from, $to, $enc]);
}

function message_delete_for_user(int $user_id, int $message_id): void {
    // mark deleted for side. if both deleted => remove row.
    $stmt = db()->prepare('SELECT sender_id, receiver_id, deleted_by_sender, deleted_by_receiver FROM messages WHERE id = ? LIMIT 1');
    $stmt->execute([$message_id]);
    $m = $stmt->fetch();
    if (!$m) return;

    $sender = (int)$m['sender_id'];
    $receiver = (int)$m['receiver_id'];

    if ($user_id !== $sender && $user_id !== $receiver) return;

    if ($user_id === $sender) {
        $upd = db()->prepare('UPDATE messages SET deleted_by_sender = 1 WHERE id = ?');
        $upd->execute([$message_id]);
    } else {
        $upd = db()->prepare('UPDATE messages SET deleted_by_receiver = 1 WHERE id = ?');
        $upd->execute([$message_id]);
    }

    $stmt2 = db()->prepare('SELECT deleted_by_sender, deleted_by_receiver FROM messages WHERE id = ? LIMIT 1');
    $stmt2->execute([$message_id]);
    $m2 = $stmt2->fetch();
    if ($m2 && (int)$m2['deleted_by_sender'] === 1 && (int)$m2['deleted_by_receiver'] === 1) {
        $del = db()->prepare('DELETE FROM messages WHERE id = ?');
        $del->execute([$message_id]);
    }
}

// -------- JIT token (JWT HS256) demo for Vet: Previous Patients --------
// Token format: base64url(header).base64url(payload).base64url(signature)
// - header: {"alg":"HS256","typ":"JWT"}
// - payload includes iat, exp, sub (user id), role

function jit_b64url_encode(string $bin): string {
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}

function jit_b64url_decode(string $b64url): string|false {
    $b64 = strtr($b64url, '-_', '+/');
    $pad = strlen($b64) % 4;
    if ($pad) $b64 .= str_repeat('=', 4 - $pad);
    return base64_decode($b64, true);
}

function jit_issue_token(int $user_id, string $role): array {
    if (!defined('JIT_TOKEN_SECRET') || !defined('JIT_TOKEN_TTL_SECONDS')) {
        throw new RuntimeException('JIT token constants are not defined in config.php');
    }

    $now = time();
    $exp = $now + (int)JIT_TOKEN_TTL_SECONDS;

    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $payload = ['sub' => $user_id, 'role' => $role, 'iat' => $now, 'exp' => $exp];

    $h = jit_b64url_encode((string)json_encode($header, JSON_UNESCAPED_SLASHES));
    $p = jit_b64url_encode((string)json_encode($payload, JSON_UNESCAPED_SLASHES));
    $signing_input = $h . '.' . $p;

    $sig = hash_hmac('sha256', $signing_input, (string)JIT_TOKEN_SECRET, true);
    $jwt = $signing_input . '.' . jit_b64url_encode($sig);

    return ['token' => $jwt, 'payload' => $payload];
}

function jit_verify_token(string $jwt): ?array {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return null;
    [$h, $p, $s] = $parts;

    $h_json = jit_b64url_decode($h);
    $p_json = jit_b64url_decode($p);
    $sig_bin = jit_b64url_decode($s);
    if ($h_json === false || $p_json === false || $sig_bin === false) return null;

    $header = json_decode($h_json, true);
    $payload = json_decode($p_json, true);
    if (!is_array($header) || !is_array($payload)) return null;
    if (($header['alg'] ?? '') !== 'HS256') return null;

    $expected = hash_hmac('sha256', $h . '.' . $p, (string)JIT_TOKEN_SECRET, true);
    if (!hash_equals($expected, $sig_bin)) return null;

    $now = time();
    $exp = (int)($payload['exp'] ?? 0);
    if ($exp <= $now) return null;

    return $payload;
}

function jit_seconds_left(string $jwt): int {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return 0;
    $p_json = jit_b64url_decode($parts[1]);
    if ($p_json === false) return 0;
    $payload = json_decode($p_json, true);
    if (!is_array($payload)) return 0;
    $exp = (int)($payload['exp'] ?? 0);
    $left = $exp - time();
    return $left > 0 ? $left : 0;
}

// -------- Patient demo data (separate DB) --------
function patient_previous_list(int $limit = 50): array {
    $pdo = patient_db();
    $stmt = $pdo->prepare('SELECT id, patient_name, animal_type, complaint, diagnosis, treatment, farmer_name, visited_at FROM previous_patients ORDER BY visited_at DESC LIMIT ?');
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}
