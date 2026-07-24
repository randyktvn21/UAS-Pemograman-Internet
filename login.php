<?php

declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
$current = current_user();
if ($current) {
    redirect($current['role'] === 'admin' ? 'Admin/' : 'dashboard.php');
}
$adminMode = isset($_GET['admin']) && $_GET['admin'] === '1';
$error = flash('error');
$success = flash('success');
$next = clean_line($_GET['next'] ?? '', 500);
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $adminMode ? 'Login Administrator' : 'Login User' ?> - CV Multi-User</title><link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>"></head>
<body class="auth-page">
<main class="auth-shell">
    <section class="auth-showcase">
        <a class="brand brand-light" href="<?= e(url()) ?>"><span class="brand-mark">CV</span><span>Portfolio Hub<small>Multi-user curriculum vitae</small></span></a>
        <div class="auth-showcase-copy"><span class="eyebrow">PEMROGRAMAN INTERNET</span><h1>Satu aplikasi,<br><em>banyak CV.</em></h1><p>Setiap pengguna memiliki akun, dashboard editor, dan alamat CV publik sendiri. Administrator mengelola user serta menentukan CV yang tampil pada halaman utama.</p></div>
        <div class="auth-route-list"><div><span>01</span><b>/Admin</b><small>Akses panel administrator</small></div><div><span>02</span><b>/login.php</b><small>Login pemilik CV</small></div><div><span>03</span><b>/username</b><small>Halaman CV publik</small></div></div>
    </section>
    <section class="auth-form-panel">
        <div class="auth-form-wrap">
            <a class="back-link" href="<?= e(url()) ?>">← Kembali ke CV publik</a>
            <div class="auth-heading"><span class="auth-badge"><?= $adminMode ? 'ADMINISTRATOR' : 'USER AREA' ?></span><h2><?= $adminMode ? 'Masuk ke panel admin' : 'Masuk ke dashboard CV' ?></h2><p>Gunakan username dan password yang telah dibuat oleh administrator.</p></div>
            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
            <form class="auth-form" action="<?= e(url('actions/login.php')) ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="admin_only" value="<?= $adminMode ? '1' : '0' ?>">
                <input type="hidden" name="next" value="<?= e($next) ?>">
                <label>Username<div class="input-with-icon"><span>@</span><input name="username" autocomplete="username" placeholder="contoh: randy" required autofocus></div></label>
                <label>Password<div class="input-with-icon"><span>●</span><input type="password" name="password" autocomplete="current-password" placeholder="Masukkan password" required></div></label>
                <button class="button button-primary button-block" type="submit">Masuk ke aplikasi <span>→</span></button>
            </form>
            <div class="demo-credentials">
                <strong>Akun demonstrasi</strong>
                <?php if ($adminMode): ?><code>admin / admin123</code><?php else: ?><code>randy / randy123</code><code>cecep_suwanda / cecep123</code><?php endif; ?>
                <small>Ganti password setelah aplikasi digunakan secara nyata.</small>
            </div>
            <div class="auth-switch"><?= $adminMode ? 'Bukan administrator?' : 'Anda administrator?' ?> <a href="<?= e(url($adminMode ? 'login.php' : 'login.php?admin=1')) ?>"><?= $adminMode ? 'Login sebagai user' : 'Buka login admin' ?></a></div>
        </div>
    </section>
</main>
</body>
</html>
