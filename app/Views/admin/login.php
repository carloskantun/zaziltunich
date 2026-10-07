<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Acceso · Zazil Tunich</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>"><link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>"></head>
<body class="login"><form method="post" action="<?= e(raw_url('admin/login')) ?>" class="login-box"><?= csrf_field() ?>
<h1>Zazil Tunich</h1><p>Panel de administración</p>
<?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
<label class="fld"><span>Correo</span><input type="email" name="email" required autofocus></label>
<label class="fld"><span>Contraseña</span><input type="password" name="password" required></label>
<button class="btn btn-lg">Entrar</button></form></body></html>
