# Instrucciones para Codex y otros agentes

Este repositorio contiene el demo de Zazil Tunich. Lee `deploy/DEMO-WORKFLOW.md` antes de preparar cambios o intervenir en el servidor. Claude debe seguir las mismas instrucciones, enlazadas en `CLAUDE.md`.

- Conserva cambios existentes; revisa `git status` y el diff antes de editar. No uses `reset --hard`, `clean`, reinstalaciones ni sobrescribas configuración, uploads o bases de datos.
- Mantén el demo con noindex y reservas públicas bloqueadas. No habilites cobros, reservas reales ni el dominio de producción sin autorización explícita.
- No publiques secretos, sesiones, datos de clientes, claves SSH ni `storage/config.php` en código, documentación o salidas.
- Prepara cambios en una rama, ejecuta las comprobaciones del documento y revisa el diff. Fusionar y enviar a `main` publica automáticamente el demo; hazlo cuando esté dentro del alcance autorizado.
- Ejecuta Git y los scripts remotos como `zazilweb`, no como root. Conserva el bloque de PHP que cPanel añadió a `.htaccess`.
- Si cambia el funcionamiento del despliegue, actualiza también las instrucciones. Reporta las comprobaciones realizadas y cualquier limitación real.
