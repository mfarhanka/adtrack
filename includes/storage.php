<?php

declare(strict_types=1);

function initialize_storage(): void
{
    $dataDir = app_path('data');

    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
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
    return read_json('ads');
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
    $users = array_filter(get_users(), static fn(array $user): bool => $user['id'] !== $userId);
    $ads = array_filter(get_ads(), static fn(array $ad): bool => $ad['user_id'] !== $userId);

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
            return false;
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