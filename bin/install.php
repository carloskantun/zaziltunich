<?php
declare(strict_types=1);

// Uso: php bin/install.php --driver=sqlite --path=storage/dev.sqlite --url=http://localhost:8080 --email=a@b.com --password=xxxxxxxxxx [--no-seed]
//      php bin/install.php --driver=mysql --host=localhost --db=nombre --user=usuario --pass=clave --url=https://zaziltunich.com --email=... --password=...
require __DIR__ . '/../app/bootstrap.php';

$o = getopt('', ['driver:', 'path:', 'host:', 'db:', 'user:', 'pass:', 'url:', 'email:', 'password:', 'name:', 'no-seed']);
$driver = $o['driver'] ?? 'mysql';
$db = $driver === 'sqlite'
    ? ['driver' => 'sqlite', 'path' => $o['path'] ?? ROOT . '/storage/dev.sqlite']
    : ['driver' => 'mysql', 'host' => $o['host'] ?? 'localhost', 'port' => 3306, 'name' => $o['db'] ?? '', 'user' => $o['user'] ?? '', 'pass' => $o['pass'] ?? ''];
$err = App\Domain\Installer::run($db, $o['url'] ?? 'http://localhost:8080', $o['name'] ?? 'Administrador', $o['email'] ?? '', $o['password'] ?? '', !isset($o['no-seed']));
fwrite($err ? STDERR : STDOUT, ($err ?? 'Instalación completa.') . "\n");
exit($err ? 1 : 0);
