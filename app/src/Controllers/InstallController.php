<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Config;
use App\Db;
use App\Migrator;
use App\View;

/** Instalacija preko browsera (hosting bez SSH-a). Radi samo dok ne postoji config.php. */
final class InstallController
{
    public function form(array $p = [], array $errors = [], array $old = []): void
    {
        $host = strtolower($_SERVER['HTTP_HOST'] ?? 'proba.sbs');
        $checks = [
            'PHP 8.1 ili noviji' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'PDO MySQL' => extension_loaded('pdo_mysql'),
            'cURL' => extension_loaded('curl'),
            'GD (slike)' => extension_loaded('gd'),
            'mbstring' => extension_loaded('mbstring'),
            'Upis u app/config' => is_writable(APP_DIR . '/config'),
            'Upis u app/storage' => is_writable(APP_DIR . '/storage'),
            'Upis u public_html/uploads' => is_writable(PUBLIC_DIR . '/uploads'),
        ];
        View::render('platform/install', [
            'checks' => $checks,
            'errors' => $errors,
            'old' => $old + [
                'db_host' => 'localhost',
                'db_name' => '',
                'db_user' => '',
                'main_domain' => preg_replace('/^www\./', '', $host),
                'email' => '',
                'name' => '',
            ],
        ]);
    }

    public function install(array $p): void
    {
        $old = [
            'db_host' => input('db_host', 'localhost'),
            'db_name' => input('db_name'),
            'db_user' => input('db_user'),
            'main_domain' => strtolower(input('main_domain')),
            'email' => strtolower(input('email')),
            'name' => input('name'),
        ];
        $password = (string)($_POST['password'] ?? '');
        $errors = [];

        foreach (['db_name' => 'Ime baze', 'db_user' => 'Korisnik baze', 'main_domain' => 'Domen'] as $k => $label) {
            if ($old[$k] === '') {
                $errors[] = "$label je obavezno.";
            }
        }
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Unesite ispravan email.';
        }
        // Samo lokalna baza: dok instalacija nije završena, niko ne može da poveže tuđu udaljenu bazu.
        if (!in_array($old['db_host'], ['localhost', '127.0.0.1'], true)) {
            $errors[] = 'Host baze mora biti localhost.';
        }
        if (strlen($password) < 10) {
            $errors[] = 'Lozinka mora imati najmanje 10 znakova.';
        }

        $db = [
            'host' => $old['db_host'],
            'port' => 3306,
            'name' => $old['db_name'],
            'user' => $old['db_user'],
            'pass' => (string)($_POST['db_pass'] ?? ''),
        ];

        if (!$errors) {
            try {
                $pdo = Db::connect($db);
                Migrator::migrate($pdo);
                $exists = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'superadmin'");
                $exists->execute();
                if ((int)$exists->fetchColumn() === 0) {
                    $stmt = $pdo->prepare('INSERT INTO users (shop_id, role, name, email, password_hash, created_at)
                        VALUES (NULL, \'superadmin\', ?, ?, ?, NOW())');
                    $stmt->execute([$old['name'] ?: 'Admin', $old['email'], Auth::hash($password)]);
                }
            } catch (\Throwable $e) {
                $errors[] = 'Baza: ' . $e->getMessage();
            }
        }

        if ($errors) {
            $this->form([], $errors, $old);
            return;
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        $sample = require APP_DIR . '/config/config.sample.php';
        $config = array_replace_recursive($sample, [
            'app_secret' => random_token(32),
            'main_domain' => $old['main_domain'],
            'scheme' => $https ? 'https' : 'http',
            'db' => $db,
            'mail' => ['from_email' => 'no-reply@' . $old['main_domain']],
        ]);

        $php = "<?php\n// Napravljeno instalacijom " . now() . ". Može se menjati kroz File Manager.\nreturn "
            . var_export($config, true) . ";\n";
        if (file_put_contents(Config::path(), $php) === false) {
            $this->form([], ['Ne mogu da upišem app/config/config.php. Proverite dozvole foldera.'], $old);
            return;
        }
        @chmod(Config::path(), 0600);

        redirect('/login');
    }
}
