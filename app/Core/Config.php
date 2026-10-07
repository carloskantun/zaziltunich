<?php
declare(strict_types=1);

namespace App\Core;

/** Configuración generada por el instalador en storage/config.php. */
final class Config
{
    private static array $data = [];

    public static function load(): void
    {
        $file = ROOT . '/storage/config.php';
        self::$data = is_file($file) ? (array) (require $file) : [];
    }

    public static function set(array $data): void
    {
        self::$data = $data;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$data;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    public static function installed(): bool
    {
        return is_file(ROOT . '/storage/installed.lock');
    }
}
