<?php

require __DIR__ . '/includes/bootstrap.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';
$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
$flash = get_flash();

if ($action === 'login' && $method === 'POST') {
    $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';

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

    $userId = trim(isset($_POST['user_id']) ? $_POST['user_id'] : '');
    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($name === '' || $username === '' || ($userId === '' && $password === '')) {
        set_flash('danger', $userId === '' ? 'Name, username, and password are required.' : 'Name and username are required.');
        redirect('users');
    }

    $existingUser = find_user_by_username($username);

    if ($existingUser !== null && $existingUser['id'] !== $userId) {
        set_flash('danger', 'That username is already taken.');
        redirect_to($userId !== '' ? 'action=users&edit=' . urlencode($userId) : 'action=users');
    }

    if ($userId !== '') {
        $user = find_user_by_id($userId);

        if ($user === null || $user['role'] === 'admin') {
            set_flash('danger', 'That user cannot be updated.');
            redirect('users');
        }

        update_user($userId, $name, $username, $password);
        set_flash('success', 'User updated successfully.');
        redirect('users');
    }

    create_user($name, $username, $password, 'user');
    set_flash('success', 'User created successfully.');
    redirect('users');
}

if ($action === 'delete-user' && $method === 'POST') {
    require_admin();

    $userId = isset($_POST['user_id']) ? $_POST['user_id'] : '';
    $user = find_user_by_id($userId);

    if ($userId === current_user()['id']) {
        set_flash('danger', 'You cannot delete your own account.');
        redirect('users');
    }

    if ($user === null || $user['role'] === 'admin') {
        set_flash('danger', 'That user cannot be deleted.');
        redirect('users');
    }

    delete_user($userId);
    set_flash('success', 'User deleted.');
    redirect('users');
}

if ($action === 'ads' && $method === 'POST') {
    require_login();

    $title = trim(isset($_POST['title']) ? $_POST['title'] : '');
    $platform = trim(isset($_POST['platform']) ? $_POST['platform'] : '');
    $details = trim(isset($_POST['details']) ? $_POST['details'] : '');
    $lastAdvertisedAt = isset($_POST['last_advertised_at']) ? $_POST['last_advertised_at'] : '';
    $repeatEveryDays = (int) (isset($_POST['repeat_every_days']) ? $_POST['repeat_every_days'] : 0);
    $notes = trim(isset($_POST['notes']) ? $_POST['notes'] : '');
    $readvertiseLink = trim(isset($_POST['readvertise_link']) ? $_POST['readvertise_link'] : '');
    $adId = isset($_POST['ad_id']) ? $_POST['ad_id'] : '';
    $uploadedPhotos = [];

    if ($title === '' || $platform === '' || $details === '' || $lastAdvertisedAt === '' || $repeatEveryDays < 1) {
        set_flash('danger', 'Title, platform, details, advertised date, and repeat schedule are required.');
        if ($adId !== '') {
            redirect_to('action=ads&edit=' . urlencode($adId));
        }

        redirect('ads');
    }

    if (!is_valid_optional_url($readvertiseLink)) {
        set_flash('danger', 'Re-advertise link must be a valid URL.');
        if ($adId !== '') {
            redirect_to('action=ads&edit=' . urlencode($adId));
        }

        redirect('ads');
    }

    try {
        $uploadedPhotos = store_uploaded_ad_photos(isset($_FILES['photos']) ? $_FILES['photos'] : []);
    } catch (RuntimeException $exception) {
        set_flash('danger', $exception->getMessage());
        if ($adId !== '') {
            redirect_to('action=ads&edit=' . urlencode($adId));
        }

        redirect('ads');
    }

    $payload = [
        'title' => $title,
        'platform' => $platform,
        'details' => $details,
        'last_advertised_at' => $lastAdvertisedAt,
        'repeat_every_days' => $repeatEveryDays,
        'notes' => $notes,
        'readvertise_link' => $readvertiseLink,
        'photos' => $uploadedPhotos,
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

if ($action === 'delete-photo' && $method === 'POST') {
    require_login();

    $adId = isset($_POST['ad_id']) ? $_POST['ad_id'] : '';
    $photoIndex = (int) (isset($_POST['photo_index']) ? $_POST['photo_index'] : -1);

    if (!delete_ad_photo($adId, $photoIndex, current_user())) {
        set_flash('danger', 'Photo could not be removed.');
    } else {
        set_flash('success', 'Photo removed.');
    }

    redirect_to('action=ads&edit=' . urlencode($adId));
}

if ($action === 'mark-advertised' && $method === 'POST') {
    require_login();
    $readvertiseLink = trim(isset($_POST['readvertise_link']) ? $_POST['readvertise_link'] : '');
    $returnAction = isset($_POST['return_to']) && $_POST['return_to'] === 'dashboard' ? 'dashboard' : 'ads';

    if ($readvertiseLink !== '' && !is_valid_optional_url($readvertiseLink)) {
        set_flash('danger', 'Re-advertise link must be a valid URL.');
        redirect($returnAction);
    }

    mark_ad_as_advertised(
        isset($_POST['ad_id']) ? $_POST['ad_id'] : '',
        current_user(),
        $readvertiseLink !== '' ? $readvertiseLink : null
    );
    set_flash('success', 'Advertised date updated to today.');
    redirect($returnAction);
}

if ($action === 'withdraw-readvertise' && $method === 'POST') {
    require_login();
    $returnAction = isset($_POST['return_to']) && $_POST['return_to'] === 'dashboard' ? 'dashboard' : 'ads';

    if (!withdraw_advertised_ad(isset($_POST['ad_id']) ? $_POST['ad_id'] : '', current_user())) {
        set_flash('danger', 'Latest re-advertise action could not be withdrawn.');
    } else {
        set_flash('success', 'Latest re-advertise action withdrawn.');
    }

    redirect($returnAction);
}

if ($action === 'update-readvertise-link' && $method === 'POST') {
    require_login();
    $readvertiseLink = trim(isset($_POST['readvertise_link']) ? $_POST['readvertise_link'] : '');
    $returnAction = isset($_POST['return_to']) && $_POST['return_to'] === 'dashboard' ? 'dashboard' : 'ads';

    if (!is_valid_optional_url($readvertiseLink)) {
        set_flash('danger', 'Re-advertise link must be a valid URL.');
        redirect($returnAction);
    }

    if (!update_ad_readvertise_link(isset($_POST['ad_id']) ? $_POST['ad_id'] : '', $readvertiseLink, current_user())) {
        set_flash('danger', 'Re-advertise link could not be updated.');
    } else {
        set_flash('success', $readvertiseLink === '' ? 'Re-advertise link removed.' : 'Re-advertise link updated.');
    }

    redirect($returnAction);
}

if ($action === 'delete-ad' && $method === 'POST') {
    require_login();
    delete_ad(isset($_POST['ad_id']) ? $_POST['ad_id'] : '', current_user());
    set_flash('success', 'Ad deleted.');
    redirect('ads');
}

$pageTitle = 'AdTrack';
$currentUser = current_user();
$users = [];
$ads = [];
$editingUser = null;
$editingAd = null;

if ($action === 'users') {
    require_admin();
    $pageTitle = 'User Management';
    $users = get_users();

    $editUserId = isset($_GET['edit']) ? $_GET['edit'] : '';

    if ($editUserId !== '') {
        $editingUser = find_user_by_id($editUserId);

        if ($editingUser === null || $editingUser['role'] === 'admin') {
            set_flash('danger', 'That user cannot be edited here.');
            redirect('users');
        }
    }
}

if ($action === 'ads') {
    require_login();
    $pageTitle = 'Manage Ads';
    $ads = get_ads_for_user($currentUser);
    $editId = isset($_GET['edit']) ? $_GET['edit'] : '';
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