<?php
declare(strict_types=1);

namespace App;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = random_token();
        }
        return $_SESSION['_csrf'];
    }

    /** Proverava token iz forme ili iz X-CSRF-Token zaglavlja (fetch pozivi). */
    public static function verify(): void
    {
        $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!is_string($sent) || $sent === '' || !hash_equals(self::token(), $sent)) {
            throw new HttpException(419, 'Sesija je istekla. Osvežite stranicu i pokušajte ponovo.');
        }
    }
}
