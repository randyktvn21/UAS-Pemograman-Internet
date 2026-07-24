<?php

declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function app_base_path(): string
{
    static $base;
    if (is_string($base)) {
        return $base;
    }

    $configured = getenv('APP_BASE_PATH');
    if (is_string($configured) && $configured !== '') {
        return $base = '/' . trim($configured, '/');
    }

    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    foreach (['/Admin/', '/actions/'] as $marker) {
        $position = strpos($script, $marker);
        if ($position !== false) {
            $candidate = substr($script, 0, $position);
            return $base = rtrim($candidate, '/');
        }
    }

    $directory = str_replace('\\', '/', dirname($script));
    if ($directory === '/' || $directory === '.' || $directory === '\\') {
        $directory = '';
    }
    return $base = rtrim($directory, '/');
}

function url(string $path = ''): string
{
    $base = app_base_path();
    $path = ltrim($path, '/');
    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }
    return ($base === '' ? '' : $base) . '/' . $path;
}

function public_profile_url(string $username): string
{
    return url(rawurlencode($username));
}

function redirect(string $path): never
{
    if (!preg_match('~^https?://~i', $path)) {
        $path = url($path);
    }
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool
{
    return $token !== '' && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return is_string($value) ? $value : null;
}

function clean_line(mixed $value, int $max = 255): string
{
    $text = trim(preg_replace('/\s+/', ' ', (string) $value) ?? '');
    return function_exists('mb_substr') ? mb_substr($text, 0, $max) : substr($text, 0, $max);
}

function clean_paragraph(mixed $value, int $max = 5000): string
{
    $text = trim(str_replace(["\r\n", "\r"], "\n", (string) $value));
    return function_exists('mb_substr') ? mb_substr($text, 0, $max) : substr($text, 0, $max);
}

function normalize_username(mixed $value): string
{
    $value = strtolower(clean_line($value, 80));
    $value = preg_replace('/[^a-z0-9_-]+/', '_', $value) ?? '';
    return trim($value, '_-');
}

function rows_from_post(array $input, string $prefix, array $fields): array
{
    $firstField = $fields[0];
    $count = count($input[$prefix][$firstField] ?? []);
    $rows = [];
    for ($index = 0; $index < $count; $index++) {
        $row = [];
        $hasContent = false;
        foreach ($fields as $field) {
            $value = $input[$prefix][$field][$index] ?? '';
            $row[$field] = $field === 'description' ? clean_paragraph($value) : clean_line($value);
            $hasContent = $hasContent || $row[$field] !== '';
        }
        if ($hasContent) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function skill_rows_from_post(array $input): array
{
    $names = $input['skills']['skill_name'] ?? [];
    $levels = $input['skills']['skill_level'] ?? [];
    $rows = [];
    foreach ($names as $index => $name) {
        $cleanName = clean_line($name, 120);
        if ($cleanName === '') {
            continue;
        }
        $rows[] = [
            'skill_name' => $cleanName,
            'skill_level' => max(0, min(100, (int) ($levels[$index] ?? 50))),
        ];
    }
    return $rows;
}

function upload_photo(array $file, string $currentPath, int $userId): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $currentPath;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Foto gagal diunggah.');
    }
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('Ukuran foto maksimal 2 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string) $file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Format foto harus JPG, PNG, atau WEBP.');
    }

    $directory = dirname(__DIR__) . '/assets/img/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder unggahan tidak dapat dibuat.');
    }

    $filename = sprintf('user_%d_%s_%s.%s', $userId, date('Ymd_His'), bin2hex(random_bytes(3)), $extensions[$mime]);
    if (!move_uploaded_file((string) $file['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('Foto tidak dapat disimpan.');
    }
    return 'assets/img/uploads/' . $filename;
}

function format_datetime(?string $value): string
{
    if (!$value) {
        return '-';
    }
    try {
        return (new DateTimeImmutable($value))->format('d M Y, H:i');
    } catch (Throwable) {
        return $value;
    }
}

function is_active_path(string $needle): string
{
    return str_contains((string) ($_SERVER['REQUEST_URI'] ?? ''), $needle) ? 'active' : '';
}
