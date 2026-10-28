<?php
declare(strict_types=1);

/**
 * Pravi paket za upload na shared hosting (bez SSH-a):
 *   php tools/build.php  →  build/torte-paket.zip
 *
 * Sadržaj zip-a odgovara folderu domains/proba.sbs/ na DirectAdmin-u:
 *   app/              aplikacija (sa vendor folderom)
 *   public_html/      javni fajlovi glavnog domena
 *   subdomain-stub/   fajlovi za folder svakog poddomena
 */

$root = dirname(__DIR__);
$buildDir = $root . '/build';
$zipPath = $buildDir . '/torte-paket.zip';

passthru('cd ' . escapeshellarg($root . '/app') . ' && composer install --no-dev --optimize-autoloader --no-interaction --quiet', $code);
if ($code !== 0) {
    fwrite(STDERR, "composer install nije uspeo\n");
    exit(1);
}

@mkdir($buildDir, 0755, true);
@unlink($zipPath);

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
    fwrite(STDERR, "Ne mogu da napravim $zipPath\n");
    exit(1);
}

$add = function (string $source, string $target, array $skip = []) use ($zip): void {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $file) {
        $rel = substr($file->getPathname(), strlen($source) + 1);
        foreach ($skip as $pattern) {
            if (fnmatch($pattern, $rel)) {
                continue 2;
            }
        }
        $name = $target . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $rel);
        $file->isDir() ? $zip->addEmptyDir($name) : $zip->addFile($file->getPathname(), $name);
    }
};

$add($root . '/app', 'app', [
    'config/config.php',
    'storage/logs/*.log',
    // Composer ponekad instalira pakete iz git-a; istorija, testovi i primeri nisu potrebni na serveru.
    'vendor/*/.git', 'vendor/*/.git/*', 'vendor/*/.github', 'vendor/*/.github/*',
    'vendor/*/*/tests', 'vendor/*/*/tests/*', 'vendor/*/*/test', 'vendor/*/*/test/*',
    'vendor/*/*/docs', 'vendor/*/*/docs/*', 'vendor/*/*/examples', 'vendor/*/*/examples/*',
]);
$add($root . '/public', 'public_html', ['uploads/[0-9]*']);
$add($root . '/subdomain-stub', 'subdomain-stub');
$zip->addFile($root . '/docs/UPUTSTVO-HOSTING.md', 'UPUTSTVO-HOSTING.md');
$zip->close();

printf("Napravljeno: %s (%.1f MB)\n", $zipPath, filesize($zipPath) / 1048576);
