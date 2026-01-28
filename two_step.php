<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';

session_start_safe();

if (is_admin() || current_user_id()) {
    redirect_path(APP_BASE_URL . '/index.php');
}

if (isset($_GET['cancel'])) {
    unset($_SESSION['two_step_pending_user_id'], $_SESSION['two_step_pending_email'], $_SESSION['two_step_pending_started']);
    flash_set('success', 'Two-step login canceled. Please login again.');
    redirect_path(APP_BASE_URL . '/login.php');
}

$pending_user_id = isset($_SESSION['two_step_pending_user_id']) ? (int)$_SESSION['two_step_pending_user_id'] : 0;
if ($pending_user_id <= 0) {
    flash_set('error', 'No pending two-step login found. Please login again.');
    redirect_path(APP_BASE_URL . '/login.php');
}

$user = user_find_by_id($pending_user_id);
if (!$user) {
    unset($_SESSION['two_step_pending_user_id'], $_SESSION['two_step_pending_email'], $_SESSION['two_step_pending_started']);
    flash_set('error', 'Account not found. Please login again.');
    redirect_path(APP_BASE_URL . '/login.php');
}

// Safety checks
if ((int)$user['is_verified'] !== 1) {
    unset($_SESSION['two_step_pending_user_id'], $_SESSION['two_step_pending_email'], $_SESSION['two_step_pending_started']);
    flash_set('error', 'Please verify your email first.');
    redirect_path(APP_BASE_URL . '/verify.php?email=' . urlencode((string)$user['email']));
}
[$banned, $bmsg] = user_is_banned($user);
if ($banned) {
    unset($_SESSION['two_step_pending_user_id'], $_SESSION['two_step_pending_email'], $_SESSION['two_step_pending_started']);
    flash_set('error', $bmsg);
    redirect_path(APP_BASE_URL . '/login.php');
}

$email = (string)$user['email'];
$otp = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_verify($token)) {
        $errors[] = 'Invalid request (CSRF). Please try again.';
    } else {
        $action = (string)($_POST['action'] ?? 'verify');

        if ($action === 'resend') {
            $otpInfo = otp_request_create($email, 'login2fa', (int)$user['id']);
            send_otp_email($email, (string)$otpInfo['otp'], 'login2fa');
            flash_set('success', 'Login OTP resent! Check your email or storage/opt.log (local testing).');
            redirect_path(APP_BASE_URL . '/two_step.php');
        }

        $otp = trim((string)($_POST['otp'] ?? ''));
        if (!preg_match('/^\\d{' . OTP_LENGTH . '}$/', $otp)) {
            $errors[] = 'Please enter a valid ' . OTP_LENGTH . '-digit OTP.';
        } else {
            $row = otp_request_get($email, 'login2fa');
            if (!$row) {
                $errors[] = 'OTP not found. Please resend OTP.';
            } elseif (otp_request_expired($row)) {
                otp_request_delete((int)$row['id']);
                $errors[] = 'OTP expired. Please resend OTP.';
            } elseif ((int)$row['attempts'] >= OTP_MAX_ATTEMPTS) {
                $errors[] = 'Too many wrong attempts. Please resend OTP.';
            } else {
                if (!otp_verify($otp, (string)$row['otp_salt'], (string)$row['otp_hash'])) {
                    otp_request_increment_attempts((int)$row['id']);
                    $errors[] = 'Wrong OTP. Please try again.';
                } else {
                    otp_request_delete((int)$row['id']);
                    unset($_SESSION['two_step_pending_user_id'], $_SESSION['two_step_pending_email'], $_SESSION['two_step_pending_started']);

                    login_user((int)$user['id'], (string)$user['role'], (string)$user['email']);

                    switch ((string)$user['role']) {
                        case 'volunteer':
                            redirect_path(APP_BASE_URL . '/dashboard_volunteer.php');
                        case 'vet':
                            redirect_path(APP_BASE_URL . '/dashboard_vet.php');
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
  <title><?= h(APP_NAME) ?> - Two-Step Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
  <div class="w-full max-w-md bg-white rounded-2xl shadow p-6">
    <h1 class="text-2xl font-bold mb-2">Two-Step Verification</h1>
    <p class="text-sm text-gray-600 mb-4">We sent a <?= OTP_LENGTH ?>-digit login OTP to <span class="font-semibold"><?= h($email) ?></span></p>

    <?php if ($flash): ?>
      <div class="mb-4 p-3 rounded <?= $flash['type']==='error' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' ?>">
        <?= h((string)$flash['message']) ?>
      </div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="mb-4 p-3 rounded bg-red-50 text-red-700">
        <ul class="list-disc pl-5 space-y-1">
          <?php foreach ($errors as $e): ?><li><?= h((string)$e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="verify">

      <div>
        <label class="block text-sm font-medium">OTP</label>
        <input name="otp" value="<?= h($otp) ?>" inputmode="numeric" maxlength="<?= OTP_LENGTH ?>" class="mt-1 w-full rounded-lg border p-2 tracking-widest text-center text-xl" placeholder="<?= str_repeat('*', OTP_LENGTH) ?>" required>
      </div>

      <button class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg py-2">Verify & Login</button>
    </form>

    <form method="post" class="mt-4">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="resend">
      <button class="w-full border border-gray-300 hover:bg-gray-50 rounded-lg py-2 text-sm">Resend OTP</button>
    </form>

    <div class="mt-4 text-sm flex items-center justify-between">
      <a class="text-green-700 hover:underline" href="<?= h(APP_BASE_URL) ?>/login.php">Back to login</a>
      <a class="text-red-600 hover:underline" href="<?= h(APP_BASE_URL) ?>/two_step.php?cancel=1">Cancel</a>
    </div>

    <p class="mt-4 text-xs text-gray-500">Local testing tip: if email cannot be sent, the login OTP is logged in <span class="font-mono">storage/opt.log</span>.</p>
  </div>
</body>
</html>
