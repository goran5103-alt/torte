<?php
declare(strict_types=1);

namespace App;

use PDO;

/** Primenjuje SQL fajlove iz database/migrations redom. Bez SSH-a se pokreće iz dashboarda. */
final class Migrator
{
    public static function pending(PDO $pdo): array
    {
        self::ensureTable($pdo);
        $done = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
        $files = glob(APP_DIR . '/database/migrations/*.sql') ?: [];
        sort($files);
        $pending = [];
        foreach ($files as $file) {
            $version = basename($file, '.sql');
            if (!in_array($version, $done, true)) {
                $pending[$version] = $file;
            }
        }
        return $pending;
    }

    /** @return string[] primenjene verzije */
    public static function migrate(PDO $pdo): array
    {
        $applied = [];
        foreach (self::pending($pdo) as $version => $file) {
            foreach (self::statements((string)file_get_contents($file)) as $sql) {
                $pdo->exec($sql);
            }
            $stmt = $pdo->prepare('INSERT INTO schema_migrations (version, applied_at) VALUES (?, NOW())');
            $stmt->execute([$version]);
            $applied[] = $version;
        }
        return $applied;
    }

    private static function ensureTable(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
            version VARCHAR(100) PRIMARY KEY,
            applied_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    private static function statements(string $sql): array
    {
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        return array_values(array_filter(array_map('trim', explode(";\n", $sql . "\n"))));
    }
}
