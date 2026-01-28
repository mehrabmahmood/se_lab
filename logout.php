<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';

logout_user();
header('Location: ' . APP_BASE_URL . '/login.php');
exit;
