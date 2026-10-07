<?php
$p = $_SERVER['ZT_PATH'] ?? '';
$nav = [
  ['admin', 'Resumen'], ['admin/reservas', 'Reservas'], ['admin/calendario', 'Calendario'], ['admin/experiencias', 'Experiencias'],
  ['admin/horarios', 'Horarios'], ['admin/extras', 'Extras'], ['admin/bloqueos', 'Bloqueos'], ['admin/clientes', 'Clientes'], ['admin/ajustes', 'Ajustes'],
];
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($title ?? 'Panel') ?> · Panel Zazil Tunich</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head><body class="admin">
<aside class="side">
  <a class="brand" href="<?= e(raw_url('admin')) ?>">Zazil Tunich</a>
  <nav>
    <?php foreach ($nav as [$href, $label]):
        $on = $p === '/' . $href || ($href !== 'admin' && str_starts_with($p, '/' . $href)); ?>
      <a href="<?= e(raw_url($href)) ?>"<?= $on ? ' class="on"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
    <a href="<?= e(url('/')) ?>" target="_blank">Ver sitio ↗</a>
  </nav>
  <form method="post" action="<?= e(raw_url('admin/logout')) ?>" class="who"><?= csrf_field() ?>
    <small><?= e($user['name']) ?> · <?= e($user['role']) ?></small><button class="link">Salir</button></form>
</aside>
<main class="adm-main">
  <?php if (!empty($flash)): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
  <h1><?= e($title ?? '') ?></h1>
  <?= $content ?>
</main>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
<?= $scripts ?? '' ?>
</body></html>
