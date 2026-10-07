<?php
declare(strict_types=1);

namespace App\Core;

/** Idiomas: español en la raíz (por defecto) e inglés bajo /en. */
final class I18n
{
    public const DEFAULT = 'es';
    public const SUPPORTED = ['es', 'en'];

    private static string $lang = self::DEFAULT;
    private static array $strings = [];

    public static function set(string $lang): void
    {
        self::$lang = in_array($lang, self::SUPPORTED, true) ? $lang : self::DEFAULT;
        self::$strings = [];
    }

    public static function lang(): string
    {
        return self::$lang;
    }

    public static function t(string $key, array $vars = []): string
    {
        if (self::$strings === []) {
            $file = ROOT . '/app/lang/' . self::$lang . '.php';
            self::$strings = is_file($file) ? (array) require $file : [];
            if (self::$lang !== self::DEFAULT) {
                self::$strings += (array) require ROOT . '/app/lang/' . self::DEFAULT . '.php';
            }
        }
        $text = self::$strings[$key] ?? $key;
        foreach ($vars as $name => $value) {
            $text = str_replace('{' . $name . '}', (string) $value, $text);
        }
        return $text;
    }
}
