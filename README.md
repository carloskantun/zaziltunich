# Zazil Tunich — sitio y sistema de reservas propio

PHP 8.1+ y MySQL/MariaDB, sin frameworks ni Composer. Se instala subiendo una carpeta, igual que un WordPress.

## Qué incluye
- **Sitio público** ES (raíz) y EN (`/en`): inicio, listado, ficha con pestañas y widget de reserva, checkout, instrucciones de pago, voucher imprimible, botones de contacto (WhatsApp, llamada, correo, SMS).
- **Motor de reservas** con precios autoritativos en el servidor: por persona, escalonado, paquete, por noche, por variante; precios por fecha/día/hora; extras (lista, casillas, cantidad; manual o automático por personas); cobro total o anticipo con porcentaje por producto; cupos por horario, capacidad compartida, bloqueos, hospedaje por noche, eventos por fecha.
- **Panel** `/admin`: resumen, reservas (CRM con estados, pagos manuales, notas, reserva manual), calendario (mes / día / por producto), experiencias (crear, editar, **duplicar**), plantillas de horarios, grupos de extras, bloqueos, clientes, ajustes y usuarios (admin / operador / solo lectura).

## Instalar en Hostinger (hPanel)
1. **Bases de datos → MySQL**: crea una base y un usuario; anota nombre, usuario y contraseña.
2. **Administrador de archivos**: sube el contenido de este repositorio a `public_html` (el `.htaccess` de la raíz ya envía todo a `public/` y deja `app/`, `storage/` y `database/` inaccesibles).
3. Permisos de escritura en `storage/` y `public/uploads/` (755 suele bastar).
4. Abre `https://tu-dominio/install/`, llena base de datos, URL y administrador. Marca «Cargar el catálogo actual» para sembrar las 15 experiencias, extras y horarios.
5. Entra a `/admin`. **Borra la carpeta `public/install`** al terminar.
6. En Ajustes: WhatsApp, teléfono, correo e instrucciones de pago.

Instalación por consola (desarrollo): `php bin/install.php --driver=sqlite --path=storage/dev.sqlite --url=http://localhost:8080 --email=tu@correo.com --password=minimo10caracteres` y `php -S localhost:8080 -t public public/index.php`.

## Pruebas
`php tests/run.php` (SQLite en memoria) o contra MySQL: `ZT_MYSQL_DB=base ZT_MYSQL_USER=u ZT_MYSQL_PASS=p php tests/run.php` (**borra las tablas de esa base**).

## Estado y pendientes
- Pagos: solo **pago manual** (la reserva queda pendiente y el equipo registra el pago en el panel). Stripe, PayPal y MercadoPago están por hacer sobre `app/Payments/GatewayInterface.php`; requieren credenciales para probarse.
- Pendiente: correos automáticos de confirmación, constructor de páginas/blog, importador desde WordPress, redirecciones SEO, SMS automático, cupones.
- Los tokens de diseño (colores, radios) están en `public/assets/css/app.css` (`:root`) y son una primera aproximación: hay que calibrarlos contra el sitio actual.
- Datos por confirmar con el cliente: número de cabañas y horarios de entrada/salida, variantes y precio del evento del 14 de feb 2027 (queda en borrador), y si el anticipo incluye extras (hoy sí).
