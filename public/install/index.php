<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

use App\Core\Config;
use App\Domain\Installer;

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$uriPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/install/', PHP_URL_PATH);
$guessBase = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim((string) preg_replace('#/install(/index\.php)?/?$#', '', $uriPath), '/');

$h = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$checks = [
    'PHP 8.1 o superior (tienes ' . PHP_VERSION . ')' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'Extensión pdo_mysql' => extension_loaded('pdo_mysql'),
    'Extensión mbstring' => extension_loaded('mbstring'),
    'storage/ con permiso de escritura' => is_writable(ROOT . '/storage'),
    'public/uploads/ con permiso de escritura' => is_writable(ROOT . '/public/uploads'),
];
$optional = ['Extensión GD con WebP (optimiza imágenes)' => function_exists('imagewebp')];
$error = null;
$done = false;

if (Config::installed()) {
    $done = true;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string) ($_POST['_csrf'] ?? '');
    if ($csrf === '' || !hash_equals(csrf_token(), $csrf)) {
        $error = 'La sesión expiró; recarga la página.';
    } elseif (in_array(false, $checks, true)) {
        $error = 'Corrige los requisitos marcados antes de instalar.';
    } else {
        $error = Installer::run(
            ['driver' => 'mysql', 'host' => trim((string) $_POST['db_host']), 'port' => 3306, 'name' => trim((string) $_POST['db_name']), 'user' => trim((string) $_POST['db_user']), 'pass' => (string) $_POST['db_pass']],
            trim((string) $_POST['base_url']), trim((string) $_POST['admin_name']), trim((string) $_POST['admin_email']), (string) $_POST['admin_pass'],
            isset($_POST['seed'])
        );
        $done = $error === null;
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Instalación · Zazil Tunich</title>
<style>body{font-family:system-ui,sans-serif;background:#143528;margin:0;padding:24px}main{background:#fff;max-width:560px;margin:auto;border-radius:14px;padding:28px}h1{margin-top:0}label{display:block;margin:12px 0 4px;font-weight:600;font-size:14px}input{width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;font-size:15px;box-sizing:border-box}button{margin-top:18px;background:#1f4d3a;color:#fff;border:0;border-radius:99px;padding:12px 26px;font-size:16px;cursor:pointer}.ok{color:#14683b}.bad{color:#b3261e}.warn{color:#8a6200}.err{background:#fdecea;padding:10px;border-radius:8px}ul{padding-left:18px}small{color:#666}</style></head><body><main>
<h1>Instalar Zazil Tunich</h1>
<?php if ($done): ?>
  <p class="ok"><strong>Listo.</strong> El sistema está instalado y el instalador quedó bloqueado.</p>
  <p><a href="<?= $h(rtrim((string) Config::get('base_url', $guessBase), '/')) ?>/admin">Entrar al panel →</a></p>
  <p><small>Por seguridad, borra la carpeta <code>public/install</code> del servidor.</small></p>
<?php else: ?>
  <ul><?php foreach ($checks as $label => $ok): ?><li class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? '✓' : '✗' ?> <?= $h($label) ?></li><?php endforeach; ?>
  <?php foreach ($optional as $label => $ok): ?><li class="<?= $ok ? 'ok' : 'warn' ?>"><?= $ok ? '✓' : '!' ?> <?= $h($label) ?></li><?php endforeach; ?></ul>
  <?php if ($error): ?><p class="err"><?= $h($error) ?></p><?php endif; ?>
  <form method="post"><input type="hidden" name="_csrf" value="<?= $h(csrf_token()) ?>">
    <h3>Base de datos MySQL (hPanel → Bases de datos)</h3>
    <label>Servidor</label><input name="db_host" value="<?= $h($_POST['db_host'] ?? 'localhost') ?>" required>
    <label>Nombre de la base</label><input name="db_name" value="<?= $h($_POST['db_name'] ?? '') ?>" required>
    <label>Usuario</label><input name="db_user" value="<?= $h($_POST['db_user'] ?? '') ?>" required>
    <label>Contraseña</label><input type="password" name="db_pass" autocomplete="off">
    <h3>Sitio</h3>
    <label>URL del sitio (sin barra final)</label><input name="base_url" value="<?= $h($_POST['base_url'] ?? $guessBase) ?>" required>
    <h3>Administrador</h3>
    <label>Nombre</label><input name="admin_name" value="<?= $h($_POST['admin_name'] ?? '') ?>" required>
    <label>Correo</label><input type="email" name="admin_email" value="<?= $h($_POST['admin_email'] ?? '') ?>" required>
    <label>Contraseña (mínimo 10 caracteres)</label><input type="password" name="admin_pass" minlength="10" required autocomplete="new-password">
    <label style="font-weight:400"><input type="checkbox" name="seed" value="1" checked style="width:auto"> Cargar el catálogo actual de Zazil Tunich (15 experiencias, extras y horarios)</label>
    <button>Instalar</button>
  </form>
<?php endif; ?>
</main></body></html>
