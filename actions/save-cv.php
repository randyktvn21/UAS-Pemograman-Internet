<?php

declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
$actor = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($actor['role'] === 'admin' ? 'Admin/' : 'dashboard.php');
}
$targetUserId = (int) ($_POST['target_user_id'] ?? 0);
$returnPath = $actor['role'] === 'admin' ? 'dashboard.php?user=' . $targetUserId : 'dashboard.php';
if (!verify_csrf((string) ($_POST['csrf_token'] ?? ''))) {
    $_SESSION['form_errors'] = ['Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.'];
    redirect($returnPath);
}
if (!$targetUserId || !can_edit_user($actor, $targetUserId)) {
    http_response_code(403);
    require dirname(__DIR__) . '/views/403.php';
    exit;
}
try {
    $result = save_cv($targetUserId, $_POST, $_FILES);
} catch (Throwable $exception) {
    $result = ['success' => false, 'errors' => ['Basis data tidak dapat digunakan: ' . $exception->getMessage()]];
}
if (!$result['success']) {
    $_SESSION['form_errors'] = $result['errors'];
    redirect($returnPath);
}
$profile = load_cv_by_user_id($targetUserId, true);
flash('success', 'Data CV @' . ($profile['username'] ?? 'user') . ' berhasil diperbarui.');
redirect($returnPath);
