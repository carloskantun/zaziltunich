<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\Config;
use App\Core\Database as DB;

/** Lógica común del instalador web y del instalador por línea de comandos. */
final class Installer
{
    /**
     * @param array $db  driver, host, name, user, pass | path (sqlite)
     * @return string|null mensaje de error o null si todo salió bien
     */
    public static function run(array $db, string $baseUrl, string $adminName, string $adminEmail, string $adminPass, bool $seedCatalog): ?string
    {
        if (Config::installed()) {
            return 'El sistema ya está instalado.';
        }
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPass) < 10) {
            return 'Correo inválido o contraseña menor a 10 caracteres.';
        }
        try {
            DB::connect($db);
            if (DB::value("SELECT 1 FROM " . (DB::isMysql() ? 'information_schema.tables WHERE table_schema = DATABASE() AND table_name' : "sqlite_master WHERE type = 'table' AND name") . " = 'users'")) {
                return 'La base de datos ya contiene tablas del sistema.';
            }
            DB::runSchema((string) file_get_contents(ROOT . '/database/schema.sql'));
            Config::set(['base_url' => rtrim($baseUrl, '/'), 'timezone' => 'America/Merida', 'db' => $db]);
            Seeder::settings();
            Seeder::admin($adminName, $adminEmail, $adminPass);
            if ($seedCatalog) {
                Seeder::catalog();
            }
        } catch (\Throwable $e) {
            return 'Error de instalación: ' . $e->getMessage();
        }
        $php = "<?php\nreturn " . var_export([
            'base_url' => rtrim($baseUrl, '/'), 'timezone' => 'America/Merida', 'db' => $db,
        ], true) . ";\n";
        if (file_put_contents(ROOT . '/storage/config.php', $php, LOCK_EX) === false) {
            return 'No se pudo escribir storage/config.php (revisa permisos).';
        }
        @chmod(ROOT . '/storage/config.php', 0600);
        file_put_contents(ROOT . '/storage/installed.lock', date('c'));
        return null;
    }
}
