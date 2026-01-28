<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

$user = require_user_access();
if (is_admin()) redirect_path(APP_BASE_URL . '/admin/dashboard.php');
if ((string)$user['role'] !== 'vet') redirect_path(APP_BASE_URL . '/index.php');

session_start_safe();

// Regenerate token on demand
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? '');
    $issued = jit_issue_token((int)$user['id'], 'vet');
    $_SESSION['jit_token'] = $issued['token'];
    $_SESSION['jit_payload'] = $issued['payload'];
    flash_set('success', 'New JIT token generated.');
    redirect_path(APP_BASE_URL . '/previous_patients.php');
}

$token = (string)($_SESSION['jit_token'] ?? '');
$payload = is_array($_SESSION['jit_payload'] ?? null) ? (array)$_SESSION['jit_payload'] : null;

// Create a token if missing/expired/invalid
$valid_payload = $token ? jit_verify_token($token) : null;
if (!$valid_payload || (int)($valid_payload['sub'] ?? 0) !== (int)$user['id'] || (string)($valid_payload['role'] ?? '') !== 'vet') {
    $issued = jit_issue_token((int)$user['id'], 'vet');
    $token = $issued['token'];
    $payload = $issued['payload'];
    $_SESSION['jit_token'] = $token;
    $_SESSION['jit_payload'] = $payload;
} else {
    $payload = $valid_payload;
}

$seconds_left = jit_seconds_left($token);
$exp = (int)($payload['exp'] ?? time());
$iat = (int)($payload['iat'] ?? time());
$flash = flash_get();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h(APP_NAME) ?> - Previous Patients (JIT Token Demo)</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 p-6">
<?php if ($flash): ?>
  <div class="fixed top-4 right-4 z-50 max-w-sm p-3 rounded-lg shadow-lg <?= $flash['type']==='error' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' ?>">
    <?= h((string)$flash['message']) ?>
  </div>
<?php endif; ?>

<div class="max-w-5xl mx-auto">
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-bold">Previous Patients</h1>
      <p class="text-sm text-gray-600">JIT token (JWT HS256) demo with a separate patient database.</p>
    </div>
    <div class="flex gap-3">
      <a class="text-sm text-gray-700 hover:underline" href="<?= h(APP_BASE_URL) ?>/dashboard_vet.php">Back</a>
      <a class="text-sm text-red-600 hover:underline" href="<?= h(APP_BASE_URL) ?>/logout.php">Logout</a>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <div class="text-sm text-gray-600">Logged in as</div>
        <div class="font-semibold text-gray-900"><?= h((string)$user['full_name']) ?> <span class="text-gray-500">(Vet)</span></div>
      </div>

      <form method="post" class="flex items-center gap-2">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <button class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50" type="submit">
          Regenerate Token
        </button>
      </form>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="p-4 rounded-xl bg-gray-50">
        <div class="text-xs text-gray-500">Token TTL</div>
        <div class="text-lg font-bold"><?= (int)JIT_TOKEN_TTL_SECONDS ?>s</div>
      </div>
      <div class="p-4 rounded-xl bg-gray-50">
        <div class="text-xs text-gray-500">Seconds left</div>
        <div class="text-lg font-bold" id="secLeft"><?= (int)$seconds_left ?></div>
      </div>
      <div class="p-4 rounded-xl bg-gray-50">
        <div class="text-xs text-gray-500">Status</div>
        <div class="text-lg font-bold" id="tokenStatus"><?= $seconds_left > 0 ? 'ACTIVE' : 'EXPIRED' ?></div>
      </div>
    </div>

    <div class="mt-6">
      <div class="text-sm font-semibold text-gray-800 mb-2">JIT Token (JWT)</div>
      <textarea class="w-full h-28 p-3 font-mono text-xs rounded-lg border border-gray-200 bg-white" readonly><?= h($token) ?></textarea>
      <div class="mt-2 text-xs text-gray-600">
        Issued at (iat): <span class="font-mono" id="iat"><?= h(gmdate('Y-m-d H:i:s', $iat)) ?> UTC</span> ·
        Expires at (exp): <span class="font-mono" id="exp"><?= h(gmdate('Y-m-d H:i:s', $exp)) ?> UTC</span>
      </div>
      <div class="mt-2 text-xs text-gray-600">
        API uses this token. When it expires, API returns <span class="font-mono">401</span> until you regenerate.
      </div>
    </div>

    <div class="mt-8">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Patient list (loaded via API using token)</h2>
        <button id="refreshBtn" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm">Refresh list</button>
      </div>
      <div class="mt-3 text-sm" id="apiMeta"></div>

      <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left p-3 border-b">ID</th>
              <th class="text-left p-3 border-b">Patient</th>
              <th class="text-left p-3 border-b">Animal</th>
              <th class="text-left p-3 border-b">Complaint</th>
              <th class="text-left p-3 border-b">Diagnosis</th>
              <th class="text-left p-3 border-b">Treatment</th>
              <th class="text-left p-3 border-b">Farmer</th>
              <th class="text-left p-3 border-b">Visited (UTC)</th>
            </tr>
          </thead>
          <tbody id="patientsBody">
            <tr><td class="p-3" colspan="8">Loading...</td></tr>
          </tbody>
        </table>
      </div>

      <div class="mt-6 p-4 rounded-xl bg-yellow-50 text-yellow-900 text-sm">
        <b>Teaching point:</b> This is a short-lived "Just-In-Time" token. It expires fast (<?= (int)JIT_TOKEN_TTL_SECONDS ?>s). Without a valid token, the API refuses to return patient data.
      </div>
    </div>
  </div>
</div>

<script>
  const exp = <?= (int)$exp ?>;
  let currentToken = <?= json_encode($token, JSON_UNESCAPED_SLASHES) ?>;

  function fmtSec(s) {
    s = Math.max(0, s|0);
    return s.toString();
  }

  function updateCountdown() {
    const now = Math.floor(Date.now()/1000);
    const left = exp - now;
    document.getElementById('secLeft').textContent = fmtSec(left);
    document.getElementById('tokenStatus').textContent = left > 0 ? 'ACTIVE' : 'EXPIRED';
  }

  async function loadPatients() {
    const meta = document.getElementById('apiMeta');
    const tbody = document.getElementById('patientsBody');
    meta.textContent = 'Calling API...';

    try {
      const t0 = performance.now();
      // Some Apache/PHP setups (incl. XAMPP) may not expose the Authorization header to PHP
      // unless extra server config is present. To make the demo reliable, we send the token
      // both ways: Authorization header (secure) + query parameter fallback.
      const url = 'previous_patients_api.php?token=' + encodeURIComponent(currentToken);
      const res = await fetch(url, {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'Authorization': 'Bearer ' + currentToken }
      });
      const t1 = performance.now();

      if (!res.ok) {
        const txt = await res.text();
        meta.innerHTML = `<span class="text-red-700 font-semibold">API failed (${res.status})</span> - ${txt}`;
        tbody.innerHTML = `<tr><td class="p-3" colspan="8">Token invalid/expired. Click "Regenerate Token".</td></tr>`;
        return;
      }

      const data = await res.json();
      const ms = (t1 - t0).toFixed(2);
      meta.innerHTML = `<span class="text-green-700 font-semibold">API OK</span> · Server time: <span class="font-mono">${data.server_time_utc}</span> · Fetch: <span class="font-mono">${ms} ms</span>`;

      const rows = (data.patients || []).map(p => `
        <tr class="border-b">
          <td class="p-3">${p.id}</td>
          <td class="p-3">${escapeHtml(p.patient_name)}</td>
          <td class="p-3">${escapeHtml(p.animal_type)}</td>
          <td class="p-3">${escapeHtml(p.complaint)}</td>
          <td class="p-3">${escapeHtml(p.diagnosis)}</td>
          <td class="p-3">${escapeHtml(p.treatment)}</td>
          <td class="p-3">${escapeHtml(p.farmer_name)}</td>
          <td class="p-3">${escapeHtml(p.visited_at)}</td>
        </tr>
      `).join('');

      tbody.innerHTML = rows || `<tr><td class="p-3" colspan="8">No patient records found.</td></tr>`;
    } catch (e) {
      meta.innerHTML = `<span class="text-red-700 font-semibold">API error</span> - ${escapeHtml(String(e))}`;
      tbody.innerHTML = `<tr><td class="p-3" colspan="8">Error.</td></tr>`;
    }
  }

  function escapeHtml(s) {
    return String(s)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  document.getElementById('refreshBtn').addEventListener('click', loadPatients);
  updateCountdown();
  setInterval(updateCountdown, 1000);
  loadPatients();
</script>
</body>
</html>
