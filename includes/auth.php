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

    $_SESSION['user_id'] = $user['id'];

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