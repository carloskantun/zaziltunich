<?php
declare(strict_types=1);

/**
 * Importa una MUESTRA BÁSICA de zaziltunich.com (WordPress) al sistema nuevo:
 * logo y portada, fotos y textos (ES/EN) de 5 productos, 3 entradas de blog y páginas clave.
 *
 * Se ejecuta en tu Mac (necesita internet):
 *     php bin/migrate.php          # una vez, crea las tablas de páginas y blog
 *     php bin/import-site.php      # importa todo
 *
 * Opciones:
 *   --only=site,products,blog,pages     solo una parte (por defecto todo)
 *   --base=https://zaziltunich.com      sitio de origen
 *   --all                               TODO el sitio: todos los productos, las ~150 entradas del blog y todas las páginas
 *   --posts=3                           cuántas entradas de blog traer (o --posts=all)
 *   --hero=URL  --logo=URL              forzar la portada o el logo si la detección falla
 *   --map=slug=URL                      forzar la URL de un producto (repetible)
 *
 * Es seguro repetirlo: actualiza lo ya importado y no duplica.
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\{Config, Database as DB};

if (!Config::installed()) {
    fwrite(STDERR, "Primero instala el sistema (php -S ... y abre /install).\n");
    exit(1);
}
if (!tableExists('pages') || !tableExists('categories')) {
    fwrite(STDERR, "Faltan las tablas nuevas. Ejecuta antes: php bin/migrate.php\n");
    exit(1);
}

$opts = ['only' => 'site,products,blog,pages', 'base' => 'https://zaziltunich.com', 'posts' => '3', 'hero' => '', 'logo' => '', 'map' => []];
foreach (array_slice($argv, 1) as $a) {
    if ($a === '--all') {
        $opts['all'] = '1';
    } elseif (preg_match('/^--(\w+)=(.*)$/s', $a, $m)) {
        if ($m[1] === 'map') {
            [$k, $v] = array_pad(explode('=', $m[2], 2), 2, '');
            $opts['map'][$k] = $v;
        } else {
            $opts[$m[1]] = $m[2];
        }
    }
}
$BASE = rtrim($opts['base'], '/');
$ONLY = array_map('trim', explode(',', $opts['only']));
$UP = ROOT . '/public/uploads/site';
@mkdir($UP, 0775, true);
$now = date('Y-m-d H:i:s');
$PH = [];
$stats = ['img' => 0, 'img_fail' => 0, 'products' => 0, 'posts' => 0, 'pages' => 0];

function tableExists(string $t): bool
{
    try {
        DB::value("SELECT 1 FROM $t LIMIT 1");
        return true;
    } catch (Throwable) {
        return false;
    }
}

function say(string $m): void
{
    echo $m . "\n";
}

// ---------------------------------------------------------------- red
function http(string $url, array $headers = []): ?string
{
    global $BASE;
    // Enlaces viejos http://www.… del mismo sitio → https sin www
    $bh = (string) parse_url($BASE, PHP_URL_HOST);
    if (str_starts_with($BASE, 'https://')) {
        $url = preg_replace('#^http://(?:www\.)?' . preg_quote($bh, '#') . '#i', 'https://' . $bh, $url) ?? $url;
    }
    $url = preg_replace('#^(https?://)www\.' . preg_quote($bh, '#') . '#i', '$1' . $bh, $url) ?? $url;
    $h = array_merge(['User-Agent: Mozilla/5.0 (ZazilTunichImporter)', 'Accept-Language: es,en;q=0.8'], $headers);
    if (function_exists('curl_init')) {
        $c = curl_init($url);
        curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5, CURLOPT_TIMEOUT => 40, CURLOPT_HTTPHEADER => $h, CURLOPT_ENCODING => '']);
        $body = curl_exec($c);
        $code = (int) curl_getinfo($c, CURLINFO_RESPONSE_CODE);
        curl_close($c);
        return ($body !== false && $code >= 200 && $code < 300) ? (string) $body : null;
    }
    $ctx = stream_context_create(['http' => ['header' => implode("\r\n", $h), 'timeout' => 40, 'follow_location' => 1, 'ignore_errors' => true]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        return null;
    }
    $ok = isset($http_response_header[0]) && preg_match('/\s2\d\d\s/', $http_response_header[0]);
    return $ok ? $body : null;
}

function absUrl(string $href, string $base): string
{
    $href = trim(html_entity_decode($href));
    if ($href === '' || str_starts_with($href, 'data:')) {
        return '';
    }
    if (str_starts_with($href, '//')) {
        return 'https:' . $href;
    }
    if (preg_match('#^https?://#i', $href)) {
        return $href;
    }
    $p = parse_url($base);
    $origin = ($p['scheme'] ?? 'https') . '://' . ($p['host'] ?? '') . (isset($p['port']) ? ':' . $p['port'] : '');
    if (str_starts_with($href, '/')) {
        return $origin . $href;
    }
    $dir = rtrim(dirname($p['path'] ?? '/') === '.' ? '' : dirname($p['path'] ?? '/'), '/');
    return $origin . $dir . '/' . $href;
}

function dom(string $html): DOMXPath
{
    libxml_use_internal_errors(true);
    $d = new DOMDocument();
    $d->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_COMPACT);
    libxml_clear_errors();
    return new DOMXPath($d);
}

function q(DOMXPath $x, string $query, ?DOMNode $ctx = null): array
{
    $r = $ctx ? @$x->query($query, $ctx) : @$x->query($query);
    return $r ? iterator_to_array($r) : [];
}

function hasClass(string $c): string
{
    return "contains(concat(' ', normalize-space(@class), ' '), ' $c ')";
}

function meta(DOMXPath $x, string $prop): string
{
    $n = q($x, "//meta[@property='$prop' or @name='$prop']/@content");
    return $n ? trim($n[0]->nodeValue) : '';
}

// ---------------------------------------------------------------- imágenes
/** Descarga una imagen y la guarda (WebP si hay GD) en public/uploads/site/<dir>. Devuelve la ruta relativa a uploads/ o null. */
function saveImage(string $url, string $dir = '', bool $keepPng = false): ?string
{
    global $UP, $stats;
    static $done = [];
    if ($url === '') {
        return null;
    }
    $full = preg_replace('/-\d{2,4}x\d{2,4}(\.(?:jpe?g|png|webp))(\?.*)?$/i', '$1', $url) ?: $url;
    $key = $full;
    if (isset($done[$key])) {
        return $done[$key];
    }
    $name = preg_replace('/[^a-z0-9]+/', '-', strtolower(pathinfo((string) parse_url($full, PHP_URL_PATH), PATHINFO_FILENAME))) ?: 'img';
    $name = trim(substr($name, 0, 60), '-') ?: 'img';
    $sub = trim($dir, '/');
    $folder = $UP . ($sub !== '' ? '/' . $sub : '');
    @mkdir($folder, 0775, true);
    $rel = 'site/' . ($sub !== '' ? $sub . '/' : '');
    // ya descargada en una corrida anterior
    foreach (['webp', 'jpg', 'png', 'gif', 'svg'] as $e) {
        if (is_file($folder . '/' . $name . '.' . $e)) {
            return $done[$key] = $rel . $name . '.' . $e;
        }
    }
    $bin = http($full) ?? ($full !== $url ? http($url) : null);
    if ($bin === null) {
        $stats['img_fail']++;
        say("   ! no se pudo bajar: $url");
        return $done[$key] = null;
    }
    if (preg_match('/\.svg(\?.*)?$/i', $full) || str_starts_with(ltrim(substr($bin, 0, 200)), '<svg') || str_contains(substr($bin, 0, 200), '<svg')) {
        file_put_contents($folder . '/' . $name . '.svg', $bin);
        $stats['img']++;
        return $done[$key] = $rel . $name . '.svg';
    }
    $info = @getimagesizefromstring($bin);
    if ($info && function_exists('imagewebp') && !$keepPng && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        $img = @imagecreatefromstring($bin);
        if ($img) {
            if (imagesx($img) > 1920) {
                $img = imagescale($img, 1920);
            }
            imagepalettetotruecolor($img);
            imagesavealpha($img, true);
            if (imagewebp($img, $folder . '/' . $name . '.webp', 82)) {
                imagedestroy($img);
                $stats['img']++;
                return $done[$key] = $rel . $name . '.webp';
            }
        }
    }
    $ext = $info ? ([IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'][$info[2]] ?? 'jpg') : 'jpg';
    file_put_contents($folder . '/' . $name . '.' . $ext, $bin);
    $stats['img']++;
    return $done[$key] = $rel . $name . '.' . $ext;
}

// ---------------------------------------------------------------- limpieza de HTML
const KEEP = ['p' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'ul' => [], 'ol' => [], 'li' => [], 'strong' => [], 'em' => [], 'b' => 'strong', 'i' => 'em',
    'br' => [], 'blockquote' => [], 'a' => ['href'], 'img' => ['src', 'alt']];
const DROP = ['script', 'style', 'noscript', 'form', 'iframe', 'nav', 'header', 'footer', 'svg', 'button', 'input', 'select', 'textarea', 'link', 'meta'];

/** Bloques con HTML propio (deslizadores, íconos) que viajan como marcadores hasta el final. */
function ph(string $html): string
{
    global $PH;
    $PH[] = $html;
    return '<!--PH' . (count($PH) - 1) . '-->';
}

/** Ícono + título de un widget "icon-box" de Elementor → <div class="ibox"> con el SVG guardado como archivo. */
function iconBox(DOMElement $c, string $pageUrl): string
{
    global $UP;
    $x = new DOMXPath($c->ownerDocument);
    $title = $x->query('.//*[contains(@class,"elementor-icon-box-title")]', $c);
    $label = $title && $title->length ? trim(preg_replace('/\s+/u', ' ', $title->item(0)->textContent)) : '';
    $src = '';
    $svg = $x->query('.//*[contains(@class,"elementor-icon-box-icon")]//svg', $c);
    if ($svg && $svg->length) {
        $xml = $c->ownerDocument->saveXML($svg->item(0));
        $xml = preg_replace('/\s+xmlns(:\w+)?="[^"]*"/', '', (string) $xml);
        $xml = preg_replace('/^<svg/', '<svg xmlns="http://www.w3.org/2000/svg"', (string) $xml);
        $xml = str_ireplace(['viewbox=', 'preserveaspectratio='], ['viewBox=', 'preserveAspectRatio='], $xml);
        $xml = preg_replace('/\s(width|height)="[^"]*"/', '', $xml, 2) ?? $xml;
        @mkdir($UP . '/icons', 0775, true);
        $name = substr(sha1($xml), 0, 12) . '.svg';
        file_put_contents($UP . '/icons/' . $name, $xml);
        $src = '/uploads/site/icons/' . $name;
    } else {
        $img = $x->query('.//*[contains(@class,"elementor-icon-box-icon")]//img', $c);
        if ($img && $img->length) {
            $saved = saveImage(absUrl($img->item(0)->getAttribute('src'), $pageUrl), 'icons');
            $src = $saved ? '/uploads/' . $saved : '';
        }
    }
    if ($label === '' && $src === '') {
        return '';
    }
    return ph('<div class="ibox">' . ($src ? '<img src="' . $src . '" alt="">' : '') . '<span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span></div>');
}

/** Carrusel (swiper) → <div class="slider"> con cada foto una sola vez. */
function sliderBlock(DOMElement $c, string $pageUrl, string $imgDir): string
{
    $x = new DOMXPath($c->ownerDocument);
    $seen = [];
    $slides = '';
    foreach ($x->query('.//img', $c) ?: [] as $img) {
        $src = $img->getAttribute('data-lazy-src') ?: $img->getAttribute('data-src') ?: $img->getAttribute('src');
        $abs = absUrl($src, $pageUrl);
        $k = preg_replace('/-\d{2,4}x\d{2,4}(\.\w+)$/', '$1', $abs);
        if ($abs === '' || isset($seen[$k])) {
            continue;
        }
        $seen[$k] = true;
        if ($saved = saveImage($abs, $imgDir)) {
            $slides .= '<div class="slide"><img src="/uploads/' . $saved . '" alt="' . htmlspecialchars($img->getAttribute('alt'), ENT_QUOTES, 'UTF-8') . '" loading="lazy"></div>';
        }
    }
    if ($slides === '') {
        return '';
    }
    $nav = count($seen) > 1 ? '<button class="prev" type="button" aria-label="Anterior">‹</button><button class="next" type="button" aria-label="Siguiente">›</button>' : '';
    return ph('<div class="slider"><div class="slides">' . $slides . '</div>' . $nav . '</div>');
}

/** Mapas de Google y videos de YouTube/Vimeo: único iframe permitido. */
function embedBlock(DOMElement $c): string
{
    $src = $c->getAttribute('data-lazy-src') ?: $c->getAttribute('data-src') ?: $c->getAttribute('src');
    if (!preg_match('#^(https?:)?//(www\.)?(google\.com/maps|maps\.google\.|youtube(-nocookie)?\.com/embed|player\.vimeo\.com)#i', $src)) {
        return '';
    }
    if (str_starts_with($src, '//')) {
        $src = 'https:' . $src;
    }
    return ph('<div class="embed"><iframe src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe></div>');
}

function esc(string $s): string
{
    return htmlspecialchars(trim(preg_replace('/\s+/u', ' ', $s) ?? $s), ENT_QUOTES, 'UTF-8');
}

/** Widgets de Elementor con comportamiento propio → bloques simples. Devuelve null si no es uno conocido. */
function widgetBlock(DOMElement $c, string $cls, string $pageUrl, string $imgDir): ?string
{
    $x = new DOMXPath($c->ownerDocument);
    $has = static fn (string $cl): string => 'contains(concat(" ",normalize-space(@class)," ")," ' . $cl . ' ")';

    // pestañas anidadas
    if (str_contains($cls, ' elementor-widget-nested-tabs ')) {
        $titles = [];
        foreach ($x->query('.//*[' . $has('e-n-tab-title') . ']', $c) ?: [] as $t) {
            $titles[] = esc($t->textContent);
        }
        $panels = [];
        foreach ($x->query('.//*[@role="tabpanel"]', $c) ?: [] as $p) {
            $panels[] = cleanNode($p, $pageUrl, $imgDir);
        }
        if (!$titles || !$panels) {
            return '';
        }
        $nav = '';
        $body = '';
        foreach ($titles as $i => $t) {
            $nav .= '<button type="button" class="wtab' . ($i === 0 ? ' on' : '') . '">' . $t . '</button>';
            $body .= '<div class="wtab-panel' . ($i === 0 ? ' on' : '') . '">' . ($panels[$i] ?? '') . '</div>';
        }
        return ph('<div class="wtabs"><div class="wtab-list">' . $nav . '</div>' . $body . '</div>');
    }
    // acordeón / preguntas frecuentes
    if (str_contains($cls, ' elementor-widget-nested-accordion ')) {
        $out = '';
        foreach ($x->query('.//details', $c) ?: [] as $d) {
            $s = $x->query('.//summary', $d);
            $q = $s && $s->length ? esc($s->item(0)->textContent) : '';
            $reg = $x->query('.//*[@role="region"]', $d);
            $a = $reg && $reg->length ? cleanNode($reg->item(0), $pageUrl, $imgDir) : '';
            if ($q !== '') {
                $out .= '<details class="acc"><summary>' . $q . '</summary><div class="acc-body">' . $a . '</div></details>';
            }
        }
        return $out === '' ? '' : ph('<div class="acc-list">' . $out . '</div>');
    }
    // botón → .btn
    if (str_contains($cls, ' elementor-widget-button ')) {
        $a = $x->query('.//a', $c);
        if (!$a || !$a->length) {
            return '';
        }
        $href = absUrl($a->item(0)->getAttribute('href'), $pageUrl);
        $label = esc($a->item(0)->textContent);
        if ($label === '' || $href === '') {
            return '';
        }
        if ((string) parse_url($href, PHP_URL_HOST) === (string) parse_url($pageUrl, PHP_URL_HOST)) {
            $href = (string) (parse_url($href, PHP_URL_PATH) ?: '/');
        }
        return ph('<p class="btn-row"><a class="btn" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . $label . '</a></p>');
    }
    // tarjetas con giro (precios de boda)
    if (str_contains($cls, ' elementor-widget-flip-box ')) {
        $front = $x->query('.//*[' . $has('elementor-flip-box__front') . ']', $c);
        $back = $x->query('.//*[' . $has('elementor-flip-box__back') . ']', $c);
        $f = $front && $front->length ? esc($front->item(0)->textContent) : '';
        $b = $back && $back->length ? esc($back->item(0)->textContent) : '';
        return $f . $b === '' ? '' : ph('<div class="pcard"><strong>' . $f . '</strong><span>' . $b . '</span></div>');
    }
    // video propio (alojado)
    if (str_contains($cls, ' elementor-widget-video ')) {
        $set = json_decode($c->getAttribute('data-settings'), true) ?: [];
        $url = $set['hosted_url']['url'] ?? ($set['youtube_url'] ?? ($set['vimeo_url'] ?? ''));
        if ($url === '') {
            return '';
        }
        if (isset($set['hosted_url'])) {
            $poster = '';
            if (!empty($set['image_overlay']['url']) && ($ps = saveImage(absUrl($set['image_overlay']['url'], $pageUrl), $imgDir))) {
                $poster = ' poster="/uploads/' . $ps . '"';
            }
            return ph('<video class="vid" controls preload="none"' . $poster . ' src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></video>');
        }
        if (preg_match('#(?:v=|youtu\.be/|embed/)([\w-]{11})#', $url, $m)) {
            return ph('<div class="embed"><iframe src="https://www.youtube-nocookie.com/embed/' . $m[1] . '" loading="lazy" allowfullscreen></iframe></div>');
        }
        return '';
    }
    // galería en cuadrícula
    if (str_contains($cls, ' elementor-widget-gallery ')) {
        $seen = [];
        $items = '';
        foreach ($x->query('.//*[@data-thumbnail or ' . $has('e-gallery-image') . ']', $c) ?: [] as $g) {
            $u = $g->getAttribute('data-thumbnail') ?: $g->getAttribute('href');
            $abs = absUrl($u, $pageUrl);
            $k = preg_replace('/-\d{2,4}x\d{2,4}(\.\w+)$/', '$1', $abs);
            if ($abs === '' || isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            if ($s = saveImage($abs, $imgDir)) {
                $items .= '<img src="/uploads/' . $s . '" alt="" loading="lazy">';
            }
        }
        return $items === '' ? '' : ph('<div class="gallery">' . $items . '</div>');
    }
    // slides a todo ancho (fondos con texto)
    if (str_contains($cls, ' elementor-widget-slides ')) {
        $slides = '';
        $seen = [];
        foreach ($x->query('.//*[' . $has('swiper-slide-bg') . ']', $c) ?: [] as $bg) {
            if (!preg_match('#url\(["\']?([^)"\']+)#', $bg->getAttribute('style') . ' ' . $bg->getAttribute('data-bg'), $m)) {
                continue;
            }
            $abs = absUrl($m[1], $pageUrl);
            if (isset($seen[$abs])) {
                continue;
            }
            $seen[$abs] = true;
            if ($s = saveImage($abs, $imgDir)) {
                $slides .= '<div class="slide"><img src="/uploads/' . $s . '" alt="" loading="lazy"></div>';
            }
        }
        if ($slides === '') {
            return '';
        }
        $nav = count($seen) > 1 ? '<button class="prev" type="button" aria-label="Anterior">‹</button><button class="next" type="button" aria-label="Siguiente">›</button>' : '';
        return ph('<div class="slider"><div class="slides">' . $slides . '</div>' . $nav . '</div>');
    }
    // widgets sin contenido propio de página (formularios, reseñas, plantillas, JS)
    foreach (['reviews', 'shortcode', 'html', 'template', 'jet-listing-grid', 'woocommerce-menu-cart', 'pdfjs-viewer', 'social-icons', 'rating', 'spacer', 'divider', 'wpr-flip-carousel', 'loop-carousel', 'theme-post-featured-image'] as $skip) {
        if (str_contains($cls, " elementor-widget-$skip ") || str_contains($cls, " elementor-widget-$skip.")) {
            return '';
        }
    }
    return null;
}

/** Convierte un nodo de WordPress/Elementor en HTML simple y propio (solo etiquetas permitidas). */
function cleanNode(DOMNode $n, string $pageUrl, string $imgDir): string
{
    $out = '';
    foreach ($n->childNodes as $c) {
        if ($c instanceof DOMText) {
            $out .= htmlspecialchars(preg_replace('/\s+/u', ' ', $c->nodeValue) ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            continue;
        }
        if (!($c instanceof DOMElement)) {
            continue;
        }
        $tag = strtolower($c->tagName);
        $cls = ' ' . preg_replace('/\s+/', ' ', $c->getAttribute('class')) . ' ';
        if ($tag === 'iframe') {
            $out .= embedBlock($c);
            continue;
        }
        if (in_array($tag, DROP, true)) {
            continue;
        }
        if (str_contains($cls, ' elementor-widget-icon-box ')) {
            $out .= iconBox($c, $pageUrl);
            continue;
        }
        if (str_contains($cls, ' elementor-widget-') && ($w = widgetBlock($c, $cls, $pageUrl, $imgDir)) !== null) {
            $out .= $w;
            continue;
        }
        if (str_contains($cls, ' swiper-wrapper ')) {
            $out .= sliderBlock($c, $pageUrl, $imgDir);
            continue;
        }
        if ($tag === 'h1') {
            $tag = 'h2';
        } elseif ($tag === 'h5' || $tag === 'h6') {
            $tag = 'h4';
        }
        $inner = cleanNode($c, $pageUrl, $imgDir);
        if ($tag === 'img') {
            $src = $c->getAttribute('data-lazy-src') ?: $c->getAttribute('data-src') ?: $c->getAttribute('src');
            $abs = absUrl($src, $pageUrl);
            $saved = $abs !== '' ? saveImage($abs, $imgDir) : null;
            if ($saved) {
                $out .= '<img src="/uploads/' . $saved . '" alt="' . htmlspecialchars($c->getAttribute('alt'), ENT_QUOTES, 'UTF-8') . '" loading="lazy">';
            }
            continue;
        }
        if (!isset(KEEP[$tag])) {
            // contenedor desconocido (div, section, span…): se conserva el contenido
            $out .= $inner;
            continue;
        }
        $t = is_string(KEEP[$tag]) ? KEEP[$tag] : $tag;
        if (trim(strip_tags($inner, '<img>')) === '' && $t !== 'br') {
            continue;
        }
        if ($t === 'br') {
            $out .= '<br>';
            continue;
        }
        $attr = '';
        if ($t === 'a') {
            $href = absUrl($c->getAttribute('href'), $pageUrl);
            if ($href === '' || preg_match('#^javascript:#i', $href)) {
                $out .= $inner;
                continue;
            }
            // enlaces internos del sitio viejo → rutas relativas del nuevo
            $host = (string) parse_url($pageUrl, PHP_URL_HOST);
            if ((string) parse_url($href, PHP_URL_HOST) === $host) {
                $path = (string) parse_url($href, PHP_URL_PATH);
                $href = ($path === '' ? '/' : $path);
            }
            $attr = ' href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"';
        }
        $out .= "<$t$attr>$inner</$t>";
    }
    return $out;
}

/** Sustituye marcadores por su HTML, agrupa íconos en una fila y, en páginas, arma el bloque "medio + texto". */
function assemble(string $html, bool $split): string
{
    global $PH;
    $isIcon = static fn (int $i) => str_starts_with((string) ($PH[$i] ?? ''), '<div class="ibox">');
    // grupos de íconos consecutivos → fila
    $html = preg_replace_callback('/(?:<!--PH(\d+)-->\s*)+/', static function ($m) use ($PH, $isIcon) {
        preg_match_all('/<!--PH(\d+)-->/', $m[0], $ids);
        $out = '';
        $row = '';
        foreach ($ids[1] as $i) {
            if ($isIcon((int) $i)) {
                $row .= $PH[(int) $i];
                continue;
            }
            if ($row !== '') {
                $out .= '<div class="ibox-row">' . $row . '</div>';
                $row = '';
            }
            $out .= $PH[(int) $i];
        }
        return $out . ($row !== '' ? '<div class="ibox-row">' . $row . '</div>' : '') . "\n";
    }, $html) ?? $html;
    if ($split && preg_match('#^(<h2>.*?</h2>)\s*(<div class="slider">.*?</div></div>(?:<button.*?</button>)*</div>|<p><img[^>]*></p>)\s*(.+)$#s', $html, $m) && trim($m[3]) !== '') {
        $html = $m[1] . '<div class="split"><div class="split-media">' . $m[2] . '</div><div class="split-body">' . $m[3] . '</div></div>';
    }
    return trim($html);
}

/** Quita saltos de bloque repetidos y texto suelto sin <p>. */
function tidy(string $html): string
{
    $html = preg_replace('/(<br>\s*){3,}/', '<br><br>', $html) ?? $html;
    $html = preg_replace('/<p>\s*(<br>)?\s*<\/p>/', '', $html) ?? $html;
    // Elementor deja a menudo texto suelto entre bloques: envolver en <p>.
    $parts = preg_split('/(<(?:p|h[2-4]|ul|ol|blockquote)\b.*?<\/(?:p|h[2-4]|ul|ol|blockquote)>|<!--PH\d+-->)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [$html];
    $out = '';
    foreach ($parts as $p) {
        if (preg_match('/^<(p|h[2-4]|ul|ol|blockquote)\b|^<!--PH/i', $p)) {
            $out .= $p . "\n";
        } elseif (trim(strip_tags($p, '<img>')) !== '') {
            $out .= '<p>' . trim($p) . "</p>\n";
        }
    }
    return trim($out);
}

function mainNode(DOMXPath $x): ?DOMNode
{
    foreach (["//*[@data-elementor-type='wp-page']", "//*[@data-elementor-type='wp-post']", '//main', '//article', '//*[' . hasClass('entry-content') . ']', '//div[@id="content"]', '//body'] as $qq) {
        $r = q($x, $qq);
        if ($r) {
            return $r[0];
        }
    }
    return null;
}

function plain(DOMNode $n): string
{
    $txt = '';
    foreach ($n->childNodes as $c) {
        if ($c instanceof DOMText) {
            $txt .= preg_replace('/\s+/u', ' ', $c->nodeValue);
        } elseif ($c instanceof DOMElement) {
            $t = strtolower($c->tagName);
            if (in_array($t, DROP, true)) {
                continue;
            }
            $inner = plain($c);
            if (in_array($t, ['li'], true)) {
                $txt .= "\n" . trim($inner);
            } elseif (in_array($t, ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'tr', 'section'], true)) {
                $txt .= "\n" . $inner . "\n";
            } elseif ($t === 'br') {
                $txt .= "\n";
            } else {
                $txt .= $inner;
            }
        }
    }
    return $txt;
}

function plainClean(DOMNode $n): string
{
    $t = plain($n);
    $t = preg_replace("/[ \t]+/u", ' ', $t) ?? $t;
    $t = preg_replace("/ *\n */", "\n", $t) ?? $t;
    $t = preg_replace("/\n{3,}/", "\n\n", $t) ?? $t;
    return trim($t);
}


/** Imagen de fondo de la primera sección de la página (portada de cada página en Elementor). */
function pageHero(DOMXPath $x, string $url): ?string
{
    $first = q($x, "(//*[@data-elementor-type='wp-page']/*[@data-id])[1]");
    $id = $first ? $first[0]->getAttribute('data-id') : '';
    $cands = [];
    foreach (q($x, '//link[@rel="stylesheet"]/@href') as $h) {
        if (preg_match('#elementor/css/post-\d+\.css#', $h->nodeValue)) {
            $css = http(absUrl($h->nodeValue, $url));
            if ($css && $id !== '' && preg_match_all('#[^{}]*elementor-element-' . preg_quote($id, '#') . '[^{}]*\{[^}]*background-image:\s*url\(["\']?([^)"\']+)["\']?\)#', $css, $m)) {
                $cands = array_merge($cands, $m[1]);
            }
        }
    }
    return $cands ? absUrl($cands[0], $url) : null;
}

// ---------------------------------------------------------------- utilidades de BD
function upsertTr(string $table, string $fk, int $id, string $lang, array $row): void
{
    $row = array_filter($row, static fn ($v) => $v !== null && $v !== '');
    if (!$row) {
        return;
    }
    if (DB::value("SELECT 1 FROM $table WHERE $fk = ? AND lang = ?", [$id, $lang])) {
        DB::update($table, $row, "$fk = ? AND lang = ?", [$id, $lang]);
    } else {
        DB::insert($table, $row + [$fk => $id, 'lang' => $lang]);
    }
}

function enUrl(string $esUrl, string $base): string
{
    return $base . '/en' . substr($esUrl, strlen($base));
}

// ================================================================ 1. SITIO: logo y portada
$home = http($BASE . '/');
$homeX = $home ? dom($home) : null;

if (in_array('site', $ONLY, true)) {
    say('== Logo y portada');
    $hero = $opts['hero'];
    $logo = $opts['logo'];
    if ($homeX && $hero === '') {
        // 1) imagen de fondo declarada en CSS de Elementor o en estilos en línea; 2) og:image
        $cands = [];
        if (preg_match_all('#url\(["\']?([^)"\']+?\.(?:jpe?g|png|webp))["\']?\)#i', $home, $m)) {
            $cands = array_merge($cands, $m[1]);
        }
        foreach (q($homeX, '//link[@rel="stylesheet"]/@href') as $h) {
            if (preg_match('#elementor/css/(post|global)-\d+\.css#', $h->nodeValue)) {
                $css = http(absUrl($h->nodeValue, $BASE . '/'));
                if ($css && preg_match_all('#url\(["\']?([^)"\']+?\.(?:jpe?g|png|webp))["\']?\)#i', $css, $m)) {
                    $cands = array_merge($cands, $m[1]);
                }
            }
        }
        foreach ($cands as $c) {
            if (str_contains($c, 'uploads') && !preg_match('/logo|icon|favicon/i', $c)) {
                $hero = absUrl($c, $BASE . '/');
                break;
            }
        }
        if ($hero === '') {
            $hero = absUrl(meta($homeX, 'og:image'), $BASE . '/');
        }
    }
    if ($homeX && $logo === '') {
        foreach (array_merge(q($homeX, '//header//img'), q($homeX, '//*[' . hasClass('elementor-widget-theme-site-logo') . ']//img'), q($homeX, '//img')) as $img) {
            $s = $img->getAttribute('data-lazy-src') ?: $img->getAttribute('src');
            if (str_contains($s, '/uploads/') && !preg_match('/flag|favicon/i', $s) && (preg_match('/logo/i', $s . ' ' . $img->getAttribute('class') . ' ' . $img->getAttribute('alt')) || $img->parentNode && in_array($img->parentNode->nodeName, ['a'], true) && $img->getAttribute('width') !== '')) {
                $logo = absUrl($s, $BASE . '/');
                break;
            }
        }
    }
    if ($hero !== '') {
        $bin = http($hero);
        if ($bin && ($info = @getimagesizefromstring($bin)) && function_exists('imagewebp') && ($img = @imagecreatefromstring($bin))) {
            if (imagesx($img) > 1920) {
                $img = imagescale($img, 1920);
            }
            imagepalettetotruecolor($img);
            imagewebp($img, $UP . '/hero-cenote.webp', 82);
            say('   portada → uploads/site/hero-cenote.webp');
        } elseif ($bin) {
            file_put_contents($UP . '/hero-cenote.webp', $bin);
            say('   portada (sin convertir) → uploads/site/hero-cenote.webp');
        } else {
            say('   ! no se pudo bajar la portada: ' . $hero . '  (usa --hero=URL)');
        }
    } else {
        say('   ! no encontré la portada (usa --hero=URL)');
    }
    if ($logo === '') {
        $logo = $BASE . '/wp-content/uploads/2024/10/2.png'; // logo conocido del sitio
    }
    if ($logo !== '') {
        $bin = http($logo);
        if ($bin) {
            file_put_contents($UP . '/logo.png', $bin);
            say('   logo → uploads/site/logo.png');
        } else {
            say('   ! no se pudo bajar el logo: ' . $logo . '  (usa --logo=URL)');
        }
    } else {
        say('   ! no encontré el logo (usa --logo=URL)');
    }
}


// ---- Logos, insignia, bandera, banner de guía e Instagram (cabecera, pie y barra lateral del blog)
if (in_array('site', $ONLY, true)) {
    say('== Elementos del diseño (pie, barra lateral, banderas)');
    $grab = static function (?string $url, string $dest) use ($UP): bool {
        if (!$url) {
            return false;
        }
        $bin = http($url);
        if (!$bin) {
            say('   ! no se pudo bajar ' . $url);
            return false;
        }
        @mkdir(dirname($UP . '/' . $dest), 0775, true);
        file_put_contents($UP . '/' . $dest, $bin);
        say('   ' . $dest . ' ← ' . basename((string) parse_url($url, PHP_URL_PATH)));
        return true;
    };
    $imgSrc = static fn (DOMElement $i, string $base) => absUrl($i->getAttribute('data-lazy-src') ?: $i->getAttribute('data-src') ?: $i->getAttribute('src'), $base);
    $blogHtml = http($BASE . '/blog/');
    $bx = $blogHtml ? dom($blogHtml) : null;
    if ($bx) {
        // pie: el primer logo y la insignia de TripAdvisor
        $fimgs = array_values(array_filter(array_map(static fn ($i) => $imgSrc($i, $BASE . '/blog/'), q($bx, '//*[@data-elementor-type="footer"]//img')), static fn ($u) => $u !== '' && str_contains($u, '/uploads/')));
        if ($fimgs) {
            $badge = null;
            foreach ($fimgs as $u) {
                if (preg_match('/trip|travel|choice|award|tc/i', basename($u))) {
                    $badge = $u;
                }
            }
            $badge = $badge ?? (count($fimgs) > 1 ? end($fimgs) : null);
            if ($badge) {
                $grab($badge, 'badge-tripadvisor.png');
            }
            if (!is_file($UP . '/logo.png')) {
                $grab($fimgs[0], 'logo.png');
            }
        } else {
            say('   ! no encontré imágenes en el pie del sitio');
        }
        // barra lateral: logo oscuro
        $side = '';
        foreach (q($bx, '//main//img | //*[@data-elementor-type="wp-page"]//img') as $i) {
            $u = $imgSrc($i, $BASE . '/blog/');
            if ($u !== '' && str_contains($u, '/uploads/') && preg_match('/logo/i', $u . ' ' . $i->getAttribute('alt') . ' ' . $i->getAttribute('class'))) {
                $side = $u;
                break;
            }
        }
        if ($side === '') {
            foreach (q($bx, '//aside//img | //*[contains(@class,"elementor-widget-image")]//img') as $i) {
                $u = $imgSrc($i, $BASE . '/blog/');
                if ($u !== '' && str_contains($u, '/uploads/') && !preg_match('/flag|favicon|icon/i', $u)) {
                    $side = $u;
                    break;
                }
            }
        }
        $side !== '' ? $grab($side, 'logo-dark.png') : say('   ! no encontré el logo oscuro de la barra lateral (usa --logodark=URL)');
        // banner de la guía turística (fondo de un call-to-action)
        $cta = q($bx, '//*[contains(@class,"elementor-cta__bg")]/@style');
        if ($cta && preg_match('#url\(["\']?([^)"\']+)#', $cta[0]->nodeValue, $m)) {
            $grab(absUrl($m[1], $BASE . '/blog/'), 'guia.webp');
        } else {
            say('   ! no encontré el banner de la guía turística');
        }
        // Instagram: miniaturas del widget
        $ig = [];
        foreach (q($bx, '//*[contains(@id,"sb_instagram") or contains(@class,"sbi_item")]//img') as $i) {
            $u = $imgSrc($i, $BASE . '/blog/');
            if ($u !== '' && !str_contains($u, 'avatar') && !in_array($u, $ig, true)) {
                $ig[] = $u;
            }
        }
        foreach (array_slice($ig, 0, 9) as $n => $u) {
            $grab($u, 'ig/' . ($n + 1) . '.jpg');
        }
        if (!$ig) {
            say('   ! no encontré fotos de Instagram en el blog (el widget se carga con JavaScript; puedes poner imágenes en public/uploads/site/ig/)');
        }
        // bandera del otro idioma (la que muestra el sitio en español es la de inglés)
        foreach (q($bx, '//img[contains(@class,"trp-flag-image")]') as $i) {
            $u = $imgSrc($i, $BASE . '/blog/');
            if (str_contains($u, 'en_US') || str_contains($u, 'en_GB')) {
                $grab($u, 'flags/en.png');
                break;
            }
        }
    }
    $ebx = ($t = http($BASE . '/en/blog/')) ? dom($t) : null;
    if ($ebx) {
        foreach (q($ebx, '//img[contains(@class,"trp-flag-image")]') as $i) {
            $u = $imgSrc($i, $BASE . '/en/blog/');
            if (preg_match('/es_(MX|ES)|es_/', $u)) {
                $grab($u, 'flags/es.png');
                break;
            }
        }
    }
}

// ================================================================ 2. PRODUCTOS
/** Mapa slug → URL tomado de los sitemaps de WordPress. */
function sitemapUrls(string $base): array
{
    $urls = [];
    foreach (['/wp-sitemap.xml', '/sitemap_index.xml', '/sitemap.xml'] as $path) {
        $xml = http($base . $path);
        if (!$xml) {
            continue;
        }
        preg_match_all('#<loc>\s*([^<\s]+)\s*</loc>#', $xml, $m);
        foreach ($m[1] as $loc) {
            if (str_ends_with($loc, '.xml')) {
                $sub = http(html_entity_decode($loc));
                if ($sub && preg_match_all('#<loc>\s*([^<\s]+)\s*</loc>#', $sub, $mm)) {
                    $urls = array_merge($urls, $mm[1]);
                }
            } else {
                $urls[] = $loc;
            }
        }
        if ($urls) {
            break;
        }
    }
    return array_values(array_unique($urls));
}

function findUrlForSlug(string $slug, array $sitemap, string $base): ?string
{
    foreach ($sitemap as $u) {
        $last = basename(rtrim((string) parse_url($u, PHP_URL_PATH), '/'));
        if ($last === $slug && !str_contains($u, '/en/')) {
            return $u;
        }
    }
    foreach (['/producto/', '/product/', '/productos/', '/experiencias/', '/'] as $pre) {
        $u = $base . $pre . $slug . '/';
        if (http($u)) {
            return $u;
        }
    }
    return null;
}

/** Extrae de una página de producto: título, imagen og, galería, pestañas, destacados. */
function parseProduct(string $html, string $url): array
{
    $x = dom($html);
    $r = ['title' => '', 'seo_title' => '', 'seo_description' => '', 'og' => absUrl(meta($x, 'og:image'), $url), 'images' => [], 'fields' => []];
    $h1 = q($x, '//h1');
    $r['title'] = $h1 ? trim(preg_replace('/\s+/u', ' ', $h1[0]->textContent)) : '';
    $t = q($x, '//title');
    $r['seo_title'] = $t ? trim(preg_replace('/\s+/u', ' ', $t[0]->textContent)) : '';
    $r['seo_description'] = meta($x, 'og:description') ?: meta($x, 'description');

    // Galería: imágenes de uploads en la zona principal (sin logos ni íconos)
    $main = mainNode($x);
    $seen = [];
    $imgs = $main ? q($x, './/img', $main) : q($x, '//img');
    foreach ($imgs as $img) {
        $s = $img->getAttribute('data-lazy-src') ?: $img->getAttribute('data-src') ?: $img->getAttribute('src');
        $abs = absUrl($s, $url);
        if ($abs === '' || !str_contains($abs, '/uploads/') || preg_match('/logo|icon|favicon|whatsapp|tripadvisor|flag/i', $abs)) {
            continue;
        }
        $w = (int) $img->getAttribute('width');
        if ($w > 0 && $w < 200) {
            continue;
        }
        $k = preg_replace('/-\d{2,4}x\d{2,4}(\.\w+)$/', '$1', $abs);
        if (!isset($seen[$k])) {
            $seen[$k] = $abs;
        }
    }
    $r['images'] = array_values($seen);

    // Pestañas de Elementor: contenedores anidados (e-n-tab-title ↔ aria-controls) o clásicas (data-tab)
    $tabs = [];
    foreach (q($x, '//*[' . hasClass('e-n-tab-title') . ' and @aria-controls]') as $tt) {
        $panel = q($x, '//*[@id="' . $tt->getAttribute('aria-controls') . '"]');
        if ($panel) {
            $tabs[] = ['title' => trim(preg_replace('/\s+/u', ' ', $tt->textContent)), 'node' => $panel[0]];
        }
    }
    if (!$tabs) {
        $byIdx = [];
        foreach (q($x, '//*[' . hasClass('elementor-tab-title') . ']') as $tt) {
            $byIdx[$tt->getAttribute('data-tab')]['title'] = trim(preg_replace('/\s+/u', ' ', $tt->textContent));
        }
        foreach (q($x, '//*[' . hasClass('elementor-tab-content') . ']') as $tc) {
            $byIdx[$tc->getAttribute('data-tab')]['node'] = $tc;
        }
        $tabs = array_values($byIdx);
    }
    $f = [];
    foreach ($tabs as $tab) {
        if (empty($tab['node'])) {
            continue;
        }
        $title = mb_strtolower($tab['title'] ?? '');
        $text = plainClean($tab['node']);
        // el panel repite su propio encabezado ("Descripción", "¿Qué llevar?"): quitarlo
        $parts = explode("\n", $text, 2);
        if (count($parts) === 2 && mb_strlen($parts[0]) <= 40 && preg_match('/descrip|incluy|llevar|bring|itiner|includ|^preguntas/iu', $parts[0])) {
            $text = trim($parts[1]);
        }
        if ($text === '') {
            continue;
        }
        $key = match (true) {
            (bool) preg_match('/no incluy|not includ|excluy|exclud/u', $title) => 'excludes',
            (bool) preg_match('/incluy|includ/u', $title) => 'includes',
            (bool) preg_match('/llev|bring|recomend|what to/u', $title) => 'bring',
            (bool) preg_match('/itiner|recorrido|agenda|schedule/u', $title) => 'itinerary',
            (bool) preg_match('/faq|pregunta|question/u', $title) => 'faq',
            (bool) preg_match('/punto|meeting|encuentro|ubicaci|location/u', $title) => 'meeting_point',
            (bool) preg_match('/descrip|about|acerca|detall/u', $title) => 'description',
            default => '',
        };
        if ($key === '') {
            say('   · pestaña sin clasificar: «' . ($tab['title'] ?? '?') . '» (se ignora)');
            continue;
        }
        $f[$key] = isset($f[$key]) ? $f[$key] . "\n\n" . $text : $text;
    }
    // Si no hubo pestaña de descripción, tomar el primer bloque largo de texto
    if (empty($f['description']) && $main) {
        foreach (q($x, './/*[' . hasClass('elementor-widget-text-editor') . ']', $main) as $w) {
            $tx = plainClean($w);
            if (mb_strlen($tx) > 80) {
                $f['description'] = $tx;
                break;
            }
        }
    }
    // Destacados: cajas con ícono
    $hl = [];
    foreach (q($x, '//*[' . hasClass('elementor-icon-box-wrapper') . ']') as $b) {
        $tt = q($x, './/*[' . hasClass('elementor-icon-box-title') . ']', $b);
        $dd = q($x, './/*[' . hasClass('elementor-icon-box-description') . ']', $b);
        $a = $tt ? trim(preg_replace('/\s+/u', ' ', $tt[0]->textContent)) : '';
        $d = $dd ? trim(preg_replace('/\s+/u', ' ', $dd[0]->textContent)) : '';
        $line = $a !== '' && $d !== '' && mb_strlen($d) <= 60 ? "$a: $d" : ($a ?: $d);
        if ($line !== '') {
            $hl[] = $line;
        }
    }
    if (!$hl) {
        foreach (q($x, '//*[' . hasClass('elementor-icon-list-text') . ']') as $li) {
            $hl[] = trim(preg_replace('/\s+/u', ' ', $li->textContent));
        }
    }
    if ($hl) {
        $f['highlights'] = implode("\n", array_slice(array_unique($hl), 0, 4));
    }
    $r['fields'] = $f;
    return $r;
}

if (in_array('products', $ONLY, true)) {
    say(isset($opts['all']) ? '== Productos (todos)' : '== Productos (muestra de 5)');
    $slugs = isset($opts['all'])
        ? array_column(DB::all('SELECT slug FROM experiences ORDER BY sort_order, id'), 'slug')
        : ['cenote-museo', 'inframundo-maya', 'comida-en-cenote-huinik', 'cena-romantica-huinik', 'noches-de-xibalba'];
    $sitemap = sitemapUrls($BASE);
    say('   sitemap: ' . count($sitemap) . ' URLs');
    foreach ($slugs as $slug) {
        $exp = DB::one('SELECT * FROM experiences WHERE slug = ?', [$slug]);
        if (!$exp) {
            say("   ! $slug no existe en tu base de datos");
            continue;
        }
        $esUrl = $opts['map'][$slug] ?? findUrlForSlug($slug, $sitemap, $BASE);
        if (!$esUrl) {
            say("   ! $slug: no existe en el sitio actual" . (isset($opts['all']) ? ' (producto nuevo, se deja como está)' : " (usa --map=$slug=URL)"));
            continue;
        }
        say("-- $slug  ←  $esUrl");
        $esHtml = http($esUrl);
        if (!$esHtml) {
            say('   ! no se pudo leer');
            continue;
        }
        $es = parseProduct($esHtml, $esUrl);
        $enHtml = http(enUrl($esUrl, $BASE));
        $en = $enHtml ? parseProduct($enHtml, enUrl($esUrl, $BASE)) : null;

        // Fotos: og primero (portada), luego la galería
        $imgs = [];
        if ($es['og']) {
            $imgs[] = $es['og'];
        }
        foreach ($es['images'] as $i) {
            $imgs[] = $i;
        }
        $saved = [];
        foreach (array_slice($imgs, 0, 8) as $u) {
            $p = saveImage($u, 'productos/' . $slug);
            if ($p && !in_array($p, $saved, true)) {
                $saved[] = $p;
            }
        }
        // En uploads/ las rutas de la BD son relativas a public/uploads/
        $upd = ['updated_at' => $now];
        if ($saved) {
            $upd['hero_image'] = $saved[0];
            $upd['gallery'] = json_encode(array_slice($saved, 1));
        }
        DB::update('experiences', $upd, 'id = ?', [(int) $exp['id']]);

        upsertTr('experience_translations', 'experience_id', (int) $exp['id'], 'es', ['title' => $es['title'] !== '' ? preg_replace('/\s*[|–-].*$/u', '', $es['title']) : '', 'seo_title' => $es['seo_title'], 'seo_description' => $es['seo_description']] + $es['fields']);
        if ($en) {
            upsertTr('experience_translations', 'experience_id', (int) $exp['id'], 'en', ['title' => $en['title'], 'seo_title' => $en['seo_title'], 'seo_description' => $en['seo_description']] + $en['fields']);
        }
        say('   fotos: ' . count($saved) . ' · campos ES: ' . implode(',', array_keys($es['fields'])) . ($en ? ' · EN: ' . implode(',', array_keys($en['fields'])) : ' · sin versión EN'));
        $stats['products']++;
    }
}

// ================================================================ 3. BLOG
/** Contenido principal de un artículo (HTML completo de la página). */
function articleBody(DOMXPath $x): ?DOMNode
{
    foreach (['//*[@data-elementor-type="wp-post"]', '//*[' . hasClass('elementor-widget-theme-post-content') . ']', '//*[' . hasClass('entry-content') . ']', '//*[' . hasClass('post-content') . ']', '//article'] as $qq) {
        $r = q($x, $qq);
        if ($r) {
            return $r[0];
        }
    }
    return null;
}

if (in_array('blog', $ONLY, true)) {
    $all = isset($opts['all']) || $opts['posts'] === 'all';
    say($all ? '== Blog (todas las entradas)' : '== Blog (muestra)');
    $limit = $all ? PHP_INT_MAX : max(1, (int) $opts['posts']);
    $list = [];
    for ($page = 1; count($list) < $limit; $page++) {
        $json = http($BASE . '/wp-json/wp/v2/posts?per_page=' . min(100, $limit) . '&page=' . $page . '&_embed=1&orderby=date&order=desc');
        $chunk = $json ? json_decode($json, true) : null;
        if (!is_array($chunk) || !$chunk || isset($chunk['code'])) {
            break;
        }
        $list = array_merge($list, $chunk);
        if (count($chunk) < min(100, $limit)) {
            break;
        }
    }
    $list = array_slice($list, 0, $limit);
    if (!$list) {
        say('   ! no pude leer /wp-json/wp/v2/posts (¿REST API desactivada?)');
    }
    $total = count($list);
    // Categorías del blog (con jerarquía) y sus nombres en inglés
    $catMap = [];
    $cj = http($BASE . '/wp-json/wp/v2/categories?per_page=100&orderby=id&order=asc');
    $cl = $cj ? json_decode($cj, true) : null;
    if (is_array($cl) && $cl && !isset($cl['code'])) {
        $enNames = [];
        if ($t = http($BASE . '/en/blog/')) {
            $ex = dom($t);
            foreach (q($ex, '//a[contains(@href,"/en/blog/")]') as $a) {
                $seg = basename(rtrim((string) parse_url($a->getAttribute('href'), PHP_URL_PATH), '/'));
                $nm = trim((string) preg_replace('/\s*\(\d+\)\s*$/u', '', preg_replace('/\s+/u', ' ', $a->textContent)));
                if ($seg !== '' && $nm !== '' && !isset($enNames[$seg])) {
                    $enNames[$seg] = $nm;
                }
            }
        }
        foreach ($cl as $c) {
            if (in_array($c['slug'] ?? '', ['sin-categoria', 'uncategorized'], true)) {
                continue;
            }
            $row = DB::one('SELECT id FROM categories WHERE slug = ?', [$c['slug']]);
            $cid = $row ? (int) $row['id'] : DB::insert('categories', ['slug' => $c['slug'], 'parent_id' => null, 'sort_order' => (int) $c['id']]);
            $catMap[(int) $c['id']] = $cid;
            upsertTr('category_translations', 'category_id', $cid, 'es', ['name' => html_entity_decode((string) $c['name'])]);
            if (!empty($enNames[$c['slug']])) {
                upsertTr('category_translations', 'category_id', $cid, 'en', ['name' => $enNames[$c['slug']]]);
            }
        }
        foreach ($cl as $c) {
            if (isset($catMap[(int) $c['id']]) && !empty($c['parent']) && isset($catMap[(int) $c['parent']])) {
                DB::update('categories', ['parent_id' => $catMap[(int) $c['parent']]], 'id = ?', [$catMap[(int) $c['id']]]);
            }
        }
        say('   categorías: ' . count($catMap));
    }
    foreach ($list as $idx => $p) {
        $slug = (string) ($p['slug'] ?? '');
        if ($slug === '') {
            continue;
        }
        $pageUrl = (string) ($p['link'] ?? $BASE . '/blog/' . $slug . '/');
        say(sprintf('-- [%d/%d] %s', $idx + 1, $total, $slug));
        $imgUrl = (string) ($p['_embedded']['wp:featuredmedia'][0]['source_url'] ?? '');
        $img = $imgUrl ? saveImage($imgUrl, 'blog') : null;
        $date = date('Y-m-d H:i:s', strtotime((string) ($p['date'] ?? 'now')));
        $row = DB::one('SELECT id FROM posts WHERE slug = ?', [$slug]);
        $data = ['status' => 'published', 'image' => $img, 'published_at' => $date, 'updated_at' => $now];
        $pid = $row ? (int) $row['id'] : 0;
        if ($pid) {
            DB::update('posts', $data, 'id = ?', [$pid]);
        } else {
            $pid = DB::insert('posts', $data + ['slug' => $slug]);
        }
        DB::exec('DELETE FROM post_categories WHERE post_id = ?', [$pid]);
        foreach ((array) ($p['categories'] ?? []) as $wid) {
            if (isset($catMap[(int) $wid])) {
                DB::insert('post_categories', ['post_id' => $pid, 'category_id' => $catMap[(int) $wid]]);
            }
        }
        $title = trim(html_entity_decode(strip_tags((string) ($p['title']['rendered'] ?? ''))));
        $x = dom('<div id="r">' . (string) ($p['content']['rendered'] ?? '') . '</div>');
        $node = q($x, '//*[@id="r"]')[0] ?? null;
        $content = $node ? assemble(tidy(cleanNode($node, $pageUrl, 'blog')), false) : '';
        $ex = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) ($p['excerpt']['rendered'] ?? '')))));
        $ex = preg_replace('/\s*(\[…\]|\[&hellip;\]|Read more.*|Leer más.*)$/iu', '', $ex);
        upsertTr('post_translations', 'post_id', $pid, 'es', ['title' => $title, 'excerpt' => mb_strimwidth((string) $ex, 0, 280, '…'), 'content' => $content]);

        // EN: la página /en/… (TranslatePress) con el mismo slug
        $enHtml = http(enUrl($pageUrl, $BASE));
        $done = false;
        if ($enHtml) {
            $ex2 = dom($enHtml);
            $body = articleBody($ex2);
            $h1 = q($ex2, '//h1');
            if ($body && $h1) {
                $enTitle = trim(preg_replace('/\s+/u', ' ', $h1[0]->textContent));
                $enContent = assemble(tidy(cleanNode($body, enUrl($pageUrl, $BASE), 'blog')), false);
                $t = q($ex2, '//title');
                if ($enContent !== '') {
                    upsertTr('post_translations', 'post_id', $pid, 'en', [
                        'title' => $enTitle, 'content' => $enContent,
                        'excerpt' => mb_strimwidth(meta($ex2, 'og:description') ?: meta($ex2, 'description'), 0, 280, '…'),
                        'seo_title' => $t ? trim(preg_replace('/\s+/u', ' ', $t[0]->textContent)) : '',
                    ]);
                    $done = true;
                }
            }
        }
        say($done ? '   ES + EN' : '   solo ES (no pude leer la versión EN)');
        $stats['posts']++;
    }
}

// ================================================================ 4. PÁGINAS CLAVE
if (in_array('pages', $ONLY, true)) {
    say('== Páginas clave');
    // slug => [etiqueta ES, etiqueta EN, orden, oscuro?, ruta EN si difiere]
    $pages = [
        'inicio' => ['Inicio', 'Home', 0, 0, ''],
        'mapa-del-recorrido' => ['Mapa del recorrido', 'Tour map', 10, 0, ''],
        'romance' => ['Romance', 'Romance', 20, 0, ''],
        'bodasencenote' => ['Bodasencenote', 'Weddings', 30, 0, ''],
        'premios-zazil-tunich' => ['Premio nacional', 'National award', 40, 0, ''],
        'fundacion' => ['ONG', 'NGO', 50, 0, ''],
        'faq' => ['FAQs', 'FAQs', 100, 0, ''],
    ];
    $pageUrls = [];
    if (isset($opts['all'])) {
        $skip = ['carrito', 'finalizar-compra', 'mi-cuenta', 'cart', 'checkout', 'my-account', 'tienda', 'shop', 'reservaciones', 'blog', 'wp-login'];
        $px = http($BASE . '/page-sitemap.xml');
        if ($px && preg_match_all('#<loc>\s*([^<\s]+)\s*</loc>#', $px, $mm)) {
            foreach ($mm[1] as $u) {
                $path = trim((string) parse_url($u, PHP_URL_PATH), '/');
                if ($path === '' || str_starts_with($path, 'en/') || $path === 'en') {
                    continue;
                }
                $sl = basename($path);
                if (in_array($sl, $skip, true)) {
                    continue;
                }
                $pageUrls[$sl] = $u;
                if (!isset($pages[$sl])) {
                    $pages[$sl] = ['', '', 200, 0, ''];
                }
            }
        }
        say('   páginas encontradas en el sitemap: ' . count($pageUrls));
    }
    foreach ($pages as $slug => [$lEs, $lEn, $sort, $dark, $enPath]) {
        $esUrl = $slug === 'inicio' ? $BASE . '/' : ($pageUrls[$slug] ?? $BASE . '/' . $slug . '/');
        $enUrl = $slug === 'inicio' ? $BASE . '/en/' : (isset($pageUrls[$slug]) ? enUrl($pageUrls[$slug], $BASE) : $BASE . '/en/' . ($enPath ?: $slug) . '/');
        $parse = static function (string $url, string $imgDir) use ($slug) {
            $html = http($url);
            if (!$html) {
                return null;
            }
            $x = dom($html);
            $main = mainNode($x);
            if (!$main) {
                return null;
            }
            $h1 = q($x, '//h1');
            $title = $h1 ? trim(preg_replace('/\s+/u', ' ', $h1[0]->textContent)) : '';
            $bg = pageHero($x, $url);
            // el <h1> ya sale en la barra de título del sitio nuevo; no repetirlo en el cuerpo
            foreach (q($x, './/h1', $main) as $hh) {
                $hh->parentNode?->removeChild($hh);
            }
            $content = tidy(cleanNode($main, $url, $imgDir));
            if ($slug === 'inicio') {
                // solo una introducción corta: los primeros bloques
                preg_match_all('#<(p|h[2-4]|ul)\b.*?</\1>#is', $content, $m);
                $content = implode("\n", array_slice($m[0], 0, 6));
            }
            $content = assemble($content, $slug !== 'inicio');
            $t = q($x, '//title');
            return [
                'title' => $title !== '' ? $title : ($t ? trim(preg_replace('/\s*[|–-].*$/u', '', $t[0]->textContent)) : ''),
                'content' => $content,
                'og' => $bg ?: absUrl(meta($x, 'og:image'), $url),
                'seo_title' => $t ? trim(preg_replace('/\s+/u', ' ', $t[0]->textContent)) : '',
                'seo_description' => meta($x, 'og:description') ?: meta($x, 'description'),
            ];
        };
        say("-- $slug");
        $es = $parse($esUrl, 'paginas');
        if (!$es || $es['content'] === '') {
            say('   ! sin contenido ES (se omite)');
            continue;
        }
        $hero = $es['og'] && $slug !== 'inicio' ? saveImage($es['og'], 'paginas') : null;
        $existing = DB::one('SELECT id FROM pages WHERE slug = ?', [$slug]);
        $data = ['status' => 'published', 'dark' => $dark, 'show_in_nav' => ($slug === 'inicio' || $sort >= 200) ? 0 : 1, 'sort_order' => $sort, 'updated_at' => $now];
        if ($hero) {
            $data['hero_image'] = $hero;
        }
        $pid = $existing ? (int) $existing['id'] : 0;
        if ($pid) {
            DB::update('pages', $data, 'id = ?', [$pid]);
        } else {
            $pid = DB::insert('pages', $data + ['slug' => $slug]);
        }
        upsertTr('page_translations', 'page_id', $pid, 'es', ['title' => $es['title'] ?: $lEs, 'nav_label' => $lEs, 'content' => $es['content'], 'seo_title' => $es['seo_title'], 'seo_description' => $es['seo_description']]);
        $en = $parse($enUrl, 'paginas');
        if ($en && $en['content'] !== '') {
            upsertTr('page_translations', 'page_id', $pid, 'en', ['title' => $en['title'] ?: $lEn, 'nav_label' => $lEn, 'content' => $en['content'], 'seo_title' => $en['seo_title'], 'seo_description' => $en['seo_description']]);
            say('   ES + EN');
        } else {
            upsertTr('page_translations', 'page_id', $pid, 'en', ['nav_label' => $lEn]);
            say('   solo ES');
        }
        $stats['pages']++;
    }
}

say('');
say(sprintf('Listo: %d imágenes (%d fallidas), %d productos, %d entradas, %d páginas.', $stats['img'], $stats['img_fail'], $stats['products'], $stats['posts'], $stats['pages']));
say('Revisa en http://localhost:8091/ y en el panel (Páginas / Blog / Experiencias) y corrige lo que haga falta.');
