<?php

declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
$admin = require_admin();
$users = all_users(false);
$stats = admin_stats();
$defaultId = default_user_id();
$success = flash('success');
$error = flash('error');
$errors = $_SESSION['admin_errors'] ?? [];
unset($_SESSION['admin_errors']);
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Panel Administrator - CV Multi-User</title><link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>"></head>
<body class="admin-page">
<header class="admin-header">
    <div class="admin-header-inner">
        <a class="brand brand-light" href="<?= e(url()) ?>"><span class="brand-mark">CV</span><span>Portfolio Hub<small>Administrator console</small></span></a>
        <div class="admin-header-nav"><a href="<?= e(url()) ?>" target="_blank">Lihat Front End ↗</a><div class="dashboard-user"><div class="user-avatar">A</div><div><strong>@<?= e($admin['username']) ?></strong><small>Administrator</small></div><a href="<?= e(url('logout.php')) ?>">Keluar</a></div></div>
    </div>
</header>
<main class="admin-main">
    <section class="admin-welcome">
        <div><span class="page-kicker">BACK END / ADMINISTRATOR</span><h1>Manajemen CV Multi-User</h1><p>Kelola akun pengguna, status publik, password, serta tentukan CV yang tampil pada halaman utama.</p></div>
        <button class="button button-accent" type="button" data-toggle-panel="create-user-panel">+ Tambah Pengguna</button>
    </section>

    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($errors): ?><div class="alert alert-error"><strong>Data belum dapat diproses:</strong><ul><?php foreach ($errors as $item): ?><li><?= e($item) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

    <section class="stats-grid">
        <article><span class="stat-icon">U</span><div><strong><?= (int) $stats['total_users'] ?></strong><p>Total pengguna</p></div><small>Semua akun CV</small></article>
        <article><span class="stat-icon">A</span><div><strong><?= (int) $stats['active_users'] ?></strong><p>CV aktif</p></div><small>Dapat diakses publik</small></article>
        <article><span class="stat-icon">D</span><div><strong><?= e($stats['database']) ?></strong><p>Basis data</p></div><small>Penyimpanan aplikasi</small></article>
        <article><span class="stat-icon">★</span><div><strong><?php $du=default_user(); echo $du ? '@'.e($du['username']) : '-'; ?></strong><p>CV default</p></div><small>Tampil di halaman utama</small></article>
    </section>

    <section id="create-user-panel" class="admin-panel create-user-panel <?= $errors ? 'open' : '' ?>">
        <div class="panel-heading"><div><span>NEW ACCOUNT</span><h2>Tambah Pengguna Baru</h2><p>Akun baru otomatis memperoleh halaman CV publik dan dashboard editor.</p></div><button type="button" class="panel-close" data-toggle-panel="create-user-panel">×</button></div>
        <form action="<?= e(url('actions/admin-user.php')) ?>" method="post" class="form-grid admin-create-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create">
            <label>Nama lengkap<input name="full_name" required placeholder="Nama pemilik CV"></label>
            <label>Username / URL<input name="username" required pattern="[a-zA-Z0-9_-]{3,80}" placeholder="contoh: budi_santoso"><small>URL publik: /username</small></label>
            <label>Email akun<input type="email" name="email" required placeholder="nama@email.com"></label>
            <label>Password awal<input type="password" name="password" minlength="6" required placeholder="Minimal 6 karakter"></label>
            <div class="full form-action-right"><button class="button button-primary" type="submit">Buat Akun dan CV</button></div>
        </form>
    </section>

    <section class="admin-panel user-management">
        <div class="panel-heading panel-heading-row"><div><span>USER DIRECTORY</span><h2>Daftar Pengguna</h2><p><?= count($users) ?> akun CV terdaftar pada aplikasi.</p></div><label class="search-box">⌕<input id="user-search" placeholder="Cari username atau nama..."></label></div>
        <div class="user-table-wrap">
            <table class="user-table">
                <thead><tr><th>Pengguna</th><th>Alamat CV Publik</th><th>Status</th><th>Terakhir Login</th><th>Aksi</th></tr></thead>
                <tbody id="user-table-body">
                <?php foreach ($users as $user): $isDefault=(int)$defaultId===(int)$user['id']; ?>
                    <tr data-search="<?= e(strtolower($user['username'].' '.$user['full_name'].' '.$user['email'])) ?>">
                        <td><div class="table-user"><div class="table-avatar"><?= e(strtoupper(substr((string) $user['full_name'],0,1))) ?></div><div><strong><?= e($user['full_name']) ?></strong><span>@<?= e($user['username']) ?> · <?= e($user['email']) ?></span></div></div></td>
                        <td><a class="public-link" href="<?= e(public_profile_url($user['username'])) ?>" target="_blank"><?= e(public_profile_url($user['username'])) ?> ↗</a><?php if ($isDefault): ?><span class="default-badge">★ DEFAULT</span><?php endif; ?></td>
                        <td><span class="account-status <?= (int)$user['is_active']===1?'active':'inactive' ?>"><i></i><?= (int)$user['is_active']===1?'Aktif':'Nonaktif' ?></span></td>
                        <td><span class="date-text"><?= e(format_datetime($user['last_login_at'])) ?></span></td>
                        <td><div class="table-actions"><a class="icon-action" title="Edit CV" href="<?= e(url('dashboard.php?user='.(int)$user['id'])) ?>">✎</a><button class="icon-action" title="Kelola akun" type="button" data-toggle-details="user-<?= (int)$user['id'] ?>">⋯</button></div></td>
                    </tr>
                    <tr class="user-detail-row" id="user-<?= (int)$user['id'] ?>"><td colspan="5">
                        <div class="user-detail-panel">
                            <div class="detail-column"><h3>Informasi Akun</h3><form action="<?= e(url('actions/admin-user.php')) ?>" method="post" class="form-grid compact-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>"><label>Nama lengkap<input name="full_name" value="<?= e($user['full_name']) ?>" required></label><label>Username<input name="username" value="<?= e($user['username']) ?>" required></label><label class="full">Email<input type="email" name="email" value="<?= e($user['email']) ?>" required></label><div class="full"><button class="button button-small button-outline" type="submit">Simpan Informasi</button></div></form></div>
                            <div class="detail-column"><h3>Reset Password</h3><form action="<?= e(url('actions/admin-user.php')) ?>" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="reset_password"><input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>"><label>Password baru<input type="password" name="new_password" minlength="6" required placeholder="Minimal 6 karakter"></label><button class="button button-small button-outline" type="submit">Reset Password</button></form></div>
                            <div class="detail-column detail-actions"><h3>Pengaturan Publik</h3><?php if (!$isDefault && (int)$user['is_active']===1): ?><form action="<?= e(url('actions/admin-user.php')) ?>" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="set_default"><input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>"><button class="button button-small button-primary" type="submit">Jadikan CV Default</button></form><?php endif; ?><form action="<?= e(url('actions/admin-user.php')) ?>" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>"><input type="hidden" name="active" value="<?= (int)$user['is_active']===1?'0':'1' ?>"><button class="button button-small button-ghost" type="submit"><?= (int)$user['is_active']===1?'Nonaktifkan Akun':'Aktifkan Akun' ?></button></form><?php if (!$isDefault): ?><form action="<?= e(url('actions/admin-user.php')) ?>" method="post" data-confirm="Hapus akun @<?= e($user['username']) ?> beserta seluruh data CV?"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>"><button class="button button-small button-danger" type="submit">Hapus Akun</button></form><?php endif; ?></div>
                        </div>
                    </td></tr>
                <?php endforeach; ?>
                <?php if (!$users): ?><tr><td colspan="5" class="empty-table">Belum ada user CV. Tambahkan pengguna pertama.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-panel access-guide">
        <div class="panel-heading"><div><span>ROUTE GUIDE</span><h2>Contoh Cara Akses</h2><p>Alamat mengikuti instruksi UAS dan bekerja melalui mod_rewrite Apache.</p></div></div>
        <div class="route-grid"><div><b>Administrator</b><code><?= e(url('Admin/')) ?></code><span>Login dan manajemen seluruh user.</span></div><div><b>Login User</b><code><?= e(url('login.php')) ?></code><span>Akses dashboard pemilik CV.</span></div><div><b>CV User</b><code><?= e(public_profile_url('cecep_suwanda')) ?></code><span>Contoh halaman CV berdasarkan username.</span></div><div><b>CV Default</b><code><?= e(url()) ?></code><span>Profil utama yang dipilih admin.</span></div></div>
    </section>
</main>
<script src="<?= e(url('assets/js/admin.js')) ?>"></script>
</body>
</html>
