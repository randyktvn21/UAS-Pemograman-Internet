<?php

declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}
if (!verify_csrf((string) ($_POST['csrf_token'] ?? ''))) {
    flash('error', 'Sesi formulir tidak valid. Silakan coba lagi.');
    redirect('login.php');
}
$adminOnly = (string) ($_POST['admin_only'] ?? '0') === '1';
$result = attempt_login((string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''), $adminOnly);
if (!$result['success']) {
    flash('error', $result['message']);
    redirect($adminOnly ? 'login.php?admin=1' : 'login.php');
}
$user = $result['user'];
$next = (string) ($_POST['next'] ?? '');
if ($next !== '' && str_starts_with($next, '/') && !str_starts_with($next, '//')) {
    header('Location: ' . $next);
    exit;
}
redirect($user['role'] === 'admin' ? 'Admin/' : 'dashboard.php');
