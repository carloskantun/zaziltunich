# Despliegue en VPS (subdominio) — Zazil Tunich

Ejemplo con `nuevo.zaziltunich.com`. Cambia el dominio y las rutas a tu gusto.

## 0. Requisitos del servidor
PHP 8.1+ con extensiones `pdo_mysql`, `mbstring`, `curl`, `dom`, `gd` (con WebP), `fileinfo`; MySQL/MariaDB; Nginx o Apache; `git`.

## 1. DNS y carpeta
- DNS: registro **A** `nuevo` → IP del VPS.
- `sudo mkdir -p /var/www/zaziltunich && sudo chown $USER /var/www/zaziltunich`

## 2. Código desde GitHub (repo privado)
Usa una *deploy key* de solo lectura:
```
ssh-keygen -t ed25519 -f ~/.ssh/zt_deploy -N ""
cat ~/.ssh/zt_deploy.pub     # GitHub → repo → Settings → Deploy keys → Add (sin escritura)
printf 'Host gh-zt\n  HostName github.com\n  User git\n  IdentityFile ~/.ssh/zt_deploy\n' >> ~/.ssh/config
git clone gh-zt:carloskantun/zaziltunich.git /var/www/zaziltunich
```

## 3. Base de datos
```
sudo mysql -e "CREATE DATABASE zaziltunich CHARACTER SET utf8mb4; CREATE USER 'zt'@'localhost' IDENTIFIED BY 'CLAVE_FUERTE'; GRANT ALL ON zaziltunich.* TO 'zt'@'localhost';"
```

## 4. Instalar (por consola)
```
cd /var/www/zaziltunich
php bin/install.php --driver=mysql --host=localhost --db=zaziltunich --user=zt --pass='CLAVE_FUERTE' \
  --url=https://nuevo.zaziltunich.com --email=tu@correo.com --password='minimo10caracteres'
```
Crea `storage/config.php` (no está en git). Permisos: `sudo chown -R www-data:www-data storage public/uploads`.

## 5. Servidor web (la raíz del sitio es `public/`)
**Nginx**
```
server {
  server_name nuevo.zaziltunich.com;
  root /var/www/zaziltunich/public;
  index index.php;
  client_max_body_size 20m;
  location / { try_files $uri $uri/ /index.php?$query_string; }
  location ~ \.php$ { include snippets/fastcgi-php.conf; fastcgi_pass unix:/run/php/php8.2-fpm.sock; }
  location ~* \.(css|js|webp|png|jpe?g|svg|woff2)$ { expires 30d; access_log off; }
  location ~ /\.(?!well-known) { deny all; }
}
```
**Apache**: `DocumentRoot /var/www/zaziltunich/public`, `AllowOverride All` (el `public/.htaccess` ya enruta).

HTTPS: `sudo certbot --nginx -d nuevo.zaziltunich.com` (o `--apache`).

Borra `public/install` en producción: `rm -rf public/install`.

## 6. Contenido (importador, desde el propio VPS)
`public/uploads/` no está en git. Desde el VPS, que sí llega a zaziltunich.com:
```
php bin/import-site.php --all
```
(usa `--only=pages --all` para solo páginas). Descarga fotos a `public/uploads/`.

## 7. Actualizar después de cada cambio
```
bash deploy/update.sh
```
Hace `git pull`, aplica migraciones y limpia nada más: `uploads/` y `storage/config.php` no se tocan.

## 8. Copias de seguridad
- Base: `mysqldump zaziltunich | gzip > ~/zt-$(date +%F).sql.gz`
- Archivos: `public/uploads/` y `storage/config.php`.

## 9. Antes de apuntar el dominio real
- Poner `noindex` mientras sea subdominio de pruebas (Ajustes o `X-Robots-Tag` en el servidor).
- Redirecciones SEO desde las URLs antiguas (pendiente).
- Pagos: hoy solo manual.
