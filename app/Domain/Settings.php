<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\Database as DB;

final class Settings
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (DB::all('SELECT name, value FROM settings') as $r) {
                self::$cache[$r['name']] = (string) $r['value'];
            }
        }
        return self::$cache;
    }

    public static function get(string $name, string $default = ''): string
    {
        $all = self::all();
        return isset($all[$name]) && $all[$name] !== '' ? $all[$name] : $default;
    }

    public static function set(string $name, string $value): void
    {
        $exists = DB::value('SELECT 1 FROM settings WHERE name = ?', [$name]);
        if ($exists) {
            DB::update('settings', ['value' => $value], 'name = ?', [$name]);
        } else {
            DB::insert('settings', ['name' => $name, 'value' => $value]);
        }
        self::$cache = null;
    }
}
