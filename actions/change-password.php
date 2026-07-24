<?php

declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
$user = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf((string) ($_POST['csrf_token'] ?? ''))) {
    flash('error', 'Permintaan perubahan password tidak valid.');
    redirect($user['role'] === 'admin' ? 'Admin/' : 'dashboard.php#keamanan');
}
$newPassword = (string) ($_POST['new_password'] ?? '');
if ($newPassword !== (string) ($_POST['confirm_password'] ?? '')) {
    flash('error', 'Konfirmasi password baru tidak sama.');
    redirect($user['role'] === 'admin' ? 'Admin/' : 'dashboard.php#keamanan');
}
$result = change_own_password((int) $user['id'], (string) ($_POST['current_password'] ?? ''), $newPassword);
flash($result['success'] ? 'success' : 'error', $result['message']);
redirect($user['role'] === 'admin' ? 'Admin/' : 'dashboard.php#keamanan');
