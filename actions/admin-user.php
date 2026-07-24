<?php

declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
$admin = require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('Admin/');
}
if (!verify_csrf((string) ($_POST['csrf_token'] ?? ''))) {
    flash('error', 'Sesi formulir tidak valid. Silakan ulangi tindakan.');
    redirect('Admin/');
}
$action = (string) ($_POST['action'] ?? '');
$userId = (int) ($_POST['user_id'] ?? 0);
try {
    switch ($action) {
        case 'create':
            $result = create_user($_POST);
            if (!$result['success']) {
                $_SESSION['admin_errors'] = $result['errors'];
            } else {
                $user = find_user_by_id((int) $result['user_id']);
                flash('success', 'Akun @' . ($user['username'] ?? 'user') . ' berhasil dibuat.');
            }
            break;
        case 'update':
            $result = update_user_account($userId, $_POST);
            if (!$result['success']) {
                $_SESSION['admin_errors'] = $result['errors'];
            } else {
                flash('success', 'Informasi akun berhasil diperbarui.');
            }
            break;
        case 'set_default':
            $result = set_default_user($userId);
            flash($result['success'] ? 'success' : 'error', $result['message']);
            break;
        case 'toggle':
            $result = set_user_active($userId, (string) ($_POST['active'] ?? '0') === '1');
            flash($result['success'] ? 'success' : 'error', $result['message']);
            break;
        case 'reset_password':
            $result = reset_user_password($userId, (string) ($_POST['new_password'] ?? ''));
            flash($result['success'] ? 'success' : 'error', $result['message']);
            break;
        case 'delete':
            $result = delete_user_account($userId);
            flash($result['success'] ? 'success' : 'error', $result['message']);
            break;
        default:
            flash('error', 'Tindakan administrator tidak dikenali.');
    }
} catch (Throwable $exception) {
    flash('error', 'Tindakan gagal diproses: ' . $exception->getMessage());
}
redirect('Admin/');
