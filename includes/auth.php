<?php

function find_user_by_username($username)
{
    foreach (get_users() as $user) {
        if (strcasecmp($user['username'], $username) === 0) {
            return $user;
        }
    }

    return null;
}

function find_user_by_id($userId)
{
    foreach (get_users() as $user) {
        if ($user['id'] === $userId) {
            return $user;
        }
    }

    return null;
}

function attempt_login($username, $password)
{
    $user = find_user_by_username($username);

    if ($user === null || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    login_user($user);

    return true;
}

function login_user($user)
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
}

function speed_login_users()
{
    $usersByRole = [];

    foreach (get_users() as $user) {
        $role = isset($user['role']) ? (string) $user['role'] : '';

        if ($role !== '' && !isset($usersByRole[$role])) {
            $usersByRole[$role] = $user;
        }
    }

    ksort($usersByRole);

    return $usersByRole;
}

function attempt_speed_login($role)
{
    $usersByRole = speed_login_users();

    if (!isset($usersByRole[$role])) {
        return false;
    }

    login_user($usersByRole[$role]);

    return true;
}

function current_user()
{
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    if (!is_string($userId)) {
        return null;
    }

    return find_user_by_id($userId);
}

function is_logged_in()
{
    return current_user() !== null;
}

function require_login()
{
    if (!is_logged_in()) {
        redirect('login');
    }
}

function require_admin()
{
    $user = current_user();

    if ($user === null || $user['role'] !== 'admin') {
        set_flash('danger', 'Admin access required.');
        redirect('dashboard');
    }
}

function logout_user()
{
    unset($_SESSION['user_id']);
}
