<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

$user = require_user_access();
if (is_admin()) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Admin is not allowed.';
    exit;
}
if ((string)$user['role'] !== 'vet') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Vet only.';
    exit;
}

// Extract Bearer token from Authorization header.
// Note: In some Apache/PHP configurations (common on XAMPP), the Authorization header is not
// passed into $_SERVER unless explicitly forwarded. We therefore try multiple sources.
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

if ($auth === '' && function_exists('getallheaders')) {
    $headers = getallheaders();
    if (is_array($headers)) {
        // handle different casing
        foreach ($headers as $k => $v) {
            if (strcasecmp((string)$k, 'Authorization') === 0) {
                $auth = (string)$v;
                break;
            }
        }
    }
}
$token = '';
if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
    $token = trim($m[1]);
} elseif (isset($_GET['token'])) {
    // Fallback for servers that strip Authorization headers
    $token = trim((string)$_GET['token']);
}

if ($token === '') {
    http_response_code(401);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Missing token.';
    exit;
}

$payload = jit_verify_token($token);
if (!$payload) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Token invalid or expired.';
    exit;
}

if ((int)($payload['sub'] ?? 0) !== (int)$user['id'] || (string)($payload['role'] ?? '') !== 'vet') {
    http_response_code(401);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Token does not match this session.';
    exit;
}

try {
    $patients = patient_previous_list(50);

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'server_time_utc' => gmdate('Y-m-d H:i:s') . ' UTC',
        'token_exp_utc'   => gmdate('Y-m-d H:i:s', (int)$payload['exp']) . ' UTC',
        'patients'        => $patients,
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    $msg = $e->getMessage();
    // Provide a clearer hint for the most common local setup issue.
    if (stripos($msg, 'Unknown database') !== false || stripos($msg, 'Base table or view not found') !== false) {
        echo "Server error: patient demo database is not initialized. Import sql/patient_demo_schema.sql in phpMyAdmin.\n";
        echo "Details: " . $msg;
    } else {
        echo 'Server error: ' . $msg;
    }
}
