<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

session_start_safe();

if (is_admin()) {
    flash_set('error', 'Admin cannot change two-step settings.');
    redirect_path(APP_BASE_URL . '/admin/dashboard.php');
}

$user = require_user_access();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_path(APP_BASE_URL . '/index.php');
}

$token = $_POST['csrf_token'] ?? '';
if (!csrf_verify($token)) {
    flash_set('error', 'Invalid request (CSRF).');
    redirect_path(APP_BASE_URL . '/index.php');
}

$enabled = ((string)($_POST['two_step_enabled'] ?? '0') === '1');
user_set_two_step_enabled((int)$user['id'], $enabled);

flash_set('success', $enabled ? 'Two-step verification enabled for next logins.' : 'Two-step verification disabled.');

// Go back to where the user came from, otherwise index
$back = (string)($_SERVER['HTTP_REFERER'] ?? '');
if ($back && strpos($back, APP_BASE_URL) === 0) {
    header('Location: ' . $back);
    exit;
}
redirect_path(APP_BASE_URL . '/index.php');
