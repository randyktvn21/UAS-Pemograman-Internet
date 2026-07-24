CREATE DATABASE IF NOT EXISTS cv_multiuser CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cv_multiuser;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS portfolios;
DROP TABLE IF EXISTS languages;
DROP TABLE IF EXISTS skills;
DROP TABLE IF EXISTS experiences;
DROP TABLE IF EXISTS education;
DROP TABLE IF EXISTS profiles;
DROP TABLE IF EXISTS system_settings;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
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

CREATE TABLE profiles (
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

CREATE TABLE education (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    institution VARCHAR(190) NOT NULL,
    major VARCHAR(190) NOT NULL,
    period VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_education_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE experiences (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    organization VARCHAR(190) NOT NULL,
    period VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_experiences_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE skills (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    skill_name VARCHAR(120) NOT NULL,
    skill_level TINYINT UNSIGNED NOT NULL DEFAULT 50,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_skills_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE languages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    language_name VARCHAR(120) NOT NULL,
    proficiency VARCHAR(100) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_languages_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE portfolios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    technology VARCHAR(190) NOT NULL,
    description TEXT NOT NULL,
    project_url VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_portfolios_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (id, username, email, password_hash, role, is_active, created_at, updated_at) VALUES
(1, 'admin', 'admin@cv.local', '$2y$12$66/U4CNkPQhttccXuL9VQuNClITKUiQbyFBY3hjCSB5lRCnH0XN4K', 'admin', 1, NOW(), NOW()),
(2, 'randy', 'randyktvn2323@gmail.com', '$2y$12$VfP96Ts2C3Rh1r5wRvsoY.Bwvs3vFBCAWuJ7PouSFRkvDWrYt1Iry', 'user', 1, NOW(), NOW()),
(3, 'cecep_suwanda', 'cecep@example.com', '$2y$12$ON7/JYhNY4E.xytgJ/RLQOfS1ayfaJUFopxBcmoU58I3EzdXArcjG', 'user', 1, NOW(), NOW());

INSERT INTO profiles (id, user_id, full_name, profession, tagline, about, email, phone, address, github, nim, program_study, student_status, cohort, photo_path, updated_at) VALUES
(1, 2, 'Randy Oktaviana Hertland', 'Mahasiswa Teknik Informatika', 'Web Development | Interface Design | Problem Solving', 'Saya adalah mahasiswa Teknik Informatika yang tertarik pada pengembangan website dan desain antarmuka. Melalui proyek ini saya mengembangkan aplikasi CV yang memiliki front end, back end, autentikasi, dan dukungan multi-user.', 'randyktvn2323@gmail.com', '08975645637', 'Bandung, Indonesia', 'https://github.com/username/project_cv', '301230053', 'Teknik Informatika', 'Mahasiswa Aktif', '2023', 'assets/img/pict.jpeg', NOW()),
(2, 3, 'Cecep Suwanda', 'Dosen dan Praktisi Teknologi Informasi', 'Teaching | Software Engineering | Digital Innovation', 'Profil contoh untuk menunjukkan bahwa setiap pengguna mempunyai halaman CV publik yang berbeda dan dapat diperbarui melalui dashboard masing-masing.', 'cecep@example.com', '0812-0000-0000', 'Bandung, Jawa Barat', 'https://github.com/username', '-', 'Teknik Informatika', 'Dosen', '-', 'assets/img/avatar-default.svg', NOW());

INSERT INTO education (profile_id, institution, major, period, description, sort_order) VALUES
(1, 'Universitas Bale Bandung', 'S1 Teknik Informatika', '2023 - Sekarang', 'Mempelajari pemrograman, basis data, pengembangan web, jaringan komputer, serta perancangan antarmuka aplikasi.', 0),
(1, 'SMA Pasundan Banjaran', 'Matematika dan Ilmu Pengetahuan Alam', '2018 - 2021', 'Mengembangkan dasar kemampuan akademik, komunikasi, kedisiplinan, dan kerja sama.', 1),
(2, 'Universitas Bale Bandung', 'Teknologi Informasi', 'Karier Akademik', 'Berfokus pada pengajaran, pengembangan perangkat lunak, dan inovasi digital.', 0);

INSERT INTO experiences (profile_id, title, organization, period, description, sort_order) VALUES
(1, 'Aplikasi CV Multi-User', 'UAS Pemrograman Internet', '2026', 'Mengembangkan aplikasi CV menggunakan PHP, autentikasi berbasis sesi, hak akses admin dan user, URL profil unik, serta basis data relasional.', 0),
(1, 'Kegiatan Akademik dan Organisasi', 'Universitas Bale Bandung', '2024 - Sekarang', 'Melatih komunikasi, tanggung jawab, pengelolaan waktu, dan kerja tim.', 1),
(2, 'Pengajaran Pemrograman Internet', 'Universitas Bale Bandung', 'Sekarang', 'Membimbing mahasiswa memahami konsep front end, back end, basis data, dan aplikasi web.', 0);

INSERT INTO skills (profile_id, skill_name, skill_level, sort_order) VALUES
(1, 'HTML dan CSS', 88, 0), (1, 'JavaScript', 74, 1), (1, 'PHP', 80, 2), (1, 'Basis Data', 79, 3), (1, 'Git dan GitHub', 76, 4),
(2, 'Software Engineering', 92, 0), (2, 'Pemrograman Web', 90, 1);

INSERT INTO languages (profile_id, language_name, proficiency, sort_order) VALUES
(1, 'Bahasa Indonesia', 'Aktif', 0), (1, 'Bahasa Inggris', 'Dasar', 1),
(2, 'Bahasa Indonesia', 'Aktif', 0), (2, 'Bahasa Inggris', 'Profesional', 1);

INSERT INTO portfolios (profile_id, title, technology, description, project_url, sort_order) VALUES
(1, 'Curriculum Vitae Multi-User', 'PHP, PDO, SQLite/MySQL', 'Aplikasi CV responsif dengan halaman publik, login user, dashboard editor, panel admin, serta pilihan CV default.', '', 0),
(1, 'Latihan Desain Antarmuka', 'HTML, CSS, JavaScript', 'Kumpulan latihan halaman web dengan fokus pada konsistensi layout, hierarki informasi, dan responsivitas.', '', 1),
(2, 'Materi Pemrograman Internet', 'Web Development', 'Materi pembelajaran mengenai aplikasi web dinamis, autentikasi, basis data, dan pengelolaan pengguna.', '', 0);

INSERT INTO system_settings (setting_key, setting_value) VALUES ('default_user_id', '2');
