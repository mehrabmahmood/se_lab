<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';

session_start_safe();

if (is_admin() || current_user_id()) {
    redirect_path(APP_BASE_URL . '/index.php');
}

$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_verify($token)) {
        $error = 'Invalid request (CSRF). Please try again.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        $pass = (string)($_POST['password'] ?? '');
        [$locked, $sec_left] = login_attempt_check($email);
if ($locked) {
            $error = 'Too many login attempts. Try again in ' . $sec_left . ' seconds.';
        } else {

        // Hardcoded admin
        if (strtolower($email) === strtolower(ADMIN_EMAIL) && $pass === ADMIN_PASSWORD) {
            login_attempt_clear($email);
            login_admin();
            redirect_path(APP_BASE_URL . '/admin/dashboard.php');
        }

        $user = user_find_by_email($email);
        if (!$user) {
            [$nowLocked, $left, $attempts] = login_attempt_record_failure($email);
            $error = $nowLocked ? ('Too many login attempts. Try again in ' . $left . ' seconds.') : 'Email or password is wrong.';
        } else {
            // ban check
            [$banned, $bmsg] = user_is_banned($user);
            if ($banned) {
                $error = $bmsg;
            } elseif (!password_verify_manual($pass, (string)$user['password_salt'], (string)$user['password_hash'])) {
                [$nowLocked, $left, $attempts] = login_attempt_record_failure($email);
                $error = $nowLocked ? ('Too many login attempts. Try again in ' . $left . ' seconds.') : 'Email or password is wrong.';
            } elseif ((int)$user['is_verified'] !== 1) {
                login_attempt_clear($email);
                flash_set('error', 'Please verify your email with OTP first.');
                redirect_path(APP_BASE_URL . '/verify.php?email=' . urlencode((string)$user['email']));
            } else {            // If Two-Step (2FA) is enabled, require an OTP before finishing login.
            login_attempt_clear($email);
            if (user_two_step_enabled($user)) {
                $otpInfo = otp_request_create((string)$user['email'], 'login2fa', (int)$user['id']);
                send_otp_email((string)$user['email'], (string)$otpInfo['otp'], 'login2fa');

                // Store a short-lived "pending login" in session (NOT logged in yet).
                session_start_safe();
                $_SESSION['two_step_pending_user_id'] = (int)$user['id'];
                $_SESSION['two_step_pending_email'] = (string)$user['email'];
                $_SESSION['two_step_pending_started'] = time();

                flash_set('success', 'We sent a login OTP. Please enter it to complete login.');
                redirect_path(APP_BASE_URL . '/two_step.php');
            }

            login_user((int)$user['id'], (string)$user['role'], (string)$user['email']);
            // role-based redirect
                switch ((string)$user['role']) {
                    case 'volunteer':
                        redirect_path(APP_BASE_URL . '/member_dashboard.php');
                        return;
                    case 'vet':
                        redirect_path(APP_BASE_URL . '/dashboard_vet.php');
                        return;
                    case 'farmer':
                    default:
                        redirect_path(APP_BASE_URL . '/home.php');
                }
            }
        }
        }
    }
}

$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h(APP_NAME) ?> - Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
  <div class="w-full max-w-md bg-white rounded-2xl shadow p-6">
    <h1 class="text-2xl font-bold mb-2">Login</h1>
    <p class="text-sm text-gray-600 mb-6">Admin login: <span class="font-mono">admin@gmail.com / admin</span></p>

    <?php if ($flash): ?>
      <div class="mb-4 p-3 rounded <?= $flash['type']==='error' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' ?>">
        <?= h((string)$flash['message']) ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="mb-4 p-3 rounded bg-red-50 text-red-700"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

      <div>
        <label class="block text-sm font-medium">Email</label>
        <input name="email" type="email" value="<?= h($email) ?>" required class="mt-1 w-full rounded-lg border p-2">
      </div>

      <div>
        <label class="block text-sm font-medium">Password</label>
        <input name="password" type="password" required class="mt-1 w-full rounded-lg border p-2">
      </div>

      <button class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg py-2">Login</button>
    </form>

    <div class="mt-4 flex items-center justify-between text-sm">
      <a class="text-green-700 hover:underline" href="<?= h(APP_BASE_URL) ?>/register.php">Create account</a>
      <a class="text-green-700 hover:underline" href="<?= h(APP_BASE_URL) ?>/forgot_password.php">Forgot password?</a>
    </div>
  </div>
</body>
</html>
