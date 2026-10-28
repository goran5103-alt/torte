<?php
declare(strict_types=1);

define('APP_DIR', __DIR__);

if (!is_file(APP_DIR . '/vendor/autoload.php')) {
    http_response_code(500);
    echo 'Nedostaje folder app/vendor. Uploadujte kompletan paket.';
    exit;
}

require APP_DIR . '/vendor/autoload.php';
require APP_DIR . '/src/helpers.php';

date_default_timezone_set('Europe/Belgrade');
mb_internal_encoding('UTF-8');

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_DIR . '/storage/logs/php-error.log');
