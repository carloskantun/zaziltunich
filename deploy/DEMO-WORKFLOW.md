# Trabajo y actualización del demo

## Entorno verificado el 7 de octubre de 2026

- Repositorio: `carloskantun/zaziltunich`; rama publicada: `main`.
- Carpeta local: `/Users/programacion/Documents/GitHub/zaziltunich`.
- Vista previa: https://zazil.serviciomultimedia.com/.
- Servidor SSH: `VPS-KANTUN`; cuenta cPanel y propietaria: `zazilweb`.
- Aplicación: `/home/zazilweb/public_html`; Apache sirve mediante los archivos `.htaccess`.
- PHP de consola observado: `/usr/local/bin/php`, 8.4.26.
- Commit local y remoto observado: `40a48e1447208f7832ad070140056138e62bf179`.
- El servidor conserva una modificación local de `.htaccess`: bloque generado por cPanel para `ea-php84`. También existe `error_log` sin seguimiento. No borrarlos ni incorporarlos automáticamente a Git.
- La portada respondió HTTP 200 con `X-Robots-Tag: noindex, nofollow, noarchive`. `is_preview_host()` reconoce este subdominio y `PublicController::confirm()` rechaza reservas públicas con 403 antes de crearlas.

Esto es un demo. Mantener esas protecciones; una salida a producción requiere una solicitud explícita y una revisión independiente. No ejecutar instalación ni importación de contenido como parte de una actualización habitual.

## Preparar y probar cambios

1. Leer `AGENTS.md`, revisar `git status --short`, rama y diff. Si hay trabajo ajeno, conservarlo y separar el cambio propio.
2. Con el checkout limpio, actualizar `main` con `git pull --ff-only` y crear una rama descriptiva: `git switch -c mejora/nombre-del-cambio`. No modificar ni publicar directamente `main` por costumbre.
3. Implementar el alcance solicitado. Configuración, datos, logs y uploads están fuera de Git; no añadirlos a la fuerza.
4. Ejecutar `php tests/run.php`: usa SQLite en memoria. No definir variables `ZT_MYSQL_*` contra el demo ni una base compartida: esa modalidad borra tablas.
5. Validar sintaxis de cada PHP modificado con `php -l ruta/al/archivo.php`. Si se tocaron scripts: `bash -n deploy/update.sh deploy/auto-update.sh`.
6. Para revisar la interfaz, usar una instalación local aislada con SQLite y datos ficticios siguiendo el README; nunca copiar credenciales del VPS. Revisar ES y EN, escritorio y móvil, enlaces, imágenes y el flujo afectado.
7. Revisar `git diff --check`, el diff final y los archivos a incluir. Hacer un commit solo de los cambios propios y abrir un PR hacia `main` cuando corresponda. El PR debe describir el resultado, las pruebas y cualquier migración.

## Fusionar y publicar

Enviar o fusionar cambios en `main` dispara la publicación del demo en el siguiente ciclo del cron. Verificar antes que la publicación esté autorizada por la tarea y que las pruebas pasen. No usar force push. Registrar el SHA final de `main` después de fusionar (un squash puede cambiar el SHA de la rama).

No hace falta subir archivos por FTP ni ejecutar el actualizador manualmente para la ruta normal. El cron observado del usuario `zazilweb` es:

```cron
*/5 * * * * /usr/bin/flock -n /home/zazilweb/.zazil-deploy.lock /bin/bash /home/zazilweb/public_html/deploy/auto-update.sh >/dev/null 2>&1
```

`auto-update.sh` obtiene `origin/main`, compara su SHA con HEAD y, si difieren, ejecuta `update.sh`. Este hace `git pull --ff-only` y `php bin/migrate.php`. El log es `storage/logs/deploy.log`; `flock` evita solapar ejecuciones del cron. Cuando no hay cambios, el script sale sin escribir al log. El log estaba vacío en la inspección inicial: aún no se verificó un ciclo con un commit nuevo.

## Verificar la publicación automática

Esperar el siguiente ciclo de cinco minutos y consultar el servidor sin ejecutar el despliegue manual: así se comprueba el cron. Si se accede por el alias SSH como root, cambiar al propietario para todas las operaciones de Git:

```sh
ssh VPS-KANTUN
runuser -u zazilweb -- bash -lc '
  cd /home/zazilweb/public_html
  git branch --show-current
  git status --short
  git rev-parse HEAD
  git rev-parse origin/main
  tail -40 storage/logs/deploy.log
  crontab -l
'
```

HEAD y `origin/main` deben coincidir con el SHA publicado en GitHub; la rama debe seguir siendo `main`. Para el cambio nuevo, el log debe mostrar migraciones aplicadas y `Listo:` sin errores. Revisar en el navegador la modificación concreta y ES/EN. Comprobar además:

```sh
curl -sS -I https://zazil.serviciomultimedia.com/
curl -sS -o /dev/null -w '%{http_code}\n' -X POST https://zazil.serviciomultimedia.com/checkout/confirmar
curl -sS -o /dev/null -w '%{http_code}\n' -X POST https://zazil.serviciomultimedia.com/en/checkout/confirmar
```

La portada debe devolver 200 y noindex; ambas confirmaciones deben devolver 403. No enviar datos personales ni crear reservas reales para verificar. Si cambian esas protecciones, detener la publicación y corregir antes de continuar.

## Si no se actualiza

- Revisar cron, log, rama, SHA y diff local. Un cambio en `.htaccess` puede impedir un pull que también toque ese archivo. Preparar una integración explícita conservando el bloque de cPanel; no resolver con un reset.
- Un error de acceso a GitHub requiere revisar la deploy key de solo lectura sin mostrar su contenido privado. El remoto del VPS usa SSH; el local observado usa HTTPS.
- `update.sh` no es un despliegue atómico ni revierte código si falla una migración. Si el pull avanzó HEAD pero la migración falló, el cron siguiente verá SHA iguales y no reintentará. Revisar y corregir la causa; después aplicar la migración como `zazilweb` bajo el mismo lock. No interpretar solo la igualdad de SHA como éxito.
- El actualizador manual debe usar el mismo lock para no competir con el cron:

```sh
runuser -u zazilweb -- /usr/bin/flock -n /home/zazilweb/.zazil-deploy.lock /bin/bash /home/zazilweb/public_html/deploy/update.sh
```

- Antes de cambios de esquema o recuperación, obtener un respaldo de la base, uploads y configuración fuera del directorio público mediante los medios del servidor. No registrar claves en comandos compartidos. Las migraciones actuales están enumeradas explícitamente en `bin/migrate.php`; añadir un archivo SQL no lo ejecuta automáticamente.
- Para revertir código publicado, preparar un commit de reversión revisado en GitHub y dejar que el cron lo aplique. Una reversión de código no deshace cambios de datos o esquema: revisar compatibilidad y restauración antes de actuar.

No cambiar DNS, servicios, cron ni otros sitios del VPS como parte de una mejora del demo. `DEPLOY-VPS.md` contiene una guía genérica de instalación; este documento es la referencia de la instalación actual.

## Publicación de contenido verificada el 8 de octubre de 2026

La vista local de `localhost:8091` se sirve desde `/Users/programacion/Projects/zaziltunich`, una carpeta diferente del checkout de GitHub. Comparar ambas antes de publicar: el checkout de GitHub ya contiene mejoras de código y protecciones del demo que esa copia local no tiene. No reemplazarlo entero por la copia local.

Se publicaron 29 páginas, 150 entradas, 16 experiencias y 681 archivos de uploads de la copia local, conservando la configuración, usuarios y ajustes de contacto/pago del servidor. Hay respaldos privados fuera del directorio público en `/home/zazilweb/demo-backups`. El contenido publicable está versionado en `demo/content.json`; ver `demo/README.md` para transferir imágenes y aplicar los datos explícitamente.

El cron de las 12:20 (Cancún) actualizó automáticamente hasta `515dcec`; el log confirmó migraciones y `Listo:`. Después se aplicó el snapshot bajo el mismo lock. Se verificaron portada ES/EN, todas las páginas del menú, blog, producto, logo y foto de portada con HTTP 200. La cabecera noindex y el bloqueo 403 de confirmación ES/EN se conservaron. Se comprobó visualmente la portada y se compararon hashes de los 681 uploads sin diferencias.
