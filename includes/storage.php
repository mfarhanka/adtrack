<?php

function initialize_storage()
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

function read_json($name)
{
    $content = file_get_contents(storage_file($name));

    if ($content === false || $content === '') {
        return [];
    }

    $decoded = json_decode($content, true);

    return is_array($decoded) ? $decoded : [];
}

function write_json($name, $data)
{
    file_put_contents(
        storage_file($name),
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
}

function get_users()
{
    return read_json('users');
}

function save_users($users)
{
    write_json('users', array_values($users));
}

function get_ads()
{
    return array_map('normalize_ad_record', read_json('ads'));
}

function save_ads($ads)
{
    write_json('ads', array_values($ads));
}

function create_user($name, $username, $password, $role)
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

function delete_user($userId)
{
    $removedAds = array_values(array_filter(get_ads(), static function ($ad) use ($userId) {
        return $ad['user_id'] === $userId;
    }));
    $users = array_filter(get_users(), static function ($user) use ($userId) {
        return $user['id'] !== $userId;
    });
    $ads = array_filter(get_ads(), static function ($ad) use ($userId) {
        return $ad['user_id'] !== $userId;
    });

    foreach ($removedAds as $ad) {
        delete_ad_photo_files(isset($ad['photos']) ? $ad['photos'] : []);
    }

    save_users($users);
    save_ads($ads);
}

function create_ad($payload, $currentUser)
{
    $ads = get_ads();
    $ads[] = [
        'id' => uniqid('ad_', true),
        'user_id' => $currentUser['role'] === 'admin' ? (isset($payload['user_id']) ? $payload['user_id'] : $currentUser['id']) : $currentUser['id'],
        'title' => $payload['title'],
        'platform' => $payload['platform'],
        'details' => $payload['details'],
        'last_advertised_at' => $payload['last_advertised_at'],
        'repeat_every_days' => (int) $payload['repeat_every_days'],
        'notes' => $payload['notes'],
        'photos' => isset($payload['photos']) ? $payload['photos'] : [],
        'created_at' => date(DATE_ATOM),
    ];

    save_ads($ads);
}

function update_ad($adId, $payload, $currentUser)
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
        $ad['photos'] = array_merge(
            isset($ad['photos']) ? $ad['photos'] : [],
            isset($payload['photos']) ? $payload['photos'] : []
        );
        break;
    }

    unset($ad);

    save_ads($ads);
}

function mark_ad_as_advertised($adId, $currentUser)
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

function delete_ad($adId, $currentUser)
{
    $ads = array_filter(get_ads(), static function ($ad) use ($adId, $currentUser) {
        if ($ad['id'] !== $adId) {
            return true;
        }

        if ($currentUser['role'] === 'admin') {
            delete_ad_photo_files(isset($ad['photos']) ? $ad['photos'] : []);
            return false;
        }

        if ($ad['user_id'] === $currentUser['id']) {
            delete_ad_photo_files(isset($ad['photos']) ? $ad['photos'] : []);
        }

        return $ad['user_id'] !== $currentUser['id'];
    });

    save_ads($ads);
}

function get_ads_for_user($currentUser)
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
        static function ($ad) use ($currentUser) {
            return $ad['user_id'] === $currentUser['id'];
        }
    ));
}

function find_ad_for_user($adId, $currentUser)
{
    foreach (get_ads_for_user($currentUser) as $ad) {
        if ($ad['id'] === $adId) {
            return $ad;
        }
    }

    return null;
}

function normalize_ad_record($ad)
{
    $ad['photos'] = isset($ad['photos']) && is_array($ad['photos']) ? array_values($ad['photos']) : [];

    return $ad;
}

function store_uploaded_ad_photos($files)
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
        $error = isset($files['error'][$index]) ? $files['error'][$index] : UPLOAD_ERR_NO_FILE;

        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($error !== UPLOAD_ERR_OK) {
            delete_ad_photo_files($storedPhotos);
            throw new RuntimeException('Photo upload failed. Please try again.');
        }

        $tmpName = isset($files['tmp_name'][$index]) ? $files['tmp_name'][$index] : '';
        $size = (int) (isset($files['size'][$index]) ? $files['size'][$index] : 0);

        if ($size > 5 * 1024 * 1024) {
            delete_ad_photo_files($storedPhotos);
            throw new RuntimeException('Each photo must be 5MB or smaller.');
        }

        $mimeType = $finfo->file($tmpName);
        $extension = isset($allowedMimeTypes[$mimeType]) ? $allowedMimeTypes[$mimeType] : null;

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

function delete_ad_photo_files($photos)
{
    foreach ($photos as $photo) {
        $photoPath = isset($photo['path']) ? $photo['path'] : '';

        if (!is_string($photoPath) || $photoPath === '') {
            continue;
        }

        $fullPath = app_path(str_replace('/', DIRECTORY_SEPARATOR, $photoPath));

        if (is_file($fullPath)) {
            unlink($fullPath);
        }
    }
}

function delete_ad_photo($adId, $photoIndex, $currentUser)
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

    unset($ad);

    return false;
}