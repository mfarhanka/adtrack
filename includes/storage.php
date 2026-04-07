<?php

declare(strict_types=1);

function initialize_storage(): void
{
    $dataDir = app_path('data');
    $uploadDir = ad_upload_directory();

    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (!file_exists(storage_file('users'))) {
        write_json('users', []);
    }

    if (!file_exists(storage_file('ads'))) {
        write_json('ads', []);
    }

    if (get_users() === []) {
        $defaultAdmin = [[
            'id' => uniqid('user_', true),
            'name' => 'Administrator',
            'username' => 'admin',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'created_at' => date(DATE_ATOM),
        ]];

        write_json('users', $defaultAdmin);
    }
}

function read_json(string $name): array
{
    $content = file_get_contents(storage_file($name));

    if ($content === false || $content === '') {
        return [];
    }

    $decoded = json_decode($content, true);

    return is_array($decoded) ? $decoded : [];
}

function write_json(string $name, array $data): void
{
    file_put_contents(
        storage_file($name),
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
}

function get_users(): array
{
    return read_json('users');
}

function save_users(array $users): void
{
    write_json('users', array_values($users));
}

function get_ads(): array
{
    return array_map('normalize_ad_record', read_json('ads'));
}

function save_ads(array $ads): void
{
    write_json('ads', array_values($ads));
}

function create_user(string $name, string $username, string $password, string $role): void
{
    $users = get_users();
    $users[] = [
        'id' => uniqid('user_', true),
        'name' => $name,
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'created_at' => date(DATE_ATOM),
    ];

    save_users($users);
}

function delete_user(string $userId): void
{
    $removedAds = array_values(array_filter(get_ads(), static fn(array $ad): bool => $ad['user_id'] === $userId));
    $users = array_filter(get_users(), static fn(array $user): bool => $user['id'] !== $userId);
    $ads = array_filter(get_ads(), static fn(array $ad): bool => $ad['user_id'] !== $userId);

    foreach ($removedAds as $ad) {
        delete_ad_photo_files($ad['photos'] ?? []);
    }

    save_users($users);
    save_ads($ads);
}

function create_ad(array $payload, array $currentUser): void
{
    $ads = get_ads();
    $ads[] = [
        'id' => uniqid('ad_', true),
        'user_id' => $currentUser['role'] === 'admin' ? ($payload['user_id'] ?? $currentUser['id']) : $currentUser['id'],
        'title' => $payload['title'],
        'platform' => $payload['platform'],
        'details' => $payload['details'],
        'last_advertised_at' => $payload['last_advertised_at'],
        'repeat_every_days' => (int) $payload['repeat_every_days'],
        'notes' => $payload['notes'],
        'photos' => $payload['photos'] ?? [],
        'created_at' => date(DATE_ATOM),
    ];

    save_ads($ads);
}

function update_ad(string $adId, array $payload, array $currentUser): void
{
    $ads = get_ads();

    foreach ($ads as &$ad) {
        if ($ad['id'] !== $adId) {
            continue;
        }

        if ($currentUser['role'] !== 'admin' && $ad['user_id'] !== $currentUser['id']) {
            return;
        }

        $ad['title'] = $payload['title'];
        $ad['platform'] = $payload['platform'];
        $ad['details'] = $payload['details'];
        $ad['last_advertised_at'] = $payload['last_advertised_at'];
        $ad['repeat_every_days'] = (int) $payload['repeat_every_days'];
        $ad['notes'] = $payload['notes'];
        $ad['photos'] = array_merge($ad['photos'] ?? [], $payload['photos'] ?? []);
        break;
    }

    unset($ad);

    save_ads($ads);
}

function mark_ad_as_advertised(string $adId, array $currentUser): void
{
    $ads = get_ads();

    foreach ($ads as &$ad) {
        if ($ad['id'] !== $adId) {
            continue;
        }

        if ($currentUser['role'] !== 'admin' && $ad['user_id'] !== $currentUser['id']) {
            return;
        }

        $ad['last_advertised_at'] = now_date();
        break;
    }

    unset($ad);

    save_ads($ads);
}

function delete_ad(string $adId, array $currentUser): void
{
    $ads = array_filter(get_ads(), static function (array $ad) use ($adId, $currentUser): bool {
        if ($ad['id'] !== $adId) {
            return true;
        }

        if ($currentUser['role'] === 'admin') {
            delete_ad_photo_files($ad['photos'] ?? []);
            return false;
        }

        if ($ad['user_id'] === $currentUser['id']) {
            delete_ad_photo_files($ad['photos'] ?? []);
        }

        return $ad['user_id'] !== $currentUser['id'];
    });

    save_ads($ads);
}

function get_ads_for_user(?array $currentUser): array
{
    if ($currentUser === null) {
        return [];
    }

    $ads = get_ads();

    if ($currentUser['role'] === 'admin') {
        return $ads;
    }

    return array_values(array_filter(
        $ads,
        static fn(array $ad): bool => $ad['user_id'] === $currentUser['id']
    ));
}

function find_ad_for_user(string $adId, array $currentUser): ?array
{
    foreach (get_ads_for_user($currentUser) as $ad) {
        if ($ad['id'] === $adId) {
            return $ad;
        }
    }

    return null;
}

function normalize_ad_record(array $ad): array
{
    $ad['photos'] = isset($ad['photos']) && is_array($ad['photos']) ? array_values($ad['photos']) : [];

    return $ad;
}

function store_uploaded_ad_photos(array $files): array
{
    if ($files === [] || !isset($files['name']) || !is_array($files['name'])) {
        return [];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $storedPhotos = [];

    foreach ($files['name'] as $index => $originalName) {
        $error = $files['error'][$index] ?? UPLOAD_ERR_NO_FILE;

        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($error !== UPLOAD_ERR_OK) {
            delete_ad_photo_files($storedPhotos);
            throw new RuntimeException('Photo upload failed. Please try again.');
        }

        $tmpName = $files['tmp_name'][$index] ?? '';
        $size = (int) ($files['size'][$index] ?? 0);

        if ($size > 5 * 1024 * 1024) {
            delete_ad_photo_files($storedPhotos);
            throw new RuntimeException('Each photo must be 5MB or smaller.');
        }

        $mimeType = $finfo->file($tmpName);
        $extension = $allowedMimeTypes[$mimeType] ?? null;

        if ($extension === null) {
            delete_ad_photo_files($storedPhotos);
            throw new RuntimeException('Only JPG, PNG, WEBP, and GIF images are allowed.');
        }

        $filename = uniqid('photo_', true) . '.' . $extension;
        $destination = ad_upload_directory() . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($tmpName, $destination)) {
            delete_ad_photo_files($storedPhotos);
            throw new RuntimeException('Unable to save uploaded photo.');
        }

        $storedPhotos[] = [
            'path' => ad_upload_relative_path($filename),
            'name' => trim((string) $originalName) !== '' ? trim((string) $originalName) : $filename,
            'uploaded_at' => date(DATE_ATOM),
        ];
    }

    return $storedPhotos;
}

function delete_ad_photo_files(array $photos): void
{
    foreach ($photos as $photo) {
        $photoPath = $photo['path'] ?? '';

        if (!is_string($photoPath) || $photoPath === '') {
            continue;
        }

        $fullPath = app_path(str_replace('/', DIRECTORY_SEPARATOR, $photoPath));

        if (is_file($fullPath)) {
            unlink($fullPath);
        }
    }
}

function delete_ad_photo(string $adId, int $photoIndex, array $currentUser): bool
{
    $ads = get_ads();

    foreach ($ads as &$ad) {
        if ($ad['id'] !== $adId) {
            continue;
        }

        if ($currentUser['role'] !== 'admin' && $ad['user_id'] !== $currentUser['id']) {
            return false;
        }

        if (!isset($ad['photos'][$photoIndex])) {
            return false;
        }

        $photo = $ad['photos'][$photoIndex];
        unset($ad['photos'][$photoIndex]);
        $ad['photos'] = array_values($ad['photos']);
        delete_ad_photo_files([$photo]);
        save_ads($ads);

        return true;
    }

    unset($ad);

    return false;
}