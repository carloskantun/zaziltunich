<?php
declare(strict_types=1);

// Servidor de desarrollo (php -S ... public/index.php): sirve archivos estáticos y /install tal como lo haría Apache.
if (PHP_SAPI === 'cli-server') {
    $devPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $devFile = __DIR__ . $devPath;
    if ($devPath !== '/' && is_file($devFile) && !str_ends_with($devFile, '.php')) {
        return false;
    }
    if (rtrim($devPath, '/') === '/install') {
        require __DIR__ . '/install/index.php';
        return;
    }
}

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Config;
use App\Core\I18n;
use App\Core\Router;
use App\Core\View;

if (!Config::installed()) {
    header('Location: /install/');
    exit;
}

if (is_preview_host()) {
    header('X-Robots-Tag: noindex, nofollow, noarchive');
}

$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$bp = base_path();
if ($bp !== '' && str_starts_with($path, $bp)) {
    $path = substr($path, strlen($bp)) ?: '/';
}
if ($path === '/en' || str_starts_with($path, '/en/')) {
    I18n::set('en');
    $path = substr($path, 3) ?: '/';
} else {
    I18n::set('es');
}
$_SERVER['ZT_PATH'] = $path;

$router = new Router();
(require ROOT . '/app/routes.php')($router);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

try {
    if (!$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path)) {
        View::notFound();
    }
} catch (\Throwable $e) {
    error_log('[zt] ' . $e);
    http_response_code(500);
    echo 'Error interno. Intenta de nuevo en unos minutos.';
}
