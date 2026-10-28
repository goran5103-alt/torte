<?php
declare(strict_types=1);

namespace App;

final class Config
{
    private static ?array $data = null;

    public static function path(): string
    {
        return APP_DIR . '/config/config.php';
    }

    public static function exists(): bool
    {
        return is_file(self::path());
    }

    public static function load(): void
    {
        if (self::$data === null) {
            self::$data = self::exists() ? require self::path() : [];
        }
    }

    /** Čita vrednost preko tačke, npr. Config::get('db.host'). */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        $value = self::$data;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    /** Samo za testove. */
    public static function set(array $data): void
    {
        self::$data = $data;
    }
}
