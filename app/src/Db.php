<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Db
{
    private static ?PDO $pdo = null;

    public static function connect(array $cfg): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $cfg['host'] ?? 'localhost',
            (int)($cfg['port'] ?? 3306),
            $cfg['name'] ?? ''
        );
        return new PDO($dsn, $cfg['user'] ?? '', $cfg['pass'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::connect(Config::get('db', []));
            self::$pdo->exec("SET time_zone = '" . date('P') . "'");
        }
        return self::$pdo;
    }

    public static function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $cols),
            implode(', ', array_map(fn($c) => ':' . $c, $cols))
        );
        self::run($sql, $data);
        return (int)self::pdo()->lastInsertId();
    }

    /** Ažurira redove; $where je npr. ['id' => 5, 'shop_id' => 2]. */
    public static function update(string $table, array $data, array $where): int
    {
        $set = implode(', ', array_map(fn($c) => "$c = :set_$c", array_keys($data)));
        $cond = implode(' AND ', array_map(fn($c) => "$c = :where_$c", array_keys($where)));
        $params = [];
        foreach ($data as $k => $v) {
            $params['set_' . $k] = $v;
        }
        foreach ($where as $k => $v) {
            $params['where_' . $k] = $v;
        }
        return self::run("UPDATE $table SET $set WHERE $cond", $params)->rowCount();
    }

    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
