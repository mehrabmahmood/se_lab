<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';

session_start_safe();

$email = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_verify($token)) {
        $errors[] = 'Invalid request (CSRF). Please try again.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        if (!validate_email_address($email)) {
            $errors[] = 'Please enter a valid email.';
        } else {
            $user = user_find_by_email($email);
            if (!$user || (int)$user['is_verified'] !== 1) {
                // do not reveal existence too clearly
                flash_set('success', 'If this email exists, an OTP was sent.');
                redirect_path(APP_BASE_URL . '/reset_password.php?email=' . urlencode($email));
            }

            // ban check
            [$banned, $bmsg] = user_is_banned($user);
            if ($banned) {
                $errors[] = $bmsg;
            } else {
                $otpInfo = otp_request_create($email, 'reset', (int)$user['id']);
                send_otp_email($email, (string)$otpInfo['otp'], 'reset');
                flash_set('success', 'OTP sent. Please check your email.');
                redirect_path(APP_BASE_URL . '/reset_password.php?email=' . urlencode($email));
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
  <title><?= h(APP_NAME) ?> - Forgot Password</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
  <div class="w-full max-w-md bg-white rounded-2xl shadow p-6">
    <h1 class="text-2xl font-bold mb-2">Forgot password</h1>
    <p class="text-sm text-gray-600 mb-6">We will send an OTP to reset your password.</p>

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

      <div>
        <label class="block text-sm font-medium">Email</label>
        <input name="email" type="email" value="<?= h($email) ?>" required class="mt-1 w-full rounded-lg border p-2">
      </div>

      <button class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg py-2">Send OTP</button>
    </form>

    <div class="mt-4 text-sm">
      <a class="text-green-700 hover:underline" href="<?= h(APP_BASE_URL) ?>/login.php">Back to login</a>
    </div>

    <p class="mt-4 text-xs text-gray-500">Local testing tip: if SMTP is OFF, OTP is logged in <span class="font-mono">storage/otp.log</span>.</p>
  </div>
</body>
</html>
