<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function session_start_safe(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

function csrf_token(): string {
    session_start_safe();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return hash_hmac('sha256', (string)$_SESSION['csrf_token'], CSRF_KEY);
}

function csrf_verify(?string $token): bool {
    session_start_safe();
    if (!$token || empty($_SESSION['csrf_token'])) return false;
    $expected = hash_hmac('sha256', (string)$_SESSION['csrf_token'], CSRF_KEY);
    return hash_equals($expected, $token);
}

function login_user(int $user_id, string $role, string $email): void {
    session_start_safe();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;
    $_SESSION['role'] = $role;
    $_SESSION['email'] = $email;
    $_SESSION['is_admin'] = false;
}

function login_admin(): void {
    session_start_safe();
    session_regenerate_id(true);
    $_SESSION['user_id'] = 0;
    $_SESSION['role'] = 'admin';
    $_SESSION['email'] = ADMIN_EMAIL;
    $_SESSION['is_admin'] = true;
}

function logout_user(): void {
    session_start_safe();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
}

function current_user_id(): ?int {
    session_start_safe();
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function current_role(): ?string {
    session_start_safe();
    return isset($_SESSION['role']) ? (string)$_SESSION['role'] : null;
}

function current_email(): ?string {
    session_start_safe();
    return isset($_SESSION['email']) ? (string)$_SESSION['email'] : null;
}

function is_admin(): bool {
    session_start_safe();
    return !empty($_SESSION['is_admin']);
}

function require_login(): void {
    if (!current_user_id() && !is_admin()) {
        header('Location: ' . APP_BASE_URL . '/login.php');
        exit;
    }
}

function require_admin(): void {
    if (!is_admin()) {
        header('Location: ' . APP_BASE_URL . '/login.php');
        exit;
    }
}
