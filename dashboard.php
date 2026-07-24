<?php

declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
$actor = require_login();

if ($actor['role'] === 'admin') {
    $targetUserId = (int) ($_GET['user'] ?? default_user_id() ?? 0);
} else {
    $targetUserId = (int) $actor['id'];
}
if (!$targetUserId || !can_edit_user($actor, $targetUserId)) {
    http_response_code(403);
    require __DIR__ . '/views/403.php';
    exit;
}
$cv = load_cv_by_user_id($targetUserId, true);
if (!$cv) {
    flash('error', 'Profil yang akan diedit tidak ditemukan.');
    redirect($actor['role'] === 'admin' ? 'Admin/' : 'login.php');
}
$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);
$success = flash('success');
$error = flash('error');
$isOwner = (int) $actor['id'] === $targetUserId;
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Editor CV @<?= e($cv['username']) ?></title><link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>"></head>
<body class="dashboard-page">
<header class="dashboard-header">
    <div class="dashboard-header-inner">
        <div><a class="brand brand-light" href="<?= e(url()) ?>"><span class="brand-mark">CV</span><span>Portfolio Hub<small>Back end editor</small></span></a></div>
        <div class="dashboard-user"><div class="user-avatar"><?= e(strtoupper(substr((string) $actor['username'], 0, 1))) ?></div><div><strong>@<?= e($actor['username']) ?></strong><small><?= $actor['role'] === 'admin' ? 'Administrator' : 'Pemilik CV' ?></small></div><a href="<?= e(url('logout.php')) ?>">Keluar</a></div>
    </div>
</header>
<div class="dashboard-layout">
    <aside class="dashboard-sidebar">
        <div class="sidebar-profile-mini"><img src="<?= e(url($cv['photo_path'])) ?>" alt="Foto"><div><strong><?= e($cv['full_name']) ?></strong><span>@<?= e($cv['username']) ?></span></div></div>
        <nav class="editor-nav">
            <a class="active" href="#profil"><span>01</span>Profil Utama</a>
            <a href="#pendidikan"><span>02</span>Pendidikan</a>
            <a href="#pengalaman"><span>03</span>Pengalaman</a>
            <a href="#keahlian"><span>04</span>Keahlian</a>
            <a href="#bahasa"><span>05</span>Bahasa</a>
            <a href="#portofolio"><span>06</span>Portofolio</a>
            <?php if ($isOwner): ?><a href="#keamanan"><span>07</span>Keamanan</a><?php endif; ?>
        </nav>
        <div class="sidebar-actions">
            <a class="button button-outline button-block" href="<?= e(public_profile_url($cv['username'])) ?>" target="_blank">Buka CV Publik ↗</a>
            <?php if ($actor['role'] === 'admin'): ?><a class="button button-ghost button-block" href="<?= e(url('Admin/')) ?>">← Panel Admin</a><?php endif; ?>
        </div>
    </aside>

    <main class="dashboard-content">
        <div class="dashboard-title-row">
            <div><span class="page-kicker"><?= $actor['role'] === 'admin' ? 'ADMIN EDIT MODE' : 'USER DASHBOARD' ?></span><h1>Editor Curriculum Vitae</h1><p>Perbarui informasi CV <strong>@<?= e($cv['username']) ?></strong>. Semua perubahan tersimpan pada <?= e(database_label()) ?>.</p></div>
            <div class="profile-status"><span class="status-dot <?= (int) $cv['user']['is_active'] === 1 ? 'on' : 'off' ?>"></span><?= (int) $cv['user']['is_active'] === 1 ? 'Profil Aktif' : 'Profil Nonaktif' ?><?= $cv['is_default'] ? '<b>CV Default</b>' : '' ?></div>
        </div>
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <?php if ($errors): ?><div class="alert alert-error"><strong>Perubahan belum disimpan:</strong><ul><?php foreach ($errors as $item): ?><li><?= e($item) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

        <form class="editor-form" action="<?= e(url('actions/save-cv.php')) ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="target_user_id" value="<?= (int) $targetUserId ?>">

            <section class="editor-section" id="profil">
                <div class="editor-section-heading"><div><span>01</span><div><h2>Profil Utama</h2><p>Identitas, kontak, dan ringkasan profesional.</p></div></div></div>
                <div class="form-grid">
                    <label>Nama lengkap<input name="full_name" value="<?= e($cv['full_name']) ?>" required></label>
                    <label>Profesi / headline<input name="profession" value="<?= e($cv['profession']) ?>" required></label>
                    <label class="full">Tagline<input name="tagline" value="<?= e($cv['tagline']) ?>"></label>
                    <label class="full">Tentang saya<textarea name="about" rows="6" required><?= e($cv['about']) ?></textarea></label>
                    <label>Email publik<input type="email" name="email" value="<?= e($cv['email']) ?>" required></label>
                    <label>Nomor telepon<input name="phone" value="<?= e($cv['phone']) ?>"></label>
                    <label>Alamat<input name="address" value="<?= e($cv['address']) ?>"></label>
                    <label>URL GitHub<input type="url" name="github" value="<?= e($cv['github']) ?>" placeholder="https://github.com/username"></label>
                    <label>NIM<input name="nim" value="<?= e($cv['nim']) ?>"></label>
                    <label>Program studi<input name="program_study" value="<?= e($cv['program_study']) ?>"></label>
                    <label>Status akademik<input name="student_status" value="<?= e($cv['student_status']) ?>"></label>
                    <label>Angkatan<input name="cohort" value="<?= e($cv['cohort']) ?>"></label>
                    <label class="full photo-field">Foto profil
                        <span class="current-photo"><img src="<?= e(url($cv['photo_path'])) ?>" alt="Foto saat ini"><small>JPG, PNG, atau WEBP. Maksimal 2 MB. Foto lama tetap digunakan apabila tidak memilih file baru.</small></span>
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                    </label>
                </div>
            </section>

            <section class="editor-section" id="pendidikan">
                <div class="editor-section-heading"><div><span>02</span><div><h2>Riwayat Pendidikan</h2><p>Urutkan dari pendidikan terbaru.</p></div></div><button type="button" class="add-row" data-add="education">+ Tambah</button></div>
                <div class="repeat-list" data-list="education">
                <?php foreach ($cv['education'] as $item): ?>
                    <div class="repeat-card"><button type="button" class="remove-row">Hapus</button><div class="form-grid"><label>Institusi<input name="education[institution][]" value="<?= e($item['institution']) ?>"></label><label>Jurusan / program<input name="education[major][]" value="<?= e($item['major']) ?>"></label><label>Periode<input name="education[period][]" value="<?= e($item['period']) ?>"></label><label class="full">Deskripsi<textarea name="education[description][]" rows="3"><?= e($item['description']) ?></textarea></label></div></div>
                <?php endforeach; ?>
                </div>
            </section>

            <section class="editor-section" id="pengalaman">
                <div class="editor-section-heading"><div><span>03</span><div><h2>Pengalaman dan Aktivitas</h2><p>Proyek, organisasi, pekerjaan, atau aktivitas akademik.</p></div></div><button type="button" class="add-row" data-add="experiences">+ Tambah</button></div>
                <div class="repeat-list" data-list="experiences">
                <?php foreach ($cv['experiences'] as $item): ?>
                    <div class="repeat-card"><button type="button" class="remove-row">Hapus</button><div class="form-grid"><label>Judul kegiatan<input name="experiences[title][]" value="<?= e($item['title']) ?>"></label><label>Organisasi / konteks<input name="experiences[organization][]" value="<?= e($item['organization']) ?>"></label><label>Periode<input name="experiences[period][]" value="<?= e($item['period']) ?>"></label><label class="full">Deskripsi<textarea name="experiences[description][]" rows="3"><?= e($item['description']) ?></textarea></label></div></div>
                <?php endforeach; ?>
                </div>
            </section>

            <section class="editor-section" id="keahlian">
                <div class="editor-section-heading"><div><span>04</span><div><h2>Keahlian Teknis</h2><p>Nilai level menggunakan rentang 0 sampai 100.</p></div></div><button type="button" class="add-row" data-add="skills">+ Tambah</button></div>
                <div class="repeat-list compact-list" data-list="skills">
                <?php foreach ($cv['skills'] as $item): ?>
                    <div class="repeat-card skill-editor-row"><button type="button" class="remove-row">Hapus</button><label>Nama keahlian<input name="skills[skill_name][]" value="<?= e($item['skill_name']) ?>"></label><label>Level<input type="number" min="0" max="100" name="skills[skill_level][]" value="<?= (int) $item['skill_level'] ?>"></label></div>
                <?php endforeach; ?>
                </div>
            </section>

            <section class="editor-section" id="bahasa">
                <div class="editor-section-heading"><div><span>05</span><div><h2>Kemampuan Bahasa</h2><p>Tambahkan bahasa dan tingkat penguasaan.</p></div></div><button type="button" class="add-row" data-add="languages">+ Tambah</button></div>
                <div class="repeat-list compact-list" data-list="languages">
                <?php foreach ($cv['languages'] as $item): ?>
                    <div class="repeat-card skill-editor-row"><button type="button" class="remove-row">Hapus</button><label>Bahasa<input name="languages[language_name][]" value="<?= e($item['language_name']) ?>"></label><label>Tingkat kemampuan<input name="languages[proficiency][]" value="<?= e($item['proficiency']) ?>"></label></div>
                <?php endforeach; ?>
                </div>
            </section>

            <section class="editor-section" id="portofolio">
                <div class="editor-section-heading"><div><span>06</span><div><h2>Portofolio</h2><p>Tampilkan karya atau proyek terbaik.</p></div></div><button type="button" class="add-row" data-add="portfolios">+ Tambah</button></div>
                <div class="repeat-list" data-list="portfolios">
                <?php foreach ($cv['portfolios'] as $item): ?>
                    <div class="repeat-card"><button type="button" class="remove-row">Hapus</button><div class="form-grid"><label>Judul proyek<input name="portfolios[title][]" value="<?= e($item['title']) ?>"></label><label>Teknologi<input name="portfolios[technology][]" value="<?= e($item['technology']) ?>"></label><label class="full">Deskripsi<textarea name="portfolios[description][]" rows="3"><?= e($item['description']) ?></textarea></label><label class="full">Tautan proyek<input type="url" name="portfolios[project_url][]" value="<?= e($item['project_url']) ?>"></label></div></div>
                <?php endforeach; ?>
                </div>
            </section>

            <div class="save-bar"><div><strong>Simpan perubahan CV</strong><span>Data akan langsung tampil pada halaman publik @<?= e($cv['username']) ?>.</span></div><button class="button button-accent" type="submit">Simpan Semua Perubahan</button></div>
        </form>

        <?php if ($isOwner): ?>
        <section class="editor-section security-section" id="keamanan">
            <div class="editor-section-heading"><div><span>07</span><div><h2>Keamanan Akun</h2><p>Ubah password secara berkala untuk menjaga akun.</p></div></div></div>
            <form class="form-grid" action="<?= e(url('actions/change-password.php')) ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label>Password saat ini<input type="password" name="current_password" required></label>
                <label>Password baru<input type="password" name="new_password" minlength="6" required></label>
                <label>Konfirmasi password baru<input type="password" name="confirm_password" minlength="6" required></label>
                <div class="full form-action-right"><button class="button button-outline" type="submit">Ubah Password</button></div>
            </form>
        </section>
        <?php endif; ?>
    </main>
</div>

<template id="template-education"><div class="repeat-card"><button type="button" class="remove-row">Hapus</button><div class="form-grid"><label>Institusi<input name="education[institution][]"></label><label>Jurusan / program<input name="education[major][]"></label><label>Periode<input name="education[period][]"></label><label class="full">Deskripsi<textarea name="education[description][]" rows="3"></textarea></label></div></div></template>
<template id="template-experiences"><div class="repeat-card"><button type="button" class="remove-row">Hapus</button><div class="form-grid"><label>Judul kegiatan<input name="experiences[title][]"></label><label>Organisasi / konteks<input name="experiences[organization][]"></label><label>Periode<input name="experiences[period][]"></label><label class="full">Deskripsi<textarea name="experiences[description][]" rows="3"></textarea></label></div></div></template>
<template id="template-skills"><div class="repeat-card skill-editor-row"><button type="button" class="remove-row">Hapus</button><label>Nama keahlian<input name="skills[skill_name][]"></label><label>Level<input type="number" min="0" max="100" name="skills[skill_level][]" value="60"></label></div></template>
<template id="template-languages"><div class="repeat-card skill-editor-row"><button type="button" class="remove-row">Hapus</button><label>Bahasa<input name="languages[language_name][]"></label><label>Tingkat kemampuan<input name="languages[proficiency][]"></label></div></template>
<template id="template-portfolios"><div class="repeat-card"><button type="button" class="remove-row">Hapus</button><div class="form-grid"><label>Judul proyek<input name="portfolios[title][]"></label><label>Teknologi<input name="portfolios[technology][]"></label><label class="full">Deskripsi<textarea name="portfolios[description][]" rows="3"></textarea></label><label class="full">Tautan proyek<input type="url" name="portfolios[project_url][]"></label></div></div></template>
<script src="<?= e(url('assets/js/editor.js')) ?>"></script>
</body>
</html>
