<?php
use App\Core\I18n;
use App\Domain\{Content, Settings};
$lang = I18n::lang();
$other = $lang === 'es' ? 'en' : 'es';
$path = $_SERVER['ZT_PATH'] ?? '/';
$site = Settings::get('site_name', 'Zazil Tunich');
$bodyClass = $bodyClass ?? 'light';
$floating = \App\Domain\Contact::links(t('contact.msg', ['exp' => $site, 'date' => '____']));
$nav = [['href' => url('/reservaciones'), 'label' => t('nav.experiences'), 'on' => str_starts_with($path, '/reservaciones')]];
$after = [];
foreach (Content::navPages() as $np) {
    $item = ['href' => url('/' . $np['slug']), 'label' => $np['label'], 'on' => $path === '/' . $np['slug']];
    if ($np['sort'] >= 100) { $after[] = $item; } else { $nav[] = $item; }
}
$nav[] = ['href' => url('/blog'), 'label' => t('nav.blog'), 'on' => str_starts_with($path, '/blog')];
$nav = array_merge($nav, $after);
$logo = is_file(ROOT . '/public/uploads/site/logo.png') ? raw_url('uploads/site/logo.png') : null;
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
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<header class="site-header">
  <a class="brand" href="<?= e(url('/')) ?>"><?php if ($logo): ?><img src="<?= e($logo) ?>" alt="<?= e($site) ?>"><?php else: ?><span><?= e(strtoupper($site)) ?></span><?php endif; ?></a>
  <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-label="<?= e(t('nav.menu')) ?>"><label for="nav-toggle" class="burger"><i></i><i></i><i></i></label>
  <nav>
    <?php foreach ($nav as $n): ?><a href="<?= e($n['href']) ?>"<?= $n['on'] ? ' class="on"' : '' ?>><?= e($n['label']) ?></a><?php endforeach; ?>
    <a class="lang" href="<?= e(url($path, $other)) ?>"><?= e(t('nav.lang_switch')) ?></a>
  </nav>
</header>
<main><?= $content ?></main>
<footer class="site-footer">
  <div class="foot-inner">
    <div><strong><?= e(strtoupper($site)) ?></strong><p><?= e(t('home.tagline')) ?></p></div>
    <div class="foot-links"><?php foreach ($nav as $n): ?><a href="<?= e($n['href']) ?>"><?= e($n['label']) ?></a><?php endforeach; ?></div>
  </div>
  <p class="copy">© <?= date('Y') ?> <?= e($site) ?>. <?= e(t('footer.rights')) ?></p>
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
