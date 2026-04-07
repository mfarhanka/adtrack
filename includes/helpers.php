<?php

function app_path($path = '')
{
    $basePath = dirname(__DIR__);

    return $path === '' ? $basePath : $basePath . DIRECTORY_SEPARATOR . $path;
}

function redirect($action)
{
    header('Location: index.php?action=' . urlencode($action));
    exit;
}

function redirect_to($query)
{
    header('Location: index.php?' . ltrim($query, '?'));
    exit;
}

function set_flash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash()
{
    $flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
    unset($_SESSION['flash']);

    return $flash;
}

function e($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function now_date()
{
    return date('Y-m-d');
}

function calculate_next_date($lastAdvertisedAt, $repeatEveryDays)
{
    $date = new DateTimeImmutable($lastAdvertisedAt);

    return $date->modify('+' . $repeatEveryDays . ' days')->format('Y-m-d');
}

function days_until($date)
{
    $today = new DateTimeImmutable(now_date());
    $target = new DateTimeImmutable($date);

    return (int) $today->diff($target)->format('%r%a');
}

function platform_options()
{
    return ['carousell', 'mudah.my', 'facebook marketplace'];
}

function hydrate_ads($ads)
{
    foreach ($ads as &$ad) {
        $ad['next_advertise_at'] = calculate_next_date($ad['last_advertised_at'], (int) $ad['repeat_every_days']);
        $daysUntil = days_until($ad['next_advertise_at']);
        $ad['days_until'] = $daysUntil;
        $ad['status'] = $daysUntil < 0 ? 'overdue' : ($daysUntil === 0 ? 'due' : 'upcoming');
    }

    unset($ad);

    usort($ads, static function ($left, $right) {
        return strcmp($left['next_advertise_at'], $right['next_advertise_at']);
    });

    return $ads;
}

function count_ads_by_status($ads, $status)
{
    return count(array_filter($ads, static function ($ad) use ($status) {
        return $ad['status'] === $status;
    }));
}

function storage_file($name)
{
    return app_path('data' . DIRECTORY_SEPARATOR . $name . '.json');
}

function ad_upload_directory()
{
    return app_path('uploads' . DIRECTORY_SEPARATOR . 'ad_photos');
}

function ad_upload_relative_path($filename)
{
    return 'uploads/ad_photos/' . $filename;
}