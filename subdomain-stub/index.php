<?php
// Poddomen poslastičarnice: koristi istu aplikaciju kao glavni domen.
if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    echo 'Poddomen koristi PHP ' . PHP_VERSION . '. Potreban je PHP 8.1 ili noviji (DirectAdmin → Select PHP version).';
    exit;
}
if (!is_file(__DIR__ . '/../index.php')) {
    http_response_code(500);
    echo 'Ne nalazim ' . htmlspecialchars(dirname(__DIR__)) . '/index.php. Ovaj folder treba da bude unutar public_html glavnog domena.';
    exit;
}
require __DIR__ . '/../index.php';
