<?php
declare(strict_types=1);

namespace App;

/**
 * Prijava. Sesija je vezana za host, pa su prijava na proba.sbs (superadmin)
 * i prijava na test.proba.sbs/admin (poslastičar) odvojene.
 */
final class Auth
{
    private const MAX_ATTEMPTS = 8;

    public static function user(): ?array
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            return null;
        }
        static $cache = [];
        return $cache[$id] ??= Db::one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function tooManyAttempts(): bool
    {
        $count = (int)Db::value(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND created_at > ?',
            [client_ip(), date('Y-m-d H:i:s', time() - 900)]
        );
        return $count >= self::MAX_ATTEMPTS;
    }

    /** Proverava email i lozinku za datu poslastičarnicu (null = superadmin). */
    public static function attempt(string $email, string $password, ?int $shopId): bool
    {
        if ($shopId === null) {
            $user = Db::one("SELECT * FROM users WHERE email = ? AND role = 'superadmin'", [$email]);
        } else {
            $user = Db::one('SELECT * FROM users WHERE email = ? AND shop_id = ?', [$email, $shopId]);
        }
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Db::insert('login_attempts', ['ip' => client_ip(), 'created_at' => now()]);
            return false;
        }
        self::login($user);
        return true;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        Db::update('users', ['last_login_at' => now()], ['id' => $user['id']]);
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
    }

    public static function requireSuperadmin(): array
    {
        $user = self::user();
        if (!$user || $user['role'] !== 'superadmin') {
            redirect('/login');
        }
        return $user;
    }

    public static function requireShopUser(): array
    {
        $user = self::user();
        if (!$user || (int)$user['shop_id'] !== Shop::id()) {
            redirect('/admin/login');
        }
        return $user;
    }

    /** Potpisan jednokratni link za ulazak superadmina u admin poslastičarnice. */
    public static function ssoToken(int $userId, int $shopId): string
    {
        $expires = time() + 120;
        $payload = "$userId.$shopId.$expires";
        return $payload . '.' . hash_hmac('sha256', $payload, (string)Config::get('app_secret'));
    }

    /** @return int|null ID korisnika poslastičarnice u koga se ulazi */
    public static function verifySso(string $token, int $shopId): ?int
    {
        $parts = explode('.', $token);
        if (count($parts) !== 4) {
            return null;
        }
        [$userId, $tokenShop, $expires, $sig] = $parts;
        $expected = hash_hmac('sha256', "$userId.$tokenShop.$expires", (string)Config::get('app_secret'));
        if (!hash_equals($expected, $sig) || (int)$tokenShop !== $shopId || (int)$expires < time()) {
            return null;
        }
        $super = Db::one("SELECT id FROM users WHERE id = ? AND role = 'superadmin'", [(int)$userId]);
        if (!$super) {
            return null;
        }
        $owner = Db::value('SELECT id FROM users WHERE shop_id = ? ORDER BY id LIMIT 1', [$shopId]);
        return $owner ? (int)$owner : null;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
