<?php

declare(strict_types=1);

// Router khusus PHP built-in server: php -S localhost:8000 router.php
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}
if (is_dir($file) && is_file(rtrim($file, '/') . '/index.php')) {
    return false;
}
if (preg_match('~^/Admin/?$~i', $path)) {
    require __DIR__ . '/Admin/index.php';
    return true;
}
if ($path === '/' || $path === '') {
    require __DIR__ . '/index.php';
    return true;
}
if (preg_match('~^/([a-zA-Z0-9_-]+)/?$~', $path, $matches)) {
    $_GET['username'] = $matches[1];
    require __DIR__ . '/index.php';
    return true;
}
http_response_code(404);
echo '404 - Halaman tidak ditemukan';
return true;
