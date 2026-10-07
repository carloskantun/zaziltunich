<?php
declare(strict_types=1);

// Aplica cambios de esquema pendientes en una instalación existente: php bin/migrate.php
require __DIR__ . '/../app/bootstrap.php';

if (!App\Core\Config::installed()) {
    fwrite(STDERR, "Aún no está instalado.\n");
    exit(1);
}
foreach (['002_content', '003_categories'] as $f) {
    App\Core\Database::runSchema((string) file_get_contents(ROOT . '/database/' . $f . '.sql'));
}
echo "Migraciones aplicadas.\n";
