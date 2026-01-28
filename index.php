<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

session_start_safe();

if (is_admin()) {
    redirect_path(APP_BASE_URL . '/admin/dashboard.php');
    exit;
}

$uid = current_user_id();
if (!$uid) {
    redirect_path(APP_BASE_URL . '/login.php');
    exit;
}

$user = user_find_by_id($uid);
if (!$user) {
    logout_user();
    redirect_path(APP_BASE_URL . '/login.php');
    exit;
}

// verified + ban check
require_user_access();

switch (strtolower(trim((string)($user['role'] ?? '')))) {
    case 'volunteer':
        redirect_path(APP_BASE_URL . '/member_dashboard.php');
        exit;

    case 'vet':
        redirect_path(APP_BASE_URL . '/dashboard_vet.php');
        exit;

    case 'farmer':
    default:
        redirect_path(APP_BASE_URL . '/home.php');
        exit;
}
