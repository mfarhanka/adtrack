<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$action = $_GET['action'] ?? 'dashboard';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$flash = get_flash();

if ($action === 'login' && $method === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (attempt_login($username, $password)) {
        set_flash('success', 'Welcome back.');
        redirect('dashboard');
    }

    set_flash('danger', 'Invalid username or password.');
    redirect('login');
}

if ($action === 'logout') {
    logout_user();
    set_flash('success', 'You have been logged out.');
    redirect('login');
}

if (!is_logged_in() && $action !== 'login') {
    redirect('login');
}

if ($action === 'users' && $method === 'POST') {
    require_admin();

    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $username === '' || $password === '') {
        set_flash('danger', 'Name, username, and password are required.');
        redirect('users');
    }

    if (find_user_by_username($username) !== null) {
        set_flash('danger', 'That username is already taken.');
        redirect('users');
    }

    create_user($name, $username, $password, 'user');
    set_flash('success', 'User created successfully.');
    redirect('users');
}

if ($action === 'delete-user' && $method === 'POST') {
    require_admin();

    $userId = $_POST['user_id'] ?? '';

    if ($userId === current_user()['id']) {
        set_flash('danger', 'You cannot delete your own account.');
        redirect('users');
    }

    delete_user($userId);
    set_flash('success', 'User deleted.');
    redirect('users');
}

if ($action === 'ads' && $method === 'POST') {
    require_login();

    $title = trim($_POST['title'] ?? '');
    $platform = trim($_POST['platform'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $lastAdvertisedAt = $_POST['last_advertised_at'] ?? '';
    $repeatEveryDays = (int) ($_POST['repeat_every_days'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $adId = $_POST['ad_id'] ?? '';

    if ($title === '' || $platform === '' || $details === '' || $lastAdvertisedAt === '' || $repeatEveryDays < 1) {
        set_flash('danger', 'Title, platform, details, advertised date, and repeat schedule are required.');
        redirect('ads');
    }

    $payload = [
        'title' => $title,
        'platform' => $platform,
        'details' => $details,
        'last_advertised_at' => $lastAdvertisedAt,
        'repeat_every_days' => $repeatEveryDays,
        'notes' => $notes,
    ];

    if ($adId !== '') {
        update_ad($adId, $payload, current_user());
        set_flash('success', 'Ad updated.');
    } else {
        create_ad($payload, current_user());
        set_flash('success', 'Ad created.');
    }

    redirect('ads');
}

if ($action === 'mark-advertised' && $method === 'POST') {
    require_login();
    mark_ad_as_advertised($_POST['ad_id'] ?? '', current_user());
    set_flash('success', 'Advertised date updated to today.');
    redirect('dashboard');
}

if ($action === 'delete-ad' && $method === 'POST') {
    require_login();
    delete_ad($_POST['ad_id'] ?? '', current_user());
    set_flash('success', 'Ad deleted.');
    redirect('ads');
}

$pageTitle = 'AdTrack';
$currentUser = current_user();
$users = [];
$ads = [];
$editingAd = null;

if ($action === 'users') {
    require_admin();
    $pageTitle = 'User Management';
    $users = get_users();
}

if ($action === 'ads') {
    require_login();
    $pageTitle = 'Manage Ads';
    $ads = get_ads_for_user($currentUser);
    $editId = $_GET['edit'] ?? '';
    $editingAd = $editId !== '' ? find_ad_for_user($editId, $currentUser) : null;
}

if ($action === 'dashboard') {
    require_login();
    $pageTitle = 'Dashboard';
    $ads = hydrate_ads(get_ads_for_user($currentUser));
}

include __DIR__ . '/partials/header.php';

if ($action === 'login') {
    include __DIR__ . '/views/login.php';
} elseif ($action === 'users') {
    include __DIR__ . '/views/users.php';
} elseif ($action === 'ads') {
    include __DIR__ . '/views/ads.php';
} else {
    include __DIR__ . '/views/dashboard.php';
}

include __DIR__ . '/partials/footer.php';