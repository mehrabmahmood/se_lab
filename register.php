<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';

session_start_safe();

if (is_admin() || current_user_id()) {
    redirect_path(APP_BASE_URL . '/index.php');
}

$name = '';
$email = '';
$role = 'farmer';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_verify($token)) {
        $errors[] = 'Invalid request (CSRF). Please try again.';
    } else {
        $name = trim((string)($_POST['full_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $role = (string)($_POST['role'] ?? 'farmer');
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if ($name === '' || strlen($name) < 2) {
            $errors[] = 'Please enter your full name.';
        }

        if (!in_array($role, ['farmer','volunteer','vet'], true)) {
            $errors[] = 'Please select a valid role.';
        }

        if (!validate_email_address($email)) {
            $errors[] = ONLY_GMAIL ? 'Please use a valid gmail.com email.' : 'Please use a valid email with a real domain.';
        }

        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        $pwErrors = validate_password_rules($password);
        $errors = array_merge($errors, $pwErrors);

        if (!$errors) {
            $existing = user_find_by_email($email);
            $salt = password_make_salt();
            $hash = password_hash_manual($password, $salt);

            if ($existing) {
                if ((int)$existing['is_verified'] === 1) {
                    $errors[] = 'This email is already registered. Please login.';
                } else {
                    // update pending account
                    user_update_pending($name, $email, $role, $hash, $salt);
                    $user_id = (int)$existing['id'];

                    $otpInfo = otp_request_create($email, 'register', $user_id);
                    $otp = (string)$otpInfo['otp'];
                    send_otp_email($email, $otp, 'register');

                    flash_set('success', 'OTP sent! Please verify your email. (Check spam folder too.)');
                    redirect_path(APP_BASE_URL . '/verify.php?email=' . urlencode($email));
                }
            } else {
                $user_id = user_create($name, $email, $role, $hash, $salt);

                $otpInfo = otp_request_create($email, 'register', $user_id);
                $otp = (string)$otpInfo['otp'];
                send_otp_email($email, $otp, 'register');

                flash_set('success', 'Registration successful! OTP sent to your email.');
                redirect_path(APP_BASE_URL . '/verify.php?email=' . urlencode($email));
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
  <title><?= h(APP_NAME) ?> - Register</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
  <div class="w-full max-w-lg bg-white rounded-2xl shadow p-6">
    <h1 class="text-2xl font-bold mb-2">Create account</h1>
    <p class="text-sm text-gray-600 mb-6">Choose your role: Farmer / Volunteer / Vet</p>

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
        <label class="block text-sm font-medium">Full name</label>
        <input name="full_name" value="<?= h($name) ?>" required class="mt-1 w-full rounded-lg border p-2">
      </div>

      <div>
        <label class="block text-sm font-medium">Email</label>
        <input name="email" type="email" value="<?= h($email) ?>" required class="mt-1 w-full rounded-lg border p-2">
        <p class="text-xs text-gray-500 mt-1">
          <?= ONLY_GMAIL ? 'Only gmail.com is allowed.' : 'Email domain must be real (fake extensions will fail).' ?>
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium">Role</label>
        <select name="role" class="mt-1 w-full rounded-lg border p-2">
          <option value="farmer" <?= $role==='farmer'?'selected':'' ?>>Farmer</option>
          <option value="volunteer" <?= $role==='volunteer'?'selected':'' ?>>Volunteer</option>
          <option value="vet" <?= $role==='vet'?'selected':'' ?>>Vet</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium">Password</label>
        <input name="password" type="password" required class="mt-1 w-full rounded-lg border p-2">
        <p class="text-xs text-gray-500 mt-1">Min 6 chars, include 1 uppercase, 1 lowercase, 1 number, 1 special character.</p>
      </div>

      <div>
        <label class="block text-sm font-medium">Confirm password</label>
        <input name="confirm_password" type="password" required class="mt-1 w-full rounded-lg border p-2">
      </div>

      <button class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg py-2">Register &amp; Send OTP</button>
    </form>

    <div class="mt-4 text-sm">
      Already have an account? <a class="text-green-700 hover:underline" href="<?= h(APP_BASE_URL) ?>/login.php">Login</a>
    </div>
  </div>
</body>
</html>
