<?php
use App\Core\I18n;
use App\Domain\{Contact, Settings};
$lang = I18n::lang();
$other = $lang === 'es' ? 'en' : 'es';
$path = $_SERVER['ZT_PATH'] ?? '/';
$site = Settings::get('site_name', 'Zazil Tunich');
$floating = Contact::links(t('contact.msg', ['exp' => $site, 'date' => '____']));
?>
<!doctype html>
<html lang="<?= e($lang) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? $site) ?> | <?= e($site) ?></title>
<?php if (!empty($description)): ?><meta name="description" content="<?= e($description) ?>"><?php endif; ?>
<link rel="alternate" hreflang="es" href="<?= e(url($path, 'es')) ?>">
<link rel="alternate" hreflang="en" href="<?= e(url($path, 'en')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<header class="site-header">
  <a class="brand" href="<?= e(url('/')) ?>"><?= e($site) ?></a>
  <nav>
    <a href="<?= e(url('/reservaciones')) ?>"><?= e(t('nav.experiences')) ?></a>
    <a class="lang" href="<?= e(url($path, $other)) ?>"><?= e(t('nav.lang_switch')) ?></a>
  </nav>
</header>
<main><?= $content ?></main>
<footer class="site-footer">
  <p>© <?= date('Y') ?> <?= e($site) ?>. <?= e(t('footer.rights')) ?></p>
</footer>
<?php if ($floating): ?>
<div class="float-contact">
  <?php foreach ($floating as $c): ?>
    <a class="fc fc-<?= e($c['key']) ?>" href="<?= e($c['href']) ?>" <?= $c['key'] === 'whatsapp' ? 'target="_blank" rel="noopener"' : '' ?>><?= e($c['label']) ?></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?= $scripts ?? '' ?>
</body>
</html>
