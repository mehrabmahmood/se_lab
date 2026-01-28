<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';

session_start_safe();

$email = trim((string)($_GET['email'] ?? ($_POST['email'] ?? '')));
$otp = '';
$errors = [];

if ($email === '') {
    flash_set('error', 'Email missing. Please register again.');
    redirect_path(APP_BASE_URL . '/register.php');
}

$user = user_find_by_email($email);
if (!$user) {
    flash_set('error', 'No account found. Please register.');
    redirect_path(APP_BASE_URL . '/register.php');
}

if ((int)$user['is_verified'] === 1) {
    flash_set('success', 'Your email is already verified. Please login.');
    redirect_path(APP_BASE_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_verify($token)) {
        $errors[] = 'Invalid request (CSRF). Please try again.';
    } else {
        $action = (string)($_POST['action'] ?? 'verify');

        if ($action === 'resend') {
            $otpInfo = otp_request_create($email, 'register', (int)$user['id']);
            send_otp_email($email, (string)$otpInfo['otp'], 'register');
            flash_set('success', 'OTP resent! Check your email or storage/otp.log (local testing).');
            redirect_path(APP_BASE_URL . '/verify.php?email=' . urlencode($email));
        }

        $otp = trim((string)($_POST['otp'] ?? ''));
        if (!preg_match('/^\d{' . OTP_LENGTH . '}$/', $otp)) {
            $errors[] = 'Please enter a valid ' . OTP_LENGTH . '-digit OTP.';
        } else {
            $row = otp_request_get($email, 'register');
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
                    // success
                    otp_request_delete((int)$row['id']);
                    user_mark_verified((int)$user['id']);

                    // auto login and redirect
                    $user2 = user_find_by_email($email);
                    if ($user2) {
                        login_user((int)$user2['id'], (string)$user2['role'], (string)$user2['email']);
                        switch ((string)$user2['role']) {
                            case 'volunteer':
                                redirect_path(APP_BASE_URL . '/member_dashboard.php');
                            case 'vet':
                                redirect_path(APP_BASE_URL . '/dashboard_vet.php');
                            case 'farmer':
                            default:
                                redirect_path(APP_BASE_URL . '/home.php');
                        }
                    }

                    flash_set('success', 'Verified! Please login.');
                    redirect_path(APP_BASE_URL . '/login.php');
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
  <title><?= h(APP_NAME) ?> - Verify OTP</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
  <div class="w-full max-w-md bg-white rounded-2xl shadow p-6">
    <h1 class="text-2xl font-bold mb-2">Verify Email</h1>
    <p class="text-sm text-gray-600 mb-4">We sent a <?= OTP_LENGTH ?>-digit OTP to <span class="font-semibold"><?= h($email) ?></span></p>

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
      <input type="hidden" name="email" value="<?= h($email) ?>">
      <input type="hidden" name="action" value="verify">

      <div>
        <label class="block text-sm font-medium">OTP</label>
        <input name="otp" value="<?= h($otp) ?>" inputmode="numeric" maxlength="<?= OTP_LENGTH ?>" class="mt-1 w-full rounded-lg border p-2 tracking-widest text-center text-xl" placeholder="<?= str_repeat('•', OTP_LENGTH) ?>" required>
      </div>

      <button class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg py-2">Verify</button>
    </form>

    <form method="post" class="mt-4">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="email" value="<?= h($email) ?>">
      <input type="hidden" name="action" value="resend">
      <button class="w-full border border-gray-300 hover:bg-gray-50 rounded-lg py-2 text-sm">Resend OTP</button>
    </form>

    <div class="mt-4 text-sm">
      <a class="text-green-700 hover:underline" href="<?= h(APP_BASE_URL) ?>/login.php">Back to login</a>
    </div>

    <p class="mt-4 text-xs text-gray-500">Local testing tip: if SMTP is OFF, OTP is logged in <span class="font-mono">storage/otp.log</span>.</p>
  </div>
</body>
</html>
