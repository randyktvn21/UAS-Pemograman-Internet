<?php

declare(strict_types=1);

function find_user_by_id(int $userId): ?array
{
    if (database_mode() === 'json') {
        foreach (json_load_data()['users'] as $user) {
            if ((int) $user['id'] === $userId) {
                return $user;
            }
        }
        return null;
    }

    $statement = database()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $userId]);
    $user = $statement->fetch();
    return is_array($user) ? $user : null;
}

function find_user_by_username(string $username): ?array
{
    $username = strtolower($username);
    if (database_mode() === 'json') {
        foreach (json_load_data()['users'] as $user) {
            if (strtolower((string) $user['username']) === $username) {
                return $user;
            }
        }
        return null;
    }

    $statement = database()->prepare('SELECT * FROM users WHERE LOWER(username) = :username LIMIT 1');
    $statement->execute(['username' => $username]);
    $user = $statement->fetch();
    return is_array($user) ? $user : null;
}

function all_users(bool $includeAdmin = true): array
{
    if (database_mode() === 'json') {
        $data = json_load_data();
        $profiles = [];
        foreach ($data['profiles'] as $profile) {
            $profiles[(int) $profile['user_id']] = $profile;
        }
        $users = [];
        foreach ($data['users'] as $user) {
            if (!$includeAdmin && $user['role'] === 'admin') {
                continue;
            }
            $profile = $profiles[(int) $user['id']] ?? [];
            $users[] = array_merge($user, [
                'full_name' => $profile['full_name'] ?? $user['username'],
                'profession' => $profile['profession'] ?? '',
                'profile_updated_at' => $profile['updated_at'] ?? null,
            ]);
        }
        usort($users, static function (array $a, array $b): int {
            if ($a['role'] !== $b['role']) {
                return $a['role'] === 'admin' ? -1 : 1;
            }
            return strcmp((string) $a['username'], (string) $b['username']);
        });
        return $users;
    }

    $sql = 'SELECT u.*, p.full_name, p.profession, p.updated_at AS profile_updated_at
            FROM users u LEFT JOIN profiles p ON p.user_id = u.id';
    if (!$includeAdmin) {
        $sql .= " WHERE u.role = 'user'";
    }
    $sql .= " ORDER BY CASE WHEN u.role = 'admin' THEN 0 ELSE 1 END, u.username";
    return database()->query($sql)->fetchAll();
}

function default_user_id(): ?int
{
    if (database_mode() === 'json') {
        $value = json_load_data()['settings']['default_user_id'] ?? null;
        return $value === null ? null : (int) $value;
    }
    $statement = database()->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'default_user_id' LIMIT 1");
    $statement->execute();
    $value = $statement->fetchColumn();
    return $value === false ? null : (int) $value;
}

function default_user(): ?array
{
    $id = default_user_id();
    return $id ? find_user_by_id($id) : null;
}

function set_default_user(int $userId): array
{
    $user = find_user_by_id($userId);
    if (!$user || $user['role'] !== 'user' || (int) $user['is_active'] !== 1) {
        return ['success' => false, 'message' => 'CV default hanya dapat dipilih dari user yang aktif.'];
    }

    if (database_mode() === 'json') {
        json_transaction(static function (array &$data) use ($userId): void {
            $data['settings']['default_user_id'] = $userId;
        });
    } else {
        $pdo = database();
        if (database_mode() === 'mysql') {
            $sql = "INSERT INTO system_settings (setting_key, setting_value) VALUES ('default_user_id', :value)
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        } else {
            $sql = "INSERT INTO system_settings (setting_key, setting_value) VALUES ('default_user_id', :value)
                    ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value";
        }
        $pdo->prepare($sql)->execute(['value' => (string) $userId]);
    }
    return ['success' => true, 'message' => 'CV default berhasil diubah ke @' . $user['username'] . '.'];
}

function load_cv_by_user_id(int $userId, bool $allowInactive = false): ?array
{
    $user = find_user_by_id($userId);
    if (!$user || (!$allowInactive && (int) $user['is_active'] !== 1) || $user['role'] !== 'user') {
        return null;
    }

    if (database_mode() === 'json') {
        $data = json_load_data();
        $profile = null;
        foreach ($data['profiles'] as $candidate) {
            if ((int) $candidate['user_id'] === $userId) {
                $profile = $candidate;
                break;
            }
        }
        if (!$profile) {
            return null;
        }
        $profile['user'] = $user;
        $profile['username'] = $user['username'];
        $profile['is_default'] = default_user_id() === $userId;
        foreach (['education', 'experiences', 'skills', 'languages', 'portfolios'] as $table) {
            $rows = array_values(array_filter(
                $data[$table],
                static fn(array $row): bool => (int) $row['profile_id'] === (int) $profile['id']
            ));
            usort($rows, static fn(array $a, array $b): int => ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0)));
            $profile[$table] = $rows;
        }
        return $profile;
    }

    $statement = database()->prepare('SELECT p.*, u.username, u.role, u.is_active, u.email AS account_email
                                      FROM profiles p JOIN users u ON u.id = p.user_id
                                      WHERE p.user_id = :user_id LIMIT 1');
    $statement->execute(['user_id' => $userId]);
    $profile = $statement->fetch();
    if (!$profile) {
        return null;
    }
    $profile['user'] = $user;
    $profile['is_default'] = default_user_id() === $userId;
    foreach (['education', 'experiences', 'skills', 'languages', 'portfolios'] as $table) {
        $profile[$table] = load_profile_children(database(), $table, (int) $profile['id']);
    }
    return $profile;
}

function load_public_cv(?string $username = null): ?array
{
    $user = $username === null || $username === '' ? default_user() : find_user_by_username($username);
    if (!$user) {
        return null;
    }
    return load_cv_by_user_id((int) $user['id'], false);
}

function load_profile_children(PDO $pdo, string $table, int $profileId): array
{
    $allowed = ['education', 'experiences', 'skills', 'languages', 'portfolios'];
    if (!in_array($table, $allowed, true)) {
        return [];
    }
    $statement = $pdo->prepare("SELECT * FROM {$table} WHERE profile_id = :profile_id ORDER BY sort_order, id");
    $statement->execute(['profile_id' => $profileId]);
    return $statement->fetchAll();
}

function save_cv(int $userId, array $input, array $files): array
{
    $current = load_cv_by_user_id($userId, true);
    if (!$current) {
        return ['success' => false, 'errors' => ['Profil pengguna tidak ditemukan.']];
    }

    $profile = [
        'full_name' => clean_line($input['full_name'] ?? '', 150),
        'profession' => clean_line($input['profession'] ?? '', 150),
        'tagline' => clean_line($input['tagline'] ?? '', 190),
        'about' => clean_paragraph($input['about'] ?? ''),
        'email' => clean_line($input['email'] ?? '', 190),
        'phone' => clean_line($input['phone'] ?? '', 50),
        'address' => clean_line($input['address'] ?? '', 190),
        'github' => clean_line($input['github'] ?? '', 255),
        'nim' => clean_line($input['nim'] ?? '', 50),
        'program_study' => clean_line($input['program_study'] ?? '', 150),
        'student_status' => clean_line($input['student_status'] ?? '', 100),
        'cohort' => clean_line($input['cohort'] ?? '', 20),
        'photo_path' => (string) $current['photo_path'],
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    $errors = [];
    foreach (['full_name' => 'Nama lengkap', 'profession' => 'Profesi', 'about' => 'Tentang saya', 'email' => 'Email'] as $field => $label) {
        if ($profile[$field] === '') {
            $errors[] = $label . ' wajib diisi.';
        }
    }
    if ($profile['email'] !== '' && !filter_var($profile['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email belum benar.';
    }
    try {
        $profile['photo_path'] = upload_photo($files['photo'] ?? [], $profile['photo_path'], $userId);
    } catch (RuntimeException $exception) {
        $errors[] = $exception->getMessage();
    }

    $children = [
        'education' => rows_from_post($input, 'education', ['institution', 'major', 'period', 'description']),
        'experiences' => rows_from_post($input, 'experiences', ['title', 'organization', 'period', 'description']),
        'skills' => skill_rows_from_post($input),
        'languages' => rows_from_post($input, 'languages', ['language_name', 'proficiency']),
        'portfolios' => rows_from_post($input, 'portfolios', ['title', 'technology', 'description', 'project_url']),
    ];
    if ($children['education'] === []) {
        $errors[] = 'Minimal satu riwayat pendidikan harus diisi.';
    }
    if ($children['skills'] === []) {
        $errors[] = 'Minimal satu keahlian harus diisi.';
    }
    if ($errors !== []) {
        return ['success' => false, 'errors' => $errors];
    }

    if (database_mode() === 'json') {
        json_transaction(static function (array &$data) use ($current, $profile, $children): void {
            foreach ($data['profiles'] as &$row) {
                if ((int) $row['id'] === (int) $current['id']) {
                    $row = array_merge($row, $profile);
                    break;
                }
            }
            unset($row);
            foreach ($children as $table => $rows) {
                $data[$table] = array_values(array_filter(
                    $data[$table],
                    static fn(array $row): bool => (int) $row['profile_id'] !== (int) $current['id']
                ));
                foreach ($rows as $sortOrder => $row) {
                    $row['id'] = json_next_id($data, $table);
                    $row['profile_id'] = (int) $current['id'];
                    $row['sort_order'] = $sortOrder;
                    $data[$table][] = $row;
                }
            }
        });
        return ['success' => true, 'errors' => []];
    }

    $pdo = database();
    $pdo->beginTransaction();
    try {
        $profile['id'] = (int) $current['id'];
        $statement = $pdo->prepare(<<<'SQL'
UPDATE profiles SET full_name = :full_name, profession = :profession, tagline = :tagline,
    about = :about, email = :email, phone = :phone, address = :address, github = :github,
    nim = :nim, program_study = :program_study, student_status = :student_status,
    cohort = :cohort, photo_path = :photo_path, updated_at = :updated_at
WHERE id = :id
SQL);
        $statement->execute($profile);
        foreach ($children as $table => $rows) {
            replace_profile_rows($pdo, $table, (int) $current['id'], $rows);
        }
        $pdo->commit();
        return ['success' => true, 'errors' => []];
    } catch (Throwable $exception) {
        $pdo->rollBack();
        return ['success' => false, 'errors' => ['Data gagal disimpan: ' . $exception->getMessage()]];
    }
}

function replace_profile_rows(PDO $pdo, string $table, int $profileId, array $rows): void
{
    $allowed = ['education', 'experiences', 'skills', 'languages', 'portfolios'];
    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Tabel tidak diizinkan.');
    }
    $pdo->prepare("DELETE FROM {$table} WHERE profile_id = :profile_id")->execute(['profile_id' => $profileId]);
    foreach ($rows as $sortOrder => $row) {
        $row['profile_id'] = $profileId;
        $row['sort_order'] = $sortOrder;
        $columns = array_keys($row);
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, implode(', ', $columns), implode(', ', array_map(static fn(string $column): string => ':' . $column, $columns)));
        $pdo->prepare($sql)->execute($row);
    }
}

function create_user(array $input): array
{
    $username = normalize_username($input['username'] ?? '');
    $email = clean_line($input['email'] ?? '', 190);
    $fullName = clean_line($input['full_name'] ?? '', 150);
    $password = (string) ($input['password'] ?? '');
    $errors = [];
    if (strlen($username) < 3) {
        $errors[] = 'Username minimal 3 karakter dan hanya boleh berisi huruf kecil, angka, garis bawah, atau tanda hubung.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email akun belum valid.';
    }
    if ($fullName === '') {
        $errors[] = 'Nama lengkap wajib diisi.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }
    if (find_user_by_username($username)) {
        $errors[] = 'Username sudah digunakan.';
    }
    foreach (all_users() as $user) {
        if (strtolower((string) $user['email']) === strtolower($email)) {
            $errors[] = 'Email akun sudah digunakan.';
            break;
        }
    }
    if ($errors !== []) {
        return ['success' => false, 'errors' => $errors];
    }

    $now = date('Y-m-d H:i:s');
    $hash = password_hash($password, PASSWORD_DEFAULT);
    if (database_mode() === 'json') {
        $userId = json_transaction(static function (array &$data) use ($username, $email, $fullName, $hash, $now): int {
            $userId = json_next_id($data, 'users');
            $profileId = json_next_id($data, 'profiles');
            $data['users'][] = ['id' => $userId, 'username' => $username, 'email' => $email, 'password_hash' => $hash, 'role' => 'user', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now, 'last_login_at' => null];
            $data['profiles'][] = ['id' => $profileId, 'user_id' => $userId, 'full_name' => $fullName, 'profession' => 'Mahasiswa Teknik Informatika', 'tagline' => 'Web Development | Creative Technology', 'about' => 'Tuliskan deskripsi singkat mengenai diri, minat, dan tujuan Anda melalui dashboard editor.', 'email' => $email, 'phone' => '-', 'address' => 'Bandung, Indonesia', 'github' => '', 'nim' => '', 'program_study' => 'Teknik Informatika', 'student_status' => 'Mahasiswa Aktif', 'cohort' => '', 'photo_path' => 'assets/img/avatar-default.svg', 'updated_at' => $now];
            $data['education'][] = ['id' => json_next_id($data, 'education'), 'profile_id' => $profileId, 'institution' => 'Universitas Bale Bandung', 'major' => 'S1 Teknik Informatika', 'period' => 'Sekarang', 'description' => 'Silakan ubah data pendidikan ini melalui dashboard.', 'sort_order' => 0];
            $data['skills'][] = ['id' => json_next_id($data, 'skills'), 'profile_id' => $profileId, 'skill_name' => 'Pemrograman Web', 'skill_level' => 60, 'sort_order' => 0];
            return $userId;
        });
        return ['success' => true, 'errors' => [], 'user_id' => $userId];
    }

    $pdo = database();
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, is_active, created_at, updated_at) VALUES (:username, :email, :password_hash, :role, 1, :created_at, :updated_at)');
        $statement->execute(['username' => $username, 'email' => $email, 'password_hash' => $hash, 'role' => 'user', 'created_at' => $now, 'updated_at' => $now]);
        $userId = (int) $pdo->lastInsertId();
        $profileStatement = $pdo->prepare('INSERT INTO profiles (user_id, full_name, profession, tagline, about, email, phone, address, github, nim, program_study, student_status, cohort, photo_path, updated_at) VALUES (:user_id, :full_name, :profession, :tagline, :about, :email, :phone, :address, :github, :nim, :program_study, :student_status, :cohort, :photo_path, :updated_at)');
        $profileStatement->execute(['user_id' => $userId, 'full_name' => $fullName, 'profession' => 'Mahasiswa Teknik Informatika', 'tagline' => 'Web Development | Creative Technology', 'about' => 'Tuliskan deskripsi singkat mengenai diri, minat, dan tujuan Anda melalui dashboard editor.', 'email' => $email, 'phone' => '-', 'address' => 'Bandung, Indonesia', 'github' => '', 'nim' => '', 'program_study' => 'Teknik Informatika', 'student_status' => 'Mahasiswa Aktif', 'cohort' => '', 'photo_path' => 'assets/img/avatar-default.svg', 'updated_at' => $now]);
        $profileId = (int) $pdo->lastInsertId();
        replace_profile_rows($pdo, 'education', $profileId, [['institution' => 'Universitas Bale Bandung', 'major' => 'S1 Teknik Informatika', 'period' => 'Sekarang', 'description' => 'Silakan ubah data pendidikan ini melalui dashboard.']]);
        replace_profile_rows($pdo, 'skills', $profileId, [['skill_name' => 'Pemrograman Web', 'skill_level' => 60]]);
        $pdo->commit();
        return ['success' => true, 'errors' => [], 'user_id' => $userId];
    } catch (Throwable $exception) {
        $pdo->rollBack();
        return ['success' => false, 'errors' => ['User gagal dibuat: ' . $exception->getMessage()]];
    }
}

function update_user_account(int $userId, array $input): array
{
    $user = find_user_by_id($userId);
    if (!$user) {
        return ['success' => false, 'errors' => ['User tidak ditemukan.']];
    }
    $username = normalize_username($input['username'] ?? $user['username']);
    $email = clean_line($input['email'] ?? $user['email'], 190);
    $fullName = clean_line($input['full_name'] ?? '', 150);
    $errors = [];
    if (strlen($username) < 3) {
        $errors[] = 'Username minimal 3 karakter.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email belum valid.';
    }
    if ($fullName === '') {
        $errors[] = 'Nama lengkap wajib diisi.';
    }
    foreach (all_users() as $candidate) {
        if ((int) $candidate['id'] === $userId) {
            continue;
        }
        if (strtolower((string) $candidate['username']) === $username) {
            $errors[] = 'Username sudah digunakan.';
        }
        if (strtolower((string) $candidate['email']) === strtolower($email)) {
            $errors[] = 'Email sudah digunakan.';
        }
    }
    if ($errors !== []) {
        return ['success' => false, 'errors' => array_values(array_unique($errors))];
    }

    $now = date('Y-m-d H:i:s');
    if (database_mode() === 'json') {
        json_transaction(static function (array &$data) use ($userId, $username, $email, $fullName, $now): void {
            foreach ($data['users'] as &$row) {
                if ((int) $row['id'] === $userId) {
                    $row['username'] = $username;
                    $row['email'] = $email;
                    $row['updated_at'] = $now;
                    break;
                }
            }
            unset($row);
            foreach ($data['profiles'] as &$profile) {
                if ((int) $profile['user_id'] === $userId) {
                    $profile['full_name'] = $fullName;
                    $profile['email'] = $email;
                    $profile['updated_at'] = $now;
                    break;
                }
            }
        });
    } else {
        $pdo = database();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE users SET username = :username, email = :email, updated_at = :updated_at WHERE id = :id')->execute(['username' => $username, 'email' => $email, 'updated_at' => $now, 'id' => $userId]);
            $pdo->prepare('UPDATE profiles SET full_name = :full_name, email = :email, updated_at = :updated_at WHERE user_id = :user_id')->execute(['full_name' => $fullName, 'email' => $email, 'updated_at' => $now, 'user_id' => $userId]);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            return ['success' => false, 'errors' => ['Data akun gagal diperbarui: ' . $exception->getMessage()]];
        }
    }
    return ['success' => true, 'errors' => []];
}

function set_user_active(int $userId, bool $active): array
{
    $user = find_user_by_id($userId);
    if (!$user || $user['role'] === 'admin') {
        return ['success' => false, 'message' => 'Status akun admin tidak dapat diubah dari menu ini.'];
    }
    if (!$active && default_user_id() === $userId) {
        return ['success' => false, 'message' => 'Pilih CV default lain sebelum menonaktifkan akun ini.'];
    }
    $now = date('Y-m-d H:i:s');
    if (database_mode() === 'json') {
        json_transaction(static function (array &$data) use ($userId, $active, $now): void {
            foreach ($data['users'] as &$row) {
                if ((int) $row['id'] === $userId) {
                    $row['is_active'] = $active ? 1 : 0;
                    $row['updated_at'] = $now;
                    break;
                }
            }
        });
    } else {
        database()->prepare('UPDATE users SET is_active = :active, updated_at = :updated_at WHERE id = :id')->execute(['active' => $active ? 1 : 0, 'updated_at' => $now, 'id' => $userId]);
    }
    return ['success' => true, 'message' => $active ? 'Akun berhasil diaktifkan.' : 'Akun berhasil dinonaktifkan.'];
}

function reset_user_password(int $userId, string $newPassword): array
{
    if (strlen($newPassword) < 6) {
        return ['success' => false, 'message' => 'Password baru minimal 6 karakter.'];
    }
    $user = find_user_by_id($userId);
    if (!$user) {
        return ['success' => false, 'message' => 'User tidak ditemukan.'];
    }
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $now = date('Y-m-d H:i:s');
    if (database_mode() === 'json') {
        json_transaction(static function (array &$data) use ($userId, $hash, $now): void {
            foreach ($data['users'] as &$row) {
                if ((int) $row['id'] === $userId) {
                    $row['password_hash'] = $hash;
                    $row['updated_at'] = $now;
                    break;
                }
            }
        });
    } else {
        database()->prepare('UPDATE users SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id')->execute(['password_hash' => $hash, 'updated_at' => $now, 'id' => $userId]);
    }
    return ['success' => true, 'message' => 'Password @' . $user['username'] . ' berhasil direset.'];
}

function change_own_password(int $userId, string $currentPassword, string $newPassword): array
{
    $user = find_user_by_id($userId);
    if (!$user || !password_verify($currentPassword, (string) $user['password_hash'])) {
        return ['success' => false, 'message' => 'Password saat ini tidak benar.'];
    }
    return reset_user_password($userId, $newPassword);
}

function delete_user_account(int $userId): array
{
    $user = find_user_by_id($userId);
    if (!$user || $user['role'] === 'admin') {
        return ['success' => false, 'message' => 'Akun admin tidak dapat dihapus.'];
    }
    if (default_user_id() === $userId) {
        return ['success' => false, 'message' => 'Pilih CV default lain sebelum menghapus akun ini.'];
    }

    if (database_mode() === 'json') {
        json_transaction(static function (array &$data) use ($userId): void {
            $profileIds = [];
            foreach ($data['profiles'] as $profile) {
                if ((int) $profile['user_id'] === $userId) {
                    $profileIds[] = (int) $profile['id'];
                }
            }
            $data['users'] = array_values(array_filter($data['users'], static fn(array $row): bool => (int) $row['id'] !== $userId));
            $data['profiles'] = array_values(array_filter($data['profiles'], static fn(array $row): bool => (int) $row['user_id'] !== $userId));
            foreach (['education', 'experiences', 'skills', 'languages', 'portfolios'] as $table) {
                $data[$table] = array_values(array_filter($data[$table], static fn(array $row): bool => !in_array((int) $row['profile_id'], $profileIds, true)));
            }
        });
    } else {
        database()->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $userId]);
    }
    return ['success' => true, 'message' => 'Akun @' . $user['username'] . ' berhasil dihapus.'];
}

function touch_last_login(int $userId): void
{
    $now = date('Y-m-d H:i:s');
    if (database_mode() === 'json') {
        json_transaction(static function (array &$data) use ($userId, $now): void {
            foreach ($data['users'] as &$row) {
                if ((int) $row['id'] === $userId) {
                    $row['last_login_at'] = $now;
                    break;
                }
            }
        });
    } else {
        database()->prepare('UPDATE users SET last_login_at = :last_login_at WHERE id = :id')->execute(['last_login_at' => $now, 'id' => $userId]);
    }
}

function admin_stats(): array
{
    $users = all_users();
    $active = 0;
    $regular = 0;
    foreach ($users as $user) {
        if ($user['role'] === 'user') {
            $regular++;
            if ((int) $user['is_active'] === 1) {
                $active++;
            }
        }
    }
    return ['total_users' => $regular, 'active_users' => $active, 'inactive_users' => $regular - $active, 'database' => database_label()];
}

function json_next_id(array &$data, string $table): int
{
    if (!isset($data['sequences'][$table])) {
        $maximum = 0;
        foreach ($data[$table] ?? [] as $row) {
            $maximum = max($maximum, (int) ($row['id'] ?? 0));
        }
        $data['sequences'][$table] = $maximum + 1;
    }
    $id = (int) $data['sequences'][$table];
    $data['sequences'][$table] = $id + 1;
    return $id;
}
