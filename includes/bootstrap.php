<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/helpers.php';
require __DIR__ . '/storage.php';
require __DIR__ . '/auth.php';

initialize_storage();