<?php
declare(strict_types=1);

// Javni folder (public_html glavnog domena). Poddomeni ga uključuju preko svog index.php.
if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    echo 'Server koristi PHP ' . PHP_VERSION . '. Potreban je PHP 8.1 ili noviji (DirectAdmin → Select PHP version).';
    exit;
}

define('PUBLIC_DIR', __DIR__);

$appDir = null;
foreach ([__DIR__ . '/../app', __DIR__ . '/app'] as $candidate) {
    if (is_file($candidate . '/bootstrap.php')) {
        $appDir = realpath($candidate);
        break;
    }
}
if ($appDir === null) {
    http_response_code(500);
    echo 'Aplikacija nije pronađena. Folder "app" treba da bude pored public_html.';
    exit;
}

require $appDir . '/bootstrap.php';

App\Kernel::handle();
