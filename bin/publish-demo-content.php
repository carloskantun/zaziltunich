<?php
declare(strict_types=1);

// Publica solo contenido del demo. No importa usuarios, clientes, reservas o credenciales.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../app/bootstrap.php';

use App\Core\{Config, Database as DB};
use App\Domain\Settings;

if (rtrim((string) Config::get('base_url'), '/') !== 'https://zazil.serviciomultimedia.com') {
    throw new RuntimeException('Este comando solo admite el subdominio de revisión.');
}
if (DB::value('SELECT COUNT(*) FROM bookings') || DB::value('SELECT COUNT(*) FROM customers')) {
    throw new RuntimeException('Hay reservas o clientes: requiere una integración de contenido sin reemplazo de catálogo.');
}
$tables = ['capacity_groups', 'experiences', 'experience_translations', 'price_tiers', 'price_overrides', 'variants', 'schedule_templates', 'schedule_template_times', 'experience_schedules', 'event_dates', 'extra_groups', 'extra_options', 'experience_extra_groups', 'pages', 'page_translations', 'posts', 'post_translations', 'categories', 'category_translations', 'post_categories'];
$payload = json_decode((string) file_get_contents(ROOT . '/demo/content.json'), true, 512, JSON_THROW_ON_ERROR);
if (($payload['format'] ?? null) !== 1 || array_keys($payload['tables'] ?? []) !== $tables) {
    throw new RuntimeException('Snapshot de contenido no válido.');
}
// Respaldo completo de datos fuera del directorio público, con permisos privados.
$backupDir = dirname(ROOT) . '/demo-backups';
if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true)) {
    throw new RuntimeException('No se pudo crear el directorio de respaldo.');
}
$backup = [];
$dbTables = DB::isMysql() ? array_column(DB::all('SHOW TABLES'), array_key_first(DB::all('SHOW TABLES')[0])) : array_column(DB::all("SELECT name FROM sqlite_master WHERE type='table'"), 'name');
foreach ($dbTables as $table) {
    $backup[$table] = DB::all('SELECT * FROM `' . str_replace('`', '``', $table) . '`');
}
$backupFile = $backupDir . '/data-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.json';
$oldMask = umask(0077);
$written = file_put_contents($backupFile, json_encode($backup, JSON_THROW_ON_ERROR));
umask($oldMask);
if ($written === false) {
    throw new RuntimeException('No se pudo guardar el respaldo.');
}

DB::tx(static function () use ($tables, $payload): void {
    // Bloqueos existentes no se deben invalidar al reemplazar experiencias.
    if (DB::value('SELECT COUNT(*) FROM blocks')) {
        throw new RuntimeException('Hay bloqueos: revisar antes de reemplazar catálogo.');
    }
    foreach (array_reverse($tables) as $table) {
        DB::exec('DELETE FROM `' . $table . '`');
    }
    foreach ($tables as $table) {
        foreach ($payload['tables'][$table] as $row) {
            DB::insert($table, $row);
        }
    }
    foreach ($payload['settings'] as $name => $value) {
        if (!str_starts_with($name, 'home_')) {
            throw new RuntimeException('Solo se admiten ajustes de portada.');
        }
        Settings::set($name, $value);
    }
});
foreach ($tables as $table) {
    echo $table . ': ' . DB::value('SELECT COUNT(*) FROM `' . $table . '`') . PHP_EOL;
}
echo 'Contenido publicado; respaldo privado: ' . basename($backupFile) . PHP_EOL;
