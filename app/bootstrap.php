<?php
declare(strict_types=1);

// Arranque de la aplicación. Sin Composer ni frameworks: autocarga simple de App\*.

define('ROOT', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }
    $file = ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require ROOT . '/app/helpers.php';

App\Core\Config::load();
date_default_timezone_set((string) App\Core\Config::get('timezone', 'America/Merida'));

if (PHP_SAPI !== 'cli') {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('zt_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (App\Core\Config::installed()) {
    App\Core\Database::connect((array) App\Core\Config::get('db', []));
}
