# Contenido de la vista previa

`content.json` registra el catálogo, páginas, blog y ajustes de portada de la instalación local revisada el 8 de octubre de 2026. No contiene configuración, usuarios, reservas, clientes ni instrucciones de pago.

Las imágenes de `public/uploads/` requieren transferencia separada: el cron solo publica código. Conservar siempre un respaldo fuera del directorio público y transferir sin borrar archivos existentes. Después de publicar el código y las imágenes, ejecutar como `zazilweb`, bajo el lock del cron:

```sh
/usr/bin/flock -n /home/zazilweb/.zazil-deploy.lock /usr/local/bin/php /home/zazilweb/public_html/bin/publish-demo-content.php
```

El comando requiere la URL exacta del demo y ausencia de reservas, clientes y bloqueos; respalda los datos fuera del directorio público y reemplaza el contenido dentro de una transacción. Conserva accesos, configuración y ajustes de contacto/pago del servidor. Si ya existen operaciones, preparar una integración por registros en lugar de reemplazar el catálogo.

El cron no ejecuta este comando automáticamente: un merge de código no debe sobrescribir cambios de contenido realizados desde el panel. Actualizar el snapshot explícitamente cuando se solicite publicar nuevos datos locales.

Verificar portada con logo, cuatro fotos, introducción, menú completo y galería; páginas ES/EN, blog y fotos de productos. Confirmar que noindex sigue presente y que POST a `/checkout/confirmar` y `/en/checkout/confirmar` devuelve 403. Mantener esas restricciones de vista previa.
