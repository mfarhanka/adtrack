<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | AdTrack</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark topbar shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php?action=dashboard">AdTrack</a>
        <?php if ($currentUser !== null): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="index.php?action=dashboard">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?action=ads">Ads</a></li>
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="index.php?action=users">Users</a></li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center gap-3 text-white small">
                    <span><?= e($currentUser['name']) ?> (<?= e($currentUser['role']) ?>)</span>
                    <a class="btn btn-outline-light btn-sm" href="index.php?action=logout">Logout</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</nav>

<main class="py-4 py-lg-5">
    <div class="container">
        <?php if ($flash !== null): ?>
            <div class="alert alert-<?= e($flash['type']) ?> border-0 shadow-sm"><?= e($flash['message']) ?></div>
        <?php endif; ?>