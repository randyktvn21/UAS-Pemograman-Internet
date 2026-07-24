<?php

declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$username = isset($_GET['username']) ? normalize_username($_GET['username']) : null;
try {
    $cv = load_public_cv($username);
    $loadError = null;
} catch (Throwable $exception) {
    $cv = null;
    $loadError = $exception->getMessage();
}
$current = current_user();
if (!$cv) {
    http_response_code(404);
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $cv ? e($cv['full_name'] . ' - Curriculum Vitae') : 'CV Tidak Ditemukan' ?></title>
    <meta name="description" content="Curriculum Vitae publik pada aplikasi CV multi-user.">
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>
<body class="public-page">
<header class="site-header">
    <a class="brand" href="<?= e(url()) ?>"><span class="brand-mark">CV</span><span>Portfolio Hub<small>Multi-user curriculum vitae</small></span></a>
    <nav class="site-nav">
        <?php if ($cv): ?><a href="#tentang">Tentang</a><a href="#pengalaman">Pengalaman</a><a href="#portofolio">Portofolio</a><?php endif; ?>
        <?php if ($current): ?>
            <a class="nav-pill" href="<?= e($current['role'] === 'admin' ? url('Admin/') : url('dashboard.php')) ?>"><?= $current['role'] === 'admin' ? 'Panel Admin' : 'Dashboard Saya' ?></a>
        <?php else: ?>
            <a class="nav-pill" href="<?= e(url('login.php')) ?>">Masuk</a>
        <?php endif; ?>
    </nav>
</header>

<?php if (!$cv): ?>
<main class="center-page public-not-found">
    <section class="status-card">
        <span class="status-code">404</span>
        <h1>CV pengguna tidak ditemukan</h1>
        <p><?= $loadError ? e($loadError) : 'Username tidak tersedia, akun tidak aktif, atau administrator belum menentukan CV default.' ?></p>
        <a class="button button-primary" href="<?= e(url()) ?>">Buka CV default</a>
    </section>
</main>
<?php else: ?>
<main class="resume-shell">
    <section class="resume-hero">
        <div class="hero-copy">
            <div class="profile-handle"><span class="online-dot"></span>@<?= e($cv['username']) ?><?= $cv['is_default'] ? '<span class="default-chip">CV Default</span>' : '' ?></div>
            <p class="eyebrow">CURRICULUM VITAE / <?= e(strtoupper($cv['program_study'])) ?></p>
            <h1><?= e($cv['full_name']) ?></h1>
            <p class="hero-profession"><?= e($cv['profession']) ?></p>
            <p class="hero-tagline"><?= e($cv['tagline']) ?></p>
            <div class="hero-links">
                <a href="mailto:<?= e($cv['email']) ?>">Email</a>
                <?php if ($cv['github']): ?><a href="<?= e($cv['github']) ?>" target="_blank" rel="noopener">GitHub</a><?php endif; ?>
                <a href="#portofolio">Lihat karya</a>
            </div>
        </div>
        <div class="hero-photo-wrap">
            <div class="hero-ring"></div>
            <img class="hero-photo" src="<?= e(url($cv['photo_path'])) ?>" alt="Foto profil <?= e($cv['full_name']) ?>">
            <div class="hero-year">EST.<strong><?= e($cv['cohort'] ?: date('Y')) ?></strong></div>
        </div>
    </section>

    <section class="resume-body">
        <aside class="resume-sidebar">
            <section class="sidebar-card contact-card">
                <p class="section-label">Kontak</p>
                <dl class="contact-list">
                    <div><dt>Email</dt><dd><a href="mailto:<?= e($cv['email']) ?>"><?= e($cv['email']) ?></a></dd></div>
                    <div><dt>Telepon</dt><dd><?= e($cv['phone']) ?></dd></div>
                    <div><dt>Alamat</dt><dd><?= e($cv['address']) ?></dd></div>
                    <div><dt>Username</dt><dd>@<?= e($cv['username']) ?></dd></div>
                </dl>
            </section>

            <section class="sidebar-section">
                <div class="sidebar-title"><span>01</span><h2>Data Akademik</h2></div>
                <dl class="academic-list">
                    <div><dt>NIM</dt><dd><?= e($cv['nim']) ?></dd></div>
                    <div><dt>Program studi</dt><dd><?= e($cv['program_study']) ?></dd></div>
                    <div><dt>Status</dt><dd><?= e($cv['student_status']) ?></dd></div>
                    <div><dt>Angkatan</dt><dd><?= e($cv['cohort']) ?></dd></div>
                </dl>
            </section>

            <section class="sidebar-section">
                <div class="sidebar-title"><span>02</span><h2>Keahlian</h2></div>
                <div class="skill-list">
                    <?php foreach ($cv['skills'] as $skill): ?>
                    <div class="skill-item"><div><strong><?= e($skill['skill_name']) ?></strong><span><?= (int) $skill['skill_level'] ?>%</span></div><div class="skill-track"><i style="width:<?= (int) $skill['skill_level'] ?>%"></i></div></div>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php if ($cv['languages']): ?>
            <section class="sidebar-section">
                <div class="sidebar-title"><span>03</span><h2>Bahasa</h2></div>
                <div class="language-list">
                    <?php foreach ($cv['languages'] as $language): ?><div><strong><?= e($language['language_name']) ?></strong><span><?= e($language['proficiency']) ?></span></div><?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </aside>

        <div class="resume-content">
            <section class="content-section" id="tentang">
                <div class="content-heading"><div><span>PROFILE</span><h2>Tentang Saya</h2></div><b>01</b></div>
                <p class="about-text"><?= nl2br(e($cv['about'])) ?></p>
            </section>

            <section class="content-section">
                <div class="content-heading"><div><span>EDUCATION</span><h2>Riwayat Pendidikan</h2></div><b>02</b></div>
                <div class="timeline">
                    <?php foreach ($cv['education'] as $item): ?>
                    <article class="timeline-item"><i></i><time><?= e($item['period']) ?></time><h3><?= e($item['institution']) ?></h3><h4><?= e($item['major']) ?></h4><p><?= e($item['description']) ?></p></article>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php if ($cv['experiences']): ?>
            <section class="content-section" id="pengalaman">
                <div class="content-heading"><div><span>EXPERIENCE</span><h2>Pengalaman dan Aktivitas</h2></div><b>03</b></div>
                <div class="experience-grid">
                    <?php foreach ($cv['experiences'] as $index => $item): ?>
                    <article class="experience-card"><div class="experience-no"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></div><div><span><?= e($item['period']) ?> / <?= e($item['organization']) ?></span><h3><?= e($item['title']) ?></h3><p><?= e($item['description']) ?></p></div></article>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($cv['portfolios']): ?>
            <section class="content-section" id="portofolio">
                <div class="content-heading"><div><span>SELECTED WORK</span><h2>Portofolio</h2></div><b>04</b></div>
                <div class="portfolio-grid">
                    <?php foreach ($cv['portfolios'] as $index => $item): ?>
                    <article class="portfolio-card"><div class="portfolio-index"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></div><span><?= e($item['technology']) ?></span><h3><?= e($item['title']) ?></h3><p><?= e($item['description']) ?></p><?php if ($item['project_url']): ?><a href="<?= e($item['project_url']) ?>" target="_blank" rel="noopener">Buka proyek →</a><?php endif; ?></article>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </section>
    <footer class="resume-footer"><span>CV publik @<?= e($cv['username']) ?></span><span>Diperbarui <?= e(format_datetime($cv['updated_at'])) ?></span><span>Aplikasi CV Multi-User</span></footer>
</main>
<?php endif; ?>
</body>
</html>
