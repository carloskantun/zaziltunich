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
<?php if (is_preview_host()): ?><meta name="robots" content="noindex,nofollow,noarchive"><?php endif; ?>
<title><?= e($title ?? $site) ?> | <?= e($site) ?></title>
<?php if (!empty($description)): ?><meta name="description" content="<?= e($description) ?>"><?php endif; ?>
<link rel="alternate" hreflang="es" href="<?= e(url($path, 'es')) ?>">
<link rel="alternate" hreflang="en" href="<?= e(url($path, 'en')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Amiri:wght@400;700&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<?php if (is_preview_host()): ?><div role="status" style="background:#173c2d;color:#fff;text-align:center;padding:10px 16px;font:600 14px/1.4 system-ui,sans-serif">Versión de revisión para el cliente · No se aceptan reservas reales</div><?php endif; ?>
<header class="site-header">
  <a class="brand" href="<?= e(url('/')) ?>"><?php if ($logo): ?><img src="<?= e($logo) ?>" alt="<?= e($site) ?>"><?php else: ?><span><?= e(strtoupper($site)) ?></span><?php endif; ?></a>
  <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-label="<?= e(t('nav.menu')) ?>"><label for="nav-toggle" class="burger"><i></i><i></i><i></i></label>
  <nav>
    <?php foreach ($nav as $n): ?><a href="<?= e($n['href']) ?>"<?= $n['on'] ? ' class="on"' : '' ?>><?= e($n['label']) ?></a><?php endforeach; ?>
    <a class="lang" href="<?= e(url($path, $other)) ?>"><?php if ($flag = site_file('flags/' . $other . '.png')): ?><img src="<?= e($flag) ?>" alt="" width="20" height="15"><?php endif; ?><?= e(t('nav.lang_switch')) ?></a>
  </nav>
</header>
<main><?= $content ?></main>
<?php $si = site_info(); $badge = site_file('badge-tripadvisor.png'); $taUrl = $si['social']['tripadvisor'] ?? '#'; ?>
<footer class="site-footer">
  <div class="foot-grid">
    <div class="foot-brand">
      <?php if ($logo): ?><img class="foot-logo" src="<?= e($logo) ?>" alt="<?= e($site) ?>"><?php else: ?><strong><?= e(strtoupper($site)) ?></strong><?php endif; ?>
      <p><?= e(t('footer.tagline')) ?></p>
      <div class="social"><?php foreach ($si['social'] as $ic => $href): ?><a href="<?= e($href) ?>" target="_blank" rel="noopener" aria-label="<?= e($ic) ?>"><?= icon($ic) ?></a><?php endforeach; ?></div>
    </div>
    <div class="foot-contact">
      <h4><?= e(t('footer.contact')) ?></h4>
      <ul>
        <li><?= icon('envelope') ?><a href="mailto:<?= e($si['email']) ?>"><?= e($si['email']) ?></a></li>
        <li><?= icon('whatsapp') ?><a href="<?= e($si['wa']) ?>" target="_blank" rel="noopener"><?= e($si['phone']) ?></a></li>
        <li><?= icon('location-dot') ?><span><?= e($si['address']) ?></span></li>
      </ul>
      <ul class="legal"><li><a href="<?= e(url('/terminos-y-condiciones')) ?>"><?= e(t('footer.terms')) ?></a></li><li><a href="<?= e(url('/politica-de-privacidad')) ?>"><?= e(t('footer.privacy')) ?></a></li><li><a class="chk" href="<?= e(url('/contacto')) ?>"><?= icon('check') ?><?= e(t('footer.contactpage')) ?></a></li><li><a class="chk" href="<?= e(url('/como-llegar')) ?>"><?= icon('check') ?><?= e(t('footer.howto')) ?></a></li></ul>
    </div>
    <div class="foot-ta">
      <p><?= e(t('footer.recommended')) ?></p>
      <?php if ($badge): ?><a href="<?= e($taUrl) ?>" target="_blank" rel="noopener"><img src="<?= e($badge) ?>" alt="TripAdvisor" loading="lazy"></a><?php endif; ?>
    </div>
  </div>
  <div class="copy"><p><?= e(t('footer.copy')) ?> <?= date('Y') ?> <?= e($site) ?></p></div>
</footer>
<?php if ($floating): ?>
<div class="float-contact">
  <?php foreach ($floating as $c): ?>
    <a class="fc fc-<?= e($c['key']) ?>" href="<?= e($c['href']) ?>" <?= $c['key'] === 'whatsapp' ? 'target="_blank" rel="noopener"' : '' ?>><?= e($c['label']) ?></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<script src="<?= e(asset('js/site.js')) ?>" defer></script>
<?= $scripts ?? '' ?>
</body>
</html>
