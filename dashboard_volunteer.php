<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
$user = require_user_access();
if (is_admin()) redirect_path(APP_BASE_URL . '/admin/dashboard.php');
if ((string)$user['role'] !== 'volunteer') redirect_path(APP_BASE_URL . '/member_dashboard.php');
$two_step_enabled = user_two_step_enabled($user);
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h(APP_NAME) ?> - Volunteer Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 p-6">
<?php if ($flash): ?>
  <div class="fixed top-4 right-4 z-50 max-w-sm p-3 rounded-lg shadow-lg <?= $flash['type']==='error' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' ?>">
    <?= h((string)$flash['message']) ?>
  </div>
<?php endif; ?>

  <div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">Volunteer Dashboard</h1>
      <a class="text-sm text-red-600 hover:underline" href="<?= h(APP_BASE_URL) ?>/logout.php">Logout</a>
    </div>

    <div class="bg-white rounded-2xl shadow p-6">
      <p class="text-gray-700">Hi <b><?= h((string)$user['full_name']) ?></b> 👋</p>
      <p class="text-gray-600 mt-2">Role: <span class="font-semibold">Volunteer</span></p>

      
      <div class="mt-4 p-4 rounded-lg bg-gray-50 text-sm flex items-center justify-between">
        <div>
          <div class="font-semibold text-gray-800">Two-step verification</div>
          <div class="text-gray-600">Status: <span class="font-mono"><?= $two_step_enabled ? 'ON' : 'OFF' ?></span></div>
        </div>
        <form method="post" action="toggle_two_step.php">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="two_step_enabled" value="<?= $two_step_enabled ? '0' : '1' ?>">
          <button class="px-4 py-2 rounded-lg border <?= $two_step_enabled ? 'border-green-600 text-green-700 hover:bg-green-50' : 'border-gray-300 text-gray-700 hover:bg-white' ?>">
            <?= $two_step_enabled ? 'Disable' : 'Enable' ?>
          </button>
        </form>
      </div>

      <div class="mt-4 p-4 rounded-lg bg-yellow-50 text-yellow-800 text-sm">
        <b>Note:</b> Community Blog & Private Messages are available for <b>Farmers only</b>.
      </div>

      <p class="mt-6 text-sm text-gray-500">(You can customize this dashboard later.)</p>
   
    </div>
  </div>
</body>
</html>
