<?php
// Poddomen poslastičarnice: koristi istu aplikaciju kao glavni domen.
// Radi i kad je poddomen folder unutar public_html glavnog domena (public_html/test)
// i kad ga DirectAdmin napravi kao poseban domen (domains/test.proba.sbs/public_html).
if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    echo 'Poddomen koristi PHP ' . PHP_VERSION . '. Potreban je PHP 8.1 ili noviji (DirectAdmin → Select PHP version).';
    exit;
}

$candidates = array_merge(
    [__DIR__ . '/../index.php'],
    glob(__DIR__ . '/../../*/public_html/index.php') ?: []
);
foreach ($candidates as $entry) {
    // Pravi glavni index.php ima folder app pored svog public_html.
    if (is_file($entry) && is_file(dirname($entry) . '/../app/bootstrap.php')) {
        require $entry;
        exit;
    }
}

http_response_code(500);
echo 'Ne nalazim aplikaciju. Folder "app" i glavni public_html treba da budu u domains/GLAVNI-DOMEN/.';
