<?php

declare(strict_types=1);

function app_path(string $path = ''): string
{
    $basePath = dirname(__DIR__);

    return $path === '' ? $basePath : $basePath . DIRECTORY_SEPARATOR . $path;
}

function redirect(string $action): void
{
    header('Location: index.php?action=' . urlencode($action));
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $flash;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function now_date(): string
{
    return date('Y-m-d');
}

function calculate_next_date(string $lastAdvertisedAt, int $repeatEveryDays): string
{
    $date = new DateTimeImmutable($lastAdvertisedAt);

    return $date->modify('+' . $repeatEveryDays . ' days')->format('Y-m-d');
}

function days_until(string $date): int
{
    $today = new DateTimeImmutable(now_date());
    $target = new DateTimeImmutable($date);

    return (int) $today->diff($target)->format('%r%a');
}

function platform_options(): array
{
    return ['carousell', 'mudah.my', 'facebook marketplace'];
}

function hydrate_ads(array $ads): array
{
    foreach ($ads as &$ad) {
        $ad['next_advertise_at'] = calculate_next_date($ad['last_advertised_at'], (int) $ad['repeat_every_days']);
        $daysUntil = days_until($ad['next_advertise_at']);
        $ad['days_until'] = $daysUntil;
        $ad['status'] = $daysUntil < 0 ? 'overdue' : ($daysUntil === 0 ? 'due' : 'upcoming');
    }

    unset($ad);

    usort($ads, static function (array $left, array $right): int {
        return strcmp($left['next_advertise_at'], $right['next_advertise_at']);
    });

    return $ads;
}

function count_ads_by_status(array $ads, string $status): int
{
    return count(array_filter($ads, static fn(array $ad): bool => $ad['status'] === $status));
}

function storage_file(string $name): string
{
    return app_path('data' . DIRECTORY_SEPARATOR . $name . '.json');
}