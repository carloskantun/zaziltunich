<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/**
 * Acceso a base de datos con PDO. Soporta MySQL/MariaDB (producción en Hostinger)
 * y SQLite (desarrollo y pruebas). El SQL de la aplicación es portable entre ambos.
 */
final class Database
{
    private static ?PDO $pdo = null;
    private static string $driver = 'mysql';

    public static function connect(array $cfg): void
    {
        $driver = (string) ($cfg['driver'] ?? 'mysql');
        if ($driver === 'sqlite') {
            $path = (string) ($cfg['path'] ?? ROOT . '/storage/dev.sqlite');
            $pdo = new PDO('sqlite:' . $path);
            $pdo->exec('PRAGMA foreign_keys = ON');
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $cfg['host'] ?? 'localhost',
                (int) ($cfg['port'] ?? 3306),
                $cfg['name'] ?? ''
            );
            $pdo = new PDO($dsn, (string) ($cfg['user'] ?? ''), (string) ($cfg['pass'] ?? ''));
            $pdo->exec("SET time_zone = '+00:00'");
            $pdo->exec("SET NAMES utf8mb4");
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        self::$pdo = $pdo;
        self::$driver = $driver;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            throw new RuntimeException('La base de datos no está conectada.');
        }
        return self::$pdo;
    }

    public static function driver(): string
    {
        return self::$driver;
    }

    public static function isMysql(): bool
    {
        return self::$driver === 'mysql';
    }

    public static function all(string $sql, array $params = []): array
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        $v = $st->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function exec(string $sql, array $params = []): int
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(static fn ($c) => '`' . $c . '`', $cols)),
            implode(', ', array_fill(0, count($cols), '?'))
        );
        self::exec($sql, array_values($data));
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $params = []): int
    {
        $set = implode(', ', array_map(static fn ($c) => '`' . $c . '` = ?', array_keys($data)));
        return self::exec("UPDATE $table SET $set WHERE $where", [...array_values($data), ...$params]);
    }

    /** Ejecuta $fn dentro de una transacción; revierte si lanza una excepción. */
    public static function tx(callable $fn): mixed
    {
        $pdo = self::pdo();
        if ($pdo->inTransaction()) {
            return $fn();
        }
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Bloquea una fila para serializar reservas concurrentes (solo MySQL; SQLite serializa escrituras). */
    public static function lockRow(string $table, int $id): void
    {
        if (self::isMysql()) {
            self::value("SELECT id FROM $table WHERE id = ? FOR UPDATE", [$id]);
        }
    }

    /** Ejecuta un archivo de esquema adaptando los marcadores al motor. */
    public static function runSchema(string $sql): void
    {
        if (self::isMysql()) {
            $sql = str_replace(['{{PK}}', '{{ENGINE}}'], ['INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY', ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'], $sql);
        } else {
            $sql = str_replace(['{{PK}}', '{{ENGINE}}'], ['INTEGER PRIMARY KEY AUTOINCREMENT', ''], $sql);
        }
        $sql = str_replace("\r\n", "\n", $sql);
        foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $statement) {
            // Quita comentarios de línea completa antes de ejecutar.
            $statement = trim(preg_replace('/^\s*--.*$/m', '', $statement) ?? '');
            if ($statement !== '') {
                self::pdo()->exec($statement);
            }
        }
    }
}
