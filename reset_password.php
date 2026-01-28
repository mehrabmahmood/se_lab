<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

session_start_safe();

$email = trim((string)($_GET['email'] ?? ($_POST['email'] ?? '')));
$otp = '';
$newpass = '';
$conf = '';
$errors = [];

if ($email === '') {
    flash_set('error', 'Email missing.');
    redirect_path(APP_BASE_URL . '/forgot_password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_verify($token)) {
        $errors[] = 'Invalid request (CSRF). Please try again.';
    } else {
        $otp = trim((string)($_POST['otp'] ?? ''));
        $newpass = (string)($_POST['new_password'] ?? '');
        $conf = (string)($_POST['confirm_password'] ?? '');

        if (!preg_match('/^\d{' . OTP_LENGTH . '}$/', $otp)) {
            $errors[] = 'Please enter a valid ' . OTP_LENGTH . '-digit OTP.';
        }

        if ($newpass !== $conf) {
            $errors[] = 'Passwords do not match.';
        }

        $errors = array_merge($errors, validate_password_rules($newpass));

        if (!$errors) {
            $row = otp_request_get($email, 'reset');
            if (!$row) {
                $errors[] = 'OTP not found. Please request a new OTP.';
            } elseif (otp_request_expired($row)) {
                otp_request_delete((int)$row['id']);
                $errors[] = 'OTP expired. Please request again.';
            } elseif ((int)$row['attempts'] >= OTP_MAX_ATTEMPTS) {
                $errors[] = 'Too many wrong attempts. Please request a new OTP.';
            } elseif (!otp_verify($otp, (string)$row['otp_salt'], (string)$row['otp_hash'])) {
                otp_request_increment_attempts((int)$row['id']);
                $errors[] = 'Wrong OTP.';
            } else {
                $uid = otp_request_user_id($row);
                if (!$uid) {
                    $errors[] = 'Reset request invalid. Please request again.';
                } else {
                    otp_request_delete((int)$row['id']);
                    $salt = password_make_salt();
                    $hash = password_hash_manual($newpass, $salt);
                    user_update_password($uid, $hash, $salt);
                    flash_set('success', 'Password updated. Please login.');
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
  <title><?= h(APP_NAME) ?> - Reset Password</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
  <div class="w-full max-w-md bg-white rounded-2xl shadow p-6">
    <h1 class="text-2xl font-bold mb-2">Reset password</h1>
    <p class="text-sm text-gray-600 mb-6">Enter the OTP and set a new password for <span class="font-semibold"><?= h($email) ?></span></p>

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

      <div>
        <label class="block text-sm font-medium">OTP</label>
        <input name="otp" value="<?= h($otp) ?>" inputmode="numeric" maxlength="<?= OTP_LENGTH ?>" class="mt-1 w-full rounded-lg border p-2 tracking-widest text-center text-xl" required>
      </div>

      <div>
        <label class="block text-sm font-medium">New password</label>
        <input name="new_password" type="password" required class="mt-1 w-full rounded-lg border p-2">
        <p class="text-xs text-gray-500 mt-1">Min 6 chars, include 1 uppercase, 1 lowercase, 1 number, 1 special character.</p>
      </div>

      <div>
        <label class="block text-sm font-medium">Confirm password</label>
        <input name="confirm_password" type="password" required class="mt-1 w-full rounded-lg border p-2">
      </div>

      <button class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg py-2">Update password</button>
    </form>

    <div class="mt-4 text-sm">
      <a class="text-green-700 hover:underline" href="<?= h(APP_BASE_URL) ?>/forgot_password.php">Request new OTP</a>
    </div>
  </div>
</body>
</html>
