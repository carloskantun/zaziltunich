#!/usr/bin/env bash
# Actualiza el sitio desde GitHub y aplica migraciones. Ejecutar en la raíz del proyecto.
set -euo pipefail
cd "$(dirname "$0")/.."
git pull --ff-only
php bin/migrate.php
echo "Listo: $(git log -1 --format='%h %s')"
