<?php

declare(strict_types=1);

function current_user(): ?array
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId < 1) {
        return null;
    }
    $user = find_user_by_id($userId);
    if (!$user || (int) $user['is_active'] !== 1) {
        unset($_SESSION['user_id']);
        return null;
    }
    return $user;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['last_activity'] = time();
    touch_last_login((int) $user['id']);
}

function logout_user(): void
{
    unset($_SESSION['user_id'], $_SESSION['last_activity'], $_SESSION['csrf_token']);
    session_regenerate_id(true);
}

function attempt_login(string $username, string $password, bool $adminOnly = false): array
{
    $user = find_user_by_username(normalize_username($username));
    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        return ['success' => false, 'message' => 'Username atau password tidak sesuai.'];
    }
    if ((int) $user['is_active'] !== 1) {
        return ['success' => false, 'message' => 'Akun sedang dinonaktifkan oleh administrator.'];
    }
    if ($adminOnly && $user['role'] !== 'admin') {
        return ['success' => false, 'message' => 'Akun ini tidak memiliki hak akses administrator.'];
    }
    login_user($user);
    return ['success' => true, 'message' => 'Login berhasil.', 'user' => $user];
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('error', 'Silakan login terlebih dahulu.');
        $next = rawurlencode((string) ($_SERVER['REQUEST_URI'] ?? url('dashboard.php')));
        redirect('login.php?next=' . $next);
    }
    return $user;
}

function require_admin(): array
{
    $user = current_user();
    if (!$user) {
        flash('error', 'Silakan login sebagai administrator.');
        redirect('login.php?admin=1');
    }
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        require dirname(__DIR__) . '/views/403.php';
        exit;
    }
    return $user;
}

function can_edit_user(array $actor, int $targetUserId): bool
{
    return $actor['role'] === 'admin' || (int) $actor['id'] === $targetUserId;
}
