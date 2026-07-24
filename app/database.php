<?php

declare(strict_types=1);

const APP_ADMIN_HASH = '$2y$12$66/U4CNkPQhttccXuL9VQuNClITKUiQbyFBY3hjCSB5lRCnH0XN4K';
const APP_RANDY_HASH = '$2y$12$VfP96Ts2C3Rh1r5wRvsoY.Bwvs3vFBCAWuJ7PouSFRkvDWrYt1Iry';
const APP_CECEP_HASH = '$2y$12$ON7/JYhNY4E.xytgJ/RLQOfS1ayfaJUFopxBcmoU58I3EzdXArcjG';

function app_settings(): array
{
    return [
        'requested_driver' => strtolower((string) (getenv('DB_DRIVER') ?: 'sqlite')),
        'sqlite_path' => dirname(__DIR__) . '/storage/cv_multiuser.sqlite',
        'json_path' => dirname(__DIR__) . '/storage/cv_multiuser.json',
        'mysql_host' => getenv('DB_HOST') ?: '127.0.0.1',
        'mysql_port' => getenv('DB_PORT') ?: '3306',
        'mysql_database' => getenv('DB_DATABASE') ?: 'cv_multiuser',
        'mysql_username' => getenv('DB_USERNAME') ?: 'root',
        'mysql_password' => getenv('DB_PASSWORD') ?: '',
    ];
}

/**
 * SQLite menjadi pilihan utama agar mudah dijalankan di XAMPP. Jika driver PDO
 * belum tersedia pada lingkungan pengujian, aplikasi memakai penyimpanan JSON
 * kompatibel supaya antarmuka tetap dapat didemonstrasikan. Struktur SQL tetap
 * disediakan untuk SQLite/MySQL dan dipakai otomatis ketika drivernya aktif.
 */
function database_mode(): string
{
    static $mode;
    if (is_string($mode)) {
        return $mode;
    }

    $requested = app_settings()['requested_driver'];
    if ($requested === 'json') {
        return $mode = 'json';
    }

    $drivers = class_exists(PDO::class) ? PDO::getAvailableDrivers() : [];
    if (in_array($requested, ['sqlite', 'mysql'], true) && in_array($requested, $drivers, true)) {
        return $mode = $requested;
    }

    return $mode = 'json';
}

function database(): PDO
{
    static $connection;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $settings = app_settings();
    $mode = database_mode();
    if ($mode === 'json') {
        throw new RuntimeException('PDO tidak aktif; aplikasi sedang menggunakan penyimpanan kompatibilitas JSON.');
    }

    if ($mode === 'mysql') {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $settings['mysql_host'],
            $settings['mysql_port'],
            $settings['mysql_database']
        );
        $connection = new PDO($dsn, $settings['mysql_username'], $settings['mysql_password'], pdo_options());
        create_mysql_tables($connection);
    } else {
        $directory = dirname((string) $settings['sqlite_path']);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder penyimpanan basis data tidak dapat dibuat.');
        }
        $connection = new PDO('sqlite:' . $settings['sqlite_path'], null, null, pdo_options());
        $connection->exec('PRAGMA foreign_keys = ON');
        create_sqlite_tables($connection);
    }

    seed_pdo_database($connection);
    return $connection;
}

function pdo_options(): array
{
    return [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
}

function create_sqlite_tables(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user',
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    last_login_at TEXT NULL
);
CREATE TABLE IF NOT EXISTS profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    full_name TEXT NOT NULL,
    profession TEXT NOT NULL,
    tagline TEXT NOT NULL,
    about TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT NOT NULL,
    address TEXT NOT NULL,
    github TEXT NOT NULL,
    nim TEXT NOT NULL,
    program_study TEXT NOT NULL,
    student_status TEXT NOT NULL,
    cohort TEXT NOT NULL,
    photo_path TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS education (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    profile_id INTEGER NOT NULL,
    institution TEXT NOT NULL,
    major TEXT NOT NULL,
    period TEXT NOT NULL,
    description TEXT NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS experiences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    profile_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    organization TEXT NOT NULL,
    period TEXT NOT NULL,
    description TEXT NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS skills (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    profile_id INTEGER NOT NULL,
    skill_name TEXT NOT NULL,
    skill_level INTEGER NOT NULL DEFAULT 50,
    sort_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS languages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    profile_id INTEGER NOT NULL,
    language_name TEXT NOT NULL,
    proficiency TEXT NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS portfolios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    profile_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    technology TEXT NOT NULL,
    description TEXT NOT NULL,
    project_url TEXT NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS system_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT NOT NULL
);
SQL);
}

function create_mysql_tables(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    last_login_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    profession VARCHAR(150) NOT NULL,
    tagline VARCHAR(190) NOT NULL,
    about TEXT NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    address VARCHAR(190) NOT NULL,
    github VARCHAR(255) NOT NULL,
    nim VARCHAR(50) NOT NULL,
    program_study VARCHAR(150) NOT NULL,
    student_status VARCHAR(100) NOT NULL,
    cohort VARCHAR(20) NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS education (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    institution VARCHAR(190) NOT NULL,
    major VARCHAR(190) NOT NULL,
    period VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_education_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS experiences (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    organization VARCHAR(190) NOT NULL,
    period VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_experiences_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS skills (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    skill_name VARCHAR(120) NOT NULL,
    skill_level TINYINT UNSIGNED NOT NULL DEFAULT 50,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_skills_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS languages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    language_name VARCHAR(120) NOT NULL,
    proficiency VARCHAR(100) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_languages_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portfolios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    technology VARCHAR(190) NOT NULL,
    description TEXT NOT NULL,
    project_url VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_portfolios_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
}

function seed_pdo_database(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $seed = initial_data();
    $pdo->beginTransaction();
    try {
        $userStatement = $pdo->prepare(
            'INSERT INTO users (id, username, email, password_hash, role, is_active, created_at, updated_at, last_login_at)
             VALUES (:id, :username, :email, :password_hash, :role, :is_active, :created_at, :updated_at, :last_login_at)'
        );
        foreach ($seed['users'] as $user) {
            $userStatement->execute($user);
        }

        $profileStatement = $pdo->prepare(
            'INSERT INTO profiles (id, user_id, full_name, profession, tagline, about, email, phone, address, github, nim, program_study, student_status, cohort, photo_path, updated_at)
             VALUES (:id, :user_id, :full_name, :profession, :tagline, :about, :email, :phone, :address, :github, :nim, :program_study, :student_status, :cohort, :photo_path, :updated_at)'
        );
        foreach ($seed['profiles'] as $profile) {
            $profileStatement->execute($profile);
        }

        foreach (['education', 'experiences', 'skills', 'languages', 'portfolios'] as $table) {
            foreach ($seed[$table] as $row) {
                $columns = array_keys($row);
                $sql = sprintf(
                    'INSERT INTO %s (%s) VALUES (%s)',
                    $table,
                    implode(', ', $columns),
                    implode(', ', array_map(static fn(string $column): string => ':' . $column, $columns))
                );
                $pdo->prepare($sql)->execute($row);
            }
        }

        $setting = $pdo->prepare('INSERT INTO system_settings (setting_key, setting_value) VALUES (:key, :value)');
        foreach ($seed['settings'] as $key => $value) {
            $setting->execute(['key' => $key, 'value' => (string) $value]);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function initial_data(): array
{
    $now = date('Y-m-d H:i:s');
    return [
        'users' => [
            ['id' => 1, 'username' => 'admin', 'email' => 'admin@cv.local', 'password_hash' => APP_ADMIN_HASH, 'role' => 'admin', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now, 'last_login_at' => null],
            ['id' => 2, 'username' => 'randy', 'email' => 'randyktvn2323@gmail.com', 'password_hash' => APP_RANDY_HASH, 'role' => 'user', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now, 'last_login_at' => null],
            ['id' => 3, 'username' => 'cecep_suwanda', 'email' => 'cecep@example.com', 'password_hash' => APP_CECEP_HASH, 'role' => 'user', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now, 'last_login_at' => null],
        ],
        'profiles' => [
            [
                'id' => 1, 'user_id' => 2, 'full_name' => 'Randy Oktaviana Hertland',
                'profession' => 'Mahasiswa Teknik Informatika',
                'tagline' => 'Web Development | Interface Design | Problem Solving',
                'about' => 'Saya adalah mahasiswa Teknik Informatika yang tertarik pada pengembangan website dan desain antarmuka. Saya senang membangun aplikasi yang terstruktur, mudah digunakan, serta mampu menyimpan data dengan aman. Melalui proyek ini saya mengembangkan aplikasi CV yang memiliki front end, back end, autentikasi, dan dukungan multi-user.',
                'email' => 'randyktvn2323@gmail.com', 'phone' => '08975645637',
                'address' => 'Bandung, Indonesia', 'github' => 'https://github.com/username/project_cv',
                'nim' => '301230053', 'program_study' => 'Teknik Informatika',
                'student_status' => 'Mahasiswa Aktif', 'cohort' => '2023',
                'photo_path' => 'assets/img/pict.jpeg', 'updated_at' => $now,
            ],
            [
                'id' => 2, 'user_id' => 3, 'full_name' => 'Cecep Suwanda',
                'profession' => 'Dosen dan Praktisi Teknologi Informasi',
                'tagline' => 'Teaching | Software Engineering | Digital Innovation',
                'about' => 'Profil contoh ini disediakan untuk menunjukkan bahwa setiap pengguna mempunyai halaman CV publik yang berbeda. Data dapat diperbarui oleh pemilik akun melalui dashboard masing-masing dan dikelola oleh administrator.',
                'email' => 'cecep@example.com', 'phone' => '0812-0000-0000',
                'address' => 'Bandung, Jawa Barat', 'github' => 'https://github.com/username',
                'nim' => '-', 'program_study' => 'Teknik Informatika',
                'student_status' => 'Dosen', 'cohort' => '-',
                'photo_path' => 'assets/img/avatar-default.svg', 'updated_at' => $now,
            ],
        ],
        'education' => [
            ['id' => 1, 'profile_id' => 1, 'institution' => 'Universitas Bale Bandung', 'major' => 'S1 Teknik Informatika', 'period' => '2023 - Sekarang', 'description' => 'Mempelajari pemrograman, basis data, pengembangan web, jaringan komputer, serta perancangan antarmuka aplikasi.', 'sort_order' => 0],
            ['id' => 2, 'profile_id' => 1, 'institution' => 'SMA Pasundan Banjaran', 'major' => 'Matematika dan Ilmu Pengetahuan Alam', 'period' => '2018 - 2021', 'description' => 'Mengembangkan dasar kemampuan akademik, komunikasi, kedisiplinan, dan kerja sama.', 'sort_order' => 1],
            ['id' => 3, 'profile_id' => 2, 'institution' => 'Universitas Bale Bandung', 'major' => 'Teknologi Informasi', 'period' => 'Karier Akademik', 'description' => 'Berfokus pada pengajaran, pengembangan perangkat lunak, dan inovasi digital.', 'sort_order' => 0],
        ],
        'experiences' => [
            ['id' => 1, 'profile_id' => 1, 'title' => 'Aplikasi CV Multi-User', 'organization' => 'UAS Pemrograman Internet', 'period' => '2026', 'description' => 'Mengembangkan aplikasi CV menggunakan PHP, autentikasi berbasis sesi, hak akses admin dan user, URL profil unik, serta basis data relasional.', 'sort_order' => 0],
            ['id' => 2, 'profile_id' => 1, 'title' => 'Kegiatan Akademik dan Organisasi', 'organization' => 'Universitas Bale Bandung', 'period' => '2024 - Sekarang', 'description' => 'Mengikuti kegiatan akademik dan nonakademik untuk melatih komunikasi, tanggung jawab, pengelolaan waktu, dan kerja tim.', 'sort_order' => 1],
            ['id' => 3, 'profile_id' => 2, 'title' => 'Pengajaran Pemrograman Internet', 'organization' => 'Universitas Bale Bandung', 'period' => 'Sekarang', 'description' => 'Membimbing mahasiswa memahami konsep front end, back end, basis data, dan pengembangan aplikasi web.', 'sort_order' => 0],
        ],
        'skills' => [
            ['id' => 1, 'profile_id' => 1, 'skill_name' => 'HTML dan CSS', 'skill_level' => 88, 'sort_order' => 0],
            ['id' => 2, 'profile_id' => 1, 'skill_name' => 'JavaScript', 'skill_level' => 74, 'sort_order' => 1],
            ['id' => 3, 'profile_id' => 1, 'skill_name' => 'PHP', 'skill_level' => 80, 'sort_order' => 2],
            ['id' => 4, 'profile_id' => 1, 'skill_name' => 'Basis Data', 'skill_level' => 79, 'sort_order' => 3],
            ['id' => 5, 'profile_id' => 1, 'skill_name' => 'Git dan GitHub', 'skill_level' => 76, 'sort_order' => 4],
            ['id' => 6, 'profile_id' => 2, 'skill_name' => 'Software Engineering', 'skill_level' => 92, 'sort_order' => 0],
            ['id' => 7, 'profile_id' => 2, 'skill_name' => 'Pemrograman Web', 'skill_level' => 90, 'sort_order' => 1],
        ],
        'languages' => [
            ['id' => 1, 'profile_id' => 1, 'language_name' => 'Bahasa Indonesia', 'proficiency' => 'Aktif', 'sort_order' => 0],
            ['id' => 2, 'profile_id' => 1, 'language_name' => 'Bahasa Inggris', 'proficiency' => 'Dasar', 'sort_order' => 1],
            ['id' => 3, 'profile_id' => 2, 'language_name' => 'Bahasa Indonesia', 'proficiency' => 'Aktif', 'sort_order' => 0],
            ['id' => 4, 'profile_id' => 2, 'language_name' => 'Bahasa Inggris', 'proficiency' => 'Profesional', 'sort_order' => 1],
        ],
        'portfolios' => [
            ['id' => 1, 'profile_id' => 1, 'title' => 'Curriculum Vitae Multi-User', 'technology' => 'PHP, PDO, SQLite/MySQL', 'description' => 'Aplikasi CV responsif dengan halaman publik, login user, dashboard editor, panel admin, serta pilihan CV default.', 'project_url' => '', 'sort_order' => 0],
            ['id' => 2, 'profile_id' => 1, 'title' => 'Latihan Desain Antarmuka', 'technology' => 'HTML, CSS, JavaScript', 'description' => 'Kumpulan latihan halaman web dengan fokus pada konsistensi layout, hierarki informasi, dan responsivitas.', 'project_url' => '', 'sort_order' => 1],
            ['id' => 3, 'profile_id' => 2, 'title' => 'Materi Pemrograman Internet', 'technology' => 'Web Development', 'description' => 'Materi pembelajaran mengenai aplikasi web dinamis, autentikasi, basis data, dan pengelolaan pengguna.', 'project_url' => '', 'sort_order' => 0],
        ],
        'settings' => ['default_user_id' => 2],
        'sequences' => [
            'users' => 4, 'profiles' => 3, 'education' => 4, 'experiences' => 4,
            'skills' => 8, 'languages' => 5, 'portfolios' => 4,
        ],
    ];
}

function json_load_data(): array
{
    $path = app_settings()['json_path'];
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder penyimpanan JSON tidak dapat dibuat.');
    }
    if (!is_file($path)) {
        json_save_data(initial_data());
    }

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new RuntimeException('Penyimpanan JSON tidak dapat dibuka.');
    }
    flock($handle, LOCK_SH);
    $json = stream_get_contents($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    $data = json_decode((string) $json, true);
    if (!is_array($data)) {
        $data = initial_data();
        json_save_data($data);
    }
    return $data;
}

function json_save_data(array $data): void
{
    $path = app_settings()['json_path'];
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder penyimpanan JSON tidak dapat dibuat.');
    }
    $temporary = $path . '.tmp';
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded) || file_put_contents($temporary, $encoded, LOCK_EX) === false) {
        throw new RuntimeException('Data JSON tidak dapat disimpan.');
    }
    if (!rename($temporary, $path)) {
        @unlink($temporary);
        throw new RuntimeException('Data JSON tidak dapat diperbarui.');
    }
}

function json_transaction(callable $callback): mixed
{
    $data = json_load_data();
    $result = $callback($data);
    json_save_data($data);
    return $result;
}

function database_label(): string
{
    $requested = app_settings()['requested_driver'];
    return $requested === 'mysql' ? 'MySQL' : 'SQLite';
}
