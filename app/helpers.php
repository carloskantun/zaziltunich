<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\I18n;

/** Escapa texto para HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL base del sitio sin barra final, definida en la instalación. */
function base_url(): string
{
    // Servidor de desarrollo (php -S): usa el host y puerto reales de la petición, sin importar lo guardado al instalar.
    if (PHP_SAPI === 'cli-server' && !empty($_SERVER['HTTP_HOST'])) {
        return 'http://' . $_SERVER['HTTP_HOST'];
    }
    return rtrim((string) Config::get('base_url', ''), '/');
}

/** El subdominio de revisión no acepta reservas reales ni debe indexarse. */
function is_preview_host(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    return explode(':', $host, 2)[0] === 'zazil.serviciomultimedia.com';
}

/** Ruta base (por ejemplo "" o "/zazil") derivada de base_url. */
function base_path(): string
{
    $path = parse_url(base_url(), PHP_URL_PATH);
    return rtrim(is_string($path) ? $path : '', '/');
}

/** URL pública con prefijo de idioma. */
function url(string $path = '/', ?string $lang = null): string
{
    $lang = $lang ?? I18n::lang();
    $prefix = $lang !== I18n::DEFAULT ? '/' . $lang : '';
    $path = '/' . ltrim($path, '/');
    return base_url() . $prefix . ($path === '/' && $prefix !== '' ? '/' : $path);
}

/** URL sin prefijo de idioma (admin, API, assets). */
function raw_url(string $path = '/'): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return raw_url('assets/' . ltrim($path, '/'));
}

/** Traducción de cadenas de la interfaz. */
function t(string $key, array $vars = []): string
{
    return I18n::t($key, $vars);
}

/** Convierte centavos a texto de dinero: 123450 -> "$ 1,234.50" o "$ 1,235" si no hay centavos. */
function money(int $cents, string $currency = 'MXN'): string
{
    $whole = intdiv($cents, 100);
    $rest = abs($cents % 100);
    $text = number_format($whole, 0, '.', ',');
    if ($cents < 0 && $whole === 0) {
        $text = '-' . $text;
    }
    if ($rest !== 0) {
        $text .= '.' . str_pad((string) $rest, 2, '0', STR_PAD_LEFT);
    }
    return '$ ' . $text . ($currency !== 'MXN' ? ' ' . $currency : '');
}

/** Decimal de base de datos o formulario -> centavos enteros. */
function to_cents(mixed $value): int
{
    if ($value === null || $value === '') {
        return 0;
    }
    $clean = str_replace([',', ' ', '$'], '', (string) $value);
    return (int) round(((float) $clean) * 100);
}

function from_cents(int $cents): string
{
    return number_format($cents / 100, 2, '.', '');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
    }
    return (string) $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function flash(?string $message = null, string $type = 'ok'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['old'][$key] ?? $default;
}

function redirect(string $to): never
{
    header('Location: ' . $to, true, 302);
    exit;
}

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function site_tz(): DateTimeZone
{
    return new DateTimeZone((string) Config::get('timezone', 'America/Merida'));
}

function now_site(): DateTimeImmutable
{
    return new DateTimeImmutable('now', site_tz());
}

function today_site(): string
{
    return now_site()->format('Y-m-d');
}

/** Divide un texto en líneas no vacías. */
function lines(?string $text): array
{
    if ($text === null || trim($text) === '') {
        return [];
    }
    $out = [];
    foreach (preg_split('/\R/u', $text) ?: [] as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

/** Fecha legible, por idioma. */
function date_label(string $date, ?string $lang = null): string
{
    $lang = $lang ?? I18n::lang();
    $ts = strtotime($date . ' 12:00:00');
    if ($ts === false) {
        return $date;
    }
    $months = $lang === 'en'
        ? ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
        : ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $d = (int) date('j', $ts);
    $m = $months[(int) date('n', $ts) - 1];
    $y = date('Y', $ts);
    return $lang === 'en' ? "$m $d, $y" : "$d de $m de $y";
}

function status_label(string $status): string
{
    return t('status.' . $status);
}

/** Ícono SVG en línea (public/assets/icons/<nombre>.svg), pintado con el color del texto. */
function icon(string $name, string $class = ''): string
{
    static $cache = [];
    if (!isset($cache[$name])) {
        $file = ROOT . '/public/assets/icons/' . preg_replace('/[^a-z0-9-]/', '', $name) . '.svg';
        $svg = is_file($file) ? (string) file_get_contents($file) : '';
        $svg = preg_replace(['/<!--.*?-->/s', '/<title>.*?<\/title>/s'], '', $svg) ?? '';
        $cache[$name] = preg_replace('/^<svg /', '<svg aria-hidden="true" focusable="false" ', trim($svg)) ?? '';
    }
    return $cache[$name] === '' ? '' : str_replace('<svg ', '<svg class="i' . ($class !== '' ? ' ' . e($class) : '') . '" ', $cache[$name]);
}

/** Datos del sitio para cabecera, pie y barra lateral (editables en Ajustes, con valores del sitio actual por defecto). */
function site_info(): array
{
    $s = static fn (string $k, string $d) => \App\Domain\Settings::get($k, $d);
    $wa = preg_replace('/\D+/', '', $s('contact_whatsapp', '529851305096'));
    return [
        'email' => $s('contact_email', 'info@zaziltunich.com'),
        'phone' => $s('site_phone_label', '+52 (985) 130-5096'),
        'wa' => 'https://wa.me/' . $wa,
        'address' => $s('site_address', 'Carretera Yalcobá-Xtut Km. 6, 97780 Valladolid, Yuc.'),
        'social' => array_filter([
            'facebook-f' => $s('social_facebook', 'https://www.facebook.com/zaziltunich/'),
            'tripadvisor' => $s('social_tripadvisor', 'https://www.tripadvisor.com.mx/Attraction_Review-g499453-d12210266-Reviews-Zazil_Tunich-Valladolid_Yucatan_Peninsula.html'),
            'x-twitter' => $s('social_x', 'https://x.com/zaziltunich'),
            'instagram' => $s('social_instagram', 'https://www.instagram.com/zaziltunich/'),
        ]),
    ];
}

/** URL de un archivo de uploads/site si existe, o null. */
function site_file(string $rel): ?string
{
    return is_file(ROOT . '/public/uploads/site/' . $rel) ? raw_url('uploads/site/' . $rel) : null;
}
